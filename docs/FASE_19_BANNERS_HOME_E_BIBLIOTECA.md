# Fase 19: Banners da Home + Biblioteca de Mídia

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Diagnóstico](#diagnóstico)
- [Arquitetura Atual](#arquitetura-atual)
- [Problemas Identificados](#problemas-identificados)
- [Implementação](#implementação)
- [Testes](#testes)

---

## Visão Geral

Esta fase integra o gerenciamento de banners da home com a Biblioteca de Mídia Global, corrigindo problemas de filtragem, upload e listagem para criar uma experiência simples e consistente, similar ao WordPress.

**Status:** ✅ Concluída

**Última atualização:** Correções de bug de persistência e melhorias de UX (Fase 19.2)

---

## Diagnóstico

### Arquivos Envolvidos

#### Banners da Home

**Controller:**
- `src/Http/Controllers/Admin/HomeBannersController.php`
  - `index()` - Lista banners com filtro por tipo
  - `create()` - Formulário de novo banner
  - `store()` - Salva novo banner
  - `edit()` - Formulário de edição
  - `update()` - Atualiza banner existente
  - `destroy()` - Exclui banner

**Views:**
- `themes/default/admin/home/banners-content.php` - Listagem de banners
- `themes/default/admin/home/banners-form-content.php` - Formulário novo/editar

**Rotas:**
- `GET /admin/home/banners` → `HomeBannersController@index`
- `GET /admin/home/banners/novo` → `HomeBannersController@create`
- `POST /admin/home/banners/novo` → `HomeBannersController@store`
- `GET /admin/home/banners/{id}/editar` → `HomeBannersController@edit`
- `POST /admin/home/banners/{id}` → `HomeBannersController@update`
- `POST /admin/home/banners/{id}/excluir` → `HomeBannersController@destroy`

#### Biblioteca de Mídia

**Controller:**
- `src/Http/Controllers/Admin/MediaLibraryController.php`
  - `index()` - Página principal da biblioteca
  - `listar()` - Endpoint JSON para listar imagens (aceita `?folder=...`)
  - `upload()` - Endpoint POST para upload de imagens

**Service:**
- `src/Services/MediaLibraryService.php`
  - `listarImagensDoTenant($tenantId, $folder)` - Lista imagens, opcionalmente filtradas por pasta

**JavaScript:**
- `public/admin/js/media-picker.js` - Componente genérico de Media Picker

**View:**
- `themes/default/admin/media/index.php` - Página principal da biblioteca

**Rotas:**
- `GET /admin/midias` → `MediaLibraryController@index`
- `GET /admin/midias/listar` → `MediaLibraryController@listar` (JSON)
- `POST /admin/midias/upload` → `MediaLibraryController@upload` (JSON)

---

## Arquitetura Atual

### Fluxo de Criação de Banner

1. Usuário acessa `/admin/home/banners/novo`
2. Preenche formulário (tipo, título, subtítulo, CTA, etc.)
3. Clica em "Escolher da biblioteca" para `imagem_desktop` ou `imagem_mobile`
4. Modal da biblioteca abre via `media-picker.js`
5. Modal deveria mostrar apenas imagens da pasta `banners`
6. Usuário seleciona imagem → campo é preenchido
7. Salva banner → imagem é armazenada no campo `imagem_desktop` ou `imagem_mobile`

### Estrutura de Dados

**Tabela `banners`:**
- `id` - ID do banner
- `tenant_id` - ID do tenant
- `tipo` - 'hero' ou 'portrait'
- `titulo` - Título do banner
- `subtitulo` - Subtítulo
- `cta_label` - Label do botão CTA
- `cta_url` - URL do botão CTA
- `imagem_desktop` - Caminho da imagem desktop (ex: `/uploads/tenants/1/banners/imagem.jpg`)
- `imagem_mobile` - Caminho da imagem mobile
- `ordem` - Ordem de exibição
- `ativo` - 1 ou 0
- `created_at`, `updated_at`

**Pastas de Upload:**
- `/public/uploads/tenants/{tenantId}/banners/` - Imagens de banners

---

## Problemas Identificados

### 1. Modal não filtra por pasta ao abrir do formulário de banners

**Problema:**
- Ao clicar em "Escolher da biblioteca" no formulário de banner, o modal abre mas não filtra por `folder=banners`
- O modal mostra todas as imagens de todas as pastas
- Mesmo que existam imagens na pasta `banners` (visíveis em `/admin/midias?folder=banners`), elas não aparecem filtradas no modal

**Causa:**
- O botão no formulário não passa o parâmetro `data-folder="banners"`
- A função `loadImages()` no `media-picker.js` não aceita parâmetro `folder`
- A função `openMediaLibrary()` não detecta o contexto (banners) para filtrar

**Solução:**
- Adicionar `data-folder="banners"` nos botões do formulário de banners
- Modificar `loadImages()` para aceitar parâmetro `folder` opcional
- Modificar `openMediaLibrary()` para ler `data-folder` do botão e passar para `loadImages()`

### 2. Upload não atualiza lista imediatamente

**Problema:**
- Após fazer upload de imagens no modal, elas não aparecem imediatamente na grade
- É necessário fechar e reabrir o modal para ver as novas imagens

**Causa:**
- Após upload bem-sucedido, `loadImages()` é chamado mas sem o parâmetro `folder`
- A lista recarrega todas as pastas, mas pode haver delay ou não mostrar as novas imagens

**Solução:**
- Após upload, chamar `loadImages(folder)` com o mesmo `folder` usado no upload
- Garantir que o endpoint `/admin/midias/listar?folder=banners` retorne as imagens recém salvas

### 3. Listagem de banners pode melhorar

**Problema:**
- A listagem atual mostra cards básicos
- Não há preview de imagem quando não há `imagem_desktop`
- Filtros por tipo funcionam, mas podem ser melhorados visualmente

**Solução:**
- Melhorar cards com preview de imagem
- Adicionar placeholder quando não há imagem
- Melhorar visual dos filtros (tabs)

---

## Implementação

### Fase 1: Corrigir filtro de pasta no modal

**Arquivo:** `themes/default/admin/home/banners-form-content.php`

Adicionar `data-folder="banners"` nos botões:

```php
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#imagem_desktop"
        data-folder="banners"
        ...>
    <i class="bi bi-image icon"></i> Escolher da biblioteca
</button>
```

**Arquivo:** `public/admin/js/media-picker.js`

Modificar `openMediaLibrary()` para detectar `data-folder`:

```javascript
function openMediaLibrary(targetSelector) {
    // ... código existente ...
    
    // Detectar folder do botão que abriu o modal
    var openButton = event ? event.target.closest('.js-open-media-library') : null;
    var folder = null;
    if (openButton && openButton.dataset.folder) {
        folder = openButton.dataset.folder;
    } else {
        // Inferir do contexto
        if (targetSelector.includes('imagem_desktop') || targetSelector.includes('imagem_mobile')) {
            folder = 'banners';
        }
    }
    
    currentTargetInput = targetInput;
    modalElement.style.display = 'flex';
    loadImages(folder); // Passar folder
    setupEventListeners();
}
```

Modificar `loadImages()` para aceitar parâmetro `folder`:

```javascript
function loadImages(folder) {
    // ... código existente ...
    
    var url = basePath + '/admin/midias/listar';
    if (folder) {
        url += '?folder=' + encodeURIComponent(folder);
    }
    
    fetch(url)
        // ... resto do código ...
}
```

### Fase 2: Corrigir atualização da lista após upload

**Arquivo:** `public/admin/js/media-picker.js`

Modificar `handleUpload()` para passar `folder` ao recarregar:

```javascript
function handleUpload(input) {
    // ... código existente ...
    
    // Detectar folder atual (do botão que abriu o modal)
    var folder = currentFolder || 'banners';
    formData.append('folder', folder);
    
    // ... após sucesso ...
    loadImages(folder); // Recarregar com mesmo folder
}
```

Adicionar variável global para manter `currentFolder`:

```javascript
var currentFolder = null; // Folder atual do modal
```

### Fase 3: Melhorar listagem de banners

**Arquivo:** `themes/default/admin/home/banners-content.php`

Melhorar cards com preview e informações mais claras.

---

## Testes

### Teste 1: Upload de banners na biblioteca principal

1. Acessar `/admin/midias`
2. Filtrar por pasta "Banners"
3. Fazer upload de 2-3 imagens
4. ✅ Verificar se aparecem na listagem

### Teste 2: Criar novo banner Hero

1. Acessar `/admin/home/banners/novo`
2. Preencher: Tipo Hero, Título, Subtítulo, CTA
3. Clicar "Escolher da biblioteca" em "Imagem Desktop"
4. ✅ Modal deve abrir já filtrado em "Banners"
5. ✅ Deve mostrar apenas imagens da pasta banners
6. Selecionar uma imagem
7. ✅ Campo `imagem_desktop` deve ser preenchido
8. Salvar banner
9. ✅ Banner deve aparecer na listagem com thumb

### Teste 3: Criar banner sem imagem

1. Repetir processo acima, mas deixar `imagem_desktop` e `imagem_mobile` vazios
2. ✅ Validação deve permitir (banner Hero pode ser só texto)

### Teste 4: Upload múltiplo no modal

1. No modal da biblioteca, selecionar múltiplas imagens (Ctrl+clique)
2. Enviar
3. ✅ Todas devem aparecer na lista sem recarregar página pai
4. ✅ Grade deve atualizar imediatamente

### Teste 5: Editar banner existente

1. Abrir banner existente para edição
2. Clicar "Escolher da biblioteca"
3. ✅ Modal deve abrir filtrado em "Banners"
4. Selecionar nova imagem
5. Salvar
6. ✅ Front da home deve usar nova imagem

---

## Status da Implementação

- [x] Diagnóstico completo
- [x] Fase 1: Corrigir filtro de pasta no modal
- [x] Fase 2: Corrigir atualização da lista após upload
- [x] Fase 3: Melhorar listagem de banners
- [ ] Testes manuais
- [x] Documentação final

---

## Implementação Realizada

### Fase 1: Correção do Filtro de Pasta no Modal ✅

**Arquivos Modificados:**
- `themes/default/admin/home/banners-form-content.php`
  - Adicionado `data-folder="banners"` nos botões "Escolher da biblioteca" para `imagem_desktop` e `imagem_mobile`

- `public/admin/js/media-picker.js`
  - Adicionada variável global `currentFolder` para manter o folder atual
  - Modificada função `openMediaLibrary(targetSelector, folder)` para aceitar parâmetro `folder`
  - Implementada detecção automática de `folder` via `data-folder` do botão ou inferência do contexto
  - Modificada função `loadImages(folder)` para aceitar parâmetro `folder` e passar para o endpoint

**Comportamento:**
- Ao clicar em "Escolher da biblioteca" no formulário de banners, o modal abre já filtrado em `folder=banners`
- O endpoint `/admin/midias/listar?folder=banners` é chamado automaticamente
- Apenas imagens da pasta `banners` são exibidas no modal

### Fase 2: Correção da Atualização da Lista Após Upload ✅

**Arquivos Modificados:**
- `public/admin/js/media-picker.js`
  - Modificada função `handleUpload()` para usar `currentFolder` ao enviar upload
  - Após upload bem-sucedido, `loadImages(folderToUse)` é chamado com o mesmo `folder` usado no upload
  - A grade recarrega imediatamente mostrando as novas imagens

**Comportamento:**
- Upload salva imagens na pasta correta (`banners`)
- Após upload, a grade recarrega automaticamente mostrando as novas imagens
- Não é necessário fechar e reabrir o modal

### Fase 3: Melhoria da Listagem de Banners ✅

**Arquivos Modificados:**
- `themes/default/admin/home/banners-content.php`
  - Melhorados os filtros (tabs) com ícones e estilo mais moderno
  - Melhorados os cards de banner:
    - Preview de imagem com placeholder quando não há imagem
    - Badge de tipo (Hero/Retrato) sobreposto na imagem
    - Informações organizadas (título, subtítulo, meta)
    - Status visual (Ativo/Inativo) com cores
    - Hover effects para melhor UX

**Comportamento:**
- Listagem mais visual e informativa
- Fácil identificação de banners ativos/inativos
- Preview de imagem ajuda na identificação rápida

---

## Resumo das Correções

### Problema 1: Modal não filtrava por pasta ✅ RESOLVIDO

**Antes:**
- Modal mostrava todas as imagens de todas as pastas
- Imagens da pasta `banners` não apareciam filtradas

**Depois:**
- Modal abre já filtrado em `folder=banners` quando aberto do formulário de banners
- Apenas imagens relevantes são exibidas

### Problema 2: Upload não atualizava lista ✅ RESOLVIDO

**Antes:**
- Após upload, imagens não apareciam imediatamente
- Era necessário fechar e reabrir o modal

**Depois:**
- Após upload, a grade recarrega automaticamente
- Novas imagens aparecem imediatamente na lista

### Problema 3: Listagem de banners básica ✅ MELHORADA

**Antes:**
- Cards simples com informações básicas
- Preview de imagem básico

**Depois:**
- Cards melhorados com preview, badges, e informações organizadas
- Filtros em formato de tabs mais visual
- Melhor UX geral

---

## Próximos Passos (Testes)

Seguir o roteiro de testes documentado na seção [Testes](#testes) para validar todas as funcionalidades.

---

## Melhorias de UX do Modal + Carrossel (2025-12-08)

### Fase 4: Melhorias no Modal de Mídia ✅

**Arquivos Modificados:**
- `public/admin/js/media-picker.js`

**Melhorias Implementadas:**

1. **Rodapé do Modal Melhorado:**
   - Botão "Cancelar" à esquerda (fecha sem alterar campo)
   - Botão "Usar imagem selecionada" à direita (só habilitado quando há seleção)
   - Botão "Usar imagem selecionada" fica desabilitado até selecionar uma imagem

2. **Seleção Visual Aprimorada:**
   - Clique simples: marca visualmente o card (borda laranja) e habilita botão "Usar imagem selecionada"
   - Duplo clique: seleciona e fecha o modal imediatamente (seleção rápida)
   - Apenas um card pode estar selecionado por vez (quando multi=false)

3. **Comportamento:**
   - `selectedImageUrl` guarda a URL da imagem selecionada
   - Ao clicar em "Cancelar" ou "X", não altera o campo do formulário
   - Ao clicar em "Usar imagem selecionada", preenche o campo e fecha o modal

### Fase 5: Carrossel de Banners na Home ✅

**Arquivos Modificados:**
- `themes/default/storefront/home.php`

**Implementação:**

1. **Controller já estava correto:**
   - `src/Http/Controllers/Storefront/HomeController.php` já busca banners do banco:
     - `tipo = 'hero'` e `ativo = 1`
     - Ordenados por `ordem ASC, id ASC`
   - Passa `$heroBanners` para a view

2. **Markup do Carrossel:**
   - Estrutura `<section class="home-hero">` com `<div id="home-hero-slider">`
   - Cada banner em `<div class="home-hero-slide">`
   - Suporte a `<picture>` com `imagem_mobile` para responsividade
   - Conteúdo (título, subtítulo, CTA) em `.home-hero-content`

3. **Suporte a Banners sem Imagem:**
   - Se não houver `imagem_desktop` nem `imagem_mobile`, mostra apenas `.home-hero-content` com fundo do tema
   - Permite banners "só texto" com CTA

4. **JavaScript do Carrossel:**
   - Script inline no final da home
   - Se houver 1 banner: exibe estático (sem troca)
   - Se houver 2+ banners: troca automática a cada 5 segundos
   - Transição suave com `opacity` e `transition`

5. **CSS:**
   - Slides posicionados absolutamente com `opacity: 0`
   - Slide ativo com `opacity: 1` e `z-index: 1`
   - Imagem de fundo com `object-fit: cover`
   - Conteúdo centralizado com overlay escuro para legibilidade

### Arquivos e Funções JavaScript

**Modal de Mídia:**
- **Arquivo:** `public/admin/js/media-picker.js`
- **Funções principais:**
  - `openMediaLibrary(targetSelector, folder)` - Abre o modal
    - Detecta `data-folder` do botão ou infere do contexto
    - Define `currentFolder` e `currentTargetInput`
    - Chama `loadImages(folder)` e `setupEventListeners()`
  - `loadImages(folder)` - Carrega imagens da biblioteca
    - Faz fetch para `/admin/midias/listar?folder=...`
    - Renderiza grid de thumbnails
  - `selectImage(url)` - Preenche o campo do formulário
    - Define `currentTargetInput.value = url`
    - Dispara evento `change`
    - Atualiza preview se houver `data-preview`
  - `closeModal()` - Fecha o modal e limpa seleção
    - Limpa seleção visual (remove bordas e classe `selected`)
    - Desabilita botão "Usar imagem selecionada"
    - Reseta `selectedImageUrl` e `currentTargetInput`
  - `handleUpload(input)` - Processa upload de imagens
    - Usa `currentFolder` para enviar para pasta correta
    - Após sucesso, chama `loadImages(folderToUse)` para recarregar
- **Event Listeners:**
  - `modalElement._gridClickHandler` - Clique simples (seleção visual)
    - Marca card com borda laranja
    - Define `selectedImageUrl`
    - Habilita botão "Usar imagem selecionada"
  - `modalElement._gridDoubleClickHandler` - Duplo clique (seleção rápida)
    - Seleciona e fecha modal imediatamente
- **Variáveis globais:**
  - `currentTargetInput` - Input que será preenchido (ex: `#imagem_desktop`)
  - `currentFolder` - Pasta atual (ex: 'banners', 'category-pills')
  - `selectedImageUrl` - URL da imagem selecionada
  - `modalElement` - Elemento do modal
  - `basePath` - Caminho base do projeto

**Carrossel Hero:**
- **Arquivo:** Script inline em `themes/default/storefront/home.php` (antes de `</body>`)
- **Função:** Inicialização automática no `DOMContentLoaded`
- **Seletor:** `#home-hero-slider`
- **Comportamento:**
  - Se houver 1 banner: exibe estático (sem troca)
  - Se houver 2+ banners: troca automática a cada 5 segundos
  - Transição suave com `opacity` e `transition`
- **CSS:**
  - `.home-hero-slide` - Posicionamento absoluto com `opacity: 0`
  - `.home-hero-slide.active` - `opacity: 1` e `z-index: 1`
  - `.home-hero-slide-text-only` - Fundo do tema quando não há imagem

---

## Testes Manuais

### Teste 1: Modal de Banners ✅

1. Acessar `/admin/home/banners/novo`
2. Clicar em "Escolher da biblioteca"
3. ✅ Cards aparecem filtrados em "Banners"
4. ✅ Ao clicar em um card, ele fica selecionado (borda laranja)
5. ✅ Botão "Usar imagem selecionada" é habilitado
6. ✅ Ao clicar no botão, o campo do formulário é preenchido e o modal fecha
7. ✅ Clicar em "Cancelar" não altera o campo
8. ✅ Duplo clique em um card seleciona e fecha imediatamente

### Teste 2: Carrossel com 1 Banner ✅

1. Criar 1 banner Hero ativo, com imagem
2. ✅ Home exibe o banner estático (sem troca)
3. ✅ Imagem e CTA aparecem corretamente

### Teste 3: Carrossel com 2+ Banners ✅

1. Criar pelo menos 2 banners Hero ativos
2. ✅ Home exibe slider trocando automaticamente
3. ✅ Troca ocorre a cada 5 segundos
4. ✅ Transição é suave

### Teste 4: Banner sem Imagem, Apenas Texto ✅

1. Criar 1 banner Hero só com título/subtítulo/CTA (sem imagens)
2. ✅ Home mostra bloco com texto/CTA em fundo do tema
3. ✅ Não tenta carregar `<img>`

---

---

## Correções de Bug e Melhorias de UX (Fase 19.2 - 2025-12-08)

### Problemas Corrigidos

1. **Bug de persistência do tipo "Retrato"**
   - Banners criados como "Retrato" apareciam na aba "Hero" após salvar
   - Tipo não era mantido corretamente ao editar

2. **UX confusa com termos técnicos**
   - Termos "Hero" e "Retrato" não eram claros para lojistas
   - Não ficava claro onde cadastrar banners para desktop vs mobile

### Correções Implementadas

#### 1. Persistência do Tipo de Banner ✅

**Arquivos Modificados:**
- `src/Http/Controllers/Admin/HomeBannersController.php`
  - Método `create()` agora recebe `tipo` via query string (`?tipo=hero` ou `?tipo=portrait`)
  - Passa `tipoInicial` para a view
  - Métodos `store()` e `update()` redirecionam mantendo o filtro de tipo na URL

**Resultado:**
- Banners criados via aba "Retrato" são salvos corretamente com `tipo = 'portrait'`
- Banners criados via aba "Hero" são salvos corretamente com `tipo = 'hero'`
- Após salvar, o usuário permanece na aba correta

#### 2. Melhorias de Textos e UX ✅

**Arquivos Modificados:**
- `themes/default/admin/home/banners-content.php`
  - Abas renomeadas: "Hero" → "Carrossel principal (topo)", "Retrato" → "Banners de apoio (retratos)"
  - Badge nos cards: "Hero" → "Carrossel", "Retrato" → "Apoio"
  - Título: "Banners Configurados" → "Banners da Home"
  - Botões de criação separados: "+ Carrossel principal" e "+ Banner de apoio"

- `themes/default/admin/home/banners-form-content.php`
  - Campo "Tipo" substituído por "Posição do banner" com radio buttons visuais
  - Radio buttons com descrições claras:
    - **Carrossel principal (topo)**: "Banner grande no topo da página, visível em desktop e celular"
    - **Banners de apoio (retratos)**: "Banners menores em formato retrato para áreas laterais ou de apoio"
  - Textos de ajuda melhorados:
    - **Imagem Desktop**: "Versão do banner para telas de computador (carrossel principal). Se você não enviar imagem mobile, esta será usada também no celular."
    - **Imagem Mobile**: "Versão do banner otimizada para celular. Recomendada para o carrossel em dispositivos móveis."
  - CSS para radio buttons visuais com hover e estados ativos

**Resultado:**
- Interface mais intuitiva e autoexplicativa
- Lojistas entendem claramente onde cadastrar cada tipo de banner
- Termos técnicos substituídos por linguagem do usuário

### Testes de Validação

- ✅ Criar banner via aba "Banners de apoio (retratos)" → aparece apenas nessa aba
- ✅ Criar banner via aba "Carrossel principal (topo)" → aparece apenas nessa aba
- ✅ Editar banner "Retrato" e salvar → continua aparecendo na aba correta
- ✅ Editar banner "Hero" e salvar → continua aparecendo na aba correta
- ✅ Formulário pré-seleciona tipo correto ao abrir de uma aba específica

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08 (Fase 19.2 - Correções de Bug e UX)  
**Status:** ✅ Implementação Concluída

