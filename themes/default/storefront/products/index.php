<?php
// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $categoriaAtual ? htmlspecialchars($categoriaAtual['nome']) : 'Todos os Produtos' ?></title>
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
        
        /* Header (reutilizar da home) */
        .topbar {
            background: <?= htmlspecialchars($theme['color_topbar_bg'] ?? '#1a1a1a') ?>;
            color: <?= htmlspecialchars($theme['color_topbar_text'] ?? '#ffffff') ?>;
            padding: 0.5rem 0;
            text-align: center;
            font-size: 0.875rem;
        }
        .header {
            background: <?= htmlspecialchars($theme['color_header_bg'] ?? '#ffffff') ?>;
            color: <?= htmlspecialchars($theme['color_header_text'] ?? '#333333') ?>;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .header-logo img {
            max-height: 50px;
            max-width: 200px;
            object-fit: contain;
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
        .cart-icon .icon {
            font-size: 1.5rem;
        }
        .cart-icon .icon {
            font-size: 1.5rem;
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
        
        /* Breadcrumb */
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
        
        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        
        /* Sidebar de filtros */
        .filters-sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        .filters-sidebar h3 {
            margin-bottom: 1rem;
            font-size: 1.125rem;
            color: #333;
        }
        .filter-group {
            margin-bottom: 1.5rem;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        /* Fase 10 - Labels em PT-BR */
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .filter-group .price-range {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        .btn-filter {
            width: 100%;
            padding: 0.75rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1rem;
        }
        .btn-clear {
            width: 100%;
            padding: 0.5rem;
            background: #f0f0f0;
            color: #666;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        /* Área de produtos */
        .products-area {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .page-title {
            font-size: 1.75rem;
            color: #333;
        }
        .sort-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
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
        .product-image-wrapper {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            flex-shrink: 0;
        }
        .product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: #f0f0f0;
        }
        /* Fase 10 - Ajustes layout storefront */
        .product-image-placeholder {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.875rem;
        }
        .product-image-placeholder .icon {
            font-size: 2.5rem;
            color: #ccc;
            margin-bottom: 0.5rem;
        }
        .product-info {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
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
            margin-bottom: 0.75rem;
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
        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        .btn-view {
            flex: 1;
            padding: 0.5rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
        }
        .btn-add {
            padding: 0.5rem 1rem;
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
            min-width: 40px;
            text-align: center;
        }
        .pagination a:hover {
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border-color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination .current {
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border-color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        
        /* Mobile */
        .mobile-filters-toggle {
            display: none;
            padding: 0.75rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 1rem;
            width: 100%;
        }
        .empty-message {
            text-align: center;
            padding: 3rem;
            color: #666;
            background: white;
            border-radius: 8px;
        }
        .cart-message {
            background: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        /* Responsivo - Fase 10 */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.25rem;
            }
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                margin: 1.5rem auto;
                padding: 0 1rem;
            }
            .filters-sidebar {
                display: none;
                position: static;
                margin-bottom: 1.5rem;
            }
            .filters-sidebar.active {
                display: block;
            }
            .mobile-filters-toggle {
                display: block;
            }
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }
            .product-image-wrapper {
                height: 180px;
            }
            .product-image-placeholder {
                height: 180px;
            }
            .products-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .sort-select {
                width: 100%;
            }
            .breadcrumb {
                padding: 0.75rem 1rem;
            }
        }
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            .product-image-wrapper,
            .product-image-placeholder {
                height: 160px;
            }
            .product-info {
                padding: 1rem;
            }
            .product-name {
                font-size: 0.875rem;
            }
            .product-price {
                font-size: 1.125rem;
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
    
    // Construir URL base para filtros
    $urlBase = $categoriaAtual ? $basePath . '/categoria/' . htmlspecialchars($categoriaAtual['slug']) : $basePath . '/produtos';
    
    // Função para construir query string mantendo filtros
    $buildQuery = function($newParams = []) use ($filtrosAtuais, $urlBase) {
        $params = array_merge($filtrosAtuais, $newParams);
        $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
        $query = http_build_query($params);
        return $urlBase . ($query ? '?' . $query : '');
    };
    ?>
    
    <!-- Header simplificado -->
    <header class="header">
        <div class="header-container">
            <a href="<?= $basePath ?>/" class="header-logo">
                <?php if (!empty($theme['logo_url'])): ?>
                    <img src="<?= $basePath . htmlspecialchars($theme['logo_url']) ?>" alt="Loja" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                    <span style="display: none;">Loja</span>
                <?php else: ?>
                    Loja
                <?php endif; ?>
            </a>
            <div class="header-search">
                <form method="GET" action="<?= $urlBase ?>">
                    <input type="text" name="q" value="<?= htmlspecialchars($filtrosAtuais['q']) ?>" placeholder="Buscar produtos...">
                    <button type="submit"><i class="bi bi-search icon"></i> Buscar</button>
                </form>
            </div>
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
        </div>
    </header>
    
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="breadcrumb-container">
            <a href="<?= $basePath ?>/">Home</a>
            <span>></span>
            <a href="<?= $basePath ?>/produtos">Loja</a>
            <?php if ($categoriaAtual): ?>
                <span>></span>
                <span><?= htmlspecialchars($categoriaAtual['nome']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="container">
        <!-- Sidebar de Filtros -->
        <aside class="filters-sidebar" id="filtersSidebar">
            <h3>Filtros</h3>
            <form method="GET" action="<?= $urlBase ?>">
                <!-- Busca -->
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filtrosAtuais['q']) ?>" placeholder="Nome ou SKU">
                </div>
                
                <!-- Categoria -->
                <?php if (!$categoriaAtual): ?>
                <div class="filter-group">
                    <label>Categoria</label>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categoriasFiltro as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                    <?= $filtrosAtuais['categoria'] === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Faixa de Preço -->
                <div class="filter-group">
                    <label>Preço</label>
                    <div class="price-range">
                        <input type="number" name="preco_min" value="<?= $filtrosAtuais['preco_min'] ?>" 
                               placeholder="Mín" step="0.01" min="0">
                        <input type="number" name="preco_max" value="<?= $filtrosAtuais['preco_max'] ?>" 
                               placeholder="Máx" step="0.01" min="0">
                    </div>
                </div>
                
                <!-- Ordenação -->
                <div class="filter-group">
                    <label>Ordenar por</label>
                    <select name="ordenar">
                        <option value="novidades" <?= $filtrosAtuais['ordenar'] === 'novidades' ? 'selected' : '' ?>>Novidades</option>
                        <option value="menor_preco" <?= $filtrosAtuais['ordenar'] === 'menor_preco' ? 'selected' : '' ?>>Menor Preço</option>
                        <option value="maior_preco" <?= $filtrosAtuais['ordenar'] === 'maior_preco' ? 'selected' : '' ?>>Maior Preço</option>
                        <option value="mais_vendidos" <?= $filtrosAtuais['ordenar'] === 'mais_vendidos' ? 'selected' : '' ?>>Mais Vendidos</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">Aplicar Filtros</button>
                <a href="<?= $urlBase ?>" class="btn-clear">Limpar Filtros</a>
            </form>
        </aside>
        
        <!-- Área de Produtos -->
        <main class="products-area">
            <button class="mobile-filters-toggle" onclick="toggleFilters()"><i class="bi bi-list icon"></i> Filtros</button>
            
            <?php if (isset($_GET['cart_message'])): ?>
                <div class="cart-message">
                    <?= htmlspecialchars(urldecode($_GET['cart_message'])) ?>
                </div>
            <?php endif; ?>
            
            <div class="products-header">
                <h1 class="page-title">
                    <?= $categoriaAtual ? htmlspecialchars($categoriaAtual['nome']) : 'Todos os Produtos' ?>
                    <?php if ($paginacao['total'] > 0): ?>
                        <span style="font-size: 1rem; color: #666; font-weight: normal;">
                            (<?= $paginacao['total'] ?> <?= $paginacao['total'] === 1 ? 'produto' : 'produtos' ?>)
                        </span>
                    <?php endif; ?>
                </h1>
                <select class="sort-select" onchange="window.location.href='<?= $buildQuery(['ordenar' => '']) ?>'.replace('ordenar=', 'ordenar=' + this.value)">
                    <option value="novidades" <?= $filtrosAtuais['ordenar'] === 'novidades' ? 'selected' : '' ?>>Novidades</option>
                    <option value="menor_preco" <?= $filtrosAtuais['ordenar'] === 'menor_preco' ? 'selected' : '' ?>>Menor Preço</option>
                    <option value="maior_preco" <?= $filtrosAtuais['ordenar'] === 'maior_preco' ? 'selected' : '' ?>>Maior Preço</option>
                    <option value="mais_vendidos" <?= $filtrosAtuais['ordenar'] === 'mais_vendidos' ? 'selected' : '' ?>>Mais Vendidos</option>
                </select>
            </div>
            
            <?php if (!empty($produtos)): ?>
                <div class="products-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <div class="product-card">
                            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-image-wrapper">
                                <?php if ($produto['imagem_principal'] && !empty($produto['imagem_principal']['caminho_arquivo'])): ?>
                                    <img src="<?= media_url($produto['imagem_principal']['caminho_arquivo']) ?>" 
                                         alt="<?= htmlspecialchars($produto['imagem_principal']['alt_text'] ?? $produto['nome']) ?>"
                                         class="product-image">
                                <?php else: ?>
                                    <div class="product-image-placeholder">
                                        <i class="bi bi-image icon"></i>
                                        <span>Sem imagem</span>
                                    </div>
                                <?php endif; ?>
                            </a>
                            
                            <div class="product-info">
                                <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-name">
                                    <?= htmlspecialchars($produto['nome']) ?>
                                </a>
                                
                                <div class="product-price">
                                    <?php if ($produto['preco_promocional']): ?>
                                        <span class="product-price-old">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
                                        <span class="product-price-promo">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                                    <?php else: ?>
                                        R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="btn-view">Ver</a>
                                    <form method="POST" action="<?= $basePath ?>/carrinho/adicionar" style="display: inline;">
                                        <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                        <input type="hidden" name="quantidade" value="1">
                                        <button type="submit" class="btn-add">+</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginação -->
                <?php if ($paginacao['totalPages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($paginacao['hasPrev']): ?>
                            <a href="<?= $buildQuery(['page' => $paginacao['currentPage'] - 1]) ?>">« Anterior</a>
                        <?php else: ?>
                            <span class="disabled">« Anterior</span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $paginacao['currentPage'] - 2);
                        $endPage = min($paginacao['totalPages'], $paginacao['currentPage'] + 2);
                        
                        if ($startPage > 1): ?>
                            <a href="<?= $buildQuery(['page' => 1]) ?>">1</a>
                            <?php if ($startPage > 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $paginacao['currentPage']): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= $buildQuery(['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $paginacao['totalPages']): ?>
                            <?php if ($endPage < $paginacao['totalPages'] - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="<?= $buildQuery(['page' => $paginacao['totalPages']]) ?>"><?= $paginacao['totalPages'] ?></a>
                        <?php endif; ?>
                        
                        <?php if ($paginacao['hasNext']): ?>
                            <a href="<?= $buildQuery(['page' => $paginacao['currentPage'] + 1]) ?>">Próxima »</a>
                        <?php else: ?>
                            <span class="disabled">Próxima »</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-message">
                    <p>Nenhum produto encontrado com os filtros selecionados.</p>
                    <a href="<?= $urlBase ?>" style="color: <?= htmlspecialchars($theme['color_primary']) ?>; margin-top: 1rem; display: inline-block;">
                        Limpar filtros
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
        function toggleFilters() {
            const sidebar = document.getElementById('filtersSidebar');
            sidebar.classList.toggle('active');
        }
    </script>
</body>
</html>
