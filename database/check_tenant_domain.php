<?php
/**
 * Script para verificar configuração de domínio do tenant
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
    
    echo "=== Verificação de Domínio do Tenant ===\n\n";
    
    // Verificar tenant
    $config = require __DIR__ . '/../config/app.php';
    $tenantId = $config['default_tenant_id'] ?? 1;
    $mode = $config['mode'] ?? 'multi';
    
    echo "Modo: {$mode}\n";
    echo "Tenant ID: {$tenantId}\n\n";
    
    // Verificar domínios
    $stmt = $db->prepare("SELECT * FROM tenant_domains WHERE tenant_id = :tenant_id");
    $stmt->execute(['tenant_id' => $tenantId]);
    $domains = $stmt->fetchAll();
    
    if (empty($domains)) {
        echo "⚠️ Nenhum domínio configurado para tenant ID {$tenantId}\n";
        echo "Criando domínio 'localhost'...\n";
        
        $stmt = $db->prepare("
            INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain) 
            VALUES (:tenant_id, 'localhost', 1, 0)
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        
        echo "✅ Domínio 'localhost' criado!\n";
    } else {
        echo "✅ Domínios encontrados:\n";
        foreach ($domains as $domain) {
            echo "   - {$domain['domain']} (primary: " . ($domain['is_primary'] ? 'sim' : 'não') . ")\n";
        }
        
        // Verificar se localhost existe
        $hasLocalhost = false;
        foreach ($domains as $domain) {
            if ($domain['domain'] === 'localhost') {
                $hasLocalhost = true;
                break;
            }
        }
        
        if (!$hasLocalhost) {
            echo "\n⚠️ Domínio 'localhost' não encontrado. Criando...\n";
            $stmt = $db->prepare("
                INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain) 
                VALUES (:tenant_id, 'localhost', 0, 0)
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            echo "✅ Domínio 'localhost' criado!\n";
        }
    }
    
    echo "\n✅ Configuração de domínio verificada!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
