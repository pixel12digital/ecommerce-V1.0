<?php
/**
 * Script de diagnóstico para problemas de login
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
use App\Tenant\TenantContext;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #666; margin-top: 30px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .info { color: #2196F3; }
        pre { background: #f5f5f5; padding: 15px; border-left: 4px solid #4CAF50; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #4CAF50; color: white; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico de Login - Store Admin</h1>

        <?php
        try {
            // 1. Verificar configuração
            echo '<div class="test-section">';
            echo '<h2>1. Configuração do Sistema</h2>';
            $config = require __DIR__ . '/../config/app.php';
            echo '<p><strong>Modo:</strong> <span class="info">' . htmlspecialchars($config['mode']) . '</span></p>';
            echo '<p><strong>Tenant ID Padrão:</strong> <span class="info">' . $config['default_tenant_id'] . '</span></p>';
            echo '<p><strong>Host:</strong> <span class="info">' . ($_SERVER['HTTP_HOST'] ?? 'N/A') . '</span></p>';
            echo '</div>';

            // 2. Verificar conexão com banco
            echo '<div class="test-section">';
            echo '<h2>2. Conexão com Banco de Dados</h2>';
            try {
                $db = Database::getConnection();
                echo '<p class="success">✅ Conexão com banco de dados estabelecida</p>';
            } catch (Exception $e) {
                echo '<p class="error">❌ Erro ao conectar: ' . htmlspecialchars($e->getMessage()) . '</p>';
                exit;
            }
            echo '</div>';

            // 3. Resolver tenant
            echo '<div class="test-section">';
            echo '<h2>3. Resolução de Tenant</h2>';
            try {
                if ($config['mode'] === 'single') {
                    TenantContext::setFixedTenant($config['default_tenant_id']);
                    echo '<p class="info">Modo single-tenant: usando tenant ID ' . $config['default_tenant_id'] . '</p>';
                } else {
                    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                    TenantContext::resolveFromHost($host);
                    echo '<p class="info">Modo multi-tenant: resolvendo por host "' . htmlspecialchars($host) . '"</p>';
                }
                
                $tenantId = TenantContext::id();
                $tenant = TenantContext::tenant();
                
                if ($tenantId) {
                    echo '<p class="success">✅ Tenant resolvido: ID ' . $tenantId . '</p>';
                    if ($tenant) {
                        echo '<p><strong>Nome:</strong> ' . htmlspecialchars($tenant->name) . '</p>';
                        echo '<p><strong>Slug:</strong> ' . htmlspecialchars($tenant->slug) . '</p>';
                    }
                } else {
                    echo '<p class="error">❌ Não foi possível resolver o tenant</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">❌ Erro ao resolver tenant: ' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            }
            echo '</div>';

            // 4. Verificar usuários no banco
            echo '<div class="test-section">';
            echo '<h2>4. Usuários Store no Banco de Dados</h2>';
            
            $tenantId = TenantContext::id();
            if (!$tenantId) {
                echo '<p class="error">❌ Não é possível verificar usuários sem tenant resolvido</p>';
            } else {
                $stmt = $db->prepare("SELECT id, name, email, role, tenant_id FROM store_users WHERE tenant_id = :tenant_id");
                $stmt->execute(['tenant_id' => $tenantId]);
                $users = $stmt->fetchAll();
                
                if (empty($users)) {
                    echo '<p class="warning">⚠️ Nenhum usuário encontrado para o tenant ID ' . $tenantId . '</p>';
                    echo '<p class="info">💡 Execute o seed: <code>php database/run_seed.php</code></p>';
                } else {
                    echo '<p class="success">✅ ' . count($users) . ' usuário(s) encontrado(s)</p>';
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Nome</th><th>Email</th><th>Role</th><th>Tenant ID</th></tr>';
                    foreach ($users as $user) {
                        echo '<tr>';
                        echo '<td>' . $user['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($user['name']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
                        echo '<td>' . htmlspecialchars($user['role']) . '</td>';
                        echo '<td>' . $user['tenant_id'] . '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }
            echo '</div>';

            // 5. Verificar credenciais específicas
            echo '<div class="test-section">';
            echo '<h2>5. Verificação de Credenciais</h2>';
            
            $testEmail = 'contato@pixel12digital.com.br';
            $testPassword = 'admin123';
            
            if ($tenantId) {
                $stmt = $db->prepare("SELECT * FROM store_users WHERE email = :email AND tenant_id = :tenant_id LIMIT 1");
                $stmt->execute(['email' => $testEmail, 'tenant_id' => $tenantId]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    echo '<p class="error">❌ Usuário "' . htmlspecialchars($testEmail) . '" não encontrado para tenant ID ' . $tenantId . '</p>';
                } else {
                    echo '<p class="success">✅ Usuário encontrado: ' . htmlspecialchars($user['name']) . '</p>';
                    
                    // Verificar senha
                    if (empty($user['password_hash'])) {
                        echo '<p class="error">❌ Senha não está definida no banco de dados</p>';
                    } else {
                        $passwordValid = password_verify($testPassword, $user['password_hash']);
                        if ($passwordValid) {
                            echo '<p class="success">✅ Senha "' . htmlspecialchars($testPassword) . '" está CORRETA</p>';
                        } else {
                            echo '<p class="error">❌ Senha "' . htmlspecialchars($testPassword) . '" está INCORRETA</p>';
                            echo '<p class="info">Hash no banco: ' . substr($user['password_hash'], 0, 20) . '...</p>';
                            
                            // Tentar recriar o hash
                            echo '<h3>Recriar Hash da Senha</h3>';
                            $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                            echo '<p>Novo hash gerado: <code>' . htmlspecialchars($newHash) . '</code></p>';
                            echo '<p class="warning">⚠️ Para atualizar, execute no banco:</p>';
                            echo '<pre>UPDATE store_users SET password_hash = \'' . $newHash . '\' WHERE id = ' . $user['id'] . ';</pre>';
                        }
                    }
                }
            } else {
                echo '<p class="error">❌ Não é possível verificar credenciais sem tenant resolvido</p>';
            }
            echo '</div>';

            // 6. Verificar sessão
            echo '<div class="test-section">';
            echo '<h2>6. Status da Sessão</h2>';
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            echo '<p><strong>Status da Sessão:</strong> ';
            if (session_status() === PHP_SESSION_ACTIVE) {
                echo '<span class="success">✅ Ativa</span></p>';
                echo '<p><strong>ID da Sessão:</strong> ' . session_id() . '</p>';
                echo '<p><strong>Nome da Sessão:</strong> ' . session_name() . '</p>';
                
                if (isset($_SESSION['store_user_id'])) {
                    echo '<p class="success">✅ Usuário já está logado (ID: ' . $_SESSION['store_user_id'] . ')</p>';
                } else {
                    echo '<p class="info">ℹ️ Nenhum usuário logado no momento</p>';
                }
            } else {
                echo '<span class="error">❌ Não iniciada</span></p>';
            }
            echo '</div>';

            // 7. Recomendações
            echo '<div class="test-section">';
            echo '<h2>7. Recomendações</h2>';
            echo '<ul>';
            
            if (empty($users)) {
                echo '<li class="warning">⚠️ Execute o seed inicial: <code>php database/run_seed.php</code></li>';
            }
            
            if ($tenantId && !empty($users)) {
                $stmt = $db->prepare("SELECT * FROM store_users WHERE email = :email AND tenant_id = :tenant_id LIMIT 1");
                $stmt->execute(['email' => $testEmail, 'tenant_id' => $tenantId]);
                $user = $stmt->fetch();
                
                if ($user && !password_verify($testPassword, $user['password_hash'])) {
                    echo '<li class="error">❌ A senha no banco não corresponde. Execute o SQL acima para corrigir.</li>';
                }
            }
            
            echo '<li class="info">ℹ️ Verifique se o arquivo <code>.env</code> está configurado corretamente</li>';
            echo '<li class="info">ℹ️ Verifique os logs do Apache/PHP para erros adicionais</li>';
            echo '</ul>';
            echo '</div>';

        } catch (Exception $e) {
            echo '<div class="test-section">';
            echo '<h2 class="error">Erro Fatal</h2>';
            echo '<p class="error">' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
        }
        ?>

        <div class="test-section">
            <h2>🔗 Links Úteis</h2>
            <ul>
                <li><a href="/ecommerce-v1.0/public/admin/login">Tentar Login Novamente</a></li>
                <li><a href="/ecommerce-v1.0/public/test.php">Script de Teste Geral</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
