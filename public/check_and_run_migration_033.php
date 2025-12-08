<?php
/**
 * Script para verificar e executar a migration 033 se necessário
 * Acesse: http://localhost/ecommerce-v1.0/public/check_and_run_migration_033.php
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
    <title>Verificar e Executar Migration 033</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; border: 1px solid #ddd; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #0066cc; }
        .warning { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Verificando e Executando Migration 033 - produto_videos</h1>
        <pre>
<?php
try {
    $db = Database::getConnection();
    $runner = new MigrationRunner();
    
    // Verificar se a tabela existe
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    // Verificar se a migration foi registrada
    $applied = $runner->getAppliedMigrationsList();
    $isRegistered = in_array('033_create_produto_videos_table', $applied);
    
    echo "Status da Migration 033:\n";
    echo str_repeat("=", 60) . "\n\n";
    
    if ($tableExists && $isRegistered) {
        echo "<span class='success'>✓ Tabela 'produto_videos' já existe</span>\n";
        echo "<span class='success'>✓ Migration já está registrada</span>\n\n";
        echo "Nenhuma ação necessária. A migration já foi aplicada.\n";
    } else {
        if (!$tableExists) {
            echo "<span class='warning'>⚠ Tabela 'produto_videos' NÃO existe</span>\n";
        }
        if (!$isRegistered) {
            echo "<span class='warning'>⚠ Migration NÃO está registrada</span>\n";
        }
        echo "\nExecutando migration...\n\n";
        
        // Verificar migrations pendentes
        $pending = $runner->getPendingMigrations();
        
        if (in_array('033_create_produto_videos_table', $pending)) {
            // Executar migration usando o MigrationRunner
            $results = $runner->runPending();
            
            $found = false;
            foreach ($results as $result) {
                if ($result['migration'] === '033_create_produto_videos_table') {
                    $found = true;
                    if ($result['status'] === 'success') {
                        echo "<span class='success'>✓ Migration 033 executada com sucesso!</span>\n";
                    } else {
                        echo "<span class='error'>✗ Erro ao executar migration: {$result['message']}</span>\n";
                    }
                    break;
                }
            }
            
            if (!$found) {
                // Se não foi encontrada na lista de pendentes, tentar executar diretamente
                echo "Executando migration diretamente...\n";
                require __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
                
                // Registrar migration
                $stmt = $db->prepare("INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
                $stmt->execute(['migration' => '033_create_produto_videos_table']);
                echo "<span class='success'>✓ Migration executada e registrada!</span>\n";
            }
        } else {
            // Executar diretamente se não estiver na lista de pendentes
            echo "Executando migration diretamente...\n";
            require __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
            
            // Registrar migration
            $stmt = $db->prepare("INSERT IGNORE INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
            $stmt->execute(['migration' => '033_create_produto_videos_table']);
            echo "<span class='success'>✓ Migration executada e registrada!</span>\n";
        }
    }
    
    // Verificar novamente após execução
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Verificação final:\n\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    if ($tableExists) {
        echo "<span class='success'>✓ Tabela 'produto_videos' existe</span>\n\n";
        
        // Mostrar estrutura
        $stmt = $db->query("DESCRIBE produto_videos");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Estrutura da tabela:\n";
        echo str_repeat("-", 80) . "\n";
        printf("%-20s %-25s %-8s %-8s %-10s\n", "Campo", "Tipo", "Null", "Key", "Default");
        echo str_repeat("-", 80) . "\n";
        foreach ($columns as $col) {
            printf("%-20s %-25s %-8s %-8s %-10s\n", 
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
        echo "Registros na tabela: <span class='info'>{$count}</span>\n";
    } else {
        echo "<span class='error'>✗ ERRO: Tabela 'produto_videos' ainda não existe!</span>\n";
    }
    
    // Verificar registro da migration
    $applied = $runner->getAppliedMigrationsList();
    $isRegistered = in_array('033_create_produto_videos_table', $applied);
    
    if ($isRegistered) {
        echo "<span class='success'>✓ Migration está registrada na tabela 'migrations'</span>\n";
    } else {
        echo "<span class='error'>✗ Migration NÃO está registrada</span>\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "<span class='success'>✓ Verificação concluída!</span>\n";
    echo "\nAgora você pode acessar: <a href='../admin/produtos/1'>/admin/produtos/1</a>\n";
    
} catch (\Exception $e) {
    echo "<span class='error'>ERRO: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nTrace:\n";
    echo htmlspecialchars($e->getTraceAsString()) . "\n";
}
?>
        </pre>
        
        <p style="margin-top: 2rem;">
            <a href="check_and_run_migration_033.php">↻ Recarregar</a> | 
            <a href="../admin/produtos">Voltar para produtos</a>
        </p>
    </div>
</body>
</html>


