<?php
require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/app.php';
session_name($config['session_name']);

// Simular tenant context
$_SERVER['HTTP_HOST'] = 'pontodogolfeoutlet.com.br';
\App\Tenant\TenantContext::resolveFromHost('pontodogolfeoutlet.com.br');

$tenantId = \App\Tenant\TenantContext::id();
$db = \App\Core\Database::getConnection();

echo "=== PRODUTOS COM ATRIBUTOS/VARIAÇÕES POR CATEGORIA ===\n\n";

// 1. Total de produtos com atributos
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT p.id) as total
    FROM produtos p
    INNER JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
    WHERE p.tenant_id = :tenant_id
    AND p.status = 'publish'
");
$stmt->execute(['tenant_id' => $tenantId]);
$totalComAtributos = $stmt->fetch()['total'];
echo "Total de produtos com atributos: $totalComAtributos\n\n";

// 2. Produtos com atributos por categoria
$stmt = $db->prepare("
    SELECT 
        c.nome as categoria,
        c.slug as categoria_slug,
        COUNT(DISTINCT p.id) as total_produtos,
        COUNT(DISTINCT CASE WHEN pa.id IS NOT NULL THEN p.id END) as produtos_com_atributos,
        COUNT(DISTINCT CASE WHEN p.tipo = 'variable' THEN p.id END) as produtos_variaveis
    FROM categorias c
    LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
    LEFT JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = pc.tenant_id AND p.status = 'publish'
    LEFT JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
    WHERE c.tenant_id = :tenant_id
    GROUP BY c.id, c.nome, c.slug
    HAVING total_produtos > 0
    ORDER BY produtos_com_atributos DESC, c.nome ASC
");
$stmt->execute(['tenant_id' => $tenantId]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "PRODUTOS POR CATEGORIA:\n";
echo str_repeat('-', 100) . "\n";
printf("%-40s | %10s | %15s | %15s\n", 'CATEGORIA', 'TOTAL', 'COM ATRIBUTOS', 'VARIÁVEIS');
echo str_repeat('-', 100) . "\n";

foreach ($categorias as $cat) {
    printf("%-40s | %10d | %15d | %15d\n", 
        substr($cat['categoria'], 0, 40),
        $cat['total_produtos'],
        $cat['produtos_com_atributos'],
        $cat['produtos_variaveis']
    );
}

echo "\n\n=== DETALHES DOS ATRIBUTOS MAIS USADOS ===\n\n";

// 3. Atributos mais usados
$stmt = $db->prepare("
    SELECT 
        a.nome as atributo,
        a.tipo,
        COUNT(DISTINCT pa.produto_id) as total_produtos
    FROM atributos a
    INNER JOIN produto_atributos pa ON pa.atributo_id = a.id AND pa.tenant_id = a.tenant_id
    WHERE a.tenant_id = :tenant_id
    GROUP BY a.id, a.nome, a.tipo
    ORDER BY total_produtos DESC
    LIMIT 10
");
$stmt->execute(['tenant_id' => $tenantId]);
$atributos = $stmt->fetchAll(PDO::FETCH_ASSOC);

printf("%-30s | %-10s | %15s\n", 'ATRIBUTO', 'TIPO', 'PRODUTOS');
echo str_repeat('-', 60) . "\n";
foreach ($atributos as $attr) {
    printf("%-30s | %-10s | %15d\n", 
        substr($attr['atributo'], 0, 30),
        $attr['tipo'],
        $attr['total_produtos']
    );
}

echo "\n\n=== PRODUTOS COM TERMOS DE ATRIBUTOS (TAMANHOS) ===\n\n";

// 4. Produtos com termos específicos (tamanhos)
$stmt = $db->prepare("
    SELECT 
        c.nome as categoria,
        COUNT(DISTINCT pat.produto_id) as produtos_com_termos
    FROM categorias c
    LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
    LEFT JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
    WHERE c.tenant_id = :tenant_id
    GROUP BY c.id, c.nome
    HAVING produtos_com_termos > 0
    ORDER BY produtos_com_termos DESC
");
$stmt->execute(['tenant_id' => $tenantId]);
$categoriasComTermos = $stmt->fetchAll(PDO::FETCH_ASSOC);

printf("%-40s | %20s\n", 'CATEGORIA', 'PRODUTOS COM TERMOS');
echo str_repeat('-', 65) . "\n";
foreach ($categoriasComTermos as $cat) {
    printf("%-40s | %20d\n", 
        substr($cat['categoria'], 0, 40),
        $cat['produtos_com_termos']
    );
}

echo "\n\n=== TAMANHOS DISPONÍVEIS POR CATEGORIA ===\n\n";

// 5. Tamanhos disponíveis por categoria
$stmt = $db->prepare("
    SELECT 
        c.nome as categoria,
        GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
    FROM categorias c
    INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
    INNER JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
    INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id AND at.tenant_id = pat.tenant_id
    INNER JOIN atributos a ON a.id = at.atributo_id AND a.tenant_id = at.tenant_id
    WHERE c.tenant_id = :tenant_id
    GROUP BY c.id, c.nome
    ORDER BY c.nome
");
$stmt->execute(['tenant_id' => $tenantId]);
$tamanhosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tamanhosPorCategoria as $cat) {
    echo "• " . $cat['categoria'] . ":\n";
    echo "  Tamanhos: " . $cat['tamanhos'] . "\n\n";
}
