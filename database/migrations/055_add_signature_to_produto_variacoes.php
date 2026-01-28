<?php

use App\Core\Database;

$db = Database::getConnection();

// Adicionar coluna signature
$db->exec("
    ALTER TABLE produto_variacoes
    ADD COLUMN signature VARCHAR(500) NULL COMMENT 'Assinatura única da combinação de atributos (formato: atributo_id:termo_id|atributo_id:termo_id)'
    AFTER status
");

// Popular signature para variações existentes
$db->exec("
    UPDATE produto_variacoes pv
    INNER JOIN (
        SELECT 
            pva.variacao_id,
            GROUP_CONCAT(
                CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) 
                ORDER BY pva.atributo_id 
                SEPARATOR '|'
            ) as signature
        FROM produto_variacao_atributos pva
        GROUP BY pva.variacao_id
    ) sig ON sig.variacao_id = pv.id
    SET pv.signature = sig.signature
");

// Criar índice único para prevenir duplicatas mesmo em requisições concorrentes
$db->exec("
    ALTER TABLE produto_variacoes
    ADD UNIQUE KEY unique_produto_signature (tenant_id, produto_id, signature)
");
