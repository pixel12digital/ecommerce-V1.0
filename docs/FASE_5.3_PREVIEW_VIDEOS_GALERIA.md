# Fase 5.3: Preview de Vídeos na Galeria da Loja

## 📋 Resumo

Implementação de thumbnails de vídeos integrados na galeria de imagens da PDP (Product Detail Page), permitindo que vídeos apareçam junto com as imagens na mesma interface unificada.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Integrar vídeos do produto na galeria de imagens da PDP, mostrando thumbnails de vídeo junto com as miniaturas de imagens, com ícone de play visível, e abrindo o player em modal ao clicar.

---

## 📦 Estrutura de Dados

### Tabela: `produto_videos`

A tabela já existe (criada na Fase 5) e contém:
- `id`, `tenant_id`, `produto_id`
- `titulo`, `url`, `ordem`, `ativo`
- `created_at`, `updated_at`

### Processamento de Vídeos

**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`

**Método:** `processVideoInfo($url)`

Este método processa a URL do vídeo e retorna:
- `type`: Tipo do vídeo (`youtube`, `vimeo`, `mp4`, `unknown`)
- `embed_url`: URL para embed (iframe)
- `thumb_url`: URL do thumbnail

**Thumbnails gerados:**
- **YouTube:** `https://img.youtube.com/vi/{VIDEO_ID}/hqdefault.jpg`
- **Vimeo:** Placeholder SVG (não há API pública simples)
- **MP4:** Placeholder SVG
- **Outros:** Placeholder SVG genérico

---

## 🔧 Implementação

### 1. Backend - Processamento de Vídeos

**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`

**Alterações:**
- Método `processVideoInfo()` adicionado para processar URLs de vídeo
- Geração automática de thumbnails (YouTube usa API pública, outros usam placeholders SVG)
- Vídeos são processados no método `show()` antes de passar para a view

**Código:**
```php
// Processar vídeos: adicionar informações de embed e thumbnails
$videosRaw = $this->getVideosByProductId($db, $tenantId, $produto['id']);

$videos = [];
foreach ($videosRaw as $video) {
    $videoInfo = $this->processVideoInfo($video['url']);
    $videos[] = array_merge($video, [
        'tipo' => $videoInfo['type'],
        'embed_url' => $videoInfo['embed_url'],
        'thumb_url' => $videoInfo['thumb_url'],
    ]);
}
```

### 2. View - Integração na Galeria

**Arquivo:** `themes/default/storefront/products/show.php`

**Alterações:**
- Galeria unificada: thumbnails de imagens e vídeos na mesma lista
- Estrutura HTML:
  - `.thumbnail-wrapper` para cada item (imagem ou vídeo)
  - `.thumbnail-wrapper--video` para identificar vídeos
  - Atributos `data-*` para JavaScript (`data-type`, `data-video-type`, `data-video-embed`, `data-video-url`)
  - Ícone de play (`<i class="bi bi-play-circle-fill">`) sobre thumbnails de vídeo

**Estrutura HTML:**
```html
<div class="thumbnails">
    <!-- Imagens -->
    <div class="thumbnail-wrapper" data-type="image">
        <img src="..." class="thumbnail" onclick="changeImage(...)">
    </div>
    
    <!-- Vídeos -->
    <div class="thumbnail-wrapper thumbnail-wrapper--video" 
         data-type="video"
         data-video-type="youtube"
         data-video-embed="https://www.youtube.com/embed/..."
         data-video-url="https://...">
        <div class="thumbnail thumbnail--video">
            <img src="..." class="thumbnail-image">
            <span class="thumbnail-play-icon">
                <i class="bi bi-play-circle-fill"></i>
            </span>
        </div>
    </div>
</div>
```

### 3. CSS - Estilos para Vídeos

**Arquivo:** `themes/default/storefront/products/show.php` (bloco `<style>`)

**Classes adicionadas:**
- `.thumbnail-wrapper`: Container para cada thumbnail
- `.thumbnail-wrapper--video`: Modificador para vídeos
- `.thumbnail--video`: Thumbnail específico de vídeo
- `.thumbnail-image`: Imagem dentro do thumbnail de vídeo
- `.thumbnail-play-icon`: Ícone de play centralizado

**Características:**
- Mesmas dimensões das thumbnails de imagem (80x80px)
- Ícone de play centralizado, branco, com sombra
- Hover: escala do ícone e borda destacada
- Transições suaves

### 4. JavaScript - Comportamento de Vídeos

**Arquivo:** `themes/default/storefront/products/show.php` (bloco `<script>`)

**Funcionalidades:**
- Detecção de cliques em thumbnails de vídeo
- Abertura do modal existente (reutilizado da Fase 5.1)
- Injeção do player apropriado (iframe para YouTube/Vimeo, `<video>` para MP4)
- Gerenciamento de classe `active` (mesmo comportamento das imagens)

**Código:**
```javascript
const videoThumbnails = document.querySelectorAll('.thumbnail-wrapper--video');
videoThumbnails.forEach(thumbWrapper => {
    thumbWrapper.addEventListener('click', function() {
        // Ler atributos data-*
        // Montar player HTML
        // Abrir modal
        // Marcar thumbnail como ativo
    });
});
```

---

## 🎨 Interface do Usuário

### Thumbnails de Vídeo

**Visual:**
- Thumbnail com imagem (YouTube usa thumbnail real, outros usam placeholder)
- Ícone de play centralizado (Bootstrap Icons `bi-play-circle-fill`)
- Mesmo tamanho das thumbnails de imagem (80x80px)
- Borda destacada no hover (cor primária do tema)

**Comportamento:**
- Clique abre modal com player
- Thumbnail recebe classe `active` quando selecionado
- Modal reutiliza estrutura da Fase 5.1

### Modal de Vídeo

**Reutilização:**
- Mesmo modal da Fase 5.1 (`#product-video-modal`)
- Mesmo comportamento de fechamento (ESC, backdrop, botão X)
- Player injetado dinamicamente via JavaScript

---

## ✅ Checklist de Aceite

- [x] Thumbnails de vídeo aparecem na galeria junto com imagens
- [x] Ícone de play é visível sobre thumbnails de vídeo
- [x] Clique em thumbnail de vídeo abre modal com player
- [x] Player funciona para YouTube, Vimeo e MP4
- [x] Thumbnail de vídeo recebe classe `active` quando selecionado
- [x] Funcionalidade de imagens continua funcionando normalmente
- [x] Modal fecha corretamente (ESC, backdrop, botão X)
- [x] Thumbnails de YouTube usam imagem real da API
- [x] Thumbnails de outros tipos usam placeholder SVG
- [x] Multi-tenant: vídeos filtrados por `tenant_id`
- [x] Responsividade mantida

---

## 🔄 Compatibilidade

### Funcionalidades Mantidas

- ✅ Galeria de imagens (troca de imagem principal)
- ✅ Thumbnails de imagens com classe `active`
- ✅ Modal de vídeos da Fase 5.1 (reutilizado)
- ✅ Seção separada de vídeos (Fase 5.1) - mantida para compatibilidade

### Não Afetado

- ❌ Dados gerais do produto
- ❌ Outras seções da PDP
- ❌ Admin de produtos

---

## 📊 Estrutura de Arquivos Modificados

```
src/Http/Controllers/Storefront/
└── ProductController.php
    ├── processVideoInfo(): Novo método para processar vídeos
    └── show(): Processa vídeos antes de passar para view

themes/default/storefront/products/
└── show.php
    ├── HTML: Galeria unificada (imagens + vídeos)
    ├── CSS: Estilos para thumbnails de vídeo
    └── JavaScript: Comportamento de cliques em vídeos
```

---

## 🚀 Próximos Passos (Futuro)

### Fase 5.4: Upload de Vídeos Próprios
- Upload de arquivos de vídeo (além de links)
- Processamento e armazenamento
- Geração de thumbnails a partir de frames

### Melhorias Futuras
- Thumbnails reais para Vimeo (via API)
- Preview de vídeo ao passar o mouse (hover)
- Controles de vídeo na área principal (em vez de modal)

---

## 📚 Referências

- **Fase 5.1:** Integração de Vídeos na PDP (modal de vídeos)
- **Fase 5.2:** Drag-and-Drop na Galeria (ordenação de imagens)
- **Tabela:** `produto_videos` (migration `033_create_produto_videos_table.php`)
- **Controller:** `Storefront\ProductController@show()`
- **View:** `themes/default/storefront/products/show.php`

---

## 🐛 Troubleshooting

### Problema: Thumbnails de vídeo não aparecem

**Verificar:**
1. Vídeos estão sendo carregados no controller (`getVideosByProductId()`)
2. `processVideoInfo()` está sendo chamado
3. `thumb_url` está sendo gerado corretamente
4. HTML está renderizando os thumbnails de vídeo

### Problema: Clique em vídeo não abre modal

**Verificar:**
1. JavaScript está carregado (sem erros no console)
2. Modal `#product-video-modal` existe no DOM
3. Event listeners estão sendo anexados corretamente
4. Atributos `data-*` estão presentes

### Problema: Thumbnail de YouTube não carrega

**Verificar:**
1. URL do vídeo está no formato correto
2. ID do vídeo está sendo extraído corretamente
3. URL do thumbnail está acessível (pode ser bloqueada por CORS em alguns casos)

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX


