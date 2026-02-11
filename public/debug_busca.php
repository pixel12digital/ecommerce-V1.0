<?php
// Script temporário para diagnóstico - REMOVER APÓS USO
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Simular request
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'pontodogolfeoutlet.com.br';
$_SERVER['REQUEST_URI'] = '/produtos?q=teste';
$_GET['q'] = 'teste';

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

echo "=== PASSO 1: Resolver Tenant ===\n";
try {
    $tenantResolver = new \App\Http\Middleware\TenantResolverMiddleware();
    $tenantResolver->handle();
    $tenantId = \App\Tenant\TenantContext::id();
    echo "Tenant ID: $tenantId OK\n";
} catch (\Throwable $e) {
    die("ERRO Tenant: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n");
}

echo "\n=== PASSO 2: ThemeConfig ===\n";
try {
    $theme = \App\Services\ThemeConfig::getFullThemeConfig();
    echo "ThemeConfig OK\n";
} catch (\Throwable $e) {
    die("ERRO ThemeConfig: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n");
}

echo "\n=== PASSO 3: CartService ===\n";
try {
    $cartItems = \App\Services\CartService::getTotalItems();
    echo "CartService OK: $cartItems itens\n";
} catch (\Throwable $e) {
    die("ERRO CartService: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine() . "\n");
}

echo "\n=== PASSO 4: ProductController::index ===\n";
try {
    $controller = new \App\Http\Controllers\Storefront\ProductController();
    $controller->index();
    echo "\nProductController OK\n";
} catch (\Throwable $e) {
    echo "\nERRO ProductController: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
