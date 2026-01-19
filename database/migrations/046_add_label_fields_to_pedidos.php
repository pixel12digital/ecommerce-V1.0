<?php

use App\Core\Database;

$db = Database::getConnection();

// Adicionar campos de etiqueta faltantes na tabela pedidos
// Migration complementar à 045_add_shipping_fields_to_pedidos.php
$campos = [
    'label_id' => "VARCHAR(100) NULL COMMENT 'ID da postagem nos Correios'",
    'label_pdf_path' => "VARCHAR(255) NULL COMMENT 'Caminho local do arquivo PDF da etiqueta (se armazenado)'",
    'label_format' => "ENUM('A4', '10x15') NOT NULL DEFAULT 'A4' COMMENT 'Formato de impressão preferido (A4 padrão, 10x15 térmica)'",
    'label_generated_at' => "DATETIME NULL COMMENT 'Data/hora de geração da etiqueta'",
];

foreach ($campos as $campo => $definicao) {
    // Verificar se coluna já existe
    $stmt = $db->query("SHOW COLUMNS FROM pedidos LIKE '{$campo}'");
    if ($stmt->rowCount() > 0) {
        // Coluna já existe, pular
        continue;
    }
    
    // Adicionar coluna
    try {
        $db->exec("
            ALTER TABLE pedidos 
            ADD COLUMN {$campo} {$definicao}
        ");
    } catch (\Exception $e) {
        error_log("Erro ao adicionar coluna {$campo}: " . $e->getMessage());
        // Continuar com próximo campo mesmo se houver erro
    }
}

// Adicionar índices
try {
    $db->exec("CREATE INDEX idx_pedidos_label_id ON pedidos(label_id)");
} catch (\Exception $e) {
    // Índice já existe, ignorar
}

try {
    $db->exec("CREATE INDEX idx_pedidos_label_generated_at ON pedidos(label_generated_at)");
} catch (\Exception $e) {
    // Índice já existe, ignorar
}
