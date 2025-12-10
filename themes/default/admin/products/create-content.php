<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$message = $message ?? null;
$messageType = $messageType ?? 'success';
$categorias = $categorias ?? [];
$formData = $formData ?? [];

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

    <div style="margin-bottom: 2rem;">
        <a href="<?= $basePath ?>/admin/produtos" class="admin-btn admin-btn-secondary">
            <i class="bi bi-arrow-left icon"></i>
            Voltar para lista
        </a>
    </div>

    <form method="POST" action="<?= $basePath ?>/admin/produtos" enctype="multipart/form-data">
        <!-- Seção: Dados Gerais -->
        <div class="info-section">
            <h2 class="section-title">Dados Gerais</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($formData['nome'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($formData['slug'] ?? '') ?>" 
                           placeholder="Será gerado automaticamente se vazio">
                </div>
                
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" value="<?= htmlspecialchars($formData['sku'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="publish" <?= ($formData['status'] ?? 'draft') === 'publish' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('publish') ?>
                        </option>
                        <option value="draft" <?= ($formData['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('draft') ?>
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="exibir_no_catalogo" value="1" 
                               <?= (!isset($formData['exibir_no_catalogo']) || ($formData['exibir_no_catalogo'] ?? '1') == '1') ? 'checked' : '' ?>>
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
                           value="<?= $formData['preco_regular'] ?? '0,00' ?>" 
                           placeholder="0,00" required
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 380,00)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Preço Promocional</label>
                    <input type="text" name="preco_promocional" id="preco_promocional" 
                           value="<?= $formData['preco_promocional'] ?? '' ?>" 
                           placeholder="0,00"
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 350,00)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Data Início Promoção</label>
                    <input type="date" name="data_promocao_inicio" 
                           value="<?= $formData['data_promocao_inicio'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Data Fim Promoção</label>
                    <input type="date" name="data_promocao_fim" 
                           value="<?= $formData['data_promocao_fim'] ?? '' ?>">
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
                               <?= ($formData['gerencia_estoque'] ?? '0') == '1' ? 'checked' : '' ?>> 
                        Gerencia Estoque
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Quantidade</label>
                    <input type="number" name="quantidade_estoque" value="<?= $formData['quantidade_estoque'] ?? '0' ?>" 
                           min="0">
                </div>
                
                <div class="form-group">
                    <label>Status de Estoque *</label>
                    <select name="status_estoque" required>
                        <option value="instock" <?= ($formData['status_estoque'] ?? 'outofstock') === 'instock' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('instock') ?>
                        </option>
                        <option value="outofstock" <?= ($formData['status_estoque'] ?? 'outofstock') === 'outofstock' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('outofstock') ?>
                        </option>
                        <option value="onbackorder" <?= ($formData['status_estoque'] ?? 'outofstock') === 'onbackorder' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::stockStatusLabel('onbackorder') ?>
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="permite_pedidos_falta" value="1" 
                               <?= ($formData['permite_pedidos_falta'] ?? '0') == '1' ? 'checked' : '' ?>> 
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
                          placeholder="Breve descrição do produto"><?= htmlspecialchars($formData['descricao_curta'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Descrição Completa</label>
                <textarea name="descricao" rows="10" 
                          placeholder="Descrição detalhada do produto"><?= htmlspecialchars($formData['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Seção: Categorias -->
        <div class="info-section">
            <h2 class="section-title">Categorias</h2>
            
            <div class="form-group">
                <label>Selecione as categorias deste produto</label>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; background: #f9f9f9;">
                    <?php 
                    $categoriasSelecionadas = $formData['categorias'] ?? [];
                    foreach ($categorias as $categoria): 
                    ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;">
                            <input type="checkbox" name="categorias[]" value="<?= $categoria['id'] ?>" 
                                   <?= in_array($categoria['id'], $categoriasSelecionadas) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($categoria['nome']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($categorias)): ?>
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
                
                <div class="form-group">
                    <label>Escolher imagem de destaque</label>
                    <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                        <input type="text" 
                               id="imagem_destaque_path_display" 
                               value="<?= htmlspecialchars($formData['imagem_destaque_path'] ?? '') ?>" 
                               placeholder="Selecione uma imagem na biblioteca"
                               readonly
                               style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                        <!-- Campo hidden que será enviado no POST -->
                        <input type="hidden" 
                               name="imagem_destaque_path" 
                               id="imagem_destaque_path" 
                               value="<?= htmlspecialchars($formData['imagem_destaque_path'] ?? '') ?>">
                        <button type="button" 
                                class="js-open-media-library admin-btn admin-btn-primary" 
                                data-media-target="#imagem_destaque_path"
                                data-folder="produtos"
                                data-preview="#imagem_destaque_preview"
                                style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                            <i class="bi bi-image icon"></i> Escolher da biblioteca
                        </button>
                    </div>
                    <div id="imagem_destaque_preview" style="margin-top: 0.75rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher uma imagem da biblioteca ou faça upload abaixo.
                    </small>
                    <div style="margin-top: 1rem;">
                        <label>Ou fazer upload direto</label>
                        <input type="file" name="imagem_destaque" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            Formatos aceitos: JPG, PNG, GIF, WEBP
                        </small>
                    </div>
                </div>
            </div>

            <!-- Galeria de Imagens -->
            <div class="media-section" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.25rem; color: #555;">Galeria de Imagens</h3>
                
                <div class="form-group">
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
                    <div id="galeria_paths_container" style="display: none;"></div>
                    <!-- Container para preview das novas imagens da biblioteca -->
                    <div id="galeria_preview_container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 1rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher imagens da biblioteca ou faça upload direto abaixo.
                    </small>
                    <div style="margin-top: 1rem;">
                        <label>Ou fazer upload direto</label>
                        <input type="file" name="galeria[]" multiple accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            Você pode selecionar múltiplas imagens
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção: Vídeos do Produto -->
        <div class="info-section">
            <h2 class="section-title">Vídeos do Produto</h2>
            
            <!-- Novo vídeo -->
            <div class="new-videos-section">
                <div id="new-videos-container">
                    <div class="video-item new-video">
                        <div class="video-fields">
                            <div class="form-group">
                                <label>Título (opcional)</label>
                                <input type="text" name="novo_videos[0][titulo]" placeholder="Ex: Vídeo demonstrativo">
                            </div>
                            <div class="form-group">
                                <label>URL do Vídeo</label>
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

        <!-- Botão Salvar -->
        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                <i class="bi bi-check-circle icon"></i>
                Criar Produto
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
                <label>URL do Vídeo</label>
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
        });
    }
})();

// Atualizar preview da imagem de destaque quando selecionada
(function() {
    var imagemDestaqueInput = document.getElementById('imagem_destaque_path');
    var imagemDestaqueDisplay = document.getElementById('imagem_destaque_path_display');
    
    if (imagemDestaqueInput) {
        imagemDestaqueInput.addEventListener('change', function() {
            var url = this.value;
            
            // Atualizar campo de exibição também
            if (imagemDestaqueDisplay) {
                imagemDestaqueDisplay.value = url;
            }
            
            if (url) {
                // Atualizar preview
                var preview = document.getElementById('imagem_destaque_preview');
                if (preview) {
                    var imageUrl = url;
                    if (!imageUrl.startsWith('/')) {
                        imageUrl = '/' + imageUrl;
                    }
                    preview.innerHTML = '<img src="' + imageUrl + '" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 0.5rem; border: 1px solid #ddd; padding: 4px;">';
                }
            }
        });
    }
})();

// Processar seleção múltipla da biblioteca de mídia para galeria
(function() {
    var container = document.getElementById('galeria_paths_container');
    var previewContainer = document.getElementById('galeria_preview_container');
    
    if (container) {
        container.addEventListener('media-picker:multiple-selected', function(event) {
            var urls = event.detail.urls;
            
            // Criar inputs hidden para cada URL
            urls.forEach(function(url) {
                // Verificar se já não existe
                var existing = container.querySelector('input[value="' + url + '"]');
                if (existing) return;
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'galeria_paths[]';
                input.value = url;
                container.appendChild(input);
                
                // Adicionar preview
                var previewItem = document.createElement('div');
                previewItem.style.cssText = 'position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; aspect-ratio: 1;';
                var imageUrl = url;
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                previewItem.innerHTML = 
                    '<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover;" ' +
                    'onerror="this.parentElement.remove()">' +
                    '<button type="button" onclick="removeGalleryPreview(this, \'' + url + '\')" ' +
                    'style="position: absolute; top: 0.25rem; right: 0.25rem; background: #dc3545; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="bi bi-x"></i></button>';
                previewContainer.appendChild(previewItem);
            });
            
            // Mostrar containers se houver imagens
            if (container.querySelectorAll('input[type="hidden"]').length > 0) {
                container.style.display = 'block';
                previewContainer.style.display = 'grid';
            }
        });
    }
    
    // Função para remover preview da galeria
    window.removeGalleryPreview = function(btn, url) {
        var previewItem = btn.closest('div');
        
        // Remover input hidden correspondente
        var inputs = container.querySelectorAll('input[type="hidden"]');
        inputs.forEach(function(input) {
            if (input.value === url) {
                input.remove();
            }
        });
        
        // Remover preview
        previewItem.remove();
        
        // Esconder containers se não houver mais imagens
        if (container.querySelectorAll('input[type="hidden"]').length === 0) {
            container.style.display = 'none';
            previewContainer.style.display = 'none';
        }
    };
})();
</script>

<style>
.product-edit-page {
    max-width: 1400px;
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
.video-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
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
    margin-top: 1rem;
}
</style>

