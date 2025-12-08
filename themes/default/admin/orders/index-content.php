<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="orders-page">
    <div class="admin-filters">
        <form method="GET" action="<?= $basePath ?>/admin/pedidos">
            <div class="admin-filter-group">
                <label for="filter-q">Buscar</label>
                <input type="text" id="filter-q" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Número, nome ou e-mail">
            </div>
            <div class="admin-filter-group">
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="">Todos</option>
                    <option value="pending" <?= $filtros['status'] === 'pending' ? 'selected' : '' ?>>Aguardando Pagamento</option>
                    <option value="paid" <?= $filtros['status'] === 'paid' ? 'selected' : '' ?>>Pago</option>
                    <option value="canceled" <?= $filtros['status'] === 'canceled' ? 'selected' : '' ?>>Cancelado</option>
                    <option value="shipped" <?= $filtros['status'] === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                    <option value="completed" <?= $filtros['status'] === 'completed' ? 'selected' : '' ?>>Concluído</option>
                </select>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-funnel icon"></i>
                Filtrar
            </button>
        </form>
    </div>
    
    <?php if (empty($pedidos)): ?>
        <div class="admin-empty-message">
            <i class="bi bi-inbox icon"></i>
            <p>Nenhum pedido encontrado.</p>
        </div>
    <?php else: ?>
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>
                                <strong style="color: #333;"><?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($pedido['cliente_nome']) ?></strong><br>
                                <small style="color: #666; font-size: 0.875rem;"><?= htmlspecialchars($pedido['cliente_email']) ?></small>
                            </td>
                            <td style="color: #666;">
                                <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                            </td>
                            <td>
                                <span class="admin-status-badge <?= $pedido['status'] ?>">
                                    <?= \App\Support\LangHelper::orderStatusLabelShort($pedido['status']) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: #2E7D32; font-size: 1.125rem;">R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?></strong>
                            </td>
                            <td>
                                <a href="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>" class="admin-btn admin-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    <i class="bi bi-eye icon"></i>
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if ($paginacao['totalPages'] > 1): ?>
        <div class="admin-pagination">
            <?php if ($paginacao['hasPrev']): ?>
                <a href="?page=<?= $paginacao['currentPage'] - 1 ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>">
                    <i class="bi bi-chevron-left icon"></i>
                    Anterior
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $paginacao['totalPages']; $i++): ?>
                <?php if ($i == $paginacao['currentPage']): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($paginacao['hasNext']): ?>
                <a href="?page=<?= $paginacao['currentPage'] + 1 ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>">
                    Próxima
                    <i class="bi bi-chevron-right icon"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Fase 10 – Ajustes layout Admin - Pedidos */
.orders-page {
    max-width: 1400px;
}
</style>


