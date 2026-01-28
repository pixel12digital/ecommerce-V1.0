<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS atributo_termos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        atributo_id BIGINT UNSIGNED NOT NULL,
        nome VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL,
        valor_cor VARCHAR(7) NULL COMMENT 'Código hexadecimal da cor (ex: #FF0000)',
        imagem VARCHAR(255) NULL COMMENT 'Caminho da imagem do termo',
        ordem INT UNSIGNED DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (atributo_id) REFERENCES atributos(id) ON DELETE CASCADE,
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_atributo_id (atributo_id),
        INDEX idx_tenant_atributo_slug (tenant_id, atributo_id, slug),
        UNIQUE KEY unique_tenant_atributo_slug (tenant_id, atributo_id, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
