# Auditoria: Remoção Melhor Envio → Correios Direto

## 📋 Checklist de Mudanças

### 🔴 Crítico (Funcional)

| Arquivo | Ação | Prioridade |
|---------|------|------------|
| `src/Services/Shipping/MelhorEnvioLabelService.php` | **RENOMEAR** para `CorreiosLabelService.php` + **SUBSTITUIR** implementação API Melhor Envio → API Correios | ALTA |
| `src/Services/Shipping/Providers/CorreiosProvider.php` | **SUBSTITUIR** implementação: remover API Melhor Envio, implementar API Correios (SIGEPWeb/API Preços) | ALTA |
| `src/Http/Controllers/Admin/OrderController.php` | **SUBSTITUIR** import `MelhorEnvioLabelService` → `CorreiosLabelService` + atualizar chamadas | ALTA |
| `database/migrations/045_add_shipping_fields_to_pedidos.php` | **ATUALIZAR** comentário: remover "melhor_envio" | MÉDIA |

### 🟡 Importante (Configuração/UI)

| Arquivo | Ação | Prioridade |
|---------|------|------------|
| `themes/default/admin/gateways/index-content.php` | **VERIFICAR** se há referência a Melhor Envio no HTML (já parece estar comentado) | BAIXA |
| Configuração `tenant_gateways.config_json` | **ATUALIZAR** documentação: remover referências a token Melhor Envio, adicionar credenciais Correios | MÉDIA |

### 🟢 Documentação (Não Bloqueia)

| Arquivo | Ação | Prioridade |
|---------|------|------------|
| `docs/DIAGNOSTICO_INTEGRACAO_CORREIOS.md` | **ATUALIZAR** seções que mencionam Melhor Envio (manter histórico mas marcar como obsoleto) | BAIXA |
| `docs/ANALISE_CALCULO_FRETE_PRODUTOS.md` | **ATUALIZAR** referências a Melhor Envio | BAIXA |
| `docs/GATEWAYS_INTEGRACAO.md` | **ATUALIZAR** exemplos removendo Melhor Envio | BAIXA |
| Outros docs | **REVISAR** e atualizar conforme necessário | BAIXA |

---

## 🎯 Plano de Execução

### Passo 1: Renomear e Substituir Label Service ⚠️ AGUARDANDO IMPLEMENTAÇÃO API CORREIOS
- [ ] Criar `CorreiosLabelService.php` com estrutura base
- [ ] Implementar métodos usando API Correios (aguardar definição da API do cliente)
- [ ] Atualizar OrderController para usar CorreiosLabelService
- [ ] Deletar `MelhorEnvioLabelService.php`

### Passo 2: Substituir CorreiosProvider ⚠️ AGUARDANDO IMPLEMENTAÇÃO API CORREIOS
- [ ] Remover chamadas API Melhor Envio
- [ ] Implementar API Correios (SIGEPWeb/API Preços conforme contrato)
- [ ] Manter interface ShippingProviderInterface
- [ ] Testar cálculo de frete

### Passo 3: Atualizar Referências
- [ ] Atualizar imports no OrderController
- [ ] Atualizar comentários/mensagens de erro
- [ ] Atualizar migration

### Passo 4: Limpeza Final
- [ ] Buscar todas as ocorrências: `grep -r "MelhorEnvio\|melhor_envio\|Melhor Envio" --exclude-dir=vendor --exclude-dir=node_modules`
- [ ] Remover/atualizar todas as referências encontradas
- [ ] Validar que não há mais nenhuma ocorrência

---

## ⚠️ Dependências Externas

**Para completar a implementação, é necessário:**
1. Credenciais da API dos Correios (usuário/senha ou token conforme contrato)
2. Documentação da API a ser usada:
   - SIGEPWeb (pré-postagem)
   - API Preços (cálculo)
   - API Prazos (prazo)
3. Formato esperado de resposta da API dos Correios

**Enquanto isso:**
- Estrutura de classes mantida
- Métodos criados com placeholders/throws
- Comentários indicando "Aguardando implementação API Correios"

---

**Data:** Janeiro 2025  
**Status:** ⚠️ Aguardando definição da API Correios do cliente
