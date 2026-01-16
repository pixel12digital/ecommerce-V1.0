<?php
/**
 * Script de Auditoria das Bolotas de Categorias
 * 
 * Analisa todas as categorias exibidas no carrossel (bolotas) e verifica:
 * - Quantidade de produtos diretamente na categoria
 * - Quantidade de produtos nas subcategorias
 * - Status: OK_DIRETO, OK_FILHOS, VAZIO, INCONSISTENTE
 * 
 * Uso:
 *   Via web: http://seu-dominio.com/auditoria_bolotas_categorias.php?tenant_id=1
 *   Via CLI: php public/auditoria_bolotas_categorias.php --tenant-id=1
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;
use App\Tenant\TenantContext;

// Inicializar conexão
$db = Database::getConnection();

// Resolver tenant
$tenantId = null;
if (php_sapi_name() === 'cli') {
    // Modo CLI
    $options = getopt('', ['tenant-id:', 'format:']);
    $tenantId = isset($options['tenant-id']) ? (int)$options['tenant-id'] : 1;
    $format = $options['format'] ?? 'console';
} else {
    // Modo Web
    header('Content-Type: text/html; charset=utf-8');
    $tenantId = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
    $format = $_GET['format'] ?? 'html';
}

// Configurar tenant para o contexto
try {
    TenantContext::setFixedTenant($tenantId);
} catch (\Exception $e) {
    die("Erro ao carregar tenant ID {$tenantId}: " . $e->getMessage() . "\n");
}

// Verificar configuração de ocultar estoque zero
$stmt = $db->prepare("
    SELECT value FROM tenant_settings 
    WHERE tenant_id = :tenant_id AND `key` = 'catalogo_ocultar_estoque_zero'
    LIMIT 1
");
$stmt->execute(['tenant_id' => $tenantId]);
$result = $stmt->fetch();
$ocultarEstoqueZero = $result ? $result['value'] : '0';
$estoqueCondition = '';
if ($ocultarEstoqueZero === '1') {
    $estoqueCondition = " AND (p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
}

// Buscar todas as bolotas ativas
$stmt = $db->prepare("
    SELECT 
        hcp.id as pill_id,
        hcp.categoria_id,
        hcp.label as pill_label,
        hcp.ordem as pill_ordem,
        hcp.ativo as pill_ativo,
        c.id as categoria_db_id,
        c.nome as categoria_nome,
        c.slug as categoria_slug,
        c.categoria_pai_id
    FROM home_category_pills hcp
    LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
    WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
    ORDER BY hcp.ordem ASC, hcp.id ASC
");
$stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
$stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
$stmt->execute();
$bolotas = $stmt->fetchAll();

// Array para armazenar resultados da auditoria
$auditoria = [];

foreach ($bolotas as $bolota) {
    $categoriaId = $bolota['categoria_id'] ?? null;
    $categoriaDbId = $bolota['categoria_db_id'];
    
    // Verificar se categoria existe no banco
    if (!$categoriaDbId) {
        $auditoria[] = [
            'pill_id' => $bolota['pill_id'],
            'pill_label' => $bolota['pill_label'],
            'pill_ordem' => $bolota['pill_ordem'],
            'categoria_id' => $categoriaId,
            'categoria_nome' => 'CATEGORIA NÃO ENCONTRADA',
            'categoria_slug' => null,
            'categoria_pai_id' => null,
            'children_count' => 0,
            'products_count_direct' => 0,
            'products_count_children_total' => 0,
            'products_count_total' => 0,
            'status' => 'INCONSISTENTE',
            'motivo' => 'Bolota aponta para categoria_id que não existe no banco',
            'url_filtro' => null,
        ];
        continue;
    }
    
    $categoriaNome = $bolota['categoria_nome'];
    $categoriaSlug = $bolota['categoria_slug'];
    $categoriaPaiId = $bolota['categoria_pai_id'];
    
    // 1. Contar produtos diretamente na categoria
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM produtos p
        INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc
        WHERE p.tenant_id = :tenant_id_prod
        AND p.status = 'publish'
        AND p.exibir_no_catalogo = 1
        AND pc.categoria_id = :categoria_id
        {$estoqueCondition}
    ");
    $stmt->bindValue(':tenant_id_pc', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id_prod', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':categoria_id', $categoriaDbId, \PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();
    $productsCountDirect = (int)($result['total'] ?? 0);
    
    // 2. Buscar subcategorias (filhos)
    $stmt = $db->prepare("
        SELECT id FROM categorias
        WHERE tenant_id = :tenant_id AND categoria_pai_id = :categoria_pai_id
    ");
    $stmt->execute([
        'tenant_id' => $tenantId,
        'categoria_pai_id' => $categoriaDbId
    ]);
    $subcategorias = $stmt->fetchAll();
    $childrenIds = array_column($subcategorias, 'id');
    $childrenCount = count($childrenIds);
    
    // 3. Contar produtos nas subcategorias (somente filhos diretos)
    $productsCountChildrenTotal = 0;
    if ($childrenCount > 0) {
        $placeholders = implode(',', array_fill(0, count($childrenIds), '?'));
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM produtos p
            INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = ?
            WHERE p.tenant_id = ?
            AND p.status = 'publish'
            AND p.exibir_no_catalogo = 1
            AND pc.categoria_id IN ({$placeholders})
            {$estoqueCondition}
        ");
        $params = [$tenantId, $tenantId];
        $params = array_merge($params, $childrenIds);
        $stmt->execute($params);
        $result = $stmt->fetch();
        $productsCountChildrenTotal = (int)($result['total'] ?? 0);
    }
    
    $productsCountTotal = $productsCountDirect + $productsCountChildrenTotal;
    
    // 4. Determinar status
    $status = 'VAZIO';
    $motivo = '';
    
    if ($productsCountDirect > 0) {
        $status = 'OK_DIRETO';
        $motivo = 'Categoria tem produtos próprios';
    } elseif ($productsCountChildrenTotal > 0) {
        $status = 'OK_FILHOS';
        $motivo = 'Categoria pai sem produtos próprios, mas subcategorias têm produtos';
    } else {
        $status = 'VAZIO';
        $motivo = 'Categoria e subcategorias não têm produtos visíveis';
    }
    
    // Se for categoria filha mas ainda assim tem filhos (possível, mas incomum)
    if ($categoriaPaiId && $childrenCount > 0) {
        $motivo .= ' | Nota: Categoria filha também tem subcategorias';
    }
    
    // URL que será gerada pelo frontend
    $urlFiltro = $categoriaSlug ? "/produtos?categoria=" . urlencode($categoriaSlug) : null;
    
    $auditoria[] = [
        'pill_id' => $bolota['pill_id'],
        'pill_label' => $bolota['pill_label'],
        'pill_ordem' => $bolota['pill_ordem'],
        'categoria_id' => $categoriaDbId,
        'categoria_nome' => $categoriaNome,
        'categoria_slug' => $categoriaSlug,
        'categoria_pai_id' => $categoriaPaiId,
        'children_count' => $childrenCount,
        'products_count_direct' => $productsCountDirect,
        'products_count_children_total' => $productsCountChildrenTotal,
        'products_count_total' => $productsCountTotal,
        'status' => $status,
        'motivo' => $motivo,
        'url_filtro' => $urlFiltro,
    ];
}

// Agrupar por status para resumo
$resumo = [
    'OK_DIRETO' => 0,
    'OK_FILHOS' => 0,
    'VAZIO' => 0,
    'INCONSISTENTE' => 0,
];
foreach ($auditoria as $item) {
    $resumo[$item['status']] = ($resumo[$item['status']] ?? 0) + 1;
}

// Output baseado no formato
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'tenant_id' => $tenantId,
        'ocultar_estoque_zero' => $ocultarEstoqueZero,
        'resumo' => $resumo,
        'total_bolotas' => count($auditoria),
        'auditoria' => $auditoria,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} elseif ($format === 'html') {
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria de Bolotas de Categorias</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2E7D32; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .resumo { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .card { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #2E7D32; }
        .card h3 { margin: 0 0 10px 0; font-size: 14px; color: #666; text-transform: uppercase; }
        .card .valor { font-size: 32px; font-weight: bold; color: #2E7D32; }
        .card.INCONSISTENTE { border-left-color: #dc3545; }
        .card.INCONSISTENTE .valor { color: #dc3545; }
        .card.VAZIO { border-left-color: #ffc107; }
        .card.VAZIO .valor { color: #ffc107; }
        .card.OK_FILHOS { border-left-color: #17a2b8; }
        .card.OK_FILHOS .valor { color: #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #2E7D32; color: white; padding: 12px; text-align: left; font-weight: 600; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f8f9fa; }
        .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status.OK_DIRETO { background: #d4edda; color: #155724; }
        .status.OK_FILHOS { background: #d1ecf1; color: #0c5460; }
        .status.VAZIO { background: #fff3cd; color: #856404; }
        .status.INCONSISTENTE { background: #f8d7da; color: #721c24; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-pai { background: #e7f3ff; color: #0066cc; }
        .badge-filho { background: #fff4e6; color: #cc6600; }
        .url-filtro { font-family: monospace; font-size: 11px; color: #666; }
        .info-box { background: #e7f3ff; border-left: 4px solid #0066cc; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .info-box strong { color: #004499; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Auditoria de Bolotas de Categorias</h1>
        
        <div class="info-box">
            <strong>Tenant ID:</strong> <?= $tenantId ?><br>
            <strong>Ocultar Estoque Zero:</strong> <?= $ocultarEstoqueZero === '1' ? 'Sim' : 'Não' ?><br>
            <strong>Total de Bolotas Ativas:</strong> <?= count($auditoria) ?>
        </div>
        
        <h2>📊 Resumo por Status</h2>
        <div class="resumo">
            <div class="card OK_DIRETO">
                <h3>✅ OK (Produtos Diretos)</h3>
                <div class="valor"><?= $resumo['OK_DIRETO'] ?></div>
            </div>
            <div class="card OK_FILHOS">
                <h3>⚠️ OK (Produtos nos Filhos)</h3>
                <div class="valor"><?= $resumo['OK_FILHOS'] ?></div>
            </div>
            <div class="card VAZIO">
                <h3>⚠️ Vazio</h3>
                <div class="valor"><?= $resumo['VAZIO'] ?></div>
            </div>
            <div class="card INCONSISTENTE">
                <h3>❌ Inconsistente</h3>
                <div class="valor"><?= $resumo['INCONSISTENTE'] ?></div>
            </div>
        </div>
        
        <h2>📋 Detalhes das Bolotas</h2>
        <table>
            <thead>
                <tr>
                    <th>Ordem</th>
                    <th>Label</th>
                    <th>Categoria</th>
                    <th>Tipo</th>
                    <th>Filhos</th>
                    <th>Produtos (Direto)</th>
                    <th>Produtos (Filhos)</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>URL Filtro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditoria as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['pill_ordem']) ?></td>
                    <td><strong><?= htmlspecialchars($item['pill_label'] ?: '-') ?></strong></td>
                    <td>
                        <?= htmlspecialchars($item['categoria_nome']) ?><br>
                        <small style="color: #666;">ID: <?= $item['categoria_id'] ?> | Slug: <?= htmlspecialchars($item['categoria_slug'] ?: '-') ?></small>
                    </td>
                    <td>
                        <?php if ($item['categoria_pai_id']): ?>
                            <span class="badge badge-filho">Filho</span>
                        <?php else: ?>
                            <span class="badge badge-pai">Pai</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $item['children_count'] ?></td>
                    <td><?= $item['products_count_direct'] ?></td>
                    <td><?= $item['products_count_children_total'] ?></td>
                    <td><strong><?= $item['products_count_total'] ?></strong></td>
                    <td>
                        <span class="status <?= $item['status'] ?>">
                            <?= $item['status'] ?>
                        </span><br>
                        <small style="color: #666; font-size: 10px;"><?= htmlspecialchars($item['motivo']) ?></small>
                    </td>
                    <td class="url-filtro"><?= htmlspecialchars($item['url_filtro'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="info-box" style="margin-top: 30px;">
            <h3 style="margin-top: 0;">💡 Interpretação dos Status</h3>
            <ul>
                <li><strong>OK_DIRETO:</strong> Categoria tem produtos próprios. Funciona perfeitamente.</li>
                <li><strong>OK_FILHOS:</strong> Categoria pai sem produtos próprios, mas subcategorias têm. <strong>PROBLEMA:</strong> Ao clicar na bolota, usuário verá "nenhum produto" porque o backend não inclui produtos dos filhos.</li>
                <li><strong>VAZIO:</strong> Categoria e subcategorias não têm produtos visíveis. Deveria ser removida das bolotas ou ter produtos adicionados.</li>
                <li><strong>INCONSISTENTE:</strong> Bolota aponta para categoria inexistente ou inválida. Requer correção imediata.</li>
            </ul>
        </div>
    </div>
</body>
</html>
    <?php
} else {
    // Formato console (texto)
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "  AUDITORIA DE BOLOTAS DE CATEGORIAS - Tenant ID: {$tenantId}\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Ocultar Estoque Zero: " . ($ocultarEstoqueZero === '1' ? 'Sim' : 'Não') . "\n";
    echo "Total de Bolotas Ativas: " . count($auditoria) . "\n";
    echo "\n";
    echo "📊 RESUMO POR STATUS:\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "  ✅ OK_DIRETO      (tem produtos próprios): " . str_pad($resumo['OK_DIRETO'], 3) . "\n";
    echo "  ⚠️  OK_FILHOS     (produtos só nos filhos): " . str_pad($resumo['OK_FILHOS'], 3) . " ⚠️ PROBLEMA\n";
    echo "  ⚠️  VAZIO         (sem produtos):           " . str_pad($resumo['VAZIO'], 3) . "\n";
    echo "  ❌ INCONSISTENTE  (categoria inválida):     " . str_pad($resumo['INCONSISTENTE'], 3) . "\n";
    echo "\n";
    echo "📋 DETALHES DAS BOLOTAS:\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    
    foreach ($auditoria as $item) {
        echo sprintf(
            "[Ordem: %-3s] %-30s | %-30s | Tipo: %-4s | Filhos: %-2s | Prod: %-3s/%s | Total: %-3s | %s\n",
            $item['pill_ordem'],
            mb_substr($item['pill_label'] ?: '-', 0, 30),
            mb_substr($item['categoria_nome'], 0, 30),
            $item['categoria_pai_id'] ? 'Filho' : 'Pai',
            $item['children_count'],
            $item['products_count_direct'],
            $item['products_count_children_total'],
            $item['products_count_total'],
            $item['status']
        );
        echo "                    Slug: {$item['categoria_slug']} | ID: {$item['categoria_id']}\n";
        echo "                    {$item['motivo']}\n";
        if ($item['url_filtro']) {
            echo "                    URL: {$item['url_filtro']}\n";
        }
        echo "\n";
    }
    
    echo "\n";
    echo "💡 INTERPRETAÇÃO:\n";
    echo "───────────────────────────────────────────────────────────────────────────────\n";
    echo "  • OK_DIRETO: Funciona perfeitamente\n";
    echo "  • OK_FILHOS: ⚠️ PROBLEMA - Ao clicar na bolota, usuário verá 'nenhum produto'\n";
    echo "                porque o backend não inclui produtos dos filhos.\n";
    echo "  • VAZIO: Deveria ser removida das bolotas ou ter produtos adicionados.\n";
    echo "  • INCONSISTENTE: Requer correção imediata.\n";
    echo "\n";
}
