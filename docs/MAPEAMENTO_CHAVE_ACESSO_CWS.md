# Mapeamento: Chave de Acesso CWS - Gateway Correios

## 📋 Resumo Executivo

**Status Atual:** ❌ **NÃO existe campo específico para "Chave de Acesso CWS"**

O campo "Senha" está sendo usado para armazenar a senha da API, mas **não há distinção** entre:
- Senha do SFE (Sistema de Franquia Eletrônica)
- Chave de Acesso CWS (Correios Web Services)

---

## 🔍 Mapeamento dos Campos Existentes

### 1. Campo "Usuário"

| Propriedade | Valor |
|------------|-------|
| **Label na UI** | "Usuário *" |
| **Nome do campo HTML** | `correios_usuario` |
| **Tipo** | `text` (input de texto) |
| **Localização na UI** | Seção "B) Credenciais do Contrato Correios" (linha 223) |
| **Variável no Backend** | `$credenciais['usuario']` |
| **Processamento** | `GatewayConfigController::processarConfigCorreios()` (linha 190) |
| **Armazenamento** | `tenant_gateways.config_json` → `{"correios": {"credenciais": {"usuario": "..."}}}` |
| **Obrigatório** | ✅ Sim |

### 2. Campo "Senha"

| Propriedade | Valor |
|------------|-------|
| **Label na UI** | "Senha *" |
| **Nome do campo HTML** | `correios_senha` |
| **Tipo** | `password` (input de senha) |
| **Localização na UI** | Seção "B) Credenciais do Contrato Correios" (linha 235) |
| **Variável no Backend** | `$credenciais['senha']` |
| **Processamento** | `GatewayConfigController::processarConfigCorreios()` (linha 191-210) |
| **Armazenamento** | `tenant_gateways.config_json` → `{"correios": {"credenciais": {"senha": "..."}}}` |
| **Obrigatório** | ✅ Sim |
| **Recursos Especiais** | - Mascaramento ao carregar (mostra `********` se já existe)<br>- Mantém senha anterior se campo vazio ao salvar |

### 3. Campos Opcionais do Contrato

| Campo | Nome HTML | Variável Backend | Localização |
|-------|-----------|-----------------|-------------|
| Código Administrativo | `correios_codigo_administrativo` | `$credenciais['codigo_administrativo']` | Linha 258 |
| Cartão de Postagem | `correios_cartao_postagem` | `$credenciais['cartao_postagem']` | Linha 264 |
| Contrato | `correios_contrato` | `$credenciais['contrato']` | Linha 270 |
| Diretoria/Unidade | `correios_diretoria` | `$credenciais['diretoria']` | Linha 276 |

---

## 📁 Estrutura de Armazenamento

### Banco de Dados

**Tabela:** `tenant_gateways`

**Coluna:** `config_json` (TEXT/JSON)

**Estrutura JSON atual:**
```json
{
  "correios": {
    "origem": {
      "cep": "01310100",
      "nome": "Nome da Loja",
      "telefone": "11999999999",
      "documento": "12345678000190",
      "endereco": {
        "logradouro": "Rua Exemplo",
        "numero": "123",
        "bairro": "Centro",
        "cidade": "São Paulo",
        "uf": "SP"
      }
    },
    "credenciais": {
      "usuario": "usuario_correios",
      "senha": "senha_atual",
      "cartao_postagem": "",
      "contrato": "",
      "codigo_administrativo": "",
      "diretoria": ""
    },
    "servicos": {
      "pac": true,
      "sedex": true
    },
    "seguro": {
      "habilitado": false
    }
  }
}
```

### Arquivos Envolvidos

1. **UI (Formulário):**
   - `themes/default/admin/gateways/index-content.php` (linhas 215-284)

2. **Backend (Controller):**
   - `src/Http/Controllers/Admin/GatewayConfigController.php`
     - Método `index()`: Carrega e decodifica config (linhas 45-57)
     - Método `store()`: Salva configurações (linhas 73-171)
     - Método `processarConfigCorreios()`: Processa campos específicos (linhas 181-292)

3. **Services (Uso das Credenciais):**
   - `src/Services/Shipping/Providers/CorreiosProvider.php` (linhas 97-105)
   - `src/Services/Shipping/CorreiosLabelService.php` (linhas 48-54)

---

## ✅ Recomendação: Onde Adicionar Campo "Chave de Acesso CWS"

### Localização Sugerida

**Seção:** "B) Credenciais do Contrato Correios"  
**Posição:** Logo após o campo "Senha", antes do `<details>` de "Campos Opcionais"  
**Linha aproximada:** Após linha 249, antes da linha 251

### Estrutura Proposta

```php
<!-- Campo: Chave de Acesso CWS -->
<div class="form-group" style="margin-top: 1rem;">
    <label for="correios_chave_acesso_cws">Chave de Acesso CWS (Correios) *</label>
    <input 
        type="password" 
        id="correios_chave_acesso_cws" 
        name="correios_chave_acesso_cws" 
        value="<?= !empty($credenciais['chave_acesso_cws_masked']) ? '' : htmlspecialchars($credenciais['chave_acesso_cws'] ?? '') ?>"
        placeholder="<?= !empty($credenciais['chave_acesso_cws_masked']) ? '******** (digite para alterar)' : 'Chave de Acesso gerada no portal CWS' ?>"
        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
        required>
    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
        ⓘ Esta é a chave técnica gerada no portal Correios CWS, vinculada às APIs Preço v3, Prazo v3 e CEP v3.
        <br>Não confundir com a senha do SFE. Esta chave será usada para gerar o TOKEN automaticamente.
    </small>
    <?php if (!empty($credenciais['chave_acesso_cws_masked'])): ?>
        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
            Chave atual mantida. Digite apenas se desejar alterar.
        </small>
    <?php endif; ?>
</div>
```

### Modificações Necessárias no Backend

#### 1. `GatewayConfigController::processarConfigCorreios()`

**Adicionar após linha 191:**
```php
// Ler chave de acesso CWS
$chaveAcessoCwsNova = trim($post['correios_chave_acesso_cws'] ?? '');

// Se chave vazia ou mascarada, manter a anterior (se existir)
$chaveAcessoCws = '';
if (empty($chaveAcessoCwsNova) || $chaveAcessoCwsNova === '********' || strlen($chaveAcessoCwsNova) < 3) {
    // Buscar chave anterior do banco
    $configAtual = [];
    if (!empty($shippingGateway['config_json'])) {
        $decoded = json_decode($shippingGateway['config_json'], true);
        if (is_array($decoded)) {
            $correiosAtual = $decoded['correios'] ?? $decoded;
            if (isset($correiosAtual['credenciais']['chave_acesso_cws']) && !empty($correiosAtual['credenciais']['chave_acesso_cws'])) {
                $chaveAcessoCws = $correiosAtual['credenciais']['chave_acesso_cws'];
            }
        }
    }
} else {
    // Usar chave nova
    $chaveAcessoCws = $chaveAcessoCwsNova;
}
```

**Adicionar validação após linha 225:**
```php
// Validar chave de acesso CWS: deve ter chave nova OU chave anterior no banco
if (empty($chaveAcessoCws)) {
    $temChaveAnterior = false;
    if (!empty($shippingGateway['config_json'])) {
        $decoded = json_decode($shippingGateway['config_json'], true);
        if (is_array($decoded)) {
            $correiosAtual = $decoded['correios'] ?? $decoded;
            if (isset($correiosAtual['credenciais']['chave_acesso_cws']) && !empty($correiosAtual['credenciais']['chave_acesso_cws'])) {
                $temChaveAnterior = true;
            }
        }
    }
    if (!$temChaveAnterior) {
        $errors[] = 'Chave de Acesso CWS é obrigatória.';
    }
}
```

**Adicionar no array `$config['credenciais']` (linha 267):**
```php
'credenciais' => [
    'usuario' => $usuario,
    'senha' => $senha,
    'chave_acesso_cws' => $chaveAcessoCws,  // ← ADICIONAR
    'cartao_postagem' => trim($post['correios_cartao_postagem'] ?? ''),
    // ... resto dos campos
],
```

#### 2. `GatewayConfigController::index()`

**Adicionar mascaramento da chave CWS (após linha 52):**
```php
// Mascarar chave de acesso CWS ao carregar
if (isset($shippingConfig['credenciais']['chave_acesso_cws']) && !empty($shippingConfig['credenciais']['chave_acesso_cws'])) {
    $shippingConfig['credenciais']['chave_acesso_cws_masked'] = true;
    $shippingConfig['credenciais']['chave_acesso_cws'] = '********';
}
```

### Nova Estrutura JSON Proposta

```json
{
  "correios": {
    "credenciais": {
      "usuario": "usuario_correios",
      "senha": "senha_sfe",
      "chave_acesso_cws": "chave_tecnica_cws_gerada_no_portal",
      "cartao_postagem": "",
      "contrato": "",
      "codigo_administrativo": "",
      "diretoria": ""
    }
  }
}
```

---

## 🔄 Fluxo de Uso da Chave de Acesso CWS

### 1. Armazenamento
- Usuário preenche "Chave de Acesso CWS" no formulário
- Valor é salvo em `credenciais.chave_acesso_cws` no JSON

### 2. Geração de Token (a implementar)
- Backend usa `usuario` + `chave_acesso_cws` para autenticação Basic
- Faz POST em `https://api.correios.com.br/token/v1/autentica`
- Recebe TOKEN temporário
- Usa TOKEN nas chamadas de Preço v3, Prazo v3, CEP v3

### 3. Serviços que Precisarão Acessar
- `CorreiosProvider::consultarCorreios()` - Para cotação de frete
- `CorreiosLabelService::criarPostagem()` - Para criação de postagem
- Futuro serviço de geração de token (a criar)

---

## 📝 Notas Importantes

1. **Distinção Clara:**
   - **Senha:** Senha do SFE (se ainda for necessária)
   - **Chave de Acesso CWS:** Chave técnica gerada no portal CWS (usada para gerar TOKEN)

2. **Segurança:**
   - Ambos os campos devem ser do tipo `password` na UI
   - Ambos devem ser mascarados ao carregar (mostrar `********`)
   - Ambos devem manter valor anterior se campo vazio ao salvar

3. **Compatibilidade:**
   - Se o campo não existir no JSON antigo, o sistema deve funcionar normalmente
   - Validação deve verificar se existe chave anterior OU chave nova

4. **Documentação:**
   - Adicionar tooltip/help text explicando a diferença entre "Senha" e "Chave de Acesso CWS"
   - Referenciar o portal CWS onde a chave é gerada

---

## ✅ Checklist de Implementação

- [ ] Adicionar campo `correios_chave_acesso_cws` na UI (após campo "Senha")
- [ ] Adicionar label descritivo com tooltip explicativo
- [ ] Implementar mascaramento da chave ao carregar (similar à senha)
- [ ] Modificar `processarConfigCorreios()` para processar o novo campo
- [ ] Adicionar validação da chave de acesso CWS
- [ ] Atualizar estrutura JSON para incluir `chave_acesso_cws`
- [ ] Criar serviço de geração de TOKEN usando a chave
- [ ] Atualizar `CorreiosProvider` para usar TOKEN nas chamadas
- [ ] Atualizar `CorreiosLabelService` para usar TOKEN nas chamadas
- [ ] Testar fluxo completo: salvar → carregar → usar → gerar token

---

**Data do Mapeamento:** 2024  
**Arquivos Analisados:** 3 arquivos principais  
**Status:** ✅ Mapeamento completo - Pronto para implementação
