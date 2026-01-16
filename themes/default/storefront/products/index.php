<?php
// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}

// Base path
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

// Carregar dados necessários para o layout base
// Se $loja não foi passado pelo controller, carregar do tenant
if (empty($loja) || empty($loja['nome'])) {
    $tenant = \App\Tenant\TenantContext::tenant();
    $loja = [
        'nome' => is_object($tenant) ? $tenant->name : ($tenant['nome'] ?? 'Loja'),
        'slug' => is_object($tenant) ? $tenant->slug : ($tenant['slug'] ?? '')
    ];
}

// Garantir que $theme existe e tem menu_main
if (empty($theme)) {
    $theme = [];
}
if (empty($theme['menu_main'])) {
    $theme['menu_main'] = \App\Services\ThemeConfig::getMainMenu();
}

// Carregar categoryPills e allCategories para a faixa de categorias
$db = \App\Core\Database::getConnection();
$tenantId = \App\Tenant\TenantContext::id();
$stmt = $db->prepare("
    SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
    FROM home_category_pills hcp
    LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
    WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
    ORDER BY hcp.ordem ASC, hcp.id ASC
");
$stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
$stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
$stmt->execute();
$categoryPills = $stmt->fetchAll();

// Buscar todas as categorias com produtos visíveis para o menu
$ocultarEstoqueZero = \App\Services\ThemeConfig::get('catalogo_ocultar_estoque_zero', '0');
$estoqueCondition = '';
if ($ocultarEstoqueZero === '1') {
    $estoqueCondition = " AND (p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
}

$sqlAllCategories = "
    SELECT DISTINCT
        c.id,
        c.nome as categoria_nome,
        c.slug as categoria_slug,
        c.categoria_pai_id,
        COALESCE(cp.nome, '') as categoria_pai_nome,
        COALESCE(cp.slug, '') as categoria_pai_slug
    FROM categorias c
    LEFT JOIN categorias cp ON cp.id = c.categoria_pai_id AND cp.tenant_id = :tenant_id_pai
    INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = :tenant_id_pc
    INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = :tenant_id_prod
    WHERE c.tenant_id = :tenant_id_cat
    AND p.status = 'publish'
    AND p.exibir_no_catalogo = 1
    {$estoqueCondition}
    
    UNION
    
    SELECT DISTINCT
        cp.id,
        cp.nome as categoria_nome,
        cp.slug as categoria_slug,
        cp.categoria_pai_id,
        COALESCE(cpp.nome, '') as categoria_pai_nome,
        COALESCE(cpp.slug, '') as categoria_pai_slug
    FROM categorias cp
    LEFT JOIN categorias cpp ON cpp.id = cp.categoria_pai_id AND cpp.tenant_id = :tenant_id_pai2
    INNER JOIN categorias c ON c.categoria_pai_id = cp.id AND c.tenant_id = :tenant_id_sub
    INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = :tenant_id_pc2
    INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = :tenant_id_prod2
    WHERE cp.tenant_id = :tenant_id_cat2
    AND cp.categoria_pai_id IS NULL
    AND p.status = 'publish'
    AND p.exibir_no_catalogo = 1
    {$estoqueCondition}
    
    ORDER BY categoria_pai_id IS NULL DESC, categoria_nome ASC
";

$stmtAllCategories = $db->prepare($sqlAllCategories);
$stmtAllCategories->bindValue(':tenant_id_pai', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_pc', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_prod', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_cat', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_pai2', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_sub', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_pc2', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_prod2', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->bindValue(':tenant_id_cat2', $tenantId, \PDO::PARAM_INT);
$stmtAllCategories->execute();
$categoriasRaw = $stmtAllCategories->fetchAll();

// Formatar para o formato esperado pelo menu
$allCategories = [];
$categoriasIds = []; // Para evitar duplicatas
foreach ($categoriasRaw as $cat) {
    $catId = $cat['id'];
    if (!in_array($catId, $categoriasIds)) {
        $categoriasIds[] = $catId;
        $allCategories[] = [
            'categoria_id' => $catId,
            'categoria_nome' => $cat['categoria_nome'],
            'categoria_slug' => $cat['categoria_slug'],
            'categoria_pai_id' => $cat['categoria_pai_id'],
            'label' => $cat['categoria_nome'],
        ];
    }
}

// Adicionar "Sem Categoria" se houver produtos sem categoria visíveis
$produtosSemCategoriaSql = "
    SELECT COUNT(DISTINCT p.id) as total
    FROM produtos p
    LEFT JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id
    WHERE p.tenant_id = :tenant_id_prod
    AND p.status = 'publish'
    AND p.exibir_no_catalogo = 1
    AND pc.produto_id IS NULL
    {$estoqueCondition}
";
$stmtSemCategoria = $db->prepare($produtosSemCategoriaSql);
$stmtSemCategoria->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
$stmtSemCategoria->bindValue(':tenant_id_prod', $tenantId, \PDO::PARAM_INT);
$stmtSemCategoria->execute();
$resultSemCategoria = $stmtSemCategoria->fetch();
if ($resultSemCategoria && (int)($resultSemCategoria['total'] ?? 0) > 0) {
    array_unshift($allCategories, [
        'categoria_id' => null,
        'categoria_nome' => 'Sem Categoria',
        'categoria_slug' => 'sem-categoria',
        'categoria_pai_id' => null,
        'label' => 'Sem Categoria',
    ]);
}

// Inicializar variáveis
$categoriaAtualSlug = $_GET['categoria'] ?? null;
$subcategoriasParaFiltro = [];

// Buscar categoria atual se não foi passada pelo controller mas existe no GET
if (!$categoriaAtual && $categoriaAtualSlug) {
    // Buscar categoria por slug quando vem via GET
    $stmt = $db->prepare("
        SELECT id, nome, slug, categoria_pai_id
        FROM categorias
        WHERE tenant_id = :tenant_id AND slug = :slug
        LIMIT 1
    ");
    $stmt->execute([
        'tenant_id' => $tenantId,
        'slug' => $categoriaAtualSlug
    ]);
    $categoriaAtual = $stmt->fetch();
}

// Se $categoriaAtual já veio do controller, atualizar $categoriaAtualSlug
if ($categoriaAtual && !$categoriaAtualSlug && !empty($categoriaAtual['slug'])) {
    $categoriaAtualSlug = $categoriaAtual['slug'];
}

// Se a categoria atual for "pai" (sem categoria_pai_id), buscar subcategorias
if ($categoriaAtual && empty($categoriaAtual['categoria_pai_id'])) {
    $stmt = $db->prepare("
        SELECT id, nome, slug
        FROM categorias
        WHERE tenant_id = :tenant_id AND categoria_pai_id = :pai_id
        ORDER BY nome ASC
    ");
    $stmt->execute([
        'tenant_id' => $tenantId,
        'pai_id' => $categoriaAtual['id']
    ]);
    $subcategoriasParaFiltro = $stmt->fetchAll();
}

// Construir URL base para filtros
$urlBase = $categoriaAtual ? $basePath . '/categoria/' . htmlspecialchars($categoriaAtual['slug']) : $basePath . '/produtos';

// Função para construir query string mantendo filtros
$buildQuery = function($newParams = []) use ($filtrosAtuais, $urlBase) {
    $params = array_merge($filtrosAtuais, $newParams);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    $query = http_build_query($params);
    return $urlBase . ($query ? '?' . $query : '');
};

// Capturar conteúdo principal em $content
ob_start();
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="breadcrumb-container">
        <a href="<?= $basePath ?>/">Home</a>
        <span>></span>
        <a href="<?= $basePath ?>/produtos">Loja</a>
        <?php if ($categoriaAtual): ?>
            <span>></span>
            <span><?= htmlspecialchars($categoriaAtual['nome']) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <!-- Sidebar de Filtros -->
    <aside class="filters-sidebar" id="filtersSidebar">
        <h3>Filtros</h3>
        <form method="GET" action="<?= $urlBase ?>">
            <!-- Busca -->
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filtrosAtuais['q']) ?>" placeholder="Nome ou SKU">
            </div>
            
            <!-- Categoria -->
            <?php if (!$categoriaAtual): ?>
            <div class="filter-group">
                <label>Categoria</label>
                <select name="categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categoriasFiltro as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['slug']) ?>" 
                                <?= $filtrosAtuais['categoria'] === $cat['slug'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Subcategoria (apenas se categoria atual for pai e tiver subcategorias) -->
            <?php if (!empty($subcategoriasParaFiltro)): ?>
            <div class="filter-group" style="margin-top: 12px;">
                <label for="subcategoria">Subcategoria:</label>
                <select id="subcategoria" onchange="if(this.value) window.location.href=this.value;">
                    <option value="<?= $basePath ?>/produtos?categoria=<?= urlencode($categoriaAtual['slug']) ?>"
                        <?= ($categoriaAtualSlug === $categoriaAtual['slug']) ? 'selected' : '' ?>>
                        Todas
                    </option>
                    <?php foreach ($subcategoriasParaFiltro as $sub): ?>
                        <option value="<?= $basePath ?>/produtos?categoria=<?= urlencode($sub['slug']) ?>"
                            <?= ($categoriaAtualSlug === $sub['slug']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Faixa de Preço -->
            <div class="filter-group">
                <label>Preço</label>
                <div class="price-range">
                    <input type="number" name="preco_min" value="<?= $filtrosAtuais['preco_min'] ?>" 
                           placeholder="Mín" step="0.01" min="0">
                    <input type="number" name="preco_max" value="<?= $filtrosAtuais['preco_max'] ?>" 
                           placeholder="Máx" step="0.01" min="0">
                </div>
            </div>
            
            <!-- Ordenação -->
            <div class="filter-group">
                <label>Ordenar por</label>
                <select name="ordenar">
                    <option value="novidades" <?= $filtrosAtuais['ordenar'] === 'novidades' ? 'selected' : '' ?>>Novidades</option>
                    <option value="menor_preco" <?= $filtrosAtuais['ordenar'] === 'menor_preco' ? 'selected' : '' ?>>Menor Preço</option>
                    <option value="maior_preco" <?= $filtrosAtuais['ordenar'] === 'maior_preco' ? 'selected' : '' ?>>Maior Preço</option>
                    <option value="mais_vendidos" <?= $filtrosAtuais['ordenar'] === 'mais_vendidos' ? 'selected' : '' ?>>Mais Vendidos</option>
                </select>
            </div>
            
            <button type="submit" class="btn-filter">Aplicar Filtros</button>
            <a href="<?= $urlBase ?>" class="btn-clear">Limpar Filtros</a>
        </form>
    </aside>
    
    <!-- Área de Produtos -->
    <main class="products-area">
        <button class="mobile-filters-toggle" onclick="toggleFilters()"><i class="bi bi-list icon"></i> Filtros</button>
        
        <?php if (isset($_GET['cart_message'])): ?>
            <div class="cart-message">
                <?= htmlspecialchars(urldecode($_GET['cart_message'])) ?>
            </div>
        <?php endif; ?>
        
        <div class="products-header">
            <h1 class="page-title">
                <?= $categoriaAtual ? htmlspecialchars($categoriaAtual['nome']) : 'Todos os Produtos' ?>
                <?php if ($paginacao['total'] > 0): ?>
                    <span style="font-size: 1rem; color: #666; font-weight: normal;">
                        (<?= $paginacao['total'] ?> <?= $paginacao['total'] === 1 ? 'produto' : 'produtos' ?>)
                    </span>
                <?php endif; ?>
            </h1>
            <select class="sort-select" onchange="window.location.href='<?= $buildQuery(['ordenar' => '']) ?>'.replace('ordenar=', 'ordenar=' + this.value)">
                <option value="novidades" <?= $filtrosAtuais['ordenar'] === 'novidades' ? 'selected' : '' ?>>Novidades</option>
                <option value="menor_preco" <?= $filtrosAtuais['ordenar'] === 'menor_preco' ? 'selected' : '' ?>>Menor Preço</option>
                <option value="maior_preco" <?= $filtrosAtuais['ordenar'] === 'maior_preco' ? 'selected' : '' ?>>Maior Preço</option>
                <option value="mais_vendidos" <?= $filtrosAtuais['ordenar'] === 'mais_vendidos' ? 'selected' : '' ?>>Mais Vendidos</option>
            </select>
        </div>
        
        <!-- Chips de Subcategorias -->
        <?php if (!empty($subcategoriasParaFiltro) && !empty($categoriaAtual)): ?>
            <div class="subcat-chips">
                <a class="chip <?= ($categoriaAtualSlug === $categoriaAtual['slug']) ? 'is-active' : '' ?>"
                   href="<?= $basePath ?>/produtos?categoria=<?= urlencode($categoriaAtual['slug']) ?>">
                    Todas
                </a>
                <?php foreach ($subcategoriasParaFiltro as $sub): ?>
                    <a class="chip <?= ($categoriaAtualSlug === $sub['slug']) ? 'is-active' : '' ?>"
                       href="<?= $basePath ?>/produtos?categoria=<?= urlencode($sub['slug']) ?>">
                        <?= htmlspecialchars($sub['nome']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($produtos)): ?>
            <div class="products-grid">
                <?php foreach ($produtos as $produto): ?>
                    <div class="product-card">
                        <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-image-wrapper">
                            <?php if ($produto['imagem_principal'] && !empty($produto['imagem_principal']['caminho_arquivo'])): ?>
                                <img src="<?= media_url($produto['imagem_principal']['caminho_arquivo']) ?>" 
                                     alt="<?= htmlspecialchars($produto['imagem_principal']['alt_text'] ?? $produto['nome']) ?>"
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <i class="bi bi-image icon"></i>
                                    <span>Sem imagem</span>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="product-info">
                            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-name">
                                <?= htmlspecialchars($produto['nome']) ?>
                            </a>
                            
                            <div class="product-price">
                                <?php if ($produto['preco_promocional']): ?>
                                    <span class="product-price-old">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
                                    <span class="product-price-promo">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                                <?php else: ?>
                                    R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="btn-view">Ver</a>
                                <form method="POST" action="<?= $basePath ?>/carrinho/adicionar" style="display: inline;">
                                    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
                                    <input type="hidden" name="quantidade" value="1">
                                    <button type="submit" class="btn-add">+</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginação -->
            <?php if ($paginacao['totalPages'] > 1): ?>
                <div class="pagination">
                    <?php if ($paginacao['hasPrev']): ?>
                        <a href="<?= $buildQuery(['page' => $paginacao['currentPage'] - 1]) ?>">« Anterior</a>
                    <?php else: ?>
                        <span class="disabled">« Anterior</span>
                    <?php endif; ?>
                    
                    <?php
                    $startPage = max(1, $paginacao['currentPage'] - 2);
                    $endPage = min($paginacao['totalPages'], $paginacao['currentPage'] + 2);
                    
                    if ($startPage > 1): ?>
                        <a href="<?= $buildQuery(['page' => 1]) ?>">1</a>
                        <?php if ($startPage > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $paginacao['currentPage']): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= $buildQuery(['page' => $i]) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $paginacao['totalPages']): ?>
                        <?php if ($endPage < $paginacao['totalPages'] - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="<?= $buildQuery(['page' => $paginacao['totalPages']]) ?>"><?= $paginacao['totalPages'] ?></a>
                    <?php endif; ?>
                    
                    <?php if ($paginacao['hasNext']): ?>
                        <a href="<?= $buildQuery(['page' => $paginacao['currentPage'] + 1]) ?>">Próxima »</a>
                    <?php else: ?>
                        <span class="disabled">Próxima »</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-message">
                <p>Nenhum produto encontrado com os filtros selecionados.</p>
                <a href="<?= $urlBase ?>" style="color: <?= htmlspecialchars($theme['color_primary']) ?>; margin-top: 1rem; display: inline-block;">
                    Limpar filtros
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
$content = ob_get_clean();

// CSS específico da página de listagem
$additionalStyles = '
    body {
        background: #f5f5f5;
    }
    /* Container principal */
    .container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 2rem;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Sidebar de filtros */
    .filters-sidebar {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        height: fit-content;
        position: sticky;
        top: 2rem;
    }
    .filters-sidebar h3 {
        margin-bottom: 1rem;
        font-size: 1.125rem;
        color: #333;
    }
    .filter-group {
        margin-bottom: 1.5rem;
    }
    .filter-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #555;
        font-size: 0.9rem;
    }
    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    .filter-group .price-range {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
    }
    .btn-filter {
        width: 100%;
        padding: 0.75rem;
        background: ' . htmlspecialchars($theme['color_primary']) . ';
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 600;
        margin-top: 1rem;
    }
    .btn-clear {
        width: 100%;
        padding: 0.5rem;
        background: #f0f0f0;
        color: #666;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    /* Área de produtos */
    .products-area {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .products-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .page-title {
        font-size: 1.75rem;
        color: #333;
    }
    .sort-select {
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    /* Chips de Subcategorias */
    .subcat-chips {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        overflow-x: auto;
        overflow-y: hidden;
        padding-bottom: 6px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .subcat-chips::-webkit-scrollbar {
        height: 0;
    }
    .subcat-chips .chip {
        flex: 0 0 auto;
        padding: 8px 12px;
        border: 1px solid #e5e5e5;
        border-radius: 999px;
        font-size: 14px;
        text-decoration: none;
        background: #fff;
        color: inherit;
        white-space: nowrap;
        transition: border-color 0.2s, font-weight 0.2s;
        min-width: 0;
    }
    .subcat-chips .chip:hover {
        border-color: ' . htmlspecialchars($theme['color_primary']) . ';
    }
    .subcat-chips .chip.is-active {
        border-color: ' . htmlspecialchars($theme['color_primary']) . ';
        font-weight: 600;
        color: ' . htmlspecialchars($theme['color_primary']) . ';
    }
    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 1.5rem;
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .product-card {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        height: 100%;
        min-width: 0;
        width: 100%;
    }
    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .product-image-wrapper {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
        flex-shrink: 0;
    }
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        background: #f0f0f0;
    }
    .product-image-placeholder {
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #999;
        border-bottom: 1px solid #e0e0e0;
        font-size: 0.875rem;
    }
    .product-image-placeholder .icon {
        font-size: 2.5rem;
        color: #ccc;
        margin-bottom: 0.5rem;
    }
    .product-info {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .product-name {
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #333;
        font-size: 0.95rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.8em;
        text-decoration: none;
    }
    .product-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: ' . htmlspecialchars($theme['color_primary']) . ';
        margin-bottom: 0.75rem;
        margin-top: auto;
    }
    .product-price-promo {
        color: ' . htmlspecialchars($theme['color_secondary']) . ';
    }
    .product-price-old {
        text-decoration: line-through;
        color: #999;
        font-size: 0.9rem;
        margin-right: 0.5rem;
    }
    .product-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: auto;
    }
    .btn-view {
        flex: 1;
        padding: 0.5rem;
        background: ' . htmlspecialchars($theme['color_primary']) . ';
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        text-align: center;
        font-size: 0.9rem;
    }
    .btn-add {
        padding: 0.5rem 1rem;
        background: ' . htmlspecialchars($theme['color_secondary']) . ';
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
    }
    
    /* Paginação */
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }
    .pagination a,
    .pagination span {
        padding: 0.5rem 1rem;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #333;
        min-width: 40px;
        text-align: center;
    }
    .pagination a:hover {
        background: ' . htmlspecialchars($theme['color_primary']) . ';
        color: white;
        border-color: ' . htmlspecialchars($theme['color_primary']) . ';
    }
    .pagination .disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .pagination .current {
        background: ' . htmlspecialchars($theme['color_primary']) . ';
        color: white;
        border-color: ' . htmlspecialchars($theme['color_primary']) . ';
    }
    
    /* Mobile */
    .mobile-filters-toggle {
        display: none;
        padding: 0.75rem;
        background: ' . htmlspecialchars($theme['color_primary']) . ';
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-bottom: 1rem;
        width: 100%;
    }
    .empty-message {
        text-align: center;
        padding: 3rem;
        color: #666;
        background: white;
        border-radius: 8px;
    }
    .cart-message {
        background: #4caf50;
        color: white;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        text-align: center;
    }
    
    /* Responsivo */
    @media (max-width: 1024px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.25rem;
        }
    }
    @media (max-width: 768px) {
        .container {
            grid-template-columns: 1fr;
            margin: 1.5rem auto;
            padding: 0 1rem;
            width: 100%;
            max-width: 100vw;
            box-sizing: border-box;
        }
        .filters-sidebar {
            display: none;
            position: static;
            margin-bottom: 1.5rem;
        }
        .filters-sidebar.active {
            display: block;
        }
        .mobile-filters-toggle {
            display: block;
        }
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .product-image-wrapper {
            height: 180px;
        }
        .product-image-placeholder {
            height: 180px;
        }
        .products-header {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .sort-select {
            width: 100%;
            box-sizing: border-box;
        }
        .subcat-chips {
            width: 100%;
            max-width: 100%;
            margin-top: 12px;
            min-width: 0;
        }
        .breadcrumb {
            padding: 0.75rem 1rem;
        }
    }
    @media (max-width: 480px) {
        .products-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .product-card {
            min-width: 0;
            width: 100%;
        }
        .product-image-wrapper,
        .product-image-placeholder {
            height: 160px;
        }
        .product-info {
            padding: 1rem;
        }
        .product-name {
            font-size: 0.875rem;
        }
        .product-price {
            font-size: 1.125rem;
        }
    }
';

// Script adicional
$additionalScripts = '
    <script>
        function toggleFilters() {
            const sidebar = document.getElementById(\'filtersSidebar\');
            if (sidebar) {
                sidebar.classList.toggle(\'active\');
            }
        }
    </script>
';

// Configurar variáveis para o layout base
$pageTitle = ($categoriaAtual ? htmlspecialchars($categoriaAtual['nome']) : 'Todos os Produtos') . ' – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = true;
$showNewsletter = true;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
