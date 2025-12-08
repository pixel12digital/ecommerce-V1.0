<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="products-page">
    <div class="admin-filters">
        <form method="GET" action="<?= $basePath ?>/admin/produtos">
            <div class="admin-filter-group">
                <label for="filter-q">Buscar por nome ou SKU</label>
                <input type="text" id="filter-q" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Digite para buscar...">
            </div>
            <div class="admin-filter-group">
                <label for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="">Todos</option>
                    <option value="publish" <?= $filtros['status'] === 'publish' ? 'selected' : '' ?>><?= \App\Support\LangHelper::productStatusLabel('publish') ?></option>
                    <option value="draft" <?= $filtros['status'] === 'draft' ? 'selected' : '' ?>><?= \App\Support\LangHelper::productStatusLabel('draft') ?></option>
                </select>
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-funnel icon"></i>
                Filtrar
            </button>
        </form>
    </div>
    
    <?php if (!empty($produtos)): ?>
        <div class="admin-table">
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
                                    <div class="admin-image-placeholder">
                                        <i class="bi bi-image icon"></i>
                                        <span>Sem img</span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= htmlspecialchars($produto['sku'] ?? '-') ?></td>
                            <td>
                                <?php if ($produto['preco_promocional']): ?>
                                    <span style="text-decoration: line-through; color: #999; font-size: 0.875rem;">
                                        R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?>
                                    </span><br>
                                    <strong style="color: #F7931E; font-size: 1rem;">
                                        R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?>
                                    </strong>
                                <?php else: ?>
                                    <strong>R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?></strong>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="admin-status-badge <?= $produto['status'] ?>">
                                    <?= \App\Support\LangHelper::productStatusLabel($produto['status']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= $produto['quantidade_estoque'] ?></strong>
                                <small style="color: #666; display: block; font-size: 0.875rem;">
                                    (<?= \App\Support\LangHelper::stockStatusLabel($produto['status_estoque'] ?? null) ?>)
                                </small>
                            </td>
                            <td>
                                <a href="<?= $basePath ?>/admin/produtos/<?= $produto['id'] ?>" class="btn-view">
                                    <i class="bi bi-eye icon"></i>
                                    Ver detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($paginacao['totalPages'] > 1): ?>
            <div class="admin-pagination">
                <?php if ($paginacao['hasPrev']): ?>
                    <a href="?page=<?= $paginacao['currentPage'] - 1 ?>&q=<?= urlencode($filtros['q']) ?>&status=<?= urlencode($filtros['status']) ?>">
                        <i class="bi bi-chevron-left icon"></i>
                        Anterior
                    </a>
                <?php else: ?>
                    <span class="disabled">Anterior</span>
                <?php endif; ?>
                
                <span class="admin-pagination-info">
                    Página <?= $paginacao['currentPage'] ?> de <?= $paginacao['totalPages'] ?>
                    (<?= $paginacao['total'] ?> produtos)
                </span>
                
                <?php if ($paginacao['hasNext']): ?>
                    <a href="?page=<?= $paginacao['currentPage'] + 1 ?>&q=<?= urlencode($filtros['q']) ?>&status=<?= urlencode($filtros['status']) ?>">
                        Próxima
                        <i class="bi bi-chevron-right icon"></i>
                    </a>
                <?php else: ?>
                    <span class="disabled">Próxima</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="admin-empty-message">
            <i class="bi bi-inbox icon"></i>
            <p>Nenhum produto encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<style>
/* Fase 10 – Ajustes layout Admin - Produtos */
.products-page {
    max-width: 1400px;
}
.product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
.btn-view {
    padding: 0.5rem 1rem;
    background: #023A8D;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s, transform 0.2s;
}
.btn-view:hover {
    background: #022a6b;
    transform: translateY(-1px);
}
.btn-view .icon {
    font-size: 0.875rem;
}
</style>


