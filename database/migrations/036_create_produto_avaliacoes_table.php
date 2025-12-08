<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se a tabela já existe
$stmt = $db->query("SHOW TABLES LIKE 'produto_avaliacoes'");
$tableExists = $stmt->fetch();

if (!$tableExists) {
    try {
        $db->exec("
            CREATE TABLE produto_avaliacoes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                produto_id BIGINT UNSIGNED NOT NULL,
                customer_id BIGINT UNSIGNED NOT NULL,
                pedido_id BIGINT UNSIGNED NULL,
                nota TINYINT UNSIGNED NOT NULL COMMENT 'Nota de 1 a 5',
                titulo VARCHAR(150) NULL,
                comentario TEXT NULL,
                status ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
                INDEX idx_tenant_produto (tenant_id, produto_id),
                INDEX idx_tenant_customer (tenant_id, customer_id),
                INDEX idx_tenant_pedido (tenant_id, pedido_id),
                INDEX idx_tenant_status (tenant_id, status),
                INDEX idx_produto_status (produto_id, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (\PDOException $e) {
        // Se a tabela já existe (erro de duplicação), ignorar
        if (strpos($e->getMessage(), 'already exists') === false) {
            throw $e;
        }
    }
}


