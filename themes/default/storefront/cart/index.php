<?php
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
    <title>Carrinho de Compras - <?= htmlspecialchars($loja['nome']) ?></title>
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
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-weight: 600;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 0 0 auto;
        }
        .header-nav {
            display: flex;
            align-items: center;
        }
        .header-menu {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            margin: 0;
            padding: 0;
        }
        .header-menu a {
            text-decoration: none;
            color: inherit;
            font-weight: 500;
            transition: color 0.2s;
        }
        .header-menu a:hover {
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
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
        }
        .mobile-menu {
            display: none;
            background: <?= htmlspecialchars($theme['color_header_bg']) ?>;
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        .mobile-menu.active {
            display: block;
        }
        .mobile-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .mobile-menu li {
            margin-bottom: 0.5rem;
        }
        .mobile-menu a {
            display: block;
            padding: 0.75rem;
            text-decoration: none;
            color: inherit;
            border-radius: 4px;
        }
        .mobile-menu a:hover {
            background: rgba(0,0,0,0.05);
        }
        
        /* Faixa azul do carrinho (sub-header) */
        .pg-cart-banner {
            background-color: var(--pg-color-primary);
            color: #ffffff;
            padding: 16px 0;
        }
        .pg-cart-banner .pg-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .pg-cart-back-link {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
        }
        .pg-cart-back-link:hover,
        .pg-cart-back-link:focus {
            opacity: 0.8;
            text-decoration: underline;
        }
        .pg-cart-title {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        /* Container principal */
        .pg-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .pg-cart-main {
            padding: 24px 0 48px;
            background-color: #f5f5f5;
        }
        
        /* Mensagens */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message .icon {
            font-size: 1.25rem;
        }
        
        /* Tabela do carrinho */
        .cart-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        .product-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        .product-image-placeholder {
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        .product-image-placeholder .icon {
            font-size: 1.5rem;
            color: #ccc;
            margin-bottom: 0.25rem;
        }
        .product-info {
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }
        .product-name:hover {
            text-decoration: underline;
        }
        .quantity-input {
            width: 80px;
            padding: 0.625rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .quantity-input:focus {
            outline: none;
            border-color: var(--pg-color-primary);
        }
        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--pg-color-primary);
            border-bottom: none;
            margin-top: 0.5rem;
        }
        .cart-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn-primary {
            background: var(--pg-color-secondary);
            color: white;
            flex: 1;
            text-align: center;
            justify-content: center;
        }
        .btn-primary:hover {
            background: var(--pg-color-secondary);
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
        }
        
        /* Footer */
        .pg-footer {
            background-color: #111111;
            color: #f5f5f5;
        }
        .pg-footer-main {
            padding: 40px 0 32px 0;
        }
        .pg-footer-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 32px 40px;
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
        .pg-footer-contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e0e0e0;
            margin-bottom: 8px;
        }
        .pg-footer-contact-item .icon {
            color: var(--pg-color-secondary);
        }
        .pg-footer-social {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }
        .pg-footer-social a {
            color: #e0e0e0;
            font-size: 1.25rem;
            transition: color 0.2s;
        }
        .pg-footer-social a:hover {
            color: var(--pg-color-secondary);
        }
        .pg-footer-bottom {
            border-top: 1px solid #222222;
            background-color: #0c0c0c;
            padding: 12px 0;
            font-size: 13px;
            color: #cccccc;
        }
        .pg-footer-bottom-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .pg-footer-copy {
            white-space: nowrap;
        }
        .pg-footer-dev a {
            color: var(--pg-color-secondary);
            text-decoration: none;
            font-weight: 600;
        }
        .pg-footer-dev a:hover {
            text-decoration: underline;
        }
        
        /* Responsivo */
        @media (max-width: 1199.98px) {
            .pg-footer-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 991.98px) {
            .pg-footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 24px 32px;
            }
        }
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
                display: none;
            }
            .header-menu {
                display: none;
            }
            .header-icons {
                gap: 8px;
            }
            .header-cart span {
                display: none;
            }
            .cart-info {
                display: none;
            }
            .menu-toggle {
                display: block;
            }
            .pg-cart-banner .pg-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .pg-cart-title {
                font-size: 20px;
            }
            .cart-table {
                overflow-x: auto;
            }
            table {
                min-width: 600px;
            }
            .cart-actions {
                flex-direction: column;
            }
            .pg-footer-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .pg-footer-main {
                padding: 32px 0 24px 0;
            }
            .pg-container {
                padding: 0 1rem;
            }
            .pg-footer-bottom-inner {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
    <div class="topbar">
        <?= htmlspecialchars($theme['topbar_text']) ?>
    </div>
    
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?= $basePath ?>/" class="header-logo">
                <?php if (!empty($theme['logo_url'])): ?>
                    <img src="<?= $basePath . htmlspecialchars($theme['logo_url']) ?>" alt="<?= htmlspecialchars($loja['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
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
                        <?php foreach ($theme['menu_main'] as $item): ?>
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
                        <?php if ($cartTotalItems > 0): ?>
                            <div class="cart-info">
                                <span class="cart-count"><?= $cartTotalItems ?> <?= $cartTotalItems === 1 ? 'item' : 'itens' ?></span>
                                <span class="cart-total">R$ <?= number_format($cartSubtotal, 2, ',', '.') ?></span>
                            </div>
                        <?php endif; ?>
                    </a>
                </div>
                
                <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="bi bi-list icon"></i></button>
            </div>
            
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
    
    <!-- Faixa azul do carrinho (sub-header) -->
    <div class="pg-cart-banner">
        <div class="pg-container">
            <a href="<?= $basePath ?>/" class="pg-cart-back-link">
                <i class="bi bi-arrow-left icon"></i>
                Voltar
            </a>
            <h1 class="pg-cart-title">Carrinho de Compras</h1>
        </div>
    </div>
    
    <!-- Conteúdo do carrinho -->
    <div class="pg-cart-main">
        <div class="pg-container">
            <?php if (isset($_GET['added'])): ?>
                <div class="message success">
                    <i class="bi bi-check-circle icon"></i>
                    <span>Produto adicionado ao carrinho!</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="message success">
                    <i class="bi bi-check-circle icon"></i>
                    <span>Carrinho atualizado!</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['removed'])): ?>
                <div class="message success">
                    <i class="bi bi-check-circle icon"></i>
                    <span>Produto removido do carrinho!</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="message error">
                    <i class="bi bi-exclamation-triangle icon"></i>
                    <span>
                        <?php
                        $errors = [
                            'carrinho_vazio' => 'Seu carrinho está vazio.',
                            'produto_invalido' => 'Produto inválido.',
                        ];
                        echo $errors[$_GET['error']] ?? 'Erro desconhecido.';
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if (empty($cart['items'])): ?>
                <div class="empty-cart">
                    <i class="bi bi-cart-x icon" style="font-size: 4rem; color: #ccc; display: block; margin-bottom: 1rem;"></i>
                    <p style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Seu carrinho está vazio</p>
                    <p style="color: #666; margin-bottom: 1.5rem;">Adicione produtos ao carrinho para continuar.</p>
                    <a href="<?= $basePath ?>/produtos" class="btn btn-primary">
                        <i class="bi bi-arrow-left icon"></i>
                        Continuar Comprando
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Preço Unitário</th>
                                <th>Quantidade</th>
                                <th>Total</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <?php if ($item['imagem']): ?>
                                                <img src="<?= $basePath ?>/<?= htmlspecialchars($item['imagem']) ?>" 
                                                     alt="<?= htmlspecialchars($item['nome']) ?>"
                                                     class="product-image">
                                            <?php else: ?>
                                                <div class="product-image-placeholder">
                                                    <i class="bi bi-image icon"></i>
                                                    <span>Sem imagem</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="product-info">
                                                <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($item['slug']) ?>" class="product-name">
                                                    <?= htmlspecialchars($item['nome']) ?>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                    <td>
                                        <form method="POST" action="<?= $basePath ?>/carrinho/atualizar" style="display: inline;">
                                            <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                            <input type="number" name="quantidade" value="<?= $item['quantidade'] ?>" 
                                                   min="1" class="quantity-input" 
                                                   onchange="this.form.submit()">
                                        </form>
                                    </td>
                                    <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                                    <td>
                                        <form method="POST" action="<?= $basePath ?>/carrinho/remover" style="display: inline;">
                                            <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                            <button type="submit" class="btn btn-remove">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    <div class="summary-row" style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                        <span style="font-weight: 600;">Frete:</span>
                        <span style="color: #666;">Será calculado no checkout</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="<?= $basePath ?>/produtos" class="btn btn-secondary">
                            <i class="bi bi-arrow-left icon"></i>
                            Continuar Comprando
                        </a>
                        <a href="<?= $basePath ?>/checkout" class="btn btn-primary">
                            Finalizar Compra
                            <i class="bi bi-arrow-right icon"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
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
    </script>
</body>
</html>
