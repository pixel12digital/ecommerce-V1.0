# Relatório: Listagem de Produtos + Categorias nos Produtos

**Data:** 2025-01-10  
**Versão:** 1.0

## 📋 Resumo Executivo

Este documento descreve as melhorias implementadas na tela de listagem e edição de produtos do painel admin do e-commerce, incluindo:

1. Botão "Novo produto" na listagem
2. Ordenação alfabética por Nome (A-Z / Z-A)
3. Vinculação de Categorias aos Produtos (criação/edição)
4. Exibição de categorias na listagem

---

## 🗂️ Estrutura de Arquivos

### Controllers

```
src/Http/Controllers/Admin/ProductController.php
```

**Métodos principais:**
- `index()` - Listagem de produtos com filtros e ordenação
- `create()` - Exibe formulário de criação
- `store()` - Salva novo produto com categorias
- `edit()` - Exibe formulário de edição
- `update()` - Atualiza produto e categorias

### Views

```
themes/default/admin/products/
├── index-content.php      # Listagem de produtos
├── create-content.php     # Formulário de criação (NOVO)
└── edit-content.php       # Formulário de edição
```

---

## ✅ Funcionalidades Implementadas

### 1. Botão "Novo produto" na Listagem

**Localização:** `themes/default/admin/products/index-content.php` (linhas 16-19)

**Implementação:**
- Botão adicionado no cabeçalho da página, ao lado do título "Produtos"
- Estilo: `admin-btn admin-btn-primary` (botão laranja padrão do painel)
- Ícone: Bootstrap Icons `bi-plus-circle`
- Rota: `/admin/produtos/novo`

**Código:**
```php
<a href="<?= $basePath ?>/admin/produtos/novo" class="admin-btn admin-btn-primary" 
   style="display: inline-flex; align-items: center; gap: 0.5rem;">
    <i class="bi bi-plus-circle icon"></i>
    Novo produto
</a>
```

**Status:** ✅ Já existia e está funcionando

---

### 2. Ordenação Alfabética por Nome (A-Z / Z-A)

#### 2.1. Backend

**Localização:** `src/Http/Controllers/Admin/ProductController.php` (linhas 25-33)

**Parâmetros aceitos:**
- `sort` - Valor permitido: `name`
- `direction` - Valores permitidos: `asc` ou `desc`

**Comportamento:**
- Sem parâmetros: ordenação padrão por `data_criacao DESC`
- `sort=name&direction=asc`: ordena por `nome ASC` (A-Z)
- `sort=name&direction=desc`: ordena por `nome DESC` (Z-A)
- Valores fora da whitelist são ignorados

**Código:**
```php
// Parâmetros de ordenação
$sort = $_GET['sort'] ?? '';
$direction = strtolower($_GET['direction'] ?? 'asc');
$orderBy = 'data_criacao DESC'; // Padrão

// Validar e aplicar ordenação por nome
if ($sort === 'name' && in_array($direction, ['asc', 'desc'])) {
    $orderBy = 'nome ' . strtoupper($direction);
}
```

**Status:** ✅ Já estava implementado

#### 2.2. Frontend

**Localização:** `themes/default/admin/products/index-content.php` (linhas 56-88)

**Funcionalidades:**
- Cabeçalho da coluna "Nome" é um link clicável
- Alterna entre A-Z e Z-A ao clicar
- Preserva todos os filtros aplicados (busca, status, "apenas com imagem")
- Exibe indicador visual (↑ para asc, ↓ para desc)

**Código:**
```php
// Construir URL para ordenação por nome
$queryParams = [];
if (!empty($filtros['q'])) $queryParams['q'] = $filtros['q'];
if (!empty($filtros['status'])) $queryParams['status'] = $filtros['status'];
if (!empty($filtros['somente_com_imagem'])) $queryParams['somente_com_imagem'] = '1';

// Determinar próxima direção
$currentSort = $ordenacao['sort'] ?? '';
$currentDirection = $ordenacao['direction'] ?? 'asc';
$nextDirection = 'asc';

if ($currentSort === 'name') {
    $nextDirection = ($currentDirection === 'asc') ? 'desc' : 'asc';
}

$queryParams['sort'] = 'name';
$queryParams['direction'] = $nextDirection;
$sortUrl = $basePath . '/admin/produtos?' . http_build_query($queryParams);

// Ícone de ordenação
$sortIcon = '';
if ($currentSort === 'name') {
    $sortIcon = $currentDirection === 'asc' ? '↑' : '↓';
}
```

**Status:** ✅ Já estava implementado

---

### 3. Vincular Categorias aos Produtos

#### 3.1. Estrutura do Banco de Dados

**Tabelas envolvidas:**

1. **`categorias`** (já existia)
   - `id` (BIGINT UNSIGNED)
   - `tenant_id` (BIGINT UNSIGNED)
   - `nome` (VARCHAR 255)
   - `slug` (VARCHAR 255)
   - `descricao` (TEXT)
   - `categoria_pai_id` (BIGINT UNSIGNED NULL)

2. **`produto_categorias`** (tabela pivot - já existia)
   - `tenant_id` (BIGINT UNSIGNED)
   - `produto_id` (BIGINT UNSIGNED)
   - `categoria_id` (BIGINT UNSIGNED)
   - `created_at` (DATETIME)
   - **Chave primária composta:** `(tenant_id, produto_id, categoria_id)`

**Migration:** `database/migrations/023_create_produto_categorias_table.php`

**Relação:** Muitos-para-muitos (N:N)
- Um produto pode ter múltiplas categorias
- Uma categoria pode ter múltiplos produtos

#### 3.2. Backend - Controller

##### Criação de Produto (`store()`)

**Localização:** `src/Http/Controllers/Admin/ProductController.php` (linhas 285-306)

**Funcionalidade:**
- Recebe array `$_POST['categorias']` com IDs das categorias selecionadas
- Valida que todas as categorias pertencem ao tenant
- Insere relações na tabela `produto_categorias`

**Código:**
```php
// 5. Vincular categorias
if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
    $categoriaIds = array_map('intval', $_POST['categorias']);
    
    // Validar que todas as categorias pertencem ao tenant
    $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
    $stmt = $db->prepare("
        SELECT id FROM categorias 
        WHERE id IN ({$placeholders}) AND tenant_id = ?
    ");
    $stmt->execute(array_merge($categoriaIds, [$tenantId]));
    $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
    
    // Inserir relações
    $stmt = $db->prepare("
        INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    foreach ($validCategoriaIds as $categoriaId) {
        $stmt->execute([$tenantId, $produtoId, $categoriaId]);
    }
}
```

##### Edição de Produto (`update()`)

**Localização:** `src/Http/Controllers/Admin/ProductController.php` (linhas 560-590)

**Funcionalidade:**
- Remove todas as categorias atuais do produto
- Adiciona novas categorias selecionadas (sync)
- Valida que todas as categorias pertencem ao tenant

**Código:**
```php
// 5. Atualizar categorias (sync: remover antigas e adicionar novas)
// Remover todas as categorias atuais do produto
$stmt = $db->prepare("
    DELETE FROM produto_categorias 
    WHERE tenant_id = :tenant_id AND produto_id = :produto_id
");
$stmt->execute([
    'tenant_id' => $tenantId,
    'produto_id' => $id
]);

// Adicionar novas categorias se houver
if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
    $categoriaIds = array_map('intval', $_POST['categorias']);
    
    // Validar que todas as categorias pertencem ao tenant
    if (!empty($categoriaIds)) {
        $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
        $stmt = $db->prepare("
            SELECT id FROM categorias 
            WHERE id IN ({$placeholders}) AND tenant_id = ?
        ");
        $stmt->execute(array_merge($categoriaIds, [$tenantId]));
        $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
        
        // Inserir relações
        $stmt = $db->prepare("
            INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        foreach ($validCategoriaIds as $categoriaId) {
            $stmt->execute([$tenantId, $id, $categoriaId]);
        }
    }
}
```

**Status:** ✅ Implementado

##### Buscar Categorias do Produto (`edit()`)

**Localização:** `src/Http/Controllers/Admin/ProductController.php` (linhas 391-407)

**Funcionalidade:**
- Busca categorias já vinculadas ao produto
- Busca todas as categorias disponíveis do tenant para o formulário
- Passa dados para a view

**Código:**
```php
// Buscar categorias do produto
$stmt = $db->prepare("
    SELECT c.* 
    FROM categorias c
    JOIN produto_categorias pc ON pc.categoria_id = c.id
    WHERE pc.tenant_id = :tenant_id_pc
    AND c.tenant_id = :tenant_id_c
    AND pc.produto_id = :produto_id
    ORDER BY c.nome ASC
");
$stmt->execute([
    'tenant_id_pc' => $tenantId,
    'tenant_id_c' => $tenantId,
    'produto_id' => $produto['id']
]);
$categoriasProduto = $stmt->fetchAll();
$categoriasProdutoIds = array_column($categoriasProduto, 'id');

// Buscar todas as categorias do tenant para o formulário
$stmt = $db->prepare("
    SELECT id, nome, slug
    FROM categorias
    WHERE tenant_id = :tenant_id
    ORDER BY nome ASC
");
$stmt->execute(['tenant_id' => $tenantId]);
$todasCategorias = $stmt->fetchAll();
```

**Status:** ✅ Já estava implementado

#### 3.3. Frontend - Views

##### Formulário de Criação (`create-content.php`)

**Localização:** `themes/default/admin/products/create-content.php` (linhas 140-165)

**Funcionalidade:**
- Campo de seleção múltipla de categorias (checkboxes)
- Lista todas as categorias disponíveis do tenant
- Permite selecionar múltiplas categorias
- Exibe mensagem se não houver categorias cadastradas

**Código:**
```php
<!-- Seção: Categorias -->
<div class="info-section">
    <h2 class="section-title">Categorias</h2>
    
    <div class="form-group">
        <label>Selecione as categorias deste produto</label>
        <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; background: #f9f9f9;">
            <?php 
            $categoriasSelecionadas = $formData['categorias'] ?? [];
            foreach ($categorias as $categoria): 
            ?>
                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                    <input type="checkbox" name="categorias[]" value="<?= $categoria['id'] ?>" 
                           <?= in_array($categoria['id'], $categoriasSelecionadas) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($categoria['nome']) ?></span>
                </label>
            <?php endforeach; ?>
            <?php if (empty($categorias)): ?>
                <p style="color: #999; font-style: italic;">Nenhuma categoria cadastrada. Crie categorias primeiro.</p>
            <?php endif; ?>
        </div>
        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
            Selecione uma ou mais categorias para organizar seus produtos. Um produto pode pertencer a múltiplas categorias.
        </small>
    </div>
</div>
```

**Status:** ✅ Criado

##### Formulário de Edição (`edit-content.php`)

**Localização:** `themes/default/admin/products/edit-content.php` (linhas 173-199)

**Funcionalidade:**
- Campo de seleção múltipla de categorias (checkboxes)
- Categorias já vinculadas aparecem pré-selecionadas
- Lista todas as categorias disponíveis do tenant
- Permite adicionar/remover categorias

**Status:** ✅ Já estava implementado

#### 3.4. Exibição na Listagem

**Localização:** 
- Backend: `src/Http/Controllers/Admin/ProductController.php` (linhas 130-145)
- Frontend: `themes/default/admin/products/index-content.php` (linhas 90, 141-160)

**Funcionalidade:**
- Nova coluna "Categorias" na tabela de listagem
- Exibe até 2 categorias com badges
- Mostra contador "+N" se houver mais categorias
- Exibe "Sem categorias" se o produto não tiver categorias

**Código Backend:**
```php
// Buscar categorias do produto
$stmtCat = $db->prepare("
    SELECT c.nome 
    FROM categorias c
    INNER JOIN produto_categorias pc ON pc.categoria_id = c.id
    WHERE pc.tenant_id = :tenant_id AND pc.produto_id = :produto_id
    ORDER BY c.nome ASC
    LIMIT 5
");
$stmtCat->execute([
    'tenant_id' => $tenantId,
    'produto_id' => $produto['id']
]);
$categorias = $stmtCat->fetchAll();
$produto['categorias'] = array_column($categorias, 'nome');
```

**Código Frontend:**
```php
<td>
    <?php 
    $categorias = $produto['categorias'] ?? [];
    if (!empty($categorias)): 
        $categoriasDisplay = array_slice($categorias, 0, 2);
        $restantes = count($categorias) - 2;
    ?>
        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
            <?php foreach ($categoriasDisplay as $cat): ?>
                <span style="display: inline-block; padding: 0.25rem 0.5rem; background: #e0e0e0; border-radius: 4px; font-size: 0.75rem; color: #555;">
                    <?= htmlspecialchars($cat) ?>
                </span>
            <?php endforeach; ?>
            <?php if ($restantes > 0): ?>
                <span style="display: inline-block; padding: 0.25rem 0.5rem; background: #f0f0f0; border-radius: 4px; font-size: 0.75rem; color: #999;">
                    +<?= $restantes ?>
                </span>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <span style="color: #999; font-style: italic; font-size: 0.875rem;">Sem categorias</span>
    <?php endif; ?>
</td>
```

**Status:** ✅ Implementado

---

## 🔒 Segurança Multi-tenant

Todas as operações respeitam o isolamento por tenant:

1. **Validação de categorias:** Todas as categorias são validadas para pertencer ao tenant atual
2. **Filtros de consulta:** Todas as queries incluem `WHERE tenant_id = :tenant_id`
3. **Tabela pivot:** A chave primária composta inclui `tenant_id`

**Exemplo:**
```php
// Validar que todas as categorias pertencem ao tenant
$stmt = $db->prepare("
    SELECT id FROM categorias 
    WHERE id IN ({$placeholders}) AND tenant_id = ?
");
```

---

## 📝 Validação

### Checklist de Validação

#### Listagem de Produtos
- ✅ Continua carregando normalmente com todos os filtros
- ✅ Exibe o botão "Novo produto" que leva à tela de criação
- ✅ Ordenação por Nome funciona corretamente

#### Ordenação por Nome
- ✅ Clique em "Nome" alterna A-Z / Z-A
- ✅ A URL reflete `sort=name&direction=asc|desc`
- ✅ Filtros (busca, status, "apenas com imagem") continuam funcionando em conjunto
- ✅ Paginação preserva parâmetros de ordenação

#### Categorias nos Produtos
- ✅ Na criação de produto, consigo escolher categoria(s) existentes
- ✅ Na edição, as categorias atuais aparecem marcadas
- ✅ Salvar o produto respeita todos os campos antigos + categorias
- ✅ Não quebra nada de estoque, preço, imagens, etc.
- ✅ Multi-tenant continua respeitado (produto só enxerga categorias do próprio tenant)
- ✅ Categorias são exibidas na listagem de produtos

---

## 🔄 Compatibilidade

### O que NÃO foi alterado:
- ✅ Lógica de multi-tenant
- ✅ Regras de visibilidade
- ✅ Joins essenciais
- ✅ Estrutura de filtros existentes
- ✅ Paginação
- ✅ Processamento de imagens
- ✅ Processamento de vídeos
- ✅ Outros campos do produto (preço, estoque, etc.)

### O que foi adicionado:
- ✅ Campo de categorias no formulário de criação
- ✅ Salvamento de categorias no método `update()`
- ✅ Busca de categorias na listagem
- ✅ Coluna de categorias na tabela de listagem

---

## 📚 Exemplos de Uso

### URL de Ordenação

**Ordenar A-Z:**
```
/admin/produtos?sort=name&direction=asc
```

**Ordenar Z-A:**
```
/admin/produtos?sort=name&direction=desc
```

**Ordenar com filtros:**
```
/admin/produtos?q=camisa&status=publish&sort=name&direction=asc&somente_com_imagem=1
```

### Formulário de Categorias

**HTML gerado:**
```html
<input type="checkbox" name="categorias[]" value="1">
<input type="checkbox" name="categorias[]" value="2">
<input type="checkbox" name="categorias[]" value="3">
```

**Processamento no backend:**
```php
$_POST['categorias'] = [1, 2, 3]; // Array de IDs
```

---

## 🐛 Troubleshooting

### Problema: Categorias não aparecem na listagem

**Solução:** Verificar se a query está buscando categorias corretamente:
```php
// Verificar se $produto['categorias'] está sendo populado
var_dump($produto['categorias']);
```

### Problema: Categorias não são salvas na edição

**Solução:** Verificar se o método `update()` está sendo executado corretamente e se a transação está sendo commitada.

### Problema: Ordenação não funciona

**Solução:** Verificar se os parâmetros `sort` e `direction` estão sendo passados corretamente na URL e se a validação está permitindo os valores.

---

## 📅 Histórico de Alterações

- **2025-01-10:** Implementação inicial
  - Botão "Novo produto" (já existia)
  - Ordenação por nome (já estava implementada)
  - Criação da view `create-content.php`
  - Implementação de salvamento de categorias no `update()`
  - Adição de coluna de categorias na listagem

---

## 📞 Suporte

Para dúvidas ou problemas relacionados a esta funcionalidade, consulte:
- Documentação de categorias: `docs/FASE_1_LOJA_E_ADMIN_CATALOGO.md`
- Documentação de produtos: `docs/FASE_5_ADMIN_PRODUTOS_EDICAO_MIDIA.md`

---

**Fim do Relatório**

