<?php
/**
 * Script de teste direto da rota /admin/categorias
 * Acesse via: https://pontodogolfeoutlet.com.br/public/test_rota_categorias.php
 * 
 * Este script simula exatamente o que acontece quando você acessa /admin/categorias
 */

require __DIR__ . '/../vendor/autoload.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste Rota Categorias</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo "pre{background:white;padding:15px;border-radius:4px;border:1px solid #ddd;overflow-x:auto;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>Teste Direto da Rota /admin/categorias</h1>";
echo "<hr>";

// Simular exatamente o que o index.php faz
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/admin/categorias';

// Processar URI como no index.php
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH);

$scriptDir = dirname('/public/index.php');
$scriptDir = rtrim($scriptDir, '/');

if ($scriptDir !== '' && $scriptDir !== '/') {
    if (strpos($uri, $scriptDir) === 0) {
        $uri = substr($uri, strlen($scriptDir));
    }
}

if (strpos($uri, '/ecommerce-v1.0/public') === 0) {
    $uri = substr($uri, strlen('/ecommerce-v1.0/public'));
} elseif (strpos($uri, '/public') === 0 && $uri !== '/public' && $uri !== '/public/') {
    $uri = substr($uri, strlen('/public'));
}

$uri = rtrim($uri, '/') ?: '/';

echo "<h2>1. URI Processada</h2>";
echo "<pre>";
echo "URI original: /admin/categorias\n";
echo "URI após processamento: {$uri}\n";
echo "</pre>";

// Inicializar tenant
try {
    $config = require __DIR__ . '/../config/app.php';
    $mode = $config['mode'] ?? 'single';
    
    if ($mode === 'single') {
        $defaultTenantId = $config['default_tenant_id'] ?? 1;
        \App\Tenant\TenantContext::setFixedTenant($defaultTenantId);
        echo "<p class='success'>✅ Tenant inicializado (single mode, ID: {$defaultTenantId})</p>";
    } else {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        \App\Tenant\TenantContext::resolveFromHost($host);
        echo "<p class='success'>✅ Tenant inicializado (multi-tenant mode, host: {$host})</p>";
    }
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao inicializar tenant: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='info'>ℹ️ Continuando sem tenant (pode causar erro depois)</p>";
}

// Criar router
$router = new \App\Core\Router();

// Registrar rota manualmente
$router->get('/admin/categorias', 'App\Http\Controllers\Admin\CategoriaController@index', [
    \App\Http\Middleware\AuthMiddleware::class => [false, true],
    \App\Http\Middleware\CheckPermissionMiddleware::class => 'manage_products'
]);

echo "<h2>2. Rota Registrada</h2>";
echo "<p class='success'>✅ Rota GET /admin/categorias registrada</p>";

// Verificar se método getRoutes existe
if (method_exists($router, 'getRoutes')) {
    $rotas = $router->getRoutes();
    $rotasCategorias = array_filter($rotas, function($rota) {
        return $rota['path'] === '/admin/categorias' && $rota['method'] === 'GET';
    });
    
    if (!empty($rotasCategorias)) {
        echo "<p class='success'>✅ Rota encontrada no Router</p>";
        echo "<pre>";
        foreach ($rotasCategorias as $rota) {
            echo "Método: {$rota['method']}\n";
            echo "Path: {$rota['path']}\n";
            echo "Handler: " . (is_string($rota['handler']) ? $rota['handler'] : 'Closure') . "\n";
            echo "Middlewares: " . count($rota['middlewares']) . "\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='error'>❌ Rota NÃO encontrada no Router</p>";
    }
}

// Testar matching
echo "<h2>3. Teste de Matching</h2>";

$reflection = new ReflectionClass($router);
$parseUriMethod = $reflection->getMethod('parseUri');
$parseUriMethod->setAccessible(true);

$uriParsed = $parseUriMethod->invoke($router, $uri);
echo "<p class='info'>URI após parseUri do Router: <code>{$uriParsed}</code></p>";

$pathToRegexMethod = $reflection->getMethod('pathToRegex');
$pathToRegexMethod->setAccessible(true);
$pattern = $pathToRegexMethod->invoke($router, '/admin/categorias');

echo "<p class='info'>Pattern regex: <code>" . htmlspecialchars($pattern) . "</code></p>";

$match = preg_match($pattern, $uriParsed);
if ($match) {
    echo "<p class='success'>✅ Pattern faz match com a URI!</p>";
} else {
    echo "<p class='error'>❌ Pattern NÃO faz match com a URI!</p>";
    echo "<p class='info'>URI testada: <code>{$uriParsed}</code></p>";
    echo "<p class='info'>Pattern: <code>" . htmlspecialchars($pattern) . "</code></p>";
}

// Tentar fazer dispatch (mas sem executar middlewares que precisam de sessão)
echo "<h2>4. Tentativa de Dispatch (sem middlewares de autenticação)</h2>";

// Criar router novo sem middlewares para teste
$routerTest = new \App\Core\Router();
$routerTest->get('/admin/categorias', 'App\Http\Controllers\Admin\CategoriaController@index', []);

echo "<p class='info'>ℹ️ Tentando fazer dispatch sem middlewares...</p>";

try {
    // Capturar output
    ob_start();
    
    // Tentar dispatch
    $routerTest->dispatch('GET', $uri);
    
    $output = ob_get_clean();
    
    if (!empty($output)) {
        echo "<p class='success'>✅ Dispatch executado com sucesso!</p>";
        echo "<h3>Output:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Dispatch executado mas não retornou output</p>";
    }
} catch (\Exception $e) {
    ob_end_clean();
    echo "<p class='error'>❌ Erro ao fazer dispatch: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h2>5. Conclusão</h2>";
echo "<p class='info'>ℹ️ Este teste verifica se o Router consegue fazer match e executar a rota sem middlewares.</p>";
echo "<p class='info'>ℹ️ Se o matching funcionar mas ainda retornar 404 em produção, o problema pode ser:</p>";
echo "<ul>";
echo "<li>Middleware bloqueando (retornaria 403, não 404)</li>";
echo "<li>Ordem de rotas (alguma rota anterior capturando)</li>";
echo "<li>Cache do PHP (OPcache)</li>";
echo "<li>Problema no .htaccess</li>";
echo "</ul>";

echo "</body></html>";

