<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos - Admin</title>
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2rem;
            color: #333;
        }
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filter-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        .filter-group input, .filter-group select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn-filter {
            padding: 0.5rem 1.5rem;
            background: #023A8D;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            align-self: flex-end;
        }
        .btn-filter:hover {
            background: #022a6b;
        }
        .products-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead {
            background: #023A8D;
            color: white;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            font-weight: 600;
        }
        tbody tr:hover {
            background: #f9f9f9;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            background: #e0e0e0;
        }
        .image-placeholder {
            width: 60px;
            height: 60px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: #999;
            border-radius: 4px;
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-block;
        }
        .status-publish {
            background: #d4edda;
            color: #155724;
        }
        .status-draft {
            background: #fff3cd;
            color: #856404;
        }
        .btn-view {
            padding: 0.5rem 1rem;
            background: #F7931E;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-view:hover {
            background: #e6851a;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        .pagination a, .pagination span {
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
            border-color: #023A8D;
        }
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .pagination-info {
            color: #666;
        }
        .empty-message {
            text-align: center;
            padding: 3rem;
            color: #666;
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
        <h2>Store Admin</h2>
        <div>
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/produtos">Produtos</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Produtos</h1>
        </div>
        
        <form method="GET" action="<?= $basePath ?>/admin/produtos" class="filters">
            <div class="filter-group">
                <label>Buscar por nome ou SKU</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Digite para buscar...">
            </div>
            <div class="filter-group">
                <label>Status</label>
                <select name="status">
                    <option value="">Todos</option>
                    <option value="publish" <?= $filtros['status'] === 'publish' ? 'selected' : '' ?>>Publicado</option>
                    <option value="draft" <?= $filtros['status'] === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">Filtrar</button>
        </form>
        
        <?php if (!empty($produtos)): ?>
            <div class="products-table">
                <table>
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Nome</th>
                            <th>SKU</th>
                            <th>Preço</th>
                            <th>Status</th>
                            <th>Estoque</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $produto): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $imagem = $produto['imagem_principal_data'] ?? null;
                                    if ($imagem && !empty($imagem['caminho_arquivo'])): 
                                        $caminho = ltrim($imagem['caminho_arquivo'], '/');
                                    ?>
                                        <img src="<?= $basePath ?>/<?= htmlspecialchars($caminho) ?>" 
                                             alt="<?= htmlspecialchars($produto['nome']) ?>"
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="image-placeholder">Sem img</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($produto['nome']) ?></td>
                                <td><?= htmlspecialchars($produto['sku'] ?? '-') ?></td>
                                <td>
                                    <?php if ($produto['preco_promocional']): ?>
                                        <span style="text-decoration: line-through; color: #999;">
                                            R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?>
                                        </span><br>
                                        <strong style="color: #F7931E;">
                                            R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?>
                                        </strong>
                                    <?php else: ?>
                                        R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $produto['status'] ?>">
                                        <?= htmlspecialchars($produto['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $produto['quantidade_estoque'] ?> 
                                    <small>(<?= htmlspecialchars($produto['status_estoque']) ?>)</small>
                                </td>
                                <td>
                                    <a href="<?= $basePath ?>/admin/produtos/<?= $produto['id'] ?>" class="btn-view">Ver detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($paginacao['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php if ($paginacao['hasPrev']): ?>
                        <a href="?page=<?= $paginacao['currentPage'] - 1 ?>&q=<?= urlencode($filtros['q']) ?>&status=<?= urlencode($filtros['status']) ?>">Anterior</a>
                    <?php else: ?>
                        <span class="disabled">Anterior</span>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Página <?= $paginacao['currentPage'] ?> de <?= $paginacao['totalPages'] ?>
                        (<?= $paginacao['total'] ?> produtos)
                    </span>
                    
                    <?php if ($paginacao['hasNext']): ?>
                        <a href="?page=<?= $paginacao['currentPage'] + 1 ?>&q=<?= urlencode($filtros['q']) ?>&status=<?= urlencode($filtros['status']) ?>">Próxima</a>
                    <?php else: ?>
                        <span class="disabled">Próxima</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">
                <p>Nenhum produto encontrado.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

