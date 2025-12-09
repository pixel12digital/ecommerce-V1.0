<?php
// Inicializar variáveis padrão se não existirem (vindas do controller)
$message = $message ?? null;
$messageType = $messageType ?? null;
$errors = $errors ?? [];
$oldData = $oldData ?? [];

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

// Carregar tema completo
$themeFull = \App\Services\ThemeConfig::getFullThemeConfig();

$cartTotalItems = \App\Services\CartService::getTotalItems();
$cartSubtotal = \App\Services\CartService::getSubtotal();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - <?= htmlspecialchars($loja['nome']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        <?= \App\Support\ThemeCssHelper::generateCssVariables() ?>
        /* Compatibilidade com variáveis antigas */
        :root {
            --cor-primaria: var(--pg-color-primary);
            --cor-secundaria: var(--pg-color-secondary);
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .store-icon-primary {
            color: var(--cor-primaria);
        }
        .store-icon-muted {
            color: #666;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .topbar {
            background: <?= htmlspecialchars($themeFull['color_topbar_bg']) ?>;
            color: <?= htmlspecialchars($themeFull['color_topbar_text']) ?>;
            padding: 0.5rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
        .header {
            background: <?= htmlspecialchars($themeFull['color_header_bg']) ?>;
            color: <?= htmlspecialchars($themeFull['color_header_text']) ?>;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: nowrap;
        }
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 0 0 auto;
            white-space: nowrap;
        }
        .header-logo img {
            max-height: 50px;
            max-width: 200px;
            object-fit: contain;
        }
        .header-search {
            flex: 1 1 auto;
            min-width: 0;
            max-width: none;
        }
        .header-search form {
            display: flex;
            width: 100%;
        }
        .header-search input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
            min-width: 0;
        }
        .header-search button {
            padding: 0.75rem 1.5rem;
            background: <?= htmlspecialchars($themeFull['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 24px;
            flex: 0 0 auto;
            flex-wrap: nowrap;
            min-width: 0;
        }
        .header-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            flex-wrap: nowrap;
        }
        .header-menu a {
            color: inherit;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
            white-space: nowrap;
        }
        .header-menu a:hover {
            opacity: 0.7;
        }
        .header-icons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            flex-shrink: 0;
        }
        .header-cart {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: inherit;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
            white-space: nowrap;
            flex: 0 0 auto;
            flex-shrink: 0;
        }
        .header-cart:hover {
            background: rgba(0,0,0,0.05);
        }
        .cart-icon {
            font-size: 1.5rem;
            position: relative;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: <?= htmlspecialchars($themeFull['color_secondary']) ?>;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            padding: 0.5rem;
            flex: 0 0 auto;
        }
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: <?= htmlspecialchars($themeFull['color_header_bg']) ?>;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 1rem;
            z-index: 1000;
        }
        .mobile-menu.active {
            display: block;
        }
        .mobile-menu ul {
            list-style: none;
        }
        .mobile-menu a {
            display: block;
            padding: 0.75rem;
            color: inherit;
            text-decoration: none;
            border-bottom: 1px solid rgba(0,0,0,0.1);
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
            color: <?= htmlspecialchars($themeFull['color_primary']) ?>;
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
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .page-intro {
            line-height: 1.8;
            color: #666;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .contact-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
        }
        .contact-info h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .contact-item .icon {
            font-size: 1.5rem;
            color: var(--cor-primaria);
            flex-shrink: 0;
        }
        .contact-item-content {
            flex: 1;
        }
        .contact-item-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }
        .contact-item-value {
            color: #666;
        }
        .contact-item-value a {
            color: var(--cor-primaria);
            text-decoration: none;
        }
        .contact-item-value a:hover {
            text-decoration: underline;
        }
        .contact-form {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #eee;
        }
        .contact-form h3 {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group button {
            padding: 0.875rem 2rem;
            background: var(--cor-primaria);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .form-group button:hover {
            opacity: 0.9;
        }
        /* Footer */
        .pg-footer {
            background-color: #111111;
            color: #f5f5f5;
            margin-top: 0;
        }
        
        .pg-footer-main {
            padding: 40px 0 32px 0;
        }
        
        .pg-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        /* Desktop grande – todas as 5 colunas em uma linha */
        .pg-footer-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 32px 40px; /* vertical / horizontal */
        }
        
        /* Entre 992px e 1199px – 3 colunas por linha (evita 4+1) */
        @media (max-width: 1199.98px) {
            .pg-footer-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        
        /* Entre 768px e 991px – 2 colunas por linha */
        @media (max-width: 991.98px) {
            .pg-footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px 32px;
            }
        }
        
        /* Abaixo de 768px – 1 coluna por linha */
        @media (max-width: 767.98px) {
            .pg-footer-main {
                padding: 32px 0 24px 0;
            }
            
            .pg-footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .pg-container {
                padding: 0 1rem;
            }
        }
        
        .pg-footer-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 12px;
        }
        
        .pg-footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .pg-footer-links li + li {
            margin-top: 6px;
        }
        
        .pg-footer-links a {
            display: inline-block;
            font-size: 14px;
            color: #e0e0e0;
            text-decoration: none;
            transition: color 0.2s ease, transform 0.15s ease;
        }
        
        .pg-footer-links a:hover {
            color: var(--pg-color-secondary);
            transform: translateX(2px);
        }
        
        .pg-footer-contact {
            font-size: 14px;
        }
        
        .pg-footer-contact p,
        .pg-footer-contact span {
            margin: 0;
        }
        
        .pg-footer-contact p + p,
        .pg-footer-contact span + span,
        .pg-footer-contact .pg-footer-contact-item + .pg-footer-contact-item {
            margin-top: 8px;
        }
        
        .pg-footer-contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e0e0e0;
        }
        
        .pg-footer-contact-item .icon {
            color: var(--pg-color-secondary);
            font-size: 1rem;
        }
        
        .pg-footer-social {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        
        .pg-footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.15s ease;
        }
        
        .pg-footer-social a:hover {
            background: rgba(247, 147, 30, 0.2);
            transform: translateY(-2px);
        }
        
        .pg-footer-social a .icon {
            font-size: 1.125rem;
            color: #e0e0e0;
        }
        
        .pg-footer-social a:hover .icon {
            color: var(--pg-color-secondary);
        }
        
        .pg-footer-bottom {
            border-top: 1px solid #222222;
            background-color: #0c0c0c;
            padding: 16px 0;
            font-size: 13px;
            color: #cccccc;
        }
        
        .pg-footer-bottom-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .pg-footer-copy {
            white-space: nowrap;
        }
        
        .pg-footer-dev {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .pg-footer-dev a {
            color: var(--pg-color-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.2s ease;
        }
        
        .pg-footer-dev a:hover {
            opacity: 1;
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .pg-footer-bottom-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
                padding: 0 1rem;
            }
            
            .pg-footer-copy {
                white-space: normal;
            }
        }
        
        /* Compatibilidade */
        .footer {
            background: <?= htmlspecialchars($themeFull['color_footer_bg']) ?>;
            color: <?= htmlspecialchars($themeFull['color_footer_text']) ?>;
            margin-top: 4rem;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-column {
            /* Mantido para compatibilidade */
        }
        .footer-contact p {
            margin-bottom: 0.5rem;
        }
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
                gap: 16px;
            }
            .header-search {
                order: 3;
                flex: 1 1 100%;
                width: 100%;
            }
            .header-right {
                gap: 12px;
            }
            .header-menu {
                display: none;
            }
            .header-cart span {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
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
    </style>
</head>
<body>
    <div class="topbar">
        <?= htmlspecialchars($themeFull['topbar_text']) ?>
    </div>
    
    <header class="header">
        <div class="header-container">
            <a href="<?= $basePath ?>/" class="header-logo">
                <?php if (!empty($themeFull['logo_url'])): ?>
                    <img src="<?= $basePath . htmlspecialchars($themeFull['logo_url']) ?>" alt="<?= htmlspecialchars($loja['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <span style="display: none;"><?= htmlspecialchars($loja['nome']) ?></span>
                <?php else: ?>
                    <?= htmlspecialchars($loja['nome']) ?>
                <?php endif; ?>
            </a>
            
            <div class="header-search">
                <form method="GET" action="<?= $basePath ?>/produtos">
                    <input type="text" name="q" placeholder="Buscar produtos...">
                    <button type="submit"><i class="bi bi-search icon"></i> Buscar</button>
                </form>
            </div>
            
            <div class="header-right">
                <nav class="header-nav">
                    <ul class="header-menu">
                        <?php foreach ($themeFull['menu_main'] as $item): ?>
                            <?php if (!empty($item['enabled'])): ?>
                                <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <div class="header-icons">
                    <?php 
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $isCustomerLoggedIn = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
                    ?>
                    <?php if ($isCustomerLoggedIn): ?>
                        <a href="<?= $basePath ?>/minha-conta" class="header-cart">
                            <i class="bi bi-person-circle icon store-icon-primary"></i>
                            <span style="margin-left: 0.5rem;"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Minha Conta') ?></span>
                        </a>
                    <?php else: ?>
                        <a href="<?= $basePath ?>/minha-conta/login" class="header-cart">
                            <i class="bi bi-person icon store-icon-primary"></i>
                            <span style="margin-left: 0.5rem;">Entrar</span>
                        </a>
                    <?php endif; ?>
                    <a href="<?= $basePath ?>/carrinho" class="header-cart">
                        <div class="cart-icon">
                            <i class="bi bi-cart3 icon store-icon-primary"></i>
                            <?php if ($cartTotalItems > 0): ?>
                                <span class="cart-badge"><?= $cartTotalItems ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                
                <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="bi bi-list icon"></i></button>
            </div>
            
            <div class="mobile-menu" id="mobileMenu">
                <ul>
                    <?php foreach ($themeFull['menu_main'] as $item): ?>
                        <?php if (!empty($item['enabled'])): ?>
                            <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </header>
    
    <div class="breadcrumb">
        <div class="breadcrumb-container">
            <a href="<?= $basePath ?>/">Home</a>
            <span>></span>
            <span>Fale conosco</span>
        </div>
    </div>
    
    <div class="container">
        <div class="page-content">
            <h1 class="page-title"><?= htmlspecialchars($page['title']) ?></h1>
            
            <?php if (!empty($page['intro'])): ?>
                <div class="page-intro">
                    <?= $page['intro'] ?>
                </div>
            <?php endif; ?>
            
            <div class="contact-info">
                <h2>Dados de contato</h2>
                
                <?php if (!empty($storeContact['nome'])): ?>
                    <div class="contact-item">
                        <i class="bi bi-shop icon store-icon-primary"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Loja</div>
                            <div class="contact-item-value"><?= htmlspecialchars($storeContact['nome']) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($storeContact['email'])): ?>
                    <div class="contact-item">
                        <i class="bi bi-envelope icon store-icon-primary"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">E-mail</div>
                            <div class="contact-item-value">
                                <a href="mailto:<?= htmlspecialchars($storeContact['email']) ?>"><?= htmlspecialchars($storeContact['email']) ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($storeContact['phone'])): ?>
                    <div class="contact-item">
                        <i class="bi bi-telephone icon store-icon-primary"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Telefone</div>
                            <div class="contact-item-value">
                                <a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', $storeContact['phone'])) ?>"><?= htmlspecialchars($storeContact['phone']) ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($storeContact['whatsapp'])): ?>
                    <div class="contact-item">
                        <i class="bi bi-whatsapp icon store-icon-primary"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">WhatsApp</div>
                            <div class="contact-item-value">
                                <?php
                                $whatsappNumber = preg_replace('/[^0-9]/', '', $storeContact['whatsapp']);
                                $whatsappLink = 'https://wa.me/55' . $whatsappNumber;
                                ?>
                                <a href="<?= htmlspecialchars($whatsappLink) ?>" target="_blank"><?= htmlspecialchars($storeContact['whatsapp']) ?></a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($storeContact['address'])): ?>
                    <div class="contact-item">
                        <i class="bi bi-geo-alt icon store-icon-primary"></i>
                        <div class="contact-item-content">
                            <div class="contact-item-label">Endereço</div>
                            <div class="contact-item-value"><?= nl2br(htmlspecialchars($storeContact['address'])) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulário de Contato -->
            <div class="contact-form">
                <h3>Envie sua mensagem</h3>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'error' ?>" style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; background: <?= $messageType === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $messageType === 'success' ? '#155724' : '#721c24' ?>; border: 1px solid <?= $messageType === 'success' ? '#c3e6cb' : '#f5c6cb' ?>;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" style="padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                        <strong>Erros encontrados:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= $basePath ?>/contato">
                    <div class="form-group">
                        <label for="nome">Nome completo *</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($oldData['nome'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($oldData['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="<?= htmlspecialchars($oldData['telefone'] ?? '') ?>" placeholder="(11) 98765-4321">
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_assunto">Tipo de assunto *</label>
                        <select id="tipo_assunto" name="tipo_assunto" required>
                            <option value="">Selecione...</option>
                            <option value="duvidas_produtos" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'duvidas_produtos') ? 'selected' : '' ?>>Dúvidas sobre produtos</option>
                            <option value="pedido_andamento" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'pedido_andamento') ? 'selected' : '' ?>>Pedido em andamento</option>
                            <option value="trocas_devolucoes" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'trocas_devolucoes') ? 'selected' : '' ?>>Trocas e devoluções</option>
                            <option value="pagamento" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'pagamento') ? 'selected' : '' ?>>Problemas com pagamento</option>
                            <option value="problema_site" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'problema_site') ? 'selected' : '' ?>>Problemas no site</option>
                            <option value="outros" <?= (isset($oldData['tipo_assunto']) && $oldData['tipo_assunto'] === 'outros') ? 'selected' : '' ?>>Outros assuntos</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="numero_pedido_group" style="display: none;">
                        <label for="numero_pedido">Número do pedido *</label>
                        <input type="text" id="numero_pedido" name="numero_pedido" value="<?= htmlspecialchars($oldData['numero_pedido'] ?? '') ?>" placeholder="Ex: PED-12345">
                        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">Obrigatório para este tipo de assunto</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="mensagem">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" required><?= htmlspecialchars($oldData['mensagem'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" style="background: var(--cor-primaria); color: white; padding: 0.875rem 2rem; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">
                            <i class="bi bi-send icon"></i> Enviar mensagem
                        </button>
                    </div>
                </form>
            </div>
            
            <script>
                // Mostrar/ocultar campo de número do pedido conforme tipo de assunto
                const tipoAssuntoSelect = document.getElementById('tipo_assunto');
                const numeroPedidoGroup = document.getElementById('numero_pedido_group');
                const numeroPedidoInput = document.getElementById('numero_pedido');
                
                function toggleNumeroPedido() {
                    const tiposQueExigemPedido = ['pedido_andamento', 'trocas_devolucoes', 'pagamento'];
                    if (tiposQueExigemPedido.includes(tipoAssuntoSelect.value)) {
                        numeroPedidoGroup.style.display = 'block';
                        numeroPedidoInput.required = true;
                    } else {
                        numeroPedidoGroup.style.display = 'none';
                        numeroPedidoInput.required = false;
                        numeroPedidoInput.value = '';
                    }
                }
                
                // Verificar valor inicial (se houver valor antigo, mostrar campo)
                if (tipoAssuntoSelect && numeroPedidoGroup && numeroPedidoInput) {
                    toggleNumeroPedido();
                    tipoAssuntoSelect.addEventListener('change', toggleNumeroPedido);
                }
            </script>
        </div>
    </div>
    
    <?php
    // Footer com configuração dinâmica
    $footerConfig = \App\Services\ThemeConfig::getFooterConfig();
    $db = \App\Core\Database::getConnection();
    $tenantId = \App\Tenant\TenantContext::id();
    $stmt = $db->prepare("
        SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
        FROM home_category_pills hcp
        LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
        WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
        ORDER BY hcp.ordem ASC, hcp.id ASC
        LIMIT :limit
    ");
    $limit = $footerConfig['sections']['categorias']['limit'] ?? 6;
    $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
    $stmt->execute();
    $footerCategories = $stmt->fetchAll();
    ?>
    <footer class="pg-footer">
        <div class="pg-footer-main">
            <div class="pg-container pg-footer-grid">
                <?php if (!empty($footerConfig['sections']['ajuda']['enabled'])): ?>
                    <div class="pg-footer-col">
                        <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['ajuda']['title']) ?></h4>
                        <ul class="pg-footer-links">
                            <?php foreach ($footerConfig['sections']['ajuda']['links'] as $linkKey => $link): ?>
                                <?php if (!empty($link['enabled'])): ?>
                                    <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($footerConfig['sections']['minha_conta']['enabled'])): ?>
                    <div class="pg-footer-col">
                        <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['minha_conta']['title']) ?></h4>
                        <ul class="pg-footer-links">
                            <?php foreach ($footerConfig['sections']['minha_conta']['links'] as $linkKey => $link): ?>
                                <?php if (!empty($link['enabled'])): ?>
                                    <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($footerConfig['sections']['institucional']['enabled'])): ?>
                    <div class="pg-footer-col">
                        <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['institucional']['title']) ?></h4>
                        <ul class="pg-footer-links">
                            <?php foreach ($footerConfig['sections']['institucional']['links'] as $linkKey => $link): ?>
                                <?php if (!empty($link['enabled'])): ?>
                                    <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($footerConfig['sections']['categorias']['enabled']) && !empty($footerCategories)): ?>
                    <div class="pg-footer-col">
                        <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['categorias']['title']) ?></h4>
                        <ul class="pg-footer-links">
                            <?php foreach ($footerCategories as $category): ?>
                                <li><a href="<?= $basePath ?>/categoria/<?= htmlspecialchars($category['categoria_slug']) ?>"><?= htmlspecialchars($category['label'] ?: $category['categoria_nome']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="pg-footer-col pg-footer-contact">
                    <h4 class="pg-footer-title">Contato</h4>
                    <?php if ($themeFull['footer_phone']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-telephone icon"></i>
                            <span><?= htmlspecialchars($themeFull['footer_phone']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($themeFull['footer_whatsapp']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-whatsapp icon"></i>
                            <span><?= htmlspecialchars($themeFull['footer_whatsapp']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($themeFull['footer_email']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-envelope icon"></i>
                            <span><?= htmlspecialchars($themeFull['footer_email']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($themeFull['footer_address']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-geo-alt icon"></i>
                            <span><?= htmlspecialchars($themeFull['footer_address']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="pg-footer-social">
                        <?php if ($themeFull['footer_social_instagram']): ?>
                            <a href="<?= htmlspecialchars($themeFull['footer_social_instagram']) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram icon"></i></a>
                        <?php endif; ?>
                        <?php if ($themeFull['footer_social_facebook']): ?>
                            <a href="<?= htmlspecialchars($themeFull['footer_social_facebook']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook icon"></i></a>
                        <?php endif; ?>
                        <?php if ($themeFull['footer_social_youtube']): ?>
                            <a href="<?= htmlspecialchars($themeFull['footer_social_youtube']) ?>" target="_blank" rel="noopener"><i class="bi bi-youtube icon"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="pg-footer-bottom">
            <div class="pg-footer-bottom-inner">
                <span class="pg-footer-copy">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($loja['nome']) ?>. Todos os direitos reservados.
                </span>
                <span class="pg-footer-dev">
                    Desenvolvido por
                    <a href="https://pixel12digital.com.br" target="_blank" rel="noopener">
                        Pixel12Digital
                    </a>
                </span>
            </div>
        </div>
    </footer>
    
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('active');
        }
    </script>
</body>
</html>

