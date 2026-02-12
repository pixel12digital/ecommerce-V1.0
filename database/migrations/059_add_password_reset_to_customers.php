<?php

/**
 * Adiciona colunas password_reset_token e password_reset_expires à tabela customers
 * para suportar o fluxo de "Primeiro acesso" (criar senha via link por email)
 */

use App\Core\Database;

$db = Database::getConnection();

// Verificar se coluna já existe antes de adicionar
$stmt = $db->prepare("
    SELECT COUNT(*) as cnt FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'customers' 
    AND COLUMN_NAME = 'password_reset_token'
");
$stmt->execute();
$exists = $stmt->fetch()['cnt'] > 0;

if (!$exists) {
    $db->exec("
        ALTER TABLE customers 
        ADD COLUMN password_reset_token VARCHAR(255) NULL DEFAULT NULL AFTER password_hash,
        ADD COLUMN password_reset_expires DATETIME NULL DEFAULT NULL AFTER password_reset_token
    ");
}
