<?php
// Script temporário para diagnóstico - REMOVER APÓS USO
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular request de busca
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['q'] = 'teste';

// Detectar ambiente: produção (tudo em public_html) vs local (public/)
$vendorPath = file_exists(__DIR__ . '/../vendor/autoload.php') 
    ? __DIR__ . '/../vendor/autoload.php' 
    : __DIR__ . '/vendor/autoload.php';
require $vendorPath;

$envPath = file_exists(__DIR__ . '/../.env') ? __DIR__ . '/..' : __DIR__;
$dotenv = Dotenv\Dotenv::createImmutable($envPath);
$dotenv->load();

\App\Core\Database::init();
$db = \App\Core\Database::getConnection();

echo "<h3>Verificando colunas da tabela produtos:</h3>";

$cols = ['exibir_no_catalogo', 'data_criacao', 'created_at', 'status', 'tipo'];
foreach ($cols as $col) {
    $stmt = $db->query("SHOW COLUMNS FROM produtos LIKE '$col'");
    $exists = $stmt->rowCount() > 0 ? '✅ EXISTE' : '❌ NAO EXISTE';
    echo "$col: $exists<br>";
}

echo "<hr><h3>Testando query de busca:</h3>";
try {
    $tenantId = \App\Tenant\TenantContext::id();
    echo "Tenant ID: $tenantId<br>";
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM produtos p 
        WHERE p.tenant_id = :tenant_id 
        AND p.status = 'publish'
        AND p.exibir_no_catalogo = 1
        AND (p.nome LIKE :q OR p.sku LIKE :q)
    ");
    $stmt->execute(['tenant_id' => $tenantId, 'q' => '%teste%']);
    $result = $stmt->fetch();
    echo "Query OK! Total: " . $result['total'] . "<br>";
} catch (\Throwable $e) {
    echo "<pre style='color:red'>ERRO: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "</pre>";
}
