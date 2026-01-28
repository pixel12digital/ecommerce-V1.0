<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    CREATE TABLE IF NOT EXISTS produto_variacoes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        produto_id BIGINT UNSIGNED NOT NULL,
        sku VARCHAR(100) NULL,
        preco DECIMAL(10,2) NULL COMMENT 'Preço específico da variação (herda do produto se NULL)',
        preco_regular DECIMAL(10,2) NULL COMMENT 'Preço regular específico (herda do produto se NULL)',
        preco_promocional DECIMAL(10,2) NULL COMMENT 'Preço promocional específico (herda do produto se NULL)',
        data_promocao_inicio DATETIME NULL,
        data_promocao_fim DATETIME NULL,
        gerencia_estoque TINYINT(1) DEFAULT 1 COMMENT 'Se gerencia estoque próprio (1) ou herda do produto (0)',
        quantidade_estoque INT DEFAULT 0,
        status_estoque ENUM('instock','outofstock','onbackorder') DEFAULT 'instock',
        permite_pedidos_falta ENUM('no','notify','yes') DEFAULT 'no',
        imagem VARCHAR(255) NULL COMMENT 'Imagem específica da variação',
        peso DECIMAL(8,2) NULL COMMENT 'Peso específico (herda do produto se NULL)',
        comprimento DECIMAL(8,2) NULL,
        largura DECIMAL(8,2) NULL,
        altura DECIMAL(8,2) NULL,
        status ENUM('publish','draft','private') DEFAULT 'publish',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
        INDEX idx_tenant_id (tenant_id),
        INDEX idx_produto_id (produto_id),
        INDEX idx_tenant_sku (tenant_id, sku),
        INDEX idx_status (status),
        INDEX idx_status_estoque (status_estoque),
        UNIQUE KEY unique_tenant_sku (tenant_id, sku)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
