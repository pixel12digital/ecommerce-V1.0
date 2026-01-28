<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se a coluna já existe
$stmt = $db->query("SHOW COLUMNS FROM produto_atributo_termos LIKE 'imagem_produto'");
if ($stmt->rowCount() == 0) {
    // Adicionar coluna imagem_produto
    $db->exec("
        ALTER TABLE produto_atributo_termos
        ADD COLUMN imagem_produto VARCHAR(255) NULL COMMENT 'Imagem do produto associada a este termo (para troca na loja)'
        AFTER atributo_termo_id
    ");
}
