<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$errors = $errors ?? [];
$formData = $formData ?? [];

// Carregar dados necessários para o layout base
if (empty($loja) || empty($loja['nome'])) {
    $tenant = \App\Tenant\TenantContext::tenant();
    $loja = ['nome' => $tenant['nome'] ?? 'Loja'];
}

// Carregar menu_main se não estiver definido
if (empty($theme['menu_main'])) {
    $theme['menu_main'] = \App\Services\ThemeConfig::getMainMenu();
}

// Carregar configurações adicionais do tema se necessário
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

// Capturar conteúdo principal em $content
ob_start();
?>

<div class="auth-page-wrapper">
    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-person-plus"></i> Cadastro</h1>
            <p>Crie sua conta</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/registrar">
            <div class="form-group">
                <label for="name">Nome Completo *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Telefone</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>

            <div class="form-group">
                <label for="document">CPF/CNPJ</label>
                <input type="text" id="document" name="document" value="<?= htmlspecialchars($formData['document'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Senha *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: #666; font-size: 0.875rem;">Mínimo de 6 caracteres</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar Senha *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>

            <button type="submit" class="btn-primary">Cadastrar</button>
        </form>

        <div class="register-footer">
            <p>Já tem conta? <a href="<?= $basePath ?>/minha-conta/login">Faça login</a></p>
            <p><a href="<?= $basePath ?>">← Voltar para a loja</a></p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS específico da página de registro
$additionalStyles = '
    .auth-page-wrapper {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }
    .register-container {
        background: white;
        padding: 2rem;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 500px;
    }
    .register-header {
        text-align: center;
        margin-bottom: 2rem;
    }
    .register-header h1 {
        font-size: 1.5rem;
        color: #333;
        margin-bottom: 0.5rem;
    }
    .register-header p {
        color: #666;
        font-size: 0.9rem;
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
        background: var(--pg-color-primary);
        opacity: 0.9;
    }
    .alert-error {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef5350;
    }
    .register-footer {
        text-align: center;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
    .register-footer a {
        color: var(--pg-color-primary);
        text-decoration: none;
    }
    .register-footer a:hover {
        text-decoration: underline;
    }
';

// Scripts adicionais
$additionalScripts = '';

// Configurar variáveis para o layout base
$pageTitle = 'Criar Conta – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
