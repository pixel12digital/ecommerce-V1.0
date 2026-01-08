<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se colunas já existem e adicionar caso não existam
$campos = [
    'peso' => "DECIMAL(8,2) NULL",
    'comprimento' => "DECIMAL(8,2) NULL",
    'largura' => "DECIMAL(8,2) NULL",
    'altura' => "DECIMAL(8,2) NULL"
];

foreach ($campos as $campo => $tipo) {
    $stmt = $db->query("SHOW COLUMNS FROM produtos LIKE '{$campo}'");
    if ($stmt->rowCount() > 0) {
        // Coluna já existe, pular
        continue;
    }
    
    // Determinar posição da coluna (após permite_pedidos_falta)
    $posicao = ($campo === 'peso') ? 'AFTER permite_pedidos_falta' : '';
    
    // Adicionar coluna
    $db->exec("
        ALTER TABLE produtos 
        ADD COLUMN {$campo} {$tipo} {$posicao}
    ");
}

