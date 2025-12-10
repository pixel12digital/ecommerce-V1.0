<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$role = $role ?? null;
$allPermissions = $allPermissions ?? [];
$rolePermissionIds = $rolePermissionIds ?? [];
$isAdmin = $role && $role->getSlug() === 'store_admin';
?>
<div class="admin-content-header">
    <h1><i class="bi bi-shield-check icon"></i> Editar Permissões - <?= htmlspecialchars($role->getName()) ?></h1>
    <p>Gerencie quais permissões este perfil possui</p>
</div>

<?php if ($isAdmin): ?>
    <div class="admin-alert admin-alert-info">
        <i class="bi bi-info-circle icon"></i>
        <span><strong>Administrador da Loja</strong> tem todas as permissões automaticamente. Não é possível modificar.</span>
    </div>
<?php endif; ?>

<div class="admin-form-section">
    <form method="POST" action="<?= $basePath ?>/admin/usuarios/perfis/<?= $role->getId() ?>">
        <div class="admin-form-group">
            <label>Nome do Perfil</label>
            <input type="text" value="<?= htmlspecialchars($role->getName()) ?>" readonly style="background: #f5f5f5;">
        </div>

        <div class="admin-form-group">
            <label>Permissões</label>
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 1rem; max-height: 500px; overflow-y: auto;">
                <?php foreach ($allPermissions as $permission): ?>
                    <?php
                    $isChecked = in_array($permission->getId(), $rolePermissionIds);
                    ?>
                    <div style="padding: 0.75rem; border-bottom: 1px solid #eee; display: flex; align-items: start; gap: 0.75rem;">
                        <input 
                            type="checkbox" 
                            id="perm_<?= $permission->getId() ?>" 
                            name="permissions[]" 
                            value="<?= $permission->getId() ?>"
                            <?= $isChecked ? 'checked' : '' ?>
                            <?= $isAdmin ? 'disabled' : '' ?>
                            style="margin-top: 0.25rem;"
                        >
                        <label for="perm_<?= $permission->getId() ?>" style="flex: 1; cursor: pointer;">
                            <strong style="display: block; color: #333;"><?= htmlspecialchars($permission->getName()) ?></strong>
                            <?php if ($permission->getDescription()): ?>
                                <small style="color: #666; display: block; margin-top: 0.25rem;">
                                    <?= htmlspecialchars($permission->getDescription()) ?>
                                </small>
                            <?php endif; ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="admin-btn admin-btn-primary" <?= $isAdmin ? 'disabled' : '' ?>>
                <i class="bi bi-check-circle icon"></i>
                Salvar Permissões
            </button>
            <a href="<?= $basePath ?>/admin/usuarios/perfis" class="admin-btn admin-btn-secondary">
                <i class="bi bi-x-circle icon"></i>
                Cancelar
            </a>
        </div>
    </form>
</div>

