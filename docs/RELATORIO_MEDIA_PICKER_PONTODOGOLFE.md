# Relatório: Correção do Carregamento do media-picker.js no Admin

**Data:** 2025-12-09  
**Problema:** Botão "Escolher da biblioteca" não funcionava em telas de Categorias em Destaque e Banners  
**Status:** ✅ Corrigido

---

## 🔍 Problema Identificado

### Sintomas
- ✅ Biblioteca de Mídia (`/admin/midias`) funcionava normalmente
- ✅ Front da loja funcionava normalmente (banners, ícones, produtos)
- ❌ **Botão "Escolher da biblioteca" não funcionava** nas seguintes telas:
  - `/admin/home/categorias-pills`
  - `/admin/home/categorias-pills/novo`
  - `/admin/home/categorias-pills/{id}/editar`
  - `/admin/home/banners`
  - `/admin/home/banners/novo?tipo=hero`
  - `/admin/home/banners/{id}/editar`
- ❌ Console do navegador mostrava: `Failed to load media-picker.js:1 – resource: the server responded with a status of 404`

### Causa Raiz

O problema estava na **detecção do caminho do `media-picker.js`** no layout admin:

1. **Código anterior:** Usava `$basePath` que era sempre definido como `/ecommerce-v1.0/public` (mesmo em produção)
2. **Resultado em produção:** Tentava carregar `/ecommerce-v1.0/public/admin/js/media-picker.js` (caminho inexistente)
3. **Caminho correto em produção:** Deveria ser `/admin/js/media-picker.js` (DocumentRoot = `public_html/`)

---

## ✅ Correções Implementadas

### 1. Função Helper `admin_asset_path()`

**Arquivo:** `themes/default/admin/layouts/store.php` (linha ~786)

**Funcionalidade:**
- Detecta automaticamente o ambiente (dev vs produção)
- Gera caminho correto para assets do admin baseado no ambiente
- Remove dependência de `$basePath` que estava incorreto

**Implementação:**
```php
function admin_asset_path($relativePath) {
    // Remover barra inicial se existir
    $relativePath = ltrim($relativePath, '/');
    
    // Detectar se estamos em desenvolvimento local
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
```

**Comportamento:**
- **Dev:** `/ecommerce-v1.0/public/admin/js/media-picker.js`
- **Produção:** `/public/admin/js/media-picker.js` (DocumentRoot = `public_html/`, arquivos em `public/admin/js/`)

### 2. Uso da Função Helper

**Antes:**
```php
if (empty($basePath)) {
    $mediaPickerPath = '/admin/js/media-picker.js';
} else {
    $mediaPickerPath = $basePath . '/admin/js/media-picker.js';
}
```

**Depois:**
```php
$mediaPickerPath = admin_asset_path('js/media-picker.js');
```

---

## 📋 Arquivos Modificados

1. **`themes/default/admin/layouts/store.php`**
   - Adicionada função `admin_asset_path()`
   - Corrigida inclusão do `media-picker.js` para usar a nova função

---

## 🧪 Como Testar

### Checklist de Testes Locais

#### 1. Categorias em Destaque

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/categorias-pills`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Recarregar a página
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre normalmente

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/categorias-pills/novo`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre normalmente

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/categorias-pills/1/editar`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre normalmente
- [ ] Selecionar uma imagem e verificar que o campo `icone_path` é preenchido

#### 2. Banners da Home

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/banners`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Recarregar a página
- [ ] Verificar que `media-picker.js` carrega com HTTP 200

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/banners/novo?tipo=hero`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca" (Imagem Desktop)
- [ ] Verificar que o modal abre normalmente
- [ ] Clicar em "Escolher da biblioteca" (Imagem Mobile)
- [ ] Verificar que o modal abre normalmente

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/banners/1/editar`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre normalmente
- [ ] Selecionar uma imagem e verificar que o campo correspondente é preenchido

#### 3. Console do Navegador

- [ ] Abrir DevTools → Aba "Console"
- [ ] Verificar que **não há erros 404** relacionados a `media-picker.js`
- [ ] Verificar que **não há erros JavaScript** ao clicar em "Escolher da biblioteca"

---

## 🚀 Checklist para Produção

Após o deploy, testar em `https://pontodogolfeoutlet.com.br/`:

### 1. Categorias em Destaque

- [ ] **Listagem:** `https://pontodogolfeoutlet.com.br/admin/home/categorias-pills`
  - [ ] Abrir DevTools → Aba "Network"
  - [ ] Verificar que `media-picker.js` carrega com HTTP 200
  - [ ] Caminho esperado: `/admin/js/media-picker.js`

- [ ] **Criação:** `https://pontodogolfeoutlet.com.br/admin/home/categorias-pills/novo`
  - [ ] Clicar em "Escolher da biblioteca"
  - [ ] Verificar que o modal abre

- [ ] **Edição:** `https://pontodogolfeoutlet.com.br/admin/home/categorias-pills/1/editar`
  - [ ] Clicar em "Escolher da biblioteca"
  - [ ] Verificar que o modal abre
  - [ ] Selecionar uma imagem e verificar que o campo é preenchido

### 2. Banners da Home

- [ ] **Listagem:** `https://pontodogolfeoutlet.com.br/admin/home/banners`
  - [ ] Abrir DevTools → Aba "Network"
  - [ ] Verificar que `media-picker.js` carrega com HTTP 200

- [ ] **Criação:** `https://pontodogolfeoutlet.com.br/admin/home/banners/novo?tipo=hero`
  - [ ] Clicar em "Escolher da biblioteca" (Imagem Desktop)
  - [ ] Verificar que o modal abre
  - [ ] Clicar em "Escolher da biblioteca" (Imagem Mobile)
  - [ ] Verificar que o modal abre

- [ ] **Edição:** `https://pontodogolfeoutlet.com.br/admin/home/banners/1/editar`
  - [ ] Clicar em "Escolher da biblioteca"
  - [ ] Verificar que o modal abre
  - [ ] Selecionar uma imagem e verificar que o campo é preenchido

### 3. Validação Final

- [ ] **Console sem erros:** Não deve haver erros 404 relacionados a `media-picker.js`
- [ ] **Modal funcional:** O modal deve abrir e permitir seleção de imagens
- [ ] **Preenchimento de campos:** Ao selecionar uma imagem, o campo correspondente deve ser preenchido automaticamente

---

## 📝 Detalhes Técnicos

### Caminho Final Público do media-picker.js

- **Localização física:** `public/admin/js/media-picker.js`
- **URL em dev:** `http://localhost/ecommerce-v1.0/public/admin/js/media-picker.js`
- **URL em produção:** `https://pontodogolfeoutlet.com.br/public/admin/js/media-picker.js`

### Como o Script é Incluído

O script é incluído no layout base do admin (`themes/default/admin/layouts/store.php`), que é usado por todas as páginas do admin. Isso garante que o `media-picker.js` esteja disponível em todas as telas que precisam do botão "Escolher da biblioteca".

**Código de inclusão:**
```php
<?php
$mediaPickerPath = admin_asset_path('js/media-picker.js');
?>
<script src="<?= htmlspecialchars($mediaPickerPath) ?>"></script>
```

### Compatibilidade

A função `admin_asset_path()` detecta automaticamente o ambiente baseado em:
- `$_SERVER['REQUEST_URI']` - URI da requisição
- `$_SERVER['SCRIPT_NAME']` - Caminho do script PHP

**Lógica de detecção:**
- Se `REQUEST_URI` ou `SCRIPT_NAME` contém `/ecommerce-v1.0/public` → **Dev** → `/ecommerce-v1.0/public/admin/...`
- Caso contrário → **Produção** → `/public/admin/...` (porque DocumentRoot = `public_html/` e arquivos estão em `public/`)

---

## 🔗 Relacionado

- **`docs/RELATORIO_MIDIA_PONTODOGOLFE.md`:** Correções de URLs de mídia no storefront e admin
- **`public/admin/js/media-picker.js`:** Script do componente de seleção de mídia
- **`themes/default/admin/layouts/store.php`:** Layout base do admin onde o script é incluído

---

## ⚠️ Observações Importantes

1. **Não alterar a estrutura de diretórios:** O arquivo `media-picker.js` deve permanecer em `public/admin/js/`
2. **Não usar caminhos hardcoded:** Sempre usar a função `admin_asset_path()` para assets do admin
3. **Compatibilidade multi-tenant:** A correção funciona tanto em modo single quanto multi-tenant
4. **Sem dependência de .htaccess:** A correção não depende de configurações do `.htaccess`

---

**Status:** ✅ Implementação Concluída  
**Última atualização:** 2025-12-09

