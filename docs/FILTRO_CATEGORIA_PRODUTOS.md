# Filtro por Categoria na Listagem de Produtos

## Descrição

Filtro adicional na tela `/admin/produtos` que permite filtrar produtos por categoria, combinando com os filtros existentes (busca por nome/SKU, status e produtos com imagem).

## Parâmetro GET

- **Nome:** `categoria_id`
- **Tipo:** Integer
- **Valor padrão:** `null` (mostra todos os produtos)
- **Exemplo:** `/admin/produtos?categoria_id=5`

## Implementação Técnica

### 1. Controller (`ProductController@index`)

#### Leitura do Parâmetro
```php
$categoriaId = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' 
    ? (int)$_GET['categoria_id'] 
    : null;
```

#### Query com JOIN Condicional
Quando `$categoriaId` não é nulo, adiciona um `LEFT JOIN` com a tabela `produto_categorias`:

```sql
LEFT JOIN produto_categorias pc 
  ON pc.produto_id = produtos.id 
 AND pc.tenant_id = :tenant_id_pc
WHERE pc.categoria_id = :categoria_id
```

**Características:**
- Usa `DISTINCT` apenas quando há JOIN para evitar duplicatas
- Mantém compatibilidade com filtros existentes (q, status, somente_com_imagem)
- Respeita multi-tenant através de `tenant_id`

#### Lista de Categorias para o Filtro
Busca todas as categorias do tenant para popular o `<select>`:

```sql
SELECT id, nome
FROM categorias
WHERE tenant_id = :tenant_id
ORDER BY nome ASC
```

### 2. View (`themes/default/admin/products/index-content.php`)

#### Campo de Filtro
Adicionado um `<select>` na área de filtros:

```php
<div class="admin-filter-group">
    <label for="filter-categoria">Categoria</label>
    <select id="filter-categoria" name="categoria_id">
        <option value="">Todas</option>
        <?php foreach ($categoriasFiltro as $categoria): ?>
            <option
                value="<?= (int)$categoria['id'] ?>"
                <?= isset($filtros['categoria_id']) && $filtros['categoria_id'] === (int)$categoria['id'] ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($categoria['nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
```

#### Preservação na Paginação
O parâmetro `categoria_id` é preservado nos links de paginação:

```php
if (!empty($filtros['categoria_id'])) {
    $queryParams[] = 'categoria_id=' . urlencode($filtros['categoria_id']);
}
```

#### Preservação na Ordenação
O parâmetro também é mantido ao ordenar por nome:

```php
if (!empty($filtros['categoria_id'])) {
    $queryParams['categoria_id'] = $filtros['categoria_id'];
}
```

## Combinação de Filtros

O filtro de categoria funciona em conjunto com todos os outros filtros:

### Exemplos de URLs

1. **Apenas categoria:**
   ```
   /admin/produtos?categoria_id=5
   ```

2. **Categoria + busca:**
   ```
   /admin/produtos?categoria_id=5&q=camisa
   ```

3. **Categoria + status:**
   ```
   /admin/produtos?categoria_id=5&status=publish
   ```

4. **Todos os filtros:**
   ```
   /admin/produtos?categoria_id=5&q=camisa&status=publish&somente_com_imagem=1
   ```

5. **Categoria + paginação:**
   ```
   /admin/produtos?categoria_id=5&page=2
   ```

6. **Categoria + ordenação:**
   ```
   /admin/produtos?categoria_id=5&sort=name&direction=asc
   ```

## Comportamento

### Quando `categoria_id` está vazio/null:
- Não aplica filtro de categoria
- Não faz JOIN com `produto_categorias`
- Mostra todos os produtos (respeitando outros filtros)

### Quando `categoria_id` tem valor:
- Aplica filtro de categoria
- Faz LEFT JOIN com `produto_categorias`
- Usa DISTINCT para evitar duplicatas
- Mostra apenas produtos que pertencem à categoria especificada

## Estrutura de Dados

### Tabela `produto_categorias`
```sql
CREATE TABLE produto_categorias (
    tenant_id INT,
    produto_id INT,
    categoria_id INT,
    created_at DATETIME,
    PRIMARY KEY (tenant_id, produto_id, categoria_id)
);
```

### Relacionamento
- Um produto pode ter múltiplas categorias
- Uma categoria pode ter múltiplos produtos
- Relação muitos-para-muitos através de `produto_categorias`

## Testes Manuais

### Checklist de Validação

1. ✅ **Sem filtros:** Acessar `/admin/produtos` → lista todos os produtos
2. ✅ **Filtro por categoria:** Selecionar categoria e filtrar → mostra apenas produtos da categoria
3. ✅ **Combinação de filtros:** Usar categoria + busca + status → todos os filtros aplicados
4. ✅ **Limpar filtro:** Voltar para "Todas" → volta a mostrar todos os produtos
5. ✅ **Paginação:** Mudar de página mantendo categoria → filtro preservado
6. ✅ **Ordenação:** Ordenar por nome mantendo categoria → filtro preservado
7. ✅ **URL:** Verificar que `categoria_id` aparece corretamente na URL

## Arquivos Modificados

1. `src/Http/Controllers/Admin/ProductController.php`
   - Método `index()`: Adicionado filtro de categoria
   - Query com JOIN condicional
   - Busca de categorias para o filtro

2. `themes/default/admin/products/index-content.php`
   - Adicionado `<select>` de categoria nos filtros
   - Preservação de `categoria_id` na paginação
   - Preservação de `categoria_id` na ordenação

## Notas Técnicas

- O JOIN é feito apenas quando necessário (quando há filtro de categoria)
- Usa `DISTINCT` apenas quando há JOIN para evitar duplicatas
- Todos os filtros são combinados com `AND` (todos devem ser satisfeitos)
- Respeita multi-tenant em todas as queries
- A ordenação usa prefixo de tabela (`produtos.nome`) para evitar ambiguidade



