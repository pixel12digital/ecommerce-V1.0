<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS atributos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        tipo ENUM('select','color','image') DEFAULT 'select',
        ordem INT UNSIGNED DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_tenant_slug (tenant_id, slug),
        UNIQUE KEY unique_tenant_slug (tenant_id, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
