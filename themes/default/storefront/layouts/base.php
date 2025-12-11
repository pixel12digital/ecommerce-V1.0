<?php
// Layout Base do Storefront
// Variáveis esperadas:
//   - $pageTitle (string): Título da página para <title>
//   - $showCategoryStrip (bool): Mostrar faixa de categorias? (padrão: false)
//   - $showNewsletter (bool): Mostrar newsletter? (padrão: false)
//   - $content (string): Conteúdo principal da página
//   - $theme (array): Configurações do tema
//   - $loja (array): Dados da loja
//   - $cartTotalItems (int): Total de itens no carrinho
//   - $cartSubtotal (float): Subtotal do carrinho
//   - $categoryPills (array, opcional): Categorias para a faixa (se $showCategoryStrip = true)
//   - $allCategories (array, opcional): Todas as categorias para o menu overlay (se $showCategoryStrip = true)

// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}

// Valores padrão
$pageTitle = $pageTitle ?? ($loja['nome'] ?? 'Loja');
$showCategoryStrip = $showCategoryStrip ?? false;
$showNewsletter = $showNewsletter ?? false;
$categoryPills = $categoryPills ?? [];
$allCategories = $allCategories ?? [];

// Base path
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
            overflow-y: hidden;
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
            display: flex;
            flex-wrap: nowrap;
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
            flex: 0 0 auto;
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
            .pg-category-strip {
                padding: 16px 0;
            }
            .pg-category-strip-inner {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 0 16px;
                max-width: 100%;
            }
            .pg-category-main-button {
                width: 100%;
                justify-content: center;
            }
            .pg-category-pills-viewport {
                width: 100%;
                max-width: 100%;
                margin-left: 0;
                margin-right: 0;
                overflow-x: auto;
                overflow-y: hidden;
                -webkit-overflow-scrolling: touch;
            }
            .pg-category-pills-scroll {
                display: flex;
                flex-wrap: nowrap;
                width: auto;
                min-width: 100%;
                gap: 12px;
                margin: 0;
                padding: 0 4px;
            }
            .pg-category-pill {
                flex: 0 0 auto;
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
        }
        
        /* Container principal para conteúdo */
        .pg-main-content {
            min-height: 50vh; /* Garantir altura mínima */
        }
        
        /* Breadcrumb (usado em várias páginas) */
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
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
            text-decoration: none;
        }
        .breadcrumb span {
            color: #666;
            margin: 0 0.5rem;
        }
        
        /* Hero Slider (usado na home) */
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
        .home-hero-slide-text-only {
            background: linear-gradient(135deg, var(--pg-color-primary), var(--pg-color-secondary));
        }
        .home-hero-slide-text-only .home-hero-content {
            background: transparent;
        }
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
        
        /* Seção Benefícios */
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
        
        /* Seções de Categorias */
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
        
        @media (max-width: 768px) {
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
        
        <?php if (!empty($additionalStyles)): ?>
            <?= $additionalStyles ?>
        <?php endif; ?>
    </style>
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <?php if (!empty($showCategoryStrip)): ?>
        <?php include __DIR__ . '/../partials/category-strip.php'; ?>
    <?php endif; ?>
    
    <main class="pg-main-content">
        <?php
        if (isset($content)) {
            echo $content; // conteúdo renderizado pela view específica
        }
        ?>
    </main>
    
    <?php if (!empty($showNewsletter)): ?>
        <?php include __DIR__ . '/../partials/newsletter.php'; ?>
    <?php endif; ?>
    
    <?php include __DIR__ . '/../partials/footer.php'; ?>
    
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            if (menu) {
                menu.classList.toggle('active');
            }
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
    
    <?php if (!empty($additionalScripts)): ?>
        <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>

