<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$ordenacao = $ordenacao ?? ['sort' => '', 'direction' => 'asc'];
?>

<div class="products-page">
    <div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 class="admin-page-title" style="font-size: 1.875rem; font-weight: 700; color: #333; margin: 0;">
            Produtos
        </h1>
        <a href="<?= $basePath ?>/admin/produtos/novo" class="admin-btn admin-btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-plus-circle icon"></i>
            Novo produto
        </a>
    </div>
    
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
            <div class="admin-filter-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="somente_com_imagem" value="1" 
                           <?= !empty($filtros['somente_com_imagem']) ? 'checked' : '' ?>>
                    <span>Mostrar apenas produtos com imagem</span>
                </label>
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
                        <th>
                            <?php
                            // Construir URL para ordenação por nome
                            $queryParams = [];
                            if (!empty($filtros['q'])) $queryParams['q'] = $filtros['q'];
                            if (!empty($filtros['status'])) $queryParams['status'] = $filtros['status'];
                            if (!empty($filtros['somente_com_imagem'])) $queryParams['somente_com_imagem'] = '1';
                            
                            // Determinar próxima direção
                            $currentSort = $ordenacao['sort'] ?? '';
                            $currentDirection = $ordenacao['direction'] ?? 'asc';
                            $nextDirection = 'asc';
                            
                            if ($currentSort === 'name') {
                                $nextDirection = ($currentDirection === 'asc') ? 'desc' : 'asc';
                            }
                            
                            $queryParams['sort'] = 'name';
                            $queryParams['direction'] = $nextDirection;
                            $sortUrl = $basePath . '/admin/produtos?' . http_build_query($queryParams);
                            
                            // Ícone de ordenação
                            $sortIcon = '';
                            if ($currentSort === 'name') {
                                $sortIcon = $currentDirection === 'asc' ? '↑' : '↓';
                            }
                            ?>
                            <a href="<?= htmlspecialchars($sortUrl) ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 0.5rem;">
                                Nome
                                <?php if ($sortIcon): ?>
                                    <span style="font-size: 0.875rem; color: var(--pg-admin-primary);"><?= $sortIcon ?></span>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th>SKU</th>
                        <th>Preço</th>
                        <th>Status</th>
                        <th>Estoque</th>
                        <th>Categorias</th>
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
                                <?php 
                                $categorias = $produto['categorias'] ?? [];
                                if (!empty($categorias)): 
                                    $categoriasDisplay = array_slice($categorias, 0, 2);
                                    $restantes = count($categorias) - 2;
                                ?>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                        <?php foreach ($categoriasDisplay as $cat): ?>
                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; background: #e0e0e0; border-radius: 4px; font-size: 0.75rem; color: #555;">
                                                <?= htmlspecialchars($cat) ?>
                                            </span>
                                        <?php endforeach; ?>
                                        <?php if ($restantes > 0): ?>
                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; background: #f0f0f0; border-radius: 4px; font-size: 0.75rem; color: #999;">
                                                +<?= $restantes ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic; font-size: 0.875rem;">Sem categorias</span>
                                <?php endif; ?>
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
                <?php 
                $queryParams = [];
                if (!empty($filtros['q'])) $queryParams[] = 'q=' . urlencode($filtros['q']);
                if (!empty($filtros['status'])) $queryParams[] = 'status=' . urlencode($filtros['status']);
                if (!empty($filtros['somente_com_imagem'])) $queryParams[] = 'somente_com_imagem=1';
                if (!empty($ordenacao['sort'])) $queryParams[] = 'sort=' . urlencode($ordenacao['sort']);
                if (!empty($ordenacao['direction'])) $queryParams[] = 'direction=' . urlencode($ordenacao['direction']);
                $queryString = !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
                ?>
                <?php if ($paginacao['hasPrev']): ?>
                    <a href="?page=<?= $paginacao['currentPage'] - 1 ?><?= $queryString ?>">
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
                    <a href="?page=<?= $paginacao['currentPage'] + 1 ?><?= $queryString ?>">
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
    background: var(--pg-admin-primary);
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
    background: var(--pg-admin-primary-hover);
    transform: translateY(-1px);
}
.btn-view .icon {
    font-size: 0.875rem;
}
</style>


