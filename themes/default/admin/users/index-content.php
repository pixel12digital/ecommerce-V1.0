<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$users = $users ?? [];
$message = $_SESSION['admin_message'] ?? null;
$messageType = $_SESSION['admin_message_type'] ?? 'success';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);
?>
<div class="admin-content-header">
    <h1><i class="bi bi-people icon"></i> Usuários da Loja</h1>
    <p>Gerencie os usuários que têm acesso ao painel administrativo</p>
    <a href="<?= $basePath ?>/admin/usuarios/novo" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
        <i class="bi bi-plus-circle icon"></i>
        Novo Usuário
    </a>
</div>

<?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $messageType ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<?php if (!empty($users)): ?>
    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Data de Cadastro</th>
                    <th style="text-align: center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong style="color: #333;"><?= htmlspecialchars($user['name']) ?></strong>
                        </td>
                        <td style="color: #666;">
                            <?= htmlspecialchars($user['email']) ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= $user['role_slug'] === 'store_admin' ? 'primary' : 'secondary' ?>">
                                <?= htmlspecialchars($user['role_name'] ?? 'Sem perfil') ?>
                            </span>
                        </td>
                        <td style="color: #666;">
                            <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                        </td>
                        <td style="text-align: center;">
                            <a href="<?= $basePath ?>/admin/usuarios/<?= $user['id'] ?>/editar" class="admin-btn admin-btn-sm admin-btn-secondary">
                                <i class="bi bi-pencil icon"></i>
                                Editar
                            </a>
                            <?php if ($user['id'] != ($_SESSION['store_user_id'] ?? null)): ?>
                                <form method="POST" action="<?= $basePath ?>/admin/usuarios/<?= $user['id'] ?>/excluir" style="display: inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">
                                        <i class="bi bi-trash icon"></i>
                                        Excluir
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="admin-empty-state">
        <i class="bi bi-people icon" style="font-size: 3rem; color: #ccc;"></i>
        <p>Nenhum usuário cadastrado ainda.</p>
        <a href="<?= $basePath ?>/admin/usuarios/novo" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle icon"></i>
            Criar Primeiro Usuário
        </a>
    </div>
<?php endif; ?>

