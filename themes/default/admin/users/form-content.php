<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$user = $user ?? null;
$roles = $roles ?? [];
$errors = $errors ?? [];
$formData = $formData ?? [];
$isEdit = $user !== null;
?>
<div class="admin-content-header">
    <h1><i class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> icon"></i> <?= $isEdit ? 'Editar' : 'Novo' ?> Usuário</h1>
    <p><?= $isEdit ? 'Altere as informações do usuário' : 'Adicione um novo usuário ao painel administrativo' ?></p>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-error">
        <i class="bi bi-exclamation-triangle icon"></i>
        <ul style="margin: 0; padding-left: 1.5rem;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-form-section">
    <form method="POST" action="<?= $basePath ?>/admin/usuarios<?= $isEdit ? '/' . $user['id'] : '' ?>">
        <div class="admin-form-group">
            <label for="name">Nome *</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? $user['name'] ?? '') ?>" required>
        </div>

        <div class="admin-form-group">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? $user['email'] ?? '') ?>" required>
        </div>

        <div class="admin-form-group">
            <label for="password">Senha <?= $isEdit ? '(deixe em branco para não alterar)' : '*' ?></label>
            <input type="password" id="password" name="password" <?= $isEdit ? '' : 'required' ?> minlength="6">
            <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                Mínimo de 6 caracteres
            </small>
        </div>

        <div class="admin-form-group">
            <label for="role">Perfil de Acesso *</label>
            <select id="role" name="role" required>
                <option value="">Selecione um perfil</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= htmlspecialchars($role->getSlug()) ?>" 
                            <?= ($formData['role'] ?? $user['role_slug'] ?? '') === $role->getSlug() ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role->getName()) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                O perfil define quais permissões o usuário terá no painel
            </small>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-check-circle icon"></i>
                <?= $isEdit ? 'Atualizar' : 'Criar' ?> Usuário
            </button>
            <a href="<?= $basePath ?>/admin/usuarios" class="admin-btn admin-btn-secondary">
                <i class="bi bi-x-circle icon"></i>
                Cancelar
            </a>
        </div>
    </form>
</div>

