<?php
/**
 * Script de validação final - Queries de Sanidade
 * 
 * Proteção:
 * - Em ambiente local (APP_ENV=local): acesso livre
 * - Em produção: requer ?key=... que deve corresponder a SANITY_KEY no .env
 * 
 * Uso em produção:
 * https://seudominio.com/check_variations_sanity.php?key=SUA_CHAVE_SECRETA
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
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

// Proteção: verificar acesso
$appEnv = $_ENV['APP_ENV'] ?? 'production';
$isLocal = ($appEnv === 'local');

if (!$isLocal) {
    // Em produção, exige chave
    $providedKey = $_GET['key'] ?? '';
    $sanityKey = $_ENV['SANITY_KEY'] ?? '';
    
    if (empty($sanityKey) || $providedKey !== $sanityKey) {
        // Retornar 404 para não expor que o script existe
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
        exit;
    }
}

use App\Core\Database;

$db = Database::getConnection();

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Validação de Variações</title>";
echo "<style>body{font-family:monospace;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;}</style></head><body>";
echo "<h1>Validação Final - Queries de Sanidade</h1>";

// Query 1: Duplicatas por signature
echo "<h2>1. Duplicatas por Assinatura</h2>";
$stmt = $db->query("
    SELECT pv.produto_id, pv.tenant_id,
           COALESCE(
               pv.signature,
               (SELECT GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|')
                FROM produto_variacao_atributos pva
                WHERE pva.variacao_id = pv.id)
           ) AS signature,
           COUNT(*) AS total
    FROM produto_variacoes pv
    GROUP BY pv.produto_id, pv.tenant_id, signature
    HAVING total > 1
");
$duplicatas = $stmt->fetchAll();
if (empty($duplicatas)) {
    echo "<p class='ok'>✓ Nenhuma duplicata encontrada (0 linhas)</p>";
} else {
    echo "<p class='error'>✗ Encontradas " . count($duplicatas) . " duplicatas:</p>";
    echo "<table><tr><th>Produto ID</th><th>Tenant ID</th><th>Signature</th><th>Total</th></tr>";
    foreach ($duplicatas as $row) {
        echo "<tr><td>{$row['produto_id']}</td><td>{$row['tenant_id']}</td><td>{$row['signature']}</td><td>{$row['total']}</td></tr>";
    }
    echo "</table>";
}

// Query 2: Variações sem atributos
echo "<h2>2. Variações Incompletas (Sem Atributos)</h2>";
$stmt = $db->query("
    SELECT pv.id, pv.produto_id, pv.tenant_id, COUNT(pva.id) AS qtd_atrib
    FROM produto_variacoes pv
    LEFT JOIN produto_variacao_atributos pva ON pva.variacao_id = pv.id AND pva.tenant_id = pv.tenant_id
    GROUP BY pv.id, pv.produto_id, pv.tenant_id
    HAVING qtd_atrib = 0
");
$semAtributos = $stmt->fetchAll();
if (empty($semAtributos)) {
    echo "<p class='ok'>✓ Todas as variações têm atributos (0 linhas)</p>";
} else {
    echo "<p class='error'>✗ Encontradas " . count($semAtributos) . " variações sem atributos:</p>";
    echo "<table><tr><th>Variação ID</th><th>Produto ID</th><th>Tenant ID</th></tr>";
    foreach ($semAtributos as $row) {
        echo "<tr><td>{$row['id']}</td><td>{$row['produto_id']}</td><td>{$row['tenant_id']}</td></tr>";
    }
    echo "</table>";
}

// Query 3: Signatures nulas/vazias
echo "<h2>3. Variações sem Signature (NULL ou Vazia)</h2>";
$stmt = $db->query("
    SELECT pv.id, pv.produto_id, pv.tenant_id
    FROM produto_variacoes pv
    WHERE (pv.signature IS NULL OR pv.signature = '')
    AND EXISTS (
        SELECT 1 FROM produto_variacao_atributos pva 
        WHERE pva.variacao_id = pv.id
    )
");
$semSignature = $stmt->fetchAll();
if (empty($semSignature)) {
    echo "<p class='ok'>✓ Todas as variações têm signature (0 linhas)</p>";
} else {
    echo "<p class='warning'>⚠ Encontradas " . count($semSignature) . " variações sem signature:</p>";
    echo "<table><tr><th>Variação ID</th><th>Produto ID</th><th>Tenant ID</th></tr>";
    foreach ($semSignature as $row) {
        echo "<tr><td>{$row['id']}</td><td>{$row['produto_id']}</td><td>{$row['tenant_id']}</td></tr>";
    }
    echo "</table>";
}

// Query 4: Consistência de assinaturas
echo "<h2>4. Consistência de Assinaturas</h2>";
$stmt = $db->query("
    SELECT 
        pv.id,
        pv.produto_id,
        pv.signature AS signature_coluna,
        (SELECT GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|')
         FROM produto_variacao_atributos pva
         WHERE pva.variacao_id = pv.id) AS signature_calculada
    FROM produto_variacoes pv
    WHERE pv.signature IS NOT NULL
    HAVING signature_coluna != signature_calculada
");
$inconsistentes = $stmt->fetchAll();
if (empty($inconsistentes)) {
    echo "<p class='ok'>✓ Todas as assinaturas estão consistentes (0 linhas)</p>";
} else {
    echo "<p class='error'>✗ Encontradas " . count($inconsistentes) . " assinaturas inconsistentes:</p>";
    echo "<table><tr><th>Variação ID</th><th>Produto ID</th><th>Signature (Coluna)</th><th>Signature (Calculada)</th></tr>";
    foreach ($inconsistentes as $row) {
        echo "<tr><td>{$row['id']}</td><td>{$row['produto_id']}</td><td>{$row['signature_coluna']}</td><td>{$row['signature_calculada']}</td></tr>";
    }
    echo "</table>";
}

echo "</body></html>";
