# Investigação: Menu "Categorias" não aparece em produção

## 📋 Contexto

- **Local:** Menu "Categorias" aparece abaixo de "Produtos" ✅
- **Produção:** Menu "Categorias" não aparece ❌
- **Observações:**
  - `/admin/produtos` funciona com JS novo (status rápido + modal de categorias)
  - `/admin/categorias` funciona se acessada diretamente via URL
  - Problema é **somente** o item do menu não aparecer

## 🔍 Plano de Investigação

### FASE 1: Confirmar qual layout está sendo usado

**Arquivo:** `themes/default/admin/layouts/store.php`

**Ação realizada:**
- ✅ Adicionado marcador de debug: `<!-- DEBUG-STORE-LAYOUT: versão categorias v2 -->`
- ✅ Adicionado comentário de debug no bloco do menu

**Como verificar em produção:**
1. Acesse `https://pontodogolfeoutlet.com.br/admin`
2. Veja o código-fonte da página (Ctrl+U)
3. Procure por `DEBUG-STORE-LAYOUT`

**Resultado esperado:**
- Se o marcador aparecer: confirma que `store.php` está sendo usado
- Se não aparecer: outro layout está sendo usado ou há cache

### FASE 2: Verificar código do menu "Categorias"

**Trecho correto do menu (linhas 674-687):**

```php
<?php if ($canManageProducts): ?>
<!-- DEBUG: Menu Produtos/Categorias - canManageProducts = true -->
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
<?php else: ?>
<!-- DEBUG: Menu Produtos/Categorias - canManageProducts = false (usuário: ...) -->
<?php endif; ?>
```

**Verificações:**
1. ✅ Código está correto no repositório
2. ⚠️ **Verificar se está no servidor de produção:**
   - Conectar via SSH/FTP
   - Abrir `themes/default/admin/layouts/store.php` diretamente no servidor
   - Confirmar que o trecho acima está presente

### FASE 3: Testar se problema é permissão ou layout

**Código de debug adicionado:**

```php
// DEBUG: Log de permissões para diagnóstico
if (isset($_GET['debug_menu'])) {
    error_log('[DEBUG MENU] currentUserId: ' . ($currentUserId ?: 'null'));
    error_log('[DEBUG MENU] canManageProducts: ' . ($canManageProducts ? 'true' : 'false'));
}
```

**Como testar:**
1. Acesse `https://pontodogolfeoutlet.com.br/admin?debug_menu=1`
2. Verifique os logs do servidor (ou use o script `debug_menu_categorias.php`)
3. Verifique se `canManageProducts` é `true` ou `false`

**Teste temporário (forçar exibição):**

Se quiser testar forçando a exibição, temporariamente altere:

```php
<?php
// DEBUG: forçar exibição do menu de produtos/categorias
$canManageProductsDebug = true;
?>
<?php if ($canManageProductsDebug): ?>
    ...
<?php endif; ?>
```

**Resultado esperado:**
- Se aparecer com `$canManageProductsDebug = true`: problema é permissão
- Se não aparecer mesmo forçando: problema é layout/cache

### FASE 4: Verificar permissões do usuário

**Script de diagnóstico criado:** `public/debug_menu_categorias.php`

**Como usar:**
1. Acesse `https://pontodogolfeoutlet.com.br/debug_menu_categorias.php`
2. O script mostrará:
   - Todos os usuários do tenant
   - Permissões de cada usuário
   - Se algum usuário tem `manage_products`
   - Código do menu no arquivo

**Verificação manual no banco:**

```sql
-- Verificar usuários do tenant
SELECT id, nome, email, ativo 
FROM store_users 
WHERE tenant_id = 1;

-- Verificar permissões de um usuário específico
SELECT p.permission_key 
FROM store_user_permissions sup
INNER JOIN store_permissions p ON p.id = sup.permission_id
WHERE sup.user_id = 1;

-- Verificar se manage_products existe
SELECT * FROM store_permissions WHERE permission_key = 'manage_products';
```

**Solução se falta permissão:**

```sql
-- Adicionar permissão manage_products para um usuário
INSERT INTO store_user_permissions (user_id, permission_id)
SELECT 1, id 
FROM store_permissions 
WHERE permission_key = 'manage_products';
```

### FASE 5: Verificar duplicidade de menu

**Arquivos encontrados:**
- ✅ `themes/default/admin/layouts/store.php` - **USADO** (todos os controllers usam este)
- ⚠️ `themes/default/admin/layout/app.php` - **NÃO USADO** (layout antigo)

**Verificação:**
- ✅ Nenhum controller usa `layout/app`
- ✅ Todos os controllers usam `admin/layouts/store`

**Conclusão:** Não há duplicidade de menu.

## 🛠️ Correções Aplicadas

1. ✅ Adicionado marcador de debug no layout
2. ✅ Adicionado comentários de debug no bloco do menu
3. ✅ Adicionado log de permissões (com `?debug_menu=1`)
4. ✅ Criado script de diagnóstico `debug_menu_categorias.php`

## 📝 Checklist de Testes em Produção

Após fazer deploy das alterações:

### 1. Verificar marcador de debug
- [ ] Acessar `https://pontodogolfeoutlet.com.br/admin`
- [ ] Ver código-fonte (Ctrl+U)
- [ ] Procurar por `DEBUG-STORE-LAYOUT`
- [ ] Se não aparecer: arquivo não foi atualizado ou há cache

### 2. Verificar código do menu
- [ ] No código-fonte, procurar por `<span>Categorias</span>`
- [ ] Se não aparecer: arquivo `store.php` está desatualizado no servidor
- [ ] Se aparecer mas não renderiza: problema de permissão

### 3. Verificar permissões
- [ ] Acessar `https://pontodogolfeoutlet.com.br/debug_menu_categorias.php`
- [ ] Verificar se usuário logado tem `manage_products`
- [ ] Se não tiver: adicionar permissão via SQL ou interface admin

### 4. Testar com debug
- [ ] Acessar `https://pontodogolfeoutlet.com.br/admin?debug_menu=1`
- [ ] Verificar logs do servidor
- [ ] Confirmar valor de `canManageProducts`

### 5. Teste final
- [ ] Menu "Categorias" aparece abaixo de "Produtos"
- [ ] Ao clicar, abre `/admin/categorias` normalmente
- [ ] Remover código de debug após confirmar funcionamento

## 🎯 Possíveis Causas e Soluções

### Causa 1: Arquivo store.php desatualizado no servidor
**Sintoma:** Marcador `DEBUG-STORE-LAYOUT` não aparece no código-fonte

**Solução:**
1. Fazer deploy do arquivo `themes/default/admin/layouts/store.php` atualizado
2. Limpar cache do PHP (OPcache) se houver
3. Fazer hard refresh no navegador (Ctrl+F5)

### Causa 2: Usuário sem permissão manage_products
**Sintoma:** Marcador aparece, mas menu não renderiza. `canManageProducts = false`

**Solução:**
1. Adicionar permissão `manage_products` para o usuário
2. Via SQL (ver FASE 4)
3. Ou via interface admin (se houver)

### Causa 3: Cache do navegador
**Sintoma:** Arquivo atualizado, mas ainda não aparece

**Solução:**
1. Fazer hard refresh (Ctrl+F5)
2. Limpar cache do navegador
3. Testar em modo anônimo/privado

### Causa 4: Cache do PHP (OPcache)
**Sintoma:** Arquivo atualizado no servidor, mas mudanças não aparecem

**Solução:**
1. Reiniciar PHP-FPM
2. Ou limpar OPcache via script PHP:
   ```php
   opcache_reset();
   ```

## 📌 Arquivos Modificados

1. `themes/default/admin/layouts/store.php`
   - Adicionado marcador de debug
   - Adicionado comentários de debug
   - Adicionado log de permissões

2. `public/debug_menu_categorias.php` (novo)
   - Script de diagnóstico completo

3. `docs/INVESTIGACAO_MENU_CATEGORIAS_PRODUCAO.md` (este arquivo)
   - Documentação da investigação

## 🔗 Referências

- Documento anterior: `docs/CORRECOES_MENU_CATEGORIAS_PRODUCTS_JS.md`
- Script de diagnóstico: `public/debug_menu_categorias.php`
- Layout admin: `themes/default/admin/layouts/store.php`

