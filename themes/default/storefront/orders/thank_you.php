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
    $loja = ['nome' => $tenant->name ?? 'Loja'];
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

    <?php 
    $paymentDetails = $paymentDetails ?? [];
    if (($paymentDetails['tipo'] ?? '') === 'cielo_pix'): 
        $qrBase64 = $paymentDetails['qr_code_base64'] ?? null;
        $qrString = $paymentDetails['qr_code_string'] ?? null;
        $mensagem = $paymentDetails['mensagem'] ?? 'Escaneie o QR Code ou copie o código PIX para pagar.';
    ?>
        <div class="order-info">
            <h3 class="info-title">Pagamento PIX (Cielo)</h3>
            <div class="payment-instructions">
                <p><?= htmlspecialchars($mensagem) ?></p>
                <?php if ($qrBase64): ?>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: white; border-radius: 8px; display: inline-block;">
                        <img src="data:image/png;base64,<?= htmlspecialchars($qrBase64) ?>" alt="QR Code PIX" style="max-width: 256px; height: auto;" />
                    </div>
                <?php endif; ?>
                <?php if ($qrString): ?>
                    <div style="margin-top: 1rem;">
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">Código PIX (copiar e colar):</label>
                        <textarea readonly rows="4" style="width: 100%; padding: 0.75rem; font-family: monospace; font-size: 0.85rem; border: 1px solid #ddd; border-radius: 4px;"><?= htmlspecialchars($qrString) ?></textarea>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
    $customerNeedPassword = $customerNeedPassword ?? false;
    $passwordCreated = $_SESSION['password_created'] ?? false;
    $passwordErrors = $_SESSION['password_errors'] ?? [];
    unset($_SESSION['password_created'], $_SESSION['password_errors']);
    ?>

    <?php if ($passwordCreated): ?>
        <div class="order-info" style="border-left: 4px solid #28a745;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                <i class="bi bi-check-circle-fill" style="color: #28a745; font-size: 1.5rem;"></i>
                <h3 class="info-title" style="border: none; margin: 0; padding: 0;">Senha criada com sucesso!</h3>
            </div>
            <p style="color: #555; margin-bottom: 1rem;">Agora você pode acompanhar seus pedidos a qualquer momento.</p>
            <a href="<?= $basePath ?>/minha-conta" style="display: inline-block; padding: 0.75rem 1.5rem; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
                <i class="bi bi-person-circle" style="margin-right: 0.5rem;"></i>
                Acessar Minha Conta
            </a>
        </div>
    <?php elseif ($customerNeedPassword): ?>
        <div class="order-info" style="border-left: 4px solid #F7931E;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <i class="bi bi-shield-lock" style="color: #F7931E; font-size: 1.5rem;"></i>
                <h3 class="info-title" style="border: none; margin: 0; padding: 0;">Crie sua senha para acompanhar o pedido</h3>
            </div>
            <p style="color: #555; margin-bottom: 1rem; font-size: 0.95rem;">
                Defina uma senha para acessar sua conta e acompanhar o status do pedido, rastreamento e histórico de compras.
            </p>
            <?php if (!empty($passwordErrors)): ?>
                <div style="background: #fff3f3; border: 1px solid #f5c6cb; border-radius: 6px; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                    <?php foreach ($passwordErrors as $err): ?>
                        <p style="margin: 0; color: #721c24; font-size: 0.9rem;"><?= htmlspecialchars($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?= $basePath ?>/pedido/criar-senha" style="max-width: 400px;">
                <input type="hidden" name="numero_pedido" value="<?= htmlspecialchars($pedido['numero_pedido']) ?>">
                <div style="margin-bottom: 1rem;">
                    <label for="password" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Senha *</label>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 1rem;">
                    <label for="password_confirm" style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Confirmar Senha *</label>
                    <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="Repita a senha"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; box-sizing: border-box;">
                </div>
                <button type="submit" style="width: 100%; padding: 0.75rem; background: #F7931E; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 1rem; cursor: pointer;">
                    <i class="bi bi-check-lg" style="margin-right: 0.5rem;"></i>
                    Criar Senha e Acessar Minha Conta
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="order-info" style="border-left: 4px solid #023A8D;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <i class="bi bi-person-circle" style="color: #023A8D; font-size: 1.5rem;"></i>
                <h3 class="info-title" style="border: none; margin: 0; padding: 0;">Acompanhe seu pedido</h3>
            </div>
            <p style="color: #555; margin-bottom: 1rem; font-size: 0.95rem;">
                Acesse sua conta para acompanhar o status, rastreamento e histórico de compras.
            </p>
            <a href="<?= $basePath ?>/minha-conta/pedidos" style="display: inline-block; padding: 0.75rem 1.5rem; background: #023A8D; color: white; text-decoration: none; border-radius: 6px; font-weight: 600;">
                <i class="bi bi-box-seam" style="margin-right: 0.5rem;"></i>
                Acompanhar Pedido
            </a>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 1rem;">
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
