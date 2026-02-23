<?php

require_once __DIR__ . '/vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/.env';
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
use App\Services\MigrationRunner;

try {
    $db = Database::getConnection();
    $runner = new MigrationRunner();
    
    echo "Executando migration 061_add_additional_info_to_produtos...\n";
    
    $results = $runner->runPending();
    
    foreach ($results as $result) {
        echo "Migration: " . $result['migration'] . "\n";
        echo "Status: " . $result['status'] . "\n";
        if (isset($result['message'])) {
            echo "Mensagem: " . $result['message'] . "\n";
        }
        echo "\n";
    }
    
    echo "Concluído!\n";
    
} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
}
