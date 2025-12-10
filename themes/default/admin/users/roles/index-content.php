<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$roles = $roles ?? [];
?>
<div class="admin-content-header">
    <h1><i class="bi bi-shield-check icon"></i> Perfis de Acesso</h1>
    <p>Gerencie os perfis e permissões dos usuários do painel administrativo</p>
</div>

<div class="admin-table">
    <table>
        <thead>
            <tr>
                <th>Nome do Perfil</th>
                <th>Slug</th>
                <th>Escopo</th>
                <th style="text-align: center;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td>
                        <strong style="color: #333;"><?= htmlspecialchars($role->getName()) ?></strong>
                        <?php if ($role->getDescription()): ?>
                            <br>
                            <small style="color: #666;"><?= htmlspecialchars($role->getDescription()) ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="color: #666;">
                        <code><?= htmlspecialchars($role->getSlug()) ?></code>
                    </td>
                    <td>
                        <span class="badge badge-secondary"><?= htmlspecialchars($role->getScope()) ?></span>
                    </td>
                    <td style="text-align: center;">
                        <a href="<?= $basePath ?>/admin/usuarios/perfis/<?= $role->getId() ?>/editar" class="admin-btn admin-btn-sm admin-btn-primary">
                            <i class="bi bi-pencil icon"></i>
                            Editar Permissões
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

