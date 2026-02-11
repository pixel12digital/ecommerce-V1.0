<?php

use App\Core\Database;

$db = Database::getConnection();

$stmt = $db->query("SHOW COLUMNS FROM customers LIKE 'cpf'");
if ($stmt->fetch() === false) {
    $db->exec("ALTER TABLE customers ADD COLUMN cpf VARCHAR(14) NULL AFTER phone");
}
