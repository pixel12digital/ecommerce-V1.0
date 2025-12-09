<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se tabela já existe
$stmt = $db->query("SHOW TABLES LIKE 'contact_messages'");
if ($stmt->rowCount() > 0) {
    // Tabela já existe, pular criação
    return;
}

$db->exec("
    CREATE TABLE contact_messages (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        nome VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        telefone VARCHAR(50) NULL,
        tipo_assunto ENUM(
            'duvidas_produtos',
            'pedido_andamento',
            'trocas_devolucoes',
            'pagamento',
            'problema_site',
            'outros'
        ) NOT NULL,
        numero_pedido VARCHAR(50) NULL,
        mensagem TEXT NOT NULL,
        status ENUM('novo', 'lido') NOT NULL DEFAULT 'novo',
        origin_url VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_contact_messages_tenant (tenant_id),
        INDEX idx_contact_messages_status (tenant_id, status),
        INDEX idx_contact_messages_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

