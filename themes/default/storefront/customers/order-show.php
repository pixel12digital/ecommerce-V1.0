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
<div class="content-header">
    <h1>Pedido #<?= htmlspecialchars($pedido['numero_pedido']) ?></h1>
    <p style="color: #666; margin-top: 0.5rem;">
        Data: <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
    </p>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <i class="bi bi-info-circle icon" style="font-size: 1.5rem; color: #023A8D;"></i>
            <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #666;">Status do Pedido</h3>
        </div>
        <div style="font-size: 1.375rem; font-weight: 700; color: #023A8D;">
            <?= \App\Support\LangHelper::orderStatusLabel($pedido['status']) ?>
        </div>
    </div>
    <div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <i class="bi bi-currency-dollar icon" style="font-size: 1.5rem; color: #e65100;"></i>
            <h3 style="margin: 0; font-size: 1rem; font-weight: 600; color: #666;">Total</h3>
        </div>
        <div style="font-size: 1.75rem; font-weight: 700; color: #2E7D32;">
            R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
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

<h3 style="margin-bottom: 1rem; font-size: 1.125rem;">Itens do Pedido</h3>
<table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
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

<div style="text-align: center; margin-top: 2rem;">
    <a href="<?= $basePath ?>/minha-conta/pedidos" style="color: #023A8D; text-decoration: none;">
        ← Voltar para lista de pedidos
    </a>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>


