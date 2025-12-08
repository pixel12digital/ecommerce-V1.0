<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="edit-pill-page">
    <div class="card">
        <form method="POST" action="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>">
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
                <label>Caminho do Ícone (opcional)</label>
                <input type="text" name="icone_path" value="<?= htmlspecialchars($pill['icone_path'] ?? '') ?>" placeholder="Ex: /images/icons/bone.png">
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
</style>


