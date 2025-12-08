<?php
/**
 * Script temporário para verificar produtos no banco
 * Acesse: http://localhost/ecommerce-v1.0/public/check_products.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
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

$db = Database::getConnection();

echo "<h1>Verificação de Produtos no Banco</h1>";
echo "<pre>";

// 1. Total geral
$stmt = $db->query("SELECT COUNT(*) AS total FROM produtos");
$result = $stmt->fetch();
echo "1. Total de produtos: " . $result['total'] . "\n\n";

// 2. Por tenant_id
$stmt = $db->query("SELECT tenant_id, COUNT(*) AS total FROM produtos GROUP BY tenant_id");
$results = $stmt->fetchAll();
echo "2. Produtos por tenant_id:\n";
if (empty($results)) {
    echo "   Nenhum produto encontrado\n";
} else {
    foreach ($results as $row) {
        echo "   tenant_id {$row['tenant_id']}: {$row['total']} produtos\n";
    }
}
echo "\n";

// 3. Por status (tenant_id = 1)
$stmt = $db->query("SELECT status, COUNT(*) AS total FROM produtos WHERE tenant_id = 1 GROUP BY status");
$results = $stmt->fetchAll();
echo "3. Produtos por status (tenant_id = 1):\n";
if (empty($results)) {
    echo "   Nenhum produto encontrado para tenant_id = 1\n";
} else {
    foreach ($results as $row) {
        echo "   status '{$row['status']}': {$row['total']} produtos\n";
    }
}
echo "\n";

// 4. Por destaque (tenant_id = 1)
$stmt = $db->query("SELECT destaque, COUNT(*) AS total FROM produtos WHERE tenant_id = 1 GROUP BY destaque");
$results = $stmt->fetchAll();
echo "4. Produtos por destaque (tenant_id = 1):\n";
if (empty($results)) {
    echo "   Nenhum produto encontrado para tenant_id = 1\n";
} else {
    foreach ($results as $row) {
        echo "   destaque = {$row['destaque']}: {$row['total']} produtos\n";
    }
}
echo "\n";

// 5. Produtos publicados e em destaque
$stmt = $db->query("SELECT COUNT(*) AS total FROM produtos WHERE tenant_id = 1 AND status = 'publish' AND destaque = 1");
$result = $stmt->fetch();
echo "5. Produtos publicados E em destaque (tenant_id = 1): {$result['total']}\n\n";

// 6. Produtos publicados (qualquer destaque)
$stmt = $db->query("SELECT COUNT(*) AS total FROM produtos WHERE tenant_id = 1 AND status = 'publish'");
$result = $stmt->fetch();
echo "6. Produtos publicados (qualquer destaque, tenant_id = 1): {$result['total']}\n\n";

// 7. Verificar imagens
$stmt = $db->query("SELECT COUNT(*) AS total FROM produto_imagens WHERE tenant_id = 1");
$result = $stmt->fetch();
echo "7. Total de imagens registradas (tenant_id = 1): {$result['total']}\n\n";

// 8. Imagens por tipo
$stmt = $db->query("SELECT tipo, COUNT(*) AS total FROM produto_imagens WHERE tenant_id = 1 GROUP BY tipo");
$results = $stmt->fetchAll();
echo "8. Imagens por tipo (tenant_id = 1):\n";
if (empty($results)) {
    echo "   Nenhuma imagem encontrada\n";
} else {
    foreach ($results as $row) {
        echo "   tipo '{$row['tipo']}': {$row['total']} imagens\n";
    }
}
echo "\n";

// 9. Produtos com imagens
$stmt = $db->query("SELECT COUNT(DISTINCT produto_id) AS total FROM produto_imagens WHERE tenant_id = 1");
$result = $stmt->fetch();
echo "9. Produtos com imagens (tenant_id = 1): {$result['total']}\n\n";

// 10. Produtos com imagem principal
$stmt = $db->query("SELECT COUNT(*) AS total FROM produtos WHERE tenant_id = 1 AND imagem_principal IS NOT NULL AND imagem_principal != ''");
$result = $stmt->fetch();
echo "10. Produtos com imagem_principal preenchida (tenant_id = 1): {$result['total']}\n\n";

echo "</pre>";
echo "<p><a href='/ecommerce-v1.0/public/'>← Voltar</a></p>";

