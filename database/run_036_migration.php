<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
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
use App\Services\MigrationRunner;

echo "Executando migration 036_create_produto_avaliacoes_table...\n\n";

try {
    $runner = new MigrationRunner();
    
    // Verificar migrations pendentes
    $pending = $runner->getPendingMigrations();
    
    if (empty($pending)) {
        echo "Nenhuma migration pendente.\n";
        exit(0);
    }
    
    echo "Migrations pendentes encontradas: " . count($pending) . "\n";
    foreach ($pending as $m) {
        echo "  - {$m}\n";
    }
    
    echo "\nExecutando migrations...\n\n";
    
    $results = $runner->runPending();
    
    $success = 0;
    $errors = 0;
    
    foreach ($results as $result) {
        if ($result['status'] === 'success') {
            echo "✓ {$result['migration']}\n";
            $success++;
        } else {
            echo "✗ {$result['migration']}: {$result['message']}\n";
            $errors++;
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Resumo: {$success} sucesso(s), {$errors} erro(s)\n";
    
    if ($errors > 0) {
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}


