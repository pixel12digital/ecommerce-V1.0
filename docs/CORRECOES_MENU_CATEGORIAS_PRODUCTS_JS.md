# Correções: Menu Categorias e products.js em Produção

## 📋 Resumo do Problema

### Ambiente Local ✅
- Menu "Categorias" aparece abaixo de "Produtos"
- `products.js` carrega corretamente
- Modal de categorias funciona
- Toggle de status funciona

### Ambiente Produção ❌
- Menu "Categorias" não aparece
- `products.js` retorna 404
- Modal de categorias não abre
- Toggle de status não funciona

## 🔍 Causas Identificadas

### 1. Menu "Categorias" não aparece
**Causa:** O arquivo `themes/default/admin/layouts/store.php` em produção pode estar desatualizado (versão antiga sem o item "Categorias").

**Solução:** O código já está correto no repositório. O item "Categorias" está dentro do bloco `<?php if ($canManageProducts): ?>`, logo abaixo de "Produtos" (linhas 681-686). Se não aparece em produção, o arquivo precisa ser atualizado no servidor.

### 2. products.js retorna 404
**Causa:** O caminho do script estava usando `$basePath` diretamente, que em produção pode estar vazio ou incorreto.

**Caminho antigo:**
```php
<script src="<?= $basePath ?>/admin/js/products.js"></script>
```

**Problema:** Em produção, se `$basePath` estiver vazio, a URL gerada seria `/admin/js/products.js`, que não existe. O arquivo físico está em `public/admin/js/products.js`, então em produção (DocumentRoot = `public_html/`) a URL correta é `/public/admin/js/products.js`.

**Solução:** Implementada função `admin_asset_path_products()` que detecta automaticamente o ambiente e gera o caminho correto.

## ✅ Correções Aplicadas

### FASE 1: Menu "Categorias"
**Arquivo:** `themes/default/admin/layouts/store.php`

**Status:** ✅ Código já está correto no repositório

O item "Categorias" está implementado nas linhas 681-686:
```php
<?php if ($canManageProducts): ?>
<li>
    <a href="<?= $basePath ?>/admin/produtos" class="...">
        <i class="bi bi-box-seam icon"></i>
        <span>Produtos</span>
    </a>
</li>
<li>
    <a href="<?= $basePath ?>/admin/categorias" class="..." style="padding-left: 2.5rem;">
        <i class="bi bi-tags icon"></i>
        <span>Categorias</span>
    </a>
</li>
<?php endif; ?>
```

**Ação necessária:** Garantir que este arquivo esteja atualizado em produção.

### FASE 2: Caminho do products.js
**Arquivo:** `themes/default/admin/products/index-content.php`

**Mudança:** Substituído caminho direto por função de detecção automática:

```php
<?php
/**
 * Helper para gerar caminho de assets do admin
 * Detecta automaticamente o ambiente (dev vs produção)
 */
function admin_asset_path_products($relativePath) {
    $relativePath = ltrim($relativePath, '/');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Se REQUEST_URI ou SCRIPT_NAME contém /ecommerce-v1.0/public, estamos em dev
    if (strpos($requestUri, '/ecommerce-v1.0/public') !== false || 
        strpos($scriptName, '/ecommerce-v1.0/public') !== false) {
        return '/ecommerce-v1.0/public/admin/' . $relativePath;
    }
    
    // Em produção na Hostinger:
    // - DocumentRoot aponta para public_html/ (raiz do projeto)
    // - Arquivos físicos estão em public_html/public/admin/js/...
    // - Para acessar via URL, precisamos usar /public/admin/...
    return '/public/admin/' . $relativePath;
}

$productsJsPath = admin_asset_path_products('js/products.js');
?>
<script src="<?= htmlspecialchars($productsJsPath) ?>" onerror="console.error('Erro ao carregar products.js:', this.src);"></script>
```

**Resultado:**
- **Local:** `/ecommerce-v1.0/public/admin/js/products.js`
- **Produção:** `/public/admin/js/products.js`

### FASE 3: Detecção de basePath no media-picker.js
**Arquivo:** `public/admin/js/media-picker.js`

**Mudança:** Priorizado `window.basePath` (definido no layout PHP) sobre detecção automática do script src.

**Antes:** Tentava detectar basePath do script src, o que em produção gerava `/public` incorretamente.

**Depois:** 
1. Primeiro tenta usar `window.basePath` (definido no layout)
2. Fallback para detecção do script src apenas se necessário
3. Normalização melhorada para remover protocolo/domínio

### FASE 4: Log de inicialização no products.js
**Arquivo:** `public/admin/js/products.js`

**Mudança:** Adicionado log de inicialização para facilitar debug:

```javascript
console.log('[Produtos] JS inicializado');
console.log('[Produtos] basePath obtido de window.basePath:', basePath);
```

## 📝 Checklist de Deploy

Para garantir que tudo funcione em produção:

1. ✅ **Atualizar `themes/default/admin/layouts/store.php`**
   - Garantir que o item "Categorias" (linhas 681-686) esteja presente

2. ✅ **Atualizar `themes/default/admin/products/index-content.php`**
   - Garantir que a função `admin_asset_path_products()` esteja implementada
   - Garantir que o script use `$productsJsPath` ao invés de `$basePath` direto

3. ✅ **Atualizar `public/admin/js/products.js`**
   - Garantir que os logs de inicialização estejam presentes

4. ✅ **Atualizar `public/admin/js/media-picker.js`**
   - Garantir que priorize `window.basePath`

5. ✅ **Verificar permissões do usuário**
   - O usuário deve ter permissão `manage_products` para ver o menu "Categorias"

## 🧪 Testes em Produção

Após o deploy, verificar:

### Menu Lateral
- [ ] Item "Categorias" aparece abaixo de "Produtos"
- [ ] Ao clicar, abre `/admin/categorias` normalmente

### Tela /admin/produtos
- [ ] Console não mostra 404 para `products.js`
- [ ] Aparece o log `[Produtos] JS inicializado`
- [ ] Ao clicar no ícone de editar categorias:
  - [ ] Modal abre com categorias marcadas corretamente
  - [ ] Ao salvar, badges são atualizados sem recarregar
- [ ] Ao clicar no status (toggle Ativo/Inativo):
  - [ ] Status é atualizado visualmente
  - [ ] Status é atualizado no banco de dados

### Console do Navegador
- [ ] `[Produtos] JS inicializado` aparece
- [ ] `[Produtos] basePath obtido de window.basePath: ...` aparece
- [ ] `[Media Picker] basePath obtido de window.basePath: ...` aparece
- [ ] Nenhum erro 404 para `products.js`

## 🔗 Arquivos Modificados

1. `themes/default/admin/products/index-content.php`
   - Adicionada função `admin_asset_path_products()`
   - Corrigido caminho do script `products.js`

2. `public/admin/js/products.js`
   - Adicionado log de inicialização
   - Melhorado log de detecção de basePath

3. `public/admin/js/media-picker.js`
   - Priorizado `window.basePath` sobre detecção automática
   - Melhorada normalização de basePath

## 📌 Notas Importantes

1. **Menu "Categorias"**: Se ainda não aparecer após atualizar o arquivo, verificar:
   - Se o usuário tem permissão `manage_products`
   - Se há cache do navegador (fazer hard refresh: Ctrl+F5)

2. **products.js 404**: Se ainda ocorrer, verificar:
   - Se o arquivo físico existe em `public/admin/js/products.js`
   - Se o Apache está configurado para servir arquivos de `public/`
   - Se há regras de `.htaccess` bloqueando o acesso

3. **basePath**: O `window.basePath` é definido no layout `store.php` (linha 853) e também na view `index-content.php` (linha 350). Ambos devem estar sincronizados.

## 🎯 Resultado Esperado

Após aplicar todas as correções:

- ✅ Menu "Categorias" visível em produção
- ✅ `products.js` carrega sem 404
- ✅ Modal de categorias funciona
- ✅ Toggle de status funciona
- ✅ Logs de debug facilitam troubleshooting futuro

