<?php

/**
 * Script para executar migrations pendentes no banco remoto
 * Usa as configurações do arquivo .env
 * 
 * Acesse via navegador: http://localhost/ecommerce-v1.0/public/run_pending_migrations.php?run=1
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
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!empty($name)) {
                $_ENV[$name] = $value;
            }
        }
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
    <title>Executar Migrations Pendentes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.3em;
        }
        .status-box {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 10px 0;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Executar Migrations Pendentes</h1>
        
        <?php
        try {
            // Verificar conexão
            $db = Database::getConnection();
            $runner = new MigrationRunner();
            
            echo '<div class="status-box success">';
            echo '✓ Conexão com banco de dados estabelecida com sucesso';
            echo '<br><small>Host: ' . htmlspecialchars($_ENV['DB_HOST'] ?? 'N/A') . '</small>';
            echo '</div>';
            
            // Obter migrations pendentes
            $pending = $runner->getPendingMigrations();
            
            // Executar migrations se solicitado
            if (isset($_GET['run']) && $_GET['run'] === '1') {
                echo '<div class="section">';
                echo '<h2>🚀 Executando Migrations</h2>';
                
                if (empty($pending)) {
                    echo '<div class="status-box info">';
                    echo 'Nenhuma migration pendente para executar.';
                    echo '</div>';
                } else {
                    echo '<p>Executando <strong>' . count($pending) . '</strong> migration(s) pendente(s)...</p>';
                    
                    $results = $runner->runPending();
                    
                    $successCount = 0;
                    $errorCount = 0;
                    
                    echo '<table>';
                    echo '<tr><th>Migration</th><th>Status</th><th>Mensagem</th></tr>';
                    
                    foreach ($results as $result) {
                        $statusClass = $result['status'] === 'success' ? 'success' : 'danger';
                        $statusIcon = $result['status'] === 'success' ? '✓' : '✗';
                        $statusBadge = $result['status'] === 'success' ? 'badge-success' : 'badge-danger';
                        
                        if ($result['status'] === 'success') {
                            $successCount++;
                        } else {
                            $errorCount++;
                        }
                        
                        echo '<tr>';
                        echo '<td><span class="code">' . htmlspecialchars($result['migration']) . '</span></td>';
                        echo '<td><span class="badge ' . $statusBadge . '">' . $statusIcon . ' ' . ucfirst($result['status']) . '</span></td>';
                        echo '<td>' . (isset($result['message']) ? htmlspecialchars($result['message']) : 'OK') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    echo '<div class="status-box ' . ($errorCount > 0 ? 'warning' : 'success') . '" style="margin-top: 20px;">';
                    echo '<strong>Resumo:</strong><br>';
                    echo '✓ Sucesso: ' . $successCount . '<br>';
                    if ($errorCount > 0) {
                        echo '✗ Erros: ' . $errorCount;
                    }
                    echo '</div>';
                    
                    // Recarregar página após 3 segundos para atualizar status
                    if ($errorCount == 0) {
                        echo '<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 3000);</script>';
                    }
                }
                
                echo '</div>';
            }
            
            // Mostrar migrations pendentes
            echo '<div class="section">';
            echo '<h2>⏳ Migrations Pendentes (' . count($pending) . ')</h2>';
            
            if (empty($pending)) {
                echo '<div class="status-box success">';
                echo '✓ Nenhuma migration pendente. Todas as migrations foram aplicadas!';
                echo '</div>';
            } else {
                echo '<div class="status-box warning">';
                echo '⚠ Existem <strong>' . count($pending) . '</strong> migration(s) pendente(s).';
                echo '</div>';
                
                echo '<table>';
                echo '<tr><th>Migration</th><th>Status</th></tr>';
                
                foreach ($pending as $migration) {
                    echo '<tr>';
                    echo '<td><span class="code">' . htmlspecialchars($migration) . '</span></td>';
                    echo '<td><span class="badge badge-warning">⏳ Pendente</span></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                echo '<div style="margin-top: 20px;">';
                echo '<a href="?run=1" class="btn btn-success">▶ Executar Migrations Pendentes</a>';
                echo '<a href="?" class="btn">🔄 Atualizar Página</a>';
                echo '</div>';
            }
            echo '</div>';
            
            // Mostrar migrations aplicadas
            $applied = $runner->getAppliedMigrationsList();
            echo '<div class="section">';
            echo '<h2>📋 Migrations Aplicadas (' . count($applied) . ')</h2>';
            
            if (empty($applied)) {
                echo '<div class="status-box info">Nenhuma migration aplicada ainda.</div>';
            } else {
                echo '<table>';
                echo '<tr><th>Migration</th><th>Status</th></tr>';
                
                foreach ($applied as $migration) {
                    echo '<tr>';
                    echo '<td><span class="code">' . htmlspecialchars($migration) . '</span></td>';
                    echo '<td><span class="badge badge-success">✓ Aplicada</span></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
        } catch (\Exception $e) {
            echo '<div class="status-box error">';
            echo '✗ Erro: ' . htmlspecialchars($e->getMessage());
            echo '<br><small>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</small>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h2>ℹ️ Informações</h2>
            <p><strong>Arquivo:</strong> <span class="code">public/run_pending_migrations.php</span></p>
            <p><strong>Data/Hora:</strong> <span class="timestamp"><?php echo date('d/m/Y H:i:s'); ?></span></p>
            <p style="margin-top: 15px;">
                <a href="?" class="btn">🔄 Atualizar</a>
                <a href="../" class="btn">🏠 Voltar</a>
            </p>
        </div>
    </div>
</body>
</html>
