<?php
// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}

// Base path
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

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
            <div class="cart-layout">
                <!-- Coluna Esquerda: Itens do Carrinho -->
                <div class="cart-items-section">
                    <h2 class="cart-section-title">Seu Carrinho</h2>
                    <div class="cart-items">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="cart-item">
                                <div class="item-thumb">
                                    <?php if ($item['imagem']): ?>
                                        <img src="<?= media_url($item['imagem']) ?>" 
                                             alt="<?= htmlspecialchars($item['nome']) ?>"
                                             class="item-image">
                                    <?php else: ?>
                                        <div class="item-image-placeholder">
                                            <i class="bi bi-image icon"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-info">
                                    <div class="item-top">
                                        <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($item['slug']) ?>" class="item-title">
                                            <?= htmlspecialchars($item['nome']) ?>
                                        </a>
                                    </div>
                                    <div class="item-unit-price">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></div>
                                    
                                    <div class="item-actions-row">
                                        <div class="qty-stepper">
                                            <form method="POST" action="<?= $basePath ?>/carrinho/atualizar" class="qty-form">
                                                <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                                <button type="button" class="qty-btn qty-decrease" data-produto-id="<?= $item['produto_id'] ?>" aria-label="Diminuir quantidade">−</button>
                                                <input type="number" 
                                                       name="quantidade" 
                                                       value="<?= $item['quantidade'] ?>" 
                                                       min="1" 
                                                       class="qty-input"
                                                       readonly
                                                       aria-label="Quantidade">
                                                <button type="button" class="qty-btn qty-increase" data-produto-id="<?= $item['produto_id'] ?>" aria-label="Aumentar quantidade">+</button>
                                            </form>
                                        </div>
                                        <form method="POST" action="<?= $basePath ?>/carrinho/remover" class="remove-form">
                                            <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                            <button type="submit" class="remove-btn" aria-label="Remover produto">
                                                <i class="bi bi-trash icon"></i> Remover
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="item-total">
                                    R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>
                
                    <div class="cart-continue-shopping">
                    <a href="<?= $basePath ?>/produtos" class="btn btn-secondary">
                        <i class="bi bi-arrow-left icon"></i>
                        Continuar Comprando
                    </a>
                    </div>
                </div>
                
                <!-- Coluna Direita: Resumo do Pedido (Sticky) -->
                <div class="order-summary">
                    <h3 class="summary-title">Resumo do Pedido</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-row small">
                        <span>Desconto</span>
                        <span>R$ 0,00</span>
                    </div>
                    
                    <!-- Seção de Frete -->
                    <div class="shipping-section">
                        <div class="summary-row">
                            <span>Frete</span>
                            <span id="shipping-display">—</span>
                        </div>
                        
                        <div class="shipping-zip">
                            <input type="text" 
                                   id="shipping-cep" 
                                   placeholder="00000-000" 
                                   maxlength="9"
                                   inputmode="numeric"
                                   class="shipping-cep-input"
                                   aria-label="CEP para cálculo de frete">
                            <button type="button" 
                                    id="calculate-shipping-btn" 
                                    class="shipping-calc-btn">
                                Calcular
                            </button>
                        </div>
                        
                        <div id="shipping-error" class="shipping-error" style="display: none;"></div>
                        
                        <div id="shipping-results" style="display: none;">
                            <div id="shipping-loading" class="shipping-loading" style="display: none;">
                                <i class="bi bi-hourglass-split icon"></i> Calculando...
                            </div>
                            <div id="shipping-options" class="shipping-options" style="display: none;">
                                <!-- Opções serão inseridas aqui via JavaScript -->
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-total">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="cart-total" aria-live="polite">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <a href="<?= $basePath ?>/checkout" class="checkout-btn" id="checkout-btn-desktop">
                        Finalizar Compra
                        <i class="bi bi-arrow-right icon"></i>
                    </a>
                    
                    <p class="summary-note">
                        <small>Frete calculado no checkout • Pagamento seguro</small>
                    </p>
                </div>
            </div>
            
            <!-- Barra Fixa Mobile (apenas no mobile) -->
            <div class="mobile-checkout-bar">
                <div class="mobile-total">
                    <span class="mobile-total-label">Total:</span>
                    <span class="mobile-total-value" id="mobile-cart-total" aria-live="polite">R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                <a href="<?= $basePath ?>/checkout" class="mobile-checkout-btn" id="checkout-btn-mobile">
                    Finalizar Compra
                    <i class="bi bi-arrow-right icon"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS específico da página do carrinho - Layout E-commerce Profissional
$additionalStyles = '
    body {
        background: #f5f5f5;
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
            padding: 24px 0;
            background-color: #f5f5f5;
            padding-bottom: 90px; /* Espaço para barra fixa mobile */
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
        
        /* Layout Grid 2 Colunas (Desktop) */
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            align-items: start;
        }
        
        /* Seção de Itens do Carrinho */
        .cart-items-section {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .cart-section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 8px 0;
        }
        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        /* Card do Item */
        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr 140px;
            gap: 16px;
            padding: 16px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(0,0,0,.08);
            align-items: center;
        }
        
        /* Thumbnail Quadrada Fixa */
        .item-thumb {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(0,0,0,.04);
            flex: 0 0 auto;
        }
        .item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .item-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,.04);
            color: #999;
        }
        .item-image-placeholder .icon {
            font-size: 1.5rem;
            color: #ccc;
        }
        
        /* Informações do Item */
        .item-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .item-top {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .item-title {
            font-weight: 700;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #333;
            text-decoration: none;
            font-size: 0.95rem;
        }
        .item-title:hover {
            color: var(--pg-color-primary);
            text-decoration: underline;
        }
        .item-unit-price {
            font-size: 0.875rem;
            color: #666;
            opacity: 0.85;
        }
        
        /* Stepper Horizontal */
        .item-actions-row {
            margin-top: 4px;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }
        .qty-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(0,0,0,.15);
            border-radius: 10px;
            overflow: hidden;
            height: 36px;
            background: #fff;
        }
        .qty-btn {
            width: 36px;
            height: 36px;
            border: 0;
            background: rgba(0,0,0,.04);
            cursor: pointer;
            font-weight: 900;
            font-size: 1.1rem;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .qty-btn:hover {
            background: rgba(0,0,0,.08);
        }
        .qty-btn:active {
            background: rgba(0,0,0,.12);
        }
        .qty-input {
            width: 44px;
            height: 36px;
            border: 0;
            text-align: center;
            outline: none;
            background: #fff;
            font-size: 0.95rem;
            font-weight: 600;
        }
        
        /* Botão Remover */
        .remove-btn {
            border: 0;
            background: transparent;
            color: #d11a2a;
            cursor: pointer;
            font-weight: 700;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            transition: opacity 0.2s;
        }
        .remove-btn:hover {
            opacity: 0.8;
        }
        .remove-btn .icon {
            font-size: 0.9rem;
        }
        
        /* Total do Item */
        .item-total {
            text-align: right;
            font-weight: 800;
            font-size: 18px;
            color: #333;
        }
        
        .cart-continue-shopping {
            margin-top: 8px;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        /* Resumo do Pedido (Sticky) */
        .order-summary {
            position: sticky;
            top: 16px;
            padding: 20px;
            border-radius: 12px;
            background: #fff;
            border: 1px solid rgba(0,0,0,.08);
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }
        .summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 16px 0;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 0.95rem;
        }
        .summary-row.small {
            opacity: 0.85;
            font-size: 0.875rem;
        }
        
        /* Seção de Frete */
        .shipping-section {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,.08);
        }
        #shipping-display {
            color: #666;
        }
        .shipping-zip {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            margin-top: 10px;
        }
        .shipping-cep-input {
            height: 40px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.15);
            padding: 0 12px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .shipping-cep-input:focus {
            outline: none;
            border-color: var(--pg-color-primary);
            box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
        }
        .shipping-calc-btn {
            height: 40px;
            border-radius: 10px;
            border: 0;
            padding: 0 14px;
            cursor: pointer;
            background: var(--pg-color-primary);
            color: white;
            font-weight: 600;
            font-size: 14px;
            white-space: nowrap;
            transition: opacity 0.2s;
        }
        .shipping-calc-btn:hover:not(:disabled) {
            opacity: 0.9;
        }
        .shipping-calc-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .shipping-error {
            margin-top: 8px;
            padding: 8px 12px;
            font-size: 0.875rem;
            color: #dc3545;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
        }
        .shipping-loading {
            text-align: center;
            padding: 12px;
            color: #666;
            font-size: 0.9rem;
        }
        .shipping-options {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .shipping-option {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.10);
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        .shipping-option:hover {
            background: rgba(2, 58, 141, 0.05);
            border-color: var(--pg-color-primary);
        }
        .shipping-option.selected {
            background: #f0f7ff;
            border-color: var(--pg-color-primary);
        }
        .shipping-option-label {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .shipping-option-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        .shipping-option-prazo {
            font-size: 0.85rem;
            color: #666;
        }
        .shipping-option-price {
            font-weight: 700;
            color: var(--pg-color-primary);
            font-size: 1.1rem;
            white-space: nowrap;
        }
        
        /* Total */
        .summary-total {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,.08);
        }
        .summary-total .total-label {
            opacity: 0.85;
            font-size: 0.95rem;
        }
        .summary-total .total-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--pg-color-primary);
        }
        
        /* Botão Checkout */
        .checkout-btn {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 0;
            cursor: pointer;
            font-weight: 800;
            margin-top: 16px;
            background: var(--pg-color-secondary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            font-size: 1rem;
            transition: opacity 0.2s, transform 0.2s;
        }
        .checkout-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .summary-note {
            margin-top: 12px;
            text-align: center;
            color: #666;
            font-size: 0.8rem;
        }
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
        }
        
        /* Barra Fixa Mobile */
        .mobile-checkout-bar {
            display: none;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            padding: 12px 16px;
            padding-bottom: calc(12px + env(safe-area-inset-bottom));
            background: #fff;
            border-top: 1px solid rgba(0,0,0,.10);
            box-shadow: 0 -2px 8px rgba(0,0,0,.1);
            z-index: 999;
            display: flex;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }
        .mobile-total {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .mobile-total-label {
            font-size: 0.875rem;
            color: #666;
        }
        .mobile-total-value {
            font-weight: 900;
            font-size: 1.25rem;
            color: var(--pg-color-primary);
        }
        .mobile-checkout-btn {
            height: 44px;
            border-radius: 12px;
            border: 0;
            padding: 0 20px;
            font-weight: 900;
            cursor: pointer;
            background: var(--pg-color-secondary);
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.95rem;
            transition: opacity 0.2s;
        }
        .mobile-checkout-btn:hover {
            opacity: 0.9;
        }
        
        /* Responsivo Desktop */
        @media (min-width: 1024px) {
            .mobile-checkout-bar {
                display: none !important;
            }
        }
        
        /* Responsivo Tablet */
        @media (max-width: 1023px) and (min-width: 768px) {
            .cart-layout {
                gap: 20px;
            }
            .mobile-checkout-bar {
                display: none !important;
            }
        }
        
        /* Responsivo Mobile */
        @media (max-width: 767px) {
            .pg-cart-banner .pg-container {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            .pg-cart-title {
                font-size: 20px;
            }
            .pg-container {
                padding: 0 1rem;
            }
            .cart-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .order-summary {
                position: static;
            }
            .cart-item {
                grid-template-columns: 1fr;
                gap: 12px;
                align-items: start;
            }
            .item-thumb {
                width: 72px;
                height: 72px;
            }
            .item-top {
                display: grid;
                grid-template-columns: 72px 1fr;
                gap: 12px;
                align-items: start;
            }
            .item-info {
                grid-column: 1 / -1;
                margin-top: 0;
            }
            .item-actions-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 8px;
            }
            .item-total {
                text-align: right;
                font-size: 16px;
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid rgba(0,0,0,.08);
            }
            .shipping-zip {
                grid-template-columns: 1fr;
            }
            .shipping-calc-btn {
                width: 100%;
            }
            .shipping-option {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            .shipping-option-price {
                align-self: flex-end;
            }
            .mobile-checkout-bar {
                display: flex !important;
            }
            #checkout-btn-desktop {
                display: none;
            }
        }
    }
';

// Scripts adicionais
$additionalScripts = '
    <script>
        (function() {
            const STORAGE_KEY = "cart_shipping_cep";
            const subtotal = ' . $subtotal . ';
            
            // Elementos DOM
            const cepInput = document.getElementById("shipping-cep");
            const calculateBtn = document.getElementById("calculate-shipping-btn");
            const resultsDiv = document.getElementById("shipping-results");
            const loadingDiv = document.getElementById("shipping-loading");
            const optionsDiv = document.getElementById("shipping-options");
            const errorDiv = document.getElementById("shipping-error");
            const shippingDisplay = document.getElementById("shipping-display");
            const cartTotalSpan = document.getElementById("cart-total");
            const mobileCartTotalSpan = document.getElementById("mobile-cart-total");
            
            let selectedShipping = null;
            
            // Máscara de CEP
            function maskCEP(value) {
                value = value.replace(/\D/g, "");
                if (value.length > 5) {
                    value = value.substring(0, 5) + "-" + value.substring(5, 8);
                }
                return value;
            }
            
            // Carregar CEP salvo do localStorage
            function loadSavedCEP() {
                if (!cepInput) return;
                const savedCEP = localStorage.getItem(STORAGE_KEY);
                if (savedCEP) {
                    cepInput.value = savedCEP;
                }
            }
            
            // Salvar CEP no localStorage
            function saveCEP(cep) {
                if (cep) {
                    localStorage.setItem(STORAGE_KEY, cep);
                }
            }
            
            // Stepper de Quantidade
            document.querySelectorAll(".qty-decrease").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    const form = this.closest(".qty-form");
                    const input = form.querySelector(".qty-input");
                    const currentValue = parseInt(input.value) || 1;
                    if (currentValue > 1) {
                        input.value = currentValue - 1;
                        form.submit();
                    }
                });
            });
            
            document.querySelectorAll(".qty-increase").forEach(function(btn) {
                btn.addEventListener("click", function() {
                    const form = this.closest(".qty-form");
                    const input = form.querySelector(".qty-input");
                    const currentValue = parseInt(input.value) || 1;
                    input.value = currentValue + 1;
                    form.submit();
                });
            });
            
            // Aplicar máscara ao input CEP
            if (cepInput) {
                cepInput.addEventListener("input", function(e) {
                    e.target.value = maskCEP(e.target.value);
                });
                
                // Permitir Enter para calcular
                cepInput.addEventListener("keypress", function(e) {
                    if (e.key === "Enter") {
                        e.preventDefault();
                        calculateShipping();
                    }
                });
            }
            
            // Atualizar total
            function updateTotal(freteValue = 0) {
                const total = subtotal + freteValue;
                const formattedTotal = "R$ " + formatMoney(total);
                if (cartTotalSpan) cartTotalSpan.textContent = formattedTotal;
                if (mobileCartTotalSpan) mobileCartTotalSpan.textContent = formattedTotal;
            }
            
            // Calcular frete
            async function calculateShipping() {
                if (!cepInput || !calculateBtn) return;
                
                const cep = cepInput.value.replace(/\D/g, "");
                
                if (cep.length !== 8) {
                    showError("CEP deve conter 8 dígitos");
                    return;
                }
                
                // Salvar CEP
                saveCEP(cepInput.value);
                
                // Desabilitar botão e mostrar "Validando..."
                const originalBtnText = calculateBtn.textContent;
                calculateBtn.disabled = true;
                calculateBtn.textContent = "Validando...";
                
                // Ocultar resultados anteriores
                if (resultsDiv) resultsDiv.style.display = "none";
                if (errorDiv) errorDiv.style.display = "none";
                
                // Mostrar loading
                if (resultsDiv) resultsDiv.style.display = "block";
                if (loadingDiv) loadingDiv.style.display = "block";
                if (optionsDiv) optionsDiv.style.display = "none";
                
                try {
                    // Primeiro, validar se todos os produtos têm peso/dimensões
                    const validateResponse = await fetch("' . $basePath . '/api/shipping/validate", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({})
                    });
                    
                    // Verificar se resposta é JSON
                    const validateContentType = validateResponse.headers.get("content-type") || "";
                    let validateData;
                    
                    if (validateContentType.includes("application/json")) {
                        validateData = await validateResponse.json();
                    } else {
                        const text = await validateResponse.text();
                        console.error("Shipping API validate non-JSON response:", text.slice(0, 500));
                        
                        if (loadingDiv) loadingDiv.style.display = "none";
                        calculateBtn.disabled = false;
                        calculateBtn.textContent = originalBtnText;
                        
                        if (validateResponse.status >= 500) {
                            showError("Erro interno ao validar frete. Tente novamente.");
                        } else {
                            showError("Erro ao validar frete. Verifique os logs.");
                        }
                        return;
                    }
                    
                    // Se validação falhou, mostrar produtos faltando e não calcular
                    if (!validateData.valido) {
                        if (loadingDiv) loadingDiv.style.display = "none";
                        calculateBtn.disabled = false;
                        calculateBtn.textContent = originalBtnText;
                        
                        showProductsMissingError(validateData.produtos_faltando || []);
                        return;
                    }
                    
                    // Se validação passou, calcular frete
                    calculateBtn.textContent = "Calculando...";
                    
                    const response = await fetch("' . $basePath . '/api/shipping/calculate", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({ cepDestino: cep })
                    });
                    
                    // Verificar se resposta é JSON
                    const contentType = response.headers.get("content-type") || "";
                    let data;
                    
                    if (contentType.includes("application/json")) {
                        data = await response.json();
                    } else {
                        const text = await response.text();
                        console.error("Shipping API calculate non-JSON response:", text.slice(0, 500));
                        
                        if (loadingDiv) loadingDiv.style.display = "none";
                        calculateBtn.disabled = false;
                        calculateBtn.textContent = originalBtnText;
                        
                        if (response.status >= 500) {
                            showError("Erro interno ao calcular frete. Tente novamente.");
                        } else {
                            showError("Erro ao calcular frete. Verifique os logs.");
                        }
                        return;
                    }
                    
                    if (loadingDiv) loadingDiv.style.display = "none";
                    
                    // Reabilitar botão
                    calculateBtn.disabled = false;
                    calculateBtn.textContent = originalBtnText;
                    
                    // Verificar status HTTP
                    if (response.status >= 500) {
                        showError("Erro interno ao calcular frete. Tente novamente.");
                        return;
                    }
                    
                    if (data.success && data.opcoes && data.opcoes.length > 0) {
                        showOptions(data.opcoes);
                    } else {
                        // Se houver produtos faltando na resposta, mostrar lista
                        if (data.produtos_faltando && data.produtos_faltando.length > 0) {
                            showProductsMissingError(data.produtos_faltando);
                        } else {
                            // Exibir errors[0] se existir, senão usar mensagem padrão
                            let errorMessage = data.message || "Não foi possível calcular o frete. Verifique o CEP e tente novamente.";
                            if (data.errors && data.errors.length > 0) {
                                errorMessage = data.errors[0];
                            }
                            showError(errorMessage);
                        }
                    }
                } catch (error) {
                    if (loadingDiv) loadingDiv.style.display = "none";
                    
                    // Reabilitar botão
                    calculateBtn.disabled = false;
                    calculateBtn.textContent = originalBtnText;
                    
                    showError("Erro ao calcular frete. Tente novamente.");
                    console.error("Erro:", error);
                }
            }
            
            // Mostrar erro com produtos faltando
            function showProductsMissingError(produtosFaltando) {
                if (!errorDiv) return;
                
                let message = "Não é possível calcular o frete porque alguns produtos não possuem peso/dimensões cadastrados.";
                
                if (produtosFaltando && produtosFaltando.length > 0) {
                    message += "<br><br><strong>Produtos com dados faltando:</strong><ul style=\"margin: 8px 0 0 0; padding-left: 20px;\">";
                    produtosFaltando.forEach(function(produto) {
                        message += "<li>" + escapeHtml(produto.nome || "Produto #" + produto.produto_id) + "</li>";
                    });
                    message += "</ul>";
                    message += "<br><small>Entre em contato com o suporte para finalizar sua compra.</small>";
                }
                
                errorDiv.innerHTML = message;
                errorDiv.style.display = "block";
                if (resultsDiv) resultsDiv.style.display = "block";
            }
            
            // Mostrar opções de frete
            function showOptions(opcoes) {
                if (!optionsDiv) return;
                optionsDiv.innerHTML = "";
                
                opcoes.forEach(function(opcao) {
                    const optionDiv = document.createElement("label");
                    optionDiv.className = "shipping-option";
                    optionDiv.style.cursor = "pointer";
                    
                    // Formatar prazo
                    let prazoText = opcao.prazo || "A consultar";
                    if (typeof prazoText === "number") {
                        prazoText = prazoText === 1 ? "1 dia útil" : prazoText + " dias úteis";
                    }
                    
                    const radio = document.createElement("input");
                    radio.type = "radio";
                    radio.name = "shipping_option";
                    radio.value = opcao.codigo;
                    radio.style.display = "none";
                    radio.addEventListener("change", function() {
                        selectShipping(opcao);
                    });
                    
                    optionDiv.appendChild(radio);
                    
                    optionDiv.innerHTML = `
                        <div class="shipping-option-label">
                            <div class="shipping-option-name">${escapeHtml(opcao.servico)}</div>
                            <div class="shipping-option-prazo">até ${escapeHtml(prazoText)}</div>
                        </div>
                        <div class="shipping-option-price">R$ ${formatMoney(opcao.preco)}</div>
                    `;
                    
                    optionDiv.insertBefore(radio, optionDiv.firstChild);
                    optionDiv.addEventListener("click", function(e) {
                        if (e.target !== radio) {
                            radio.checked = true;
                            radio.dispatchEvent(new Event("change"));
                        }
                    });
                    
                    optionsDiv.appendChild(optionDiv);
                });
                
                optionsDiv.style.display = "block";
                if (resultsDiv) resultsDiv.style.display = "block";
            }
            
            // Selecionar frete
            function selectShipping(opcao) {
                selectedShipping = opcao;
                
                // Marcar visualmente
                document.querySelectorAll(".shipping-option").forEach(function(el) {
                    el.classList.remove("selected");
                });
                const selectedOption = document.querySelector(`input[value="${opcao.codigo}"]`);
                if (selectedOption && selectedOption.closest(".shipping-option")) {
                    selectedOption.closest(".shipping-option").classList.add("selected");
                }
                
                // Atualizar display do frete
                if (shippingDisplay) {
                    shippingDisplay.textContent = opcao.servico + " - R$ " + formatMoney(opcao.preco);
                }
                
                // Atualizar total
                updateTotal(opcao.preco);
            }
            
            // Mostrar erro
            function showError(message) {
                if (!errorDiv) return;
                errorDiv.innerHTML = escapeHtml(message);
                errorDiv.style.display = "block";
                if (resultsDiv) resultsDiv.style.display = "none";
            }
            
            // Utilitários
            function formatMoney(value) {
                return parseFloat(value).toFixed(2).replace(".", ",").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            }
            
            function escapeHtml(text) {
                const div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Event listeners
            if (calculateBtn) {
                calculateBtn.addEventListener("click", calculateShipping);
            }
            
            // Carregar CEP salvo ao carregar página
            loadSavedCEP();
            
            // Inicializar total
            updateTotal(0);
        })();
    </script>
';

// Configurar variáveis para o layout base
$pageTitle = 'Carrinho de Compras – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
