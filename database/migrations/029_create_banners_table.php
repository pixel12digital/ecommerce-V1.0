<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS banners (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        tipo ENUM('hero', 'portrait') NOT NULL,
        titulo VARCHAR(150) NULL,
        subtitulo VARCHAR(255) NULL,
        cta_label VARCHAR(50) NULL,
        cta_url VARCHAR(255) NULL,
        imagem_desktop VARCHAR(255) NOT NULL,
        imagem_mobile VARCHAR(255) NULL,
        ordem INT UNSIGNED NOT NULL DEFAULT 0,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_tenant_tipo_ativo (tenant_id, tipo, ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");


