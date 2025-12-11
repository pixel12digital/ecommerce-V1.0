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

// 5. Simular carregamento do index.php e verificar rotas
echo "<h2>5. Simular Registro de Rotas (Carregar index.php)</h2>";
try {
    // Capturar output do index.php
    ob_start();
    
    // Simular variáveis de ambiente
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/categorias';
    
    // Tentar carregar o index.php de forma segura
    $indexPath = __DIR__ . '/index.php';
    if (file_exists($indexPath)) {
        // Ler o conteúdo e extrair apenas as rotas
        $indexContent = file_get_contents($indexPath);
        
        // Verificar se tem a linha de registro da rota
        $linhas = explode("\n", $indexContent);
        $encontrouRota = false;
        $linhaNumero = 0;
        
        foreach ($linhas as $num => $linha) {
            if (preg_match('/\$router->get\([\'"]\/admin\/categorias[\'"]/', $linha)) {
                $encontrouRota = true;
                $linhaNumero = $num + 1;
                echo "<p class='success'>✅ Rota encontrada na linha {$linhaNumero}:</p>";
                echo "<pre>" . htmlspecialchars(trim($linha)) . "</pre>";
                break;
            }
        }
        
        if (!$encontrouRota) {
            echo "<p class='error'>❌ Rota '/admin/categorias' NÃO encontrada no index.php</p>";
            echo "<p class='warning'>⚠️ Isso significa que o arquivo index.php em produção está desatualizado!</p>";
        }
        
        // Verificar se o import está presente
        $temImport = false;
        foreach ($linhas as $linha) {
            if (strpos($linha, 'use App\Http\Controllers\Admin\CategoriaController;') !== false) {
                $temImport = true;
                echo "<p class='success'>✅ Import do CategoriaController encontrado</p>";
                break;
            }
        }
        
        if (!$temImport) {
            echo "<p class='error'>❌ Import do CategoriaController NÃO encontrado no index.php</p>";
        }
    }
    
    ob_end_clean();
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao analisar index.php: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 6. Testar se o Router consegue processar a rota
echo "<h2>6. Testar Router com Rota /admin/categorias</h2>";
try {
    // Inicializar tenant context
    $config = require __DIR__ . '/../config/app.php';
    $mode = $config['mode'] ?? 'single';
    
    if ($mode === 'single') {
        $defaultTenantId = $config['default_tenant_id'] ?? 1;
        \App\Tenant\TenantContext::setFixedTenant($defaultTenantId);
    } else {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        \App\Tenant\TenantContext::resolveFromHost($host);
    }
    
    // Criar router e registrar rota manualmente para teste
    $router = new \App\Core\Router();
    
    // Verificar se consegue registrar
    try {
        $router->get('/admin/categorias', 'App\Http\Controllers\Admin\CategoriaController@index', []);
        echo "<p class='success'>✅ Router consegue registrar a rota manualmente</p>";
    } catch (\Exception $e) {
        echo "<p class='error'>❌ Erro ao registrar rota: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Tentar fazer dispatch (mas sem executar middlewares/auth)
    echo "<p class='info'>ℹ️ Para testar o dispatch completo, seria necessário autenticação. Mas o Router está funcionando.</p>";
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao testar Router: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// 7. Verificar .htaccess
echo "<h2>7. Verificar Configuração .htaccess</h2>";
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

// 8. Verificar se há diferença entre local e produção
echo "<h2>8. Comparar index.php Local vs Produção</h2>";
echo "<p class='info'>ℹ️ Para verificar se há diferença, compare o conteúdo do index.php:</p>";
echo "<ul>";
echo "<li><strong>Local:</strong> O arquivo deve ter a rota na linha ~191</li>";
echo "<li><strong>Produção:</strong> Verifique se o arquivo no servidor tem a mesma rota</li>";
echo "</ul>";
echo "<p class='warning'>⚠️ <strong>PROBLEMA MAIS PROVÁVEL:</strong> O arquivo <code>public/index.php</code> em produção está desatualizado e não contém as rotas de categorias.</p>";

// 9. Conclusão
echo "<hr>";
echo "<h2>9. Conclusão e Próximos Passos</h2>";

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

