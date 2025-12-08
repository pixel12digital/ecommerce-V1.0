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

echo "<h1>Verificação de Migration 033 - produto_videos</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getConnection();
    
    // Verificar se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    if ($tableExists) {
        echo "✓ Tabela 'produto_videos' existe\n\n";
        
        // Mostrar estrutura
        $stmt = $db->query("DESCRIBE produto_videos");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Estrutura da tabela:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-20s %-10s %-10s %-10s\n", "Campo", "Tipo", "Null", "Key", "Default");
        echo str_repeat("-", 80) . "\n";
        foreach ($columns as $col) {
            printf("%-20s %-20s %-10s %-10s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key'], 
                $col['Default'] ?? 'NULL'
            );
        }
        echo str_repeat("-", 80) . "\n\n";
        
        // Contar registros
        $stmt = $db->query("SELECT COUNT(*) as total FROM produto_videos");
        $count = $stmt->fetch()['total'];
        echo "Registros na tabela: {$count}\n\n";
    } else {
        echo "✗ Tabela 'produto_videos' NÃO existe\n\n";
    }
    
    // Verificar se a migration foi registrada
    $runner = new MigrationRunner();
    $applied = $runner->getAppliedMigrationsList();
    
    echo "Migrations aplicadas: " . count($applied) . "\n";
    if (in_array('033_create_produto_videos_table', $applied)) {
        echo "✓ Migration 033_create_produto_videos_table está registrada\n";
    } else {
        echo "✗ Migration 033_create_produto_videos_table NÃO está registrada\n";
    }
    
    // Verificar migrations pendentes
    $pending = $runner->getPendingMigrations();
    if (!empty($pending)) {
        echo "\nMigrations pendentes:\n";
        foreach ($pending as $migration) {
            echo "  - {$migration}\n";
        }
    } else {
        echo "\n✓ Nenhuma migration pendente\n";
    }
    
} catch (\Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";


