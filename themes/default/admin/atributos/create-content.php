<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="atributo-form-page" style="max-width: 800px;">
    <div style="margin-bottom: 2rem;">
        <a href="<?= $basePath ?>/admin/atributos" style="color: #666; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.9rem;">
            <i class="bi bi-arrow-left"></i>
            Voltar para lista
        </a>
        <h1 style="font-size: 1.875rem; font-weight: 700; color: #333; margin: 0;">Criar Atributo</h1>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background: #fee; border: 1px solid #fcc; border-radius: 6px; padding: 1rem; margin-bottom: 1.5rem; color: #c33;">
            <?php if (isset($errors['_general'])): ?>
                <p style="margin: 0;"><?= htmlspecialchars($errors['_general']) ?></p>
            <?php else: ?>
                <p style="margin: 0;">Por favor, corrija os erros abaixo:</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="background: #e7f3ff; border-left: 4px solid #023A8D; border-radius: 4px; padding: 1rem; margin-bottom: 2rem;">
            <p style="margin: 0; color: #023A8D; font-size: 0.95rem; line-height: 1.6;">
                <strong>💡 Entenda a diferença:</strong><br>
                <strong>Atributo</strong> = Característica do produto (ex: Cor, Tamanho, Material)<br>
                <strong>Termos</strong> = Valores específicos do atributo (ex: Vermelho/Azul para Cor, P/M/G para Tamanho)<br>
                <small style="color: #666;">Após criar o atributo, você será redirecionado para cadastrar os termos.</small>
            </p>
        </div>
        
        <form method="POST" action="<?= $basePath ?>/admin/atributos">
            <div style="display: grid; gap: 1.5rem;">
                <div class="form-group">
                    <label style="display: block; font-weight: 600; color: #555; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        Nome *
                    </label>
                    <input type="text" 
                           name="nome" 
                           value="<?= htmlspecialchars($formData['nome'] ?? '') ?>" 
                           required
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s;"
                           onfocus="this.style.borderColor='var(--pg-admin-primary, #F7931E)'; this.style.boxShadow='0 0 0 3px rgba(247, 147, 30, 0.1)'"
                           onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
                    <?php if (isset($errors['nome'])): ?>
                        <small style="color: #dc3545; display: block; margin-top: 0.25rem;"><?= htmlspecialchars($errors['nome']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label style="display: block; font-weight: 600; color: #555; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        Slug
                    </label>
                    <input type="text" 
                           name="slug" 
                           value="<?= htmlspecialchars($formData['slug'] ?? '') ?>" 
                           placeholder="Será gerado automaticamente"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s; background: #f8f9fa;"
                           onfocus="this.style.borderColor='var(--pg-admin-primary, #F7931E)'; this.style.boxShadow='0 0 0 3px rgba(247, 147, 30, 0.1)'; this.style.background='white'"
                           onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'; this.style.background='#f8f9fa'">
                    <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">URL amigável do atributo (gerado automaticamente se vazio)</small>
                    <?php if (isset($errors['slug'])): ?>
                        <small style="color: #dc3545; display: block; margin-top: 0.25rem;"><?= htmlspecialchars($errors['slug']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label style="display: block; font-weight: 600; color: #555; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        Tipo Visual
                    </label>
                    <select name="tipo"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; background: white; cursor: pointer; transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--pg-admin-primary, #F7931E)'; this.style.boxShadow='0 0 0 3px rgba(247, 147, 30, 0.1)'"
                            onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
                        <option value="select" <?= ($formData['tipo'] ?? 'select') === 'select' ? 'selected' : '' ?>>Lista Suspensa (Dropdown) - Para tamanhos, numeração, etc.</option>
                        <option value="color" <?= ($formData['tipo'] ?? '') === 'color' ? 'selected' : '' ?>>Seletor de Cor - Para cores (mostra quadrados coloridos)</option>
                        <option value="image" <?= ($formData['tipo'] ?? '') === 'image' ? 'selected' : '' ?>>Seletor de Imagem - Para estampas, padrões (mostra imagens pequenas)</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 0.25rem; font-size: 0.875rem;">
                        <strong>Exemplos:</strong><br>
                        • <strong>Lista Suspensa:</strong> Tamanho (P, M, G), Numeração (32, 34, 36)<br>
                        • <strong>Seletor de Cor:</strong> Cor (Vermelho, Azul, Preto) - mostra quadrados coloridos<br>
                        • <strong>Seletor de Imagem:</strong> Estampa (Floral, Listras) - mostra imagens pequenas
                    </small>
                </div>

            </div>

            <div style="display: flex; gap: 0.75rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                <button type="submit" 
                        class="admin-btn admin-btn-primary" 
                        style="padding: 0.625rem 1.25rem; font-size: 0.95rem;">
                    <i class="bi bi-check-circle icon"></i>
                    Criar
                </button>
                <a href="<?= $basePath ?>/admin/atributos" 
                   class="admin-btn admin-btn-secondary" 
                   style="padding: 0.625rem 1.25rem; font-size: 0.95rem; text-decoration: none;">
                    <i class="bi bi-x-circle icon"></i>
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
