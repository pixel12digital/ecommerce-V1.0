<?php
/**
 * Script de diagnóstico para verificar categorias no menu
 * Acesse via: https://pontodogolfeoutlet.com.br/debug_categorias.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Tenant\TenantContext;

// Inicializar tenant context usando a mesma lógica do sistema
try {
    $config = require __DIR__ . '/../config/app.php';
    $mode = $config['mode'] ?? 'single';
    
    if ($mode === 'single') {
        $defaultTenantId = $config['default_tenant_id'] ?? 1;
        TenantContext::setFixedTenant($defaultTenantId);
    } else {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        TenantContext::resolveFromHost($host);
    }
    
    $tenant = TenantContext::tenant();
    $tenantId = TenantContext::id();
    $db = Database::getConnection();
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico de Categorias</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;}";
    echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
    echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
    echo "th{background-color:#4CAF50;color:white;}";
    echo ".success{color:green;font-weight:bold;}";
    echo ".error{color:red;font-weight:bold;}";
    echo ".warning{color:orange;font-weight:bold;}";
    echo "</style></head><body>";
    
    echo "<h1>Diagnóstico de Categorias - Menu</h1>";
    echo "<p><strong>Tenant:</strong> {$tenant->name} (ID: {$tenantId})</p>";
    echo "<p><strong>Domínio:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . "</p>";
    echo "<hr>";
    
    // 1. Verificar categorias na tabela categorias
    echo "<h2>1. Categorias Cadastradas</h2>";
    $stmt = $db->prepare("SELECT id, nome, slug, ativo FROM categorias WHERE tenant_id = :tenant_id ORDER BY nome ASC");
    $stmt->execute(['tenant_id' => $tenantId]);
    $categorias = $stmt->fetchAll();
    
    if (empty($categorias)) {
        echo "<p class='error'>❌ Nenhuma categoria cadastrada na tabela 'categorias'</p>";
    } else {
        echo "<p class='success'>✅ Encontradas " . count($categorias) . " categorias</p>";
        echo "<table><tr><th>ID</th><th>Nome</th><th>Slug</th><th>Ativo</th></tr>";
        foreach ($categorias as $cat) {
            $ativo = $cat['ativo'] ? '✅ Sim' : '❌ Não';
            echo "<tr><td>{$cat['id']}</td><td>{$cat['nome']}</td><td>{$cat['slug']}</td><td>{$ativo}</td></tr>";
        }
        echo "</table>";
    }
    
    // 2. Verificar home_category_pills
    echo "<h2>2. Categorias em Destaque (home_category_pills)</h2>";
    $stmt = $db->prepare("
        SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
        FROM home_category_pills hcp
        LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
        WHERE hcp.tenant_id = :tenant_id_where
        ORDER BY hcp.ordem ASC, hcp.id ASC
    ");
    $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
    $stmt->execute();
    $pills = $stmt->fetchAll();
    
    if (empty($pills)) {
        echo "<p class='error'>❌ Nenhuma categoria configurada em 'home_category_pills'</p>";
        echo "<p class='warning'>⚠️ Para aparecer no menu, você precisa cadastrar categorias em: <strong>Admin → Home → Categorias em Destaque</strong></p>";
    } else {
        $pillsAtivas = array_filter($pills, function($p) { return $p['ativo'] == 1; });
        echo "<p class='success'>✅ Encontradas " . count($pills) . " configurações de categorias em destaque</p>";
        echo "<p class='" . (count($pillsAtivas) > 0 ? 'success' : 'error') . "'>";
        echo count($pillsAtivas) > 0 ? "✅ " : "❌ ";
        echo count($pillsAtivas) . " estão ativas (aparecerão no menu)</p>";
        
        echo "<table><tr><th>ID</th><th>Categoria ID</th><th>Nome da Categoria</th><th>Label</th><th>Ativo</th><th>Ordem</th></tr>";
        foreach ($pills as $pill) {
            $ativo = $pill['ativo'] ? '✅ Sim' : '❌ Não';
            $categoriaNome = $pill['categoria_nome'] ?? '<span style="color:red;">Categoria não encontrada (ID: ' . $pill['categoria_id'] . ')</span>';
            echo "<tr>";
            echo "<td>{$pill['id']}</td>";
            echo "<td>{$pill['categoria_id']}</td>";
            echo "<td>{$categoriaNome}</td>";
            echo "<td>{$pill['label']}</td>";
            echo "<td>{$ativo}</td>";
            echo "<td>{$pill['ordem']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Verificar query que o sistema usa
    echo "<h2>3. Query Usada pelo Sistema (apenas ativas)</h2>";
    $stmt = $db->prepare("
        SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
        FROM home_category_pills hcp
        LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
        WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
        ORDER BY hcp.ordem ASC, hcp.id ASC
    ");
    $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
    $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
    $stmt->execute();
    $resultadoFinal = $stmt->fetchAll();
    
    if (empty($resultadoFinal)) {
        echo "<p class='error'>❌ A query do sistema não retornou nenhuma categoria</p>";
        echo "<p><strong>Possíveis causas:</strong></p>";
        echo "<ul>";
        echo "<li>Nenhuma categoria configurada em 'home_category_pills'</li>";
        echo "<li>Todas as categorias estão com 'ativo = 0'</li>";
        echo "<li>O tenant_id não corresponde</li>";
        echo "<li>As categorias referenciadas não existem mais</li>";
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ Query retornou " . count($resultadoFinal) . " categorias (estas aparecerão no menu)</p>";
        echo "<table><tr><th>Label</th><th>Nome da Categoria</th><th>Slug</th><th>Ordem</th></tr>";
        foreach ($resultadoFinal as $cat) {
            echo "<tr>";
            echo "<td>{$cat['label']}</td>";
            echo "<td>{$cat['categoria_nome']}</td>";
            echo "<td>{$cat['categoria_slug']}</td>";
            echo "<td>{$cat['ordem']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Verificar variáveis de ambiente
    echo "<h2>4. Informações do Ambiente</h2>";
    echo "<table><tr><th>Variável</th><th>Valor</th></tr>";
    echo "<tr><td>REQUEST_URI</td><td>" . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>HTTP_HOST</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>SCRIPT_NAME</td><td>" . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</td></tr>";
    echo "<tr><td>PHP_SELF</td><td>" . htmlspecialchars($_SERVER['PHP_SELF'] ?? 'N/A') . "</td></tr>";
    echo "</table>";
    
    echo "<hr>";
    echo "<p><strong>Conclusão:</strong> ";
    if (empty($resultadoFinal)) {
        echo "<span class='error'>As categorias NÃO aparecerão no menu porque não há categorias ativas configuradas.</span>";
        echo "<br><br><strong>Ação necessária:</strong> Acesse o painel admin e configure categorias em <strong>Home → Categorias em Destaque</strong>, garantindo que estejam marcadas como 'Ativo'.</p>";
    } else {
        echo "<span class='success'>As categorias DEVEM aparecer no menu. Se não aparecem, pode ser um problema de cache ou de renderização.</span></p>";
    }
    
    echo "</body></html>";
    
} catch (\Exception $e) {
    die('<h1>Erro: ' . htmlspecialchars($e->getMessage()) . '</h1><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>');
}

