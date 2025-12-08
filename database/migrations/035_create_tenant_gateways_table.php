<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se a tabela já existe
$stmt = $db->query("SHOW TABLES LIKE 'tenant_gateways'");
$tableExists = $stmt->fetch();

if (!$tableExists) {
    $db->exec("
        CREATE TABLE tenant_gateways (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            tipo ENUM('payment', 'shipping') NOT NULL,
            codigo VARCHAR(50) NOT NULL,
            config_json JSON NULL,
            ativo TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
            UNIQUE KEY unique_tenant_tipo (tenant_id, tipo),
            INDEX idx_tenant_tipo_codigo (tenant_id, tipo, codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Inserir configurações padrão para todos os tenants existentes
    $stmt = $db->query("SELECT id FROM tenants");
    $tenants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tenants as $tenantId) {
        // Gateway de pagamento padrão: manual
        $stmt = $db->prepare("
            INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json, ativo, created_at, updated_at)
            VALUES (:tenant_id, 'payment', 'manual', NULL, 1, NOW(), NOW())
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        
        // Gateway de frete padrão: simples
        $stmt = $db->prepare("
            INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json, ativo, created_at, updated_at)
            VALUES (:tenant_id, 'shipping', 'simples', NULL, 1, NOW(), NOW())
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
    }
}


