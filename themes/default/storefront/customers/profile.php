<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$customer = $customer ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'success';
?>
<?php ob_start(); ?>
<div class="content-header">
    <h1>Dados da Conta</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/minha-conta/perfil" style="max-width: 600px;">
    <div style="margin-bottom: 1.5rem;">
        <label for="profile-name" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Nome Completo *</label>
        <input type="text" id="profile-name" name="name" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" 
               required placeholder="Seu nome completo"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="profile-email" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">E-mail</label>
        <input type="email" id="profile-email" value="<?= htmlspecialchars($customer['email'] ?? '') ?>" 
               disabled placeholder="seu@email.com"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; background: #f5f5f5; color: #666;">
        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
            <i class="bi bi-info-circle icon" style="margin-right: 0.25rem;"></i>
            O e-mail não pode ser alterado
        </small>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="profile-phone" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Telefone</label>
        <input type="tel" id="profile-phone" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" 
               placeholder="(00) 00000-0000"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="profile-document" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">CPF/CNPJ</label>
        <input type="text" id="profile-document" name="document" value="<?= htmlspecialchars($customer['document'] ?? '') ?>" 
               placeholder="000.000.000-00"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
    </div>

    <hr style="margin: 2rem 0; border: none; border-top: 2px solid #eee;">

    <h3 style="margin-bottom: 0.75rem; font-size: 1.25rem; font-weight: 700; color: #333;">Alterar Senha</h3>
    <p style="color: #666; margin-bottom: 1.5rem; font-size: 0.9rem;">Deixe em branco se não quiser alterar a senha</p>

    <div style="margin-bottom: 1.5rem;">
        <label for="profile-password" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Nova Senha</label>
        <input type="password" id="profile-password" name="password" minlength="6" 
               placeholder="Mínimo 6 caracteres"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">Mínimo de 6 caracteres</small>
    </div>

    <div style="margin-bottom: 1.5rem;">
        <label for="profile-password-confirm" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Confirmar Nova Senha</label>
        <input type="password" id="profile-password-confirm" name="password_confirm" minlength="6" 
               placeholder="Digite a senha novamente"
               style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
    </div>

    <button type="submit" style="padding: 0.875rem 2rem; background: #023A8D; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;">
        <i class="bi bi-check-circle icon"></i>
        Salvar Alterações
    </button>
</form>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>


