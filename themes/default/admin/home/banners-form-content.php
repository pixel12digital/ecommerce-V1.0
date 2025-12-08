<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$isEdit = $banner !== null;
?>

<div class="banner-form-page">
    <div class="card">
        <form method="POST" action="<?= $basePath ?>/admin/home/banners<?= $isEdit ? '/' . $banner['id'] : '/novo' ?>">
            <div class="form-group">
                <label>Tipo *</label>
                <select name="tipo" required>
                    <option value="hero" <?= $isEdit && $banner['tipo'] === 'hero' ? 'selected' : '' ?>>Hero</option>
                    <option value="portrait" <?= $isEdit && $banner['tipo'] === 'portrait' ? 'selected' : '' ?>>Retrato</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Título</label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($banner['titulo'] ?? '') ?>" placeholder="Título do banner">
            </div>
            
            <div class="form-group">
                <label>Subtítulo</label>
                <input type="text" name="subtitulo" value="<?= htmlspecialchars($banner['subtitulo'] ?? '') ?>" placeholder="Subtítulo do banner">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Label do Botão CTA</label>
                    <input type="text" name="cta_label" value="<?= htmlspecialchars($banner['cta_label'] ?? '') ?>" placeholder="Ex: Ver Agora">
                </div>
                <div class="form-group">
                    <label>URL do Botão CTA</label>
                    <input type="text" name="cta_url" value="<?= htmlspecialchars($banner['cta_url'] ?? '') ?>" placeholder="Ex: /produtos">
                </div>
            </div>
            
            <div class="form-group">
                <label>Caminho da Imagem Desktop *</label>
                <input type="text" name="imagem_desktop" value="<?= htmlspecialchars($banner['imagem_desktop'] ?? '') ?>" 
                       placeholder="Ex: /images/banners/hero1.jpg" required>
            </div>
            
            <div class="form-group">
                <label>Caminho da Imagem Mobile (opcional)</label>
                <input type="text" name="imagem_mobile" value="<?= htmlspecialchars($banner['imagem_mobile'] ?? '') ?>" 
                       placeholder="Ex: /images/banners/hero1-mobile.jpg">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Ordem</label>
                    <input type="number" name="ordem" value="<?= $banner['ordem'] ?? 0 ?>" min="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" value="1" <?= $isEdit && $banner['ativo'] ? 'checked' : '' ?>> Ativo
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Atualizar' : 'Criar' ?> Banner</button>
            <a href="<?= $basePath ?>/admin/home/banners" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>

<style>
.banner-form-page {
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


