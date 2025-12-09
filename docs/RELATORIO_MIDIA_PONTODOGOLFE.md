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

