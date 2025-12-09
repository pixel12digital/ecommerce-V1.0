/**
 * Media Picker - Componente genérico para escolha de mídia
 * Reutilizável em Banners, Categorias em Destaque, etc.
 */

(function() {
    'use strict';

    // Estado global do modal
    var modalElement = null;
    var currentTargetInput = null;
    var currentFolder = null; // Folder atual do modal (ex: 'banners', 'category-pills')
    var selectedImageUrl = null; // URL da imagem selecionada
    var basePath = '';

    /**
     * Inicializa o Media Picker
     * Deve ser chamado após o DOM estar carregado
     */
    function init() {
        console.log('[Media Picker] Inicializando...');
        
        // Criar modal se não existir
        if (!document.getElementById('pg-media-picker-modal')) {
            createModal();
            console.log('[Media Picker] Modal criado');
        }
        modalElement = document.getElementById('pg-media-picker-modal');
        
        if (!modalElement) {
            console.error('[Media Picker] Erro: Modal não foi criado!');
            return;
        }

        // Escutar cliques em botões .js-open-media-library
        document.addEventListener('click', function(event) {
            var btn = event.target.closest('.js-open-media-library');
            if (btn) {
                console.log('[Media Picker] Botão clicado:', btn);
                event.preventDefault();
                var targetSelector = btn.getAttribute('data-media-target');
                var folder = btn.getAttribute('data-folder') || null;
                console.log('[Media Picker] Target:', targetSelector, 'Folder:', folder);
                if (targetSelector) {
                    openMediaLibrary(targetSelector, folder);
                } else {
                    console.error('[Media Picker] Erro: data-media-target não encontrado no botão');
                }
            }
        });
        
        console.log('[Media Picker] Inicialização concluída');

        // Detectar basePath
        var scripts = document.getElementsByTagName('script');
        for (var i = 0; i < scripts.length; i++) {
            if (scripts[i].src && scripts[i].src.includes('/admin/js/media-picker.js')) {
                var match = scripts[i].src.match(/(.*)\/admin\/js\/media-picker\.js/);
                if (match) {
                    basePath = match[1];
                    console.log('[Media Picker] basePath detectado do script src:', basePath);
                }
                break;
            }
        }
        // Fallback: tentar detectar do window.basePath (definido no layout)
        if (!basePath && typeof window.basePath !== 'undefined') {
            basePath = window.basePath || '';
            console.log('[Media Picker] basePath detectado do window.basePath:', basePath);
        }
        if (!basePath || basePath === 'undefined' || basePath === 'null') {
            console.warn('[Media Picker] basePath não detectado ou inválido, usando vazio');
            basePath = '';
        }
        console.log('[Media Picker] basePath final:', basePath, '(tipo:', typeof basePath, ')');
    }

    /**
     * Cria o modal HTML se não existir
     */
    function createModal() {
        var modalHTML = `
            <div class="pg-modal-overlay" id="pg-media-picker-modal" style="display: none;">
                <div class="pg-modal-dialog" style="max-width: 900px; width: 90%; max-height: 90vh;">
                    <div class="pg-modal-content" style="display: flex; flex-direction: column; height: 100%; max-height: 90vh;">
                        <div class="pg-modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                            <h5 class="pg-modal-title" style="margin: 0; font-size: 1.25rem; font-weight: 600; color: #333;">Biblioteca de Mídia</h5>
                            <button type="button" class="pg-modal-close" id="pg-media-picker-close" aria-label="Fechar" style="background: none; border: none; font-size: 1.75rem; line-height: 1; color: #666; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 4px;">&times;</button>
                        </div>
                        <div class="pg-modal-body" style="padding: 1.5rem; overflow-y: auto; flex: 1;">
                            <!-- Upload de nova imagem -->
                            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 6px; border: 2px dashed #ddd;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">Enviar imagens</label>
                                <input type="file" id="pg-media-picker-upload" name="imagens[]" multiple accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.875rem;">
                                <small style="display: block; margin-top: 0.25rem; color: #666; font-size: 0.75rem;">Você pode selecionar múltiplas imagens de uma vez (Ctrl+clique ou Shift+clique)</small>
                                <button type="button" id="pg-media-picker-upload-btn" class="admin-btn admin-btn-primary" style="margin-top: 0.75rem; padding: 0.5rem 1rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                    <i class="bi bi-upload icon"></i> Enviar
                                </button>
                                <div id="pg-media-picker-upload-status" style="margin-top: 0.5rem; font-size: 0.875rem; display: none;"></div>
                            </div>
                            
                            <div id="pg-media-picker-loading" class="pg-midia-loading" style="text-align: center; padding: 2rem; color: #666;">
                                Carregando imagens...
                            </div>
                            <div id="pg-media-picker-erro" class="pg-midia-erro" style="padding: 1rem; background: #fee; border: 1px solid #fcc; border-radius: 4px; color: #c33; margin-bottom: 1rem; display: none;"></div>
                            <div id="pg-media-picker-grid" class="pg-midia-grid" style="display: none; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;"></div>
                        </div>
                        <div class="pg-modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                            <button type="button" class="btn btn-secondary" id="pg-media-picker-cancel" style="padding: 0.5rem 1rem; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                <i class="bi bi-x-circle icon"></i> Cancelar
                            </button>
                            <div style="display: flex; gap: 0.75rem;">
                                <button type="button" class="btn btn-primary" id="pg-media-picker-use-selected" style="padding: 0.5rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; opacity: 0.5; pointer-events: none;" disabled>
                                    <i class="bi bi-check-circle icon"></i> Usar imagem selecionada
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * Abre o modal da biblioteca de mídia
     * @param {string} targetSelector Seletor do input que será preenchido (ex: '#imagem_desktop')
     * @param {string} folder Pasta opcional para filtrar (ex: 'banners', 'category-pills')
     */
    function openMediaLibrary(targetSelector, folder) {
        console.log('[Media Picker] openMediaLibrary chamado:', targetSelector, folder);
        var targetInput = document.querySelector(targetSelector);
        if (!targetInput) {
            console.error('[Media Picker] Input não encontrado:', targetSelector);
            return;
        }
        
        if (!modalElement) {
            console.error('[Media Picker] Modal não está disponível!');
            return;
        }

        // Detectar folder se não foi passado
        if (!folder) {
            // Tentar inferir do contexto
            if (targetSelector.includes('imagem_desktop') || targetSelector.includes('imagem_mobile')) {
                folder = 'banners';
            } else if (targetSelector.includes('icone_path')) {
                folder = 'category-pills';
            }
        }

        currentFolder = folder;
        currentTargetInput = targetInput;
        modalElement.style.display = 'flex';
        loadImages(folder);
        setupEventListeners();
    }

    /**
     * Fecha o modal e limpa a seleção
     */
    function closeModal() {
        if (modalElement) {
            modalElement.style.display = 'none';
        }
        // Limpar seleção visual
        var grid = document.getElementById('pg-media-picker-grid');
        if (grid) {
            grid.querySelectorAll('.pg-midia-item').forEach(function(i) {
                i.style.borderColor = '#ddd';
                i.style.borderWidth = '2px';
                i.classList.remove('selected');
            });
        }
        // Desabilitar botão "Usar imagem selecionada"
        var useBtn = document.getElementById('pg-media-picker-use-selected');
        if (useBtn) {
            useBtn.disabled = true;
            useBtn.style.opacity = '0.5';
            useBtn.style.pointerEvents = 'none';
        }
        selectedImageUrl = null;
        currentTargetInput = null;
    }
    
    /**
     * Configura os event listeners do modal
     */
    function setupEventListeners() {
        // Fechar modal
        var closeBtn = document.getElementById('pg-media-picker-close');
        var cancelBtn = document.getElementById('pg-media-picker-cancel');
        var useSelectedBtn = document.getElementById('pg-media-picker-use-selected');

        if (closeBtn) {
            closeBtn.onclick = function() {
                selectedImageUrl = null;
                closeModal();
            };
        }
        if (cancelBtn) {
            cancelBtn.onclick = function() {
                selectedImageUrl = null;
                closeModal();
            };
        }
        if (useSelectedBtn) {
            useSelectedBtn.onclick = function() {
                if (selectedImageUrl && currentTargetInput) {
                    selectImage(selectedImageUrl);
                    selectedImageUrl = null;
                    closeModal();
                }
            };
        }

        // Fechar ao clicar no overlay
        if (modalElement) {
            modalElement.onclick = function(event) {
                if (event.target === modalElement) {
                    closeModal();
                }
            };
        }

        // Upload de imagem
        var uploadBtn = document.getElementById('pg-media-picker-upload-btn');
        var uploadInput = document.getElementById('pg-media-picker-upload');
        if (uploadBtn && uploadInput) {
            uploadBtn.onclick = function() {
                handleUpload(uploadInput);
            };
        }

        // Selecionar imagem - usar event delegation no modal para funcionar após recarregar
        // O listener é adicionado no modal, não no grid, para funcionar mesmo quando o grid é recriado
        if (modalElement) {
            // Remover listener antigo se existir (usando uma flag)
            if (modalElement._gridClickHandler) {
                modalElement.removeEventListener('click', modalElement._gridClickHandler);
            }
            
            // Criar novo handler para clique simples
            modalElement._gridClickHandler = function(event) {
                var item = event.target.closest('.pg-midia-item');
                if (item && item.dataset.url) {
                    var grid = document.getElementById('pg-media-picker-grid');
                    if (grid) {
                        // Destacar item selecionado
                        grid.querySelectorAll('.pg-midia-item').forEach(function(i) {
                            i.style.borderColor = '#ddd';
                            i.style.borderWidth = '2px';
                            i.classList.remove('selected');
                        });
                        item.style.borderColor = 'var(--pg-admin-primary, #F7931E)';
                        item.style.borderWidth = '3px';
                        item.classList.add('selected');
                        
                        // Guardar URL selecionada
                        selectedImageUrl = item.dataset.url;
                        
                        // Habilitar botão "Usar imagem selecionada"
                        var useBtn = document.getElementById('pg-media-picker-use-selected');
                        if (useBtn) {
                            useBtn.disabled = false;
                            useBtn.style.opacity = '1';
                            useBtn.style.pointerEvents = 'auto';
                        }
                    }
                }
            };
            
            // Handler para duplo clique (seleção rápida)
            modalElement._gridDoubleClickHandler = function(event) {
                var item = event.target.closest('.pg-midia-item');
                if (item && item.dataset.url) {
                    // Selecionar e usar imediatamente
                    selectedImageUrl = item.dataset.url;
                    if (currentTargetInput) {
                        selectImage(selectedImageUrl);
                        selectedImageUrl = null;
                        closeModal();
                    }
                }
            };
            
            modalElement.addEventListener('click', modalElement._gridClickHandler);
            modalElement.addEventListener('dblclick', modalElement._gridDoubleClickHandler);
        }
    }

    /**
     * Carrega as imagens da biblioteca
     * @param {string} folder Pasta opcional para filtrar (ex: 'banners', 'category-pills')
     */
    function loadImages(folder) {
        var loading = document.getElementById('pg-media-picker-loading');
        var erro = document.getElementById('pg-media-picker-erro');
        var grid = document.getElementById('pg-media-picker-grid');

        loading.style.display = 'block';
        grid.style.display = 'none';
        erro.style.display = 'none';
        erro.textContent = '';

        // Usar folder atual se não foi especificado
        var folderToUse = folder || currentFolder || null;

        // Construir URL corretamente: garantir que não tenha barras duplicadas
        var url = '/admin/midias/listar';
        if (basePath && basePath !== '') {
            // Remover barra final do basePath se existir
            var cleanBasePath = basePath.replace(/\/$/, '');
            url = cleanBasePath + url;
        }
        
        if (folderToUse) {
            url += '?folder=' + encodeURIComponent(folderToUse);
        }
        
        console.log('[Media Picker] Carregando imagens de:', url);
        console.log('[Media Picker] basePath:', basePath);
        console.log('[Media Picker] folder:', folderToUse);
        
        fetch(url)
            .then(function(response) {
                console.log('[Media Picker] Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Erro ao carregar imagens: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('[Media Picker] Dados recebidos:', data);
                console.log('[Media Picker] Tipo de dados:', typeof data);
                console.log('[Media Picker] data.success:', data.success);
                console.log('[Media Picker] data.files:', data.files);
                console.log('[Media Picker] data.count:', data.count);
                console.log('[Media Picker] Quantidade de arquivos:', data.files ? data.files.length : 0);
                
                loading.style.display = 'none';

                if (!data || typeof data !== 'object') {
                    console.error('[Media Picker] Resposta inválida:', data);
                    erro.textContent = 'Resposta inválida do servidor.';
                    erro.style.display = 'block';
                    return;
                }

                if (!data.success) {
                    erro.textContent = data.message || 'Não foi possível carregar as imagens.';
                    erro.style.display = 'block';
                    return;
                }

                grid.innerHTML = '';
                if (!data.files || !Array.isArray(data.files) || data.files.length === 0) {
                    console.log('[Media Picker] Nenhuma imagem encontrada (array vazio ou não é array)');
                    console.log('[Media Picker] data.files é array?', Array.isArray(data.files));
                    console.log('[Media Picker] data.files.length:', data.files ? data.files.length : 'undefined');
                    grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">Nenhuma imagem encontrada ainda. Use o campo acima para fazer upload.</div>';
                    grid.style.display = 'grid';
                    return;
                }

                console.log('[Media Picker] Renderizando', data.files.length, 'imagens');
                data.files.forEach(function(file) {
                    // Construir URL da imagem corretamente
                    // file.url já vem como /uploads/tenants/1/... do backend
                    var imageUrl = file.url || '';
                    if (!imageUrl.startsWith('/')) {
                        imageUrl = '/' + imageUrl;
                    }
                    // Em produção, basePath pode estar vazio (string vazia), então usar apenas a URL
                    // Em dev, basePath é /ecommerce-v1.0/public, então concatenar
                    // Mas se basePath for vazio ou undefined, a URL já está correta (começa com /)
                    var fullImageUrl = (basePath && basePath !== '') ? (basePath + imageUrl) : imageUrl;
                    console.log('[Media Picker] Imagem:', {
                        'file.url': file.url,
                        'imageUrl': imageUrl,
                        'basePath': basePath,
                        'fullImageUrl': fullImageUrl
                    });
                    
                    var item = document.createElement('div');
                    item.className = 'pg-midia-item';
                    item.dataset.url = file.url; // Guardar URL relativa original
                    item.style.cssText = 'border: 2px solid #ddd; border-radius: 8px; padding: 0.5rem; background: white; cursor: pointer; transition: all 0.2s; text-align: center;';
                    
                    var folderBadge = file.folderLabel ? '<span style="font-size: 0.7rem; color: #666; display: block; margin-top: 0.25rem;">' + escapeHtml(file.folderLabel) + '</span>' : '';
                    var fileName = file.filename || file.name || 'Sem nome';

                    item.innerHTML =
                        '<div style="width: 100%; padding-top: 100%; position: relative; overflow: hidden; border-radius: 4px; background: #f5f5f5; margin-bottom: 0.5rem;">' +
                            '<img src="' + fullImageUrl + '" alt="" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" onerror="console.error(\'[Media Picker] Erro ao carregar imagem:\', this.src); this.parentElement.parentElement.style.display=\'none\';">' +
                        '</div>' +
                        '<div class="pg-midia-item-name" style="margin-top: 0.5rem; font-size: 0.75rem; color: #666; word-break: break-word; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;" title="' + escapeHtml(fileName) + '">' + escapeHtml(fileName) + '</div>' +
                        folderBadge;

                    grid.appendChild(item);
                });

                grid.style.display = 'grid';
                console.log('[Media Picker] Grid renderizado com', data.files.length, 'itens');
            })
            .catch(function(err) {
                console.error('[Media Picker] Erro ao carregar imagens:', err);
                loading.style.display = 'none';
                erro.textContent = 'Erro ao carregar as imagens: ' + (err.message || 'Erro desconhecido');
                erro.style.display = 'block';
            });
    }

    /**
     * Seleciona uma imagem e preenche o input
     */
    function selectImage(url) {
        if (currentTargetInput) {
            currentTargetInput.value = url;
            // Trigger change event para outros scripts
            var event = new Event('change', { bubbles: true });
            currentTargetInput.dispatchEvent(event);
            
            // Mostrar preview se houver um elemento de preview
            var previewId = currentTargetInput.getAttribute('data-preview');
            if (previewId) {
                var preview = document.getElementById(previewId);
                if (preview) {
                    preview.innerHTML = '<img src="' + basePath + url + '" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 0.5rem;">';
                }
            }
        }
    }
    
    /**
     * Fecha o modal e limpa a seleção
     */
    /**
     * Faz upload de uma ou múltiplas imagens
     */
    function handleUpload(input) {
        var files = input.files;
        if (!files || files.length === 0) {
            alert('Selecione pelo menos um arquivo primeiro.');
            return;
        }

        var statusDiv = document.getElementById('pg-media-picker-upload-status');
        var uploadBtn = document.getElementById('pg-media-picker-upload-btn');
        
        statusDiv.style.display = 'block';
        var fileCount = files.length;
        statusDiv.innerHTML = '<span style="color: #666;">Enviando ' + fileCount + ' imagem(ns)...</span>';
        uploadBtn.disabled = true;

        var formData = new FormData();
        // Adicionar todos os arquivos ao FormData com nome 'imagens[]'
        for (var i = 0; i < files.length; i++) {
            formData.append('imagens[]', files[i]);
        }
        // Usar folder atual (detectado ao abrir o modal) ou padrão 'banners'
        var folderToUse = currentFolder || 'banners';
        formData.append('folder', folderToUse);

        var url = basePath + '/admin/midias/upload';
        fetch(url, {
            method: 'POST',
            body: formData
        })
            .then(function(response) {
                // Verificar se a resposta é JSON
                var contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(function(text) {
                        throw new Error('Resposta do servidor não é JSON: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    var successCount = data.uploaded && data.uploaded.length ? data.uploaded.length : fileCount;
                    var errorCount = data.errors && data.errors.length ? data.errors.length : 0;
                    
                    var message = '<span style="color: #4caf50;">✓ ';
                    if (errorCount === 0) {
                        message += successCount + ' imagem(ns) enviada(s) com sucesso!';
                    } else {
                        message += successCount + ' enviada(s), ' + errorCount + ' erro(s).';
                    }
                    message += '</span>';
                    
                    if (data.errors && data.errors.length > 0) {
                        message += '<div style="margin-top: 0.5rem; font-size: 0.75rem; color: #c33;">';
                        data.errors.forEach(function(err) {
                            message += '<div>• ' + escapeHtml(err) + '</div>';
                        });
                        message += '</div>';
                    }
                    
                    statusDiv.innerHTML = message;
                    
                    // Recarregar lista de imagens imediatamente com o mesmo folder usado no upload
                    loadImages(folderToUse);
                    // Reconfigurar event listeners após recarregar
                    setTimeout(function() {
                        setupEventListeners();
                    }, 100);
                    input.value = '';
                    // Ocultar mensagem de sucesso após 3 segundos
                    setTimeout(function() {
                        statusDiv.style.display = 'none';
                    }, 3000);
                } else {
                    var errorMsg = '<span style="color: #c33;">Erro: ' + escapeHtml(data.message || 'Falha no upload') + '</span>';
                    if (data.errors && data.errors.length > 0) {
                        errorMsg += '<div style="margin-top: 0.5rem; font-size: 0.75rem; color: #c33;">';
                        data.errors.forEach(function(err) {
                            errorMsg += '<div>• ' + escapeHtml(err) + '</div>';
                        });
                        errorMsg += '</div>';
                    }
                    statusDiv.innerHTML = errorMsg;
                }
                uploadBtn.disabled = false;
            })
            .catch(function(err) {
                var errorMsg = 'Erro ao enviar imagem(ns). ';
                if (err.message) {
                    errorMsg += escapeHtml(err.message);
                } else {
                    errorMsg += 'Tente novamente.';
                }
                statusDiv.innerHTML = '<span style="color: #c33;">' + errorMsg + '</span>';
                uploadBtn.disabled = false;
                console.error('Erro no upload:', err);
            });
    }

    /**
     * Escapa HTML para prevenir XSS
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Estilos CSS do modal (adicionar ao head se não existirem)
    function injectStyles() {
        if (document.getElementById('pg-media-picker-styles')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'pg-media-picker-styles';
        style.textContent = `
            .pg-modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            .pg-modal-dialog {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                overflow: hidden;
            }
            .pg-modal-close:hover {
                background: #f0f0f0 !important;
                color: #333 !important;
            }
            .pg-midia-item:hover {
                border-color: var(--pg-admin-primary, #F7931E) !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            }
        `;
        document.head.appendChild(style);
    }

    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            injectStyles();
            init();
        });
    } else {
        injectStyles();
        init();
    }

    // Expor função globalmente para uso direto
    window.openMediaLibrary = openMediaLibrary;

})();

