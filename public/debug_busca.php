<?php
// Script temporário para diagnóstico - REMOVER APÓS USO
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env manualmente (mesmo approach do index.php)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

try {
    \App\Core\Database::init();
    $db = \App\Core\Database::getConnection();
    echo "DB conectado OK<br>";
} catch (\Throwable $e) {
    die("ERRO DB: " . $e->getMessage());
}

echo "<h3>Colunas da tabela produtos:</h3>";
$cols = ['exibir_no_catalogo', 'data_criacao', 'created_at', 'status', 'tipo', 'sku'];
foreach ($cols as $col) {
    $stmt = $db->query("SHOW COLUMNS FROM produtos LIKE '$col'");
    echo "$col: " . ($stmt->rowCount() > 0 ? 'EXISTE' : 'NAO EXISTE') . "<br>";
}

echo "<hr><h3>Testando query de busca:</h3>";
try {
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM produtos p 
        WHERE p.tenant_id = 1
        AND p.status = 'publish'
    ");
    $stmt->execute();
    echo "Query basica OK: " . $stmt->fetch()['total'] . " produtos<br>";

    $stmt2 = $db->prepare("
        SELECT COUNT(*) as total 
        FROM produtos p 
        WHERE p.tenant_id = 1
        AND p.status = 'publish'
        AND p.exibir_no_catalogo = 1
    ");
    $stmt2->execute();
    echo "Query com exibir_no_catalogo OK: " . $stmt2->fetch()['total'] . " produtos<br>";
} catch (\Throwable $e) {
    echo "<pre style='color:red'>ERRO: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "</pre>";
}
