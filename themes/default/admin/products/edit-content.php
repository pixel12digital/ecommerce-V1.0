<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$message = $message ?? null;
$messageType = $messageType ?? 'success';

// Helper para URLs de mídia
use App\Support\MediaUrlHelper;
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}
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
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="exibir_no_catalogo" value="1" 
                               <?= (!isset($produto['exibir_no_catalogo']) || $produto['exibir_no_catalogo'] == 1) ? 'checked' : '' ?>>
                        <span>Exibir este produto no catálogo da loja</span>
                    </label>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                        Quando desmarcado, o produto não aparecerá nas listagens da loja, mas ainda poderá ser acessado diretamente pela URL.
                    </small>
                </div>
            </div>
        </div>

        <!-- Seção: Preços -->
        <div class="info-section">
            <h2 class="section-title">Preços</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Preço Regular *</label>
                    <input type="text" name="preco_regular" id="preco_regular" 
                           value="<?= number_format($produto['preco_regular'], 2, ',', '') ?>" 
                           placeholder="0,00" required
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 380,00)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Preço Promocional</label>
                    <input type="text" name="preco_promocional" id="preco_promocional" 
                           value="<?= $produto['preco_promocional'] ? number_format($produto['preco_promocional'], 2, ',', '') : '' ?>" 
                           placeholder="0,00"
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 350,00)
                    </small>
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

        <!-- Seção: Categorias -->
        <div class="info-section">
            <h2 class="section-title">Categorias</h2>
            
            <div class="form-group">
                <label>Selecione as categorias deste produto</label>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; background: #f9f9f9;">
                    <?php 
                    $categoriasProdutoIds = $categoriasProdutoIds ?? [];
                    $todasCategorias = $todasCategorias ?? [];
                    foreach ($todasCategorias as $categoria): 
                    ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="categorias[]" value="<?= $categoria['id'] ?>" 
                                   <?= in_array($categoria['id'], $categoriasProdutoIds) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($categoria['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($todasCategorias)): ?>
                        <p style="color: #999; font-style: italic;">Nenhuma categoria cadastrada. Crie categorias primeiro.</p>
                    <?php endif; ?>
                </div>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Selecione uma ou mais categorias para organizar seus produtos. Um produto pode pertencer a múltiplas categorias.
                </small>
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
                            <img src="<?= media_url($imagemPrincipal['caminho_arquivo']) ?>" 
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
                            <label>Escolher imagem de destaque</label>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <input type="text" 
                                       id="imagem_destaque_path_display" 
                                       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>" 
                                       placeholder="Selecione uma imagem na biblioteca"
                                       readonly
                                       style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                                <!-- Campo hidden que será enviado no POST -->
                                <input type="hidden" 
                                       name="imagem_destaque_path" 
                                       id="imagem_destaque_path" 
                                       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>">
                                <!-- Campo hidden para sinalizar remoção da imagem de destaque -->
                                <input type="hidden" 
                                       name="remove_featured" 
                                       id="remove_featured" 
                                       value="0">
                                <button type="button" 
                                        class="js-open-media-library admin-btn admin-btn-primary" 
                                        data-media-target="#imagem_destaque_path"
                                        data-folder="produtos"
                                        data-preview="#imagem_destaque_preview"
                                        style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                                    <i class="bi bi-image icon"></i> Escolher da biblioteca
                                </button>
                                <?php if ($imagemPrincipal): ?>
                                <button type="button" 
                                        id="btn-remove-featured"
                                        class="admin-btn" 
                                        onclick="removeFeaturedImage()"
                                        style="padding: 0.75rem 1.5rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                                    <i class="bi bi-trash icon"></i> Remover imagem
                                </button>
                                <?php endif; ?>
                            </div>
                            <div id="imagem_destaque_preview" style="margin-top: 0.75rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher uma imagem da biblioteca de mídia.
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
                                    <img src="<?= media_url($img['caminho_arquivo']) ?>" 
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
                    <div style="display: flex; gap: 0.5rem; align-items: flex-start; margin-bottom: 0.75rem;">
                        <button type="button" 
                                class="js-open-media-library admin-btn admin-btn-primary" 
                                data-media-target="#galeria_paths_container"
                                data-folder="produtos"
                                data-multiple="true"
                                style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                            <i class="bi bi-image icon"></i> Adicionar da biblioteca
                        </button>
                    </div>
                    <!-- Container para inputs hidden das imagens da biblioteca -->
                    <div id="galeria_paths_container" style="display: none;">
                        <?php 
                        // Preencher com imagens existentes da galeria para preservar ao salvar
                        foreach ($galeria as $img): 
                        ?>
                            <input type="hidden" 
                                   name="galeria_paths[]" 
                                   value="<?= htmlspecialchars($img['caminho_arquivo']) ?>"
                                   data-imagem-id="<?= (int)$img['id'] ?>">
                        <?php endforeach; ?>
                    </div>
                    <!-- Container para preview das novas imagens da biblioteca -->
                    <div id="galeria_preview_container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 1rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher imagens da biblioteca de mídia.
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

// Gerenciar comportamento do campo Status de Estoque baseado em Gerencia Estoque
(function() {
    var gerenciaEstoqueCheckbox = document.querySelector('input[name="gerencia_estoque"]');
    var statusEstoqueSelect = document.querySelector('select[name="status_estoque"]');
    var statusEstoqueGroup = statusEstoqueSelect ? statusEstoqueSelect.closest('.form-group') : null;
    
    function updateStatusEstoqueField() {
        if (!gerenciaEstoqueCheckbox || !statusEstoqueSelect) return;
        
        var isGerenciando = gerenciaEstoqueCheckbox.checked;
        
        if (isGerenciando) {
            // Desabilitar select e adicionar texto explicativo
            statusEstoqueSelect.disabled = true;
            statusEstoqueSelect.style.opacity = '0.6';
            statusEstoqueSelect.style.cursor = 'not-allowed';
            
            // Adicionar ou atualizar texto de ajuda
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (!helpText) {
                helpText = document.createElement('small');
                helpText.className = 'help-text-estoque';
                helpText.style.cssText = 'color: #666; display: block; margin-top: 0.5rem; font-style: italic;';
                helpText.textContent = 'Quando o gerenciamento de estoque está ativo, o status é definido automaticamente com base na quantidade em estoque.';
                statusEstoqueGroup.appendChild(helpText);
            }
            helpText.style.display = 'block';
        } else {
            // Habilitar select e remover texto de ajuda
            statusEstoqueSelect.disabled = false;
            statusEstoqueSelect.style.opacity = '1';
            statusEstoqueSelect.style.cursor = 'pointer';
            
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (helpText) {
                helpText.style.display = 'none';
            }
        }
    }
    
    // Aplicar ao carregar a página
    if (gerenciaEstoqueCheckbox && statusEstoqueSelect) {
        updateStatusEstoqueField();
        
        // Aplicar quando checkbox mudar
        gerenciaEstoqueCheckbox.addEventListener('change', updateStatusEstoqueField);
    }
})();

// Máscara de preço (aceitar vírgula, converter para ponto antes de enviar)
(function() {
    function formatPrice(value) {
        // Remove tudo exceto números e vírgula
        value = value.replace(/[^\d,]/g, '');
        // Substitui múltiplas vírgulas por uma única
        value = value.replace(/,+/g, ',');
        // Garante que há no máximo uma vírgula
        var parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts.slice(1).join('');
        }
        return value;
    }
    
    function convertPriceToFloat(value) {
        if (!value || value.trim() === '') return '';
        // Converte vírgula para ponto
        return value.replace(',', '.');
    }
    
    var precoRegular = document.getElementById('preco_regular');
    var precoPromocional = document.getElementById('preco_promocional');
    
    if (precoRegular) {
        precoRegular.addEventListener('input', function() {
            this.value = formatPrice(this.value);
        });
        
        precoRegular.addEventListener('blur', function() {
            if (this.value && !this.value.includes(',')) {
                // Se não tem vírgula, adiciona ,00
                this.value = this.value + ',00';
            }
        });
    }
    
    if (precoPromocional) {
        precoPromocional.addEventListener('input', function() {
            this.value = formatPrice(this.value);
        });
        
        precoPromocional.addEventListener('blur', function() {
            if (this.value && !this.value.includes(',')) {
                this.value = this.value + ',00';
            }
        });
    }
    
    // Converter antes de enviar formulário
    var form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (precoRegular && precoRegular.value) {
                precoRegular.value = convertPriceToFloat(precoRegular.value);
            }
            if (precoPromocional && precoPromocional.value) {
                precoPromocional.value = convertPriceToFloat(precoPromocional.value);
            }
            
            // Log para debug: verificar quantos inputs de galeria estão sendo enviados
            var galeriaInputs = document.querySelectorAll('#galeria_paths_container input[name="galeria_paths[]"]');
            console.log('[Form Submit] Total de inputs de galeria que serão enviados:', galeriaInputs.length);
            var galeriaPaths = [];
            galeriaInputs.forEach(function(input) {
                galeriaPaths.push(input.value);
            });
            console.log('[Form Submit] Caminhos de galeria:', galeriaPaths);
            
            // Verificar se há imagens marcadas para remoção
            var removeInputs = document.querySelectorAll('input[name="remove_imagens[]"]:checked');
            console.log('[Form Submit] Imagens marcadas para remoção:', removeInputs.length);
        });
    }
})();

// Função para remover imagem de destaque
window.removeFeaturedImage = function() {
    console.log('[Imagem Destaque] Removendo imagem de destaque');
    
    var imagemDestaqueInput = document.getElementById('imagem_destaque_path');
    var imagemDestaqueDisplay = document.getElementById('imagem_destaque_path_display');
    var removeFeaturedInput = document.getElementById('remove_featured');
    var previewContainer = document.getElementById('imagem_destaque_preview');
    var currentImageContainer = document.querySelector('.current-image');
    var btnRemove = document.getElementById('btn-remove-featured');
    
    // Limpar campos
    if (imagemDestaqueInput) {
        imagemDestaqueInput.value = '';
    }
    if (imagemDestaqueDisplay) {
        imagemDestaqueDisplay.value = '';
    }
    
    // Marcar flag de remoção
    if (removeFeaturedInput) {
        removeFeaturedInput.value = '1';
    }
    
    // Atualizar preview visual
    if (previewContainer) {
        previewContainer.innerHTML = '';
    }
    
    // Atualizar container da imagem atual
    if (currentImageContainer) {
        currentImageContainer.classList.add('placeholder');
        currentImageContainer.innerHTML = 
            '<div class="placeholder-content">' +
            '<i class="bi bi-image icon" style="font-size: 3rem; color: #999;"></i>' +
            '<div class="image-label">Sem imagem de destaque</div>' +
            '</div>';
    }
    
    // Esconder botão de remoção
    if (btnRemove) {
        btnRemove.style.display = 'none';
    }
    
    console.log('[Imagem Destaque] Imagem de destaque removida (será salva ao submeter formulário)');
};

// Atualizar preview da imagem de destaque quando selecionada
(function() {
    var imagemDestaqueInput = document.getElementById('imagem_destaque_path');
    var imagemDestaqueDisplay = document.getElementById('imagem_destaque_path_display');
    var removeFeaturedInput = document.getElementById('remove_featured');
    var btnRemove = document.getElementById('btn-remove-featured');
    
    function updateImagePreview(url) {
        if (!url) return;
        
        // Atualizar campo de exibição
        if (imagemDestaqueDisplay) {
            imagemDestaqueDisplay.value = url;
        }
        
        // Construir URL completa da imagem
        var imageUrl = url;
        if (!imageUrl.startsWith('/')) {
            imageUrl = '/' + imageUrl;
        }
        
        // Atualizar preview pequeno (#imagem_destaque_preview)
        var previewSmall = document.getElementById('imagem_destaque_preview');
        if (previewSmall) {
            previewSmall.innerHTML = '<img src="' + imageUrl + '" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 0.5rem; border: 1px solid #ddd; padding: 4px;" onerror="this.parentElement.innerHTML=\'<div style=\\\'color: #999; padding: 1rem; text-align: center;\\\'>Erro ao carregar imagem</div>\'">';
        }
        
        // Atualizar preview principal (.current-image img)
        var mainPreview = document.querySelector('.current-image img');
        if (mainPreview) {
            mainPreview.src = imageUrl;
            mainPreview.onerror = function() {
                this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E';
            };
            // Remover classe placeholder se existir
            var currentImageContainer = mainPreview.closest('.current-image');
            if (currentImageContainer) {
                currentImageContainer.classList.remove('placeholder');
            }
        } else {
            // Se não existe img, pode ser placeholder - substituir
            var placeholderContainer = document.querySelector('.current-image.placeholder');
            if (placeholderContainer) {
                placeholderContainer.classList.remove('placeholder');
                var imgElement = document.createElement('img');
                imgElement.src = imageUrl;
                imgElement.alt = 'Imagem de destaque';
                imgElement.onerror = function() {
                    this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E';
                };
                placeholderContainer.innerHTML = '';
                placeholderContainer.appendChild(imgElement);
                var label = document.createElement('div');
                label.className = 'image-label';
                label.textContent = 'Imagem atual';
                placeholderContainer.appendChild(label);
            }
        }
    }
    
    if (imagemDestaqueInput) {
        // Listener para mudanças no campo hidden
        imagemDestaqueInput.addEventListener('change', function() {
            updateImagePreview(this.value);
        });
        
        // Também escutar eventos customizados do media-picker
        imagemDestaqueInput.addEventListener('input', function() {
            updateImagePreview(this.value);
        });
        
        // Carregar preview inicial se houver valor
        if (imagemDestaqueInput.value) {
            updateImagePreview(imagemDestaqueInput.value);
        }
    }
})();

// Processar seleção múltipla da biblioteca de mídia para galeria
(function() {
    var container = document.getElementById('galeria_paths_container');
    var previewContainer = document.getElementById('galeria_preview_container');
    
    // Mostrar container se já houver imagens existentes
    if (container && container.querySelectorAll('input[type="hidden"]').length > 0) {
        container.style.display = 'block';
    }
    
    if (container) {
        console.log('[Galeria] Container encontrado, adicionando listener para media-picker:multiple-selected');
        
        container.addEventListener('media-picker:multiple-selected', function(event) {
            console.log('[Galeria] Evento media-picker:multiple-selected recebido!');
            console.log('[Galeria] URLs recebidas:', event.detail.urls);
            
            var urls = event.detail.urls;
            if (!urls || !Array.isArray(urls)) {
                console.error('[Galeria] URLs inválidas:', urls);
                return;
            }
            
            var addedCount = 0;
            var skippedCount = 0;
            
            // Criar inputs hidden para cada URL
            urls.forEach(function(url) {
                if (!url || typeof url !== 'string') {
                    console.warn('[Galeria] URL inválida ignorada:', url);
                    return;
                }
                
                // Verificar se já não existe (por valor ou por imagem existente)
                var existing = container.querySelector('input[value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existing) {
                    console.log('[Galeria] URL já existe (por valor), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                // Verificar se já existe uma imagem com esse caminho na galeria existente
                var existingByPath = container.querySelector('input[data-imagem-id][value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existingByPath) {
                    console.log('[Galeria] URL já existe (por data-imagem-id), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                console.log('[Galeria] Adicionando nova URL:', url);
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'galeria_paths[]';
                input.value = url;
                container.appendChild(input);
                addedCount++;
                
                // Adicionar preview
                var previewItem = document.createElement('div');
                previewItem.style.cssText = 'position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; aspect-ratio: 1;';
                var imageUrl = url;
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                // Escapar aspas simples para evitar problemas no onclick
                var escapedUrl = url.replace(/'/g, "\\'");
                previewItem.innerHTML = 
                    '<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover;" ' +
                    'onerror="this.parentElement.remove()">' +
                    '<button type="button" onclick="removeGalleryPreview(this, \'' + escapedUrl + '\')" ' +
                    'style="position: absolute; top: 0.25rem; right: 0.25rem; background: #dc3545; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="bi bi-x"></i></button>';
                previewContainer.appendChild(previewItem);
            });
            
            console.log('[Galeria] Resumo: ' + addedCount + ' adicionadas, ' + skippedCount + ' ignoradas');
            console.log('[Galeria] Total de inputs hidden agora:', container.querySelectorAll('input[type="hidden"]').length);
            
            // Mostrar containers se houver imagens
            if (container.querySelectorAll('input[type="hidden"]').length > 0) {
                container.style.display = 'block';
                previewContainer.style.display = 'grid';
            }
        });
        
        // Também escutar no document para garantir que o evento seja capturado
        document.addEventListener('media-picker:multiple-selected', function(event) {
            // Verificar se o evento é para o nosso container
            if (event.target && event.target.id === 'galeria_paths_container') {
                console.log('[Galeria] Evento capturado via document listener');
                // O listener do container já vai processar, não precisa fazer nada aqui
            }
        });
    } else {
        console.error('[Galeria] Container #galeria_paths_container não encontrado!');
    }
    
    // Função para remover preview da galeria
    window.removeGalleryPreview = function(btn, url) {
        console.log('[Galeria] removeGalleryPreview chamado para URL:', url);
        
        var previewItem = btn.closest('div');
        if (!previewItem) {
            console.error('[Galeria] Preview item não encontrado');
            return;
        }
        
        // Encontrar o input hidden correspondente a essa URL
        var input = container.querySelector('input[value="' + url.replace(/"/g, '&quot;').replace(/'/g, "&#39;") + '"]');
        
        if (input) {
            // Verificar se é imagem existente (tem data-imagem-id) ou nova
            if (input.hasAttribute('data-imagem-id')) {
                // É imagem existente - marcar checkbox de remoção
                var imagemId = input.getAttribute('data-imagem-id');
                console.log('[Galeria] Imagem existente encontrada, ID:', imagemId);
                
                // Buscar checkbox de remoção correspondente
                var removeCheckbox = document.querySelector('input[name="remove_imagens[]"][value="' + imagemId + '"]');
                if (removeCheckbox) {
                    removeCheckbox.checked = true;
                    console.log('[Galeria] Checkbox de remoção marcado para imagem ID:', imagemId);
                    
                    // Remover visualmente o preview (opcional - pode manter até salvar)
                    previewItem.style.opacity = '0.5';
                    previewItem.style.border = '2px solid #dc3545';
                    
                    // Adicionar indicador visual de que será removida
                    var indicator = document.createElement('div');
                    indicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 53, 69, 0.9); color: white; padding: 0.5rem; border-radius: 4px; font-size: 0.875rem; z-index: 10;';
                    indicator.textContent = 'Será removida';
                    previewItem.appendChild(indicator);
                } else {
                    console.warn('[Galeria] Checkbox de remoção não encontrado para imagem ID:', imagemId);
                    // Criar checkbox se não existir (fallback)
                    var newCheckbox = document.createElement('input');
                    newCheckbox.type = 'checkbox';
                    newCheckbox.name = 'remove_imagens[]';
                    newCheckbox.value = imagemId;
                    newCheckbox.checked = true;
                    newCheckbox.style.display = 'none';
                    document.querySelector('form[method="POST"]').appendChild(newCheckbox);
                    console.log('[Galeria] Checkbox de remoção criado dinamicamente');
                }
            } else {
                // É imagem nova - remover input e preview
                console.log('[Galeria] Imagem nova encontrada, removendo input e preview');
                input.remove();
                previewItem.remove();
            }
        } else {
            console.warn('[Galeria] Input hidden não encontrado para URL:', url);
            // Remover preview mesmo assim
            previewItem.remove();
        }
        
        // Atualizar contadores
        var totalInputs = container ? container.querySelectorAll('input[type="hidden"]').length : 0;
        var totalPreviews = previewContainer ? previewContainer.querySelectorAll('div').length : 0;
        console.log('[Galeria] Total de inputs restantes:', totalInputs);
        console.log('[Galeria] Total de previews restantes:', totalPreviews);
        
        // Esconder container de preview se não houver mais imagens novas
        if (previewContainer && previewContainer.querySelectorAll('div').length === 0) {
            previewContainer.style.display = 'none';
        }
        
        // Container de paths sempre fica visível se houver imagens (existentes ou novas)
        if (container && container.querySelectorAll('input[type="hidden"]').length === 0) {
            container.style.display = 'none';
        }
    };
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


