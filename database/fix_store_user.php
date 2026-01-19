<?php
/**
 * Script para verificar e corrigir usuário store admin
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

try {
    $db = Database::getConnection();
    
    echo "=== Verificação e Correção de Usuário Store Admin ===\n\n";
    
    // Verificar tenant
    $config = require __DIR__ . '/../config/app.php';
    $tenantId = $config['default_tenant_id'] ?? 1;
    
    echo "Tenant ID: {$tenantId}\n";
    
    // Verificar se tenant existe
    $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id");
    $stmt->execute(['id' => $tenantId]);
    $tenant = $stmt->fetch();
    
    if (!$tenant) {
        echo "❌ Tenant ID {$tenantId} não encontrado!\n";
        echo "Execute o seed primeiro: php database/run_seed.php\n";
        exit(1);
    }
    
    echo "✅ Tenant encontrado: {$tenant['name']}\n\n";
    
    // Verificar usuário
    $email = 'contato@pixel12digital.com.br';
    $password = 'admin123';
    
    $stmt = $db->prepare("SELECT * FROM store_users WHERE email = :email AND tenant_id = :tenant_id");
    $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "⚠️ Usuário não encontrado. Criando...\n";
        
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO store_users (tenant_id, name, email, password_hash, role) 
            VALUES (:tenant_id, 'Admin Loja', :email, :password, 'owner')
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'email' => $email,
            'password' => $passwordHash
        ]);
        
        echo "✅ Usuário criado com sucesso!\n";
    } else {
        echo "✅ Usuário encontrado: {$user['name']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Role: {$user['role']}\n";
        
        // Verificar senha
        if (empty($user['password_hash'])) {
            echo "⚠️ Senha não está definida. Atualizando...\n";
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE store_users SET password_hash = :password WHERE id = :id");
            $stmt->execute(['password' => $passwordHash, 'id' => $user['id']]);
            echo "✅ Senha atualizada!\n";
        } else {
            $passwordValid = password_verify($password, $user['password_hash']);
            if ($passwordValid) {
                echo "✅ Senha está correta\n";
            } else {
                echo "⚠️ Senha está incorreta. Atualizando...\n";
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE store_users SET password_hash = :password WHERE id = :id");
                $stmt->execute(['password' => $passwordHash, 'id' => $user['id']]);
                echo "✅ Senha atualizada!\n";
            }
        }
    }
    
    echo "\n=== Resumo ===\n";
    echo "Email: {$email}\n";
    echo "Senha: {$password}\n";
    echo "Tenant ID: {$tenantId}\n";
    echo "\n✅ Tudo pronto! Tente fazer login novamente.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
