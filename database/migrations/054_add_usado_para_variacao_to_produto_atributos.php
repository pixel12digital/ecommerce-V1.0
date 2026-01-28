<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se a coluna já existe
$stmt = $db->query("SHOW COLUMNS FROM produto_atributos LIKE 'usado_para_variacao'");
if ($stmt->rowCount() > 0) {
    // Coluna já existe, pular
    return;
}

// Adicionar coluna usado_para_variacao
$db->exec("
    ALTER TABLE produto_atributos
    ADD COLUMN usado_para_variacao TINYINT(1) DEFAULT 0 COMMENT 'Se este atributo é usado para gerar variações' AFTER ordem
");
