<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($loja['nome']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --cor-primaria: <?= htmlspecialchars($theme['color_primary']) ?>;
            --cor-secundaria: <?= htmlspecialchars($theme['color_secondary']) ?>;
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        /* Loja (vitrine) – ícones seguem as cores do Tema */
        .store-icon-primary {
            color: var(--cor-primaria);
        }
        .store-icon-muted {
            color: #666;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #333;
        }
        
        /* Top Bar */
        .topbar {
            background: <?= htmlspecialchars($theme['color_topbar_bg']) ?>;
            color: <?= htmlspecialchars($theme['color_topbar_text']) ?>;
            padding: 0.5rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
        
        /* Header */
        .header {
            background: <?= htmlspecialchars($theme['color_header_bg']) ?>;
            color: <?= htmlspecialchars($theme['color_header_text']) ?>;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: inherit;
        }
        .header-search {
            flex: 1;
            max-width: 500px;
        }
        .header-search form {
            display: flex;
        }
        .header-search input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
        }
        .header-search button {
            padding: 0.75rem 1.5rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 600;
        }
        .header-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }
        .header-menu a {
            color: inherit;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.2s;
        }
        .header-menu a:hover {
            opacity: 0.7;
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
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
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
        .cart-info {
            display: flex;
            flex-direction: column;
            font-size: 0.875rem;
        }
        .cart-count {
            font-weight: 600;
        }
        .cart-total {
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
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
        }
        .menu-toggle .icon {
            font-size: 1.5rem;
        }
        .mobile-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: <?= htmlspecialchars($theme['color_header_bg']) ?>;
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
        
        /* Fase 10 - Ajustes layout storefront */
        /* Faixa de Categorias */
        .categories-bar {
            background: #f8f8f8;
            padding: 1.25rem 0;
            border-bottom: 1px solid #eee;
        }
        .categories-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .categories-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border-radius: 50px;
            white-space: nowrap;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        .categories-toggle .icon {
            color: white;
            cursor: pointer;
        }
        .categories-scroll {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            flex: 1;
            padding: 0.5rem 0;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: <?= htmlspecialchars($theme['color_primary']) ?> #f0f0f0;
        }
        .categories-scroll::-webkit-scrollbar {
            height: 6px;
        }
        .categories-scroll::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 3px;
        }
        .categories-scroll::-webkit-scrollbar-thumb {
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            border-radius: 3px;
        }
        .category-chip {
            padding: 0.5rem 1.25rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 50px;
            white-space: nowrap;
            text-decoration: none;
            color: #333;
            font-size: 0.9rem;
            transition: all 0.2s;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .category-chip:hover {
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border-color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        .category-chip img {
            flex-shrink: 0;
        }
        
        /* Hero Slider */
        .hero {
            position: relative;
            height: 400px;
            background: linear-gradient(135deg, <?= htmlspecialchars($theme['color_primary']) ?>, <?= htmlspecialchars($theme['color_secondary']) ?>);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
        .hero-button {
            display: inline-block;
            padding: 1rem 2rem;
            background: white;
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .hero-button:hover {
            transform: translateY(-2px);
        }
        
        /* Seção Benefícios - Fase 10 */
        .benefits {
            padding: 4rem 2rem;
            background: white;
        }
        .benefits-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .benefit-card {
            text-align: center;
            padding: 1.5rem;
        }
        .benefit-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .benefit-icon .icon {
            font-size: 2.5rem;
        }
        .benefit-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .benefit-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .benefit-text {
            color: #666;
        }
        
        /* Seções de Categorias - Fase 10 */
        .category-section {
            padding: 4rem 2rem;
            background: #f8f8f8;
        }
        .category-section:nth-child(even) {
            background: white;
        }
        .section-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-title {
            font-size: 1.875rem;
            margin-bottom: 1.5rem;
            color: #333;
            font-weight: 700;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.875rem;
        }
        .product-image-placeholder {
            width: 100%;
            height: 220px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.875rem;
            border-bottom: 1px solid #e0e0e0;
        }
        .product-info {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #333;
            font-size: 0.95rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 2.8em;
        }
        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
            margin-top: auto;
        }
        .product-price-promo {
            color: <?= htmlspecialchars($theme['color_secondary']) ?>;
        }
        .product-price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9rem;
            margin-right: 0.5rem;
        }
        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        /* Banners Retrato */
        .banners-portrait {
            padding: 3rem 2rem;
            background: white;
        }
        .banners-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        .banner-portrait {
            position: relative;
            height: 400px;
            background: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .banner-link {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            padding: 0.75rem 1.5rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
        }
        
        /* Newsletter - Fase 10 */
        .newsletter {
            padding: 4rem 2rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            text-align: center;
        }
        .newsletter-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .newsletter h2 {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }
        .newsletter p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            line-height: 1.6;
        }
        .newsletter-message {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        .newsletter-message.success {
            background: rgba(76, 175, 80, 0.9);
            color: white;
        }
        .newsletter-message.error {
            background: rgba(244, 67, 54, 0.9);
            color: white;
        }
        .newsletter-message.warning {
            background: rgba(255, 193, 7, 0.9);
            color: #333;
        }
        .newsletter-form {
            display: flex;
            gap: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }
        .newsletter-form input {
            flex: 1;
            padding: 0.875rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            color: #333;
        }
        .newsletter-form input::placeholder {
            color: #999;
        }
        .newsletter-form button {
            padding: 0.875rem 2rem;
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s, transform 0.2s;
        }
        .newsletter-form button:hover {
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Footer */
        .footer {
            background: <?= htmlspecialchars($theme['color_footer_bg']) ?>;
            color: <?= htmlspecialchars($theme['color_footer_text']) ?>;
            padding: 3rem 2rem 1rem;
        }
        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .footer-column h3 {
            margin-bottom: 1rem;
            font-size: 1.125rem;
        }
        .footer-column ul {
            list-style: none;
        }
        .footer-column a {
            color: inherit;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.2s;
        }
        .footer-column a:hover {
            opacity: 1;
        }
        .footer-contact p {
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        .footer-social {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .footer-social a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-decoration: none;
            font-size: 1.25rem;
        }
        .footer-social a .icon {
            font-size: 1.25rem;
            color: rgba(255,255,255,0.9);
        }
        .footer-contact p .icon {
            margin-right: 0.5rem;
        }
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            opacity: 0.7;
        }
        
        /* Responsivo - Fase 10 */
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
            }
            .header-menu {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .header-search {
                order: 3;
                width: 100%;
                max-width: 100%;
            }
            .hero {
                min-height: 300px;
                height: 350px;
            }
            .hero-content {
                padding: 1.5rem;
            }
            .hero-content h1 {
                font-size: 1.75rem;
                margin-bottom: 0.75rem;
            }
            .hero-content p {
                font-size: 1rem;
                margin-bottom: 1.5rem;
            }
            .hero-button {
                padding: 0.875rem 2rem;
                font-size: 0.95rem;
            }
            .newsletter {
                padding: 3rem 1.5rem;
            }
            .newsletter h2 {
                font-size: 1.75rem;
            }
            .newsletter p {
                font-size: 1rem;
            }
            .newsletter-form {
                flex-direction: column;
            }
            .newsletter-form input,
            .newsletter-form button {
                width: 100%;
            }
            .categories-container {
                padding: 0 1rem;
            }
            .categories-scroll {
                gap: 0.75rem;
            }
            .category-section {
                padding: 3rem 1.5rem;
            }
            .section-title {
                font-size: 1.5rem;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
            .product-image,
            .product-image-placeholder {
                height: 180px;
            }
            .benefits {
                padding: 3rem 1.5rem;
            }
            .benefits-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    
    <!-- Top Bar -->
    <div class="topbar">
        <?= htmlspecialchars($theme['topbar_text']) ?>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?= $basePath ?>/" class="header-logo"><?= htmlspecialchars($loja['nome']) ?></a>
            
            <div class="header-search">
                <form method="GET" action="<?= $basePath ?>/produtos">
                    <input type="text" name="q" placeholder="Buscar produtos...">
                    <button type="submit"><i class="bi bi-search icon"></i> Buscar</button>
                </form>
            </div>
            
            <nav>
                <?php 
                // Fase 10 - Verificar se sessão já está ativa antes de iniciar
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $isCustomerLoggedIn = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
                ?>
                <?php if ($isCustomerLoggedIn): ?>
                    <a href="<?= $basePath ?>/minha-conta" class="header-cart" style="margin-right: 1rem;">
                        <i class="bi bi-person-circle icon store-icon-primary"></i>
                        <span style="margin-left: 0.5rem;"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Minha Conta') ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?= $basePath ?>/minha-conta/login" class="header-cart" style="margin-right: 1rem;">
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
                    <?php if ($cartTotalItems > 0): ?>
                        <div class="cart-info">
                            <span class="cart-count"><?= $cartTotalItems ?> <?= $cartTotalItems === 1 ? 'item' : 'itens' ?></span>
                            <span class="cart-total">R$ <?= number_format($cartSubtotal, 2, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                </a>
                <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="bi bi-list icon"></i></button>
                <ul class="header-menu">
                    <?php foreach ($theme['menu_main'] as $item): ?>
                        <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="mobile-menu" id="mobileMenu">
                    <ul>
                        <?php foreach ($theme['menu_main'] as $item): ?>
                            <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Faixa de Categorias -->
    <div class="categories-bar">
        <div class="categories-container">
            <div class="categories-toggle"><i class="bi bi-list icon"></i> Categorias</div>
            <div class="categories-scroll">
                <?php if (!empty($categoryPills)): ?>
                    <?php foreach ($categoryPills as $pill): ?>
                        <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($pill['categoria_slug']) ?>" class="category-chip">
                            <?php if ($pill['icone_path']): ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($pill['icone_path']) ?>" 
                                     alt="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>"
                                     style="width: 24px; height: 24px; border-radius: 50%; margin-right: 0.5rem; vertical-align: middle;">
                            <?php endif; ?>
                            <?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Hero Slider -->
    <?php if (!empty($heroBanners)): ?>
        <section class="hero" style="background-image: url('<?= $basePath ?>/<?= htmlspecialchars($heroBanners[0]['imagem_desktop']) ?>'); background-size: cover; background-position: center;">
            <div class="hero-content">
                <?php if ($heroBanners[0]['titulo']): ?>
                    <h1><?= htmlspecialchars($heroBanners[0]['titulo']) ?></h1>
                <?php endif; ?>
                <?php if ($heroBanners[0]['subtitulo']): ?>
                    <p><?= htmlspecialchars($heroBanners[0]['subtitulo']) ?></p>
                <?php endif; ?>
                <?php if ($heroBanners[0]['cta_label'] && $heroBanners[0]['cta_url']): ?>
                    <a href="<?= $basePath ?><?= htmlspecialchars($heroBanners[0]['cta_url']) ?>" class="hero-button">
                        <?= htmlspecialchars($heroBanners[0]['cta_label']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <section class="hero">
            <div class="hero-content">
                <h1>Bem-vindo à <?= htmlspecialchars($loja['nome']) ?></h1>
                <p>Os melhores produtos de golfe para você</p>
                <a href="<?= $basePath ?>/produtos" class="hero-button">VER AGORA</a>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Seção Benefícios -->
    <section class="benefits">
        <div class="benefits-container">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="bi bi-truck icon store-icon-primary"></i></div>
                <div class="benefit-title">Frete Grátis</div>
                <div class="benefit-text">Acima de R$ 299</div>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="bi bi-shield-check icon store-icon-primary"></i></div>
                <div class="benefit-title">Garantia</div>
                <div class="benefit-text">Troca garantida em até 7 dias</div>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="bi bi-lightning-charge icon store-icon-primary"></i></div>
                <div class="benefit-title">Entrega Rápida</div>
                <div class="benefit-text">Receba em até 48h</div>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="bi bi-lock icon store-icon-primary"></i></div>
                <div class="benefit-title">Compra Segura</div>
                <div class="benefit-text">Seus dados protegidos</div>
            </div>
        </div>
    </section>
    
    <!-- Seções de Categorias -->
    <?php if (!empty($sections)): ?>
        <?php foreach ($sections as $section): ?>
            <?php if (!empty($section['produtos'])): ?>
                <section class="category-section">
                    <div class="section-container">
                        <h2 class="section-title"><?= htmlspecialchars($section['titulo']) ?></h2>
                        <?php if ($section['subtitulo']): ?>
                            <p style="margin-bottom: 1.5rem; color: #666;"><?= htmlspecialchars($section['subtitulo']) ?></p>
                        <?php endif; ?>
                        <div class="products-grid">
                            <?php foreach ($section['produtos'] as $produto): ?>
                                <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-link">
                                    <div class="product-card">
                                        <?php if ($produto['imagem_principal']): ?>
                                            <img src="<?= $basePath ?>/<?= htmlspecialchars($produto['imagem_principal']['caminho_arquivo']) ?>" 
                                                 alt="<?= htmlspecialchars($produto['imagem_principal']['alt_text'] ?? $produto['nome']) ?>"
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="bi bi-image icon" style="font-size: 2rem; color: #ccc;"></i>
                                                <span style="margin-left: 0.5rem;">Sem imagem</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                                            <div class="product-price">
                                                <?php if ($produto['preco_promocional']): ?>
                                                    <span class="product-price-old">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
                                                    <span class="product-price-promo">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                                                <?php else: ?>
                                                    R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($section['categoria_slug']): ?>
                            <div style="text-align: center; margin-top: 2rem;">
                                <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($section['categoria_slug']) ?>" 
                                   style="color: <?= htmlspecialchars($theme['color_primary']) ?>; font-weight: 600; text-decoration: none;">
                                    Ver tudo <i class="bi bi-arrow-right icon"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Banners Retrato -->
    <?php if (!empty($portraitBanners)): ?>
        <section class="banners-portrait">
            <div class="banners-container">
                <?php foreach ($portraitBanners as $banner): ?>
                    <div class="banner-portrait" style="background-image: url('<?= $basePath ?>/<?= htmlspecialchars($banner['imagem_desktop']) ?>'); background-size: cover; background-position: center;">
                        <?php if ($banner['titulo']): ?>
                            <h3 style="color: white; margin-bottom: 1rem;"><?= htmlspecialchars($banner['titulo']) ?></h3>
                        <?php endif; ?>
                        <?php if ($banner['cta_label'] && $banner['cta_url']): ?>
                            <a href="<?= $basePath ?><?= htmlspecialchars($banner['cta_url']) ?>" class="banner-link">
                                <?= htmlspecialchars($banner['cta_label']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Newsletter -->
    <section class="newsletter">
        <div class="newsletter-container">
            <h2><?= htmlspecialchars($theme['newsletter_title'] ?? 'Receba nossas ofertas') ?></h2>
            <p><?= htmlspecialchars($theme['newsletter_subtitle'] ?? 'Cadastre-se e receba promoções exclusivas em seu e-mail') ?></p>
            <?php if (isset($_GET['newsletter'])): ?>
                <?php if ($_GET['newsletter'] === 'ok'): ?>
                    <div class="newsletter-message success">
                        <i class="bi bi-check-circle icon" style="font-size: 1.25rem;"></i>
                        <span>Inscrição realizada com sucesso!</span>
                    </div>
                <?php elseif ($_GET['newsletter'] === 'exists'): ?>
                    <div class="newsletter-message warning">
                        <i class="bi bi-exclamation-triangle icon" style="font-size: 1.25rem;"></i>
                        <span>Este e-mail já está cadastrado.</span>
                    </div>
                <?php elseif ($_GET['newsletter'] === 'error'): ?>
                    <div class="newsletter-message error">
                        <i class="bi bi-x-circle icon" style="font-size: 1.25rem;"></i>
                        <span>Erro ao processar inscrição. Tente novamente.</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <form method="POST" action="<?= $basePath ?>/newsletter/inscrever" class="newsletter-form">
                <input type="text" name="nome" placeholder="Seu nome" aria-label="Nome">
                <input type="email" name="email" placeholder="Seu e-mail" required aria-label="E-mail">
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>Ajuda</h3>
                <ul>
                    <li><a href="<?= $basePath ?>/frete-prazos">Frete e Prazos</a></li>
                    <li><a href="<?= $basePath ?>/trocas">Trocas e Devoluções</a></li>
                    <li><a href="<?= $basePath ?>/duvidas">Dúvidas Frequentes</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Minha Conta</h3>
                <ul>
                    <li><a href="<?= $basePath ?>/minha-conta">Entrar</a></li>
                    <li><a href="<?= $basePath ?>/minha-conta/pedidos">Meus Pedidos</a></li>
                    <li><a href="<?= $basePath ?>/minha-conta/favoritos">Favoritos</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Institucional</h3>
                <ul>
                    <li><a href="<?= $basePath ?>/sobre">Sobre Nós</a></li>
                    <li><a href="<?= $basePath ?>/contato">Contato</a></li>
                    <li><a href="<?= $basePath ?>/politica-privacidade">Política de Privacidade</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Categorias</h3>
                <ul>
                    <?php if (!empty($categoryPills)): ?>
                        <?php foreach (array_slice($categoryPills, 0, 4) as $pill): ?>
                            <li><a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($pill['categoria_slug']) ?>"><?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?></a></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="footer-column footer-contact">
                <h3>Contato</h3>
                <?php if ($theme['footer_phone']): ?>
                    <p><i class="bi bi-telephone icon store-icon-muted"></i> <?= htmlspecialchars($theme['footer_phone']) ?></p>
                <?php endif; ?>
                <?php if ($theme['footer_whatsapp']): ?>
                    <p><i class="bi bi-whatsapp icon store-icon-muted"></i> <?= htmlspecialchars($theme['footer_whatsapp']) ?></p>
                <?php endif; ?>
                <?php if ($theme['footer_email']): ?>
                    <p><i class="bi bi-envelope icon store-icon-muted"></i> <?= htmlspecialchars($theme['footer_email']) ?></p>
                <?php endif; ?>
                <?php if ($theme['footer_address']): ?>
                    <p><i class="bi bi-geo-alt icon store-icon-muted"></i> <?= htmlspecialchars($theme['footer_address']) ?></p>
                <?php endif; ?>
                <div class="footer-social">
                    <?php if ($theme['footer_social_instagram']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_instagram']) ?>" target="_blank"><i class="bi bi-instagram icon store-icon-muted"></i></a>
                    <?php endif; ?>
                    <?php if ($theme['footer_social_facebook']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_facebook']) ?>" target="_blank"><i class="bi bi-facebook icon store-icon-muted"></i></a>
                    <?php endif; ?>
                    <?php if ($theme['footer_social_youtube']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_youtube']) ?>" target="_blank"><i class="bi bi-youtube icon store-icon-muted"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($loja['nome']) ?>. Todos os direitos reservados.</p>
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
