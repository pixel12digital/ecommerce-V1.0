<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS produto_variacao_atributos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        variacao_id BIGINT UNSIGNED NOT NULL,
        atributo_id BIGINT UNSIGNED NOT NULL,
        atributo_termo_id BIGINT UNSIGNED NOT NULL COMMENT 'Termo selecionado para este atributo nesta variação',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (variacao_id) REFERENCES produto_variacoes(id) ON DELETE CASCADE,
        FOREIGN KEY (atributo_id) REFERENCES atributos(id) ON DELETE CASCADE,
        FOREIGN KEY (atributo_termo_id) REFERENCES atributo_termos(id) ON DELETE CASCADE,
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_variacao_id (variacao_id),
        INDEX idx_atributo_id (atributo_id),
        INDEX idx_atributo_termo_id (atributo_termo_id),
        UNIQUE KEY unique_variacao_atributo (variacao_id, atributo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
