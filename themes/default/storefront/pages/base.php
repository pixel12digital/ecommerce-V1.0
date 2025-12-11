<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

// Obter dados da loja
$tenant = \App\Tenant\TenantContext::tenant();
$loja = [
    'nome' => $tenant->name,
    'slug' => $tenant->slug
];

// Carregar tema completo (incluindo topbar e footer)
$themeFull = \App\Services\ThemeConfig::getFullThemeConfig();

// Dados do carrinho
$cartTotalItems = \App\Services\CartService::getTotalItems();
$cartSubtotal = \App\Services\CartService::getSubtotal();

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
if (empty($theme['footer_social_instagram'])) {
    $theme['footer_social_instagram'] = \App\Services\ThemeConfig::get('footer_social_instagram', '');
}
if (empty($theme['footer_social_facebook'])) {
    $theme['footer_social_facebook'] = \App\Services\ThemeConfig::get('footer_social_facebook', '');
}
if (empty($theme['footer_social_youtube'])) {
    $theme['footer_social_youtube'] = \App\Services\ThemeConfig::get('footer_social_youtube', '');
}

// Mapear $themeFull para $theme para compatibilidade
$theme = array_merge($theme ?? [], [
    'color_primary' => $themeFull['color_primary'] ?? '#2E7D32',
    'color_secondary' => $themeFull['color_secondary'] ?? '#F7931E',
    'color_topbar_bg' => $themeFull['color_topbar_bg'] ?? '#1a1a1a',
    'color_topbar_text' => $themeFull['color_topbar_text'] ?? '#ffffff',
    'color_header_bg' => $themeFull['color_header_bg'] ?? '#ffffff',
    'color_header_text' => $themeFull['color_header_text'] ?? '#333333',
    'logo_url' => $themeFull['logo_url'] ?? '',
    'menu_main' => $themeFull['menu_main'] ?? [],
]);

// Capturar conteúdo principal em $content
ob_start();
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="<?= $basePath ?>/">Home</a>
        <span>></span>
        <span><?= htmlspecialchars($page['title'] ?? 'Página') ?></span>
    </div>
</div>

<!-- Conteúdo -->
<div class="container">
    <div class="page-content">
        <h1 class="page-title"><?= htmlspecialchars($page['title'] ?? 'Página') ?></h1>
        <div class="page-body">
            <?= !empty($page['content']) ? $page['content'] : '<p>Conteúdo em breve.</p>' ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS específico das páginas institucionais
$additionalStyles = '
    body {
        background: #f5f5f5;
    }
    
    .breadcrumb {
        background: white;
        padding: 1rem 2rem;
        border-bottom: 1px solid #eee;
    }
    .breadcrumb-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .breadcrumb a {
        color: var(--pg-color-primary);
        text-decoration: none;
    }
    .breadcrumb span {
        color: #666;
        margin: 0 0.5rem;
    }
    
    .container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .page-content {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid var(--pg-color-primary);
        padding-bottom: 0.75rem;
    }
    .page-body {
        line-height: 1.8;
        color: #666;
    }
    .page-body h1,
    .page-body h2,
    .page-body h3,
    .page-body h4,
    .page-body h5,
    .page-body h6 {
        color: #333;
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }
    .page-body p {
        margin-bottom: 1rem;
    }
    .page-body ul,
    .page-body ol {
        margin-left: 2rem;
        margin-bottom: 1rem;
    }
    .page-body a {
        color: var(--pg-color-primary);
        text-decoration: none;
    }
    .page-body a:hover {
        text-decoration: underline;
    }
    
    @media (max-width: 768px) {
        .container {
            padding: 0 1rem;
        }
        .page-content {
            padding: 1.5rem;
        }
        .page-title {
            font-size: 1.5rem;
        }
    }
';

// Scripts adicionais
$additionalScripts = '';

// Configurar variáveis para o layout base
$pageTitle = htmlspecialchars($page['title'] ?? 'Página') . ' – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = true; // Newsletter pode aparecer em páginas institucionais

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
