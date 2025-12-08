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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Migrations</title>
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
            max-width: 1200px;
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
        .badge-info {
            background: #17a2b8;
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
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
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
        .timestamp {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Migrations</h1>
        
        <?php
        try {
            $db = Database::getConnection();
            $runner = new MigrationRunner();
            
            // Verificar conexão
            echo '<div class="status-box success">';
            echo '✓ Conexão com banco de dados estabelecida com sucesso';
            echo '</div>';
            
            // Verificar se tabela migrations existe
            $stmt = $db->query("SHOW TABLES LIKE 'migrations'");
            $migrationsTableExists = $stmt->fetch();
            
            if (!$migrationsTableExists) {
                echo '<div class="status-box warning">';
                echo '⚠ Tabela <span class="code">migrations</span> não existe. Será criada automaticamente.';
                echo '</div>';
            }
            
            // Verificar se tabela produto_avaliacoes existe
            $stmt = $db->query("SHOW TABLES LIKE 'produto_avaliacoes'");
            $produtoAvaliacoesExists = $stmt->fetch();
            
            if ($produtoAvaliacoesExists) {
                echo '<div class="status-box success">';
                echo '✓ Tabela <span class="code">produto_avaliacoes</span> já existe no banco de dados';
                echo '</div>';
            } else {
                echo '<div class="status-box warning">';
                echo '⚠ Tabela <span class="code">produto_avaliacoes</span> não existe. Execute as migrations pendentes.';
                echo '</div>';
            }
            
            // Obter migrations aplicadas
            $applied = $runner->getAppliedMigrationsList();
            
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
                    $results = $runner->runPending();
                    
                    echo '<table>';
                    echo '<tr><th>Migration</th><th>Status</th><th>Mensagem</th></tr>';
                    
                    foreach ($results as $result) {
                        $statusClass = $result['status'] === 'success' ? 'success' : 'danger';
                        $statusIcon = $result['status'] === 'success' ? '✓' : '✗';
                        $statusBadge = $result['status'] === 'success' ? 'badge-success' : 'badge-danger';
                        
                        echo '<tr>';
                        echo '<td><span class="code">' . htmlspecialchars($result['migration']) . '</span></td>';
                        echo '<td><span class="badge ' . $statusBadge . '">' . $statusIcon . ' ' . ucfirst($result['status']) . '</span></td>';
                        echo '<td>' . (isset($result['message']) ? htmlspecialchars($result['message']) : 'OK') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</table>';
                    
                    // Verificar novamente se tabela foi criada
                    $stmt = $db->query("SHOW TABLES LIKE 'produto_avaliacoes'");
                    $produtoAvaliacoesExists = $stmt->fetch();
                    
                    if ($produtoAvaliacoesExists) {
                        echo '<div class="status-box success">';
                        echo '✓ Tabela <span class="code">produto_avaliacoes</span> foi criada com sucesso!';
                        echo '</div>';
                    }
                    
                    // Recarregar página após 2 segundos para atualizar status
                    echo '<script>setTimeout(function(){ window.location.href = window.location.pathname; }, 2000);</script>';
                }
                
                echo '</div>';
            }
            
            // Mostrar migrations aplicadas
            echo '<div class="section">';
            echo '<h2>📋 Migrations Aplicadas (' . count($applied) . ')</h2>';
            
            if (empty($applied)) {
                echo '<div class="status-box info">Nenhuma migration aplicada ainda.</div>';
            } else {
                echo '<table>';
                echo '<tr><th>Migration</th><th>Status</th></tr>';
                
                foreach ($applied as $migration) {
                    $is036 = strpos($migration, '036_create_produto_avaliacoes_table') !== false;
                    $rowClass = $is036 ? 'style="background: #d4edda;"' : '';
                    
                    echo '<tr ' . $rowClass . '>';
                    echo '<td><span class="code">' . htmlspecialchars($migration) . '</span></td>';
                    echo '<td><span class="badge badge-success">✓ Aplicada</span></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            echo '</div>';
            
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
            
            // Informações da tabela produto_avaliacoes
            if ($produtoAvaliacoesExists) {
                echo '<div class="section">';
                echo '<h2>📊 Informações da Tabela produto_avaliacoes</h2>';
                
                try {
                    // Contar registros
                    $stmt = $db->query("SELECT COUNT(*) as total FROM produto_avaliacoes");
                    $total = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];
                    
                    // Contar por status
                    $stmt = $db->query("SELECT status, COUNT(*) as total FROM produto_avaliacoes GROUP BY status");
                    $statusCounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    echo '<p><strong>Total de avaliações:</strong> ' . $total . '</p>';
                    
                    if (!empty($statusCounts)) {
                        echo '<table>';
                        echo '<tr><th>Status</th><th>Quantidade</th></tr>';
                        foreach ($statusCounts as $row) {
                            echo '<tr>';
                            echo '<td><span class="badge badge-info">' . htmlspecialchars($row['status']) . '</span></td>';
                            echo '<td>' . $row['total'] . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    
                    // Mostrar estrutura da tabela
                    $stmt = $db->query("DESCRIBE produto_avaliacoes");
                    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    
                    echo '<h3 style="margin-top: 20px;">Estrutura da Tabela</h3>';
                    echo '<table>';
                    echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th></tr>';
                    foreach ($columns as $col) {
                        echo '<tr>';
                        echo '<td><span class="code">' . htmlspecialchars($col['Field']) . '</span></td>';
                        echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                        echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                    
                } catch (\Exception $e) {
                    echo '<div class="status-box error">Erro ao obter informações: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
                echo '</div>';
            }
            
        } catch (\Exception $e) {
            echo '<div class="status-box error">';
            echo '✗ Erro: ' . htmlspecialchars($e->getMessage());
            echo '<br><small>' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</small>';
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h2>ℹ️ Informações</h2>
            <p><strong>Arquivo:</strong> <span class="code">public/migrations.php</span></p>
            <p><strong>Data/Hora:</strong> <span class="timestamp"><?php echo date('d/m/Y H:i:s'); ?></span></p>
            <p style="margin-top: 15px;">
                <a href="?" class="btn">🔄 Atualizar</a>
                <a href="../" class="btn">🏠 Voltar</a>
            </p>
        </div>
    </div>
</body>
</html>


