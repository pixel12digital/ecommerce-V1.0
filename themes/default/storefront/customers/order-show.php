<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$pedido = $pedido ?? [];
$itens = $itens ?? [];
?>
<?php ob_start(); ?>

<?php
// Timeline do pedido
$statusOrder = ['pending', 'paid', 'shipped', 'completed'];
$statusLabels = [
    'pending'   => 'Pedido Realizado',
    'paid'      => 'Pagamento Confirmado',
    'shipped'   => 'Enviado',
    'completed' => 'Entregue',
];
$statusIcons = [
    'pending'   => 'bi-bag-check',
    'paid'      => 'bi-credit-card-2-front',
    'shipped'   => 'bi-truck',
    'completed' => 'bi-house-check',
];
$currentStatus = $pedido['status'];
$isCanceled = ($currentStatus === 'canceled');
$currentIdx = array_search($currentStatus, $statusOrder);
if ($currentIdx === false) $currentIdx = -1;
?>

<div class="content-header">
    <h1>Pedido #<?= htmlspecialchars($pedido['numero_pedido']) ?></h1>
    <p style="color: #666; margin-top: 0.5rem;">
        Realizado em <?= date('d/m/Y \à\s H:i', strtotime($pedido['created_at'])) ?>
    </p>
</div>

<?php if ($isCanceled): ?>
    <div style="background: #fce4ec; border-left: 4px solid #c62828; border-radius: 8px; padding: 1.25rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <i class="bi bi-x-circle-fill" style="font-size: 1.5rem; color: #c62828;"></i>
            <div>
                <strong style="color: #c62828; font-size: 1.1rem;">Pedido Cancelado</strong>
                <p style="margin: 0.25rem 0 0; color: #666; font-size: 0.9rem;">Este pedido foi cancelado.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Timeline Visual -->
    <div class="order-timeline" style="background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
        <div style="display: flex; justify-content: space-between; position: relative; padding: 0 0.5rem;">
            <!-- Linha de fundo -->
            <div style="position: absolute; top: 20px; left: 40px; right: 40px; height: 3px; background: #e0e0e0; z-index: 0;"></div>
            <!-- Linha de progresso -->
            <?php
            $progressPercent = 0;
            if ($currentIdx >= 0) {
                $progressPercent = ($currentIdx / (count($statusOrder) - 1)) * 100;
            }
            ?>
            <div style="position: absolute; top: 20px; left: 40px; width: calc((100% - 80px) * <?= $progressPercent ?> / 100); height: 3px; background: #2E7D32; z-index: 1; transition: width 0.5s;"></div>

            <?php foreach ($statusOrder as $idx => $step): ?>
                <?php
                $isActive = ($idx <= $currentIdx);
                $isCurrent = ($idx === $currentIdx);
                $circleColor = $isActive ? '#2E7D32' : '#ccc';
                $textColor = $isActive ? '#2E7D32' : '#999';
                $fontWeight = $isCurrent ? '700' : '500';
                ?>
                <div style="display: flex; flex-direction: column; align-items: center; z-index: 2; flex: 1;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $circleColor ?>; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem; <?= $isCurrent ? 'box-shadow: 0 0 0 4px rgba(46,125,50,0.2);' : '' ?>">
                        <?php if ($isActive): ?>
                            <i class="bi <?= $statusIcons[$step] ?>" style="color: white; font-size: 1rem;"></i>
                        <?php else: ?>
                            <i class="bi <?= $statusIcons[$step] ?>" style="color: white; font-size: 1rem; opacity: 0.6;"></i>
                        <?php endif; ?>
                    </div>
                    <span style="font-size: 0.75rem; color: <?= $textColor ?>; font-weight: <?= $fontWeight ?>; text-align: center; line-height: 1.3;">
                        <?= $statusLabels[$step] ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Resumo -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
    <div style="flex: 1; min-width: 140px; background: #f0f7ff; padding: 1rem; border-radius: 8px; text-align: center;">
        <div style="font-size: 0.8rem; color: #666; margin-bottom: 0.25rem;">Status</div>
        <div style="font-size: 1.1rem; font-weight: 700; color: #023A8D;">
            <?= \App\Support\LangHelper::orderStatusLabel($pedido['status']) ?>
        </div>
    </div>
    <div style="flex: 1; min-width: 140px; background: #f0fff4; padding: 1rem; border-radius: 8px; text-align: center;">
        <div style="font-size: 0.8rem; color: #666; margin-bottom: 0.25rem;">Total</div>
        <div style="font-size: 1.1rem; font-weight: 700; color: #2E7D32;">
            R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
        </div>
    </div>
    <div style="flex: 1; min-width: 140px; background: #fff8f0; padding: 1rem; border-radius: 8px; text-align: center;">
        <div style="font-size: 0.8rem; color: #666; margin-bottom: 0.25rem;">Frete</div>
        <div style="font-size: 1.1rem; font-weight: 700; color: #e65100;">
            <?php if (($pedido['total_frete'] ?? 0) == 0): ?>
                Grátis
            <?php else: ?>
                R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="order-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1rem; font-size: 1.125rem; font-weight: 700; color: #333; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-geo-alt icon" style="color: #023A8D;"></i>
            Endereço de Entrega
        </h3>
        <div style="line-height: 1.8; color: #666; font-size: 0.95rem;">
            <strong style="color: #333; display: block; margin-bottom: 0.5rem;"><?= htmlspecialchars($pedido['cliente_nome']) ?></strong>
            <?= htmlspecialchars($pedido['entrega_logradouro']) ?>, <?= htmlspecialchars($pedido['entrega_numero']) ?><br>
            <?php if ($pedido['entrega_complemento']): ?>
                <?= htmlspecialchars($pedido['entrega_complemento']) ?><br>
            <?php endif; ?>
            <?= htmlspecialchars($pedido['entrega_bairro']) ?><br>
            <?= htmlspecialchars($pedido['entrega_cidade']) ?> - <?= htmlspecialchars($pedido['entrega_estado']) ?><br>
            CEP: <?= htmlspecialchars($pedido['entrega_cep']) ?>
        </div>
    </div>
    <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e0e0e0; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <h3 style="margin-bottom: 1rem; font-size: 1.125rem; font-weight: 700; color: #333; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-credit-card icon" style="color: #023A8D;"></i>
            Forma de Pagamento
        </h3>
        <div style="color: #666; font-size: 0.95rem; margin-bottom: 1.5rem;">
            <?= htmlspecialchars($pedido['metodo_pagamento']) ?>
        </div>
        <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1.125rem; font-weight: 700; color: #333; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-truck icon" style="color: #023A8D;"></i>
            Frete
        </h3>
        <div style="color: #666; font-size: 0.95rem;">
            <?= htmlspecialchars($pedido['metodo_frete']) ?><br>
            <strong style="color: #333; font-size: 1.125rem;">R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?></strong>
        </div>
    </div>
</div>

<?php if (!empty($pedido['tracking_code'])): ?>
    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #023A8D; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h3 style="margin: 0 0 1rem 0; font-size: 1.125rem; font-weight: 700; color: #333; display: flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-box-seam icon" style="color: #023A8D;"></i>
            Rastreamento
        </h3>
        <div style="margin-bottom: 1rem;">
            <p style="margin: 0 0 0.5rem 0; color: #666; font-size: 0.95rem;">
                <strong style="color: #333;">Código de rastreamento:</strong>
            </p>
            <p style="margin: 0 0 1rem 0; font-size: 1.25rem; font-weight: 700; color: #023A8D; font-family: monospace;">
                <?= htmlspecialchars($pedido['tracking_code']) ?>
            </p>
        </div>
        <a href="https://www.correios.com.br/precisa-de-ajuda/rastreamento-de-objetos" 
           target="_blank"
           style="display: inline-block; padding: 0.75rem 1.5rem; background: #023A8D; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; transition: background 0.2s;">
            <i class="bi bi-box-arrow-up-right" style="margin-right: 0.5rem;"></i>
            Rastrear nos Correios
        </a>
    </div>
<?php endif; ?>

<h3 style="margin-bottom: 1rem; font-size: 1.125rem;">Itens do Pedido</h3>
<div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
<table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem; min-width: 400px;">
    <thead>
        <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
            <th style="padding: 0.75rem; text-align: left;">Produto</th>
            <th style="padding: 0.75rem; text-align: center;">Quantidade</th>
            <th style="padding: 0.75rem; text-align: right;">Preço Unit.</th>
            <th style="padding: 0.75rem; text-align: right;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($itens as $item): ?>
            <tr style="border-bottom: 1px solid #eee;">
                <td style="padding: 0.75rem;">
                    <strong><?= htmlspecialchars($item['nome_produto']) ?></strong>
                    <?php if ($item['sku']): ?>
                        <br><small style="color: #666;">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                    <?php endif; ?>
                </td>
                <td style="padding: 0.75rem; text-align: center;"><?= $item['quantidade'] ?></td>
                <td style="padding: 0.75rem; text-align: right;">
                    R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?>
                </td>
                <td style="padding: 0.75rem; text-align: right; font-weight: 600;">
                    R$ <?= number_format($item['total_linha'], 2, ',', '.') ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600;">Subtotal:</td>
            <td style="padding: 0.75rem; text-align: right; font-weight: 600;">
                R$ <?= number_format($pedido['total_produtos'], 2, ',', '.') ?>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600;">Frete:</td>
            <td style="padding: 0.75rem; text-align: right; font-weight: 600;">
                R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?>
            </td>
        </tr>
        <?php if ($pedido['total_descontos'] > 0): ?>
            <tr>
                <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 600;">Descontos:</td>
                <td style="padding: 0.75rem; text-align: right; font-weight: 600; color: #2e7d32;">
                    - R$ <?= number_format($pedido['total_descontos'], 2, ',', '.') ?>
                </td>
            </tr>
        <?php endif; ?>
        <tr style="background: #f5f5f5; font-size: 1.125rem;">
            <td colspan="3" style="padding: 0.75rem; text-align: right; font-weight: 700;">Total:</td>
            <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: #2E7D32;">
                R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
            </td>
        </tr>
    </tfoot>
</table>
</div>

<style>
@media (max-width: 768px) {
    .order-stats, .order-details { grid-template-columns: 1fr !important; }
    .order-stats > div, .order-details > div { padding: 1rem !important; }
}
</style>

<div style="text-align: center; margin-top: 2rem;">
    <a href="<?= $basePath ?>/minha-conta/pedidos" style="color: #023A8D; text-decoration: none;">
        ← Voltar para lista de pedidos
    </a>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>


