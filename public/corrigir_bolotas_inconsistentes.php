<?php
/**
 * Script para Corrigir Bolotas Inconsistentes
 * 
 * Identifica e desativa bolotas que apontam para categorias inexistentes
 * 
 * Uso:
 *   Via web: http://seu-dominio.com/corrigir_bolotas_inconsistentes.php?tenant_id=1
 *   Via CLI: php public/corrigir_bolotas_inconsistentes.php --tenant-id=1
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
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;
use App\Tenant\TenantContext;

// Resolver tenant
$tenantId = null;
$dryRun = false;
if (php_sapi_name() === 'cli') {
    // Modo CLI
    $options = getopt('', ['tenant-id:', 'dry-run']);
    $tenantId = isset($options['tenant-id']) ? (int)$options['tenant-id'] : 1;
    $dryRun = isset($options['dry-run']);
} else {
    // Modo Web
    header('Content-Type: text/html; charset=utf-8');
    $tenantId = isset($_GET['tenant_id']) ? (int)$_GET['tenant_id'] : 1;
    $dryRun = isset($_GET['dry_run']) && $_GET['dry_run'] === '1';
}

// Configurar tenant para o contexto
try {
    TenantContext::setFixedTenant($tenantId);
} catch (\Exception $e) {
    die("Erro ao carregar tenant ID {$tenantId}: " . $e->getMessage() . "\n");
}

// Inicializar conexão
$db = Database::getConnection();

// 1. Identificar bolotas inconsistentes (categoria_id não existe em categorias)
$stmt = $db->prepare("
    SELECT 
        hcp.id,
        hcp.categoria_id,
        hcp.label,
        hcp.ordem,
        hcp.ativo
    FROM home_category_pills hcp
    LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
    WHERE hcp.tenant_id = :tenant_id_where
    AND hcp.ativo = 1
    AND (c.id IS NULL OR hcp.categoria_id IS NULL)
    ORDER BY hcp.ordem ASC, hcp.id ASC
");
$stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
$stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
$stmt->execute();
$bolotasInconsistentes = $stmt->fetchAll();

if (empty($bolotasInconsistentes)) {
    $message = "✅ Nenhuma bolota inconsistente encontrada no tenant ID {$tenantId}.\n";
    if (php_sapi_name() === 'cli') {
        echo $message;
    } else {
        echo "<h1>Correção de Bolotas Inconsistentes</h1><p>{$message}</p>";
    }
    exit(0);
}

// 2. Exibir bolotas inconsistentes encontradas
$count = count($bolotasInconsistentes);
$message = "Encontradas {$count} bolota(s) inconsistente(s) no tenant ID {$tenantId}:\n\n";

if (php_sapi_name() === 'cli') {
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "  CORREÇÃO DE BOLOTAS INCONSISTENTES - Tenant ID: {$tenantId}\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n\n";
    echo $message;
    
    foreach ($bolotasInconsistentes as $bolota) {
        echo sprintf(
            "  • ID: %d | Label: %s | Categoria ID: %s | Ordem: %s | Status: %s\n",
            $bolota['id'],
            $bolota['label'] ?: '(sem label)',
            $bolota['categoria_id'] ?: 'NULL',
            $bolota['ordem'],
            $bolota['ativo'] ? 'Ativo' : 'Inativo'
        );
    }
    echo "\n";
} else {
    echo "<h1>Correção de Bolotas Inconsistentes</h1>";
    echo "<p>" . nl2br($message) . "</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Label</th><th>Categoria ID</th><th>Ordem</th><th>Status</th></tr>";
    foreach ($bolotasInconsistentes as $bolota) {
        echo "<tr>";
        echo "<td>{$bolota['id']}</td>";
        echo "<td>" . htmlspecialchars($bolota['label'] ?: '(sem label)') . "</td>";
        echo "<td>" . ($bolota['categoria_id'] ?: 'NULL') . "</td>";
        echo "<td>{$bolota['ordem']}</td>";
        echo "<td>" . ($bolota['ativo'] ? 'Ativo' : 'Inativo') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
}

// 3. Aplicar correção (desativar bolotas inconsistentes)
if ($dryRun) {
    $actionMessage = "🔍 MODO DRY-RUN: Nenhuma alteração será feita.\n";
    $actionMessage .= "Execute sem --dry-run para aplicar as correções.\n";
} else {
    $ids = array_column($bolotasInconsistentes, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    $stmt = $db->prepare("
        UPDATE home_category_pills 
        SET ativo = 0, updated_at = NOW()
        WHERE tenant_id = ? 
        AND id IN ({$placeholders})
    ");
    
    $params = array_merge([$tenantId], $ids);
    $stmt->execute($params);
    
    $affected = $stmt->rowCount();
    $actionMessage = "✅ Correção aplicada: {$affected} bolota(s) desativada(s) (ativo = 0).\n";
}

if (php_sapi_name() === 'cli') {
    echo $actionMessage;
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
    echo "  Re-execute a auditoria para confirmar:\n";
    echo "  php public/auditoria_bolotas_categorias.php --tenant-id={$tenantId}\n";
    echo "═══════════════════════════════════════════════════════════════════════════════\n";
} else {
    echo "<p><strong>" . nl2br($actionMessage) . "</strong></p>";
    if (!$dryRun) {
        echo "<p><a href='auditoria_bolotas_categorias.php?tenant_id={$tenantId}&format=html'>Re-executar Auditoria</a></p>";
    }
}
