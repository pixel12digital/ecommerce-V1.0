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
                <label for="filter-categoria">Categoria</label>
                <select id="filter-categoria" name="categoria_id">
                    <option value="">Todas</option>
                    <?php if (!empty($categoriasFiltro)): ?>
                        <?php foreach ($categoriasFiltro as $categoria): ?>
                            <option
                                value="<?= (int)$categoria['id'] ?>"
                                <?= isset($filtros['categoria_id']) && $filtros['categoria_id'] === (int)$categoria['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                            if (!empty($filtros['categoria_id'])) $queryParams['categoria_id'] = $filtros['categoria_id'];
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
                        <tr data-produto-id="<?= (int)$produto['id'] ?>">
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
                                <button type="button"
                                    class="btn-status-toggle js-toggle-status"
                                    data-id="<?= (int)$produto['id'] ?>"
                                    data-status="<?= htmlspecialchars($produto['status']) ?>">
                                    <span class="admin-status-badge <?= $produto['status'] ?>">
                                        <?= \App\Support\LangHelper::productStatusLabel($produto['status']) ?>
                                    </span>
                                </button>
                            </td>
                            <td>
                                <strong><?= $produto['quantidade_estoque'] ?></strong>
                                <small style="color: #666; display: block; font-size: 0.875rem;">
                                    (<?= \App\Support\LangHelper::stockStatusLabel($produto['status_estoque'] ?? null) ?>)
                                </small>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <div style="flex: 1; min-width: 0;">
                                        <?php 
                                        $categorias = $produto['categorias'] ?? [];
                                        $categoriaIds = $produto['categoria_ids'] ?? [];
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
                                    </div>
                                    <button type="button"
                                        class="btn-link-icon js-open-categorias-modal"
                                        data-id="<?= (int)$produto['id'] ?>"
                                        data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                                        data-categorias="<?= htmlspecialchars(json_encode($categoriaIds)) ?>"
                                        title="Editar categorias">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" 
                                       class="btn-action btn-action-view" 
                                       target="_blank"
                                       title="Ver produto na loja">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= $basePath ?>/admin/produtos/<?= $produto['id'] ?>" 
                                       class="btn-action btn-action-edit" 
                                       title="Editar produto">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn-action btn-action-delete js-open-excluir-produto-modal"
                                            data-id="<?= (int)$produto['id'] ?>"
                                            data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                                            title="Excluir produto">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
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
                if (!empty($filtros['categoria_id'])) $queryParams[] = 'categoria_id=' . urlencode($filtros['categoria_id']);
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

<!-- Modal de Exclusão de Produto -->
<div class="pg-modal-overlay" id="modal-excluir-produto" style="display: none;">
    <div class="pg-modal-dialog">
        <div class="pg-modal-content">
            <div class="pg-modal-header">
                <h5 class="pg-modal-title">Excluir Produto</h5>
                <button type="button" class="pg-modal-close" onclick="window.fecharModalExclusaoProduto()" aria-label="Fechar">&times;</button>
            </div>
            <div class="pg-modal-body">
                <p style="margin: 0; color: #333; font-size: 1rem; line-height: 1.6;">
                    Tem certeza que deseja excluir o produto <strong id="modal-produto-nome"></strong>?
                </p>
                <p style="margin: 1rem 0 0 0; color: #d32f2f; font-size: 0.875rem;">
                    <i class="bi bi-exclamation-triangle icon"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="pg-modal-footer">
                <form method="POST" id="form-excluir-produto" style="display: inline;">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="window.fecharModalExclusaoProduto()">
                        Cancelar
                    </button>
                    <button type="submit" class="admin-btn admin-btn-danger">
                        <i class="bi bi-trash icon"></i>
                        Excluir Produto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Categorias Rápidas -->
<div class="pg-modal-overlay" id="modal-categorias-produto" style="display: none;">
    <div class="pg-modal-dialog" style="max-width: 600px;">
        <div class="pg-modal-content">
            <div class="pg-modal-header">
                <h5 class="pg-modal-title">Editar Categorias</h5>
                <button type="button" class="pg-modal-close" onclick="window.fecharModalCategorias()" aria-label="Fechar">&times;</button>
            </div>
            <div class="pg-modal-body">
                <p style="margin: 0 0 1rem 0; color: #333; font-size: 0.875rem;">
                    Produto: <strong id="modal-categorias-produto-nome"></strong>
                </p>
                <div id="modal-categorias-list" style="max-height: 400px; overflow-y: auto; border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem;">
                    <?php if (!empty($todasCategorias)): ?>
                        <?php foreach ($todasCategorias as $cat): ?>
                            <label style="display: block; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s;" 
                                   onmouseover="this.style.background='#f5f5f5'" 
                                   onmouseout="this.style.background='transparent'">
                                <input type="checkbox" 
                                       name="categorias[]" 
                                       value="<?= (int)$cat['id'] ?>" 
                                       class="categoria-checkbox"
                                       data-categoria-id="<?= (int)$cat['id'] ?>">
                                <span style="margin-left: 0.5rem;"><?= htmlspecialchars($cat['nome']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; font-style: italic;">Nenhuma categoria disponível.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="pg-modal-footer">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="window.fecharModalCategorias()">
                    Cancelar
                </button>
                <button type="button" class="admin-btn admin-btn-primary" id="btn-salvar-categorias">
                    <i class="bi bi-check icon"></i>
                    Salvar Categorias
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir basePath globalmente para o JS
    window.basePath = <?= json_encode($basePath) ?>;
</script>
<script src="<?= $basePath ?>/admin/js/products.js"></script>

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

/* Status clicável */
.btn-status-toggle {
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-status-toggle:hover {
    opacity: 0.8;
}

.btn-status-toggle:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Botão de editar categorias */
.btn-link-icon {
    background: none;
    border: none;
    padding: 0.25rem;
    color: #666;
    cursor: pointer;
    font-size: 0.875rem;
    transition: color 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-link-icon:hover {
    color: var(--pg-admin-primary);
}

/* Botões de ação */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.btn-action-view {
    background: var(--pg-admin-primary);
    color: white;
}

.btn-action-view:hover {
    background: var(--pg-admin-primary-hover);
    transform: translateY(-1px);
}

.btn-action-edit {
    background: #17a2b8;
    color: white;
}

.btn-action-edit:hover {
    background: #138496;
    transform: translateY(-1px);
}

.btn-action-delete {
    background: #dc3545;
    color: white;
}

.btn-action-delete:hover {
    background: #c82333;
    transform: translateY(-1px);
}

/* Modal de Exclusão - Estilos do tema */
.pg-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.pg-modal-dialog {
    width: 100%;
    max-width: 500px;
    display: flex;
    flex-direction: column;
}

.pg-modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.pg-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--pg-admin-primary);
    color: white;
}

.pg-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.pg-modal-close {
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    color: white;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s;
}

.pg-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.pg-modal-body {
    padding: 1.5rem;
}

.pg-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.admin-btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
}

.admin-btn-danger:hover {
    background: #c82333;
}
</style>


