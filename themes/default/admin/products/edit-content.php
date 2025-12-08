<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$message = $message ?? null;
$messageType = $messageType ?? 'success';
?>

<div class="product-edit-page">
    <?php if ($message): ?>
        <div class="admin-alert admin-alert-<?= $messageType ?>" style="margin-bottom: 2rem;">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="<?= $basePath ?>/admin/produtos" class="admin-btn admin-btn-secondary">
                <i class="bi bi-arrow-left icon"></i>
                Voltar para lista
            </a>
        </div>
        <div>
            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" target="_blank" class="admin-btn admin-btn-outline">
                <i class="bi bi-eye icon"></i>
                Ver na loja
            </a>
        </div>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/produtos/<?= $produto['id'] ?>" enctype="multipart/form-data">
        <!-- Seção: Dados Gerais -->
        <div class="info-section">
            <h2 class="section-title">Dados Gerais</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($produto['slug']) ?>" 
                           placeholder="Será gerado automaticamente se vazio">
                </div>
                
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" value="<?= htmlspecialchars($produto['sku'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="publish" <?= $produto['status'] === 'publish' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('publish') ?>
                        </option>
                        <option value="draft" <?= $produto['status'] === 'draft' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('draft') ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Seção: Preços -->
        <div class="info-section">
            <h2 class="section-title">Preços</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Preço Regular *</label>
                    <input type="number" name="preco_regular" value="<?= $produto['preco_regular'] ?>" 
                           step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Preço Promocional</label>
                    <input type="number" name="preco_promocional" value="<?= $produto['preco_promocional'] ?? '' ?>" 
                           step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label>Data Início Promoção</label>
                    <input type="date" name="data_promocao_inicio" 
                           value="<?= $produto['data_promocao_inicio'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Data Fim Promoção</label>
                    <input type="date" name="data_promocao_fim" 
                           value="<?= $produto['data_promocao_fim'] ?? '' ?>">
                </div>
            </div>
        </div>

        <!-- Seção: Estoque -->
        <div class="info-section">
            <h2 class="section-title">Estoque</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="gerencia_estoque" value="1" 
                               <?= $produto['gerencia_estoque'] ? 'checked' : '' ?>> 
                        Gerencia Estoque
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade_estoque" value="<?= $produto['quantidade_estoque'] ?>" 
                           min="0">
                </div>
                
                <div class="form-group">
                    <label>Status de Estoque *</label>
                    <select name="status_estoque" required>
                        <option value="instock" <?= $produto['status_estoque'] === 'instock' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('instock') ?>
                        </option>
                        <option value="outofstock" <?= $produto['status_estoque'] === 'outofstock' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('outofstock') ?>
                        </option>
                        <option value="onbackorder" <?= $produto['status_estoque'] === 'onbackorder' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('onbackorder') ?>
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="permite_pedidos_falta" value="1" 
                               <?= $produto['permite_pedidos_falta'] ? 'checked' : '' ?>> 
                        Permite Pedidos em Falta
                    </label>
                </div>
            </div>
        </div>

        <!-- Seção: Descrições -->
        <div class="info-section">
            <h2 class="section-title">Descrições</h2>
            
            <div class="form-group">
                <label>Descrição Curta</label>
                <textarea name="descricao_curta" rows="3" 
                          placeholder="Breve descrição do produto"><?= htmlspecialchars($produto['descricao_curta'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Descrição Completa</label>
                <textarea name="descricao" rows="10" 
                          placeholder="Descrição detalhada do produto"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Seção: Mídia do Produto -->
        <div class="info-section">
            <h2 class="section-title">Mídia do Produto</h2>
            
            <!-- Imagem de Destaque -->
            <div class="media-section">
                <h3 style="margin-bottom: 1rem; font-size: 1.25rem; color: #555;">Imagem de Destaque</h3>
                
                <div class="featured-image-container">
                    <?php if ($imagemPrincipal): ?>
                        <div class="current-image">
                            <img src="<?= $basePath ?><?= htmlspecialchars($imagemPrincipal['caminho_arquivo']) ?>" 
                                 alt="Imagem de destaque atual" 
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E'">
                            <div class="image-label">Imagem atual</div>
                        </div>
                    <?php else: ?>
                        <div class="current-image placeholder">
                            <div class="placeholder-content">
                                <i class="bi bi-image icon" style="font-size: 3rem; color: #999;"></i>
                                <div class="image-label">Sem imagem de destaque</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-actions">
                        <div class="form-group">
                            <label>Nova imagem de destaque</label>
                            <input type="file" name="imagem_destaque" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                            <small style="color: #666; display: block; margin-top: 0.5rem;">
                                Formatos aceitos: JPG, PNG, GIF, WEBP
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Galeria de Imagens -->
            <div class="media-section" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.25rem; color: #555;">Galeria de Imagens</h3>
                
                <?php if (!empty($galeria)): ?>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                        <i class="bi bi-info-circle icon"></i> Arraste as imagens para reordená-las
                    </p>
                    <div class="gallery-grid product-gallery" id="product-gallery">
                        <?php foreach ($galeria as $index => $img): ?>
                            <div class="gallery-item product-gallery__item" 
                                 data-imagem-id="<?= (int)$img['id'] ?>"
                                 draggable="true">
                                <div class="product-gallery__thumb">
                                    <img src="<?= $basePath ?><?= htmlspecialchars($img['caminho_arquivo']) ?>" 
                                         alt="Imagem da galeria"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'150\' height=\'150\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImagem%3C/text%3E%3C/svg%3E'">
                                </div>
                                <div class="gallery-item-actions">
                                    <button type="button" class="btn-set-main" 
                                            onclick="setMainFromGallery(<?= $img['id'] ?>)"
                                            title="Definir como imagem de destaque">
                                        <i class="bi bi-star-fill icon"></i>
                                    </button>
                                    <label class="btn-remove">
                                        <input type="checkbox" name="remove_imagens[]" value="<?= $img['id'] ?>">
                                        <i class="bi bi-trash icon"></i>
                                    </label>
                                </div>
                                <input type="hidden"
                                       name="galeria_ordem[<?= (int)$img['id'] ?>]"
                                       value="<?= (int)($img['ordem'] ?? ($index + 1)) ?>"
                                       class="product-gallery__ordem-input">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 1rem;">Nenhuma imagem na galeria.</p>
                <?php endif; ?>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label>Adicionar imagens à galeria</label>
                    <input type="file" name="galeria[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Você pode selecionar múltiplas imagens
                    </small>
                </div>
            </div>

            <!-- Campo oculto para definir main da galeria -->
            <input type="hidden" name="main_from_gallery_id" id="main_from_gallery_id" value="">
        </div>

        <!-- Seção: Vídeos do Produto -->
        <div class="info-section">
            <h2 class="section-title">Vídeos do Produto</h2>
            
            <?php if (!empty($videos)): ?>
                <div class="videos-list">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <div class="video-fields">
                                <div class="form-group">
                                    <label>Título (opcional)</label>
                                    <input type="text" name="videos[<?= $video['id'] ?>][titulo]" 
                                           value="<?= htmlspecialchars($video['titulo'] ?? '') ?>" 
                                           placeholder="Ex: Vídeo demonstrativo">
                                </div>
                                <div class="form-group">
                                    <label>URL do Vídeo *</label>
                                    <input type="url" name="videos[<?= $video['id'] ?>][url]" 
                                           value="<?= htmlspecialchars($video['url']) ?>" 
                                           placeholder="https://www.youtube.com/watch?v=..." required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="videos[<?= $video['id'] ?>][ativo]" value="1" 
                                               <?= $video['ativo'] ? 'checked' : '' ?>> 
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            <div class="video-actions">
                                <label class="btn-remove-video">
                                    <input type="checkbox" name="remove_videos[]" value="<?= $video['id'] ?>">
                                    <i class="bi bi-trash icon"></i> Remover
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 1rem;">Nenhum vídeo cadastrado.</p>
            <?php endif; ?>
            
            <!-- Novo vídeo -->
            <div class="new-videos-section" style="margin-top: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1.1rem; color: #555;">Adicionar Novo Vídeo</h4>
                <div id="new-videos-container">
                    <div class="video-item new-video">
                        <div class="video-fields">
                            <div class="form-group">
                                <label>Título (opcional)</label>
                                <input type="text" name="novo_videos[0][titulo]" placeholder="Ex: Vídeo demonstrativo">
                            </div>
                            <div class="form-group">
                                <label>URL do Vídeo *</label>
                                <input type="url" name="novo_videos[0][url]" 
                                       placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-add-video" onclick="addNewVideoField()">
                    <i class="bi bi-plus-circle icon"></i> Adicionar mais um vídeo
                </button>
            </div>
        </div>

        <!-- Informações somente leitura -->
        <div class="info-section" style="background: #f8f9fa;">
            <h2 class="section-title">Informações do Sistema</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">ID Interno</span>
                    <span class="info-value">#<?= $produto['id'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">ID Original WP</span>
                    <span class="info-value"><?= $produto['id_original_wp'] ?? '-' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo</span>
                    <span class="info-value"><?= htmlspecialchars($produto['tipo']) ?></span>
                </div>
                <?php if (!empty($categorias)): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Categorias</span>
                        <span class="info-value">
                            <?php foreach ($categorias as $cat): ?>
                                <span class="badge-category"><?= htmlspecialchars($cat['nome']) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($tags)): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Tags</span>
                        <span class="info-value">
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge-tag"><?= htmlspecialchars($tag['nome']) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botão Salvar -->
        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem; margin-top: 2rem;">
                <i class="bi bi-check-circle icon"></i>
                Salvar Alterações
            </button>
                <i class="bi bi-check-circle icon"></i> Salvar alterações
            </button>
        </div>
    </form>
</div>

<script>
let newVideoIndex = 1;

function addNewVideoField() {
    const container = document.getElementById('new-videos-container');
    const newVideo = document.createElement('div');
    newVideo.className = 'video-item new-video';
    newVideo.innerHTML = `
        <div class="video-fields">
            <div class="form-group">
                <label>Título (opcional)</label>
                <input type="text" name="novo_videos[${newVideoIndex}][titulo]" placeholder="Ex: Vídeo demonstrativo">
            </div>
            <div class="form-group">
                <label>URL do Vídeo *</label>
                <input type="url" name="novo_videos[${newVideoIndex}][url]" 
                       placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
            </div>
        </div>
        <div class="video-actions">
            <button type="button" class="btn-remove-video" onclick="this.closest('.video-item').remove()">
                <i class="bi bi-trash icon"></i> Remover
            </button>
        </div>
    `;
    container.appendChild(newVideo);
    newVideoIndex++;
}

function setMainFromGallery(imageId) {
    document.getElementById('main_from_gallery_id').value = imageId;
    // Marcar visualmente a seleção
    document.querySelectorAll('.btn-set-main').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.btn-set-main').classList.add('active');
    
    // Mostrar mensagem
    alert('Imagem selecionada! Clique em "Salvar alterações" para aplicar.');
}

// Drag-and-Drop para Galeria
(function() {
    const gallery = document.getElementById('product-gallery');
    if (!gallery) return;
    
    let draggedElement = null;
    let draggedIndex = null;
    
    const items = gallery.querySelectorAll('.product-gallery__item');
    
    items.forEach((item, index) => {
        // Drag Start
        item.addEventListener('dragstart', function(e) {
            draggedElement = this;
            draggedIndex = Array.from(gallery.children).indexOf(this);
            this.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        // Drag End
        item.addEventListener('dragend', function(e) {
            this.classList.remove('is-dragging');
            gallery.querySelectorAll('.product-gallery__item').forEach(el => {
                el.classList.remove('drag-over');
            });
            updateOrder();
        });
        
        // Drag Over
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = getDragAfterElement(gallery, e.clientY);
            const dragging = document.querySelector('.is-dragging');
            
            if (afterElement == null) {
                gallery.appendChild(dragging);
            } else {
                gallery.insertBefore(dragging, afterElement);
            }
        });
        
        // Drag Enter
        item.addEventListener('dragenter', function(e) {
            e.preventDefault();
            if (this !== draggedElement) {
                this.classList.add('drag-over');
            }
        });
        
        // Drag Leave
        item.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        // Drop
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
    });
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.product-gallery__item:not(.is-dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    function updateOrder() {
        const items = gallery.querySelectorAll('.product-gallery__item');
        items.forEach((item, index) => {
            const input = item.querySelector('.product-gallery__ordem-input');
            if (input) {
                // Ordem começa em 1 (a imagem principal tem ordem 0)
                input.value = index + 1;
            }
        });
    }
})();
</script>

<style>
.product-edit-page {
    max-width: 1400px;
}
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.btn-back {
    padding: 0.75rem 1.5rem;
    background: #023A8D;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}
.btn-view-store {
    padding: 0.75rem 1.5rem;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}
.info-section {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.section-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #023A8D;
    padding-bottom: 0.5rem;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.form-group label {
    font-weight: 600;
    color: #555;
    font-size: 0.9rem;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group input[type="url"],
.form-group input[type="file"],
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
}
.form-group textarea {
    resize: vertical;
}
.form-group input[type="checkbox"] {
    width: auto;
    margin-right: 0.5rem;
}
.media-section {
    margin-top: 1.5rem;
}
.featured-image-container {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}
.current-image {
    width: 300px;
    height: 300px;
    border: 2px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    position: relative;
}
.current-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.current-image.placeholder .placeholder-content {
    text-align: center;
    color: #999;
}
.image-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.5rem;
    text-align: center;
    font-size: 0.875rem;
}
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.gallery-item {
    position: relative;
    border: 2px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}
.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Drag-and-Drop Styles */
.product-gallery__item {
    cursor: grab;
    transition: transform 0.2s, opacity 0.2s;
}
.product-gallery__item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.product-gallery__item.is-dragging {
    opacity: 0.5;
    cursor: grabbing;
    transform: scale(0.95);
}
.product-gallery__item.drag-over {
    border-color: var(--cor-primaria, #2E7D32);
    border-width: 3px;
}
.product-gallery__thumb {
    width: 100%;
    height: 100%;
    position: relative;
}
.product-gallery__ordem-input {
    display: none;
}
.gallery-item-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.5rem;
}
.btn-set-main,
.btn-remove {
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 4px;
    padding: 0.5rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-set-main.active {
    background: #F7931E;
    color: white;
}
.btn-remove {
    cursor: pointer;
}
.btn-remove input[type="checkbox"] {
    display: none;
}
.btn-remove:has(input:checked) {
    background: #dc3545;
    color: white;
}
.videos-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.video-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.video-fields {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.video-actions {
    display: flex;
    align-items: flex-start;
}
.btn-remove-video {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-remove-video input[type="checkbox"] {
    display: none;
}
.btn-remove-video:has(input:checked) {
    opacity: 0.5;
}
.btn-add-video {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.75rem 1.5rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
/* Botão salvar agora usa admin-btn admin-btn-primary */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-label {
    font-weight: 600;
    color: #555;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}
.info-value {
    color: #333;
    font-size: 1rem;
}
.badge-category,
.badge-tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #e0e0e0;
    border-radius: 4px;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}
</style>


