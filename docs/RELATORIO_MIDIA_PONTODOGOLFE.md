# Relatório: Correção de URLs de Mídia no Storefront (Ponto do Golfe)

**Data:** 2025-12-09  
**Problema:** Imagens não apareciam no storefront (hero, categorias, produtos) em produção  
**Status:** ✅ Corrigido

---

## 🔍 Problema Identificado

### Sintomas
- ✅ Imagens apareciam normalmente na **Biblioteca de Mídia** (admin `/admin/midias`)
- ✅ URLs geradas estavam corretas (`/uploads/tenants/1/banners/...`)
- ✅ Imagens carregavam quando acessadas diretamente pela URL
- ❌ **Imagens não apareciam no storefront** (home, listagem de produtos, PDP)
- ❌ Hero banners: área branca com placeholder cinza
- ❌ Categorias (bolotas): apenas círculos brancos sem imagens
- ❌ Produtos: cards sem fotos

### Causa Raiz

O problema estava na **forma como as URLs de mídia eram geradas no storefront**:

1. **Inconsistência entre admin e storefront:**
   - **Admin (Biblioteca de Mídia):** Usava `$basePath . htmlspecialchars($img['url'])` onde `$img['url']` já vinha como `/uploads/tenants/...`
   - **Storefront:** Usava `$basePath ?>/<?= htmlspecialchars($banner['imagem_desktop']) ?>` onde `$banner['imagem_desktop']` também vinha como `/uploads/tenants/...`

2. **Problema de concatenação:**
   - Se `$basePath` fosse vazio (produção) e o caminho já começasse com `/`, poderia gerar `//uploads` (dupla barra)
   - Se `$basePath` fosse `/ecommerce-v1.0/public` e o caminho começasse com `/`, funcionava, mas não era padronizado

3. **Falta de helper centralizado:**
   - Cada view gerava URLs de forma diferente
   - Sem normalização consistente
   - Difícil manter e debugar

---

## ✅ Correções Implementadas

### 1. Helper Centralizado `MediaUrlHelper`

**Arquivo:** `src/Support/MediaUrlHelper.php` (NOVO)

**Funcionalidade:**
- Classe estática para normalizar URLs de mídia
- Método `url(string $relativePath): string` que:
  - Detecta `basePath` automaticamente (dev vs produção)
  - Normaliza caminhos (remove barras duplicadas, garante `/` inicial)
  - Retorna URL completa e consistente

**Uso:**
```php
use App\Support\MediaUrlHelper;

// Em views, usar função helper:
function media_url(string $relativePath): string {
    return MediaUrlHelper::url($relativePath);
}

// Exemplo:
<img src="<?= media_url($banner['imagem_desktop']) ?>">
```

**Comportamento:**
- **Dev:** `/ecommerce-v1.0/public/uploads/tenants/1/banners/golfe04.webp`
- **Produção:** `/uploads/tenants/1/banners/golfe04.webp`
- **Normalização:** Remove barras duplicadas, garante formato correto

### 2. Refatoração do Storefront

#### 2.1. Home (`themes/default/storefront/home.php`)

**Alterações:**
- ✅ Adicionado `use App\Support\MediaUrlHelper` e função helper `media_url()`
- ✅ Hero banners: `src="<?= media_url($imagemDesktop) ?>"`
- ✅ Hero banners mobile: `srcset="<?= media_url($banner['imagem_mobile']) ?>"`
- ✅ Banners portrait: `background-image: url('<?= media_url($imagemBanner) ?>')`
- ✅ Categorias (bolotas): `src="<?= media_url($pill['icone_path']) ?>"`
- ✅ Produtos em destaque: `src="<?= media_url($produto['imagem_principal']['caminho_arquivo']) ?>"`
- ✅ Logo: `src="<?= media_url($theme['logo_url']) ?>"`

#### 2.2. Listagem de Produtos (`themes/default/storefront/products/index.php`)

**Alterações:**
- ✅ Adicionado helper `media_url()`
- ✅ Cards de produtos: `src="<?= media_url($produto['imagem_principal']['caminho_arquivo']) ?>"`

#### 2.3. Página de Produto (PDP) (`themes/default/storefront/products/show.php`)

**Alterações:**
- ✅ Adicionado helper `media_url()`
- ✅ Imagem principal: `src="<?= media_url($imagemPrincipal['caminho_arquivo']) ?>"`
- ✅ Thumbnails: `src="<?= media_url($imagem['caminho_arquivo']) ?>"`
- ✅ Produtos relacionados: `src="<?= media_url($prodRel['imagem_principal']['caminho_arquivo']) ?>"`
- ✅ Função JavaScript `changeImage()` ajustada para usar URL completa (já normalizada pelo PHP)

### 3. Validação de Caminhos

**Método `isValid()` no `MediaUrlHelper`:**
- Verifica se a URL não está vazia
- Verifica se começa com `/uploads/tenants/`
- Pode ser usado para validação antes de renderizar imagens

---

## 📋 Arquivos Modificados

1. **`src/Support/MediaUrlHelper.php`** (NOVO)
   - Helper centralizado para URLs de mídia

2. **`themes/default/storefront/home.php`**
   - Refatorado para usar `media_url()` em todas as imagens

3. **`themes/default/storefront/products/index.php`**
   - Refatorado para usar `media_url()` nos cards de produtos

4. **`themes/default/storefront/products/show.php`**
   - Refatorado para usar `media_url()` na imagem principal, thumbnails e produtos relacionados
   - Ajustada função JavaScript `changeImage()` para usar URL completa

---

## 🧪 Como Testar

### Checklist de Testes

#### 1. Home (`/`)
- [ ] **Hero banners:** Primeiro banner aparece imediatamente, carrossel rotaciona a cada 5s
- [ ] **Categorias (bolotas):** Imagens aparecem nos círculos brancos
- [ ] **Produtos em destaque:** Fotos aparecem nos cards
- [ ] **Logo:** Logo da loja aparece no header
- [ ] **Banners portrait:** Imagens aparecem como background

#### 2. Listagem de Produtos (`/produtos`)
- [ ] **Cards de produtos:** Fotos aparecem em todos os cards
- [ ] **Placeholder:** Se produto não tem imagem, placeholder aparece corretamente

#### 3. Página de Produto (`/produto/{slug}`)
- [ ] **Imagem principal:** Foto principal aparece
- [ ] **Thumbnails:** Miniaturas aparecem e funcionam ao clicar
- [ ] **Troca de imagem:** Ao clicar em thumbnail, imagem principal muda
- [ ] **Produtos relacionados:** Fotos aparecem nos cards relacionados

#### 4. Console do Navegador (F12)
- [ ] **Sem erros 404:** Nenhuma imagem retorna 404
- [ ] **URLs corretas:** Todas as URLs começam com `/uploads/tenants/...` (ou `/ecommerce-v1.0/public/uploads/...` em dev)
- [ ] **Sem dupla barra:** Nenhuma URL com `//uploads`

#### 5. Acesso Direto às Imagens
- [ ] **URL direta:** Acessar `https://pontodogolfeoutlet.com.br/uploads/tenants/1/banners/golfe04.webp` abre a imagem
- [ ] **Permissões:** Arquivos são acessíveis publicamente

---

## 🔄 Padrão de Uso Futuro

### Para Novas Views

**Sempre usar o helper `media_url()`:**

```php
<?php
use App\Support\MediaUrlHelper;

// Função helper (já definida nas views principais)
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}
?>

<!-- Uso em HTML -->
<img src="<?= media_url($caminhoDoBanco) ?>" alt="...">
```

### Validação (Opcional)

```php
<?php if (MediaUrlHelper::isValid($caminho)): ?>
    <img src="<?= media_url($caminho) ?>" alt="...">
<?php else: ?>
    <div class="placeholder">Sem imagem</div>
<?php endif; ?>
```

---

## 📝 Observações Importantes

1. **Compatibilidade:**
   - ✅ Funciona em dev (`/ecommerce-v1.0/public/...`)
   - ✅ Funciona em produção (Hostinger, domínio raiz)
   - ✅ Detecta ambiente automaticamente

2. **Formato de Caminhos no Banco:**
   - Os caminhos no banco devem começar com `/uploads/tenants/...` (com `/` inicial)
   - O helper normaliza automaticamente se o caminho não começar com `/`

3. **Biblioteca de Mídia:**
   - A Biblioteca de Mídia (`MediaLibraryService`) já gera URLs no formato correto
   - O helper `media_url()` é compatível com essas URLs

4. **JavaScript:**
   - A função `changeImage()` no PDP foi ajustada para usar URL completa
   - Não precisa mais concatenar `basePath` no JavaScript

---

## 🔗 Relacionado

- **`docs/RELATORIO_HERO_PONTODOGOLFE.md`:** Correções do hero slider (CSS/JS fallbacks)
- **`docs/DEPLOY_HOSTINGER_PONTODOGOLFE.md`:** Guia de deploy na Hostinger
- **`src/Services/MediaLibraryService.php`:** Serviço que lista imagens para a Biblioteca de Mídia

---

---

## 🔧 Correções Adicionais - Painel Admin (2025-12-09)

### Problema Identificado

Após a correção inicial do storefront, foram identificados problemas no painel admin:

1. **Listagem de Categorias em Destaque:** Coluna "Ícone" mostrava imagem quebrada
2. **Listagem de Banners da Home:** Cards apareciam com "Sem imagem"
3. **Botão "Escolher da biblioteca":** Não abria o modal em algumas telas

### Correções Aplicadas

#### 1. Listagem de Categorias em Destaque

**Arquivo:** `themes/default/admin/home/categories-pills-content.php`

**Mudanças:**
- ✅ Adicionado helper `media_url()` no início do arquivo
- ✅ Corrigida renderização da coluna "Ícone" para usar `media_url($pill['icone_path'])`
- ✅ Adicionado tratamento de erro (`onerror`) para fallback visual

**Antes:**
```php
<img src="<?= $basePath ?>/<?= htmlspecialchars($pill['icone_path']) ?>" 
     alt="Ícone" class="icon-preview">
```

**Depois:**
```php
<img src="<?= media_url($pill['icone_path']) ?>" 
     alt="Ícone" class="icon-preview"
     onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
```

#### 2. Listagem de Banners da Home

**Arquivo:** `themes/default/admin/home/banners-content.php`

**Mudanças:**
- ✅ Adicionado helper `media_url()` no início do arquivo
- ✅ Corrigida lógica de prioridade: `imagem_desktop` > `imagem_mobile`
- ✅ Corrigida renderização das miniaturas para usar `media_url()`

**Antes:**
```php
<?php if (!empty($banner['imagem_desktop'])): ?>
    <img src="<?= $basePath ?>/<?= htmlspecialchars($banner['imagem_desktop']) ?>" ...>
<?php elseif (!empty($banner['imagem_mobile'])): ?>
    <img src="<?= $basePath ?>/<?= htmlspecialchars($banner['imagem_mobile']) ?>" ...>
<?php endif; ?>
```

**Depois:**
```php
<?php 
$imagemBanner = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
if (!empty($imagemBanner)): 
?>
    <img src="<?= media_url($imagemBanner) ?>" ...>
<?php endif; ?>
```

#### 3. Formulário de Edição de Categorias

**Arquivo:** `themes/default/admin/home/categories-pills-edit-content.php`

**Mudanças:**
- ✅ Adicionado helper `media_url()` no início do arquivo
- ✅ Corrigida pré-visualização "Imagem Atual" para usar `media_url()`
- ✅ Adicionado atributo `data-folder="category-pills"` no botão "Escolher da biblioteca"

#### 4. Caminho do JS do Modal de Mídia

**Arquivo:** `themes/default/admin/layouts/store.php`

**Mudanças:**
- ✅ Corrigido caminho do `media-picker.js` para funcionar em produção (quando `$basePath` é vazio)

**Antes:**
```php
$mediaPickerPath = $basePath ? $basePath . '/admin/js/media-picker.js' : '/ecommerce-v1.0/public/admin/js/media-picker.js';
```

**Depois:**
```php
if (empty($basePath)) {
    $mediaPickerPath = '/admin/js/media-picker.js';
} else {
    $mediaPickerPath = $basePath . '/admin/js/media-picker.js';
}
```

### Arquivos Modificados

1. **`themes/default/admin/home/categories-pills-content.php`**
   - Helper `media_url()` adicionado
   - Coluna "Ícone" corrigida

2. **`themes/default/admin/home/categories-pills-edit-content.php`**
   - Helper `media_url()` adicionado
   - Pré-visualização "Imagem Atual" corrigida
   - Atributo `data-folder` adicionado ao botão

3. **`themes/default/admin/home/banners-content.php`**
   - Helper `media_url()` adicionado
   - Lógica de miniaturas corrigida

4. **`themes/default/admin/layouts/store.php`**
   - Caminho do `media-picker.js` corrigido para produção

### Padrão de Uso do Botão "Escolher da biblioteca"

Para que o botão "Escolher da biblioteca" funcione corretamente, ele deve ter:

```html
<button type="button"
        class="js-open-media-library admin-btn admin-btn-primary"
        data-media-target="#campo_input_id"
        data-folder="nome_da_pasta">
    <i class="bi bi-image icon"></i> Escolher da biblioteca
</button>
```

**Atributos obrigatórios:**
- `class="js-open-media-library"` - Classe que o JS escuta
- `data-media-target="#campo_input_id"` - ID do input que será preenchido
- `data-folder="nome_da_pasta"` - Pasta para filtrar imagens (ex: `"banners"`, `"category-pills"`, `"produtos"`)

**O JS `media-picker.js` já está incluído no layout admin (`themes/default/admin/layouts/store.php`), então está disponível em todas as telas.**

---

**Status:** ✅ Implementação Concluída  
**Última atualização:** 2025-12-09

---

## Integração da Biblioteca de Mídia com Produtos (Imagem de Destaque + Galeria)

**Data:** 2025-01-10  
**Status:** ✅ Implementado

### Objetivo

Integrar a Biblioteca de Mídia nos formulários de criação e edição de produtos, permitindo escolher imagens da biblioteca ao invés de fazer upload direto via janela do sistema operacional.

### Funcionalidades Implementadas

#### 1. Imagem de Destaque do Produto

**Localização:**
- `themes/default/admin/products/create-content.php`
- `themes/default/admin/products/edit-content.php`

**Implementação:**
- Campo de texto readonly (`imagem_destaque_path`) que recebe o caminho da imagem selecionada
- Botão "Escolher da biblioteca" com atributos:
  - `class="js-open-media-library"`
  - `data-media-target="#imagem_destaque_path"`
  - `data-folder="produtos"`
  - `data-preview="#imagem_destaque_preview"`
- Preview da imagem selecionada
- Mantém compatibilidade com upload direto (campo `imagem_destaque` ainda disponível)

**Código HTML:**
```html
<input type="text" 
       name="imagem_destaque_path" 
       id="imagem_destaque_path" 
       placeholder="Selecione uma imagem na biblioteca"
       readonly>
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#imagem_destaque_path"
        data-folder="produtos"
        data-preview="#imagem_destaque_preview">
    <i class="bi bi-image icon"></i> Escolher da biblioteca
</button>
<div id="imagem_destaque_preview"></div>
```

#### 2. Galeria de Imagens do Produto

**Localização:**
- `themes/default/admin/products/create-content.php`
- `themes/default/admin/products/edit-content.php`

**Implementação:**
- Botão "Adicionar da biblioteca" com modo múltiplo:
  - `data-multiple="true"`
  - `data-folder="produtos"`
- Container para inputs hidden (`galeria_paths_container`)
- Container para previews das imagens selecionadas (`galeria_preview_container`)
- JavaScript que processa evento `media-picker:multiple-selected`
- Função `removeGalleryPreview()` para remover imagens antes de salvar

**Código HTML:**
```html
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#galeria_paths_container"
        data-folder="produtos"
        data-multiple="true">
    <i class="bi bi-image icon"></i> Adicionar da biblioteca
</button>
<div id="galeria_paths_container" style="display: none;"></div>
<div id="galeria_preview_container" style="display: grid; ..."></div>
```

**JavaScript:**
```javascript
container.addEventListener('media-picker:multiple-selected', function(event) {
    var urls = event.detail.urls;
    // Criar inputs hidden para cada URL
    // Adicionar previews visuais
});
```

#### 3. Backend - Processamento de Caminhos

**Localização:** `src/Http/Controllers/Admin/ProductController.php`

**Métodos adaptados:**

##### `processMainImage()`
- Aceita `$_POST['imagem_destaque_path']` (caminho da biblioteca)
- Prioridade: caminho da biblioteca > upload direto
- Valida que o caminho pertence ao tenant
- Verifica existência física do arquivo
- Cria registro em `produto_imagens` com tipo `main`
- Atualiza `produtos.imagem_principal`

**Código:**
```php
// Verificar se veio caminho de imagem da biblioteca (prioridade sobre upload)
if (!empty($_POST['imagem_destaque_path']) && is_string($_POST['imagem_destaque_path'])) {
    $imagePath = trim($_POST['imagem_destaque_path']);
    
    // Validar que o caminho é válido e pertence ao tenant
    if (strpos($imagePath, "/uploads/tenants/{$tenantId}/") === 0) {
        // Processar caminho da biblioteca...
    }
}
// Verificar se veio arquivo novo (upload direto)
elseif (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK) {
    // Processar upload...
}
```

##### `processGallery()`
- Aceita `$_POST['galeria_paths']` (array de caminhos)
- Prioridade: caminhos da biblioteca > upload direto
- Valida cada caminho
- Verifica duplicatas (não adiciona se imagem já está na galeria)
- Cria registros em `produto_imagens` com tipo `gallery`

**Código:**
```php
// Processar caminhos de imagens da biblioteca (prioridade sobre upload)
if (!empty($_POST['galeria_paths']) && is_array($_POST['galeria_paths'])) {
    foreach ($_POST['galeria_paths'] as $imagePath) {
        // Validar e processar cada caminho...
    }
}
// Processar upload de novas imagens (se não veio da biblioteca)
if (!empty($_FILES['galeria']['name'][0])) {
    // Processar upload...
}
```

#### 4. Media Picker - Modo Múltiplo

**Localização:** `public/admin/js/media-picker.js`

**Funcionalidades adicionadas:**
- Suporte a `data-multiple="true"` no botão
- Variável global `isMultipleMode`
- Array `selectedImageUrls` para armazenar múltiplas seleções
- Toggle de seleção (clique marca/desmarca)
- Botão "Adicionar X imagem(ns)" dinâmico
- Função `selectMultipleImages()` que dispara evento customizado
- Evento `media-picker:multiple-selected` com `detail.urls` (array)

**Código:**
```javascript
// Modo múltiplo: toggle seleção
if (isMultipleMode) {
    var index = selectedImageUrls.indexOf(url);
    if (index > -1) {
        // Desmarcar
        selectedImageUrls.splice(index, 1);
    } else {
        // Marcar
        selectedImageUrls.push(url);
    }
}
```

### Fluxo de Uso

#### Imagem de Destaque

1. Usuário acessa `/admin/produtos/novo` ou `/admin/produtos/{id}`
2. Na seção "Imagem de Destaque":
   - Vê preview atual (se houver) ou placeholder
   - Clica em "Escolher da biblioteca"
3. Modal abre filtrado em `produtos`
4. Usuário pode:
   - Selecionar imagem existente
   - Fazer upload dentro do modal e depois selecionar
5. Ao clicar em "Usar imagem selecionada":
   - Modal fecha
   - Campo `imagem_destaque_path` é preenchido
   - Preview atualiza
6. Ao salvar:
   - Backend processa `imagem_destaque_path` primeiro
   - Se não houver, processa `imagem_destaque` (upload)

#### Galeria

1. Na seção "Galeria de Imagens":
   - Vê miniaturas já ligadas ao produto (se houver)
   - Clica em "Adicionar da biblioteca"
2. Modal abre filtrado em `produtos` em modo múltiplo
3. Usuário seleciona uma ou mais imagens (toggle com clique)
4. Ao clicar em "Adicionar X imagem(ns)":
   - Modal fecha
   - Inputs hidden `galeria_paths[]` são criados
   - Previews aparecem na galeria
5. Usuário pode remover previews antes de salvar
6. Ao salvar:
   - Backend processa `galeria_paths[]` primeiro
   - Se não houver, processa `galeria[]` (upload)

### Compatibilidade

**Mantida:**
- ✅ Upload direto ainda funciona (campos `imagem_destaque` e `galeria[]`)
- ✅ Produtos existentes não são afetados
- ✅ Multi-tenant respeitado (validação de caminhos)
- ✅ Outros usos do media picker não são afetados

**Novo:**
- ✅ Seleção múltipla no media picker (modo opcional via `data-multiple`)
- ✅ Processamento de caminhos de imagens no backend
- ✅ Preview dinâmico de imagens selecionadas

### Arquivos Modificados

1. **`public/admin/js/media-picker.js`**
   - Adicionado suporte a seleção múltipla
   - Variáveis globais: `isMultipleMode`, `selectedImageUrls`
   - Função `selectMultipleImages()`
   - Evento customizado `media-picker:multiple-selected`

2. **`src/Http/Controllers/Admin/ProductController.php`**
   - Método `processMainImage()` adaptado para aceitar `imagem_destaque_path`
   - Método `processGallery()` adaptado para aceitar `galeria_paths[]`

3. **`themes/default/admin/products/create-content.php`**
   - Campo de imagem de destaque com media picker
   - Galeria com media picker em modo múltiplo
   - JavaScript para processar seleção múltipla

4. **`themes/default/admin/products/edit-content.php`**
   - Campo de imagem de destaque com media picker
   - Galeria com media picker em modo múltiplo
   - JavaScript para processar seleção múltipla
   - Helper `media_url()` para URLs corretas

### Padrão de Uso

#### Modo Único (Imagem de Destaque)

```html
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#campo_input"
        data-folder="produtos"
        data-preview="#preview_container">
    Escolher da biblioteca
</button>
<input type="text" id="campo_input" name="imagem_destaque_path" readonly>
<div id="preview_container"></div>
```

#### Modo Múltiplo (Galeria)

```html
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#container_paths"
        data-folder="produtos"
        data-multiple="true">
    Adicionar da biblioteca
</button>
<div id="container_paths" style="display: none;"></div>
<div id="preview_container"></div>

<script>
container.addEventListener('media-picker:multiple-selected', function(event) {
    var urls = event.detail.urls;
    // Processar URLs...
});
</script>
```

### Validação

**Backend:**
- Caminhos devem começar com `/uploads/tenants/{tenant_id}/`
- Arquivo físico deve existir
- Validação de tenant (segurança multi-tenant)

**Frontend:**
- Preview automático ao selecionar
- Remoção de previews antes de salvar
- Prevenção de duplicatas na galeria

---

**Status:** ✅ Implementação Concluída  
**Última atualização:** 2025-01-10

