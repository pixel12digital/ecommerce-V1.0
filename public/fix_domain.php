<?php
/**
 * Script para corrigir domínio - BYPASS do middleware
 * Acesse: https://pontodogolfeoutlet.com.br/fix_domain.php
 * 
 * ⚠️ IMPORTANTE: Remova este arquivo após usar por segurança!
 */

// Carregar autoloader diretamente
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

// Conectar diretamente ao banco (bypass TenantContext)
$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['name'],
        $config['charset']
    );
    
    $db = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    $domain = 'pontodogolfeoutlet.com.br';
    $tenantId = 1;
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Corrigir Domínio</title></head><body>";
    echo "<h1>🔧 Corrigir Domínio do Tenant</h1>";
    echo "<hr>";
    
    // Verificar se o tenant existe
    $stmt = $db->prepare("SELECT id, name, slug, status FROM tenants WHERE id = :id");
    $stmt->execute(['id' => $tenantId]);
    $tenant = $stmt->fetch();
    
    if (!$tenant) {
        echo "<p style='color: red; font-size: 18px;'>❌ <strong>ERRO:</strong> Tenant ID {$tenantId} não encontrado!</p>";
        echo "<h2>Tenants existentes:</h2><ul>";
        $stmt = $db->query("SELECT id, name, slug, status FROM tenants");
        $tenants = $stmt->fetchAll();
        foreach ($tenants as $t) {
            echo "<li>ID: {$t['id']}, Nome: {$t['name']}, Slug: {$t['slug']}, Status: {$t['status']}</li>";
        }
        echo "</ul>";
        echo "</body></html>";
        exit;
    }
    
    echo "<p style='color: green; font-size: 16px;'>✅ Tenant encontrado: <strong>{$tenant['name']}</strong> (ID: {$tenant['id']}, Status: {$tenant['status']})</p>";
    
    // Verificar se o domínio já existe
    $stmt = $db->prepare("SELECT id, tenant_id, is_primary FROM tenant_domains WHERE domain = :domain");
    $stmt->execute(['domain' => $domain]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        if ($existing['tenant_id'] == $tenantId) {
            echo "<p style='color: green; font-size: 16px;'>✅ Domínio '{$domain}' já está vinculado ao tenant ID {$tenantId}!</p>";
            echo "<p>Status: " . ($existing['is_primary'] ? 'PRIMÁRIO' : 'Secundário') . "</p>";
        } else {
            // Atualizar domínio existente
            $stmt = $db->prepare("
                UPDATE tenant_domains 
                SET tenant_id = :tenant_id, is_primary = 1, updated_at = NOW()
                WHERE domain = :domain
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'domain' => $domain
            ]);
            echo "<p style='color: green; font-size: 16px;'>✅ Domínio atualizado com sucesso! Agora está vinculado ao tenant ID {$tenantId}</p>";
        }
    } else {
        // Verificar se já existe domínio primário para este tenant
        $stmt = $db->prepare("SELECT id FROM tenant_domains WHERE tenant_id = :tenant_id AND is_primary = 1");
        $stmt->execute(['tenant_id' => $tenantId]);
        $hasPrimary = $stmt->fetch();
        
        // Adicionar novo domínio
        $stmt = $db->prepare("
            INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain, ssl_status, created_at, updated_at)
            VALUES (:tenant_id, :domain, :is_primary, 1, 'pending', NOW(), NOW())
        ");
        
        $isPrimary = $hasPrimary ? 0 : 1;
        
        $stmt->execute([
            'tenant_id' => $tenantId,
            'domain' => $domain,
            'is_primary' => $isPrimary
        ]);
        
        echo "<p style='color: green; font-size: 18px;'>✅ <strong>Domínio '{$domain}' adicionado com sucesso!</strong></p>";
        echo "<p>Tenant ID: {$tenantId}</p>";
        echo "<p>É primário: " . ($isPrimary ? 'SIM' : 'NÃO') . "</p>";
        echo "<p>É domínio customizado: SIM</p>";
    }
    
    // Listar todos os domínios do tenant
    echo "<hr><h2>📋 Domínios do Tenant:</h2><ul>";
    $stmt = $db->prepare("
        SELECT domain, is_primary, is_custom_domain, ssl_status 
        FROM tenant_domains 
        WHERE tenant_id = :tenant_id 
        ORDER BY is_primary DESC, created_at ASC
    ");
    $stmt->execute(['tenant_id' => $tenantId]);
    $domains = $stmt->fetchAll();
    
    if (empty($domains)) {
        echo "<li><em>Nenhum domínio cadastrado</em></li>";
    } else {
        foreach ($domains as $d) {
            $primary = $d['is_primary'] ? ' <strong style="color: green;">(PRIMÁRIO)</strong>' : '';
            $custom = $d['is_custom_domain'] ? ' [Custom]' : '';
            echo "<li><strong>{$d['domain']}</strong>{$primary}{$custom}</li>";
        }
    }
    echo "</ul>";
    
    echo "<hr>";
    echo "<p style='color: green; font-size: 18px;'><strong>✅ Pronto! Agora você pode acessar: <a href='/' target='_blank'>https://{$domain}</a></strong></p>";
    echo "<p style='color: red; font-size: 16px;'><strong>⚠️ IMPORTANTE: Remova este arquivo (fix_domain.php) por segurança após verificar que tudo está funcionando!</strong></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro</title></head><body>";
    echo "<h1 style='color: red;'>❌ Erro ao conectar ao banco de dados</h1>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>Se o arquivo .env está configurado corretamente</li>";
    echo "<li>Se as credenciais do banco estão corretas</li>";
    echo "<li>Se o banco de dados existe</li>";
    echo "</ul>";
    echo "</body></html>";
} catch (Exception $e) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro</title></head><body>";
    echo "<h1 style='color: red;'>❌ Erro</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

