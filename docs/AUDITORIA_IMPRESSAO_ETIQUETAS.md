# Auditoria: Sistema de Impressão de Etiquetas Correios

**Data:** $(date)  
**Status:** ⚠️ **Aguardando API Correios (placeholder)**  
**Objetivo:** Mapear o que existe hoje e definir o padrão para quando a API estiver implementada.

---

## 1. O QUE EXISTE HOJE

### 1.1 OrderController::imprimirEtiqueta()

**Rota:** `GET /admin/pedidos/{id}/frete/imprimir-etiqueta`

**Comportamento atual:**
```php
// 1. Busca pedido (valida multi-tenant)
// 2. Verifica se tem label_url OU tracking_code
// 3. Se tiver label_url → REDIRECT para a URL (header Location)
// 4. Se não tiver URL mas tiver tracking → ERRO "url_etiqueta_indisponivel"
```

**✅ O que funciona:**
- Validação de pedido e multi-tenant
- Redirect simples quando `label_url` existe
- Mensagem de erro quando etiqueta não gerada

**⚠️ O que falta:**
- Stream de PDF binário (quando `label_url` for endpoint interno)
- Suporte a `label_pdf_path` (arquivo local)
- Seletor de formato (A4 / 10x15)
- Fallback quando API não disponível

---

### 1.2 Campos no Banco (Migration 045)

**Tabela:** `pedidos`

| Campo | Tipo | Uso Atual | Status |
|-------|------|-----------|--------|
| `shipping_provider` | VARCHAR(50) | `'correios'` quando gerado | ✅ Usado |
| `tracking_code` | VARCHAR(100) | Código OB (ex: "BR123456789BR") | ✅ Usado |
| `label_url` | TEXT | URL externa ou endpoint interno | ✅ Usado |
| `documento_envio` | ENUM | `'declaracao_conteudo'` / `'nota_fiscal'` | ✅ Usado |
| `nf_reference` | VARCHAR(255) | Referência da NF (opcional) | ✅ Usado |

**⚠️ Campos que FALTAM (sugeridos):**
- `label_id` / `postagem_id` → ID interno da postagem nos Correios
- `label_pdf_path` → Caminho local do PDF (se armazenado)
- `label_format` → `'A4'` ou `'10x15'` (preferência de impressão)
- `label_generated_at` → Data/hora de geração (já existe na migration original, mas não está sendo usado)

**Observação:** A migration original `045_add_shipping_fields_to_pedidos.php` tinha `label_generated_at`, mas foi removido. Pode ser necessário adicionar de volta.

---

### 1.3 CorreiosLabelService (Placeholder)

**Método:** `createShipmentFromOrder()`

**Retorno esperado:**
```php
[
    'postagem_id' => string,      // ID interno Correios
    'tracking_code' => string,    // Código OB
    'label_url' => string,        // URL ou caminho do PDF
    'service_code' => string,     // '40126' (PAC) ou '40096' (SEDEX)
    'service_name' => string,     // 'PAC' ou 'SEDEX'
]
```

**Status:** ⚠️ **Placeholder** - métodos `criarPostagem()` e `gerarEtiqueta()` lançam `\Exception`

---

### 1.4 Idempotência (Gerar Etiqueta)

**Método:** `OrderController::gerarEtiqueta()`

**Verificação atual:**
```php
// Se já tem tracking_code, NÃO gera novamente
if (!empty($pedido['tracking_code'])) {
    $this->redirect("/admin/pedidos/{$id}?error=etiqueta_ja_gerada");
    return;
}
```

**✅ Funciona:** Previne duplicação de postagem

**⚠️ Melhorias sugeridas:**
- Mensagem mais clara: "Etiqueta já gerada" + botão "Imprimir Etiqueta"
- Salvar `label_generated_at` para auditoria

---

## 2. CONTRATO DE IMPRESSÃO (Padrão)

### 2.1 Opção A (RECOMENDADA): Correios Retorna PDF Pronto

**Como funciona:**
1. `CorreiosLabelService::gerarEtiqueta()` recebe `postagem_id`
2. Chama API Correios para obter PDF da etiqueta
3. API retorna PDF em um dos formatos:
   - **URL temporária** (ex: `https://api.correios.gov.br/etiquetas/{id}.pdf`)
   - **Base64** (string base64 do PDF)
   - **Binário** (bytes do PDF)

**Sistema salva:**
- `label_url` → URL externa (se vier URL) OU endpoint interno `/admin/pedidos/{id}/etiqueta/pdf`
- `label_pdf_path` → Caminho local (se salvar arquivo)
- `tracking_code` → Código OB

**`imprimirEtiqueta()`:**
- Se `label_url` é externa → Redirect
- Se `label_url` é endpoint interno → Stream do PDF local
- Se `label_pdf_path` existe → Stream do arquivo

**✅ Vantagens:**
- PDF já formatado pelo Correios (padrão oficial)
- Sem necessidade de montar layout
- QR Code/Barcode já gerados
- Suporta A4 e 10x15 conforme API entregar

**⚠️ Desvantagens:**
- Depende do formato que a API entrega
- Se URL expira, precisa baixar e armazenar localmente

---

### 2.2 Opção B (FALLBACK): Sistema Monta PDF

**Quando usar:** Se a API Correios não entregar PDF pronto, apenas dados (endereços, códigos, etc)

**Como funciona:**
1. `CorreiosLabelService::gerarEtiqueta()` recebe dados da postagem
2. Sistema gera PDF usando Dompdf/FPDF
3. Salva `label_pdf_path` localmente
4. `imprimirEtiqueta()` faz stream do arquivo

**⚠️ Desvantagens:**
- Layout precisa seguir padrão dos Correios (manual)
- QR Code/Barcode precisa ser gerado manualmente (biblioteca adicional)
- Mais complexo e propenso a erros

**Recomendação:** Usar **APENAS** se a API não entregar PDF pronto.

---

### 2.3 DECISÃO: Opção A (PDF Pronto)

**Justificativa:**
- Padrão de mercado (Melhor Envio, ShipStation, etc entregam PDF pronto)
- Menos manutenção
- Conformidade com padrão Correios

**Implementação sugerida:**
```php
// CorreiosLabelService::gerarEtiqueta()
$response = /* chamada API Correios */;

// Se API retornar URL
if (isset($response['pdf_url'])) {
    $labelUrl = $response['pdf_url'];
}
// Se API retornar base64
elseif (isset($response['pdf_base64'])) {
    $pdfPath = self::salvarPdfLocal($response['pdf_base64'], $postagemId);
    $labelUrl = "/admin/pedidos/{$pedidoId}/etiqueta/pdf";
}
// Se API retornar binário
elseif (isset($response['pdf_binary'])) {
    $pdfPath = self::salvarPdfLocal($response['pdf_binary'], $postagemId);
    $labelUrl = "/admin/pedidos/{$pedidoId}/etiqueta/pdf";
}

return [
    'tracking_code' => $response['tracking_code'],
    'label_url' => $labelUrl,
    'label_pdf_path' => $pdfPath ?? null,
];
```

---

## 3. FORMATO DE IMPRESSÃO (A4 vs 10x15)

### 3.1 Formatos Comuns Correios

| Formato | Uso | Impressora |
|---------|-----|------------|
| **A4** (210x297mm) | 2 etiquetas por folha | Impressora comum |
| **10x15** (100x150mm) | Etiqueta térmica única | Impressora térmica |

### 3.2 Recomendação

**Default:** A4 (folha comum - mais comum)

**Implementação:**
1. Adicionar campo `label_format` na tabela `pedidos` (ENUM: 'A4', '10x15')
2. Seletor na UI do admin (quando gerar etiqueta ou imprimir)
3. Preferência por tenant (opcional, salvar em `tenant_gateways.config_json`)

**Quando a API for implementada:**
- Se API suportar escolha de formato → usar `label_format`
- Se API não suportar → ocultar seletor e usar formato padrão da API

---

## 4. MELHORIAS SUGERIDAS (AGORA)

### 4.1 Campos Adicionais na Migration

```php
// Adicionar em 045_add_shipping_fields_to_pedidos.php (se não existir)
'label_id' => "VARCHAR(100) NULL COMMENT 'ID da postagem nos Correios'",
'label_pdf_path' => "VARCHAR(255) NULL COMMENT 'Caminho local do PDF (se armazenado)'",
'label_format' => "ENUM('A4', '10x15') NULL DEFAULT 'A4' COMMENT 'Formato de impressão preferido'",
'label_generated_at' => "DATETIME NULL COMMENT 'Data/hora de geração da etiqueta'",
```

### 4.2 Melhorar `imprimirEtiqueta()`

**Comportamento sugerido:**
```php
public function imprimirEtiqueta(int $id): void
{
    // 1. Buscar pedido (valida multi-tenant)
    
    // 2. Se NÃO tem etiqueta gerada
    if (empty($pedido['tracking_code']) && empty($pedido['label_url'])) {
        $this->redirect("/admin/pedidos/{$id}?error=etiqueta_nao_gerada");
        return;
    }
    
    // 3. Se tem label_pdf_path (arquivo local) → Stream
    if (!empty($pedido['label_pdf_path']) && file_exists($pedido['label_pdf_path'])) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="etiqueta-pedido-' . $pedido['numero_pedido'] . '.pdf"');
        readfile($pedido['label_pdf_path']);
        exit;
    }
    
    // 4. Se label_url é endpoint interno → Stream do serviço
    if (!empty($pedido['label_url']) && strpos($pedido['label_url'], '/admin/pedidos/') === 0) {
        // Buscar PDF via CorreiosLabelService ou endpoint específico
        $pdfContent = CorreiosLabelService::getLabelPdf($pedido['label_id'], $config);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="etiqueta-pedido-' . $pedido['numero_pedido'] . '.pdf"');
        echo $pdfContent;
        exit;
    }
    
    // 5. Se label_url é externa → Redirect
    if (!empty($pedido['label_url'])) {
        header('Location: ' . $pedido['label_url']);
        exit;
    }
    
    // 6. Fallback: erro claro
    $this->redirect("/admin/pedidos/{$id}?error=etiqueta_indisponivel_api_pendente");
}
```

### 4.3 Melhorar Mensagem de "Etiqueta Já Gerada"

**Comportamento sugerido:**
```php
// OrderController::gerarEtiqueta()
if (!empty($pedido['tracking_code'])) {
    $_SESSION['order_message'] = 'Etiqueta já foi gerada anteriormente.';
    $_SESSION['order_message_type'] = 'info';
    $this->redirect("/admin/pedidos/{$id}?info=etiqueta_ja_gerada");
    return;
}
```

**UI mostrar:**
- Mensagem: "Etiqueta já gerada em [data]"
- Botão "Imprimir Etiqueta" visível
- Tracking code exibido

---

## 5. CHECKLIST DE TESTES (Quando API Estiver Pronta)

### 5.1 Geração de Etiqueta
- [ ] Gerar etiqueta em pedido com endereço completo
- [ ] Verificar `tracking_code` salvo corretamente
- [ ] Verificar `label_url` ou `label_pdf_path` salvo
- [ ] Verificar idempotência (não gerar novamente)
- [ ] Verificar `label_generated_at` preenchido

### 5.2 Impressão A4
- [ ] Abrir PDF da etiqueta
- [ ] Imprimir em folha A4
- [ ] Verificar: não corta bordas
- [ ] Verificar: QR Code/Barcode legível
- [ ] Verificar: remetente/destinatário completos
- [ ] Verificar: código de rastreamento visível

### 5.3 Impressão 10x15 (se suportado)
- [ ] Selecionar formato 10x15
- [ ] Gerar etiqueta
- [ ] Abrir PDF
- [ ] Imprimir em impressora térmica 10x15
- [ ] Verificar: tamanho correto
- [ ] Verificar: código de rastreamento legível

### 5.4 Erros e Validações
- [ ] Testar sem remetente configurado → erro claro
- [ ] Testar sem endereço completo → erro claro
- [ ] Testar sem itens → erro claro
- [ ] Testar com CEP inválido → erro claro

---

## 6. RESUMO EXECUTIVO

### ✅ O que está OK hoje:
- Pipeline básico de impressão (redirect quando `label_url` existe)
- Validação de multi-tenant
- Idempotência (não gera etiqueta duplicada)
- Campos básicos no banco (`tracking_code`, `label_url`, `shipping_provider`)

### ⚠️ O que precisa melhorar:
1. **Suporte a stream de PDF** (quando `label_url` for endpoint interno)
2. **Campo `label_pdf_path`** (para armazenar PDF local)
3. **Campo `label_format`** (A4 / 10x15)
4. **Campo `label_id`** (ID da postagem nos Correios)
5. **Mensagem clara** quando etiqueta já gerada (com botão imprimir)
6. **Fallback claro** quando API não disponível

### 🎯 Próximos Passos:
1. **Agora (sem API):**
   - Adicionar campos faltantes na migration
   - Melhorar `imprimirEtiqueta()` com suporte a stream
   - Melhorar mensagem "etiqueta já gerada"

2. **Quando API estiver definida:**
   - Implementar `CorreiosLabelService::gerarEtiqueta()` (Opção A: PDF pronto)
   - Testar formato de PDF (A4 vs 10x15)
   - Implementar endpoint de stream `/admin/pedidos/{id}/etiqueta/pdf` (se necessário)

---

## 7. OBSERVAÇÕES IMPORTANTES

⚠️ **Não é possível afirmar que "as etiquetas estão OK no padrão"** porque:
- O padrão depende do formato de PDF que a API Correios entregará
- O sistema atual só suporta redirect para URL externa
- Falta suporte a stream de PDF (endpoint interno)

✅ **O que está garantido:**
- Pipeline de impressão pronto (redirect funciona)
- Idempotência implementada
- Validações básicas funcionando
- Estrutura extensível para quando API estiver pronta

**Recomendação final:** Aguardar definição da API Correios para confirmar formato de PDF e implementar suporte completo (stream, formato, armazenamento).
