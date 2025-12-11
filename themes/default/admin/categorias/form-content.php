<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

$isEdit = !empty($categoria);
$formData = $formData ?? [];
$categoriaData = $isEdit ? $categoria : $formData;
?>

<div class="categoria-form-page">
    <div style="margin-bottom: 2rem;">
        <a href="<?= $basePath ?>/admin/categorias" class="admin-btn admin-btn-secondary">
            <i class="bi bi-arrow-left icon"></i>
            Voltar para lista
        </a>
    </div>

    <?php if ($message): ?>
        <div class="admin-alert admin-alert-<?= $messageType ?>" style="margin-bottom: 2rem;">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="card" style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 class="section-title" style="font-size: 1.5rem; font-weight: 700; color: #333; margin-bottom: 2rem; border-bottom: 2px solid #023A8D; padding-bottom: 0.75rem;">
            <?= $isEdit ? 'Editar Categoria' : 'Nova Categoria' ?>
        </h2>

        <form method="POST" action="<?= $basePath ?>/admin/categorias<?= $isEdit ? '/' . $categoria['id'] . '/editar' : '/criar' ?>">
            <!-- Nome -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="nome" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">
                    Nome da Categoria *
                </label>
                <input type="text" 
                       id="nome" 
                       name="nome" 
                       value="<?= htmlspecialchars($categoriaData['nome'] ?? '') ?>" 
                       required
                       style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;"
                       placeholder="Ex: Roupas, Eletrônicos, etc.">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Nome que será exibido na loja e no painel administrativo.
                </small>
            </div>

            <!-- Slug -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="slug" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">
                    Slug *
                </label>
                <input type="text" 
                       id="slug" 
                       name="slug" 
                       value="<?= htmlspecialchars($categoriaData['slug'] ?? '') ?>" 
                       required
                       style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; font-family: monospace;"
                       placeholder="ex: roupas-eletronicos">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    URL amigável da categoria. Deixe em branco para gerar automaticamente a partir do nome. Use apenas letras minúsculas, números e hífens.
                </small>
            </div>

            <!-- Descrição -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="descricao" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">
                    Descrição
                </label>
                <textarea id="descricao" 
                          name="descricao" 
                          rows="5"
                          style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"
                          placeholder="Descrição opcional da categoria..."><?= htmlspecialchars($categoriaData['descricao'] ?? '') ?></textarea>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Descrição opcional que pode ser exibida nas páginas da categoria.
                </small>
            </div>

            <!-- Categoria Pai -->
            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="categoria_pai_id" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555;">
                    Categoria Pai
                </label>
                <select id="categoria_pai_id" 
                        name="categoria_pai_id"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    <option value="">Nenhuma (categoria raiz)</option>
                    <?php foreach ($categoriasForSelect as $catOption): ?>
                        <option value="<?= $catOption['id'] ?>" 
                                <?= ($categoriaData['categoria_pai_id'] ?? null) == $catOption['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($catOption['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Selecione uma categoria pai para criar uma subcategoria. Deixe em branco para criar uma categoria raiz.
                    <?php if ($isEdit): ?>
                        <br><strong>Nota:</strong> A própria categoria e suas subcategorias não aparecem nesta lista para evitar loops.
                    <?php endif; ?>
                </small>
            </div>

            <!-- Botões -->
            <div style="display: flex; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="bi bi-check-circle icon"></i>
                    <?= $isEdit ? 'Salvar Alterações' : 'Criar Categoria' ?>
                </button>
                <a href="<?= $basePath ?>/admin/categorias" class="admin-btn admin-btn-secondary">
                    <i class="bi bi-x-circle icon"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.categoria-form-page {
    padding: 2rem;
}

.card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
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
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #F7931E;
    box-shadow: 0 0 0 3px rgba(247, 147, 30, 0.1);
}

.admin-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
    font-size: 1rem;
}

.admin-btn-primary {
    background: #F7931E;
    color: white;
}

.admin-btn-primary:hover {
    background: #e6851a;
}

.admin-btn-secondary {
    background: #6c757d;
    color: white;
}

.admin-btn-secondary:hover {
    background: #5a6268;
}

.admin-alert {
    padding: 1rem 1.5rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.admin-alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.admin-alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<script>
// Auto-gerar slug a partir do nome quando o campo slug estiver vazio
document.addEventListener('DOMContentLoaded', function() {
    const nomeInput = document.getElementById('nome');
    const slugInput = document.getElementById('slug');
    
    if (nomeInput && slugInput) {
        nomeInput.addEventListener('blur', function() {
            // Só gerar slug se o campo slug estiver vazio ou igual ao slug gerado anteriormente
            if (!slugInput.value || slugInput.value === generateSlug(nomeInput.value)) {
                slugInput.value = generateSlug(nomeInput.value);
            }
        });
    }
    
    function generateSlug(text) {
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remove acentos
            .replace(/[^a-z0-9\s-]/g, '') // Remove caracteres especiais
            .replace(/\s+/g, '-') // Substitui espaços por hífens
            .replace(/-+/g, '-') // Remove hífens duplicados
            .trim()
            .replace(/^-+|-+$/g, ''); // Remove hífens do início e fim
    }
});
</script>

