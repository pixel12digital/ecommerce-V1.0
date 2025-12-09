<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') !== false) {
    $basePath = '/ecommerce-v1.0/public';
}
$isEdit = $banner !== null;
// Determinar tipo: se editando usa o tipo do banner, senão usa o tipoInicial passado pelo controller
$tipoAtual = $isEdit ? ($banner['tipo'] ?? 'hero') : ($tipoInicial ?? 'hero');
// Tipo é fixo - não pode ser alterado no formulário
$tipoLabel = $tipoAtual === 'hero' ? 'Carrossel principal (topo)' : 'Banners de apoio (entre seções)';
$tipoDescricao = $tipoAtual === 'hero' 
    ? 'Banner grande no topo da página, visível em desktop e celular' 
    : 'Banners menores em formato retrato para áreas laterais ou de apoio';
?>

<div class="banner-form-page">
    <div class="card">
        <form method="POST" action="<?= $basePath ?>/admin/home/banners<?= $isEdit ? '/' . $banner['id'] : '/novo' ?>">
            <!-- Tipo fixo (hidden) -->
            <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoAtual) ?>">
            
            <!-- Informação sobre o tipo (somente leitura) -->
            <div class="form-group">
                <label>Tipo de Banner</label>
                <div class="banner-type-info">
                    <strong><?= htmlspecialchars($tipoLabel) ?></strong>
                    <small><?= htmlspecialchars($tipoDescricao) ?></small>
                </div>
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
                <label>Imagem Desktop (opcional)</label>
                <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                    <input type="text" 
                           name="imagem_desktop" 
                           id="imagem_desktop" 
                           value="<?= htmlspecialchars($banner['imagem_desktop'] ?? '') ?>" 
                           placeholder="Selecione uma imagem na biblioteca"
                           readonly
                           style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                    <button type="button" 
                            class="js-open-media-library admin-btn admin-btn-primary" 
                            data-media-target="#imagem_desktop"
                            data-folder="banners"
                            style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                        <i class="bi bi-image icon"></i> Escolher da biblioteca
                    </button>
                </div>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Versão do banner para telas de computador. Se você não enviar uma imagem mobile, esta será usada também no celular.
                </small>
            </div>
            
            <div class="form-group">
                <label>Imagem Mobile (opcional)</label>
                <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                    <input type="text" 
                           name="imagem_mobile" 
                           id="imagem_mobile" 
                           value="<?= htmlspecialchars($banner['imagem_mobile'] ?? '') ?>" 
                           placeholder="Selecione uma imagem na biblioteca"
                           readonly
                           style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                    <button type="button" 
                            class="js-open-media-library admin-btn admin-btn-primary" 
                            data-media-target="#imagem_mobile"
                            data-folder="banners"
                            style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                        <i class="bi bi-image icon"></i> Escolher da biblioteca
                    </button>
                </div>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Versão do banner otimizada para celular. Recomendada para o carrossel em dispositivos móveis.
                </small>
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

<!-- Media Picker já está incluído no layout do admin -->

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
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.radio-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.radio-option:hover {
    border-color: var(--pg-admin-primary, #F7931E);
    background: #fff9f0;
}
.radio-option input[type="radio"] {
    margin-top: 0.25rem;
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    cursor: pointer;
}
.radio-option input[type="radio"]:checked + .radio-content {
    color: var(--pg-admin-primary, #F7931E);
}
.radio-content {
    flex: 1;
}
.radio-content strong {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 1rem;
    color: #333;
}
.radio-content small {
    display: block;
    color: #666;
    font-size: 0.875rem;
    line-height: 1.4;
}
.radio-option input[type="radio"]:checked ~ .radio-content strong,
.radio-option:has(input[type="radio"]:checked) .radio-content strong {
    color: var(--pg-admin-primary, #F7931E);
    font-weight: 600;
}
.radio-option:has(input[type="radio"]:checked) {
    border-color: var(--pg-admin-primary, #F7931E);
    background: #fff9f0;
}
.banner-type-info {
    padding: 1rem;
    background: #f8f9fa;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
}
.banner-type-info strong {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 1rem;
    color: var(--pg-admin-primary, #F7931E);
    font-weight: 600;
}
.banner-type-info small {
    display: block;
    color: #666;
    font-size: 0.875rem;
    line-height: 1.4;
}
</style>


