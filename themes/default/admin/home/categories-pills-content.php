<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="categories-pills-page">
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">Categoria em destaque criada/atualizada com sucesso!</div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?php if ($_GET['error'] == '1'): ?>
                Erro: Categoria é obrigatória.
            <?php elseif ($_GET['error'] == '2'): ?>
                Erro: Categoria em destaque não encontrada.
            <?php else: ?>
                Erro ao processar. Tente novamente.
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Formulário de Adicionar -->
    <div class="card">
        <h3>Adicionar Categoria em Destaque</h3>
        <form method="POST" action="<?= $basePath ?>/admin/home/categorias-pills" enctype="multipart/form-data">
            <div class="form-group">
                <label>Categoria *</label>
                <select name="categoria_id" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Label (opcional - sobrescreve nome da categoria)</label>
                <input type="text" name="label" placeholder="Ex: Bonés">
            </div>
            <div class="form-group">
                <label for="icon_upload">Imagem da categoria (círculo)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-start;">
                    <div style="flex: 1; min-width: 200px;">
                        <input
                            type="file"
                            name="icon_upload"
                            id="icon_upload"
                            accept="image/*"
                            style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; width: 100%;"
                        >
                    </div>
                    <div>
                        <button type="button"
                                class="btn btn-outline-secondary"
                                id="btn-abrir-biblioteca-midia"
                                style="padding: 0.75rem 1.5rem; border: 1px solid #6c757d; border-radius: 4px; background: white; color: #6c757d; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                            Escolher da biblioteca
                        </button>
                    </div>
                </div>
                <small style="display: block; margin-top: 0.5rem; color: #666; font-size: 0.875rem;">
                    Formatos recomendados: JPG, PNG, WEBP. Use uma imagem quadrada (ex.: 300x300px) para melhor resultado no círculo.
                </small>
            </div>
            <div class="form-group">
                <label for="icone_path">Caminho do ícone (opcional - avançado)</label>
                <input 
                    type="text" 
                    name="icone_path" 
                    id="icone_path"
                    placeholder="Ex: /images/icons/bone.png"
                    style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; width: 100%;"
                >
                <small style="display: block; margin-top: 0.5rem; color: #666; font-size: 0.875rem;">
                    Preencha apenas se quiser usar um caminho personalizado. Caso envie um arquivo acima, o sistema preencherá automaticamente.
                </small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="ordem" value="0" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" checked> Ativo
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Adicionar</button>
        </form>
    </div>

    <!-- Lista de Categorias em Destaque -->
    <div class="card">
        <h3>Categorias em Destaque Configuradas</h3>
        <?php if (empty($pills)): ?>
            <p>Nenhuma categoria em destaque configurada ainda.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ordem</th>
                        <th>Ícone</th>
                        <th>Label</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pills as $pill): ?>
                        <tr>
                            <td><?= $pill['ordem'] ?></td>
                            <td>
                                <?php if ($pill['icone_path']): ?>
                                    <img src="<?= $basePath ?>/<?= htmlspecialchars($pill['icone_path']) ?>" 
                                         alt="Ícone" class="icon-preview">
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?></td>
                            <td><?= htmlspecialchars($pill['categoria_nome']) ?></td>
                            <td><?= $pill['ativo'] ? '<i class="bi bi-check-circle-fill" style="color: #2e7d32;"></i> Ativo' : '<i class="bi bi-x-circle-fill" style="color: #d32f2f;"></i> Inativo' ?></td>
                            <td>
                                <a href="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>/editar" 
                                   class="btn btn-secondary">Editar</a>
                                <form method="POST" 
                                      action="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>/excluir" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                    <button type="submit" class="btn btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Biblioteca de Mídia -->
<div class="pg-modal-overlay" id="modal-biblioteca-midia" style="display: none;">
    <div class="pg-modal-dialog">
        <div class="pg-modal-content">
            <div class="pg-modal-header">
                <h5 class="pg-modal-title">Biblioteca de imagens – Categorias em Destaque</h5>
                <button type="button" class="pg-modal-close" id="btn-fechar-modal-biblioteca" aria-label="Fechar">&times;</button>
            </div>
            <div class="pg-modal-body">
                <div id="midia-loading" class="pg-midia-loading">
                    Carregando imagens...
                </div>
                <div id="midia-erro" class="pg-midia-erro" style="display: none;"></div>
                <div id="midia-grid" class="pg-midia-grid" style="display: none;">
                    <!-- thumbnails serão inseridas via JS -->
                </div>
            </div>
            <div class="pg-modal-footer">
                <button type="button" class="btn btn-secondary" id="btn-fechar-modal-biblioteca-footer">Fechar</button>
            </div>
        </div>
    </div>
</div>

<style>
.categories-pills-page {
    max-width: 1200px;
}
.card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.card h3 {
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 0.5rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #555;
}
.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
.table th,
.table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}
.table th {
    background: #f8f8f8;
    font-weight: 600;
}
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}
.btn-primary {
    background: #F7931E;
    color: white;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.success-message {
    background: #4caf50;
    color: white;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.error-message {
    background: #f44336;
    color: white;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.icon-preview {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
    background: #e0e0e0;
}
.btn-outline-secondary:hover {
    background: #6c757d;
    color: white;
}

/* Modal Biblioteca de Mídia */
.pg-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.pg-modal-dialog {
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.pg-modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
}
.pg-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.pg-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}
.pg-modal-close {
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    color: #666;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
}
.pg-modal-close:hover {
    background: #f0f0f0;
    color: #333;
}
.pg-modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}
.pg-midia-loading {
    text-align: center;
    padding: 2rem;
    color: #666;
}
.pg-midia-erro {
    padding: 1rem;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 4px;
    color: #c33;
    margin-bottom: 1rem;
}
.pg-midia-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
}
.pg-midia-item {
    border: 2px solid #ddd;
    border-radius: 8px;
    padding: 0.5rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.pg-midia-item:hover {
    border-color: #F7931E;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}
.pg-midia-item img {
    width: 100%;
    height: auto;
    border-radius: 4px;
    display: block;
}
.pg-midia-item-name {
    margin-top: 0.5rem;
    font-size: 0.75rem;
    color: #666;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.pg-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    flex-shrink: 0;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btnAbrir = document.getElementById('btn-abrir-biblioteca-midia');
    var modalElement = document.getElementById('modal-biblioteca-midia');
    var grid = document.getElementById('midia-grid');
    var loading = document.getElementById('midia-loading');
    var erro = document.getElementById('midia-erro');
    var iconPathInput = document.getElementById('icone_path');
    var btnFechar = document.getElementById('btn-fechar-modal-biblioteca');
    var btnFecharFooter = document.getElementById('btn-fechar-modal-biblioteca-footer');

    if (!btnAbrir || !modalElement || !grid || !loading || !erro || !iconPathInput) {
        return;
    }

    function abrirModal() {
        modalElement.style.display = 'flex';
        carregarImagens();
    }

    function fecharModal() {
        modalElement.style.display = 'none';
    }

    function carregarImagens() {
        loading.style.display = 'block';
        grid.style.display = 'none';
        erro.style.display = 'none';
        erro.textContent = '';

        var basePath = '<?= $basePath ?>';
        var url = basePath + '/admin/home/categorias-pills/midia';

        fetch(url)
            .then(function (response) { 
                if (!response.ok) {
                    throw new Error('Erro ao carregar imagens');
                }
                return response.json(); 
            })
            .then(function (data) {
                loading.style.display = 'none';

                if (!data.success) {
                    erro.textContent = data.message || 'Não foi possível carregar as imagens.';
                    erro.style.display = 'block';
                    return;
                }

                grid.innerHTML = '';
                if (!data.files || !data.files.length) {
                    grid.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem; grid-column: 1 / -1;">Nenhuma imagem encontrada ainda. Faça um upload para começar.</p>';
                    grid.style.display = 'grid';
                    return;
                }

                data.files.forEach(function (file) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'pg-midia-item';
                    item.dataset.url = file.url;

                    var folderBadge = file.folderLabel ? '<span style="font-size: 0.7rem; color: #666; display: block; margin-top: 0.25rem;">' + file.folderLabel + '</span>' : '';

                    item.innerHTML =
                        '<div style="width: 100%; padding-top: 100%; position: relative; overflow: hidden; border-radius: 4px; background: #f5f5f5; margin-bottom: 0.5rem;">' +
                            '<img src="' + basePath + file.url + '" alt="" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">' +
                        '</div>' +
                        '<div class="pg-midia-item-name" title="' + file.name + '">' + file.name + '</div>' +
                        folderBadge;

                    grid.appendChild(item);
                });

                grid.style.display = 'grid';
            })
            .catch(function (err) {
                loading.style.display = 'none';
                erro.textContent = 'Erro ao carregar as imagens. Tente novamente.';
                erro.style.display = 'block';
                console.error('Erro ao carregar imagens:', err);
            });
    }

    btnAbrir.addEventListener('click', abrirModal);
    
    if (btnFechar) {
        btnFechar.addEventListener('click', fecharModal);
    }
    
    if (btnFecharFooter) {
        btnFecharFooter.addEventListener('click', fecharModal);
    }

    // Fechar ao clicar no overlay
    modalElement.addEventListener('click', function(event) {
        if (event.target === modalElement) {
            fecharModal();
        }
    });

    // Selecionar imagem
    grid.addEventListener('click', function (event) {
        var btn = event.target.closest('.pg-midia-item');
        if (!btn) return;

        var url = btn.dataset.url;
        if (!url) return;

        // Preenche o campo caminho do ícone com o arquivo selecionado
        iconPathInput.value = url;

        // Fecha o modal
        fecharModal();
    });
});
</script>
</style>


