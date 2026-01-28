<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="atributo-edit-page">
    <h1 style="font-size: 2rem; margin-bottom: 2rem; color: #333;">Editar Atributo: <?= htmlspecialchars($atributo['nome']) ?></h1>

    <?php if (!empty($message)): ?>
        <div class="admin-alert admin-alert-<?= htmlspecialchars($messageType ?? 'error') ?>" style="margin-bottom: 2rem;">
            <i class="bi bi-<?= ($messageType ?? 'error') === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="attr-edit-grid">
        <div class="attr-edit-col info-section">
            <h2 class="section-title">Dados do Atributo</h2>
            <form method="POST" action="<?= $basePath ?>/admin/atributos/<?= $atributo['id'] ?>">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($atributo['nome']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($atributo['slug']) ?>" placeholder="Será gerado automaticamente se vazio">
                    <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">
                        URL amigável do atributo (gerado automaticamente se vazio)
                    </small>
                </div>

                <div class="form-group">
                    <label>Tipo Visual</label>
                    <select name="tipo">
                        <option value="select" <?= $atributo['tipo'] === 'select' ? 'selected' : '' ?>>Lista Suspensa (Dropdown) - Para tamanhos, numeração, etc.</option>
                        <option value="color" <?= $atributo['tipo'] === 'color' ? 'selected' : '' ?>>Seletor de Cor - Para cores (mostra quadrados coloridos)</option>
                        <option value="image" <?= $atributo['tipo'] === 'image' ? 'selected' : '' ?>>Seletor de Imagem - Para estampas, padrões (mostra imagens pequenas)</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">
                        <strong>Exemplos:</strong> Lista Suspensa (Tamanho), Seletor de Cor (Cor), Seletor de Imagem (Estampa)
                    </small>
                </div>

                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="ordem" value="<?= htmlspecialchars($atributo['ordem']) ?>" min="0">
                    <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">
                        Ordem de exibição (menor número aparece primeiro)
                    </small>
                </div>

                <button type="submit" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
                    <i class="bi bi-check-circle icon"></i>
                    Salvar
                </button>
            </form>
        </div>

        <div class="info-section">
            <h2 class="section-title">Termos do Atributo</h2>
            
            <?php if (!empty($termos)): ?>
                <div style="margin-bottom: 2rem;">
                    <p style="color: #666; font-size: 0.875rem; margin-bottom: 1rem;">
                        <i class="bi bi-info-circle"></i>
                        Arraste as linhas para reordenar os termos
                    </p>
                    <table class="admin-table termos-sortable" id="termos-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Nome</th>
                                <th>Visualização</th>
                                <th style="width: 120px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="termos-tbody">
                            <?php foreach ($termos as $index => $termo): ?>
                                <tr class="termo-row" draggable="true" data-termo-id="<?= (int)$termo['id'] ?>" data-ordem="<?= (int)$termo['ordem'] ?>">
                                    <td class="drag-handle" style="cursor: move; text-align: center; color: #999; user-select: none;">
                                        <i class="bi bi-grip-vertical" style="font-size: 1.2rem;"></i>
                                    </td>
                                    <td><strong><?= htmlspecialchars($termo['nome']) ?></strong></td>
                                    <td>
                                        <?php if ($atributo['tipo'] === 'color' && $termo['valor_cor']): ?>
                                            <span style="display: inline-block; width: 30px; height: 30px; background: <?= htmlspecialchars($termo['valor_cor']) ?>; border: 1px solid #ddd; border-radius: 4px; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
                                        <?php elseif ($atributo['tipo'] === 'image' && $termo['imagem']): ?>
                                            <?php
                                            $imagemUrl = $termo['imagem'];
                                            if (!str_starts_with($imagemUrl, '/') && !str_starts_with($imagemUrl, 'http')) {
                                                $imagemUrl = '/' . ltrim($imagemUrl, '/');
                                            }
                                            ?>
                                            <img src="<?= htmlspecialchars($imagemUrl) ?>" 
                                                 alt="<?= htmlspecialchars($termo['nome']) ?>" 
                                                 style="max-width: 50px; max-height: 50px; border-radius: 4px; border: 1px solid #ddd; object-fit: cover; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.875rem;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="<?= $basePath ?>/admin/atributos/<?= $atributo['id'] ?>/termos/<?= $termo['id'] ?>/excluir" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este termo?')">
                                            <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">
                                                <i class="bi bi-trash icon"></i>
                                                Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 2rem; text-align: center; background: #f8f9fa; border-radius: 8px; margin-bottom: 2rem; color: #666;">
                    <i class="bi bi-info-circle" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                    <p style="margin: 0;">Nenhum termo cadastrado ainda. Adicione termos abaixo.</p>
                </div>
            <?php endif; ?>

            <h3 style="font-size: 1.25rem; margin-bottom: 1.5rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;" id="termos-section">Adicionar Termo</h3>
            <form method="POST" action="<?= $basePath ?>/admin/atributos/<?= $atributo['id'] ?>/termos" id="form-adicionar-termo" data-atributo-id="<?= (int)$atributo['id'] ?>" data-atributo-tipo="<?= htmlspecialchars($atributo['tipo'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" id="termo-nome-input" required>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" placeholder="Será gerado automaticamente">
                </div>

                <?php if ($atributo['tipo'] === 'color'): ?>
                    <div class="form-group">
                        <label>Valor Cor (hex) *</label>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <input type="color" 
                                   id="termo_color_picker" 
                                   value="#FF0000"
                                   style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <input type="text" 
                                   name="valor_cor" 
                                   id="termo_color_text" 
                                   placeholder="#FF0000" 
                                   pattern="^#[0-9A-Fa-f]{6}$"
                                   required
                                   style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 1rem;">
                        </div>
                        <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">Código hexadecimal da cor (ex: #FF0000 para vermelho)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Swatch (imagem miniatura) - Opcional</label>
                        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <input type="text" 
                                   id="termo_swatch_path_display" 
                                   placeholder="Selecione uma imagem na biblioteca"
                                   readonly
                                   style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                            <input type="hidden" 
                                   name="imagem" 
                                   id="termo_swatch_path" 
                                   value="">
                            <button type="button" 
                                    class="js-open-media-library admin-btn admin-btn-primary" 
                                    data-media-target="#termo_swatch_path"
                                    data-folder="atributos/swatches"
                                    data-preview="#termo_swatch_preview">
                                <i class="bi bi-image icon"></i>
                                Escolher
                            </button>
                        </div>
                        <div id="termo_swatch_preview" style="margin-top: 0.75rem;"></div>
                        <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">Imagem miniatura para exibir como swatch (opcional, se não informar usa a cor hex)</small>
                    </div>
                <?php endif; ?>

                <?php if ($atributo['tipo'] === 'image'): ?>
                    <div class="form-group">
                        <label>Imagem do Termo *</label>
                        <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <input type="text" 
                                   id="termo_imagem_path_display" 
                                   placeholder="Selecione uma imagem na biblioteca"
                                   readonly
                                   style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                            <input type="hidden" 
                                   name="imagem" 
                                   id="termo_imagem_path" 
                                   value="">
                            <button type="button" 
                                    class="js-open-media-library admin-btn admin-btn-primary" 
                                    data-media-target="#termo_imagem_path"
                                    data-folder="atributos"
                                    data-preview="#termo_imagem_preview">
                                <i class="bi bi-image icon"></i>
                                Escolher
                            </button>
                        </div>
                        <div id="termo_imagem_preview" style="margin-top: 0.75rem;"></div>
                        <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">Imagem que representa este termo (ex: foto da peça na cor)</small>
                    </div>
                <?php endif; ?>


                <button type="submit" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
                    <i class="bi bi-plus-circle icon"></i>
                    Adicionar Termo
                </button>
            </form>
        </div>
    </div>

    <div style="margin-top: 2rem;">
        <a href="<?= $basePath ?>/admin/atributos" class="admin-btn admin-btn-secondary">
            <i class="bi bi-arrow-left icon"></i>
            Voltar para lista
        </a>
    </div>
</div>

<style>
.atributo-edit-page {
    max-width: 1400px;
    width: 100%;
    box-sizing: border-box;
}
.attr-edit-grid {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    align-items: flex-start;
}
.attr-edit-col {
    flex: 1 1 420px;
    min-width: 320px;
    max-width: 100%;
    box-sizing: border-box;
}
@media (max-width: 900px) {
    .attr-edit-col {
        flex: 1 1 100%;
        min-width: 100%;
    }
}
.info-section {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    width: 100%;
    box-sizing: border-box;
}
.section-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #023A8D;
    padding-bottom: 0.5rem;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}
.form-group:last-child {
    margin-bottom: 0;
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
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
    transition: border-color 0.2s;
    width: 100%;
    box-sizing: border-box;
}
.form-group input[type="text"]:focus,
.form-group input[type="number"]:focus,
.form-group input[type="date"]:focus,
.form-group input[type="url"]:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #023A8D;
    box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
}
.form-group input[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}
.terms-table-wrap {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.admin-table {
    width: 100%;
    min-width: 600px;
    border-collapse: collapse;
    background: white;
}
.admin-table thead {
    background: #f8f9fa;
}
.admin-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #555;
    border-bottom: 2px solid #ddd;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.admin-table td {
    padding: 1rem;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}
.admin-table tbody tr:hover {
    background: #f8f9fa;
}
.admin-table tbody tr:last-child td {
    border-bottom: none;
}
.termo-row {
    cursor: move;
    transition: background-color 0.2s;
}
.termo-row:hover {
    background: #f0f7ff !important;
}
.termo-row.drag-over {
    border-top: 2px solid #023A8D;
    background: #e7f3ff !important;
}
.termo-row.dragging {
    opacity: 0.5;
}
.drag-handle {
    cursor: move;
    user-select: none;
    text-align: center;
    color: #999;
}
.drag-handle:hover {
    color: #023A8D !important;
}
@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}
</style>

<script>
// Scroll automático para #termos e foco no input
(function() {
    if (window.location.hash === '#termos') {
        setTimeout(function() {
            var termosSection = document.getElementById('termos-section');
            var nomeInput = document.getElementById('termo-nome-input');
            
            if (termosSection) {
                termosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Focar no input após scroll
                setTimeout(function() {
                    if (nomeInput) {
                        nomeInput.focus();
                    }
                }, 500);
            }
        }, 100);
    }
})();

// Adicionar termo via AJAX (sem recarregar página)
(function() {
    var form = document.getElementById('form-adicionar-termo');
    if (!form) return;
    
    var tbody = document.getElementById('termos-tbody');
    var atributoId = form.getAttribute('data-atributo-id');
    var atributoTipo = form.getAttribute('data-atributo-tipo');
    var basePath = '<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>';
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = form.querySelector('button[type="submit"]');
        var originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split icon"></i> Adicionando...';
        
        var formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(function(response) {
            var contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                return response.text().then(function(text) {
                    throw new Error('Resposta não é JSON. Status: ' + response.status);
                });
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success && data.termo) {
                // Adicionar nova linha na tabela
                addTermoToTable(data.termo, atributoTipo);
                
                // Limpar formulário
                form.reset();
                
                // Resetar color picker se existir
                var colorPicker = document.getElementById('termo_color_picker');
                var colorText = document.getElementById('termo_color_text');
                if (colorPicker) colorPicker.value = '#FF0000';
                if (colorText) colorText.value = '';
                
                // Limpar previews
                var swatchPreview = document.getElementById('termo_swatch_preview');
                var imagemPreview = document.getElementById('termo_imagem_preview');
                if (swatchPreview) swatchPreview.innerHTML = '';
                if (imagemPreview) imagemPreview.innerHTML = '';
                
                // Limpar campos de display
                var swatchDisplay = document.getElementById('termo_swatch_path_display');
                var imagemDisplay = document.getElementById('termo_imagem_path_display');
                if (swatchDisplay) swatchDisplay.value = '';
                if (imagemDisplay) imagemDisplay.value = '';
                
                // Focar no campo nome novamente
                var nomeInput = document.getElementById('termo-nome-input');
                if (nomeInput) nomeInput.focus();
                
                // Mostrar mensagem de sucesso (temporária)
                showTemporaryMessage('Termo adicionado com sucesso!', 'success');
            } else {
                throw new Error(data.message || 'Erro ao adicionar termo');
            }
        })
        .catch(function(error) {
            console.error('Erro:', error);
            showTemporaryMessage('Erro ao adicionar termo: ' + error.message, 'error');
        })
        .finally(function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
    
    function addTermoToTable(termo, tipo) {
        if (!tbody) {
            // Se não existe tbody, criar a tabela
            var termosSection = document.getElementById('termos-section');
            if (!termosSection) return;
            
            var tableWrap = document.createElement('div');
            tableWrap.className = 'terms-table-wrap';
            tableWrap.style.cssText = 'margin-bottom: 2rem;';
            
            var infoP = document.createElement('p');
            infoP.style.cssText = 'color: #666; font-size: 0.875rem; margin-bottom: 1rem;';
            infoP.innerHTML = '<i class="bi bi-info-circle"></i> Arraste as linhas para reordenar os termos';
            tableWrap.appendChild(infoP);
            
            var table = document.createElement('table');
            table.className = 'admin-table termos-sortable';
            table.id = 'termos-table';
            table.style.cssText = 'width: 100%;';
            table.innerHTML = '<thead><tr><th style="width: 40px;"></th><th>Nome</th><th>Visualização</th><th style="width: 120px;">Ações</th></tr></thead><tbody id="termos-tbody"></tbody>';
            tableWrap.appendChild(table);
            
            termosSection.parentNode.insertBefore(tableWrap, termosSection.nextSibling);
            tbody = document.getElementById('termos-tbody');
        }
        
        var row = document.createElement('tr');
        row.className = 'termo-row';
        row.setAttribute('draggable', 'true');
        row.setAttribute('data-termo-id', termo.id);
        row.setAttribute('data-ordem', termo.ordem || 0);
        
        var visualizacao = '<span style="color: #999; font-size: 0.875rem;">-</span>';
        if (tipo === 'color' && termo.valor_cor) {
            visualizacao = '<span style="display: inline-block; width: 30px; height: 30px; background: ' + 
                          escapeHtml(termo.valor_cor) + 
                          '; border: 1px solid #ddd; border-radius: 4px; vertical-align: middle; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>';
        } else if (tipo === 'image' && termo.imagem) {
            var imgUrl = termo.imagem;
            if (!imgUrl.startsWith('/') && !imgUrl.startsWith('http')) {
                imgUrl = '/' + imgUrl;
            }
            visualizacao = '<img src="' + escapeHtml(imgUrl) + 
                         '" alt="' + escapeHtml(termo.nome) + 
                         '" style="max-width: 50px; max-height: 50px; border-radius: 4px; border: 1px solid #ddd; object-fit: cover; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        }
        
        row.innerHTML = 
            '<td class="drag-handle" style="cursor: move; text-align: center; color: #999; user-select: none;"><i class="bi bi-grip-vertical" style="font-size: 1.2rem;"></i></td>' +
            '<td><strong>' + escapeHtml(termo.nome) + '</strong></td>' +
            '<td>' + visualizacao + '</td>' +
            '<td><form method="POST" action="' + basePath + '/admin/atributos/' + atributoId + '/termos/' + termo.id + '/excluir" style="display: inline;" onsubmit="return confirm(\'Tem certeza que deseja excluir este termo?\')"><button type="submit" class="admin-btn admin-btn-sm admin-btn-danger"><i class="bi bi-trash icon"></i> Excluir</button></form></td>';
        
        tbody.appendChild(row);
        
        // Reanexar event listeners de drag para a nova linha
        if (window.attachDragListenersToRow) {
            window.attachDragListenersToRow(row);
        }
    }
    
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    function showTemporaryMessage(message, type) {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'admin-alert admin-alert-' + type;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease-out;';
        alertDiv.innerHTML = '<i class="bi bi-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + ' icon"></i><span>' + escapeHtml(message) + '</span>';
        document.body.appendChild(alertDiv);
        
        setTimeout(function() {
            alertDiv.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(function() {
                alertDiv.remove();
            }, 300);
        }, 3000);
    }
})();

// Sincronizar color picker com campo de texto
(function() {
    var colorPicker = document.getElementById('termo_color_picker');
    var colorText = document.getElementById('termo_color_text');
    
    if (colorPicker && colorText) {
        // Color picker -> Text
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
        });
        
        // Text -> Color picker
        colorText.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorPicker.value = this.value.toUpperCase();
            }
        });
    }
})();

// Drag and Drop para reordenar termos
(function() {
    var tbody = document.getElementById('termos-tbody');
    if (!tbody) return;
    
    var draggedRow = null;
    var reorderUrl = '<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin/atributos/<?= (int)$atributo['id'] ?>/termos/reordenar';
    
    // Adicionar event listeners para drag
    function attachDragListeners() {
        var rows = tbody.querySelectorAll('.termo-row');
        rows.forEach(function(row) {
            row.addEventListener('dragstart', function(e) {
                draggedRow = this;
                this.style.opacity = '0.5';
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', this.innerHTML);
            });
            
            row.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
                this.classList.remove('dragging');
                var allRows = tbody.querySelectorAll('.termo-row');
                allRows.forEach(function(r) {
                    r.classList.remove('drag-over');
                });
            });
            
            row.addEventListener('dragover', function(e) {
                if (e.preventDefault) {
                    e.preventDefault();
                }
                e.dataTransfer.dropEffect = 'move';
                if (draggedRow && draggedRow !== this) {
                    this.classList.add('drag-over');
                }
                return false;
            });
            
            row.addEventListener('dragenter', function(e) {
                if (draggedRow && draggedRow !== this) {
                    this.classList.add('drag-over');
                }
            });
            
            row.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });
            
            row.addEventListener('drop', function(e) {
                if (e.stopPropagation) {
                    e.stopPropagation();
                }
                
                if (draggedRow && draggedRow !== this) {
                    var allRows = Array.from(tbody.querySelectorAll('.termo-row'));
                    var draggedIndex = allRows.indexOf(draggedRow);
                    var targetIndex = allRows.indexOf(this);
                    
                    if (draggedIndex < targetIndex) {
                        tbody.insertBefore(draggedRow, this.nextSibling);
                    } else {
                        tbody.insertBefore(draggedRow, this);
                    }
                    
                    // Atualizar ordem no servidor
                    updateTermosOrder();
                }
                
                this.classList.remove('drag-over');
                return false;
            });
        });
    }
    
    attachDragListeners();
    
    // Função global para reanexar listeners após adicionar novo termo
    window.attachDragListenersToRow = function(row) {
        row.addEventListener('dragstart', function(e) {
            draggedRow = this;
            this.style.opacity = '0.5';
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        row.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
            this.classList.remove('dragging');
            var allRows = tbody.querySelectorAll('.termo-row');
            allRows.forEach(function(r) {
                r.classList.remove('drag-over');
            });
        });
        
        row.addEventListener('dragover', function(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            if (draggedRow && draggedRow !== this) {
                this.classList.add('drag-over');
            }
            return false;
        });
        
        row.addEventListener('dragenter', function(e) {
            if (draggedRow && draggedRow !== this) {
                this.classList.add('drag-over');
            }
        });
        
        row.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        row.addEventListener('drop', function(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            if (draggedRow && draggedRow !== this) {
                var allRows = Array.from(tbody.querySelectorAll('.termo-row'));
                var draggedIndex = allRows.indexOf(draggedRow);
                var targetIndex = allRows.indexOf(this);
                
                if (draggedIndex < targetIndex) {
                    tbody.insertBefore(draggedRow, this.nextSibling);
                } else {
                    tbody.insertBefore(draggedRow, this);
                }
                
                updateTermosOrder();
            }
            
            this.classList.remove('drag-over');
            return false;
        });
    };
    
    function updateTermosOrder() {
        var rows = tbody.querySelectorAll('.termo-row');
        var orderedTermIds = [];
        
        rows.forEach(function(row) {
            var termoId = row.getAttribute('data-termo-id');
            if (termoId) {
                orderedTermIds.push(parseInt(termoId));
            }
        });
        
        if (orderedTermIds.length === 0) {
            return;
        }
        
        // Enviar para servidor via AJAX
        fetch(reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ordered_term_ids: orderedTermIds
            })
        })
        .then(function(response) {
            // Verificar se a resposta é JSON
            var contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                return response.text().then(function(text) {
                    throw new Error('Endpoint retornou HTML ao invés de JSON. Status: ' + response.status + '. Resposta: ' + text.substring(0, 200));
                });
            }
            
            if (!response.ok) {
                return response.json().then(function(data) {
                    throw new Error(data.message || 'Erro HTTP ' + response.status);
                });
            }
            
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Atualizar visualmente a ordem
                var rows = tbody.querySelectorAll('.termo-row');
                rows.forEach(function(row, index) {
                    row.setAttribute('data-ordem', index);
                });
            } else {
                throw new Error(data.message || 'Falha ao reordenar termos');
            }
        })
        .catch(function(error) {
            console.error('Erro ao reordenar termos:', error);
            alert('Erro ao reordenar termos: ' + error.message + '\n\nA página será recarregada.');
            location.reload();
        });
    }
})();

// Atualizar preview da imagem do termo quando selecionada
(function() {
    var imagemInput = document.getElementById('termo_imagem_path');
    var imagemDisplay = document.getElementById('termo_imagem_path_display');
    var preview = document.getElementById('termo_imagem_preview');
    
    if (imagemInput) {
        imagemInput.addEventListener('change', function() {
            var url = this.value;
            
            // Atualizar campo de exibição
            if (imagemDisplay) {
                imagemDisplay.value = url;
            }
            
            // Atualizar preview
            if (preview && url) {
                var imageUrl = url;
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                preview.innerHTML = '<img src="' + imageUrl + '" alt="Preview" style="max-width: 150px; max-height: 150px; border-radius: 4px; margin-top: 0.5rem; border: 1px solid #ddd; padding: 4px;">';
            }
        });
    }
    
    // Swatch preview
    var swatchInput = document.getElementById('termo_swatch_path');
    var swatchDisplay = document.getElementById('termo_swatch_path_display');
    var swatchPreview = document.getElementById('termo_swatch_preview');
    
    if (swatchInput) {
        swatchInput.addEventListener('change', function() {
            var url = this.value;
            
            if (swatchDisplay) {
                swatchDisplay.value = url;
            }
            
            if (swatchPreview && url) {
                var imageUrl = url;
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                swatchPreview.innerHTML = '<img src="' + imageUrl + '" alt="Swatch Preview" style="max-width: 80px; max-height: 80px; border-radius: 4px; margin-top: 0.5rem; border: 1px solid #ddd; padding: 4px;">';
            }
        });
    }
})();
</script>
