<?php
/**
 * Script temporário para verificar tabelas da Fase 2
 * Acesse: http://localhost/ecommerce-v1.0/public/check_fase2_tables.php
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

echo "<h1>Verificação de Tabelas da Fase 2</h1>";
echo "<pre>";

$tables = [
    'home_category_pills',
    'home_category_sections',
    'banners',
    'newsletter_inscricoes'
];

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->fetch();
        
        if ($exists) {
            $stmt = $db->query("SELECT COUNT(*) as total FROM {$table}");
            $result = $stmt->fetch();
            echo "✓ Tabela '{$table}' existe ({$result['total']} registros)\n";
        } else {
            echo "✗ Tabela '{$table}' NÃO existe\n";
        }
    } catch (Exception $e) {
        echo "✗ Erro ao verificar '{$table}': " . $e->getMessage() . "\n";
    }
}

echo "\n\n";
echo "Se todas as tabelas existem, a Fase 2 está pronta para uso!\n";
echo "</pre>";


