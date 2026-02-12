<?php

/**
 * Adiciona coluna frete_gratis à tabela produtos
 * Permite marcar produtos individuais como frete grátis
 */

use App\Core\Database;

$db = Database::getConnection();

$stmt = $db->prepare("
    SELECT COUNT(*) as cnt FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'produtos' 
    AND COLUMN_NAME = 'frete_gratis'
");
$stmt->execute();
$exists = $stmt->fetch()['cnt'] > 0;

if (!$exists) {
    $db->exec("
        ALTER TABLE produtos 
        ADD COLUMN frete_gratis TINYINT(1) NOT NULL DEFAULT 0 AFTER exibir_no_catalogo
    ");
}
