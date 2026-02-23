<?php

use App\Core\Database;

$db = Database::getConnection();

$db->exec("
    ALTER TABLE produtos 
    ADD COLUMN informacoes_adicionais TEXT NULL AFTER descricao
");
