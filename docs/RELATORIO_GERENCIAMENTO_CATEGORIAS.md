# Relatório: Gerenciamento de Categorias e Subcategorias

## 📋 Resumo Executivo

O sistema **possui estrutura de banco de dados** para suportar categorias e subcategorias (hierarquia), mas **NÃO possui interface administrativa** para criar, editar ou excluir categorias diretamente. As categorias são atualmente gerenciadas apenas através de importação de dados ou inserção manual no banco de dados.

---

## ✅ O Que Já Está Implementado

### 1. Estrutura de Banco de Dados

#### Tabela `categorias` (Migration 022)
```sql
CREATE TABLE categorias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    id_original_wp INT NULL,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    categoria_pai_id BIGINT UNSIGNED NULL,  -- ⭐ Suporte a hierarquia
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_pai_id) REFERENCES categorias(id) ON DELETE SET NULL,  -- ⭐ Auto-referência
    UNIQUE KEY unique_categorias_tenant_slug (tenant_id, slug)
)
```

**Características:**
- ✅ Suporte a **hierarquia** através do campo `categoria_pai_id`
- ✅ **Multi-tenant**: cada categoria pertence a um tenant específico
- ✅ **Slug único** por tenant
- ✅ **Descrição** opcional
- ✅ **Cascata**: se uma categoria pai for deletada, `categoria_pai_id` é setado para NULL (não deleta subcategorias)

#### Tabela `produto_categorias` (Migration 023)
```sql
CREATE TABLE produto_categorias (
    tenant_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,
    categoria_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id, produto_id, categoria_id)
)
```

**Características:**
- ✅ Relação **muitos-para-muitos** (N:N)
- ✅ Um produto pode pertencer a múltiplas categorias
- ✅ Uma categoria pode ter múltiplos produtos

### 2. Funcionalidades Implementadas

#### 2.1. Importação de Categorias
**Arquivo:** `database/import_products.php`

- ✅ Importa categorias de arquivos JSON (exportação WordPress)
- ✅ **Suporta hierarquia**: processa primeiro as categorias, depois ajusta os relacionamentos pai-filho
- ✅ Valida duplicatas por `id_original_wp` ou `slug`
- ✅ Mantém referência ao WordPress original (`id_original_wp`)

**Código relevante:**
```php
// Primeiro passo: inserir todas as categorias
INSERT INTO categorias (tenant_id, id_original_wp, nome, slug, descricao, categoria_pai_id)
VALUES (:tenant_id, :wp_id, :nome, :slug, :descricao, NULL)

// Segundo passo: ajustar categorias pai
UPDATE categorias 
SET categoria_pai_id = :pai_id 
WHERE id = :id
```

#### 2.2. Vinculação de Categorias a Produtos
**Controller:** `src/Http/Controllers/Admin/ProductController.php`

- ✅ Ao criar/editar produto, permite selecionar múltiplas categorias
- ✅ Valida que todas as categorias pertencem ao tenant
- ✅ Interface: checkboxes no formulário de produto

**Código relevante:**
```php
// Validação e inserção de relações
if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
    $categoriaIds = array_map('intval', $_POST['categorias']);
    // Valida e insere em produto_categorias
}
```

#### 2.3. Exibição no Storefront
**Controller:** `src/Http/Controllers/Storefront/ProductController.php`

- ✅ Listagem de produtos por categoria
- ✅ Rota: `/categoria/{slug}`
- ✅ Filtro de produtos por categoria na listagem

**Views:**
- `themes/default/storefront/partials/category-strip.php` - Faixa de categorias na home
- `themes/default/storefront/products/index.php` - Listagem com filtro de categoria

#### 2.4. Categorias em Destaque (Home)
**Controller:** `src/Http/Controllers/Admin/HomeCategoriesController.php`

- ✅ Configuração de categorias para exibição na home
- ✅ Permite definir label customizado, ícone e ordem
- ✅ Tabela `home_category_pills` para configuração

**Rotas:**
- `GET /admin/home/categorias-pills` - Listar
- `POST /admin/home/categorias-pills` - Criar
- `GET /admin/home/categorias-pills/{id}/editar` - Editar
- `POST /admin/home/categorias-pills/{id}` - Atualizar
- `POST /admin/home/categorias-pills/{id}/excluir` - Excluir

#### 2.5. Listagem de Categorias
**Script:** `public/listar_categorias_produtos.php`

- ✅ Script utilitário para visualizar todas as categorias
- ✅ Mostra estatísticas (total de produtos por categoria)
- ✅ Exibe hierarquia (`categoria_pai_id`)
- ✅ Exportação para CSV

---

## ❌ O Que NÃO Está Implementado

### 1. Interface Administrativa para Gerenciar Categorias

**Faltando:**
- ❌ **CRUD completo** de categorias (Create, Read, Update, Delete)
- ❌ **Controller específico** para categorias (`CategoryController` ou `CategoriaController`)
- ❌ **Views administrativas** para:
  - Listar todas as categorias
  - Criar nova categoria
  - Editar categoria existente
  - Excluir categoria
  - Visualizar hierarquia (árvore de categorias)

### 2. Visualização de Hierarquia

**Faltando:**
- ❌ Interface para visualizar categorias em formato de árvore
- ❌ Indentação visual de subcategorias
- ❌ Navegação hierárquica no admin
- ❌ Breadcrumbs mostrando a hierarquia

### 3. Validações e Regras de Negócio

**Faltando:**
- ❌ Validação para evitar loops na hierarquia (ex: categoria A filha de B, B filha de A)
- ❌ Validação de profundidade máxima da hierarquia
- ❌ Prevenção de exclusão de categoria que tem produtos vinculados
- ❌ Prevenção de exclusão de categoria que tem subcategorias

### 4. Funcionalidades Avançadas

**Faltando:**
- ❌ Reordenação de categorias (drag & drop)
- ❌ Busca/filtro de categorias no admin
- ❌ Estatísticas de categorias (quantidade de produtos, subcategorias, etc.)
- ❌ Migração em massa de produtos entre categorias

---

## 🔍 Como o Sistema Gerencia Categorias Atualmente

### Fluxo Atual:

1. **Criação de Categorias:**
   - Via script de importação (`database/import_products.php`)
   - Ou inserção manual direta no banco de dados

2. **Uso de Categorias:**
   - Seleção em formulário de produto (apenas categorias existentes)
   - Configuração de categorias em destaque na home
   - Filtro de produtos por categoria no storefront

3. **Visualização:**
   - Script utilitário (`public/listar_categorias_produtos.php`)
   - Select/checkboxes em formulários (lista simples, sem hierarquia)

---

## 📊 Estrutura de Dados - Exemplo

```
Tenant: Loja ABC

Categorias:
├── Roupas (id: 1, categoria_pai_id: NULL)
│   ├── Camisetas (id: 2, categoria_pai_id: 1)
│   ├── Calças (id: 3, categoria_pai_id: 1)
│   └── Acessórios (id: 4, categoria_pai_id: 1)
│       └── Bonés (id: 5, categoria_pai_id: 4)
├── Calçados (id: 6, categoria_pai_id: NULL)
│   └── Tênis (id: 7, categoria_pai_id: 6)
└── Eletrônicos (id: 8, categoria_pai_id: NULL)
```

**No banco de dados:**
```sql
id | nome        | categoria_pai_id
1  | Roupas      | NULL
2  | Camisetas   | 1
3  | Calças      | 1
4  | Acessórios  | 1
5  | Bonés       | 4
6  | Calçados    | NULL
7  | Tênis       | 6
8  | Eletrônicos | NULL
```

---

## 🎯 Recomendações

### Prioridade ALTA:
1. **Criar CRUD completo de categorias** no admin
   - Controller: `CategoriaController`
   - Views: listagem, criação, edição
   - Rotas: `/admin/categorias`

2. **Visualizar hierarquia** na interface
   - Árvore de categorias com indentação
   - Select hierárquico no formulário de produtos

### Prioridade MÉDIA:
3. **Validações de negócio**
   - Prevenir loops na hierarquia
   - Prevenir exclusão de categorias com produtos/subcategorias

4. **Melhorias de UX**
   - Busca/filtro de categorias
   - Reordenação (drag & drop)
   - Estatísticas visuais

### Prioridade BAIXA:
5. **Funcionalidades avançadas**
   - Migração em massa de produtos
   - Importação/exportação de categorias
   - Histórico de alterações

---

## 📝 Conclusão

O sistema **possui toda a infraestrutura necessária** para suportar categorias e subcategorias:
- ✅ Banco de dados com suporte a hierarquia
- ✅ Relacionamentos corretos
- ✅ Importação funcionando

Porém, **falta a interface administrativa** para gerenciar categorias de forma amigável. Atualmente, as categorias precisam ser criadas via importação ou inserção manual no banco de dados.

**Status:** ⚠️ **Infraestrutura pronta, interface administrativa pendente**

---

## ✅ Implementação Realizada

### Interface Administrativa de Categorias

**Data:** Dezembro 2024

Foi implementada a interface administrativa completa para gerenciamento de categorias e subcategorias, incluindo:

#### Rotas Criadas

- `GET  /admin/categorias` - Listagem hierárquica de categorias
- `GET  /admin/categorias/criar` - Formulário de criação
- `POST /admin/categorias/criar` - Salvar nova categoria
- `GET  /admin/categorias/{id}/editar` - Formulário de edição
- `POST /admin/categorias/{id}/editar` - Atualizar categoria
- `POST /admin/categorias/{id}/excluir` - Excluir categoria

#### Arquivos Criados

1. **Controller:**
   - `src/Http/Controllers/Admin/CategoriaController.php`
     - Métodos: `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`
     - Funções auxiliares: `buildCategoryTree()`, `buildCategorySelectOptions()`, `flattenTreeForSelect()`, `isDescendant()`, `generateSlug()`

2. **Views:**
   - `themes/default/admin/categorias/index-content.php` - Listagem hierárquica
   - `themes/default/admin/categorias/form-content.php` - Formulário de criação/edição

3. **Rotas:**
   - Adicionadas em `public/index.php`

#### Arquivos Modificados

1. **ProductController:**
   - `src/Http/Controllers/Admin/ProductController.php`
     - Adicionados métodos auxiliares para hierarquia: `buildCategoryTree()`, `buildCategorySelectOptions()`, `flattenTreeForSelect()`
     - Modificado `create()` e `edit()` para carregar categorias com hierarquia

2. **Views de Produtos:**
   - `themes/default/admin/products/create-content.php` - Exibição hierárquica de categorias
   - `themes/default/admin/products/edit-content.php` - Exibição hierárquica de categorias

#### Funcionalidades Implementadas

✅ **Listagem Hierárquica:**
- Visualização em árvore com indentação visual
- Exibição de estatísticas (total de produtos e subcategorias)
- Busca por nome ou slug
- Ações de editar e excluir

✅ **Criação/Edição:**
- Formulário completo com validações
- Geração automática de slug a partir do nome
- Seleção de categoria pai com hierarquia visual
- Prevenção de loops na hierarquia
- Validação de slug único por tenant

✅ **Exclusão Segura:**
- Validação de subcategorias existentes
- Validação de produtos vinculados
- Mensagens de erro claras quando bloqueado

✅ **Integração com Produtos:**
- Formulários de produto exibem categorias hierarquicamente
- Indentação visual para melhor UX
- Mantém compatibilidade total com código existente

#### Regras de Negócio Implementadas

1. **Slug Único:** Validação de slug único por tenant (ignorando a própria categoria em edição)
2. **Prevenção de Loops:** Validação para evitar que uma categoria seja pai de si mesma ou de seus descendentes
3. **Exclusão Protegida:** Não permite excluir categorias que possuem:
   - Subcategorias vinculadas
   - Produtos vinculados
4. **Multi-tenant:** Todas as operações filtram rigorosamente por `tenant_id`

#### Melhorias de UX

- Auto-geração de slug via JavaScript no formulário
- Indentação visual nas listagens e formulários
- Mensagens de sucesso/erro claras
- Confirmação antes de excluir
- Busca em tempo real na listagem

**Status:** ✅ **Interface administrativa implementada e funcional**

