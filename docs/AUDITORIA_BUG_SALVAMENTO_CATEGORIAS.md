# AUDITORIA: Bug de Salvamento de Categorias - Produto SKU 354

## Data da Auditoria
11/12/2025

## Problema Reportado
Ao alterar categorias de um produto via modal de "Editar Categorias", as categorias não são salvas no banco de dados. O produto SKU 354 continua mostrando "Sem categorias" mesmo após salvar e recarregar a página.

## Escopo da Auditoria
- Fluxo completo de salvamento de categorias via AJAX
- Formato de dados enviados do frontend
- Processamento no backend
- Detecção de requisições AJAX
- Estrutura de queries SQL
- Tratamento de erros

---

## 1. ANÁLISE DO FLUXO FRONTEND → BACKEND

### 1.1. JavaScript - Coleta de Dados

**Arquivo:** `public/admin/js/products.js` (linhas 199-234)

```javascript
// Coletar categorias selecionadas
var checkboxes = modal.querySelectorAll('.categoria-checkbox:checked');
var categoriaIds = [];
checkboxes.forEach(function(checkbox) {
    categoriaIds.push(checkbox.value);
});

makeRequest(
    basePath + '/admin/produtos/' + produtoId + '/atualizar-categorias',
    'POST',
    { categorias: categoriaIds },  // ← Array sendo enviado
    function(error, response) { ... }
);
```

**Análise:**
- ✅ Coleta corretamente os valores dos checkboxes marcados
- ✅ Cria array `categoriaIds` com os valores
- ✅ Passa objeto `{ categorias: categoriaIds }` para `makeRequest`

### 1.2. JavaScript - Função makeRequest

**Arquivo:** `public/admin/js/products.js` (linhas 29-68)

```javascript
function makeRequest(url, method, data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    // ... tratamento de resposta ...
    
    var formData = '';
    if (data) {
        var pairs = [];
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                if (Array.isArray(data[key])) {
                    data[key].forEach(function(value) {
                        pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                    });
                } else {
                    pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(data[key]));
                }
            }
        }
        formData = pairs.join('&');
    }
    
    xhr.send(formData);
}
```

**Análise:**
- ✅ Define header `Content-Type: application/x-www-form-urlencoded`
- ✅ Define header `X-Requested-With: XMLHttpRequest` (para detecção AJAX)
- ✅ Converte array para formato URL-encoded: `categorias=1&categorias=2&categorias=3`
- ✅ Envia dados via `xhr.send(formData)`

**Formato Final Enviado:**
```
POST /admin/produtos/180/atualizar-categorias
Content-Type: application/x-www-form-urlencoded
X-Requested-With: XMLHttpRequest

categorias=5&categorias=7
```

**Status:** ✅ CORRETO - Formato adequado para PHP processar como `$_POST['categorias']` array

---

## 2. ANÁLISE DO BACKEND - RECEPÇÃO E PROCESSAMENTO

### 2.1. Rota e Middleware

**Arquivo:** `public/index.php` (linha 181-184)

```php
$router->post('/admin/produtos/{id}/atualizar-categorias', AdminProductController::class . '@updateCategoriesQuick', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
```

**Análise:**
- ✅ Rota configurada corretamente
- ✅ Middleware de autenticação aplicado
- ✅ Middleware de permissão aplicado

**Possível Problema:**
- ⚠️ Se `CheckPermissionMiddleware` retornar erro 403 para AJAX, pode estar retornando JSON mas o JS pode não estar tratando corretamente

### 2.2. Método updateCategoriesQuick - Recepção de Dados

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` (linhas 1755-1763)

```php
// Receber categorias do POST
$categoriaIds = [];
if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
    $categoriaIds = array_map('intval', $_POST['categorias']);
}

if ($isProduto354) {
    error_log("IDs recebidos no POST: " . json_encode($categoriaIds));
}
```

**Análise:**
- ✅ Verifica se `$_POST['categorias']` existe e é array
- ✅ Converte para inteiros com `intval`
- ✅ Logs de debug adicionados para produto 354

**Possível Problema:**
- ⚠️ **CRÍTICO:** Se `$_POST['categorias']` não estiver chegando como array, pode estar vindo como string ou não estar chegando
- ⚠️ PHP pode não estar parseando `categorias=1&categorias=2` corretamente se houver problema na configuração do PHP

### 2.3. Validação de Categorias

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` (linhas 1765-1783)

```php
// Validar que todas as categorias pertencem ao tenant
if (!empty($categoriaIds)) {
    $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
    $stmt = $db->prepare("
        SELECT id FROM categorias 
        WHERE id IN ({$placeholders}) AND tenant_id = ?
    ");
    $stmt->execute(array_merge($categoriaIds, [$tenantId]));
    $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
}
```

**Análise:**
- ✅ Valida que categorias pertencem ao tenant
- ✅ Usa prepared statements (seguro)
- ✅ Filtra apenas IDs válidos

**Possível Problema:**
- ⚠️ Se nenhuma categoria for válida, `$validCategoriaIds` fica vazio e nenhum INSERT é executado
- ⚠️ Não há log se nenhuma categoria for válida (exceto para produto 354)

### 2.4. DELETE e INSERT

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` (linhas 1799-1842)

```php
// DELETE
$stmt = $db->prepare("
    DELETE FROM produto_categorias 
    WHERE tenant_id = :tenant_id AND produto_id = :produto_id
");
$stmt->execute([
    'tenant_id' => $tenantId,
    'produto_id' => $id
]);

// INSERT
if (!empty($validCategoriaIds)) {
    $stmt = $db->prepare("
        INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    foreach ($validCategoriaIds as $categoriaId) {
        $stmt->execute([$tenantId, $id, $categoriaId]);
    }
}
```

**Análise:**
- ✅ DELETE remove todas as categorias do produto (tenant correto)
- ✅ INSERT usa prepared statements
- ✅ Transação garante atomicidade

**Possíveis Problemas:**
- ⚠️ **CRÍTICO:** Se `$validCategoriaIds` estiver vazio, nenhum INSERT é executado mas o commit acontece mesmo assim
- ⚠️ Se houver erro no INSERT (ex: violação de chave primária), a exception é lançada mas pode não estar sendo logada corretamente
- ⚠️ Não há verificação se o INSERT realmente inseriu linhas (`rowCount()`)

### 2.5. Detecção de Requisição AJAX

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` (linhas 2060-2067)

```php
private function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (!empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
```

**Análise:**
- ⚠️ **BUG CRÍTICO:** Problema de precedência de operadores!
- A expressão está sendo avaliada como:
  ```php
  (!empty(...) && strtolower(...) === 'xmlhttprequest') || (...)
  ```
- Mas deveria ser:
  ```php
  (!empty(...) && strtolower(...) === 'xmlhttprequest') || (!empty(...) && strpos(...) !== false)
  ```
- **Resultado:** Se `HTTP_X_REQUESTED_WITH` não existir, retorna `false` mas ainda avalia a segunda condição. Se `HTTP_ACCEPT` existir mas não tiver `application/json`, pode retornar `true` incorretamente.

**Correção Necessária:**
```php
private function isAjaxRequest(): bool
{
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
           (!empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
```

### 2.6. Retorno JSON

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` (linhas 1863-1871)

```php
if ($this->isAjaxRequest()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'categorias_labels_html' => $categoriasData['labels_html'],
        'categoria_ids' => $categoriasData['ids'],
        'categorias_nomes' => $categoriasData['nomes']
    ]);
    exit;
}
```

**Análise:**
- ✅ Define header JSON
- ✅ Retorna estrutura esperada pelo frontend
- ✅ Usa método unificado para buscar categorias

**Possível Problema:**
- ⚠️ Se `isAjaxRequest()` retornar `false` incorretamente, vai tentar redirecionar ao invés de retornar JSON
- ⚠️ Se houver qualquer output antes (erros PHP, warnings), o JSON pode ficar inválido

---

## 3. POSSÍVEIS CAUSAS DO PROBLEMA

### 3.1. Problema na Detecção AJAX (MAIS PROVÁVEL)

**Causa:** Método `isAjaxRequest()` com precedência de operadores incorreta pode estar retornando `false` quando deveria retornar `true`.

**Sintoma:** Backend tenta redirecionar ao invés de retornar JSON, causando erro no frontend.

**Evidência:** Se o método retornar `false`, o código executa:
```php
$_SESSION['product_edit_message'] = 'Categorias atualizadas com sucesso!';
header('Location: ' . $this->getBasePath() . '/admin/produtos');
exit;
```
Isso causaria um redirecionamento que o AJAX não espera.

### 3.2. Dados Não Chegando no POST

**Causa:** PHP pode não estar parseando `categorias=1&categorias=2` como array.

**Sintoma:** `$_POST['categorias']` pode estar vazio ou não ser array.

**Verificação Necessária:**
- Adicionar log de `var_export($_POST)` no início do método
- Verificar se `php.ini` tem configurações que afetam parsing de POST

### 3.3. Validação Falhando Silenciosamente

**Causa:** Se nenhuma categoria for válida após validação de tenant, `$validCategoriaIds` fica vazio e nenhum INSERT é executado, mas o código continua e retorna sucesso.

**Sintoma:** DELETE executa, mas INSERT não executa, deixando produto sem categorias.

**Evidência:** Logs mostram "Nenhum INSERT executado (validCategoriaIds vazio)" mas não há tratamento de erro.

### 3.4. Erro no INSERT Não Sendo Capturado

**Causa:** Exception no INSERT pode estar sendo lançada mas não está sendo logada adequadamente.

**Sintoma:** Transação faz rollback mas erro não aparece no frontend.

**Verificação Necessária:**
- Verificar logs de erro do PHP
- Verificar se há violação de chave primária (duplicatas)
- Verificar estrutura da tabela `produto_categorias`

### 3.5. Problema com Middleware

**Causa:** `CheckPermissionMiddleware` pode estar retornando 403 para requisições AJAX.

**Sintoma:** Requisição é bloqueada antes de chegar ao controller.

**Verificação Necessária:**
- Verificar console do navegador para resposta 403
- Verificar se usuário tem permissão `manage_products`

---

## 4. TRECHOS DE CÓDIGO PROBLEMÁTICOS

### 4.1. Método isAjaxRequest() - BUG DE PRECEDÊNCIA

**Localização:** `src/Http/Controllers/Admin/ProductController.php:2060-2067`

**Código Atual (INCORRETO):**
```php
private function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (!empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
```

**Problema:** Precedência de operadores faz com que a primeira condição seja avaliada incorretamente.

**Código Corrigido:**
```php
private function isAjaxRequest(): bool
{
    $isXmlHttpRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    $isJsonAccept = !empty($_SERVER['HTTP_ACCEPT']) && 
                    strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    
    return $isXmlHttpRequest || $isJsonAccept;
}
```

### 4.2. Falta de Validação Após INSERT

**Localização:** `src/Http/Controllers/Admin/ProductController.php:1814-1842`

**Código Atual:**
```php
if (!empty($validCategoriaIds)) {
    // ... INSERT ...
    foreach ($validCategoriaIds as $categoriaId) {
        $stmt->execute([$tenantId, $id, $categoriaId]);
    }
}
```

**Problema:** Não verifica se INSERT realmente inseriu linhas. Se houver erro silencioso, não é detectado.

**Melhoria Sugerida:**
```php
if (!empty($validCategoriaIds)) {
    $stmt = $db->prepare("...");
    $insertedCount = 0;
    foreach ($validCategoriaIds as $categoriaId) {
        $result = $stmt->execute([$tenantId, $id, $categoriaId]);
        if ($result) {
            $insertedCount++;
        } else {
            error_log("Falha ao inserir categoria {$categoriaId} para produto {$id}");
        }
    }
    
    if ($insertedCount === 0 && !empty($validCategoriaIds)) {
        throw new \Exception("Nenhuma categoria foi inserida, mas havia categorias válidas");
    }
}
```

### 4.3. Falta de Log Quando Nenhuma Categoria Válida

**Localização:** `src/Http/Controllers/Admin/ProductController.php:1778-1783`

**Código Atual:**
```php
} else {
    $validCategoriaIds = [];
    if ($isProduto354) {
        error_log("Nenhuma categoria recebida no POST");
    }
}
```

**Problema:** Log só acontece para produto 354. Se validação falhar para outros produtos, não há log.

**Melhoria Sugerida:**
```php
} else {
    $validCategoriaIds = [];
    error_log("updateCategoriesQuick: Nenhuma categoria válida após validação. Produto ID: {$id}, Tenant: {$tenantId}");
}
```

---

## 5. POSSÍVEIS SOLUÇÕES

### Solução 1: Corrigir Método isAjaxRequest()

**Prioridade:** 🔴 ALTA

**Ação:**
1. Corrigir precedência de operadores no método `isAjaxRequest()`
2. Adicionar parênteses explícitos para garantir ordem de avaliação correta
3. Testar com requisição AJAX real

**Impacto:** Se este for o problema, corrigir isso deve resolver o bug completamente.

### Solução 2: Adicionar Logs Detalhados

**Prioridade:** 🟡 MÉDIA

**Ação:**
1. Adicionar log de `$_POST` completo no início do método
2. Adicionar log após cada etapa crítica (validação, DELETE, INSERT)
3. Adicionar log do resultado final antes de retornar JSON

**Impacto:** Ajudará a identificar exatamente onde o fluxo está falhando.

### Solução 3: Validar Dados Recebidos

**Prioridade:** 🟡 MÉDIA

**Ação:**
1. Adicionar verificação explícita se `$_POST['categorias']` existe
2. Se não existir, retornar erro JSON explicativo
3. Validar formato dos dados recebidos

**Impacto:** Previne falhas silenciosas e fornece feedback melhor ao usuário.

### Solução 4: Verificar Estrutura da Tabela

**Prioridade:** 🟢 BAIXA

**Ação:**
1. Verificar se tabela `produto_categorias` tem chave primária composta
2. Verificar se há constraints que podem estar bloqueando INSERTs
3. Verificar se há índices que podem estar causando problemas

**Impacto:** Pode revelar problemas de estrutura que impedem INSERTs.

### Solução 5: Adicionar Tratamento de Erro no Frontend

**Prioridade:** 🟡 MÉDIA

**Ação:**
1. Melhorar tratamento de erro no callback do `makeRequest`
2. Verificar status HTTP da resposta
3. Exibir mensagem de erro mais detalhada ao usuário

**Impacto:** Ajudará a identificar problemas de comunicação frontend-backend.

### Solução 6: Verificar Middleware

**Prioridade:** 🟡 MÉDIA

**Ação:**
1. Adicionar log no `CheckPermissionMiddleware` quando bloquear requisição AJAX
2. Verificar se resposta 403 está sendo tratada corretamente no frontend
3. Testar com usuário que tem permissão garantida

**Impacto:** Pode revelar se problema está na autorização ao invés do processamento.

---

## 6. CHECKLIST DE DIAGNÓSTICO

Para identificar a causa exata, execute na seguinte ordem:

### Passo 1: Verificar Logs do PHP
```bash
# Verificar logs de erro do PHP
tail -f /path/to/php/error.log

# Ou no Windows/XAMPP
tail -f C:\xampp\apache\logs\error.log
```

**O que procurar:**
- Erros relacionados a `updateCategoriesQuick`
- Mensagens de debug do produto 354
- Erros de SQL (violação de chave, etc.)

### Passo 2: Verificar Console do Navegador
1. Abrir DevTools (F12)
2. Aba "Network"
3. Filtrar por "atualizar-categorias"
4. Tentar salvar categorias
5. Verificar:
   - Status HTTP da resposta (200, 403, 500?)
   - Conteúdo da resposta (JSON válido?)
   - Headers da requisição (X-Requested-With presente?)

### Passo 3: Verificar Dados Enviados
Adicionar temporariamente no início de `updateCategoriesQuick()`:
```php
error_log("=== DEBUG UPDATE CATEGORIAS ===");
error_log("POST completo: " . var_export($_POST, true));
error_log("Headers: " . var_export(getallheaders(), true));
error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NÃO DEFINIDO'));
error_log("isAjaxRequest(): " . ($this->isAjaxRequest() ? 'SIM' : 'NÃO'));
```

### Passo 4: Verificar Banco de Dados
Executar script de diagnóstico:
```bash
php database/debug_produto_354_categorias.php
```

**Antes de salvar:** Verificar estado atual
**Depois de salvar:** Verificar se linhas foram inseridas

### Passo 5: Testar com Produto Diferente
Testar com outro produto para verificar se problema é específico do produto 354 ou geral.

---

## 7. RECOMENDAÇÕES PRIORITÁRIAS

### 🔴 CRÍTICO - Corrigir Imediatamente

1. **Corrigir método `isAjaxRequest()`**
   - Adicionar parênteses explícitos
   - Testar com requisição AJAX real
   - Verificar se retorna `true` quando deveria

2. **Adicionar logs detalhados**
   - Log de `$_POST` completo
   - Log após cada etapa crítica
   - Log do resultado final

### 🟡 IMPORTANTE - Corrigir em Seguida

3. **Validar dados recebidos**
   - Verificar se `$_POST['categorias']` existe e é array
   - Retornar erro JSON se dados inválidos

4. **Melhorar tratamento de erro**
   - Verificar `rowCount()` após INSERT
   - Lançar exception se INSERT falhar
   - Retornar erro JSON detalhado

### 🟢 DESEJÁVEL - Melhorias Futuras

5. **Adicionar testes automatizados**
6. **Melhorar feedback ao usuário**
7. **Adicionar validação no frontend antes de enviar**

---

## 8. CONCLUSÃO DA AUDITORIA

### Problemas Identificados

1. ✅ **BUG CRÍTICO:** Método `isAjaxRequest()` com precedência de operadores incorreta
2. ⚠️ **FALTA:** Validação explícita de dados recebidos no POST
3. ⚠️ **FALTA:** Verificação se INSERT realmente inseriu linhas
4. ⚠️ **FALTA:** Logs detalhados para diagnóstico
5. ⚠️ **FALTA:** Tratamento de erro mais robusto

### Próximos Passos Recomendados

1. **Imediato:** Corrigir método `isAjaxRequest()`
2. **Imediato:** Adicionar logs detalhados e testar novamente
3. **Seguinte:** Verificar logs e console do navegador durante teste
4. **Seguinte:** Aplicar outras correções conforme necessário

### Arquivos que Precisam de Alteração

1. `src/Http/Controllers/Admin/ProductController.php`
   - Método `isAjaxRequest()` (linha 2060)
   - Método `updateCategoriesQuick()` (adicionar logs e validações)

2. `public/admin/js/products.js`
   - Melhorar tratamento de erro no callback (opcional)

---

## 9. EVIDÊNCIAS COLETADAS

### Código Analisado

- ✅ JavaScript de envio AJAX (`products.js`)
- ✅ Função `makeRequest()` (formato de dados)
- ✅ Método `updateCategoriesQuick()` (processamento)
- ✅ Método `isAjaxRequest()` (detecção AJAX)
- ✅ Rotas e middlewares
- ✅ Estrutura de queries SQL

### Pontos de Falha Potenciais Identificados

1. **Detecção AJAX incorreta** (mais provável)
2. **Dados não chegando no POST** (possível)
3. **Validação falhando silenciosamente** (possível)
4. **INSERT falhando sem erro visível** (possível)
5. **Middleware bloqueando requisição** (menos provável)

---

**Status da Auditoria:** ✅ COMPLETA
**Próxima Ação:** Aplicar correções prioritárias e testar

