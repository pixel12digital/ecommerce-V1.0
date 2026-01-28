<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se as colunas já existem
$stmt = $db->query("SHOW COLUMNS FROM pedido_itens LIKE 'variacao_id'");
if ($stmt->rowCount() > 0) {
    // Coluna já existe, pular
    return;
}

// Adicionar coluna variacao_id e atributos_json
$db->exec("
    ALTER TABLE pedido_itens
    ADD COLUMN variacao_id BIGINT UNSIGNED NULL AFTER produto_id,
    ADD COLUMN atributos_json TEXT NULL COMMENT 'Snapshot JSON dos atributos da variação no momento do pedido' AFTER sku
");

// Adicionar índice
try {
    $db->exec("
        ALTER TABLE pedido_itens
        ADD INDEX idx_variacao_id (variacao_id)
    ");
} catch (\Exception $e) {
    // Índice já existe, ignorar
    error_log("Índice idx_variacao_id já existe ou erro ao criar: " . $e->getMessage());
}

// Adicionar foreign key (só se tabela produto_variacoes existir)
$stmt = $db->query("SHOW TABLES LIKE 'produto_variacoes'");
if ($stmt->rowCount() > 0) {
    try {
        $db->exec("
            ALTER TABLE pedido_itens
            ADD CONSTRAINT fk_pedido_itens_variacao
                FOREIGN KEY (variacao_id) REFERENCES produto_variacoes(id) ON DELETE SET NULL
        ");
    } catch (\Exception $e) {
        // Foreign key já existe, ignorar
        error_log("Foreign key fk_pedido_itens_variacao já existe ou erro ao criar: " . $e->getMessage());
    }
}
