# Correção: Categorias/Subcategorias não aparecem no menu da loja

## 🐞 Problema Identificado

**Sintoma:** A categoria "ACESSÓRIOS" (slug: `acessorios`) com 50 produtos e 3 subcategorias (CINTOS, LUVAS, ÓCULOS) não aparecia no menu/modal de categorias do frontend (desktop e mobile).

**Causa Raiz:**
O menu de categorias estava sendo populado apenas com categorias da tabela `home_category_pills` (categorias configuradas manualmente no admin para aparecer na home). Categorias que têm produtos visíveis mas não estão configuradas em `home_category_pills` não apareciam no menu.

**Localização do problema:**
- `HomeController.php` linha 124: `$allCategories = $categoryPills;`
- `themes/default/storefront/products/index.php` linha 51: `$allCategories = $categoryPills;`

## ✅ Correção Implementada

### 1. Nova Query para Buscar Categorias com Produtos Visíveis

Criado método `getCategoriesWithVisibleProducts()` em `HomeController` que:
- Busca todas as categorias que têm produtos visíveis diretamente
- Busca categorias pai que têm subcategorias com produtos visíveis
- Usa os mesmos critérios de visibilidade do catálogo:
  - `status = 'publish'`
  - `exibir_no_catalogo = 1`
  - Se `catalogo_ocultar_estoque_zero = '1'`: `(gerencia_estoque = 0 OR (gerencia_estoque = 1 AND quantidade_estoque > 0))`

### 2. Exibição Hierárquica de Subcategorias

Atualizado template `category-strip.php` para:
- Separar categorias pai e filhas
- Exibir subcategorias indentadas abaixo das categorias pai
- Adicionar CSS para estilização das subcategorias (`.pg-category-menu-sublist`)

### 3. Tratamento de "Sem Categoria"

Adicionada lógica para incluir "Sem Categoria" no menu se houver produtos sem categoria visíveis.

### 4. Correção de Erros 404

Adicionado tratamento de erro em imagens de categorias (`onerror`) para evitar 404 quando imagens não existem.

## 📁 Arquivos Modificados

### 1. `src/Http/Controllers/Storefront/HomeController.php`
- **Linha 124:** Substituído `$allCategories = $categoryPills;` por chamada ao novo método
- **Novo método:** `getCategoriesWithVisibleProducts()` - Busca categorias com produtos visíveis
- **Novo método:** `getProdutosSemCategoriaCount()` - Conta produtos sem categoria

### 2. `themes/default/storefront/products/index.php`
- **Linhas 52-130:** Substituída lógica que usava apenas `$categoryPills` por query completa que busca todas as categorias com produtos visíveis
- Adicionada lógica para incluir "Sem Categoria" se necessário

### 3. `themes/default/storefront/partials/category-strip.php`
- **Linhas 56-100:** Atualizado para exibir hierarquia (categorias pai com subcategorias indentadas)
- **Linha 27:** Adicionado `onerror` para tratar imagens ausentes

### 4. `themes/default/storefront/layouts/base.php`
- **Linhas 567-576:** Adicionado CSS para subcategorias (`.pg-category-menu-sublist`, `.pg-category-menu-sublink`)

## 🧪 Checklist de Teste

### Testes Funcionais

- [ ] **ACESSÓRIOS aparece no menu desktop**
  - Abrir loja em desktop
  - Clicar no botão "Categorias"
  - Verificar se "ACESSÓRIOS" aparece na lista

- [ ] **ACESSÓRIOS aparece no modal mobile**
  - Abrir loja em mobile (ou modo responsivo)
  - Clicar no botão "Categorias"
  - Verificar se "ACESSÓRIOS" aparece no modal

- [ ] **Subcategorias aparecem corretamente**
  - Abrir menu/modal de categorias
  - Verificar se "CINTOS", "LUVAS" e "ÓCULOS" aparecem como subcategorias de "ACESSÓRIOS"
  - Verificar se estão indentadas/abaixo de "ACESSÓRIOS"

- [ ] **Links funcionam corretamente**
  - Clicar em "ACESSÓRIOS" → deve ir para `/produtos?categoria=acessorios`
  - Clicar em "CINTOS" → deve ir para `/produtos?categoria=cintos`
  - Clicar em "LUVAS" → deve ir para `/produtos?categoria=luvas`
  - Clicar em "ÓCULOS" → deve ir para `/produtos?categoria=oculos`

- [ ] **Não existem mais 404 no console**
  - Abrir DevTools > Console
  - Abrir modal de categorias
  - Verificar que não há erros 404

### Testes de Regressão

- [ ] **Categorias configuradas em home_category_pills ainda aparecem**
  - Verificar se categorias como "BOLSAS", "BONÉS, VISEIRAS E CHAPÉUS" ainda aparecem

- [ ] **Produtos sem categoria aparecem corretamente**
  - Se houver produtos sem categoria, verificar se "Sem Categoria" aparece no menu

- [ ] **Filtro de estoque zero funciona**
  - Se `catalogo_ocultar_estoque_zero = '1'`, categorias com apenas produtos sem estoque não devem aparecer

## 🔍 Validação SQL (Opcional)

Para validar que a categoria ACESSÓRIOS e seus produtos estão corretos:

```sql
-- Verificar categoria ACESSÓRIOS
SELECT id, nome, slug, categoria_pai_id 
FROM categorias 
WHERE tenant_id = 1 AND slug = 'acessorios';

-- Verificar produtos visíveis em ACESSÓRIOS
SELECT COUNT(DISTINCT p.id) as total_produtos
FROM produtos p
INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = 1
INNER JOIN categorias c ON c.id = pc.categoria_id AND c.tenant_id = 1
WHERE c.slug = 'acessorios'
AND p.status = 'publish'
AND p.exibir_no_catalogo = 1;

-- Verificar subcategorias de ACESSÓRIOS
SELECT id, nome, slug 
FROM categorias 
WHERE tenant_id = 1 AND categoria_pai_id = (SELECT id FROM categorias WHERE slug = 'acessorios' AND tenant_id = 1);
```

## 📝 Notas Técnicas

### Critérios de Visibilidade de Produtos

A query usa os mesmos critérios do catálogo:
1. `status = 'publish'`
2. `exibir_no_catalogo = 1`
3. Se `catalogo_ocultar_estoque_zero = '1'`: `(gerencia_estoque = 0 OR (gerencia_estoque = 1 AND quantidade_estoque > 0))`

### Hierarquia de Categorias

- Categorias pai aparecem primeiro (ordenadas por nome)
- Subcategorias aparecem indentadas abaixo da categoria pai
- Se uma categoria pai não tem produtos próprios mas tem subcategorias com produtos, ela ainda aparece

### Performance

A query usa `UNION` para combinar:
1. Categorias com produtos visíveis diretamente
2. Categorias pai que têm subcategorias com produtos visíveis

O `DISTINCT` garante que não há duplicatas.

## 🚀 Próximos Passos

1. Testar em ambiente de desenvolvimento
2. Fazer deploy para produção
3. Validar que não há regressões
4. Monitorar logs por possíveis erros


