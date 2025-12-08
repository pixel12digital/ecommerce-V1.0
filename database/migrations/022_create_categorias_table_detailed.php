<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se tabela já existe
$stmt = $db->query("SHOW TABLES LIKE 'categorias'");
if ($stmt->rowCount() > 0) {
    // Tabela já existe, pular criação
    return;
}

$db->exec("
    CREATE TABLE categorias (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        id_original_wp INT NULL,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        descricao TEXT NULL,
        categoria_pai_id BIGINT UNSIGNED NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (categoria_pai_id) REFERENCES categorias(id) ON DELETE SET NULL,
        INDEX idx_categorias_tenant (tenant_id),
        INDEX idx_categorias_tenant_slug (tenant_id, slug),
        INDEX idx_categorias_tenant_pai (tenant_id, categoria_pai_id),
        INDEX idx_categorias_id_original_wp (id_original_wp),
        UNIQUE KEY unique_categorias_tenant_slug (tenant_id, slug)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

