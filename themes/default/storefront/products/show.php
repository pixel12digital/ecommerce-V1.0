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
    <title><?= htmlspecialchars($produto['nome']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        :root {
            --cor-primaria: <?= htmlspecialchars($theme['color_primary'] ?? '#2E7D32') ?>;
            --cor-secundaria: <?= htmlspecialchars($theme['color_secondary'] ?? '#F7931E') ?>;
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
        
        /* Header simplificado */
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
        }
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: inherit;
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
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        /* Produto principal */
        .product-detail {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }
        
        /* Galeria de imagens */
        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .main-image-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            background: #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .main-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 1rem;
        }
        /* Fase 10 - Ajustes layout storefront */
        .image-placeholder {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.875rem;
            border: 1px solid #e0e0e0;
        }
        .image-placeholder .icon {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 0.5rem;
        }
        .thumbnails {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .thumbnail-wrapper {
            position: relative;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color 0.2s, transform 0.2s;
            display: block;
        }
        .thumbnail:hover {
            transform: scale(1.05);
        }
        .thumbnail.active {
            border-color: <?= htmlspecialchars($theme['color_primary']) ?>;
            border-width: 3px;
        }
        
        /* Thumbnails de vídeo */
        .thumbnail-wrapper--video {
            position: relative;
        }
        .thumbnail--video {
            position: relative;
            width: 80px;
            height: 80px;
            padding: 0;
            overflow: hidden;
            background: #f0f0f0;
        }
        .thumbnail--video .thumbnail-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .thumbnail-play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 2rem;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
            pointer-events: none;
            z-index: 1;
            transition: transform 0.2s;
        }
        .thumbnail-wrapper--video:hover .thumbnail-play-icon {
            transform: translate(-50%, -50%) scale(1.1);
        }
        .thumbnail-wrapper--video:hover .thumbnail {
            border-color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        
        /* Informações do produto */
        .product-info {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .product-title {
            font-size: 2rem;
            color: #333;
            line-height: 1.3;
        }
        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }
        .stars {
            color: #ffc107;
        }
        .product-price-section {
            padding: 1rem;
            background: #f8f8f8;
            border-radius: 8px;
        }
        .price-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        .product-price-promo {
            color: <?= htmlspecialchars($theme['color_secondary']) ?>;
        }
        .product-price-old {
            text-decoration: line-through;
            color: #999;
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        .product-price-from {
            font-size: 1.2rem;
            color: #666;
            margin-right: 0.5rem;
        }
        .product-stock {
            padding: 0.875rem 1.25rem;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .stock-in {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .stock-out {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .add-to-cart-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .quantity-input {
            width: 80px;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            text-align: center;
        }
        .btn-add-cart {
            flex: 1;
            padding: 1rem 2.5rem;
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-add-cart:hover:not(:disabled) {
            background: <?= htmlspecialchars($theme['color_secondary']) ?>;
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn-add-cart:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            background: #ccc;
        }
        
        /* Seções */
        .product-sections {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .section-tabs {
            display: flex;
            gap: 1rem;
            border-bottom: 2px solid #eee;
            margin-bottom: 1.5rem;
        }
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            margin-bottom: -2px;
        }
        .tab-button.active {
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
            border-bottom-color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        .description {
            line-height: 1.8;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #333;
        }
        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .category-link {
            padding: 0.5rem 1rem;
            background: <?= htmlspecialchars($theme['color_primary']) ?>;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }
        .category-link:hover {
            opacity: 0.8;
        }
        
        /* Produtos relacionados */
        .related-products {
            margin-top: 3rem;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .related-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .related-card:hover {
            transform: translateY(-4px);
        }
        .related-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: #e0e0e0;
        }
        .related-info {
            padding: 1rem;
        }
        .related-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .related-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: <?= htmlspecialchars($theme['color_primary']) ?>;
        }
        .related-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        /* Mensagens */
        .cart-message {
            background: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        /* Vídeos do Produto */
        .product-videos {
            margin-bottom: 2rem;
        }
        .product-videos__grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        .product-videos__item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e0e0e0;
        }
        .product-videos__item:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .product-videos__thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-videos__play-icon {
            font-size: 4rem;
            color: white;
            opacity: 0.9;
            transition: transform 0.2s;
        }
        .product-videos__item:hover .product-videos__play-icon {
            transform: scale(1.1);
        }
        .product-videos__info {
            padding: 1rem;
        }
        .product-videos__label {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        /* Modal de Vídeo */
        .product-video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .product-video-modal.is-open {
            display: flex;
        }
        .product-video-modal__backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            cursor: pointer;
        }
        .product-video-modal__dialog {
            position: relative;
            width: 90%;
            max-width: 900px;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            z-index: 10001;
        }
        .product-video-modal__close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 10002;
            transition: background 0.2s;
        }
        .product-video-modal__close:hover {
            background: rgba(0, 0, 0, 0.9);
        }
        .product-video-modal__content {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            height: 0;
            overflow: hidden;
        }
        .product-video-modal__content iframe,
        .product-video-modal__content video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }
        
        /* Responsivo - Fase 10 */
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 1.5rem;
            }
            .product-title {
                font-size: 1.5rem;
            }
            .product-price {
                font-size: 2rem;
            }
            .product-price-section {
                padding: 1rem;
            }
            .add-to-cart-form {
                flex-direction: column;
            }
            .quantity-input {
                width: 100%;
            }
            .thumbnails {
                gap: 0.5rem;
                overflow-x: auto;
                padding: 0.5rem 0;
                -webkit-overflow-scrolling: touch;
            }
            .thumbnail {
                width: 70px;
                height: 70px;
                flex-shrink: 0;
            }
            .product-videos__grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
            .product-video-modal__dialog {
                width: 95%;
            }
            .product-reviews {
                padding: 2rem 0;
            }
            .product-reviews__summary {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .product-reviews__average {
                font-size: 2rem;
            }
            .product-review__form-wrapper {
                padding: 1.5rem;
            }
            .section-tabs {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .tab-button {
                white-space: nowrap;
                flex-shrink: 0;
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
    
    $categoriaPrincipal = !empty($categorias) ? $categorias[0] : null;
    ?>
    
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="<?= $basePath ?>/" class="header-logo">Loja</a>
            <div style="display: flex; align-items: center; gap: 1rem;">
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
                <a href="<?= $basePath ?>/produtos" style="color: inherit; text-decoration: none;"><i class="bi bi-arrow-left icon"></i> Voltar</a>
            </div>
        </div>
    </header>
    
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="breadcrumb-container">
            <a href="<?= $basePath ?>/">Home</a>
            <span>></span>
            <a href="<?= $basePath ?>/produtos">Loja</a>
            <?php if ($categoriaPrincipal): ?>
                <span>></span>
                <a href="<?= $basePath ?>/categoria/<?= htmlspecialchars($categoriaPrincipal['slug']) ?>">
                    <?= htmlspecialchars($categoriaPrincipal['nome']) ?>
                </a>
            <?php endif; ?>
            <span>></span>
            <span><?= htmlspecialchars($produto['nome']) ?></span>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['cart_message'])): ?>
            <div class="cart-message">
                <?= htmlspecialchars(urldecode($_GET['cart_message'])) ?>
            </div>
        <?php endif; ?>
        
        <!-- Detalhes do Produto -->
        <div class="product-detail">
            <!-- Galeria de Imagens e Vídeos -->
            <div class="product-images">
                <?php if (!empty($imagens) || !empty($videos)): ?>
                    <?php 
                    // Determinar imagem principal (primeira imagem ou placeholder)
                    $imagemPrincipal = !empty($imagens) ? $imagens[0] : null;
                    ?>
                    <div class="main-image-wrapper">
                        <?php if ($imagemPrincipal): ?>
                            <img id="mainImage" 
                                 src="<?= $basePath ?>/<?= htmlspecialchars($imagemPrincipal['caminho_arquivo']) ?>" 
                                 alt="<?= htmlspecialchars($imagemPrincipal['alt_text'] ?? $produto['nome']) ?>"
                                 class="main-image">
                        <?php else: ?>
                            <div class="image-placeholder">
                                <i class="bi bi-image icon"></i>
                                <span>Sem imagem</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php 
                    // Combinar imagens e vídeos para thumbnails
                    $totalThumbs = count($imagens) + count($videos);
                    if ($totalThumbs > 1 || !empty($videos)): 
                    ?>
                        <div class="thumbnails">
                            <?php 
                            // Thumbnails de imagens
                            foreach ($imagens as $index => $imagem): 
                            ?>
                                <div class="thumbnail-wrapper" data-type="image">
                                    <img src="<?= $basePath ?>/<?= htmlspecialchars($imagem['caminho_arquivo']) ?>" 
                                         alt="<?= htmlspecialchars($imagem['alt_text'] ?? '') ?>"
                                         class="thumbnail <?= $index === 0 && empty($videos) ? 'active' : '' ?>"
                                         onclick="changeImage('<?= htmlspecialchars($imagem['caminho_arquivo']) ?>', this)">
                                </div>
                            <?php endforeach; ?>
                            
                            <?php 
                            // Thumbnails de vídeos
                            foreach ($videos as $index => $video): 
                                $videoTitle = !empty($video['titulo']) ? htmlspecialchars($video['titulo']) : 'Vídeo ' . ($index + 1);
                            ?>
                                <div class="thumbnail-wrapper thumbnail-wrapper--video" 
                                     data-type="video"
                                     data-video-type="<?= htmlspecialchars($video['tipo'] ?? 'unknown') ?>"
                                     data-video-embed="<?= htmlspecialchars($video['embed_url'] ?? '') ?>"
                                     data-video-url="<?= htmlspecialchars($video['url']) ?>">
                                    <div class="thumbnail thumbnail--video">
                                        <img src="<?= htmlspecialchars($video['thumb_url'] ?? '') ?>" 
                                             alt="<?= $videoTitle ?>"
                                             class="thumbnail-image">
                                        <span class="thumbnail-play-icon" aria-hidden="true">
                                            <i class="bi bi-play-circle-fill"></i>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="image-placeholder">Sem imagem</div>
                <?php endif; ?>
            </div>
            
            <!-- Informações do Produto -->
            <div class="product-info">
                <div>
                    <h1 class="product-title"><?= htmlspecialchars($produto['nome']) ?></h1>
                    <?php if ($avaliacoesResumo['total'] > 0): ?>
                        <div class="product-rating" style="margin-top: 0.75rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <div style="display: flex; gap: 0.125rem;">
                                    <?php
                                    $mediaInteira = floor($avaliacoesResumo['media']);
                                    $temMeia = ($avaliacoesResumo['media'] - $mediaInteira) >= 0.5;
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $mediaInteira):
                                    ?>
                                        <i class="bi bi-star-fill" style="color: #FFC107; font-size: 1.125rem;"></i>
                                    <?php elseif ($i == $mediaInteira + 1 && $temMeia): ?>
                                        <i class="bi bi-star-half" style="color: #FFC107; font-size: 1.125rem;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star" style="color: #ddd; font-size: 1.125rem;"></i>
                                    <?php
                                        endif;
                                    endfor;
                                    ?>
                                </div>
                                <span style="color: #666; font-size: 0.9rem;">
                                    <?= number_format($avaliacoesResumo['media'], 1, ',', '.') ?> 
                                    (<?= $avaliacoesResumo['total'] ?> <?= $avaliacoesResumo['total'] === 1 ? 'avaliação' : 'avaliações' ?>)
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-price-section">
                    <div class="price-label" style="font-size: 0.875rem; color: #666;">Preço</div>
                    <div class="product-price">
                        <?php if ($produto['preco_promocional']): ?>
                            <span class="product-price-from">de</span>
                            <span class="product-price-old">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
                            <span class="product-price-from">por</span>
                            <span class="product-price-promo">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                        <?php else: ?>
                            R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="product-stock <?= $produto['status_estoque'] === 'instock' ? 'stock-in' : 'stock-out' ?>">
                    <?php 
                    $stockStatus = \App\Support\LangHelper::stockStatusLabel($produto['status_estoque'] ?? null);
                    $isInStock = $produto['status_estoque'] === 'instock';
                    ?>
                    <?php if ($isInStock): ?>
                        <i class="bi bi-check-circle-fill icon" style="color: #28a745;"></i> <?= $stockStatus ?>
                        <?php if ($produto['quantidade_estoque']): ?>
                            (<?= $produto['quantidade_estoque'] ?> unidades disponíveis)
                        <?php endif; ?>
                    <?php else: ?>
                        <i class="bi bi-x-circle-fill icon" style="color: #dc3545;"></i> <?= $stockStatus ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" action="<?= $basePath ?>/carrinho/adicionar" class="add-to-cart-form">
                    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                    <input type="number" name="quantidade" value="1" min="1" 
                           max="<?= $produto['quantidade_estoque'] ?? 999 ?>" 
                           class="quantity-input"
                           <?= $produto['status_estoque'] !== 'instock' ? 'disabled' : '' ?>>
                    <button type="submit" class="btn-add-cart" 
                            <?= $produto['status_estoque'] !== 'instock' ? 'disabled' : '' ?>>
                        <i class="bi bi-cart-plus icon" style="margin-right: 0.5rem;"></i>
                        Adicionar ao Carrinho
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Seções com Abas -->
        <div class="product-sections">
            <div class="section-tabs">
                <button class="tab-button active" onclick="showTab('descricao')">Descrição</button>
                <?php if ($produto['peso'] || $produto['comprimento'] || $produto['largura'] || $produto['altura'] || $produto['sku']): ?>
                    <button class="tab-button" onclick="showTab('informacoes')">Informações Adicionais</button>
                <?php endif; ?>
                <?php if (!empty($categorias)): ?>
                    <button class="tab-button" onclick="showTab('categorias')">Categorias</button>
                <?php endif; ?>
            </div>
            
            <!-- Descrição -->
            <div id="tab-descricao" class="tab-content active">
                <div class="description">
                    <?php if ($produto['descricao']): ?>
                        <?= nl2br(htmlspecialchars($produto['descricao'])) ?>
                    <?php else: ?>
                        <p>Sem descrição disponível.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Informações Adicionais -->
            <?php if ($produto['peso'] || $produto['comprimento'] || $produto['largura'] || $produto['altura'] || $produto['sku']): ?>
                <div id="tab-informacoes" class="tab-content">
                    <div class="info-grid">
                        <?php if ($produto['sku']): ?>
                            <div class="info-item">
                                <span class="info-label">SKU</span>
                                <span class="info-value"><?= htmlspecialchars($produto['sku']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($produto['peso']): ?>
                            <div class="info-item">
                                <span class="info-label">Peso</span>
                                <span class="info-value"><?= number_format($produto['peso'], 2, ',', '.') ?> kg</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($produto['comprimento'] || $produto['largura'] || $produto['altura']): ?>
                            <div class="info-item">
                                <span class="info-label">Dimensões</span>
                                <span class="info-value">
                                    <?php
                                    $dims = [];
                                    if ($produto['comprimento']) $dims[] = number_format($produto['comprimento'], 2, ',', '.') . ' cm (C)';
                                    if ($produto['largura']) $dims[] = number_format($produto['largura'], 2, ',', '.') . ' cm (L)';
                                    if ($produto['altura']) $dims[] = number_format($produto['altura'], 2, ',', '.') . ' cm (A)';
                                    echo implode(' × ', $dims);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Categorias -->
            <?php if (!empty($categorias)): ?>
                <div id="tab-categorias" class="tab-content">
                    <div class="categories">
                        <?php foreach ($categorias as $categoria): ?>
                            <a href="<?= $basePath ?>/categoria/<?= htmlspecialchars($categoria['slug']) ?>" class="category-link">
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Vídeos do Produto -->
        <?php if (!empty($videos)): ?>
            <?php
            // Função helper para detectar tipo de vídeo e gerar URL de embed
            function getVideoEmbedInfo($url) {
                $url = trim($url);
                if (empty($url)) {
                    return ['type' => 'unknown', 'embed_url' => '', 'original_url' => $url];
                }
                
                // YouTube
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
                    $videoId = $matches[1];
                    return [
                        'type' => 'youtube',
                        'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
                        'original_url' => $url
                    ];
                }
                
                // Vimeo
                if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $matches)) {
                    $videoId = $matches[1];
                    return [
                        'type' => 'vimeo',
                        'embed_url' => 'https://player.vimeo.com/video/' . $videoId,
                        'original_url' => $url
                    ];
                }
                
                // MP4 ou outros links diretos
                if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
                    return [
                        'type' => 'mp4',
                        'embed_url' => $url,
                        'original_url' => $url
                    ];
                }
                
                // Tentar como MP4 se começar com http/https e não for YouTube/Vimeo
                if (preg_match('/^https?:\/\//', $url)) {
                    return [
                        'type' => 'mp4',
                        'embed_url' => $url,
                        'original_url' => $url
                    ];
                }
                
                return ['type' => 'unknown', 'embed_url' => $url, 'original_url' => $url];
            }
            ?>
            <section class="product-videos">
                <div class="product-sections">
                    <h2 class="section-title">Vídeos do produto</h2>
                    <div class="product-videos__grid">
                        <?php foreach ($videos as $index => $video): ?>
                            <?php
                            $videoInfo = getVideoEmbedInfo($video['url']);
                            $videoTitle = !empty($video['titulo']) ? htmlspecialchars($video['titulo']) : 'Vídeo ' . ($index + 1);
                            ?>
                            <div class="product-videos__item" 
                                 data-video-type="<?= htmlspecialchars($videoInfo['type']) ?>"
                                 data-video-embed="<?= htmlspecialchars($videoInfo['embed_url']) ?>"
                                 data-video-url="<?= htmlspecialchars($video['url']) ?>">
                                <div class="product-videos__thumb">
                                    <i class="bi bi-play-circle-fill product-videos__play-icon"></i>
                                </div>
                                <div class="product-videos__info">
                                    <div class="product-videos__label"><?= $videoTitle ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            
            <!-- Modal de Vídeo -->
            <div class="product-video-modal" id="product-video-modal" aria-hidden="true">
                <div class="product-video-modal__backdrop" data-video-modal-close></div>
                <div class="product-video-modal__dialog">
                    <button class="product-video-modal__close" type="button" data-video-modal-close aria-label="Fechar">
                        <i class="bi bi-x-lg"></i>
                    </button>
                    <div class="product-video-modal__content" id="product-video-modal-content">
                        <!-- Conteúdo do player será injetado via JavaScript -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        /* Fase 10 - Seção de Avaliações */
        .product-reviews {
            margin-top: 4rem;
            padding: 3rem 0;
            border-top: 2px solid #eee;
            background: #f8f8f8;
        }
        .product-reviews__header {
            margin-bottom: 2rem;
        }
        .product-reviews__summary {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .product-reviews__average {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--cor-primaria);
        }
        .product-reviews__stars {
            display: flex;
            gap: 0.25rem;
        }
        .product-reviews__count {
            color: #666;
            font-size: 1rem;
        }
        .product-reviews__list {
            margin-bottom: 2rem;
        }
        .product-review {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-review__header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .product-review__author {
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }
        .product-review__rating {
            display: flex;
            gap: 0.125rem;
        }
        .product-review__date {
            color: #999;
            font-size: 0.875rem;
            margin-left: auto;
        }
        .product-review__title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.75rem;
        }
        .product-review__comment {
            color: #666;
            line-height: 1.6;
            margin: 0;
        }
        .product-review__form-wrapper {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .product-review__not-allowed {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .product-review-form .form-group {
            margin-bottom: 1.5rem;
        }
        .product-review-form label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .product-review-form input[type="text"],
        .product-review-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }
        .product-review-form textarea {
            resize: vertical;
            min-height: 120px;
        }
        .star-rating {
            display: flex;
            gap: 0.5rem;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #FFC107;
        }
        .star-rating input[type="radio"]:checked ~ label {
            color: #FFC107;
        }
        
        <!-- Avaliações - Fase 10 -->
        <section class="product-reviews">
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
                <div class="product-reviews__header">
                    <h2 class="section-title" style="font-size: 1.875rem; font-weight: 700; color: #333; margin-bottom: 1.5rem;">Avaliações do Produto</h2>
                    
                    <?php if ($avaliacoesResumo['total'] > 0): ?>
                        <div class="product-reviews__summary">
                            <span class="product-reviews__average">
                                <?= number_format($avaliacoesResumo['media'], 1, ',', '.'); ?> de 5
                            </span>
                            <div class="product-reviews__stars">
                                <?php
                                $mediaInteira = floor($avaliacoesResumo['media']);
                                $temMeia = ($avaliacoesResumo['media'] - $mediaInteira) >= 0.5;
                                for ($i = 1; $i <= 5; $i++):
                                    if ($i <= $mediaInteira):
                                ?>
                                    <i class="bi bi-star-fill" style="color: #FFC107; font-size: 1.5rem;"></i>
                                <?php elseif ($i == $mediaInteira + 1 && $temMeia): ?>
                                    <i class="bi bi-star-half" style="color: #FFC107; font-size: 1.5rem;"></i>
                                <?php else: ?>
                                    <i class="bi bi-star" style="color: #ddd; font-size: 1.5rem;"></i>
                                <?php endif; endfor; ?>
                            </div>
                            <span class="product-reviews__count">
                                <?= (int)$avaliacoesResumo['total']; ?> <?= $avaliacoesResumo['total'] === 1 ? 'avaliação' : 'avaliações'; ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <div class="product-reviews__summary" style="justify-content: center;">
                            <p style="color: #666; font-size: 1rem; margin: 0;">Ainda não há avaliações para este produto.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Lista de Avaliações - Fase 10 -->
                <?php if (!empty($avaliacoes)): ?>
                    <div class="product-reviews__list">
                        <?php foreach ($avaliacoes as $review): ?>
                            <article class="product-review">
                                <header class="product-review__header">
                                    <strong class="product-review__author">
                                        <?= htmlspecialchars($review['nome_cliente'] ?? 'Cliente'); ?>
                                    </strong>
                                    <div class="product-review__rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['nota']): ?>
                                                <i class="bi bi-star-fill" style="color: #FFC107; font-size: 1rem;"></i>
                                            <?php else: ?>
                                                <i class="bi bi-star" style="color: #ddd; font-size: 1rem;"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <time class="product-review__date">
                                        <?= date('d/m/Y', strtotime($review['created_at'])); ?>
                                    </time>
                                </header>
                                <?php if (!empty($review['titulo'])): ?>
                                    <h3 class="product-review__title">
                                        <?= htmlspecialchars($review['titulo']); ?>
                                    </h3>
                                <?php endif; ?>
                                <?php if (!empty($review['comentario'])): ?>
                                    <p class="product-review__comment">
                                        <?= nl2br(htmlspecialchars($review['comentario'])); ?>
                                    </p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Formulário de Avaliação -->
                <?php if ($reviewMessage): ?>
                    <div class="alert alert-<?= $reviewMessageType ?>" style="padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; <?= $reviewMessageType === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
                        <?= htmlspecialchars($reviewMessage) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$clientePodeAvaliar['permitido']): ?>
                    <div class="product-review__not-allowed">
                        <?php if ($clientePodeAvaliar['motivo'] === 'precisa estar logado'): ?>
                            <p style="color: #666; margin: 0 0 1rem 0; font-size: 1rem;">
                                <i class="bi bi-info-circle icon" style="margin-right: 0.5rem;"></i>
                                Faça <a href="<?= $basePath ?>/minha-conta/login?redirect=<?= urlencode($requestUri) ?>" style="color: var(--cor-primaria); text-decoration: none; font-weight: 600;">login</a> para avaliar este produto.
                            </p>
                        <?php elseif ($clientePodeAvaliar['motivo'] === 'ainda não comprou este produto'): ?>
                            <p style="color: #666; margin: 0; font-size: 1rem;">
                                <i class="bi bi-cart-x icon" style="margin-right: 0.5rem;"></i>
                                Você precisa ter comprado este produto para avaliá-lo.
                            </p>
                        <?php elseif ($clientePodeAvaliar['motivo'] === 'já avaliou'): ?>
                            <p style="color: #666; margin: 0; font-size: 1rem;">
                                <i class="bi bi-check-circle icon" style="margin-right: 0.5rem;"></i>
                                Você já avaliou este produto.
                            </p>
                        <?php else: ?>
                            <p style="color: #666; margin: 0; font-size: 1rem;">
                                Você não pode avaliar este produto no momento.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="product-review__form-wrapper">
                        <h3 style="font-size: 1.25rem; font-weight: 600; color: #333; margin-bottom: 1.5rem;">Deixe sua avaliação</h3>
                        <form method="POST" action="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>/avaliar" class="product-review-form">
                            <div class="form-group">
                                <label for="review-nota">Nota *</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" name="nota" id="star<?= $i ?>" value="<?= $i ?>" required>
                                        <label for="star<?= $i ?>">
                                            <i class="bi bi-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">Selecione uma nota de 1 a 5 estrelas</small>
                            </div>
                            <div class="form-group">
                                <label for="review-titulo">Título (opcional)</label>
                                <input type="text" id="review-titulo" name="titulo" maxlength="150" placeholder="Ex: Excelente produto">
                            </div>
                            <div class="form-group">
                                <label for="review-comentario">Comentário (opcional)</label>
                                <textarea id="review-comentario" name="comentario" rows="5" maxlength="5000" placeholder="Compartilhe sua experiência com este produto..."></textarea>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">Máximo de 5000 caracteres</small>
                            </div>
                            <button type="submit" style="background: var(--cor-primaria); color: white; padding: 0.875rem 2rem; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.2s;">
                                <i class="bi bi-send icon" style="margin-right: 0.5rem;"></i>
                                Enviar Avaliação
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Produtos Relacionados -->
        <?php if (!empty($produtosRelacionados)): ?>
            <div class="product-sections related-products">
                <h2 class="section-title">Produtos Relacionados</h2>
                <div class="related-grid">
                    <?php foreach ($produtosRelacionados as $prodRel): ?>
                        <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($prodRel['slug']) ?>" class="related-link">
                            <div class="related-card">
                                <?php if ($prodRel['imagem_principal']): ?>
                                    <img src="<?= $basePath ?>/<?= htmlspecialchars($prodRel['imagem_principal']['caminho_arquivo']) ?>" 
                                         alt="<?= htmlspecialchars($prodRel['imagem_principal']['alt_text'] ?? $prodRel['nome']) ?>"
                                         class="related-image">
                                <?php else: ?>
                                    <div class="related-image" style="display: flex; flex-direction: column; align-items: center; justify-content: center; color: #999; background: #f0f0f0;">
                                        <i class="bi bi-image icon" style="font-size: 2rem; color: #ccc; margin-bottom: 0.5rem;"></i>
                                        <span style="font-size: 0.875rem;">Sem imagem</span>
                                    </div>
                                <?php endif; ?>
                                <div class="related-info">
                                    <div class="related-name"><?= htmlspecialchars($prodRel['nome']) ?></div>
                                    <div class="related-price">
                                        <?php if ($prodRel['preco_promocional']): ?>
                                            <span style="text-decoration: line-through; color: #999; font-size: 0.9rem; margin-right: 0.5rem;">
                                                R$ <?= number_format($prodRel['preco_regular'], 2, ',', '.') ?>
                                            </span>
                                            <span>R$ <?= number_format($prodRel['preco_promocional'], 2, ',', '.') ?></span>
                                        <?php else: ?>
                                            R$ <?= number_format($prodRel['preco'] ?? $prodRel['preco_regular'], 2, ',', '.') ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function changeImage(imagePath, thumbnail) {
            var basePath = '<?= $basePath ?>';
            var mainImage = document.getElementById('mainImage');
            if (mainImage) {
                mainImage.src = basePath + '/' + imagePath;
            }
            // Remover active de todos os thumbnails
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            // Adicionar active no thumbnail clicado
            if (thumbnail) {
                thumbnail.classList.add('active');
            }
        }
        
        // Gerenciamento de vídeos na galeria
        (function() {
            const videoThumbnails = document.querySelectorAll('.thumbnail-wrapper--video');
            const modal = document.getElementById('product-video-modal');
            const modalContent = document.getElementById('product-video-modal-content');
            
            if (!modal || !modalContent) return;
            
            videoThumbnails.forEach(thumbWrapper => {
                thumbWrapper.addEventListener('click', function() {
                    const videoType = this.getAttribute('data-video-type');
                    const embedUrl = this.getAttribute('data-video-embed');
                    const videoUrl = this.getAttribute('data-video-url');
                    
                    // Remover active de todos os thumbnails
                    document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                    
                    // Adicionar active no thumbnail de vídeo clicado
                    const thumb = this.querySelector('.thumbnail');
                    if (thumb) {
                        thumb.classList.add('active');
                    }
                    
                    // Montar player HTML
                    let playerHtml = '';
                    
                    if (videoType === 'youtube' || videoType === 'vimeo') {
                        playerHtml = '<iframe src="' + embedUrl + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } else if (videoType === 'mp4') {
                        playerHtml = '<video controls autoplay style="width: 100%; height: 100%;"><source src="' + videoUrl + '" type="video/mp4">Seu navegador não suporta vídeo HTML5.</video>';
                    } else {
                        // Fallback: tentar como iframe ou link
                        playerHtml = '<iframe src="' + videoUrl + '" frameborder="0" allowfullscreen></iframe>';
                    }
                    
                    // Injetar player no modal e abrir
                    modalContent.innerHTML = playerHtml;
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                });
            });
        })();
        
        function showTab(tabName) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remover active de todos os botões
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Mostrar conteúdo selecionado
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Ativar botão selecionado
            event.target.classList.add('active');
        }
        
        // Gerenciamento de vídeos
        (function() {
            const modal = document.getElementById('product-video-modal');
            const modalContent = document.getElementById('product-video-modal-content');
            const videoItems = document.querySelectorAll('.product-videos__item');
            const closeButtons = document.querySelectorAll('[data-video-modal-close]');
            
            if (!modal || !modalContent) return;
            
            // Abrir modal ao clicar em um vídeo
            videoItems.forEach(item => {
                item.addEventListener('click', function() {
                    const videoType = this.getAttribute('data-video-type');
                    const embedUrl = this.getAttribute('data-video-embed');
                    const videoUrl = this.getAttribute('data-video-url');
                    
                    let playerHtml = '';
                    
                    if (videoType === 'youtube' || videoType === 'vimeo') {
                        playerHtml = '<iframe src="' + embedUrl + '?autoplay=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } else if (videoType === 'mp4') {
                        playerHtml = '<video controls autoplay style="width: 100%; height: 100%;"><source src="' + videoUrl + '" type="video/mp4">Seu navegador não suporta vídeo HTML5.</video>';
                    } else {
                        // Fallback: tentar como iframe ou link
                        playerHtml = '<iframe src="' + videoUrl + '" frameborder="0" allowfullscreen></iframe>';
                    }
                    
                    modalContent.innerHTML = playerHtml;
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.style.overflow = 'hidden';
                });
            });
            
            // Fechar modal
            function closeModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                modalContent.innerHTML = '';
                document.body.style.overflow = '';
            }
            
            closeButtons.forEach(button => {
                button.addEventListener('click', closeModal);
            });
            
            // Fechar com ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        })();

        // Estrelas clicáveis no formulário de avaliação
        (function() {
            const starInputs = document.querySelectorAll('.star-rating input[type="radio"]');
            const starLabels = document.querySelectorAll('.star-rating label');
            
            starInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    const value = parseInt(this.value);
                    starLabels.forEach((label, labelIndex) => {
                        const labelValue = 5 - labelIndex;
                        if (labelValue <= value) {
                            label.style.color = '#FFC107';
                            label.querySelector('i').classList.remove('bi-star');
                            label.querySelector('i').classList.add('bi-star-fill');
                        } else {
                            label.style.color = '#ddd';
                            label.querySelector('i').classList.remove('bi-star-fill');
                            label.querySelector('i').classList.add('bi-star');
                        }
                    });
                });
            });

            starLabels.forEach(label => {
                label.addEventListener('mouseenter', function() {
                    const value = parseInt(this.previousElementSibling.value);
                    starLabels.forEach((l, index) => {
                        const labelValue = 5 - index;
                        if (labelValue <= value) {
                            l.style.color = '#FFC107';
                        }
                    });
                });
            });
        })();
    </script>
</body>
</html>
