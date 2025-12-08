<?php
/**
 * Script de teste para verificar se o ambiente está configurado corretamente
 * Acesse: http://localhost/test.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carregar autoloader primeiro
try {
    require_once __DIR__ . '/../vendor/autoload.php';
} catch (\Exception $e) {
    die("✗ Erro ao carregar autoloader: " . $e->getMessage() . "<br>Verifique se executou 'composer install'");
}

// Importar classes necessárias no topo
use App\Core\Database;
use App\Tenant\TenantContext;

echo "<h1>Teste de Configuração</h1>";

// 1. Verificar autoloader
echo "<h2>1. Autoloader</h2>";
echo "✓ Autoloader carregado<br>";

// 2. Verificar .env
echo "<h2>2. Arquivo .env</h2>";
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    echo "✓ Arquivo .env existe<br>";
    
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
    echo "✓ Variáveis de ambiente carregadas<br>";
} else {
    echo "✗ Arquivo .env não encontrado<br>";
}

// 3. Verificar banco de dados
echo "<h2>3. Conexão com Banco de Dados</h2>";
try {
    $db = Database::getConnection();
    echo "✓ Conexão com banco estabelecida<br>";
    
    // Verificar tabelas
    $tables = ['tenants', 'platform_users', 'store_users', 'produtos', 'categorias'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT COUNT(*) as total FROM {$table}");
            $result = $stmt->fetch();
            echo "✓ Tabela '{$table}' existe ({$result['total']} registros)<br>";
        } catch (\Exception $e) {
            echo "✗ Tabela '{$table}' não existe ou erro: " . $e->getMessage() . "<br>";
        }
    }
} catch (\Exception $e) {
    echo "✗ Erro ao conectar ao banco: " . $e->getMessage() . "<br>";
}

// 4. Verificar TenantContext
echo "<h2>4. TenantContext</h2>";
try {
    $config = require __DIR__ . '/../config/app.php';
    $mode = $config['mode'] ?? 'single';
    $defaultTenantId = $config['default_tenant_id'] ?? 1;
    
    TenantContext::setFixedTenant($defaultTenantId);
    $tenant = TenantContext::tenant();
    echo "✓ Tenant resolvido: {$tenant->name} (ID: {$tenant->id})<br>";
} catch (\Exception $e) {
    echo "✗ Erro ao resolver tenant: " . $e->getMessage() . "<br>";
}

// 5. Verificar rotas
echo "<h2>5. Rotas</h2>";
echo "✓ Rotas configuradas:<br>";
$basePath = '/ecommerce-v1.0/public';
echo "<ul>";
echo "<li><a href='{$basePath}/admin/platform/login'>{$basePath}/admin/platform/login</a> - Platform Admin Login</li>";
echo "<li><a href='{$basePath}/admin/login'>{$basePath}/admin/login</a> - Store Admin Login</li>";
echo "<li><a href='{$basePath}/'>{$basePath}/</a> - Home</li>";
echo "</ul>";

// 6. Verificar views
echo "<h2>6. Views</h2>";
$views = [
    'admin/platform/login',
    'admin/store/login',
];
foreach ($views as $view) {
    $viewPath = __DIR__ . '/../themes/default/' . $view . '.php';
    if (file_exists($viewPath)) {
        echo "✓ View '{$view}' existe<br>";
    } else {
        echo "✗ View '{$view}' não encontrada<br>";
    }
}

echo "<hr>";
echo "<h2>Conclusão</h2>";
echo "<p>Se todos os itens acima estão marcados com ✓, o sistema deve estar funcionando.</p>";
$basePath = '/ecommerce-v1.0/public';
echo "<p><a href='{$basePath}/admin/platform/login'>Testar Platform Admin Login</a> | <a href='{$basePath}/admin/login'>Testar Store Admin Login</a></p>";

