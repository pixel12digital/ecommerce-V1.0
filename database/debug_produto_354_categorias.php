<?php
/**
 * Script de diagnóstico para produto SKU 354
 * Executar via linha de comando: php database/debug_produto_354_categorias.php
 */

// Carregar autoloader do Composer primeiro
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente do .env (necessário para banco remoto)
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
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;
use App\Tenant\TenantContext;

$db = Database::getConnection();

// Descobrir ID do produto pelo SKU 354
$stmt = $db->prepare("SELECT id, nome, tenant_id FROM produtos WHERE sku = '354' LIMIT 1");
$stmt->execute();
$produto = $stmt->fetch();

if (!$produto) {
    echo "Produto com SKU 354 não encontrado\n";
    exit;
}

$produtoId = $produto['id'];
$tenantId = $produto['tenant_id'];

// Definir tenant no contexto (necessário para algumas operações)
try {
    TenantContext::setFixedTenant($tenantId);
} catch (\Exception $e) {
    // Se falhar, continuar mesmo assim (já temos o tenant_id do produto)
    echo "Aviso: Não foi possível definir tenant no contexto: " . $e->getMessage() . "\n";
}

echo "Produto SKU 354 → ID: {$produtoId}, Nome: {$produto['nome']}, Tenant: {$tenantId}\n\n";

// Buscar vínculos em produto_categorias
$stmt = $db->prepare("
    SELECT pc.tenant_id, pc.produto_id, pc.categoria_id, c.nome
    FROM produto_categorias pc
    LEFT JOIN categorias c
      ON c.id = pc.categoria_id
     AND c.tenant_id = pc.tenant_id
    WHERE pc.produto_id = ?
    ORDER BY pc.categoria_id
");
$stmt->execute([$produtoId]);
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "Nenhuma linha em produto_categorias para o produto {$produtoId}\n";
} else {
    echo "Linhas em produto_categorias:\n";
    foreach ($rows as $row) {
        $nomeCategoria = $row['nome'] ?? '(categoria não encontrada)';
        echo "- tenant_id={$row['tenant_id']}, categoria_id={$row['categoria_id']}, nome={$nomeCategoria}\n";
    }
}

