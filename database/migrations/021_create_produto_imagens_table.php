<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS produto_imagens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        produto_id BIGINT UNSIGNED NOT NULL,
        tipo ENUM('main','gallery') NOT NULL,
        ordem INT DEFAULT 0,
        caminho_arquivo VARCHAR(255) NOT NULL,
        url_original VARCHAR(500) NULL,
        alt_text VARCHAR(255) NULL,
        titulo VARCHAR(255) NULL,
        legenda TEXT NULL,
        mime_type VARCHAR(100) NULL,
        tamanho_arquivo BIGINT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        INDEX idx_produto_imagens_tenant_produto (tenant_id, produto_id),
        INDEX idx_produto_imagens_tenant_tipo (tenant_id, tipo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");



