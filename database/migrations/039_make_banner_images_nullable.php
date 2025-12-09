<?php

use App\Core\Database;

$db = Database::getConnection();

// Verificar se as colunas já permitem NULL
$stmt = $db->query("SHOW COLUMNS FROM banners WHERE Field = 'imagem_desktop'");
$column = $stmt->fetch();

if ($column && $column['Null'] === 'NO') {
    // Tornar imagem_desktop opcional
    $db->exec("
        ALTER TABLE banners 
        MODIFY COLUMN imagem_desktop VARCHAR(255) NULL
    ");
}

// Verificar imagem_mobile
$stmt = $db->query("SHOW COLUMNS FROM banners WHERE Field = 'imagem_mobile'");
$column = $stmt->fetch();

if ($column && $column['Null'] === 'NO') {
    // Tornar imagem_mobile opcional (já deveria ser, mas garantindo)
    $db->exec("
        ALTER TABLE banners 
        MODIFY COLUMN imagem_mobile VARCHAR(255) NULL
    ");
}

