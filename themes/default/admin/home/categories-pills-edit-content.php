<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="edit-pill-page">
    <div class="card">
        <form method="POST" action="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>" enctype="multipart/form-data">
            <div class="form-group">
                <label>Categoria *</label>
                <select name="categoria_id" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $pill['categoria_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Label (opcional)</label>
                <input type="text" name="label" value="<?= htmlspecialchars($pill['label'] ?? '') ?>" placeholder="Ex: Bonés">
            </div>
            <div class="form-group">
                <label for="icon_upload">Imagem da categoria (círculo)</label>
                <?php if (!empty($pill['icone_path'])): ?>
                    <div style="margin-bottom: 0.75rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">Imagem Atual</label>
                        <img src="<?= $basePath . htmlspecialchars($pill['icone_path']) ?>" 
                             alt="Ícone atual" 
                             style="max-width: 80px; max-height: 80px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px; padding: 4px; background: #f9f9f9;">
                    </div>
                <?php endif; ?>
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
                                class="js-open-media-library admin-btn admin-btn-primary"
                                data-media-target="#icone_path"
                                style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                            <i class="bi bi-image icon"></i> Escolher da biblioteca
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
                    value="<?= htmlspecialchars($pill['icone_path'] ?? '') ?>" 
                    placeholder="Selecione uma imagem na biblioteca"
                    readonly
                    style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; width: 100%; background: #f8f9fa;"
                >
                <small style="display: block; margin-top: 0.5rem; color: #666; font-size: 0.875rem;">
                    Use o botão "Escolher da biblioteca" acima ou preencha manualmente se necessário.
                </small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="ordem" value="<?= $pill['ordem'] ?>" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?= $pill['ativo'] ? 'checked' : '' ?>> Ativo
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
            <a href="<?= $basePath ?>/admin/home/categorias-pills" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<!-- Modal Biblioteca de Mídia agora é gerenciado pelo componente genérico media-picker.js -->

<style>
.edit-pill-page {
    max-width: 800px;
}
.card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 1rem;
}
.btn-primary {
    background: #F7931E;
    color: white;
}
.btn-secondary {
    background: #6c757d;
    color: white;
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

<!-- JavaScript do Media Picker já está incluído no layout do admin -->
</style>


