<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS coupons (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        code VARCHAR(50) NOT NULL,
        type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
        value DECIMAL(10,2) NOT NULL,
        min_purchase DECIMAL(10,2) NULL,
        max_discount DECIMAL(10,2) NULL,
        usage_limit INT NULL,
        used_count INT NOT NULL DEFAULT 0,
        valid_from DATETIME NULL,
        valid_until DATETIME NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        UNIQUE KEY unique_tenant_code (tenant_id, code),
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_code (code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");



