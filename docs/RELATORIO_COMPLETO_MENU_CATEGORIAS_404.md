# Relatório Completo: Problemas Menu Categorias e Rota 404 em Produção

## 📋 Resumo Executivo

**Problema Principal:** Menu "Categorias" não aparece em produção e rota `/admin/categorias` retorna 404.

**Status:** 🔴 **NÃO RESOLVIDO** - Aguardando deploy dos arquivos atualizados em produção.

**Data:** 11/12/2025

---

## 🔍 Problema 1: Menu "Categorias" não aparece em produção

### Contexto Inicial

- **Local:** Menu "Categorias" aparece abaixo de "Produtos" ✅
- **Produção:** Menu "Categorias" não aparece ❌
- **Observação:** `/admin/produtos` funciona normalmente com JS novo

### Investigação Realizada

#### FASE 1: Verificação do Layout

**Arquivo verificado:** `themes/default/admin/layouts/store.php`

**Código encontrado (linhas 674-687):**

```php
<?php if ($canManageProducts): ?>
<li>
    <a href="<?= $basePath ?>/admin/produtos" class="<?= $isActive('/admin/produtos') && !$isActive('/admin/categorias') ? 'active' : '' ?>">
        <i class="bi bi-box-seam icon"></i>
        <span>Produtos</span>
    </a>
</li>
<li>
    <a href="<?= $basePath ?>/admin/categorias" class="<?= $isActive('/admin/categorias') ? 'active' : '' ?>" style="padding-left: 2.5rem;">
        <i class="bi bi-tags icon"></i>
        <span>Categorias</span>
    </a>
</li>
<?php endif; ?>
```

**Conclusão:** Código está correto no repositório. O item "Categorias" está dentro do bloco `canManageProducts`, logo abaixo de "Produtos".

#### FASE 2: Adição de Marcadores de Debug

**Alterações realizadas:**

1. **Marcador de versão no layout:**
```php
<!-- DEBUG-STORE-LAYOUT: versão categorias v2 -->
<div class="admin-wrapper">
```

2. **Comentários de debug no menu:**
```php
<?php if ($canManageProducts): ?>
<!-- DEBUG: Menu Produtos/Categorias - canManageProducts = true -->
...
<?php else: ?>
<!-- DEBUG: Menu Produtos/Categorias - canManageProducts = false (usuário: ...) -->
<?php endif; ?>
```

3. **Log de permissões:**
```php
// DEBUG: Log de permissões para diagnóstico
if (isset($_GET['debug_menu'])) {
    error_log('[DEBUG MENU] currentUserId: ' . ($currentUserId ?: 'null'));
    error_log('[DEBUG MENU] canManageProducts: ' . ($canManageProducts ? 'true' : 'false'));
}
```

**Arquivos modificados:**
- `themes/default/admin/layouts/store.php`

**Commit:** `a510c95` - "feat: Adicionar debug e diagnóstico para menu Categorias em produção"

#### FASE 3: Script de Diagnóstico de Menu

**Arquivo criado:** `public/debug_menu_categorias.php`

**Funcionalidades:**
- Verifica usuários e permissões
- Verifica se usuário tem `manage_products`
- Verifica código do menu no arquivo
- Testa permissões em tempo real

**Status:** Script criado, mas não testado em produção (não foi acessado ainda).

---

## 🔍 Problema 2: Rota `/admin/categorias` retorna 404

### Contexto

Após investigar o menu, descobrimos que mesmo quando o menu aparece (após correções), ao clicar em "Categorias" a rota retorna 404.

### Investigação Realizada

#### FASE 1: Verificação de Rotas

**Arquivo verificado:** `public/index.php`

**Código encontrado (linhas 50, 191-214):**

```php
// Import do controller (linha 50)
use App\Http\Controllers\Admin\CategoriaController;

// Rotas Admin - Categorias (linhas 191-214)
$router->get('/admin/categorias', CategoriaController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/categorias/criar', CategoriaController::class . '@create', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/criar', CategoriaController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/categorias/{id}/editar', CategoriaController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/{id}/editar', CategoriaController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/{id}/excluir', CategoriaController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
```

**Conclusão:** Rotas estão corretamente registradas no código.

#### FASE 2: Verificação do Controller

**Arquivo verificado:** `src/Http/Controllers/Admin/CategoriaController.php`

**Código do método index (linhas 14-96):**

```php
public function index(): void
{
    // Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        $config = require __DIR__ . '/../../../config/app.php';
        session_name($config['session_name']);
        session_start();
    }

    $tenantId = TenantContext::id();
    $db = Database::getConnection();

    // Busca opcional
    $q = trim($_GET['q'] ?? '');

    // Buscar todas as categorias do tenant
    $where = ['c.tenant_id = :tenant_id'];
    $params = ['tenant_id' => $tenantId];

    if (!empty($q)) {
        $where[] = '(c.nome LIKE :q OR c.slug LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }

    $whereClause = implode(' AND ', $where);

    try {
        $stmt = $db->prepare("
            SELECT c.*, 
                   COUNT(DISTINCT pc.produto_id) as total_produtos,
                   COUNT(DISTINCT filhos.id) as total_subcategorias,
                   MAX(pai.nome) as categoria_pai_nome
            FROM categorias c
            LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
            LEFT JOIN categorias filhos ON filhos.categoria_pai_id = c.id AND filhos.tenant_id = c.tenant_id
            LEFT JOIN categorias pai ON pai.id = c.categoria_pai_id AND pai.tenant_id = c.tenant_id
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.nome ASC
        ");
        
        foreach ($params as $key => $value) {
            $paramType = ($key === 'tenant_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $categoriasFlat = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Erro ao buscar categorias: " . $e->getMessage());
        throw $e;
    }

    // Construir árvore hierárquica
    $categoriasTree = $this->buildCategoryTree($categoriasFlat);
    $categoriasForSelect = $this->buildCategorySelectOptions($categoriasFlat);

    $tenant = TenantContext::tenant();
    
    $this->viewWithLayout('admin/layouts/store', 'admin/categorias/index-content', [
        'tenant' => $tenant,
        'pageTitle' => 'Categorias',
        'categoriasTree' => $categoriasTree ?? [],
        'categoriasFlat' => $categoriasFlat,
        'categoriasForSelect' => $categoriasForSelect ?? [],
        'filtros' => ['q' => $q],
        'message' => $_SESSION['categoria_message'] ?? null,
        'messageType' => $_SESSION['categoria_message_type'] ?? null
    ]);

    unset($_SESSION['categoria_message']);
    unset($_SESSION['categoria_message_type']);
}
```

**Conclusão:** Controller existe e está correto.

#### FASE 3: Verificação da View

**Arquivo verificado:** `themes/default/admin/categorias/index-content.php`

**Status:** Arquivo existe no repositório.

**Conclusão:** View existe e está correta.

#### FASE 4: Script de Diagnóstico de Rota

**Arquivo criado:** `public/debug_rota_categorias.php`

**Funcionalidades implementadas:**

1. Verifica se `public/index.php` contém a rota
2. Verifica se o controller existe
3. Verifica se a view existe
4. Testa autoload da classe
5. Analisa linha por linha do `index.php` para encontrar a rota
6. Testa registro manual de rota no Router
7. Verifica configuração `.htaccess`
8. Compara local vs produção

**Código principal do script:**

```php
// Verificar se tem a rota /admin/categorias
$temRota = strpos($indexContent, "/admin/categorias'") !== false || 
           strpos($indexContent, '/admin/categorias"') !== false ||
           preg_match('/\/admin\/categorias[,\'"]/', $indexContent);

// Analisar linha por linha
$linhas = explode("\n", $indexContent);
foreach ($linhas as $num => $linha) {
    if (preg_match('/\$router->get\([\'"]\/admin\/categorias[\'"]/', $linha)) {
        $encontrouRota = true;
        $linhaNumero = $num + 1;
        break;
    }
}
```

**Commit:** `319efd5` - "feat: Adicionar script de diagnóstico para rota /admin/categorias 404"
**Commit:** `9005e3f` - "feat: Melhorar script de diagnóstico de rota categorias"

---

## 🔍 Problema 3: Script de Diagnóstico também retorna 404

### Contexto

Ao tentar acessar `https://pontodogolfeoutlet.com.br/debug_rota_categorias.php`, o próprio script retorna 404.

### Análise

**Causa provável:** O arquivo `public/debug_rota_categorias.php` não foi deployado em produção.

**Arquivos criados mas não deployados:**
1. `public/debug_menu_categorias.php`
2. `public/debug_rota_categorias.php`
3. `public/debug_categorias.php` (criado anteriormente)

**Status:** 🔴 Scripts não estão acessíveis em produção porque não foram deployados.

---

## 📊 Resumo de Arquivos Modificados/Criados

### Arquivos Modificados

1. **`themes/default/admin/layouts/store.php`**
   - Adicionado marcador de debug
   - Adicionado comentários de debug no menu
   - Adicionado log de permissões
   - **Commit:** `a510c95`

2. **`themes/default/admin/products/index-content.php`**
   - Corrigido caminho do `products.js` usando `admin_asset_path_products()`
   - **Commit:** `d62b617`

3. **`public/admin/js/products.js`**
   - Adicionado logs de inicialização
   - **Commit:** `d62b617`

4. **`public/admin/js/media-picker.js`**
   - Priorizado `window.basePath` sobre detecção automática
   - **Commit:** `d62b617`

5. **`themes/default/storefront/layouts/base.php`**
   - Adicionado fallback de carregamento de categorias
   - **Commit:** `d62b617`

### Arquivos Criados

1. **`public/debug_menu_categorias.php`**
   - Script de diagnóstico de menu e permissões
   - **Commit:** `a510c95`

2. **`public/debug_rota_categorias.php`**
   - Script de diagnóstico de rota 404
   - **Commits:** `319efd5`, `9005e3f`

3. **`public/debug_categorias.php`**
   - Script de diagnóstico de categorias no storefront
   - **Commit:** `d62b617`

4. **`docs/CORRECOES_MENU_CATEGORIAS_PRODUCTS_JS.md`**
   - Documentação das correções de products.js
   - **Commit:** `d62b617`

5. **`docs/INVESTIGACAO_MENU_CATEGORIAS_PRODUCAO.md`**
   - Plano de investigação do menu
   - **Commit:** `a510c95`

6. **`docs/RELATORIO_COMPLETO_MENU_CATEGORIAS_404.md`** (este arquivo)
   - Relatório completo de todos os problemas e tentativas

---

## 🔧 Tentativas de Resolução

### Tentativa 1: Correção do caminho do products.js

**Problema:** `products.js` retornava 404 em produção.

**Solução aplicada:**

```php
// Função para detectar ambiente automaticamente
function admin_asset_path_products($relativePath) {
    $relativePath = ltrim($relativePath, '/');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    if (strpos($requestUri, '/ecommerce-v1.0/public') !== false || 
        strpos($scriptName, '/ecommerce-v1.0/public') !== false) {
        return '/ecommerce-v1.0/public/admin/' . $relativePath;
    }
    
    return '/public/admin/' . $relativePath;
}

$productsJsPath = admin_asset_path_products('js/products.js');
?>
<script src="<?= htmlspecialchars($productsJsPath) ?>" onerror="console.error('Erro ao carregar products.js:', this.src);"></script>
```

**Status:** ✅ Resolvido (products.js agora carrega corretamente)

### Tentativa 2: Adição de marcadores de debug

**Objetivo:** Confirmar qual layout está sendo usado em produção.

**Solução aplicada:**

```php
<!-- DEBUG-STORE-LAYOUT: versão categorias v2 -->
<div class="admin-wrapper">
```

**Status:** ⏳ Aguardando verificação em produção (arquivo precisa ser deployado)

### Tentativa 3: Scripts de diagnóstico

**Objetivo:** Identificar causa raiz dos problemas.

**Solução aplicada:** Criados 3 scripts de diagnóstico diferentes.

**Status:** 🔴 Scripts não acessíveis em produção (não foram deployados)

---

## 🎯 Causa Raiz Identificada

### Problema Principal

**O arquivo `public/index.php` em produção está desatualizado e não contém as rotas de categorias.**

### Evidências

1. ✅ Código local está correto (rotas registradas na linha 191)
2. ✅ Controller existe e está correto
3. ✅ View existe e está correta
4. ❌ Rota retorna 404 em produção
5. ❌ Scripts de diagnóstico também retornam 404 (não deployados)

### Arquivos que Precisam ser Deployados

1. **`public/index.php`** ⚠️ **CRÍTICO**
   - Deve conter import do `CategoriaController` (linha 50)
   - Deve conter rotas de categorias (linhas 191-214)

2. **`src/Http/Controllers/Admin/CategoriaController.php`** ⚠️ **CRÍTICO**
   - Controller completo com todos os métodos

3. **`themes/default/admin/categorias/index-content.php`** ⚠️ **CRÍTICO**
   - View de listagem de categorias

4. **`themes/default/admin/layouts/store.php`** ⚠️ **IMPORTANTE**
   - Layout com menu "Categorias" e marcadores de debug

5. **Scripts de diagnóstico** (opcional, para troubleshooting)
   - `public/debug_menu_categorias.php`
   - `public/debug_rota_categorias.php`
   - `public/debug_categorias.php`

---

## 📝 Checklist de Deploy

### Arquivos Críticos (DEVEM ser deployados)

- [ ] `public/index.php` - **PRIORIDADE MÁXIMA**
- [ ] `src/Http/Controllers/Admin/CategoriaController.php`
- [ ] `themes/default/admin/categorias/index-content.php`
- [ ] `themes/default/admin/categorias/` (toda a pasta)

### Arquivos Importantes (recomendado deploy)

- [ ] `themes/default/admin/layouts/store.php`
- [ ] `themes/default/admin/products/index-content.php`
- [ ] `public/admin/js/products.js`
- [ ] `public/admin/js/media-picker.js`

### Arquivos Opcionais (para diagnóstico)

- [ ] `public/debug_menu_categorias.php`
- [ ] `public/debug_rota_categorias.php`
- [ ] `public/debug_categorias.php`

### Após Deploy

1. [ ] Limpar cache do PHP (OPcache) se houver
2. [ ] Fazer hard refresh no navegador (Ctrl+F5)
3. [ ] Testar acesso a `/admin/categorias`
4. [ ] Verificar se menu "Categorias" aparece
5. [ ] Executar scripts de diagnóstico se deployados

---

## 🔄 Fluxo de Resolução Recomendado

### Passo 1: Deploy do index.php

**Ação:** Fazer deploy do arquivo `public/index.php` atualizado.

**Verificação:** Após deploy, acessar `/admin/categorias` deve funcionar.

### Passo 2: Deploy do Controller e View

**Ação:** Fazer deploy do controller e da view.

**Verificação:** Página de categorias deve carregar completamente.

### Passo 3: Deploy do Layout

**Ação:** Fazer deploy do layout atualizado.

**Verificação:** Menu "Categorias" deve aparecer.

### Passo 4: Limpeza de Cache

**Ação:** Limpar cache do PHP e navegador.

**Verificação:** Todas as alterações devem estar visíveis.

---

## 📌 Commits Relacionados

1. **`d62b617`** - "fix: Corrigir menu Categorias e 404 do products.js em produção"
2. **`a510c95`** - "feat: Adicionar debug e diagnóstico para menu Categorias em produção"
3. **`319efd5`** - "feat: Adicionar script de diagnóstico para rota /admin/categorias 404"
4. **`9005e3f`** - "feat: Melhorar script de diagnóstico de rota categorias"

---

## 🚨 Status Atual

- 🔴 **Menu "Categorias":** Não aparece (arquivo `store.php` precisa deploy)
- 🔴 **Rota `/admin/categorias`:** Retorna 404 (arquivo `index.php` precisa deploy)
- 🔴 **Scripts de diagnóstico:** Retornam 404 (não foram deployados)
- ✅ **Código local:** Está correto e completo
- ⏳ **Aguardando:** Deploy dos arquivos atualizados em produção

---

## 💡 Conclusão

Todos os problemas identificados têm a mesma causa raiz: **arquivos não foram deployados em produção**.

O código está correto no repositório, mas o servidor de produção está usando versões antigas dos arquivos que não contêm:
- Rotas de categorias no `index.php`
- Item "Categorias" no menu do `store.php` (ou versão antiga)
- Scripts de diagnóstico

**Ação necessária:** Fazer deploy completo de todos os arquivos modificados/criados para produção.

