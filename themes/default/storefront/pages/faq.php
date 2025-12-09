<?php
// View específica da FAQ com accordion dinâmico
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

// Dados do carrinho
$cartTotalItems = \App\Services\CartService::getTotalItems();
$cartSubtotal = \App\Services\CartService::getSubtotal();

$title = $page['title'] ?? 'Perguntas frequentes (FAQ)';
$intro = $page['intro'] ?? '';
$faqItems = $page['items'] ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($loja['nome']) ?></title>
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
        
        /* Top Bar */
        .topbar {
            background: <?= htmlspecialchars($themeFull['color_topbar_bg']) ?>;
            color: <?= htmlspecialchars($themeFull['color_topbar_text']) ?>;
            padding: 0.5rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
        
        /* Header */
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
            flex-shrink: 0;
        }
        .header-logo img {
            max-height: 50px;
            width: auto;
        }
        .header-search {
            flex: 1;
            max-width: 500px;
        }
        .header-search form {
            display: flex;
            gap: 0.5rem;
        }
        .header-search input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .header-search button {
            padding: 0.75rem 1.5rem;
            background: var(--cor-primaria);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }
        .header-nav {
            display: none;
        }
        @media (min-width: 768px) {
            .header-nav {
                display: block;
            }
        }
        .header-menu {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
        }
        .header-menu a {
            color: inherit;
            text-decoration: none;
            font-weight: 500;
        }
        .header-menu a:hover {
            color: var(--cor-primaria);
        }
        .header-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .header-cart {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: inherit;
            text-decoration: none;
        }
        .cart-icon {
            position: relative;
        }
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--cor-secundaria);
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
        
        /* Breadcrumb */
        .breadcrumb {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        .breadcrumb a {
            color: var(--cor-primaria);
            text-decoration: none;
        }
        .breadcrumb span {
            margin: 0 0.5rem;
        }
        
        /* Page Content */
        .page-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .page-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        .page-intro {
            margin-bottom: 2rem;
            line-height: 1.6;
            color: #555;
        }
        
        /* FAQ Accordion */
        .pg-faq-accordion {
            margin-top: 2rem;
        }
        .pg-faq-item {
            border-bottom: 1px solid #eee;
        }
        .pg-faq-item:first-child {
            border-top: 1px solid #eee;
        }
        .pg-faq-question {
            width: 100%;
            text-align: left;
            background: #f8f9fa;
            border: none;
            padding: 16px 20px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
            color: #333;
        }
        .pg-faq-question:hover {
            background: #f1f3f5;
        }
        .pg-faq-question span:first-child {
            flex: 1;
        }
        .pg-faq-toggle-icon {
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--cor-primaria);
            transition: transform 0.3s;
            margin-left: 1rem;
        }
        .pg-faq-question[aria-expanded="true"] .pg-faq-toggle-icon {
            transform: rotate(45deg);
        }
        .pg-faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        }
        .pg-faq-answer:not([hidden]) {
            padding: 16px 20px;
            max-height: 2000px;
        }
        .pg-faq-answer-inner {
            line-height: 1.6;
            color: #555;
        }
        .pg-faq-answer-inner p:last-child {
            margin-bottom: 0;
        }
        .pg-faq-answer-inner ul,
        .pg-faq-answer-inner ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }
        .pg-faq-answer-inner a {
            color: var(--cor-primaria);
            text-decoration: none;
        }
        .pg-faq-answer-inner a:hover {
            text-decoration: underline;
        }
        
        /* Footer */
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
            color: #F7931E;
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
            color: #F7931E;
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
            color: #F7931E;
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
            color: #F7931E;
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
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }
            .header-search {
                order: 3;
                width: 100%;
                max-width: 100%;
            }
            .menu-toggle {
                display: block;
            }
            .page-content {
                padding: 1rem;
            }
            .pg-faq-question {
                font-size: 14px;
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="topbar">
        <?= htmlspecialchars($themeFull['topbar_text']) ?>
    </div>
    
    <!-- Header -->
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

    <div class="page-content">
        <nav class="breadcrumb">
            <a href="<?= $basePath ?>/">Home</a> <span>&gt;</span> <?= htmlspecialchars($title) ?>
        </nav>
        <h1 class="page-title"><?= htmlspecialchars($title) ?></h1>
        
        <?php if (!empty($intro)): ?>
            <div class="page-intro">
                <?= $intro ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($faqItems)): ?>
            <div class="pg-faq-accordion" id="pg-faq-accordion">
                <?php foreach ($faqItems as $i => $item): ?>
                    <?php
                        $qid = 'faq-item-' . (int)$i;
                        $question = trim($item['question'] ?? '');
                        $answer = trim($item['answer'] ?? '');
                        if ($question === '' && $answer === '') {
                            continue;
                        }
                    ?>
                    <div class="pg-faq-item">
                        <button class="pg-faq-question"
                                type="button"
                                data-target="#<?= $qid ?>"
                                aria-expanded="false"
                                aria-controls="<?= $qid ?>">
                            <span><?= htmlspecialchars($question, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="pg-faq-toggle-icon">+</span>
                        </button>
                        <div id="<?= $qid ?>" class="pg-faq-answer" hidden>
                            <div class="pg-faq-answer-inner">
                                <?= $answer ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Nenhuma pergunta frequente cadastrada ainda.</p>
        <?php endif; ?>
    </div>
    
    <?php
    // Footer com configuração dinâmica
    $footerConfig = \App\Services\ThemeConfig::getFooterConfig();
    $db = \App\Core\Database::getConnection();
    $tenantId = \App\Tenant\TenantContext::id();
    
    $footerCategories = [];
    if (!empty($footerConfig['sections']['categorias']['enabled'])) {
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
    }
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
        
        // FAQ Accordion
        document.addEventListener('DOMContentLoaded', function () {
            var accordion = document.getElementById('pg-faq-accordion');
            if (!accordion) return;
            
            accordion.addEventListener('click', function (event) {
                var btn = event.target.closest('.pg-faq-question');
                if (!btn) return;
                
                var targetSelector = btn.getAttribute('data-target');
                var target = targetSelector ? document.querySelector(targetSelector) : null;
                if (!target) return;
                
                var isOpen = btn.getAttribute('aria-expanded') === 'true';
                
                // Fechar todas as outras ao abrir uma
                accordion.querySelectorAll('.pg-faq-question').forEach(function (otherBtn) {
                    otherBtn.setAttribute('aria-expanded', 'false');
                });
                accordion.querySelectorAll('.pg-faq-answer').forEach(function (answerEl) {
                    answerEl.hidden = true;
                });
                
                if (!isOpen) {
                    btn.setAttribute('aria-expanded', 'true');
                    target.hidden = false;
                }
            });
        });
    </script>
</body>
</html>
