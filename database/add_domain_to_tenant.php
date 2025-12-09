<?php
/**
 * Script para adicionar domínio ao tenant existente
 * Execute: php database/add_domain_to_tenant.php
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

$db = Database::getConnection();

// Configurações
$domain = 'pontodogolfeoutlet.com.br';
$tenantId = 1; // ID do tenant (ajuste se necessário)

echo "=== Adicionar Domínio ao Tenant ===\n\n";

// Verificar se o tenant existe
$stmt = $db->prepare("SELECT id, name, slug FROM tenants WHERE id = :id");
$stmt->execute(['id' => $tenantId]);
$tenant = $stmt->fetch();

if (!$tenant) {
    echo "❌ ERRO: Tenant com ID {$tenantId} não encontrado!\n";
    echo "Tenants existentes:\n";
    $stmt = $db->query("SELECT id, name, slug FROM tenants");
    $tenants = $stmt->fetchAll();
    foreach ($tenants as $t) {
        echo "  - ID: {$t['id']}, Nome: {$t['name']}, Slug: {$t['slug']}\n";
    }
    exit(1);
}

echo "✅ Tenant encontrado: {$tenant['name']} (ID: {$tenant['id']}, Slug: {$tenant['slug']})\n\n";

// Verificar se o domínio já existe
$stmt = $db->prepare("SELECT id, tenant_id FROM tenant_domains WHERE domain = :domain");
$stmt->execute(['domain' => $domain]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['tenant_id'] == $tenantId) {
        echo "✅ Domínio '{$domain}' já está vinculado ao tenant ID {$tenantId}\n";
        exit(0);
    } else {
        echo "⚠️  Domínio '{$domain}' já existe, mas está vinculado ao tenant ID {$existing['tenant_id']}\n";
        echo "Deseja atualizar? (s/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 's') {
            echo "Operação cancelada.\n";
            exit(0);
        }
        
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
        
        echo "✅ Domínio atualizado com sucesso!\n";
        exit(0);
    }
}

// Verificar se já existe um domínio primário para este tenant
$stmt = $db->prepare("SELECT id FROM tenant_domains WHERE tenant_id = :tenant_id AND is_primary = 1");
$stmt->execute(['tenant_id' => $tenantId]);
$hasPrimary = $stmt->fetch();

// Adicionar novo domínio
$stmt = $db->prepare("
    INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain, ssl_status, created_at, updated_at)
    VALUES (:tenant_id, :domain, :is_primary, 1, 'pending', NOW(), NOW())
");

$isPrimary = $hasPrimary ? 0 : 1; // Se não tem primário, este será o primário

$stmt->execute([
    'tenant_id' => $tenantId,
    'domain' => $domain,
    'is_primary' => $isPrimary
]);

echo "✅ Domínio '{$domain}' adicionado com sucesso!\n";
echo "   Tenant ID: {$tenantId}\n";
echo "   É primário: " . ($isPrimary ? 'SIM' : 'NÃO') . "\n";
echo "   É domínio customizado: SIM\n\n";

// Listar todos os domínios do tenant
echo "Domínios do tenant:\n";
$stmt = $db->prepare("
    SELECT domain, is_primary, is_custom_domain, ssl_status 
    FROM tenant_domains 
    WHERE tenant_id = :tenant_id 
    ORDER BY is_primary DESC, created_at ASC
");
$stmt->execute(['tenant_id' => $tenantId]);
$domains = $stmt->fetchAll();

foreach ($domains as $d) {
    $primary = $d['is_primary'] ? ' (PRIMÁRIO)' : '';
    echo "  - {$d['domain']}{$primary}\n";
}

echo "\n✅ Pronto! Agora você pode acessar: https://{$domain}\n";

