<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos - Store Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a { color: white; text-decoration: none; }
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-title {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            background: #F7931E;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .orders-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-canceled { background: #f8d7da; color: #721c24; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background: #023A8D;
            color: white;
        }
        .pagination .current {
            background: #023A8D;
            color: white;
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
    ?>
    
    <div class="header">
        <h2>Pedidos</h2>
        <a href="<?= $basePath ?>/admin"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    
    <div class="container">
        <h1 class="page-title">Pedidos</h1>
        
        <div class="filters">
            <form method="GET" action="<?= $basePath ?>/admin/pedidos">
                <div class="form-group">
                    <label>Buscar</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Número, nome ou e-mail">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Todos</option>
                        <option value="pending" <?= $filtros['status'] === 'pending' ? 'selected' : '' ?>>Aguardando Pagamento</option>
                        <option value="paid" <?= $filtros['status'] === 'paid' ? 'selected' : '' ?>>Pago</option>
                        <option value="canceled" <?= $filtros['status'] === 'canceled' ? 'selected' : '' ?>>Cancelado</option>
                        <option value="shipped" <?= $filtros['status'] === 'shipped' ? 'selected' : '' ?>>Enviado</option>
                        <option value="completed" <?= $filtros['status'] === 'completed' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn">Filtrar</button>
                </div>
            </form>
        </div>
        
        <div class="orders-table">
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
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: #666;">
                                Nenhum pedido encontrado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                                </td>
                                <td>
                                    <?= htmlspecialchars($pedido['cliente_nome']) ?><br>
                                    <small style="color: #666;"><?= htmlspecialchars($pedido['cliente_email']) ?></small>
                                </td>
                                <td>
                                    <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $pedido['status'] ?>">
                                        <?php
                                        echo \App\Support\LangHelper::orderStatusLabelShort($pedido['status']);
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?></strong>
                                </td>
                                <td>
                                    <a href="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>" style="color: #023A8D; text-decoration: none;">
                                        Ver Detalhes <i class="bi bi-arrow-right icon"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($paginacao['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($paginacao['hasPrev']): ?>
                    <a href="?page=<?= $paginacao['currentPage'] - 1 ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>">« Anterior</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $paginacao['totalPages']; $i++): ?>
                    <?php if ($i == $paginacao['currentPage']): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($paginacao['hasNext']): ?>
                    <a href="?page=<?= $paginacao['currentPage'] + 1 ?>&status=<?= htmlspecialchars($filtros['status']) ?>&q=<?= htmlspecialchars($filtros['q']) ?>">Próxima »</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


