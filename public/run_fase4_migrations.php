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

use App\Services\MigrationRunner;

$runner = new MigrationRunner();
$pending = $runner->getPendingMigrations();

echo "<h1>Executar Migrations da Fase 4</h1>";

if (empty($pending)) {
    echo "<p style='color: green; font-size: 1.2rem;'>✓ Todas as migrations já foram aplicadas!</p>";
} else {
    echo "<p>Migrations pendentes: " . count($pending) . "</p>";
    echo "<ul>";
    foreach ($pending as $migration) {
        echo "<li>{$migration}</li>";
    }
    echo "</ul>";
    
    if (isset($_GET['run'])) {
        echo "<h2>Executando...</h2>";
        $results = $runner->runPending();
        
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Migration</th><th>Status</th></tr>";
        foreach ($results as $result) {
            $status = $result['status'] === 'success' 
                ? '<span style="color: green;">✓ Sucesso</span>' 
                : '<span style="color: red;">✗ Erro: ' . htmlspecialchars($result['message']) . '</span>';
            echo "<tr><td>{$result['migration']}</td><td>{$status}</td></tr>";
        }
        echo "</table>";
        
        echo "<p><a href='?' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 1rem;'>Atualizar</a></p>";
    } else {
        echo "<p><a href='?run=1' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>Executar Migrations Pendentes</a></p>";
    }
}


