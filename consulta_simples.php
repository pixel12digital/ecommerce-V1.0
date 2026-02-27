<?php
require __DIR__ . '/vendor/autoload.php';

try {
    // Usar a classe Database do projeto
    $db = \App\Core\Database::getConnection();
    
    $tenantId = 1; // Ponto do Golfe
    
    echo "=== PRODUTOS COM ATRIBUTOS/VARIAÇÕES ===\n\n";
    
    // 1. Total geral
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE tenant_id = ? AND status = 'publish'");
    $stmt->execute([$tenantId]);
    $totalProdutos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de produtos publicados: $totalProdutos\n";
    
    // 2. Com atributos
    $stmt = $db->prepare("
        SELECT COUNT(DISTINCT p.id) as total
        FROM produtos p
        INNER JOIN produto_atributos pa ON pa.produto_id = p.id
        WHERE p.tenant_id = ? AND p.status = 'publish'
    ");
    $stmt->execute([$tenantId]);
    $comAtributos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $percent = $totalProdutos > 0 ? round(($comAtributos / $totalProdutos) * 100, 1) : 0;
    echo "Produtos com atributos: $comAtributos ($percent%)\n\n";
    
    // 3. Por categoria
    echo "=== POR CATEGORIA ===\n\n";
    $stmt = $db->prepare("
        SELECT 
            c.nome as categoria,
            COUNT(DISTINCT p.id) as total,
            COUNT(DISTINCT CASE WHEN pa.id IS NOT NULL THEN p.id END) as com_atributos,
            COUNT(DISTINCT CASE WHEN p.tipo = 'variable' THEN p.id END) as variaveis
        FROM categorias c
        LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id
        LEFT JOIN produtos p ON p.id = pc.produto_id AND p.status = 'publish'
        LEFT JOIN produto_atributos pa ON pa.produto_id = p.id
        WHERE c.tenant_id = ?
        GROUP BY c.id, c.nome
        HAVING total > 0
        ORDER BY com_atributos DESC
    ");
    $stmt->execute([$tenantId]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($categorias as $cat) {
        $p = $cat['total'] > 0 ? round(($cat['com_atributos'] / $cat['total']) * 100, 1) : 0;
        printf("%-40s | Total: %3d | Com Atributos: %3d (%5.1f%%) | Variáveis: %3d\n", 
            $cat['categoria'], $cat['total'], $cat['com_atributos'], $p, $cat['variaveis']);
    }
    
    // 4. Tamanhos por categoria
    echo "\n\n=== TAMANHOS POR CATEGORIA ===\n\n";
    $stmt = $db->prepare("
        SELECT 
            c.nome as categoria,
            GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
        FROM categorias c
        INNER JOIN produto_categorias pc ON pc.categoria_id = c.id
        INNER JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id
        INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
        WHERE c.tenant_id = ?
        GROUP BY c.id, c.nome
        ORDER BY c.nome
    ");
    $stmt->execute([$tenantId]);
    $tamanhos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tamanhos as $t) {
        echo "• {$t['categoria']}:\n  {$t['tamanhos']}\n\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
