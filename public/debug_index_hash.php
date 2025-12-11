<?php
/**
 * Script temporário para verificar hash do index.php em produção
 * Acesse via: https://pontodogolfeoutlet.com.br/debug_index_hash.php
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Index Hash</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo "pre{background:white;padding:15px;border-radius:4px;border:1px solid #ddd;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>Verificação de index.php em Produção</h1>";

$indexPath = __DIR__ . '/index.php';
echo "<h2>1. Informações do Arquivo</h2>";
echo "<pre>";
echo "Caminho: " . htmlspecialchars($indexPath) . "\n";
echo "Existe: " . (file_exists($indexPath) ? '<span class="success">✅ SIM</span>' : '<span class="error">❌ NÃO</span>') . "\n";

if (file_exists($indexPath)) {
    echo "Tamanho: " . filesize($indexPath) . " bytes\n";
    echo "Última modificação: " . date('Y-m-d H:i:s', filemtime($indexPath)) . "\n";
    echo "Hash MD5: <strong>" . md5_file($indexPath) . "</strong>\n";
    echo "Hash SHA1: " . sha1_file($indexPath) . "\n";
}
echo "</pre>";

if (file_exists($indexPath)) {
    echo "<h2>2. Verificação de Conteúdo</h2>";
    $content = file_get_contents($indexPath);
    
    // Verificar import do CategoriaController
    $temImport = strpos($content, 'use App\Http\Controllers\Admin\CategoriaController;') !== false;
    echo "<p class='" . ($temImport ? 'success' : 'error') . "'>";
    echo $temImport ? "✅" : "❌";
    echo " Import CategoriaController: " . ($temImport ? "ENCONTRADO" : "NÃO ENCONTRADO");
    echo "</p>";
    
    // Verificar rotas
    $rotas = [
        "/admin/categorias'",
        "/admin/categorias\"",
        "/admin/categorias/criar",
        "/admin/categorias/{id}/editar"
    ];
    
    echo "<h3>Rotas Encontradas:</h3>";
    echo "<pre>";
    foreach ($rotas as $rota) {
        $encontrou = strpos($content, $rota) !== false;
        echo ($encontrou ? "✅" : "❌") . " Rota contendo: " . htmlspecialchars($rota) . "\n";
    }
    echo "</pre>";
    
    // Extrair trecho das rotas
    if (preg_match('/\/\/ Rotas Admin - Categorias.*?\/admin\/categorias.*?\]\);/s', $content, $matches)) {
        echo "<h3>Trecho das Rotas:</h3>";
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    } else {
        echo "<p class='error'>❌ Trecho de rotas não encontrado no arquivo</p>";
    }
    
    // Contar linhas
    $linhas = explode("\n", $content);
    echo "<p>Total de linhas no arquivo: " . count($linhas) . "</p>";
    
    // Encontrar linha do import
    foreach ($linhas as $num => $linha) {
        if (strpos($linha, 'CategoriaController') !== false) {
            echo "<p>Linha " . ($num + 1) . " (import): " . htmlspecialchars(trim($linha)) . "</p>";
        }
    }
    
    // Encontrar linhas das rotas
    echo "<h3>Linhas das Rotas:</h3>";
    echo "<pre>";
    foreach ($linhas as $num => $linha) {
        if (preg_match('/\/admin\/categorias/', $linha)) {
            echo "Linha " . ($num + 1) . ": " . htmlspecialchars(trim($linha)) . "\n";
        }
    }
    echo "</pre>";
}

echo "<h2>3. Comparação com Local</h2>";
echo "<p class='info'>ℹ️ Compare o hash MD5 acima com o hash do arquivo local:</p>";
echo "<p>Para obter hash local, execute no terminal:</p>";
echo "<pre>md5sum public/index.php</pre>";
echo "<p>Ou no Windows PowerShell:</p>";
echo "<pre>Get-FileHash public/index.php -Algorithm MD5</pre>";

echo "<h2>4. Informações do Servidor</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "</pre>";

echo "</body></html>";

