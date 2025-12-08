<?php
/**
 * Script para executar a migration 033
 * Acesse: http://localhost/ecommerce-v1.0/public/exec_migration_033.php
 */

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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Executar Migration 033</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #0066cc; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Executando Migration 033 - produto_videos</h1>
        <pre>
<?php
try {
    $db = Database::getConnection();
    $runner = new MigrationRunner();
    
    // Verificar status atual
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    $applied = $runner->getAppliedMigrationsList();
    $isRegistered = in_array('033_create_produto_videos_table', $applied);
    
    echo "Status inicial:\n";
    echo "  Tabela existe: " . ($tableExists ? "SIM" : "NÃO") . "\n";
    echo "  Migration registrada: " . ($isRegistered ? "SIM" : "NÃO") . "\n\n";
    
    if ($tableExists && $isRegistered) {
        echo "<span class='success'>✓ Migration já foi aplicada anteriormente.</span>\n";
    } else {
        echo "Executando migration...\n\n";
        
        // Verificar migrations pendentes
        $pending = $runner->getPendingMigrations();
        
        if (in_array('033_create_produto_videos_table', $pending)) {
            echo "Migration encontrada na lista de pendentes.\n";
            echo "Executando via MigrationRunner...\n\n";
            
            $results = $runner->runPending();
            
            $found = false;
            foreach ($results as $result) {
                if ($result['migration'] === '033_create_produto_videos_table') {
                    $found = true;
                    if ($result['status'] === 'success') {
                        echo "<span class='success'>✓ Migration executada com sucesso!</span>\n";
                    } else {
                        echo "<span class='error'>✗ Erro: {$result['message']}</span>\n";
                    }
                    break;
                }
            }
            
            if (!$found) {
                echo "Executando diretamente...\n";
                require __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
                
                $stmt = $db->prepare("INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
                $stmt->execute(['migration' => '033_create_produto_videos_table']);
                echo "<span class='success'>✓ Migration executada e registrada!</span>\n";
            }
        } else {
            echo "Executando migration diretamente...\n";
            require __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
            
            $stmt = $db->prepare("INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
            $stmt->execute(['migration' => '033_create_produto_videos_table']);
            echo "<span class='success'>✓ Migration executada e registrada!</span>\n";
        }
    }
    
    // Verificação final
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Verificação final:\n\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    if ($tableExists) {
        echo "<span class='success'>✓ Tabela 'produto_videos' existe</span>\n\n";
        
        $stmt = $db->query("DESCRIBE produto_videos");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Estrutura:\n";
        printf("%-20s %-25s %-8s %-8s\n", "Campo", "Tipo", "Null", "Key");
        echo str_repeat("-", 70) . "\n";
        foreach ($columns as $col) {
            printf("%-20s %-25s %-8s %-8s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key']
            );
        }
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM produto_videos");
        $count = $stmt->fetch()['total'];
        echo "\nRegistros: <span class='info'>{$count}</span>\n";
    } else {
        echo "<span class='error'>✗ Tabela não existe!</span>\n";
    }
    
    $applied = $runner->getAppliedMigrationsList();
    $isRegistered = in_array('033_create_produto_videos_table', $applied);
    
    if ($isRegistered) {
        echo "<span class='success'>✓ Migration registrada</span>\n";
    }
    
    echo "\n<span class='success'>=== Concluído! ===</span>\n";
    echo "\nAcesse: <a href='../admin/produtos/1'>/admin/produtos/1</a>\n";
    
} catch (\Exception $e) {
    echo "<span class='error'>ERRO: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nTrace:\n" . htmlspecialchars($e->getTraceAsString()) . "\n";
}
?>
        </pre>
    </div>
</body>
</html>


