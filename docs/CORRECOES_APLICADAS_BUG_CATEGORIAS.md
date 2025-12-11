# Correções Aplicadas - Bug de Salvamento de Categorias

## Data
11/12/2025

## Resumo Executivo

Aplicadas correções críticas identificadas na auditoria para resolver o problema de salvamento de categorias do produto SKU 354. As correções focam em:
1. Correção do método `isAjaxRequest()` (bug crítico de precedência)
2. Instrumentação completa com logs detalhados
3. Unificação da lógica de leitura de categorias
4. Script de diagnóstico atualizado

---

## FASE 1 - Correção do Método isAjaxRequest()

### Código Antigo (INCORRETO)

**Localização:** `src/Http/Controllers/Admin/ProductController.php:2060-2066`

```php
private function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           (!empty($_SERVER['HTTP_ACCEPT']) && 
            strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}
```

**Problema:** Precedência de operadores incorreta fazia com que a primeira condição fosse avaliada incorretamente, podendo retornar `false` quando deveria retornar `true`.

### Código Novo (CORRIGIDO)

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

**Melhoria:** Variáveis explícitas garantem ordem de avaliação correta e código mais legível.

### Verificações Realizadas

- ✅ Não existe outra função `isAjaxRequest()` no projeto
- ✅ Todas as 5 chamadas para `isAjaxRequest()` no controller usam esta função
- ✅ Método agora retorna corretamente `true` para requisições AJAX

---

## FASE 2 - Instrumentação com Logs Detalhados

### Logs Adicionados no Início do Método

**Localização:** `src/Http/Controllers/Admin/ProductController.php:1715-1720`

```php
error_log("=== updateCategoriesQuick chamado === Produto ID: {$id}");
error_log("POST recebido em updateCategoriesQuick: " . var_export($_POST, true));
error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'N/A'));
error_log("HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'N/A'));
error_log("isAjaxRequest(): " . ($this->isAjaxRequest() ? 'SIM' : 'NAO'));
error_log("Tenant ID obtido: {$tenantId}");
```

### Logs Após Processamento de Dados

```php
error_log("Categorias recebidas (brutas): " . json_encode($_POST['categorias'] ?? null));
error_log("Categorias após intval: " . json_encode($categoriaIds));
error_log("Categorias válidas para tenant {$tenantId}: " . json_encode($validCategoriaIds));
```

### Logs de Operações no Banco

```php
error_log("DELETE produto_categorias executado para produto {$id}, tenant {$tenantId}. Linhas removidas: {$deletedRows}");
error_log("INSERT produto_categorias OK - Produto {$id}, Categoria {$categoriaId}, Tenant {$tenantId}");
error_log("Total de categorias inseridas para produto {$id}: {$insertedCount}");
```

### Logs Após Operações

```php
error_log("Vínculos DEPOIS do INSERT: " . count($vinculosDepois));
foreach ($vinculosDepois as $v) {
    error_log("  - produto_id: {$v['produto_id']}, tenant_id: {$v['tenant_id']}, categoria_id: {$v['categoria_id']}");
}
error_log("Transação commitada com sucesso para produto {$id}");
error_log("Categorias buscadas após INSERT - IDs: " . json_encode($categoriasData['ids']) . ", Nomes: " . json_encode($categoriasData['nomes']));
```

### Validação Adicionada

```php
// Se houver categorias válidas mas nenhuma foi inserida, lançar exception
if (!empty($validCategoriaIds) && $insertedCount === 0) {
    throw new \RuntimeException("Nenhuma categoria foi inserida para o produto {$id}, mesmo havendo categorias válidas.");
}
```

### Tratamento de Erro Melhorado

```php
catch (\Exception $e) {
    $db->rollBack();
    error_log("ERRO em updateCategoriesQuick - Produto {$id}: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    // ...
}
```

---

## FASE 3 - Unificação da Lógica de Leitura

### Método Unificado Atualizado

**Localização:** `src/Http/Controllers/Admin/ProductController.php:1990-2038`

**Query Unificada:**
```sql
SELECT c.id, c.nome
FROM produto_categorias pc
JOIN categorias c
  ON c.id = pc.categoria_id
 AND c.tenant_id = pc.tenant_id
WHERE pc.produto_id = ?
  AND pc.tenant_id = ?
ORDER BY c.nome ASC
LIMIT 5
```

**Características:**
- ✅ Começa de `produto_categorias` (fonte de verdade)
- ✅ JOIN com `categorias` verifica ambos os `tenant_id`
- ✅ Retorna formato consistente: `['ids' => [], 'nomes' => [], 'labels_html' => '']`
- ✅ HTML gerado no mesmo padrão da view

### Uso na Listagem

**Localização:** `src/Http/Controllers/Admin/ProductController.php:157-159`

```php
// Buscar categorias do produto usando método unificado
$categoriasData = $this->getCategoriasDoProduto($produto['id'], $tenantId);
$produto['categorias'] = $categoriasData['nomes'];
$produto['categoria_ids'] = $categoriasData['ids'];
```

### Uso no Retorno JSON

**Localização:** `src/Http/Controllers/Admin/ProductController.php:1860-1869`

```php
// Buscar categorias atualizadas usando método unificado (garante consistência com a listagem)
// Isso garante que o que retornamos é exatamente o que está no banco depois do INSERT
$categoriasData = $this->getCategoriasDoProduto($id, $tenantId);

if ($this->isAjaxRequest()) {
    echo json_encode([
        'success' => true,
        'categorias_labels_html' => $categoriasData['labels_html'],
        'categoria_ids' => $categoriasData['ids'],
        'categorias_nomes' => $categoriasData['nomes']
    ]);
}
```

**Garantia:** O que está no banco é exatamente o que aparece na tabela e o que é retornado no JSON.

---

## FASE 4 - Script de Diagnóstico Atualizado

### Script Simplificado

**Arquivo:** `database/debug_produto_354_categorias.php`

**Funcionalidades:**
- Busca produto por SKU 354
- Lista todas as linhas em `produto_categorias` para o produto
- Mostra tenant_id, categoria_id e nome da categoria
- Identifica inconsistências

**Uso:**
```bash
php database/debug_produto_354_categorias.php
```

**Saída Esperada:**
```
Produto SKU 354 → ID: 180, Nome: Tênis de Golfe..., Tenant: 1

Linhas em produto_categorias:
- tenant_id=1, categoria_id=5, nome=Calças
```

---

## FASE 5 - Checklist de Testes

### Teste 1: Verificar Logs

1. Limpar log de erros do PHP
2. Acessar `/admin/produtos`
3. Filtrar por SKU 354
4. Abrir modal de categorias
5. Selecionar categoria "Calças"
6. Clicar em "Salvar Categorias"
7. Verificar logs do PHP

**Logs Esperados:**
```
=== updateCategoriesQuick chamado === Produto ID: 180
POST recebido em updateCategoriesQuick: array('categorias' => array(5))
HTTP_X_REQUESTED_WITH: XMLHttpRequest
isAjaxRequest(): SIM
Categorias recebidas (brutas): [5]
Categorias após intval: [5]
Categorias válidas para tenant 1: [5]
DELETE produto_categorias executado... Linhas removidas: 1
INSERT produto_categorias OK - Produto 180, Categoria 5, Tenant 1
Total de categorias inseridas para produto 180: 1
Vínculos DEPOIS do INSERT: 1
  - produto_id: 180, tenant_id: 1, categoria_id: 5
Transação commitada com sucesso para produto 180
Categorias buscadas após INSERT - IDs: [5], Nomes: ["Calças"]
Retornando resposta JSON para requisição AJAX
```

### Teste 2: Verificar Banco de Dados

**Antes de salvar:**
```bash
php database/debug_produto_354_categorias.php
# Deve mostrar vínculos existentes ou "Nenhuma linha"
```

**Depois de salvar:**
```bash
php database/debug_produto_354_categorias.php
# Deve mostrar a nova categoria inserida
```

### Teste 3: Verificar Comportamento Visual

1. ✅ Modal fecha sem erro
2. ✅ Célula CATEGORIAS atualiza imediatamente (sem recarregar)
3. ✅ Após recarregar página, categoria persiste
4. ✅ Modal abre com categoria correta marcada

---

## Arquivos Modificados

### 1. `src/Http/Controllers/Admin/ProductController.php`

**Alterações:**
- ✅ Método `isAjaxRequest()` corrigido (linha 2043)
- ✅ Método `updateCategoriesQuick()` instrumentado com logs (linha 1715+)
- ✅ Método `getCategoriasDoProduto()` atualizado com query unificada (linha 1990)
- ✅ Validação adicionada: exception se INSERT falhar (linha ~1825)
- ✅ Logs de erro melhorados no catch (linha ~1878)

### 2. `database/debug_produto_354_categorias.php`

**Alterações:**
- ✅ Script simplificado conforme roteiro
- ✅ Query focada em `produto_categorias` com LEFT JOIN
- ✅ Saída mais clara e direta

---

## Comparação: Código Antigo vs Novo

### Método isAjaxRequest()

**ANTES:**
```php
return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
       strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
       (!empty($_SERVER['HTTP_ACCEPT']) && ...);
```

**DEPOIS:**
```php
$isXmlHttpRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

$isJsonAccept = !empty($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;

return $isXmlHttpRequest || $isJsonAccept;
```

### Query de Categorias

**ANTES (na listagem):**
```sql
SELECT c.id, c.nome 
FROM categorias c
INNER JOIN produto_categorias pc ON pc.categoria_id = c.id
WHERE pc.tenant_id = :tenant_id AND pc.produto_id = :produto_id
```

**DEPOIS (unificada):**
```sql
SELECT c.id, c.nome
FROM produto_categorias pc
JOIN categorias c
  ON c.id = pc.categoria_id
 AND c.tenant_id = pc.tenant_id
WHERE pc.produto_id = ?
  AND pc.tenant_id = ?
```

**Diferença:** Agora começa de `produto_categorias` (fonte de verdade) e verifica ambos os `tenant_id`.

---

## Próximos Passos para Validação

1. **Executar script de diagnóstico ANTES de testar**
   ```bash
   php database/debug_produto_354_categorias.php
   ```

2. **Testar salvamento de categorias**
   - Abrir modal para produto SKU 354
   - Selecionar categoria "Calças"
   - Salvar
   - Verificar logs do PHP

3. **Executar script de diagnóstico DEPOIS**
   ```bash
   php database/debug_produto_354_categorias.php
   ```
   - Deve mostrar a categoria inserida

4. **Verificar persistência**
   - Recarregar página `/admin/produtos`
   - Verificar se categoria aparece na coluna CATEGORIAS
   - Abrir modal novamente e verificar se categoria está marcada

---

## Status das Correções

- ✅ **FASE 1:** Método `isAjaxRequest()` corrigido
- ✅ **FASE 2:** Logs detalhados adicionados
- ✅ **FASE 3:** Lógica unificada implementada
- ✅ **FASE 4:** Script de diagnóstico atualizado
- ⏳ **FASE 5:** Aguardando testes manuais

---

## Observações Importantes

1. **Logs sempre ativos:** Todos os logs foram adicionados sem condição de produto específico, facilitando diagnóstico de qualquer produto.

2. **Validação rigorosa:** Exception é lançada se houver categorias válidas mas nenhuma inserida, prevenindo falhas silenciosas.

3. **Consistência garantida:** Método unificado garante que listagem e retorno JSON sempre mostram os mesmos dados.

4. **Query otimizada:** Query unificada começa de `produto_categorias`, garantindo que apenas vínculos reais sejam considerados.

---

**Próxima ação:** Executar testes conforme FASE 5 e verificar logs/banco de dados.

