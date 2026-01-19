<?php

use App\Core\Database;

$db = Database::getConnection();

// Adicionar campos de frete/etiqueta na tabela pedidos
$campos = [
    'shipping_provider' => "VARCHAR(50) NULL COMMENT 'Provider usado (correios, simples, etc)'",
    'tracking_code' => "VARCHAR(100) NULL COMMENT 'Código de rastreamento'",
    'label_url' => "TEXT NULL COMMENT 'URL ou ID da etiqueta para impressão'",
    'documento_envio' => "ENUM('declaracao_conteudo', 'nota_fiscal') NULL DEFAULT 'declaracao_conteudo' COMMENT 'Tipo de documento de envio'",
    'nf_reference' => "VARCHAR(255) NULL COMMENT 'Referência da Nota Fiscal (chave, número ou observação)'",
    'nf_chave' => "VARCHAR(44) NULL COMMENT 'Chave da Nota Fiscal (44 dígitos) - DEPRECATED, usar nf_reference'",
    'nf_arquivo_path' => "VARCHAR(255) NULL COMMENT 'Caminho do arquivo PDF da NF se anexado'",
    'prazo_entrega' => "VARCHAR(50) NULL COMMENT 'Prazo estimado de entrega (ex: 5 a 8 dias úteis)'",
];

foreach ($campos as $campo => $definicao) {
    // Verificar se coluna já existe
    $stmt = $db->query("SHOW COLUMNS FROM pedidos LIKE '{$campo}'");
    if ($stmt->rowCount() > 0) {
        // Coluna já existe, pular
        continue;
    }
    
    // Adicionar coluna
    $db->exec("
        ALTER TABLE pedidos 
        ADD COLUMN {$campo} {$definicao}
    ");
}

// Adicionar índices
try {
    $db->exec("CREATE INDEX idx_pedidos_tracking_code ON pedidos(tracking_code)");
} catch (\Exception $e) {
    // Índice já existe, ignorar
}

try {
    $db->exec("CREATE INDEX idx_pedidos_shipping_provider ON pedidos(shipping_provider)");
} catch (\Exception $e) {
    // Índice já existe, ignorar
}

