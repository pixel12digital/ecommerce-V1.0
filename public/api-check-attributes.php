<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_start();
}

$host = $_SERVER['HTTP_HOST'] ?? 'pontodogolfeoutlet.com.br';
\App\Tenant\TenantContext::resolveFromHost($host);

$tenantId = \App\Tenant\TenantContext::id();
$db = \App\Core\Database::getConnection();

$result = [];

// 1. Total de produtos com atributos
$stmt = $db->prepare("
    SELECT COUNT(DISTINCT p.id) as total
    FROM produtos p
    INNER JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
    WHERE p.tenant_id = :tenant_id AND p.status = 'publish'
");
$stmt->execute(['tenant_id' => $tenantId]);
$result['total_com_atributos'] = $stmt->fetch()['total'];

// 2. Total geral
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE tenant_id = :tenant_id AND status = 'publish'");
$stmt->execute(['tenant_id' => $tenantId]);
$result['total_produtos'] = $stmt->fetch()['total'];

// 3. Por categoria
$stmt = $db->prepare("
    SELECT 
        c.nome as categoria,
        COUNT(DISTINCT p.id) as total_produtos,
        COUNT(DISTINCT CASE WHEN pa.id IS NOT NULL THEN p.id END) as produtos_com_atributos,
        COUNT(DISTINCT CASE WHEN p.tipo = 'variable' THEN p.id END) as produtos_variaveis
    FROM categorias c
    LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
    LEFT JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = pc.tenant_id AND p.status = 'publish'
    LEFT JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
    WHERE c.tenant_id = :tenant_id
    GROUP BY c.id, c.nome
    HAVING total_produtos > 0
    ORDER BY produtos_com_atributos DESC
");
$stmt->execute(['tenant_id' => $tenantId]);
$result['categorias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. Atributos
$stmt = $db->prepare("
    SELECT a.nome as atributo, a.tipo, COUNT(DISTINCT pa.produto_id) as total_produtos
    FROM atributos a
    INNER JOIN produto_atributos pa ON pa.atributo_id = a.id AND pa.tenant_id = a.tenant_id
    WHERE a.tenant_id = :tenant_id
    GROUP BY a.id, a.nome, a.tipo
    ORDER BY total_produtos DESC
");
$stmt->execute(['tenant_id' => $tenantId]);
$result['atributos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. Tamanhos por categoria
$stmt = $db->prepare("
    SELECT 
        c.nome as categoria,
        GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
    FROM categorias c
    INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
    INNER JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
    INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id AND at.tenant_id = pat.tenant_id
    WHERE c.tenant_id = :tenant_id
    GROUP BY c.id, c.nome
    ORDER BY c.nome
");
$stmt->execute(['tenant_id' => $tenantId]);
$result['tamanhos_por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
