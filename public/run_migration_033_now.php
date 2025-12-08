<?php
/**
 * Script para executar a migration 033 AGORA
 * Acesse: http://localhost/ecommerce-v1.0/public/run_migration_033_now.php
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
        pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; overflow-x: auto; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
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
    
    // Verificar se já existe
    $stmt = $db->query("SHOW TABLES LIKE 'produto_videos'");
    $tableExists = $stmt->fetch() !== false;
    
    if ($tableExists) {
        echo "<span class='success'>✓ Tabela 'produto_videos' já existe</span>\n\n";
    } else {
        echo "Criando tabela produto_videos...\n";
        
        // Executar migration diretamente
        require __DIR__ . '/../database/migrations/033_create_produto_videos_table.php';
        
        echo "<span class='success'>✓ Tabela criada!</span>\n\n";
    }
    
    // Registrar migration
    $stmt = $db->prepare("SELECT COUNT(*) FROM migrations WHERE migration = :migration");
    $stmt->execute(['migration' => '033_create_produto_videos_table']);
    $isRegistered = $stmt->fetchColumn() > 0;
    
    if (!$isRegistered) {
        echo "Registrando migration...\n";
        $stmt = $db->prepare("INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
        $stmt->execute(['migration' => '033_create_produto_videos_table']);
        echo "<span class='success'>✓ Migration registrada!</span>\n\n";
    } else {
        echo "Migration já estava registrada.\n\n";
    }
    
    // Verificar estrutura
    $stmt = $db->query("DESCRIBE produto_videos");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Estrutura da tabela:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-25s %-8s %-8s\n", "Campo", "Tipo", "Null", "Key");
    echo str_repeat("-", 80) . "\n";
    foreach ($columns as $col) {
        printf("%-20s %-25s %-8s %-8s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    echo str_repeat("-", 80) . "\n";
    
    echo "\n<span class='success'>✓ Migration 033 aplicada com sucesso!</span>\n";
    echo "\nAgora você pode acessar: <a href='../admin/produtos/1'>/admin/produtos/1</a>\n";
    
} catch (\Exception $e) {
    echo "<span class='error'>ERRO: " . htmlspecialchars($e->getMessage()) . "</span>\n";
    echo "\nTrace:\n";
    echo htmlspecialchars($e->getTraceAsString()) . "\n";
}
?>
        </pre>
    </div>
</body>
</html>


