# Onde Alterar "Loja Demo" no Painel Admin

Este documento explica onde a "Loja Demo" aparece no sistema e como alterá-la.

## 🎯 Método Recomendado: Interface Admin

**A partir de agora, você pode alterar essas informações diretamente pela interface do painel admin, sem precisar editar banco de dados ou código.**

### **Via Tema da Loja (Recomendado)**

1. Acesse o painel admin: `/admin/tema`
2. Role até a seção **"Informações da Loja"** (antes de "Contato e Endereço")
3. Preencha os campos:
   - **Nome da loja (painel/admin)**: Nome que aparece na sidebar
   - **Título base do painel (aba do navegador)**: Título padrão da aba
4. Clique em **"Salvar Tema"**

**O que acontece:**
- O nome da loja é salvo em `tenants.name` (banco de dados) e em `tenant_settings` (chave `admin_store_name`)
- O título base é salvo em `tenant_settings` (chave `admin_title_base`)
- As alterações são aplicadas imediatamente na próxima requisição

---

## 📍 Onde "Loja Demo" Aparece

### 1. **No Painel Admin (Sidebar Esquerda)**

**Localização no código:**
- **Arquivo:** `themes/default/admin/layouts/store.php`
- **Linha:** ~603

**Código atual:**
```php
// Obter nome da loja: priorizar admin_store_name (settings), depois tenant->name, depois 'Loja'
$adminStoreName = \App\Services\ThemeConfig::get('admin_store_name', '');
$storeName = !empty($adminStoreName) 
    ? htmlspecialchars($adminStoreName)
    : htmlspecialchars($tenant->name ?? 'Loja');
```

**Onde vem (ordem de prioridade):**
1. `tenant_settings.admin_store_name` (se configurado via Tema da Loja)
2. `tenants.name` (banco de dados)
3. `'Loja'` (fallback)

**Como alterar:**
1. **Via interface admin (recomendado):**
   - Acesse `/admin/tema` → Seção "Informações da Loja"
   - Preencha "Nome da loja (painel/admin)" e salve

2. **Via banco de dados (método antigo):**
   ```sql
   UPDATE tenants SET name = 'Nome da Sua Loja' WHERE id = 1;
   ```

3. **Via código (temporário para testes):**
   - Editar `themes/default/admin/layouts/store.php` linha ~603
   - Alterar para: `$storeName = 'Nome da Sua Loja';`

---

### 2. **Na Aba do Navegador (Title Tag)**

**Localização no código:**
- **Arquivo:** `themes/default/admin/layouts/store.php`
- **Linha:** ~6

**Código atual:**
```php
<?php
// Recuperar título base do painel a partir dos settings
$adminTitleBase = \App\Services\ThemeConfig::get('admin_title_base', 'Store Admin');
?>
<title><?= $pageTitle ?? $adminTitleBase ?></title>
```

**Onde vem (ordem de prioridade):**
1. `$pageTitle` (se passado pelo controller - título específico da página)
2. `tenant_settings.admin_title_base` (se configurado via Tema da Loja)
3. `'Store Admin'` (fallback padrão)

**Como alterar:**
1. **Via interface admin (recomendado):**
   - Acesse `/admin/tema` → Seção "Informações da Loja"
   - Preencha "Título base do painel (aba do navegador)" e salve

2. **Em cada controller específico (título por página):**
   - Editar o array passado para `viewWithLayout()` ou `view()`
   - Exemplo em `src/Http/Controllers/Admin/MediaLibraryController.php`:
     ```php
     $this->viewWithLayout('admin/layouts/store', 'admin/media/index', [
         'pageTitle' => 'Biblioteca de Mídia', // ← Título específico desta página
         // ...
     ]);
     ```

3. **Valor padrão global (código):**
   - Editar `themes/default/admin/layouts/store.php` linha ~6
   - Alterar para: `<title><?= $pageTitle ?? 'Seu Título Padrão' ?></title>`

---

## 🔍 Estrutura Completa

### Sidebar do Admin

```
themes/default/admin/layouts/store.php
├── Linha 603: $storeName = htmlspecialchars($tenant->name ?? 'Loja');
├── Linha 624: <span class="pg-admin-brand-store"><?= $storeName ?></span>
└── Linha 625: <span class="pg-admin-brand-subtitle">Store Admin</span>
```

**Fluxo de dados (atualizado):**
```
Tema da Loja (/admin/tema)
    ↓
Salva em tenant_settings (admin_store_name) + tenants.name
    ↓
ThemeConfig::get('admin_store_name')
    ↓
Se vazio: TenantContext::tenant()->name
    ↓
$storeName
    ↓
HTML da Sidebar
```

### Title da Página

```
Controller (pageTitle específico)
    ↓
Se não houver: ThemeConfig::get('admin_title_base')
    ↓
Se não houver: 'Store Admin' (fallback)
    ↓
themes/default/admin/layouts/store.php
    ↓
<title><?= $pageTitle ?? $adminTitleBase ?></title>
```

**Fluxo completo:**
```
Tela Tema da Loja (/admin/tema)
    ↓
Usuário preenche campos
    ↓
Salva em tenant_settings (admin_title_base)
    ↓
Layout store.php carrega via ThemeConfig::get()
    ↓
Usa como fallback quando pageTitle não está definido
```

---

## 📝 Exemplos Práticos

### Exemplo 1: Alterar nome da loja no banco

```sql
-- Conectar ao banco de dados
-- Executar:
UPDATE tenants 
SET name = 'Ponto do Golfe Outlet' 
WHERE id = 1;
```

**Resultado:**
- Sidebar mostrará: "Ponto do Golfe Outlet"
- Não altera o title da aba (continua sendo o `pageTitle` específico de cada página)

---

### Exemplo 2: Alterar title padrão de todas as páginas

**Arquivo:** `themes/default/admin/layouts/store.php`

**Antes:**
```php
<title><?= $pageTitle ?? 'Store Admin' ?></title>
```

**Depois:**
```php
<title><?= $pageTitle ?? 'Ponto do Golfe - Admin' ?></title>
```

**Resultado:**
- Páginas sem `pageTitle` definido mostrarão "Ponto do Golfe - Admin"
- Páginas com `pageTitle` específico continuarão mostrando seu título

---

### Exemplo 3: Alterar title de uma página específica

**Arquivo:** `src/Http/Controllers/Admin/MediaLibraryController.php`

**Antes:**
```php
$this->viewWithLayout('admin/layouts/store', 'admin/media/index', [
    'pageTitle' => 'Biblioteca de Mídia',
    // ...
]);
```

**Depois:**
```php
$this->viewWithLayout('admin/layouts/store', 'admin/media/index', [
    'pageTitle' => 'Biblioteca de Mídia - Ponto do Golfe',
    // ...
]);
```

---

## ⚠️ Observações Importantes

1. **"Loja Demo" na Sidebar:**
   - **NOVO:** Pode ser alterado via interface admin em `/admin/tema` → Seção "Informações da Loja"
   - Salvo em `tenant_settings.admin_store_name` e sincronizado com `tenants.name`
   - Se não configurado, usa `tenants.name` (compatibilidade retroativa)
   - Ainda pode ser alterado via SQL se necessário

2. **"Store Admin" na Sidebar:**
   - É texto fixo no código (`themes/default/admin/layouts/store.php` linha 625)
   - Para alterar, editar diretamente o arquivo

3. **Title da Aba:**
   - **NOVO:** Título base configurável via `/admin/tema` → Seção "Informações da Loja"
   - Salvo em `tenant_settings.admin_title_base`
   - Cada página pode ter seu próprio `pageTitle` (prioridade maior)
   - Se não houver `pageTitle`, usa `admin_title_base`
   - Se não houver `admin_title_base`, usa "Store Admin" (fallback)

4. **Persistência:**
   - Alterações via interface admin são permanentes e sincronizadas
   - Alterações no banco de dados são permanentes
   - Alterações no código são perdidas em atualizações do sistema
   - **Recomendado:** Usar a interface admin (`/admin/tema`) para alterações

---

## 🗂️ Arquivos Relacionados

- `themes/default/admin/layouts/store.php` - Layout principal do admin
- `src/Http/Controllers/Admin/*.php` - Controllers que definem `pageTitle`
- `database/seeds/001_initial_seed.php` - Seed inicial que cria "Loja Demo"
- Tabela `tenants` no banco de dados - Armazena o nome da loja

---

## 📌 Resumo Rápido

| Onde Aparece | Onde Alterar | Tipo | Prioridade |
|--------------|--------------|------|------------|
| **Sidebar (nome da loja)** | Interface: `/admin/tema` → "Informações da Loja" | Dinâmico (Settings + BD) | ⭐ **Recomendado** |
| **Sidebar (nome da loja)** | Banco: `tenants.name` | Dinâmico (BD) | Método antigo |
| **Sidebar ("Store Admin")** | Código: `store.php:625` | Fixo (código) | - |
| **Aba do navegador (padrão)** | Interface: `/admin/tema` → "Informações da Loja" | Dinâmico (Settings) | ⭐ **Recomendado** |
| **Aba do navegador (específico)** | Controller: `pageTitle` | Dinâmico (código) | Prioridade maior |

---

**Última atualização:** 2025-01-XX

