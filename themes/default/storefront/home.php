<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($loja['nome']) ?></title>
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
        /* Header Container - Layout em uma linha (Desktop) */
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            flex-wrap: nowrap; /* Impede quebra de linha no desktop */
        }
        
        /* Logo - Fixo à esquerda */
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: inherit;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 0 0 auto; /* Logo com largura fixa */
            white-space: nowrap;
        }
        .header-logo img {
            max-height: 50px;
            max-width: 200px;
            object-fit: contain;
        }
        
        /* Barra de Busca - Ocupa espaço flexível do centro */
        .header-search {
            flex: 1 1 auto; /* Ocupa o espaço flexível do meio */
            min-width: 0; /* Permite que o flex shrink funcione */
            max-width: none; /* Remove limite máximo */
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
            min-width: 0; /* Permite shrink */
        }
        .header-search button {
            padding: 0.75rem 1.5rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }
        
        /* Container da direita - Menu + Ícones */
        .header-right {
            display: flex;
            align-items: center;
            gap: 24px;
            flex: 0 0 auto; /* Não cresce, não encolhe */
            flex-wrap: nowrap; /* Impede quebra de linha */
            min-width: 0; /* Permite shrink se necessário */
        }
        
        /* Menu de navegação */
        .header-menu {
            display: flex;
            gap: 1.5rem;
            list-style: none;
            margin: 0;
            padding: 0;
            align-items: center;
            flex-wrap: nowrap; /* Impede quebra de linha */
        }
        .header-menu li {
            margin: 0;
            padding: 0;
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
        
        /* Container dos ícones (conta + carrinho) */
        .header-icons {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap; /* Impede quebra de linha */
            flex-shrink: 0; /* Não encolhe */
        }
        
        /* Ícones de conta e carrinho */
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
            flex-shrink: 0; /* Não encolhe */
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
        /* Botão Mobile Menu - Oculto no desktop */
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
        
        /* Nav wrapper para organização */
        .header-nav {
            display: block;
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
        
        /* Fase 17 - Faixa de Categorias estilo Ponto do Golfe */
        /* Faixa de Categorias - Estilo Ponto do Golfe */
        .pg-category-strip {
            background-color: var(--cor-primaria, <?= htmlspecialchars($theme['color_primary']) ?>);
            padding: 16px 0;
            width: 100%;
        }
        .pg-category-strip-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 16px;
        }
        /* Botão "Categorias" */
        .pg-category-main-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 0 32px;
            height: 64px;
            border-radius: 999px;
            border: none;
            background-color: #ffffff;
            color: var(--cor-primaria, <?= htmlspecialchars($theme['color_primary']) ?>);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
            flex-shrink: 0;
            text-decoration: none;
        }
        .pg-category-main-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .pg-category-main-button-icon {
            display: inline-flex;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid var(--cor-primaria, <?= htmlspecialchars($theme['color_primary']) ?>);
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .pg-category-main-button-label {
            font-weight: 600;
        }
        /* Viewport para scroll inteligente */
        .pg-category-pills-viewport {
            flex: 1;
            overflow-x: auto;
            padding-bottom: 8px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }
        .pg-category-pills-viewport::-webkit-scrollbar {
            height: 6px;
        }
        .pg-category-pills-viewport::-webkit-scrollbar-track {
            background: transparent;
        }
        .pg-category-pills-viewport::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        .pg-category-pills-viewport::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        /* Lista de categorias (scroll horizontal) - centralizado quando couber */
        .pg-category-pills-scroll {
            display: inline-flex;
            align-items: center;
            gap: 16px;
            margin: 0 auto;
            padding: 0 4px;
        }
        /* Cada categoria (pill) */
        .pg-category-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #ffffff;
            min-width: 80px;
            flex-shrink: 0;
            transition: transform 0.2s;
        }
        .pg-category-pill:hover {
            transform: translateY(-2px);
        }
        .pg-category-pill:focus {
            outline: 2px solid rgba(255, 255, 255, 0.6);
            outline-offset: 4px;
            border-radius: 4px;
        }
        .pg-category-pill-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #ffffff;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
            transition: box-shadow 0.2s;
        }
        .pg-category-pill:hover .pg-category-pill-circle,
        .pg-category-pill:focus .pg-category-pill-circle {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.6);
        }
        .pg-category-pill-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 50%;
        }
        .pg-category-pill-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 24px;
        }
        .pg-category-pill-label {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            max-width: 90px;
            line-height: 1.2;
            color: #ffffff;
            transition: text-decoration 0.2s;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }
        .pg-category-pill:hover .pg-category-pill-label,
        .pg-category-pill:focus .pg-category-pill-label {
            text-decoration: underline;
        }
        
        /* Menu de Categorias (Overlay) */
        .pg-category-menu-overlay {
            position: fixed;
            inset: 0;
            z-index: 999;
            display: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .pg-category-menu-overlay.is-visible {
            display: block;
            opacity: 1;
        }
        .pg-category-menu-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            cursor: pointer;
        }
        .pg-category-menu-panel {
            position: relative;
            max-width: 480px;
            width: calc(100% - 32px);
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            transform: translateY(-20px);
            transition: transform 0.2s ease;
        }
        .pg-category-menu-overlay.is-visible .pg-category-menu-panel {
            transform: translateY(0);
        }
        .pg-category-menu-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        .pg-category-menu-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        .pg-category-menu-close {
            border: none;
            background: transparent;
            font-size: 28px;
            cursor: pointer;
            line-height: 1;
            color: #666;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        .pg-category-menu-close:hover {
            background-color: #e0e0e0;
        }
        .pg-category-menu-body {
            max-height: 60vh;
            overflow-y: auto;
            padding: 8px 0 16px;
        }
        .pg-category-menu-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .pg-category-menu-list li + li {
            margin-top: 2px;
        }
        .pg-category-menu-link {
            display: block;
            padding: 12px 20px;
            text-decoration: none;
            color: #333;
            font-size: 15px;
            transition: background-color 0.2s;
        }
        .pg-category-menu-link:hover,
        .pg-category-menu-link:focus {
            background-color: #f3f3f3;
            outline: none;
        }
        .pg-category-menu-link:focus {
            box-shadow: inset 0 0 0 2px var(--cor-primaria, <?= htmlspecialchars($theme['color_primary']) ?>);
        }
        
        /* Hero Slider */
        .home-hero {
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .home-hero-slider {
            position: relative;
            width: 100%;
            height: 500px;
        }
        .home-hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Fallback: primeiro slide sempre visível (mesmo sem JS) */
        .home-hero-slide:first-child {
            opacity: 1;
            z-index: 1;
        }
        .home-hero-slide.active {
            opacity: 1;
            z-index: 1;
        }
        .home-hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        .home-hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            padding: 2rem;
            max-width: 800px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 8px;
        }
        .home-hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        .home-hero-content p {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            color: white;
        }
        /* Hero sem imagem (só texto) */
        .home-hero-slide-text-only {
            background: linear-gradient(135deg, var(--pg-color-primary), var(--pg-color-secondary));
        }
        .home-hero-slide-text-only .home-hero-content {
            background: transparent;
        }
        /* Hero padrão (fallback) */
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
        .pg-footer {
            background-color: #111111;
            color: #f5f5f5;
            margin-top: 0;
        }
        
        /* Parte das colunas */
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
        
        /* Títulos das colunas */
        .pg-footer-title {
            font-size: 16px;
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 12px;
        }
        
        /* Lista de links do footer */
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
        
        /* Coluna de contato */
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
        
        /* Parte inferior (copyright + desenvolvido por) */
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
        
        /* Quebra em mobile */
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
        
        /* Compatibilidade com classes antigas (manter temporariamente) */
        .footer {
            background: <?= htmlspecialchars($theme['color_footer_bg']) ?>;
            color: <?= htmlspecialchars($theme['color_footer_text']) ?>;
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
        }
        
        /* Responsivo - Mobile */
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
                gap: 16px;
            }
            .header-logo {
                flex: 0 0 auto;
            }
            .header-search {
                order: 3;
                flex: 1 1 100%;
                width: 100%;
                max-width: 100%;
            }
            .header-right {
                flex: 0 0 auto;
                gap: 12px;
            }
            .header-nav {
                display: none; /* Menu desktop oculto no mobile */
            }
            .header-menu {
                display: none; /* Menu desktop oculto no mobile */
            }
            .header-icons {
                gap: 8px;
            }
            .header-cart span {
                display: none; /* Esconde texto nos ícones no mobile */
            }
            .cart-info {
                display: none; /* Esconde info do carrinho no mobile */
            }
            .menu-toggle {
                display: block;
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
            .pg-category-strip-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .pg-category-main-button {
                width: 100%;
                justify-content: center;
            }
            .pg-category-pills-scroll {
                width: 100%;
                gap: 12px;
            }
            .pg-category-pill-circle {
                width: 64px;
                height: 64px;
            }
            .pg-category-pill-label {
                font-size: 12px;
                max-width: 72px;
            }
            .pg-category-menu-panel {
                width: calc(100% - 16px);
                margin: 20px auto;
                max-height: calc(100vh - 40px);
            }
            .pg-category-menu-body {
                max-height: calc(60vh - 60px);
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
    
    <!-- Header - Layout em uma linha (Desktop) -->
    <header class="header">
        <div class="header-container">
            <!-- Logo - Esquerda -->
            <a href="<?= $basePath ?>/" class="header-logo">
                <?php if (!empty($theme['logo_url'])): ?>
                    <img src="<?= $basePath . htmlspecialchars($theme['logo_url']) ?>" alt="<?= htmlspecialchars($loja['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <span style="display: none;"><?= htmlspecialchars($loja['nome']) ?></span>
                <?php else: ?>
                    <?= htmlspecialchars($loja['nome']) ?>
                <?php endif; ?>
            </a>
            
            <!-- Barra de Busca - Centro (flex-grow) -->
            <div class="header-search">
                <form method="GET" action="<?= $basePath ?>/produtos">
                    <input type="text" name="q" placeholder="Buscar produtos...">
                    <button type="submit"><i class="bi bi-search icon"></i> Buscar</button>
                </form>
            </div>
            
            <!-- Menu + Ícones - Direita -->
            <div class="header-right">
                <!-- Menu de Navegação -->
                <nav class="header-nav">
                    <ul class="header-menu">
                        <?php foreach ($theme['menu_main'] as $item): ?>
                            <?php if (!empty($item['enabled'])): ?>
                                <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <!-- Ícones (Conta + Carrinho) -->
                <div class="header-icons">
                    <?php 
                    // Fase 10 - Verificar se sessão já está ativa antes de iniciar
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
                        <?php if ($cartTotalItems > 0): ?>
                            <div class="cart-info">
                                <span class="cart-count"><?= $cartTotalItems ?> <?= $cartTotalItems === 1 ? 'item' : 'itens' ?></span>
                                <span class="cart-total">R$ <?= number_format($cartSubtotal, 2, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Botão Mobile Menu (oculto no desktop) -->
                <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="bi bi-list icon"></i></button>
            </div>
            
            <!-- Menu Mobile (oculto no desktop) -->
            <div class="mobile-menu" id="mobileMenu">
                <ul>
                    <?php foreach ($theme['menu_main'] as $item): ?>
                        <?php if (!empty($item['enabled'])): ?>
                            <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </header>
    
    <!-- Faixa de Categorias -->
    <section class="pg-category-strip">
        <div class="pg-category-strip-inner">
            <a href="#" class="pg-category-main-button js-open-category-menu" 
               role="button" 
               aria-expanded="false" 
               aria-controls="pgCategoryMenu"
               aria-label="Abrir menu de categorias">
                <span class="pg-category-main-button-icon">
                    <i class="bi bi-list icon"></i>
                </span>
                <span class="pg-category-main-button-label">Categorias</span>
            </a>
            <div class="pg-category-pills-viewport">
                <div class="pg-category-pills-scroll">
                    <?php if (!empty($categoryPills)): ?>
                        <?php foreach ($categoryPills as $pill): ?>
                            <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($pill['categoria_slug']) ?>" 
                               class="pg-category-pill"
                               aria-label="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>">
                                <div class="pg-category-pill-circle">
                                    <?php if ($pill['icone_path']): ?>
                                        <img src="<?= $basePath ?>/<?= htmlspecialchars($pill['icone_path']) ?>" 
                                             alt="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>">
                                    <?php else: ?>
                                        <div class="pg-category-pill-placeholder">
                                            <i class="bi bi-image icon"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="pg-category-pill-label">
                                    <?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Menu de Categorias (Overlay) -->
    <div class="pg-category-menu-overlay" id="pgCategoryMenu" hidden>
        <div class="pg-category-menu-backdrop js-close-category-menu"></div>
        <div class="pg-category-menu-panel" role="dialog" aria-modal="true" aria-labelledby="pgCategoryMenuTitle">
            <div class="pg-category-menu-header">
                <h2 id="pgCategoryMenuTitle">Categorias</h2>
                <button type="button" class="pg-category-menu-close js-close-category-menu" aria-label="Fechar menu de categorias">
                    ×
                </button>
            </div>
            <div class="pg-category-menu-body">
                <ul class="pg-category-menu-list">
                    <?php if (!empty($allCategories)): ?>
                        <?php foreach ($allCategories as $cat): ?>
                            <li>
                                <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($cat['categoria_slug']) ?>" 
                                   class="pg-category-menu-link">
                                    <?= htmlspecialchars($cat['label'] ?? $cat['categoria_nome']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><p style="padding: 8px 10px; color: #666; font-size: 15px;">Nenhuma categoria disponível.</p></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Hero Slider -->
    <?php if (!empty($heroBanners)): ?>
        <section class="home-hero">
            <div class="home-hero-slider" id="home-hero-slider">
                <?php foreach ($heroBanners as $index => $banner): ?>
                    <div class="home-hero-slide <?= $index === 0 ? 'active' : '' ?> <?= empty($banner['imagem_desktop']) && empty($banner['imagem_mobile']) ? 'home-hero-slide-text-only' : '' ?>">
                        <?php if (!empty($banner['imagem_desktop']) || !empty($banner['imagem_mobile'])): ?>
                            <picture>
                                <?php if (!empty($banner['imagem_mobile'])): ?>
                                    <source media="(max-width: 768px)" srcset="<?= $basePath ?>/<?= htmlspecialchars($banner['imagem_mobile']) ?>">
                                <?php endif; ?>
                                <?php 
                                // Fallback: se não houver imagem_desktop, usar imagem_mobile também no desktop
                                $imagemDesktop = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
                                ?>
                                <img src="<?= $basePath ?>/<?= htmlspecialchars($imagemDesktop) ?>"
                                     alt="<?= htmlspecialchars($banner['titulo'] ?: 'Banner') ?>"
                                     class="home-hero-image"
                                     loading="eager"
                                     onerror="this.style.display='none'; console.error('Erro ao carregar banner:', this.src);">
                            </picture>
                        <?php endif; ?>
                        <div class="home-hero-content">
                            <?php if (!empty($banner['titulo'])): ?>
                                <h1><?= htmlspecialchars($banner['titulo']) ?></h1>
                            <?php endif; ?>
                            <?php if (!empty($banner['subtitulo'])): ?>
                                <p><?= nl2br(htmlspecialchars($banner['subtitulo'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($banner['cta_label']) && !empty($banner['cta_url'])): ?>
                                <a href="<?= $basePath ?><?= htmlspecialchars($banner['cta_url']) ?>" class="hero-button">
                                    <?= htmlspecialchars($banner['cta_label']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php else: ?>
        <!-- Hero padrão se não houver banners cadastrados -->
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
                    <?php 
                    // Fallback: usar imagem_mobile se imagem_desktop não existir
                    $imagemBanner = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
                    ?>
                    <div class="banner-portrait" style="<?= !empty($imagemBanner) ? "background-image: url('{$basePath}/" . htmlspecialchars($imagemBanner) . "'); background-size: cover; background-position: center;" : 'background: #f0f0f0;' ?>">
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
    <?php
    // Carregar configuração do footer
    $footerConfig = \App\Services\ThemeConfig::getFooterConfig();
    
    // Buscar categorias de destaque para o footer
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
                    <?php if ($theme['footer_phone']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-telephone icon"></i>
                            <span><?= htmlspecialchars($theme['footer_phone']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($theme['footer_whatsapp']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-whatsapp icon"></i>
                            <span><?= htmlspecialchars($theme['footer_whatsapp']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($theme['footer_email']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-envelope icon"></i>
                            <span><?= htmlspecialchars($theme['footer_email']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($theme['footer_address']): ?>
                        <div class="pg-footer-contact-item">
                            <i class="bi bi-geo-alt icon"></i>
                            <span><?= htmlspecialchars($theme['footer_address']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="pg-footer-social">
                        <?php if ($theme['footer_social_instagram']): ?>
                            <a href="<?= htmlspecialchars($theme['footer_social_instagram']) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram icon"></i></a>
                        <?php endif; ?>
                        <?php if ($theme['footer_social_facebook']): ?>
                            <a href="<?= htmlspecialchars($theme['footer_social_facebook']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook icon"></i></a>
                        <?php endif; ?>
                        <?php if ($theme['footer_social_youtube']): ?>
                            <a href="<?= htmlspecialchars($theme['footer_social_youtube']) ?>" target="_blank" rel="noopener"><i class="bi bi-youtube icon"></i></a>
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
        
        // Menu de Categorias
        document.addEventListener('DOMContentLoaded', function () {
            var openBtn = document.querySelector('.js-open-category-menu');
            var overlay = document.getElementById('pgCategoryMenu');
            var closeButtons = document.querySelectorAll('.js-close-category-menu');
            
            if (!openBtn || !overlay) {
                return;
            }
            
            function openMenu() {
                overlay.hidden = false;
                overlay.classList.add('is-visible');
                openBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden'; // Prevenir scroll do body
                var firstLink = overlay.querySelector('.pg-category-menu-link');
                if (firstLink) {
                    setTimeout(function() {
                        firstLink.focus();
                    }, 100);
                }
            }
            
            function closeMenu() {
                overlay.classList.remove('is-visible');
                setTimeout(function() {
                    overlay.hidden = true;
                }, 200); // Delay para animação
                openBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = ''; // Restaurar scroll do body
                openBtn.focus();
            }
            
            openBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (overlay.hidden) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });
            
            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    closeMenu();
                });
            });
            
            // Fechar ao clicar no backdrop
            var backdrop = overlay.querySelector('.pg-category-menu-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', function () {
                    closeMenu();
                });
            }
            
            // Fechar com ESC
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !overlay.hidden) {
                    closeMenu();
                }
            });
        });
    </script>
    
    <!-- Script do Carrossel Hero -->
    <script>
    (function() {
        'use strict';
        
        // Aguardar DOM estar pronto
        function initHeroSlider() {
            try {
                const slider = document.querySelector('#home-hero-slider');
                if (!slider) {
                    console.warn('[Hero Slider] Elemento #home-hero-slider não encontrado');
                    return;
                }

                const slides = Array.from(slider.querySelectorAll('.home-hero-slide'));
                if (slides.length === 0) {
                    console.warn('[Hero Slider] Nenhum slide encontrado');
                    return;
                }

                // Se só tiver um banner, garantir que está visível e sair
                if (slides.length === 1) {
                    slides[0].classList.add('active');
                    return;
                }

                // Inicializar: garantir que primeiro slide está ativo
                let currentIndex = 0;
                slides.forEach((slide, index) => {
                    if (index === 0) {
                        slide.classList.add('active');
                    } else {
                        slide.classList.remove('active');
                    }
                });

                function showSlide(index) {
                    if (index < 0 || index >= slides.length) return;
                    
                    slides.forEach((slide, i) => {
                        if (i === index) {
                            slide.classList.add('active');
                        } else {
                            slide.classList.remove('active');
                        }
                    });
                    currentIndex = index;
                }

                // Trocar slide automaticamente a cada 5 segundos
                let intervalId = setInterval(function() {
                    try {
                        currentIndex = (currentIndex + 1) % slides.length;
                        showSlide(currentIndex);
                    } catch (e) {
                        console.error('[Hero Slider] Erro ao trocar slide:', e);
                        clearInterval(intervalId);
                    }
                }, 5000);
                
                // Limpar intervalo quando a página sair de foco (opcional, economiza recursos)
                document.addEventListener('visibilitychange', function() {
                    if (document.hidden) {
                        clearInterval(intervalId);
                    } else {
                        intervalId = setInterval(function() {
                            try {
                                currentIndex = (currentIndex + 1) % slides.length;
                                showSlide(currentIndex);
                            } catch (e) {
                                console.error('[Hero Slider] Erro ao trocar slide:', e);
                                clearInterval(intervalId);
                            }
                        }, 5000);
                    }
                });
                
            } catch (error) {
                console.error('[Hero Slider] Erro ao inicializar carrossel:', error);
                // Fallback: garantir que pelo menos o primeiro slide está visível
                const slider = document.querySelector('#home-hero-slider');
                if (slider) {
                    const firstSlide = slider.querySelector('.home-hero-slide');
                    if (firstSlide) {
                        firstSlide.classList.add('active');
                    }
                }
            }
        }
        
        // Executar quando DOM estiver pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeroSlider);
        } else {
            // DOM já está pronto
            initHeroSlider();
        }
    })();
    </script>
</body>
</html>
