<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se coluna já existe
$stmt = $db->query("SHOW COLUMNS FROM produtos LIKE 'exibir_no_catalogo'");
if ($stmt->rowCount() > 0) {
    // Coluna já existe, pular
    return;
}

// Adicionar coluna exibir_no_catalogo
$db->exec("
    ALTER TABLE produtos 
    ADD COLUMN exibir_no_catalogo TINYINT(1) NOT NULL DEFAULT 1 
    AFTER status
");

