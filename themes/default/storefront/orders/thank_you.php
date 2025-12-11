<?php
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

<div class="thank-you-header-banner">
    <div class="thank-you-container">
        <h1>Pedido Confirmado</h1>
        <a href="<?= $basePath ?>/"><i class="bi bi-arrow-left icon"></i> Voltar à Home</a>
    </div>
</div>

<div class="thank-you-container">
    <div class="success-box">
        <h1><i class="bi bi-check-circle-fill icon" style="color: #28a745; font-size: 2rem;"></i> Pedido Recebido!</h1>
        <p>Obrigado pela sua compra. Seu pedido foi registrado com sucesso.</p>
    </div>
    
    <div class="order-info">
        <h3 class="info-title">Informações do Pedido</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Número do Pedido</span>
                <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: var(--pg-color-primary);">
                    <?= htmlspecialchars($pedido['numero_pedido']) ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                    <?php
                    echo \App\Support\LangHelper::orderStatusLabel($pedido['status']);
                    ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Data</span>
                <span class="info-value">
                    <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Total</span>
                <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: var(--pg-color-primary);">
                    R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                </span>
            </div>
        </div>
    </div>
    
    <div class="order-info">
        <h3 class="info-title">Itens do Pedido</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Quantidade</th>
                    <th>Preço Unitário</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($item['total_linha'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 1rem; text-align: right;">
            <div style="display: inline-block; text-align: left;">
                <div style="margin-bottom: 0.5rem;">
                    <strong>Subtotal:</strong> R$ <?= number_format($pedido['total_produtos'], 2, ',', '.') ?>
                </div>
                <div style="margin-bottom: 0.5rem;">
                    <strong>Frete:</strong> R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?>
                </div>
                <div style="font-size: 1.2rem; font-weight: 700; color: var(--pg-color-primary);">
                    <strong>Total:</strong> R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="order-info">
        <h3 class="info-title">Endereço de Entrega</h3>
        <p>
            <?= htmlspecialchars($pedido['entrega_logradouro']) ?>, 
            <?= htmlspecialchars($pedido['entrega_numero']) ?>
            <?php if ($pedido['entrega_complemento']): ?>
                - <?= htmlspecialchars($pedido['entrega_complemento']) ?>
            <?php endif; ?><br>
            <?= htmlspecialchars($pedido['entrega_bairro']) ?> - 
            <?= htmlspecialchars($pedido['entrega_cidade']) ?>/<?= htmlspecialchars($pedido['entrega_estado']) ?><br>
            CEP: <?= htmlspecialchars($pedido['entrega_cep']) ?>
        </p>
    </div>
    
    <?php if ($pedido['metodo_pagamento'] === 'manual_pix'): ?>
        <div class="order-info">
            <h3 class="info-title">Instruções de Pagamento</h3>
            <div class="payment-instructions">
                <p><strong>Método:</strong> PIX / Transferência</p>
                <p><?= htmlspecialchars($instrucoesPagamento) ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="text-align: center;">
        <a href="<?= $basePath ?>/produtos" class="btn">Continuar Comprando</a>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS específico da página de confirmação de pedido
$additionalStyles = '
    body {
        background: #f5f5f5;
    }
    
    .thank-you-header-banner {
        background: var(--pg-color-primary);
        color: white;
        padding: 1rem 2rem;
        margin-bottom: 2rem;
    }
    .thank-you-header-banner .thank-you-container {
        max-width: 800px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .thank-you-header-banner h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    .thank-you-header-banner a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .thank-you-header-banner a:hover {
        text-decoration: underline;
    }
    
    .thank-you-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 1rem;
    }
    .success-box {
        background: #d4edda;
        border: 2px solid #28a745;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
    }
    .success-box h1 {
        color: #155724;
        margin-bottom: 0.5rem;
    }
    .success-box p {
        color: #155724;
        font-size: 1.1rem;
    }
    .order-info {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .info-title {
        font-size: 1.25rem;
        margin-bottom: 1rem;
        color: #333;
        border-bottom: 2px solid var(--pg-color-primary);
        padding-bottom: 0.5rem;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
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
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }
    .items-table th,
    .items-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    .items-table th {
        background: #f8f8f8;
        font-weight: 600;
    }
    .payment-instructions {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 4px;
        padding: 1rem;
        margin-top: 1rem;
    }
    .btn {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: var(--pg-color-primary);
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-top: 1rem;
        transition: background 0.2s, transform 0.2s;
    }
    .btn:hover {
        background: var(--pg-color-primary);
        opacity: 0.9;
        transform: translateY(-1px);
    }
    
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        .thank-you-header-banner {
            padding: 1rem 1.5rem;
        }
    }
';

// Scripts adicionais
$additionalScripts = '';

// Configurar variáveis para o layout base
$pageTitle = 'Pedido Confirmado – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
