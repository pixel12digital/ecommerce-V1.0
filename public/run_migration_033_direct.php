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

header('Content-Type: text/html; charset=utf-8');
echo "<h1>Executando Migration 033 - produto_videos</h1>\n";
echo "<pre>\n";

try {
    $db = Database::getConnection();
    
    // Verificar se já foi aplicada
    $runner = new MigrationRunner();
    $applied = $runner->getAppliedMigrationsList();
    
    if (in_array('033_create_produto_videos_table', $applied)) {
        echo "✓ Migration 033 já foi aplicada anteriormente.\n";
        echo "\nVerificando tabela...\n";
    } else {
        echo "Executando migration 033_create_produto_videos_table...\n";
        
        // Executar migration
        $migrationFile = __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
        if (file_exists($migrationFile)) {
            require $migrationFile;
            
            // Registrar migration
            $stmt = $db->prepare("INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
            $stmt->execute(['migration' => '033_create_produto_videos_table']);
            
            echo "✓ Migration executada com sucesso!\n";
        } else {
            echo "✗ Arquivo de migration não encontrado: {$migrationFile}\n";
        }
    }
    
    // Verificar se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    if ($tableExists) {
        echo "\n✓ Tabela 'produto_videos' criada com sucesso!\n\n";
        
        // Mostrar estrutura
        $stmt = $db->query("DESCRIBE produto_videos");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Estrutura da tabela:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-20s %-10s %-10s\n", "Campo", "Tipo", "Null", "Key");
        echo str_repeat("-", 80) . "\n";
        foreach ($columns as $col) {
            printf("%-20s %-20s %-10s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key']
            );
        }
        echo str_repeat("-", 80) . "\n";
    } else {
        echo "\n✗ ERRO: Tabela 'produto_videos' não foi criada!\n";
    }
    
    // Verificar todas as migrations aplicadas
    echo "\n\nMigrations aplicadas: " . count($applied) . "\n";
    $allPending = $runner->getPendingMigrations();
    if (!empty($allPending)) {
        echo "Migrations pendentes: " . count($allPending) . "\n";
        foreach ($allPending as $migration) {
            echo "  - {$migration}\n";
        }
    } else {
        echo "✓ Todas as migrations foram aplicadas!\n";
    }
    
} catch (\Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='check_migration_033.php'>Verificar status da migration</a></p>\n";


