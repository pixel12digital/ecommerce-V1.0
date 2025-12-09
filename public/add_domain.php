<?php
/**
 * Script rápido para adicionar domínio ao tenant
 * Acesse: https://pontodogolfeoutlet.com.br/add_domain.php
 * 
 * ⚠️ IMPORTANTE: Remova este arquivo após usar por segurança!
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;

$db = Database::getConnection();

$domain = 'pontodogolfeoutlet.com.br';
$tenantId = 1;

echo "<h1>Adicionar Domínio ao Tenant</h1>";

try {
    // Verificar se o tenant existe
    $stmt = $db->prepare("SELECT id, name FROM tenants WHERE id = :id");
    $stmt->execute(['id' => $tenantId]);
    $tenant = $stmt->fetch();
    
    if (!$tenant) {
        echo "<p style='color: red;'>❌ Tenant ID {$tenantId} não encontrado!</p>";
        exit;
    }
    
    echo "<p>✅ Tenant encontrado: <strong>{$tenant['name']}</strong></p>";
    
    // Verificar se o domínio já existe
    $stmt = $db->prepare("SELECT id, tenant_id FROM tenant_domains WHERE domain = :domain");
    $stmt->execute(['domain' => $domain]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['tenant_id'] == $tenantId) {
            echo "<p style='color: green;'>✅ Domínio '{$domain}' já está vinculado ao tenant!</p>";
        } else {
            // Atualizar
            $stmt = $db->prepare("
                UPDATE tenant_domains 
                SET tenant_id = :tenant_id, is_primary = 1, updated_at = NOW()
                WHERE domain = :domain
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'domain' => $domain
            ]);
            echo "<p style='color: green;'>✅ Domínio atualizado com sucesso!</p>";
        }
    } else {
        // Verificar se já existe domínio primário
        $stmt = $db->prepare("SELECT id FROM tenant_domains WHERE tenant_id = :tenant_id AND is_primary = 1");
        $stmt->execute(['tenant_id' => $tenantId]);
        $hasPrimary = $stmt->fetch();
        
        // Adicionar novo domínio
        $stmt = $db->prepare("
            INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain, ssl_status, created_at, updated_at)
            VALUES (:tenant_id, :domain, :is_primary, 1, 'pending', NOW(), NOW())
        ");
        
        $stmt->execute([
            'tenant_id' => $tenantId,
            'domain' => $domain,
            'is_primary' => $hasPrimary ? 0 : 1
        ]);
        
        echo "<p style='color: green;'>✅ Domínio '{$domain}' adicionado com sucesso!</p>";
    }
    
    // Listar domínios do tenant
    echo "<h2>Domínios do Tenant:</h2>";
    echo "<ul>";
    $stmt = $db->prepare("
        SELECT domain, is_primary, is_custom_domain 
        FROM tenant_domains 
        WHERE tenant_id = :tenant_id 
        ORDER BY is_primary DESC
    ");
    $stmt->execute(['tenant_id' => $tenantId]);
    $domains = $stmt->fetchAll();
    
    foreach ($domains as $d) {
        $primary = $d['is_primary'] ? ' <strong>(PRIMÁRIO)</strong>' : '';
        echo "<li>{$d['domain']}{$primary}</li>";
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>✅ Pronto! Agora você pode acessar: <a href='/'>https://{$domain}</a></strong></p>";
    echo "<p style='color: red;'><strong>⚠️ IMPORTANTE: Remova este arquivo (add_domain.php) por segurança!</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}

