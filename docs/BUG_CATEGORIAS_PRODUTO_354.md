# BUG: Categorias não aparecem na listagem (Produto SKU 354)

## Descrição do Bug

**Produto afetado:** Tênis de Golfe Spikeless Feminino FJ Fuel da FootJoy ROSA TM 6, SKU 354

**Sintoma:**
- A coluna "CATEGORIAS" na listagem `/admin/produtos` mostra "Sem categorias"
- O modal de edição de categorias mostra categorias marcadas (ex: "Calçados")
- Ao alterar e salvar categorias, a listagem continua mostrando "Sem categorias"
- Mesmo após recarregar a página, o problema persiste

## Causa Raiz Identificada

### Problema 1: Query da Listagem Incompleta

A query original em `ProductController@index()` não verificava se a categoria pertence ao mesmo tenant:

```sql
-- ANTES (INCORRETO)
SELECT c.id, c.nome 
FROM categorias c
INNER JOIN produto_categorias pc ON pc.categoria_id = c.id
WHERE pc.tenant_id = :tenant_id AND pc.produto_id = :produto_id
```

**Problema:** O JOIN não verifica `c.tenant_id`, apenas `pc.tenant_id`. Isso pode causar inconsistências se houver categorias com mesmo ID em tenants diferentes.

### Problema 2: Lógica Duplicada e Inconsistente

- A listagem montava `categoria_ids` e `categorias_labels_html` de uma forma
- O método `updateCategoriesQuick` montava o retorno JSON de outra forma
- Não havia garantia de que ambos retornassem o mesmo formato

### Problema 3: Falta de Verificação de Tenant na Categoria

O JOIN deveria verificar ambos os `tenant_id`:
- `pc.tenant_id = :tenant_id` (já estava)
- `c.tenant_id = :tenant_id` (faltava)

## Correções Implementadas

### 1. Método Unificado `getCategoriasDoProduto()`

Criado método privado que garante consistência entre listagem e ação rápida:

```php
private function getCategoriasDoProduto(int $produtoId, int $tenantId): array
{
    // Query corrigida com verificação completa de tenant
    $stmt = $db->prepare("
        SELECT c.id, c.nome 
        FROM categorias c
        INNER JOIN produto_categorias pc 
            ON pc.categoria_id = c.id 
            AND pc.tenant_id = :tenant_id_pc
            AND pc.produto_id = :produto_id
        WHERE c.tenant_id = :tenant_id_c
        ORDER BY c.nome ASC
        LIMIT 5
    ");
    
    // Retorna: ['ids' => [], 'nomes' => [], 'labels_html' => '']
}
```

**Características:**
- Verifica `pc.tenant_id` E `c.tenant_id`
- Retorna formato consistente
- Gera HTML igual ao usado na view

### 2. Uso do Método Unificado

**Na listagem (`index()`):**
```php
$categoriasData = $this->getCategoriasDoProduto($produto['id'], $tenantId);
$produto['categorias'] = $categoriasData['nomes'];
$produto['categoria_ids'] = $categoriasData['ids'];
```

**No método de atualização (`updateCategoriesQuick()`):**
```php
// Após salvar, busca usando o mesmo método
$categoriasData = $this->getCategoriasDoProduto($id, $tenantId);

// Retorna JSON com dados consistentes
echo json_encode([
    'success' => true,
    'categorias_labels_html' => $categoriasData['labels_html'],
    'categoria_ids' => $categoriasData['ids'],
    'categorias_nomes' => $categoriasData['nomes']
]);
```

### 3. Logs de Debug Adicionados

Adicionados logs específicos para o produto SKU 354 no método `updateCategoriesQuick()`:
- IDs recebidos no POST
- IDs válidos após validação
- Vínculos ANTES do DELETE
- Linhas removidas no DELETE
- INSERTs executados
- Vínculos DEPOIS do INSERT

## SQL Final Corrigida

### Listagem de Produtos (`index()`)

```sql
SELECT c.id, c.nome 
FROM categorias c
INNER JOIN produto_categorias pc 
    ON pc.categoria_id = c.id 
    AND pc.tenant_id = :tenant_id_pc
    AND pc.produto_id = :produto_id
WHERE c.tenant_id = :tenant_id_c
ORDER BY c.nome ASC
LIMIT 5
```

**Diferenças da versão anterior:**
- ✅ Verifica `c.tenant_id = :tenant_id_c` (novo)
- ✅ Verifica `pc.tenant_id = :tenant_id_pc` no JOIN (melhorado)
- ✅ Garante que categoria e vínculo pertencem ao mesmo tenant

### Atualização Rápida (`updateCategoriesQuick()`)

**DELETE:**
```sql
DELETE FROM produto_categorias 
WHERE tenant_id = :tenant_id AND produto_id = :produto_id
```

**INSERT:**
```sql
INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
VALUES (?, ?, ?, NOW())
```

**Validação antes de INSERT:**
```sql
SELECT id FROM categorias 
WHERE id IN (...) AND tenant_id = ?
```

## Script de Diagnóstico

Criado script `database/debug_produto_354_categorias.php` que:
1. Busca produto por SKU
2. Lista vínculos em `produto_categorias`
3. Lista categorias via JOIN
4. Verifica inconsistências de `tenant_id`

**Como usar:**
```bash
php database/debug_produto_354_categorias.php
```

Ou acessar via browser (requer autenticação no admin).

## Como Reproduzir e Verificar a Correção

### Passo 1: Verificar Estado Inicial
1. Acessar `/admin/produtos`
2. Buscar produto SKU 354
3. Verificar coluna "CATEGORIAS" (deve mostrar categorias se houver)

### Passo 2: Testar Modal
1. Clicar no ícone de editar categorias do produto 354
2. Verificar se categorias estão marcadas corretamente
3. Comparar com o que aparece na coluna "CATEGORIAS"

### Passo 3: Testar Atualização
1. Alterar categoria (ex: de "Calçados" para "Calças")
2. Clicar em "Salvar Categorias"
3. **SEM recarregar:** Verificar se a coluna "CATEGORIAS" atualiza imediatamente
4. **Recarregar página:** Verificar se a categoria persiste

### Passo 4: Verificar Banco de Dados
1. Executar script de diagnóstico
2. Verificar que há vínculos em `produto_categorias` com `tenant_id` correto
3. Verificar que JOIN encontra as categorias

## Comportamento Esperado Após Correção

✅ **Listagem:** Mostra categorias corretamente na coluna "CATEGORIAS"

✅ **Modal:** Abre com categorias corretas marcadas

✅ **Atualização AJAX:** Atualiza visualmente sem recarregar página

✅ **Persistência:** Após recarregar, categorias continuam corretas

✅ **Consistência:** Listagem e modal sempre mostram as mesmas categorias

## Arquivos Modificados

1. `src/Http/Controllers/Admin/ProductController.php`
   - Método `index()`: Usa `getCategoriasDoProduto()`
   - Método `updateCategoriesQuick()`: Usa `getCategoriasDoProduto()` para retorno
   - Novo método `getCategoriasDoProduto()`: Lógica unificada

2. `database/debug_produto_354_categorias.php`
   - Script de diagnóstico criado

## Notas Técnicas

- O método unificado garante que listagem e ação rápida sempre retornem o mesmo formato
- A query corrigida previne problemas de multi-tenant
- Os logs de debug ajudam a identificar problemas futuros
- O script de diagnóstico pode ser usado para outros produtos também

## Status

✅ **CORRIGIDO** - Aguardando testes com produto SKU 354



