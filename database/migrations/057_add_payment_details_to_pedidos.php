<?php

use App\Core\Database;

$db = Database::getConnection();

$stmt = $db->query("SHOW COLUMNS FROM pedidos LIKE 'payment_details'");
if ($stmt->fetch() === false) {
    $db->exec("ALTER TABLE pedidos ADD COLUMN payment_details JSON NULL AFTER codigo_transacao");
}
