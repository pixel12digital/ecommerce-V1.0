<?php
/**
 * Script temporário para verificar configurações de tema no banco
 * Acesse: http://localhost/ecommerce-v1.0/public/check_theme_settings.php
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

echo "<h1>Verificação de Configurações de Tema</h1>";
echo "<pre>";

// Verificar configurações do tenant_id = 1
$stmt = $db->prepare("SELECT `key`, value FROM tenant_settings WHERE tenant_id = 1 ORDER BY `key`");
$stmt->execute();
$settings = $stmt->fetchAll();

echo "Configurações de tema para tenant_id = 1:\n\n";
if (empty($settings)) {
    echo "✗ Nenhuma configuração encontrada. Execute o seed novamente.\n";
} else {
    echo "✓ Total de configurações: " . count($settings) . "\n\n";
    foreach ($settings as $setting) {
        $value = $setting['value'];
        if (strlen($value) > 100) {
            $value = substr($value, 0, 100) . '...';
        }
        echo "  - {$setting['key']}: {$value}\n";
    }
}

echo "\n\n";
echo "Para executar o seed novamente, execute no terminal:\n";
echo "  php database/run_seed.php\n";
echo "</pre>";


