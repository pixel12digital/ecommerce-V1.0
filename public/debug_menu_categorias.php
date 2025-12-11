<?php
/**
 * Script de diagnóstico para verificar por que o menu "Categorias" não aparece
 * Acesse via: https://pontodogolfeoutlet.com.br/debug_menu_categorias.php
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\StoreUserService;

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
    
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Menu Categorias</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:20px auto;padding:20px;}";
    echo "table{border-collapse:collapse;width:100%;margin:20px 0;}";
    echo "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
    echo "th{background-color:#4CAF50;color:white;}";
    echo ".success{color:green;font-weight:bold;}";
    echo ".error{color:red;font-weight:bold;}";
    echo ".warning{color:orange;font-weight:bold;}";
    echo ".info{color:blue;font-weight:bold;}";
    echo "pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;}";
    echo "</style></head><body>";
    
    echo "<h1>Diagnóstico: Menu 'Categorias' não aparece</h1>";
    echo "<p><strong>Tenant:</strong> {$tenant->name} (ID: {$tenantId})</p>";
    echo "<p><strong>Domínio:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . "</p>";
    echo "<hr>";
    
    // 1. Verificar layout usado
    echo "<h2>1. Layout Usado</h2>";
    echo "<p class='info'>✅ Todos os controllers usam: <code>admin/layouts/store</code></p>";
    echo "<p class='info'>✅ Arquivo físico: <code>themes/default/admin/layouts/store.php</code></p>";
    echo "<p class='warning'>⚠️ Verifique se este arquivo está atualizado em produção (deve conter o marcador DEBUG-STORE-LAYOUT)</p>";
    
    // 2. Verificar usuários e permissões
    echo "<h2>2. Usuários e Permissões</h2>";
    
    // Buscar todos os usuários do tenant
    $stmt = $db->prepare("SELECT id, nome, email, ativo FROM store_users WHERE tenant_id = :tenant_id ORDER BY id ASC");
    $stmt->execute(['tenant_id' => $tenantId]);
    $usuarios = $stmt->fetchAll();
    
    if (empty($usuarios)) {
        echo "<p class='error'>❌ Nenhum usuário encontrado para este tenant</p>";
    } else {
        echo "<p class='success'>✅ Encontrados " . count($usuarios) . " usuários</p>";
        echo "<table><tr><th>ID</th><th>Nome</th><th>Email</th><th>Ativo</th><th>Permissões</th></tr>";
        
        foreach ($usuarios as $usuario) {
            $ativo = $usuario['ativo'] ? '✅ Sim' : '❌ Não';
            
            // Buscar permissões do usuário
            $stmtPerms = $db->prepare("
                SELECT p.permission_key 
                FROM store_user_permissions sup
                INNER JOIN store_permissions p ON p.id = sup.permission_id
                WHERE sup.user_id = :user_id
            ");
            $stmtPerms->execute(['user_id' => $usuario['id']]);
            $permissoes = $stmtPerms->fetchAll(\PDO::FETCH_COLUMN);
            
            $temManageProducts = in_array('manage_products', $permissoes);
            $permissoesStr = implode(', ', $permissoes);
            if (empty($permissoesStr)) {
                $permissoesStr = '<span style="color:red;">Nenhuma permissão</span>';
            }
            
            $manageProductsStatus = $temManageProducts 
                ? '<span class="success">✅ manage_products</span>' 
                : '<span class="error">❌ SEM manage_products</span>';
            
            echo "<tr>";
            echo "<td>{$usuario['id']}</td>";
            echo "<td>{$usuario['nome']}</td>";
            echo "<td>{$usuario['email']}</td>";
            echo "<td>{$ativo}</td>";
            echo "<td>{$manageProductsStatus}<br><small>{$permissoesStr}</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Verificar permissões disponíveis
    echo "<h2>3. Permissões Disponíveis no Sistema</h2>";
    $stmt = $db->prepare("SELECT id, permission_key, description FROM store_permissions ORDER BY permission_key ASC");
    $stmt->execute();
    $permissoesSistema = $stmt->fetchAll();
    
    if (empty($permissoesSistema)) {
        echo "<p class='error'>❌ Nenhuma permissão cadastrada no sistema</p>";
    } else {
        echo "<p class='success'>✅ Encontradas " . count($permissoesSistema) . " permissões no sistema</p>";
        echo "<table><tr><th>ID</th><th>Chave</th><th>Descrição</th></tr>";
        foreach ($permissoesSistema as $perm) {
            $isManageProducts = $perm['permission_key'] === 'manage_products';
            $rowStyle = $isManageProducts ? 'style="background-color:#e8f5e9;"' : '';
            echo "<tr {$rowStyle}>";
            echo "<td>{$perm['id']}</td>";
            echo "<td><strong>{$perm['permission_key']}</strong></td>";
            echo "<td>{$perm['description']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Verificar código do menu no arquivo
    echo "<h2>4. Código do Menu no Layout</h2>";
    $layoutPath = __DIR__ . '/../themes/default/admin/layouts/store.php';
    if (!file_exists($layoutPath)) {
        echo "<p class='error'>❌ Arquivo não encontrado: {$layoutPath}</p>";
    } else {
        $layoutContent = file_get_contents($layoutPath);
        
        // Verificar se tem o marcador de debug
        $temMarcador = strpos($layoutContent, 'DEBUG-STORE-LAYOUT') !== false;
        echo "<p class='" . ($temMarcador ? 'success' : 'error') . "'>";
        echo $temMarcador ? "✅" : "❌";
        echo " Marcador DEBUG-STORE-LAYOUT: " . ($temMarcador ? "ENCONTRADO" : "NÃO ENCONTRADO");
        echo "</p>";
        
        // Verificar se tem o item Categorias
        $temCategorias = strpos($layoutContent, '<span>Categorias</span>') !== false;
        echo "<p class='" . ($temCategorias ? 'success' : 'error') . "'>";
        echo $temCategorias ? "✅" : "❌";
        echo " Item 'Categorias' no menu: " . ($temCategorias ? "ENCONTRADO" : "NÃO ENCONTRADO");
        echo "</p>";
        
        // Verificar se está dentro do bloco canManageProducts
        $temBloco = preg_match('/if\s*\(\s*\$canManageProducts\s*\)\s*:.*?<span>Categorias<\/span>/s', $layoutContent);
        echo "<p class='" . ($temBloco ? 'success' : 'error') . "'>";
        echo $temBloco ? "✅" : "❌";
        echo " Item 'Categorias' dentro do bloco canManageProducts: " . ($temBloco ? "SIM" : "NÃO");
        echo "</p>";
        
        // Extrair trecho do menu
        if (preg_match('/if\s*\(\s*\$canManageProducts\s*\)\s*:.*?endif\s*;/s', $layoutContent, $matches)) {
            echo "<h3>Trecho do Menu (Produtos/Categorias):</h3>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        }
    }
    
    // 5. Teste de permissão em tempo real
    echo "<h2>5. Teste de Permissão em Tempo Real</h2>";
    session_start();
    $currentUserId = StoreUserService::getCurrentUserId();
    
    if (!$currentUserId) {
        echo "<p class='warning'>⚠️ Nenhum usuário logado no momento</p>";
        echo "<p>Para testar permissões, você precisa estar logado no admin.</p>";
    } else {
        echo "<p class='info'>Usuário logado: ID {$currentUserId}</p>";
        
        $canManageProducts = StoreUserService::can($currentUserId, 'manage_products');
        echo "<p class='" . ($canManageProducts ? 'success' : 'error') . "'>";
        echo $canManageProducts ? "✅" : "❌";
        echo " canManageProducts para usuário {$currentUserId}: " . ($canManageProducts ? "TRUE" : "FALSE");
        echo "</p>";
        
        if (!$canManageProducts) {
            echo "<p class='error'><strong>PROBLEMA IDENTIFICADO:</strong> O usuário logado NÃO tem a permissão 'manage_products'. Por isso o menu 'Categorias' não aparece.</p>";
            echo "<p><strong>Solução:</strong> Adicionar a permissão 'manage_products' para este usuário.</p>";
        }
    }
    
    // 6. Conclusão
    echo "<hr>";
    echo "<h2>6. Conclusão e Próximos Passos</h2>";
    
    $problemas = [];
    if (!$temCategorias) {
        $problemas[] = "Arquivo store.php não contém o item 'Categorias'";
    }
    if (!$currentUserId || !$canManageProducts) {
        $problemas[] = "Usuário logado não tem permissão 'manage_products'";
    }
    
    if (empty($problemas)) {
        echo "<p class='success'>✅ Não foram encontrados problemas óbvios. O menu deve aparecer.</p>";
        echo "<p>Se ainda não aparecer, pode ser:</p>";
        echo "<ul>";
        echo "<li>Cache do navegador (fazer Ctrl+F5)</li>";
        echo "<li>Cache do PHP (OPcache) - reiniciar PHP-FPM</li>";
        echo "<li>Arquivo store.php desatualizado no servidor</li>";
        echo "</ul>";
    } else {
        echo "<p class='error'><strong>Problemas identificados:</strong></p>";
        echo "<ul>";
        foreach ($problemas as $problema) {
            echo "<li class='error'>{$problema}</li>";
        }
        echo "</ul>";
    }
    
    echo "</body></html>";
    
} catch (\Exception $e) {
    die('<h1>Erro: ' . htmlspecialchars($e->getMessage()) . '</h1><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>');
}

