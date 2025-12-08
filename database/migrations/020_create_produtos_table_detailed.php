<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se tabela já existe
$stmt = $db->query("SHOW TABLES LIKE 'produtos'");
if ($stmt->rowCount() > 0) {
    // Tabela já existe, pular criação
    return;
}

$db->exec("
    CREATE TABLE produtos (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tenant_id BIGINT UNSIGNED NOT NULL,
        id_original_wp INT NULL,
        nome VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        sku VARCHAR(100) NULL,
        tipo ENUM('simple','variable','grouped','external') DEFAULT 'simple',
        status ENUM('publish','draft','private') DEFAULT 'publish',
        preco DECIMAL(10,2) DEFAULT 0.00,
        preco_regular DECIMAL(10,2) DEFAULT 0.00,
        preco_promocional DECIMAL(10,2) NULL,
        data_promocao_inicio DATETIME NULL,
        data_promocao_fim DATETIME NULL,
        gerencia_estoque TINYINT(1) DEFAULT 0,
        quantidade_estoque INT DEFAULT 0,
        status_estoque ENUM('instock','outofstock','onbackorder') DEFAULT 'instock',
        permite_pedidos_falta ENUM('no','notify','yes') DEFAULT 'no',
        peso DECIMAL(8,2) NULL,
        comprimento DECIMAL(8,2) NULL,
        largura DECIMAL(8,2) NULL,
        altura DECIMAL(8,2) NULL,
        descricao TEXT NULL,
        descricao_curta TEXT NULL,
        imagem_principal VARCHAR(255) NULL,
        destaque TINYINT(1) DEFAULT 0,
        visibilidade_catalogo ENUM('visible','catalog','search','hidden') DEFAULT 'visible',
        status_imposto ENUM('taxable','shipping','none') DEFAULT 'taxable',
        data_criacao DATETIME NOT NULL,
        data_modificacao DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
        INDEX idx_produtos_tenant (tenant_id),
        INDEX idx_produtos_tenant_slug (tenant_id, slug),
        INDEX idx_produtos_tenant_sku (tenant_id, sku),
        INDEX idx_produtos_tenant_status (tenant_id, status),
        INDEX idx_produtos_id_original_wp (id_original_wp),
        UNIQUE KEY unique_produtos_tenant_slug (tenant_id, slug),
        UNIQUE KEY unique_produtos_tenant_wp_id (tenant_id, id_original_wp)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

