<?php
/**
 * Script para executar a migration 033 diretamente
 * Acesse via navegador: http://localhost/ecommerce-v1.0/public/executar_migration_033.php
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executar Migration 033</title>
    <style>
        body {
            font-family: monospace;
            padding: 2rem;
            background: #f5f5f5;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .info {
            color: #0066cc;
        }
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
    
    // Verificar migrations pendentes
    $pending = $runner->getPendingMigrations();
    
    echo "Migrations pendentes encontradas: " . count($pending) . "\n";
    echo str_repeat("=", 60) . "\n\n";
    
    if (empty($pending)) {
        echo "<span class='success'>✓ Nenhuma migration pendente. Todas as migrations já foram aplicadas.</span>\n\n";
    } else {
        echo "Executando migrations pendentes...\n\n";
        
        $results = $runner->runPending();
        
        foreach ($results as $result) {
            if ($result['status'] === 'success') {
                echo "<span class='success'>✓ {$result['migration']}</span>\n";
            } else {
                echo "<span class='error'>✗ {$result['migration']}: {$result['message']}</span>\n";
            }
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        
        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'success'));
        $errorCount = count(array_filter($results, fn($r) => $r['status'] === 'error'));
        
        echo "\nResumo:\n";
        echo "  Sucesso: {$successCount}\n";
        echo "  Erros: {$errorCount}\n\n";
        
        if ($successCount > 0) {
            echo "<span class='success'>✓ Migrations aplicadas com sucesso!</span>\n";
        }
        
        if ($errorCount > 0) {
            echo "<span class='error'>⚠ Atenção: Algumas migrations falharam.</span>\n";
        }
    }
    
    // Verificar se a tabela produto_videos existe
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Verificando tabela produto_videos...\n\n";
    
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
        echo "<span class='error'>✗ Tabela 'produto_videos' NÃO existe</span>\n";
    }
    
    // Listar todas as migrations aplicadas
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Migrations aplicadas no sistema:\n\n";
    
    $applied = $runner->getAppliedMigrationsList();
    if (!empty($applied)) {
        foreach ($applied as $migration) {
            $is033 = ($migration === '033_create_produto_videos_table');
            $class = $is033 ? 'success' : 'info';
            echo "<span class='{$class}'>  • {$migration}</span>\n";
        }
    } else {
        echo "Nenhuma migration aplicada ainda.\n";
    }
    
} catch (\Exception $e) {
    echo "<span class='error'>ERRO: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nTrace:\n";
    echo htmlspecialchars($e->getTraceAsString()) . "\n";
}
?>
        </pre>
        
        <p style="margin-top: 2rem;">
            <a href="executar_migration_033.php">↻ Recarregar</a> | 
            <a href="check_migration_033.php">Verificar status</a> | 
            <a href="../admin/produtos">Voltar para produtos</a>
        </p>
    </div>
</body>
</html>


