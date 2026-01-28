<?php
/**
 * Script CLI de validação final - Queries de Sanidade
 * Uso: php database/check_variations_sanity_cli.php
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

use App\Core\Database;

$db = Database::getConnection();

echo "========================================\n";
echo "Validação Final - Queries de Sanidade\n";
echo "========================================\n\n";

$erros = 0;

// Query 1: Duplicatas por signature
echo "1. Duplicatas por Assinatura...\n";
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
    echo "   ✓ Nenhuma duplicata encontrada (0 linhas)\n";
} else {
    echo "   ✗ Encontradas " . count($duplicatas) . " duplicatas:\n";
    foreach ($duplicatas as $row) {
        echo "     - Produto {$row['produto_id']}, Tenant {$row['tenant_id']}, Signature: {$row['signature']}, Total: {$row['total']}\n";
    }
    $erros++;
}

// Query 2: Variações sem atributos
echo "\n2. Variações Incompletas (Sem Atributos)...\n";
$stmt = $db->query("
    SELECT pv.id, pv.produto_id, pv.tenant_id, COUNT(pva.id) AS qtd_atrib
    FROM produto_variacoes pv
    LEFT JOIN produto_variacao_atributos pva ON pva.variacao_id = pv.id AND pva.tenant_id = pv.tenant_id
    GROUP BY pv.id, pv.produto_id, pv.tenant_id
    HAVING qtd_atrib = 0
");
$semAtributos = $stmt->fetchAll();
if (empty($semAtributos)) {
    echo "   ✓ Todas as variações têm atributos (0 linhas)\n";
} else {
    echo "   ✗ Encontradas " . count($semAtributos) . " variações sem atributos:\n";
    foreach ($semAtributos as $row) {
        echo "     - Variação {$row['id']}, Produto {$row['produto_id']}, Tenant {$row['tenant_id']}\n";
    }
    $erros++;
}

// Query 3: Signatures nulas/vazias
echo "\n3. Variações sem Signature (NULL ou Vazia)...\n";
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
    echo "   ✓ Todas as variações têm signature (0 linhas)\n";
} else {
    echo "   ⚠ Encontradas " . count($semSignature) . " variações sem signature:\n";
    foreach ($semSignature as $row) {
        echo "     - Variação {$row['id']}, Produto {$row['produto_id']}, Tenant {$row['tenant_id']}\n";
    }
    // Não conta como erro crítico, apenas warning
}

// Query 4: Consistência de assinaturas
echo "\n4. Consistência de Assinaturas...\n";
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
    echo "   ✓ Todas as assinaturas estão consistentes (0 linhas)\n";
} else {
    echo "   ✗ Encontradas " . count($inconsistentes) . " assinaturas inconsistentes:\n";
    foreach ($inconsistentes as $row) {
        echo "     - Variação {$row['id']}, Produto {$row['produto_id']}\n";
        echo "       Coluna: {$row['signature_coluna']}\n";
        echo "       Calculada: {$row['signature_calculada']}\n";
    }
    $erros++;
}

echo "\n========================================\n";
if ($erros === 0) {
    echo "✓ VALIDAÇÃO PASSOU - Sistema pronto para produção\n";
    exit(0);
} else {
    echo "✗ VALIDAÇÃO FALHOU - {$erros} erro(s) encontrado(s)\n";
    exit(1);
}
