<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Services\MigrationRunner;

$runner = new MigrationRunner();
$pending = $runner->getPendingMigrations();

if (empty($pending)) {
    echo "✓ Nenhuma migration pendente. Todas as migrations já foram aplicadas.\n";
    echo "\nPara verificar quais migrations foram aplicadas, consulte a tabela 'migrations' no banco de dados.\n";
    exit(0);
}

echo "Migrations pendentes encontradas: " . count($pending) . "\n";
echo "Executando migrations...\n\n";

$results = $runner->runPending();

$successCount = 0;
$errorCount = 0;

echo "Resultado:\n";
echo str_repeat("=", 50) . "\n";
foreach ($results as $result) {
    if ($result['status'] === 'success') {
        echo "✓ {$result['migration']}\n";
        $successCount++;
    } else {
        echo "✗ {$result['migration']}: {$result['message']}\n";
        $errorCount++;
    }
}

echo str_repeat("=", 50) . "\n";
echo "\nResumo:\n";
echo "  Sucesso: {$successCount}\n";
echo "  Erros: {$errorCount}\n";

if ($successCount > 0) {
    echo "\n✓ Migrations aplicadas com sucesso!\n";
}

if ($errorCount > 0) {
    echo "\n⚠ Atenção: Algumas migrations falharam. Verifique os erros acima.\n";
    exit(1);
}

