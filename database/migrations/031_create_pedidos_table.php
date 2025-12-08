<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS pedidos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        numero_pedido VARCHAR(30) NOT NULL,
        status ENUM('pending', 'paid', 'canceled', 'shipped', 'completed') NOT NULL DEFAULT 'pending',
        total_produtos DECIMAL(10,2) NOT NULL,
        total_frete DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_descontos DECIMAL(10,2) NOT NULL DEFAULT 0,
        total_geral DECIMAL(10,2) NOT NULL,
        cliente_nome VARCHAR(150) NOT NULL,
        cliente_email VARCHAR(150) NOT NULL,
        cliente_telefone VARCHAR(50) NULL,
        entrega_cep VARCHAR(20) NOT NULL,
        entrega_logradouro VARCHAR(150) NOT NULL,
        entrega_numero VARCHAR(20) NOT NULL,
        entrega_complemento VARCHAR(100) NULL,
        entrega_bairro VARCHAR(100) NOT NULL,
        entrega_cidade VARCHAR(100) NOT NULL,
        entrega_estado VARCHAR(2) NOT NULL,
        metodo_pagamento VARCHAR(50) NOT NULL,
        metodo_frete VARCHAR(50) NOT NULL,
        codigo_transacao VARCHAR(100) NULL,
        observacoes TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        UNIQUE KEY unique_tenant_numero (tenant_id, numero_pedido),
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_tenant_status (tenant_id, status),
        INDEX idx_numero_pedido (numero_pedido)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");


