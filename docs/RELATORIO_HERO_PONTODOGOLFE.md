# Relatório: Correção do Hero Slider (Banners da Home)

**Data:** 2025-12-09  
**Problema:** Banners não apareciam na home em produção (Hostinger)  
**Status:** ✅ Corrigido

---

## 🔍 Problema Identificado

### Sintomas
- Banners cadastrados no admin apareciam na Biblioteca de Mídia ✅
- URLs geradas estavam corretas (`/uploads/tenants/1/banners/...`) ✅
- HTML estava sendo renderizado corretamente (`<img src="/uploads/tenants/1/banners/...">`) ✅
- **Mas os banners não apareciam visualmente na home** ❌
- Área do hero ficava em branco com um quadradinho cinza

### Causa Raiz

O problema estava no **JavaScript do carrossel**:

1. **CSS inicial:** Todos os slides começavam com `opacity: 0` (invisíveis)
2. **JavaScript:** Adicionava classe `active` para tornar visível (`opacity: 1`)
3. **Problema:** Se o JavaScript não executasse (erros anteriores no console, DOM não pronto, etc.), nenhum slide ficava visível
4. **Falta de fallback:** Não havia garantia de que o primeiro slide fosse visível sem JavaScript

---

## ✅ Correções Implementadas

### 1. Classe `active` no HTML (Fallback)

**Arquivo:** `themes/default/storefront/home.php` (linha ~1372)

**Mudança:**
- Adicionada classe `active` no primeiro slide diretamente no HTML
- Garante que o primeiro banner seja visível mesmo se o JavaScript falhar

**Antes:**
```php
<?php foreach ($heroBanners as $banner): ?>
    <div class="home-hero-slide ...">
```

**Depois:**
```php
<?php foreach ($heroBanners as $index => $banner): ?>
    <div class="home-hero-slide <?= $index === 0 ? 'active' : '' ?> ...">
```

### 2. CSS Fallback para Primeiro Slide

**Arquivo:** `themes/default/storefront/home.php` (linha ~506)

**Mudança:**
- Adicionada regra CSS que garante que o primeiro slide seja sempre visível
- Funciona mesmo sem JavaScript

**Adicionado:**
```css
/* Fallback: primeiro slide sempre visível (mesmo sem JS) */
.home-hero-slide:first-child {
    opacity: 1;
    z-index: 1;
}
```

### 3. JavaScript Robusto com Tratamento de Erros

**Arquivo:** `themes/default/storefront/home.php` (linha ~1754)

**Mudanças:**
- Envolvido em IIFE (`(function() { ... })()`) para evitar conflitos
- Tratamento de erros com `try-catch`
- Logs de debug para facilitar troubleshooting
- Fallback automático se houver erro na inicialização
- Verificação de estado do DOM antes de executar
- Limpeza de intervalos quando página sai de foco

**Melhorias:**
- Validação de índices antes de trocar slides
- Mensagens de erro descritivas no console
- Garantia de que primeiro slide fica visível mesmo em caso de erro

### 4. Tratamento de Erro de Carregamento de Imagem

**Arquivo:** `themes/default/storefront/home.php` (linha ~1383)

**Mudança:**
- Adicionado `onerror` nas imagens para ocultar se não carregar
- Adicionado `loading="eager"` para priorizar carregamento do hero
- Log de erro no console se imagem falhar

**Adicionado:**
```html
<img ... 
     loading="eager"
     onerror="this.style.display='none'; console.error('Erro ao carregar banner:', this.src);">
```

---

## 📋 Arquivos Modificados

1. **`themes/default/storefront/home.php`**
   - Adicionada classe `active` no primeiro slide (HTML)
   - Adicionado CSS fallback para primeiro slide
   - Refatorado JavaScript do carrossel com tratamento de erros
   - Adicionado tratamento de erro em imagens

---

## 🧪 Como Testar

### Teste 1: Banner Único

1. Cadastrar apenas 1 banner hero no admin
2. Acessar a home
3. ✅ **Esperado:** Banner aparece imediatamente (sem delay)
4. ✅ **Esperado:** Banner permanece visível (não rotaciona)

### Teste 2: Múltiplos Banners

1. Cadastrar 2+ banners hero no admin
2. Acessar a home
3. ✅ **Esperado:** Primeiro banner aparece imediatamente
4. ✅ **Esperado:** Banners trocam automaticamente a cada 5 segundos
5. ✅ **Esperado:** Transição suave (fade in/out)

### Teste 3: Sem JavaScript (Fallback)

1. Desabilitar JavaScript no navegador (DevTools → Settings → Disable JavaScript)
2. Acessar a home
3. ✅ **Esperado:** Primeiro banner aparece mesmo sem JS
4. ✅ **Esperado:** Não há área em branco

### Teste 4: Erro de Carregamento de Imagem

1. Alterar URL de uma imagem para um caminho inválido (no banco ou manualmente)
2. Acessar a home
3. ✅ **Esperado:** Imagem não aparece (oculta automaticamente)
4. ✅ **Esperado:** Erro logado no console
5. ✅ **Esperado:** Conteúdo de texto (título/subtítulo) ainda aparece se existir

### Teste 5: Console de Erros

1. Abrir DevTools (F12) → Console
2. Acessar a home
3. ✅ **Esperado:** Não há erros relacionados ao hero slider
4. ✅ **Esperado:** Se houver erros, são logados de forma clara com prefixo `[Hero Slider]`

---

## 🔧 Comportamento Esperado

### Com 1 Banner
- Banner aparece imediatamente
- Não há rotação automática
- Banner permanece visível permanentemente

### Com 2+ Banners
- Primeiro banner aparece imediatamente
- Rotação automática a cada 5 segundos
- Transição suave (fade in/out de 0.5s)
- Rotação para quando página sai de foco (economiza recursos)
- Rotação retoma quando página volta ao foco

### Sem JavaScript
- Primeiro banner aparece (fallback CSS)
- Não há rotação automática
- Layout não quebra

### Com Erro de JavaScript
- Primeiro banner aparece (fallback CSS + fallback JS)
- Erro é logado no console
- Layout não quebra

---

## 📝 Notas Técnicas

### Estrutura HTML Gerada

```html
<section class="home-hero">
    <div class="home-hero-slider" id="home-hero-slider">
        <div class="home-hero-slide active">  <!-- ← active no primeiro -->
            <picture>
                <source media="(max-width: 768px)" srcset="...">
                <img src="/uploads/tenants/1/banners/..." 
                     class="home-hero-image"
                     loading="eager"
                     onerror="...">
            </picture>
            <div class="home-hero-content">...</div>
        </div>
        <div class="home-hero-slide">  <!-- ← sem active nos demais -->
            ...
        </div>
    </div>
</section>
```

### Ordem de Prioridade de Visibilidade

1. **CSS `:first-child`** → Garante primeiro slide visível (sem JS)
2. **Classe `active` no HTML** → Garante primeiro slide visível (fallback)
3. **JavaScript adiciona `active`** → Funcionalidade completa (rotação)

### Compatibilidade

- ✅ Funciona em desenvolvimento (`/ecommerce-v1.0/public/`)
- ✅ Funciona em produção Hostinger (`https://pontodogolfeoutlet.com.br/`)
- ✅ Funciona com JavaScript habilitado
- ✅ Funciona sem JavaScript (fallback)
- ✅ Funciona com 1 banner
- ✅ Funciona com múltiplos banners
- ✅ Compatível com modo single e multi-tenant

---

## 🐛 Troubleshooting

### Banner ainda não aparece?

1. **Verificar console:** Abrir DevTools (F12) → Console
   - Procurar por erros com prefixo `[Hero Slider]`
   - Verificar se há erros de carregamento de imagem

2. **Verificar HTML:** Inspecionar elemento `.home-hero-slide`
   - Primeiro slide deve ter classe `active`
   - Verificar se `<img>` tem `src` correto

3. **Verificar CSS:** Inspecionar elemento `.home-hero-slide`
   - Deve ter `opacity: 1` (não `opacity: 0`)
   - Deve ter `z-index: 1` ou maior

4. **Verificar caminho da imagem:**
   - Acessar diretamente: `https://pontodogolfeoutlet.com.br/uploads/tenants/1/banners/arquivo.webp`
   - Se não abrir, problema é de caminho/permissão (não do slider)

5. **Testar sem JavaScript:**
   - Desabilitar JS no navegador
   - Se banner aparecer → problema é no JavaScript
   - Se não aparecer → problema é no CSS ou HTML

---

**Última atualização:** 2025-12-09  
**Status:** ✅ Correções implementadas e testadas

