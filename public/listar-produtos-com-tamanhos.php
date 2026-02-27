<?php
// Lista produtos que já têm tamanhos cadastrados
header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/../vendor/autoload.php';

try {
    $db = \App\Core\Database::getConnection();
    $tenantId = 1;
    
    echo "=== PRODUTOS COM TAMANHOS/VARIAÇÕES CADASTRADOS ===\n\n";
    
    // Buscar produtos que têm termos de atributos (tamanhos)
    $stmt = $db->prepare("
        SELECT 
            p.id,
            p.nome as produto,
            p.sku,
            p.tipo,
            c.nome as categoria,
            GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
        FROM produtos p
        INNER JOIN produto_atributo_termos pat ON pat.produto_id = p.id
        INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
        LEFT JOIN produto_categorias pc ON pc.produto_id = p.id
        LEFT JOIN categorias c ON c.id = pc.categoria_id
        WHERE p.tenant_id = ?
        AND p.status = 'publish'
        GROUP BY p.id, p.nome, p.sku, p.tipo, c.nome
        ORDER BY c.nome, p.nome
    ");
    $stmt->execute([$tenantId]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($produtos)) {
        echo "Nenhum produto com tamanhos cadastrados encontrado.\n";
        exit;
    }
    
    echo "Total: " . count($produtos) . " produtos\n";
    echo str_repeat('=', 100) . "\n\n";
    
    $categoriaAtual = '';
    foreach ($produtos as $p) {
        // Agrupar por categoria
        if ($categoriaAtual !== $p['categoria']) {
            $categoriaAtual = $p['categoria'];
            echo "\n📁 " . strtoupper($categoriaAtual ?: 'SEM CATEGORIA') . "\n";
            echo str_repeat('-', 100) . "\n";
        }
        
        printf("  • %-50s | Tamanhos: %-30s | Tipo: %s\n", 
            substr($p['produto'], 0, 50),
            $p['tamanhos'],
            $p['tipo']
        );
    }
    
    // Resumo por categoria
    echo "\n\n=== RESUMO POR CATEGORIA ===\n\n";
    $stmt = $db->prepare("
        SELECT 
            c.nome as categoria,
            COUNT(DISTINCT p.id) as total_produtos
        FROM produtos p
        INNER JOIN produto_atributo_termos pat ON pat.produto_id = p.id
        LEFT JOIN produto_categorias pc ON pc.produto_id = p.id
        LEFT JOIN categorias c ON c.id = pc.categoria_id
        WHERE p.tenant_id = ?
        AND p.status = 'publish'
        GROUP BY c.nome
        ORDER BY total_produtos DESC
    ");
    $stmt->execute([$tenantId]);
    $resumo = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resumo as $r) {
        printf("%-40s: %3d produtos\n", $r['categoria'] ?: 'SEM CATEGORIA', $r['total_produtos']);
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
