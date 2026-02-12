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
    <?php
    $statusColors = [
        'pending'   => ['bg' => '#fff3cd', 'text' => '#856404', 'icon' => 'bi-clock'],
        'paid'      => ['bg' => '#d4edda', 'text' => '#155724', 'icon' => 'bi-check-circle'],
        'shipped'   => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'icon' => 'bi-truck'],
        'completed' => ['bg' => '#d4edda', 'text' => '#155724', 'icon' => 'bi-house-check'],
        'canceled'  => ['bg' => '#f8d7da', 'text' => '#721c24', 'icon' => 'bi-x-circle'],
    ];
    $statusMessages = [
        'pending'   => 'Aguardando pagamento',
        'paid'      => 'Pagamento confirmado, preparando envio',
        'shipped'   => 'Pedido enviado',
        'completed' => 'Pedido entregue',
        'canceled'  => 'Pedido cancelado',
    ];
    ?>
    <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($pedidos as $pedido): ?>
            <?php
            $sc = $statusColors[$pedido['status']] ?? $statusColors['pending'];
            $sm = $statusMessages[$pedido['status']] ?? '';
            $trackCode = $pedido['tracking_code'] ?? '';
            ?>
            <a href="<?= $basePath ?>/minha-conta/pedidos/<?= htmlspecialchars($pedido['numero_pedido']) ?>" 
               style="text-decoration: none; color: inherit; display: block; background: white; border: 1px solid #e8e8e8; border-radius: 10px; padding: 1.25rem; transition: box-shadow 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.06);"
               onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.06)'">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <strong style="color: #023A8D; font-size: 0.95rem;">#<?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                    <span style="padding: 0.3rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; background: <?= $sc['bg'] ?>; color: <?= $sc['text'] ?>; display: inline-flex; align-items: center; gap: 0.35rem;">
                        <i class="bi <?= $sc['icon'] ?>" style="font-size: 0.75rem;"></i>
                        <?= \App\Support\LangHelper::orderStatusLabelShort($pedido['status']) ?>
                    </span>
                </div>
                <p style="margin: 0 0 0.5rem; font-size: 0.85rem; color: <?= $sc['text'] ?>; font-weight: 500;">
                    <i class="bi <?= $sc['icon'] ?>" style="margin-right: 0.25rem;"></i>
                    <?= $sm ?>
                </p>
                <?php if (!empty($trackCode) && in_array($pedido['status'], ['shipped', 'completed'])): ?>
                    <div style="background: #f0f7ff; padding: 0.5rem 0.75rem; border-radius: 6px; margin-bottom: 0.5rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-box-seam" style="color: #023A8D; font-size: 0.85rem;"></i>
                        <span style="font-size: 0.85rem; color: #333; font-weight: 600; font-family: monospace;"><?= htmlspecialchars($trackCode) ?></span>
                    </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: #888; margin-top: 0.25rem;">
                    <span><?= date('d/m/Y', strtotime($pedido['created_at'])) ?></span>
                    <strong style="color: #333; font-size: 1rem;">R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?></strong>
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


