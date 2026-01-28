<?php

/**
 * Script para adicionar a coluna imagem_produto na tabela produto_atributo_termos
 * Execute este script uma vez para corrigir o erro de coluna não encontrada
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!empty($name)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

use App\Core\Database;

$db = Database::getConnection();

echo "Verificando se a coluna 'imagem_produto' existe na tabela 'produto_atributo_termos'...\n";

try {
    // Verificar se a coluna já existe
    $stmt = $db->query("SHOW COLUMNS FROM produto_atributo_termos LIKE 'imagem_produto'");
    if ($stmt->rowCount() > 0) {
        echo "✓ A coluna 'imagem_produto' já existe na tabela 'produto_atributo_termos'.\n";
        exit(0);
    }

    echo "Adicionando coluna 'imagem_produto'...\n";

    // Adicionar coluna imagem_produto
    $db->exec("
        ALTER TABLE produto_atributo_termos
        ADD COLUMN imagem_produto VARCHAR(255) NULL COMMENT 'Imagem do produto associada a este termo (para troca na loja)'
        AFTER atributo_termo_id
    ");

    echo "✓ Coluna 'imagem_produto' adicionada com sucesso!\n";

    // Registrar a migration como aplicada (se ainda não estiver)
    $migrationName = '056_add_imagem_produto_to_produto_atributo_termos';
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM migrations WHERE migration = :migration");
    $stmt->execute(['migration' => $migrationName]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        $stmt = $db->prepare("INSERT INTO migrations (migration, applied_at) VALUES (:migration, NOW())");
        $stmt->execute(['migration' => $migrationName]);
        echo "✓ Migration registrada na tabela 'migrations'.\n";
    } else {
        echo "✓ Migration já estava registrada.\n";
    }

} catch (\Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
