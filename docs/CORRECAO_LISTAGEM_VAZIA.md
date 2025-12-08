# 🔧 Correção - Listagem Vazia de Produtos

Este documento descreve as correções aplicadas para resolver o problema de listagens vazias de produtos.

## 🔍 Problema Identificado

As listagens de produtos estavam retornando vazias devido a:
1. Filtro de status no admin aceitando string vazia (`''`) como valor válido
2. Home pública não tinha fallback quando não havia produtos em destaque
3. Possível falta de verificação de status na página de detalhe pública

## ✅ Correções Aplicadas

### 1. Admin\ProductController@index

**Problema:** O filtro de status estava aplicando `status = ''` quando o parâmetro vinha vazio, resultando em 0 resultados.

**Solução:** Ajustado para só filtrar por status se o valor não estiver vazio E não for "todos":

```php
// ANTES
if (!empty($status)) {
    $where[] = 'status = :status';
    $params['status'] = $status;
}

// DEPOIS
if (!empty($status) && $status !== 'todos') {
    $where[] = 'status = :status';
    $params['status'] = $status;
}
```

**SQL Final:**
```sql
-- Sem filtros
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id
ORDER BY data_criacao DESC 
LIMIT :limit OFFSET :offset

-- Com busca (q)
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id
AND (nome LIKE :q OR sku LIKE :q)
ORDER BY data_criacao DESC 
LIMIT :limit OFFSET :offset

-- Com status específico
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id
AND status = :status
ORDER BY data_criacao DESC 
LIMIT :limit OFFSET :offset
```

### 2. Storefront\HomeController@index

**Problema:** A home só buscava produtos publicados, mas não tentava produtos em destaque primeiro.

**Solução:** Implementado fallback:
1. Primeiro tenta buscar produtos com `destaque = 1`
2. Se não encontrar, busca qualquer produto publicado

```php
// Primeiro, tentar buscar produtos em destaque
$stmt = $db->prepare("
    SELECT * FROM produtos 
    WHERE tenant_id = :tenant_id 
    AND status = 'publish'
    AND destaque = 1
    ORDER BY data_criacao DESC 
    LIMIT 8
");
$stmt->execute(['tenant_id' => $tenantId]);
$produtos = $stmt->fetchAll();

// Se não encontrou produtos em destaque, buscar qualquer produto publicado
if (empty($produtos)) {
    $stmt = $db->prepare("
        SELECT * FROM produtos 
        WHERE tenant_id = :tenant_id 
        AND status = 'publish'
        ORDER BY data_criacao DESC 
        LIMIT 8
    ");
    $stmt->execute(['tenant_id' => $tenantId]);
    $produtos = $stmt->fetchAll();
}
```

**SQL Final:**
```sql
-- Primeira tentativa (produtos em destaque)
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND status = 'publish'
AND destaque = 1
ORDER BY data_criacao DESC 
LIMIT 8

-- Fallback (qualquer produto publicado)
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND status = 'publish'
ORDER BY data_criacao DESC 
LIMIT 8
```

### 3. Storefront\ProductController@show

**Problema:** A página de detalhe não verificava se o produto estava publicado.

**Solução:** Adicionado filtro `status = 'publish'` na query:

```php
// ANTES
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND slug = :slug 
LIMIT 1

// DEPOIS
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND slug = :slug 
AND status = 'publish'
LIMIT 1
```

### 4. Storefront\ProductController@index

**Status:** ✅ Já estava correto
- Filtra por `tenant_id` e `status = 'publish'`
- Não filtra por destaque
- Paginação funcionando corretamente

**SQL Final:**
```sql
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND status = 'publish'
ORDER BY data_criacao DESC 
LIMIT :limit OFFSET :offset
```

### 5. Admin\ProductController@show

**Status:** ✅ Já estava correto
- Não filtra por status (mostra qualquer status no admin)
- Filtra apenas por `id` e `tenant_id`

## 📊 Verificação de Dados

Para verificar os dados no banco, acesse:
```
http://localhost/ecommerce-v1.0/public/check_products.php
```

Este script mostra:
1. Total de produtos
2. Produtos por tenant_id
3. Produtos por status (tenant_id = 1)
4. Produtos por destaque (tenant_id = 1)
5. Produtos publicados E em destaque
6. Produtos publicados (qualquer destaque)

## 📝 Arquivos Alterados

1. `src/Http/Controllers/Admin/ProductController.php`
   - Método `index()`: Corrigido filtro de status

2. `src/Http/Controllers/Storefront/HomeController.php`
   - Método `index()`: Adicionado fallback para produtos sem destaque

3. `src/Http/Controllers/Storefront/ProductController.php`
   - Método `show()`: Adicionado filtro `status = 'publish'`

4. `public/check_products.php` (novo)
   - Script temporário para verificação de dados

## ✅ Resultado Esperado

Após as correções:

- **`/admin/produtos`**: Lista todos os produtos do tenant (sem filtros) ou filtrados corretamente
- **`/` (home)**: Mostra produtos em destaque se houver; caso contrário, mostra produtos publicados
- **`/produtos`**: Mostra todos os produtos publicados, paginados
- **`/produto/{slug}`**: Mostra apenas produtos publicados

## 🧪 Teste

1. Acesse `http://localhost/ecommerce-v1.0/public/check_products.php` para ver as contagens
2. Teste `/admin/produtos` sem filtros - deve mostrar produtos
3. Teste `/admin/produtos?status=` - deve mostrar produtos (não filtrar)
4. Teste `/admin/produtos?status=todos` - deve mostrar produtos (não filtrar)
5. Teste `/admin/produtos?status=publish` - deve mostrar apenas publicados
6. Teste `/` - deve mostrar produtos (destaque ou fallback)
7. Teste `/produtos` - deve mostrar todos os produtos publicados

---

**Data:** Dezembro 2024  
**Status:** ✅ Corrigido



