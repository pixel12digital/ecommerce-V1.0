<?php
/**
 * Script de diagnóstico para verificar rota /admin/categorias
 * Acesse via: https://pontodogolfeoutlet.com.br/debug_rota_categorias.php
 */

require __DIR__ . '/../vendor/autoload.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Rota Categorias</title>";
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

echo "<h1>Diagnóstico: Rota /admin/categorias retorna 404</h1>";
echo "<hr>";

// 1. Verificar se o arquivo index.php tem a rota
echo "<h2>1. Verificar Rota em public/index.php</h2>";
$indexPath = __DIR__ . '/index.php';
if (!file_exists($indexPath)) {
    echo "<p class='error'>❌ Arquivo index.php não encontrado: {$indexPath}</p>";
} else {
    $indexContent = file_get_contents($indexPath);
    
    // Verificar se tem o import do CategoriaController
    $temImport = strpos($indexContent, 'use App\Http\Controllers\Admin\CategoriaController;') !== false;
    echo "<p class='" . ($temImport ? 'success' : 'error') . "'>";
    echo $temImport ? "✅" : "❌";
    echo " Import do CategoriaController: " . ($temImport ? "ENCONTRADO" : "NÃO ENCONTRADO");
    echo "</p>";
    
    // Verificar se tem a rota /admin/categorias
    $temRota = strpos($indexContent, "/admin/categorias'") !== false || 
               strpos($indexContent, '/admin/categorias"') !== false ||
               preg_match('/\/admin\/categorias[,\'"]/', $indexContent);
    echo "<p class='" . ($temRota ? 'success' : 'error') . "'>";
    echo $temRota ? "✅" : "❌";
    echo " Rota '/admin/categorias': " . ($temRota ? "ENCONTRADA" : "NÃO ENCONTRADA");
    echo "</p>";
    
    // Mostrar trecho da rota
    if (preg_match('/\/\/ Rotas Admin - Categorias.*?\/admin\/categorias.*?\]\);/s', $indexContent, $matches)) {
        echo "<h3>Trecho da Rota:</h3>";
        echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
    }
}

// 2. Verificar se o controller existe
echo "<h2>2. Verificar Controller CategoriaController</h2>";
$controllerPath = __DIR__ . '/../src/Http/Controllers/Admin/CategoriaController.php';
if (!file_exists($controllerPath)) {
    echo "<p class='error'>❌ Controller não encontrado: {$controllerPath}</p>";
} else {
    echo "<p class='success'>✅ Controller encontrado: {$controllerPath}</p>";
    
    $controllerContent = file_get_contents($controllerPath);
    
    // Verificar se tem o método index
    $temIndex = strpos($controllerContent, 'public function index()') !== false;
    echo "<p class='" . ($temIndex ? 'success' : 'error') . "'>";
    echo $temIndex ? "✅" : "❌";
    echo " Método index(): " . ($temIndex ? "ENCONTRADO" : "NÃO ENCONTRADO");
    echo "</p>";
    
    // Verificar namespace
    $temNamespace = strpos($controllerContent, 'namespace App\Http\Controllers\Admin;') !== false;
    echo "<p class='" . ($temNamespace ? 'success' : 'error') . "'>";
    echo $temNamespace ? "✅" : "❌";
    echo " Namespace correto: " . ($temNamespace ? "SIM" : "NÃO");
    echo "</p>";
}

// 3. Verificar se a view existe
echo "<h2>3. Verificar View admin/categorias/index-content</h2>";
$viewPath = __DIR__ . '/../themes/default/admin/categorias/index-content.php';
if (!file_exists($viewPath)) {
    echo "<p class='error'>❌ View não encontrada: {$viewPath}</p>";
} else {
    echo "<p class='success'>✅ View encontrada: {$viewPath}</p>";
}

// 4. Testar autoload
echo "<h2>4. Testar Autoload do Controller</h2>";
try {
    if (class_exists('App\Http\Controllers\Admin\CategoriaController')) {
        echo "<p class='success'>✅ Classe CategoriaController pode ser carregada via autoload</p>";
        
        $reflection = new ReflectionClass('App\Http\Controllers\Admin\CategoriaController');
        $temIndex = $reflection->hasMethod('index');
        echo "<p class='" . ($temIndex ? 'success' : 'error') . "'>";
        echo $temIndex ? "✅" : "❌";
        echo " Método index() existe na classe: " . ($temIndex ? "SIM" : "NÃO");
        echo "</p>";
    } else {
        echo "<p class='error'>❌ Classe CategoriaController NÃO pode ser carregada via autoload</p>";
    }
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao carregar classe: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 5. Verificar rotas registradas (simular)
echo "<h2>5. Simular Registro de Rotas</h2>";
try {
    // Tentar instanciar o router e verificar se a rota seria registrada
    $router = new \App\Core\Router();
    
    // Verificar se o método get existe
    if (method_exists($router, 'get')) {
        echo "<p class='success'>✅ Router tem método get()</p>";
    } else {
        echo "<p class='error'>❌ Router NÃO tem método get()</p>";
    }
    
    echo "<p class='info'>ℹ️ Para verificar se a rota está realmente registrada, é necessário acessar /admin/categorias e verificar os logs do servidor.</p>";
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao testar Router: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. Verificar .htaccess
echo "<h2>6. Verificar Configuração .htaccess</h2>";
$htaccessPath = __DIR__ . '/.htaccess';
if (!file_exists($htaccessPath)) {
    echo "<p class='warning'>⚠️ Arquivo .htaccess não encontrado: {$htaccessPath}</p>";
    echo "<p>Isso pode ser normal se o servidor não usa .htaccess ou está configurado de outra forma.</p>";
} else {
    echo "<p class='success'>✅ Arquivo .htaccess encontrado</p>";
    $htaccessContent = file_get_contents($htaccessPath);
    
    // Verificar se tem rewrite rule para index.php
    $temRewrite = strpos($htaccessContent, 'RewriteRule') !== false && 
                  strpos($htaccessContent, 'index.php') !== false;
    echo "<p class='" . ($temRewrite ? 'success' : 'warning') . "'>";
    echo $temRewrite ? "✅" : "⚠️";
    echo " RewriteRule para index.php: " . ($temRewrite ? "ENCONTRADA" : "NÃO ENCONTRADA");
    echo "</p>";
}

// 7. Conclusão
echo "<hr>";
echo "<h2>7. Conclusão e Próximos Passos</h2>";

$problemas = [];
if (!$temImport || !$temRota) {
    $problemas[] = "Arquivo public/index.php não contém a rota /admin/categorias ou o import do controller";
}
if (!file_exists($controllerPath)) {
    $problemas[] = "Controller CategoriaController não existe";
}
if (!file_exists($viewPath)) {
    $problemas[] = "View admin/categorias/index-content.php não existe";
}

if (empty($problemas)) {
    echo "<p class='success'>✅ Todos os arquivos necessários estão presentes no código.</p>";
    echo "<p class='warning'><strong>PROBLEMA PROVÁVEL:</strong> O arquivo <code>public/index.php</code> não foi atualizado em produção.</p>";
    echo "<p><strong>Solução:</strong></p>";
    echo "<ol>";
    echo "<li>Fazer deploy do arquivo <code>public/index.php</code> atualizado para produção</li>";
    echo "<li>Fazer deploy do controller <code>src/Http/Controllers/Admin/CategoriaController.php</code></li>";
    echo "<li>Fazer deploy da view <code>themes/default/admin/categorias/index-content.php</code></li>";
    echo "<li>Limpar cache do PHP (OPcache) se houver</li>";
    echo "<li>Testar novamente acessando <code>/admin/categorias</code></li>";
    echo "</ol>";
} else {
    echo "<p class='error'><strong>Problemas identificados:</strong></p>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li class='error'>{$problema}</li>";
    }
    echo "</ul>";
}

echo "</body></html>";

