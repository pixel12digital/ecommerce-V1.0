<?php

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
use App\Services\MigrationRunner;

$db = Database::getConnection();

echo "<h1>Verificação de Migrations da Fase 4</h1>";

// Verificar migrations aplicadas
$runner = new MigrationRunner();
$applied = $runner->getAppliedMigrationsList();
$pending = $runner->getPendingMigrations();

echo "<h2>Migrations Aplicadas:</h2>";
echo "<ul>";
foreach ($applied as $migration) {
    $isFase4 = strpos($migration, '031_') === 0 || strpos($migration, '032_') === 0;
    $style = $isFase4 ? 'color: green; font-weight: bold;' : '';
    echo "<li style='{$style}'>✓ {$migration}</li>";
}
echo "</ul>";

echo "<h2>Migrations Pendentes:</h2>";
if (empty($pending)) {
    echo "<p style='color: green;'>✓ Nenhuma migration pendente</p>";
} else {
    echo "<ul>";
    foreach ($pending as $migration) {
        echo "<li style='color: red;'>✗ {$migration}</li>";
    }
    echo "</ul>";
}

// Verificar tabelas da Fase 4
echo "<h2>Tabelas da Fase 4:</h2>";
$tables = [
    'pedidos' => '031_create_pedidos_table',
    'pedido_itens' => '032_create_pedido_itens_table',
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Tabela</th><th>Migration</th><th>Status</th><th>Registros</th></tr>";

foreach ($tables as $table => $migration) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM {$table}");
        $count = $stmt->fetchColumn();
        $exists = true;
    } catch (\Exception $e) {
        $count = 0;
        $exists = false;
        $error = $e->getMessage();
    }
    
    $status = $exists ? '<span style="color: green;">✓ Existe</span>' : '<span style="color: red;">✗ Não existe</span>';
    $migrationStatus = in_array($migration, $applied) ? '<span style="color: green;">✓ Aplicada</span>' : '<span style="color: red;">✗ Pendente</span>';
    
    echo "<tr>";
    echo "<td><strong>{$table}</strong></td>";
    echo "<td>{$migration}</td>";
    echo "<td>{$status} / {$migrationStatus}</td>";
    echo "<td>{$count}</td>";
    echo "</tr>";
    
    if (!$exists && isset($error)) {
        echo "<tr><td colspan='4' style='color: red; font-size: 0.9rem;'>Erro: " . htmlspecialchars($error) . "</td></tr>";
    }
}

echo "</table>";

// Botão para executar migrations
if (!empty($pending)) {
    echo "<h2>Ação:</h2>";
    echo "<p><a href='?run_migrations=1' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Executar Migrations Pendentes</a></p>";
}

// Executar migrations se solicitado
if (isset($_GET['run_migrations'])) {
    echo "<h2>Executando Migrations...</h2>";
    $results = $runner->runPending();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'><th>Migration</th><th>Status</th></tr>";
    foreach ($results as $result) {
        $status = $result['status'] === 'success' 
            ? '<span style="color: green;">✓ Sucesso</span>' 
            : '<span style="color: red;">✗ Erro: ' . htmlspecialchars($result['message']) . '</span>';
        echo "<tr><td>{$result['migration']}</td><td>{$status}</td></tr>";
    }
    echo "</table>";
    
    echo "<p><a href='?' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 1rem;'>Atualizar Página</a></p>";
}


