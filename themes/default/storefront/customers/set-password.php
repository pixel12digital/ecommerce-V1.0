<?php
use App\Support\StoreBranding;

$branding = StoreBranding::getBranding();
$logoUrl = $branding['logo_url'] ?? null;
$storeName = $branding['store_name'] ?? 'Loja';

$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$errors = $errors ?? [];
$token = $token ?? '';
$tokenValid = $tokenValid ?? false;
$customer = $customer ?? null;

if (empty($loja) || empty($loja['nome'])) {
    $tenant = \App\Tenant\TenantContext::tenant();
    $loja = ['nome' => $tenant['nome'] ?? 'Loja'];
}
if (empty($theme['menu_main'])) {
    $theme['menu_main'] = \App\Services\ThemeConfig::getMainMenu();
}
if (empty($theme['topbar_text'])) {
    $theme['topbar_text'] = \App\Services\ThemeConfig::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe');
}
if (empty($theme['newsletter_title'])) {
    $theme['newsletter_title'] = \App\Services\ThemeConfig::get('newsletter_title', 'Receba nossas ofertas');
}
if (empty($theme['newsletter_subtitle'])) {
    $theme['newsletter_subtitle'] = \App\Services\ThemeConfig::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas');
}
if (empty($theme['footer_phone'])) {
    $theme['footer_phone'] = \App\Services\ThemeConfig::get('footer_phone', '');
}
if (empty($theme['footer_whatsapp'])) {
    $theme['footer_whatsapp'] = \App\Services\ThemeConfig::get('footer_whatsapp', '');
}
if (empty($theme['footer_email'])) {
    $theme['footer_email'] = \App\Services\ThemeConfig::get('footer_email', '');
}
if (empty($theme['footer_address'])) {
    $theme['footer_address'] = \App\Services\ThemeConfig::get('footer_address', '');
}
if (!isset($theme['footer_cnpj']) || $theme['footer_cnpj'] === '') {
    $theme['footer_cnpj'] = \App\Services\ThemeConfig::get('footer_cnpj', '');
}
if (empty($theme['footer_social_instagram'])) {
    $theme['footer_social_instagram'] = \App\Services\ThemeConfig::get('footer_social_instagram', '');
}
if (empty($theme['footer_social_facebook'])) {
    $theme['footer_social_facebook'] = \App\Services\ThemeConfig::get('footer_social_facebook', '');
}
if (empty($theme['footer_social_youtube'])) {
    $theme['footer_social_youtube'] = \App\Services\ThemeConfig::get('footer_social_youtube', '');
}

ob_start();
?>

<div class="auth-page-wrapper">
    <div class="login-container">
        <div class="pg-store-login-brand">
            <?php if ($logoUrl): ?>
                <div class="pg-store-login-logo">
                    <img src="<?= $basePath . htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($storeName) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="pg-store-login-logo-placeholder" style="display: none;">
                        <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="pg-store-login-logo pg-store-login-logo-placeholder">
                    <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                </div>
            <?php endif; ?>
            
            <h1 class="pg-store-login-title">
                <?= htmlspecialchars($storeName) ?>
            </h1>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenValid && $customer): ?>
            <div class="set-password-header">
                <h2><i class="bi bi-shield-lock"></i> Criar Senha</h2>
                <p>Olá, <strong><?= htmlspecialchars($customer['name']) ?></strong>! Defina sua senha abaixo.</p>
            </div>

            <form method="POST" action="<?= $basePath ?>/minha-conta/criar-senha">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label for="password">Nova Senha</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Mínimo de 6 caracteres" autofocus>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar Senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="Repita a senha">
                </div>

                <button type="submit" class="btn-primary">Criar senha e entrar</button>
            </form>
        <?php else: ?>
            <div class="set-password-header">
                <h2><i class="bi bi-exclamation-triangle"></i> Link Inválido</h2>
                <p>Este link expirou ou é inválido.</p>
            </div>
        <?php endif; ?>

        <div class="login-footer">
            <p><a href="<?= $basePath ?>/minha-conta/primeiro-acesso">Solicitar novo link</a></p>
            <p><a href="<?= $basePath ?>/minha-conta/login">Fazer login</a></p>
            <p><a href="<?= $basePath ?>">← Voltar para a loja</a></p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$additionalStyles = '
    .auth-page-wrapper {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .login-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 400px;
    }
    .pg-store-login-brand {
        text-align: center;
        margin-bottom: 24px;
    }
    .pg-store-login-logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: #ffffff;
        padding: 10px 14px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        margin-bottom: 12px;
    }
    .pg-store-login-logo img {
        display: block;
        max-height: 48px;
        max-width: 210px;
        object-fit: contain;
    }
    .pg-store-login-logo-placeholder {
        width: 56px;
        height: 56px;
        background: #f4f4f4;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        color: #333333;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .pg-store-login-title {
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        color: #333333;
    }
    .set-password-header {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .set-password-header h2 {
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 0.5rem;
    }
    .set-password-header p {
        color: #666;
        font-size: 0.9rem;
        margin: 0;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #333;
    }
    .form-group input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
    }
    .form-group input:focus {
        outline: none;
        border-color: var(--pg-color-primary);
    }
    .btn-primary {
        width: 100%;
        padding: 0.75rem;
        background: var(--pg-color-primary);
        color: white;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s;
    }
    .btn-primary:hover {
        opacity: 0.9;
    }
    .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef5350;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #4caf50;
    }
    .login-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    .login-footer a {
        color: var(--pg-color-primary);
        text-decoration: none;
    }
    .login-footer a:hover {
        text-decoration: underline;
    }
';

$additionalScripts = '';
$pageTitle = 'Criar Senha – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

include __DIR__ . '/../layouts/base.php';
