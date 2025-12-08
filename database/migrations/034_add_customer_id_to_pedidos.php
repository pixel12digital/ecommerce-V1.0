<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se a coluna já existe
$stmt = $db->query("SHOW COLUMNS FROM pedidos LIKE 'customer_id'");
$columnExists = $stmt->fetch();

if (!$columnExists) {
    // Adicionar coluna customer_id na tabela pedidos (nullable para manter compatibilidade com pedidos antigos)
    $db->exec("
        ALTER TABLE pedidos 
        ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER tenant_id
    ");
    
    // Adicionar índice (verificar se já existe)
    try {
        $db->exec("
            ALTER TABLE pedidos 
            ADD INDEX idx_pedidos_customer (tenant_id, customer_id)
        ");
    } catch (\Exception $e) {
        // Índice pode já existir, ignorar erro
    }
    
    // Adicionar foreign key (verificar se já existe)
    try {
        $db->exec("
            ALTER TABLE pedidos 
            ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ");
    } catch (\Exception $e) {
        // Foreign key pode já existir, ignorar erro
    }
}


