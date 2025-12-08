<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$customer = $customer ?? [];
$pedidos = $pedidos ?? [];
$totalPedidos = $totalPedidos ?? 0;
$registered = isset($_GET['registered']) ? true : false;
?>
<?php ob_start(); ?>
<div class="content-header">
    <h1>Dashboard</h1>
</div>

<?php if ($registered): ?>
    <div class="alert alert-success">
        <strong>Bem-vindo!</strong> Sua conta foi criada com sucesso.
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <i class="bi bi-receipt icon" style="font-size: 1.5rem; color: #023A8D;"></i>
            <span style="color: #666; font-size: 0.9rem; font-weight: 500;">Total de Pedidos</span>
        </div>
        <div style="font-size: 2.5rem; font-weight: 700; color: #023A8D;"><?= $totalPedidos ?></div>
    </div>
    <div style="background: #fff3e0; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
            <i class="bi bi-clock-history icon" style="font-size: 1.5rem; color: #e65100;"></i>
            <span style="color: #666; font-size: 0.9rem; font-weight: 500;">Últimos Pedidos</span>
        </div>
        <div style="font-size: 2.5rem; font-weight: 700; color: #e65100;"><?= count($pedidos) ?></div>
    </div>
</div>

<?php if (!empty($pedidos)): ?>
    <div>
        <h2 style="margin-bottom: 1.5rem; font-size: 1.375rem; font-weight: 700; color: #333;">Últimos Pedidos</h2>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
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
                        <tr style="border-bottom: 1px solid #eee; transition: background 0.2s;">
                            <td style="padding: 0.875rem; font-weight: 600; color: #333;"><?= htmlspecialchars($pedido['numero_pedido']) ?></td>
                            <td style="padding: 0.875rem; color: #666;"><?= date('d/m/Y', strtotime($pedido['created_at'])) ?></td>
                            <td style="padding: 0.875rem;">
                                <span style="padding: 0.375rem 0.875rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; background: #e3f2fd; color: #023A8D; display: inline-block;">
                                    <?= \App\Support\LangHelper::orderStatusLabelShort($pedido['status']) ?>
                                </span>
                            </td>
                            <td style="padding: 0.875rem; text-align: right; font-weight: 600; color: #333;">
                                R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                            </td>
                            <td style="padding: 0.875rem; text-align: center;">
                                <a href="<?= $basePath ?>/minha-conta/pedidos/<?= htmlspecialchars($pedido['numero_pedido']) ?>" 
                                   style="color: #023A8D; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                                    <i class="bi bi-eye icon"></i>
                                    Ver detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPedidos > 5): ?>
            <div style="margin-top: 1rem; text-align: center;">
                <a href="<?= $basePath ?>/minha-conta/pedidos" style="color: #023A8D; text-decoration: none;">
                    Ver todos os pedidos →
                </a>
            </div>
        <?php endif; ?>
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


