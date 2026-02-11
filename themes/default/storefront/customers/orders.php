<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$pedidos = $pedidos ?? [];
?>
<?php ob_start(); ?>
<div class="content-header">
    <h1>Meus Pedidos</h1>
</div>

<?php if (!empty($pedidos)): ?>
    <!-- Desktop: Tabela -->
    <div class="desktop-table" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 0.875rem; text-align: left; font-weight: 600; color: #555;">Número</th>
                    <th style="padding: 0.875rem; text-align: left; font-weight: 600; color: #555;">Data</th>
                    <th style="padding: 0.875rem; text-align: left; font-weight: 600; color: #555;">Status</th>
                    <th style="padding: 0.875rem; text-align: right; font-weight: 600; color: #555;">Total</th>
                    <th style="padding: 0.875rem; text-align: center; font-weight: 600; color: #555;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 0.875rem; font-weight: 600; color: #333;"><?= htmlspecialchars($pedido['numero_pedido']) ?></td>
                        <td style="padding: 0.875rem; color: #666;"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></td>
                        <td style="padding: 0.875rem;">
                            <span style="padding: 0.375rem 0.875rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: #e3f2fd; color: #023A8D; display: inline-block;">
                                <?= \App\Support\LangHelper::orderStatusLabelShort($pedido['status']) ?>
                            </span>
                        </td>
                        <td style="padding: 0.875rem; text-align: right; font-weight: 600;">R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?></td>
                        <td style="padding: 0.875rem; text-align: center;">
                            <a href="<?= $basePath ?>/minha-conta/pedidos/<?= htmlspecialchars($pedido['numero_pedido']) ?>" style="color: #023A8D; text-decoration: none; font-weight: 500;"><i class="bi bi-eye icon"></i> Ver detalhes</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile: Cards -->
    <div class="mobile-cards" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($pedidos as $pedido): ?>
            <a href="<?= $basePath ?>/minha-conta/pedidos/<?= htmlspecialchars($pedido['numero_pedido']) ?>" style="text-decoration: none; color: inherit; display: block; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <strong style="color: #023A8D; font-size: 0.9rem;"><?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                    <span style="padding: 0.25rem 0.6rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; background: #e3f2fd; color: #023A8D;">
                        <?= \App\Support\LangHelper::orderStatusLabelShort($pedido['status']) ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #666;">
                    <span><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></span>
                    <strong style="color: #333;">R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?></strong>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div style="text-align: center; padding: 3rem; color: #666;">
        <i class="bi bi-inbox" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.5;"></i>
        <p>Você ainda não fez nenhum pedido.</p>
        <a href="<?= $basePath ?>/produtos" style="display: inline-block; margin-top: 1rem; color: #023A8D; text-decoration: none;">
            Começar a comprar →
        </a>
    </div>
<?php endif; ?>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>


