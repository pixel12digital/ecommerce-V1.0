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
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "</p>";
echo "<p><strong>SCRIPT_NAME:</strong> " . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</p>";
echo "<p><strong>DOCUMENT_ROOT:</strong> " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "</p>";
echo "<hr>";

    // 0. Informações do index.php
    echo "<h2>0. Informações do index.php</h2>";
    $indexPath = __DIR__ . '/index.php';
    if (file_exists($indexPath)) {
        echo "<p class='success'>✅ Arquivo encontrado: {$indexPath}</p>";
        echo "<p><strong>Hash MD5:</strong> " . md5_file($indexPath) . "</p>";
        echo "<p><strong>Última modificação:</strong> " . date('Y-m-d H:i:s', filemtime($indexPath)) . "</p>";
        echo "<p><strong>Tamanho:</strong> " . filesize($indexPath) . " bytes</p>";
    } else {
        echo "<p class='error'>❌ Arquivo não encontrado: {$indexPath}</p>";
    }
    echo "<hr>";
    
    // 1. Verificar se o arquivo index.php tem a rota
    echo "<h2>1. Verificar Rota em public/index.php</h2>";
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

// 6. Simular Router e verificar rotas registradas (carregando index.php completo)
echo "<h2>6. Simular Router e Verificar Rotas Registradas</h2>";
echo "<p class='info'>ℹ️ Tentando carregar o Router do index.php e verificar rotas registradas...</p>";

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
    
    // Criar router
    $router = new \App\Core\Router();
    
    // Tentar carregar e executar apenas a parte de registro de rotas do index.php
    // Isso é complexo, então vamos simular manualmente algumas rotas principais
    echo "<h3>6.1. Teste de Registro Manual</h3>";
    try {
        $router->get('/admin/categorias', 'App\Http\Controllers\Admin\CategoriaController@index', []);
        echo "<p class='success'>✅ Router consegue registrar a rota manualmente</p>";
    } catch (\Exception $e) {
        echo "<p class='error'>❌ Erro ao registrar rota: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Verificar se método getRoutes existe
    echo "<h3>6.2. Listar Rotas Registradas</h3>";
    if (method_exists($router, 'getRoutes')) {
        $rotas = $router->getRoutes();
        echo "<p class='info'>Total de rotas registradas no teste manual: " . count($rotas) . "</p>";
        
        // Filtrar rotas de categorias
        $rotasCategorias = array_filter($rotas, function($rota) {
            return strpos($rota['path'], 'categorias') !== false;
        });
        
        if (!empty($rotasCategorias)) {
            echo "<h4>Rotas de Categorias no Router:</h4>";
            echo "<pre>";
            foreach ($rotasCategorias as $rota) {
                echo "Método: {$rota['method']}, Path: {$rota['path']}\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='warning'>⚠️ Nenhuma rota de categorias encontrada no Router (esperado, pois só registramos uma manualmente)</p>";
        }
        
        // Listar todas as rotas GET para referência
        $rotasGET = array_filter($rotas, function($rota) {
            return $rota['method'] === 'GET';
        });
        if (count($rotasGET) > 0) {
            echo "<h4>Exemplo de Rotas GET Registradas (teste manual):</h4>";
            echo "<pre>";
            foreach (array_slice($rotasGET, 0, 10) as $rota) {
                echo "GET {$rota['path']}\n";
            }
            if (count($rotasGET) > 10) {
                echo "... (total: " . count($rotasGET) . " rotas GET)\n";
            }
            echo "</pre>";
        }
    } else {
        echo "<p class='warning'>⚠️ Método getRoutes() não disponível no Router</p>";
    }
    
    // Tentar simular dispatch (sem executar middlewares)
    echo "<h3>6.3. Teste de Matching de Rota</h3>";
    echo "<p class='info'>ℹ️ Testando se o Router consegue fazer match da URI '/admin/categorias'...</p>";
    
    // Criar um router novo para teste de matching
    $routerTest = new \App\Core\Router();
    $routerTest->get('/admin/categorias', 'App\Http\Controllers\Admin\CategoriaController@index', []);
    
    // Usar reflection para acessar método privado parseUri
    $reflection = new ReflectionClass($routerTest);
    $parseUriMethod = $reflection->getMethod('parseUri');
    $parseUriMethod->setAccessible(true);
    
    $uriTest = '/admin/categorias';
    $uriParsed = $parseUriMethod->invoke($routerTest, $uriTest);
    echo "<p class='info'>URI original: <code>{$uriTest}</code></p>";
    echo "<p class='info'>URI após parseUri: <code>{$uriParsed}</code></p>";
    
    // Verificar regex pattern
    $pathToRegexMethod = $reflection->getMethod('pathToRegex');
    $pathToRegexMethod->setAccessible(true);
    $pattern = $pathToRegexMethod->invoke($routerTest, '/admin/categorias');
    echo "<p class='info'>Pattern regex gerado: <code>" . htmlspecialchars($pattern) . "</code></p>";
    
    // Testar match
    $match = preg_match($pattern, $uriParsed);
    if ($match) {
        echo "<p class='success'>✅ Pattern faz match com a URI processada!</p>";
    } else {
        echo "<p class='error'>❌ Pattern NÃO faz match com a URI processada!</p>";
        echo "<p class='warning'>⚠️ Isso pode indicar problema no matching do Router</p>";
    }
    
    echo "<p class='info'>ℹ️ <strong>Nota:</strong> O dispatch completo requer autenticação e middlewares. Este teste verifica apenas o matching básico.</p>";
    
} catch (\Exception $e) {
    echo "<p class='error'>❌ Erro ao testar Router: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// 7. Verificar logs de erro do PHP
echo "<h2>7. Verificar Logs de Erro (Últimas Entradas)</h2>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    echo "<p class='info'>Arquivo de log: {$logFile}</p>";
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $logLines = array_filter($logLines, function($line) {
        return strpos($line, 'DEBUG') !== false || strpos($line, 'categorias') !== false;
    });
    $logLines = array_slice($logLines, -20); // Últimas 20 linhas
    
    if (!empty($logLines)) {
        echo "<h3>Últimas entradas de log relacionadas:</h3>";
        echo "<pre>" . htmlspecialchars(implode("\n", $logLines)) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Nenhuma entrada de log DEBUG encontrada. Verifique se error_log está configurado.</p>";
        echo "<p class='info'>ℹ️ Para ver logs em tempo real, acesse <code>/admin/produtos</code> e <code>/admin/categorias</code> e depois verifique os logs novamente.</p>";
    }
} else {
    echo "<p class='warning'>⚠️ Arquivo de log não encontrado ou não configurado.</p>";
    echo "<p>Verifique a configuração de error_log no PHP.</p>";
    echo "<p class='info'>ℹ️ No painel Hostinger: Avançado → Logs de erro</p>";
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

// 8. Verificar processamento de URI
echo "<h2>8. Verificar Processamento de URI</h2>";
echo "<p class='info'>ℹ️ Simulando o processamento de URI que acontece no index.php...</p>";

$uriOriginal = '/admin/categorias';
$uri = $uriOriginal;

// Simular processamento do index.php
$uri = parse_url($uri, PHP_URL_PATH);
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$scriptDir = rtrim($scriptDir, '/');

if ($scriptDir !== '' && $scriptDir !== '/') {
    if (strpos($uri, $scriptDir) === 0) {
        $uri = substr($uri, strlen($scriptDir));
    }
}

if (strpos($uri, '/ecommerce-v1.0/public') === 0) {
    $uri = substr($uri, strlen('/ecommerce-v1.0/public'));
} elseif (strpos($uri, '/public') === 0 && $uri !== '/public' && $uri !== '/public/') {
    $uri = substr($uri, strlen('/public'));
}

$uri = rtrim($uri, '/') ?: '/';

echo "<p><strong>URI Original:</strong> <code>{$uriOriginal}</code></p>";
echo "<p><strong>SCRIPT_NAME:</strong> <code>" . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "</code></p>";
echo "<p><strong>scriptDir calculado:</strong> <code>{$scriptDir}</code></p>";
echo "<p><strong>URI após processamento:</strong> <code>{$uri}</code></p>";

if ($uri === '/admin/categorias') {
    echo "<p class='success'>✅ URI processada corretamente: <code>{$uri}</code></p>";
} else {
    echo "<p class='error'>❌ URI processada incorretamente! Esperado: <code>/admin/categorias</code>, Obtido: <code>{$uri}</code></p>";
    echo "<p class='warning'>⚠️ <strong>PROBLEMA IDENTIFICADO:</strong> O processamento de URI está modificando incorretamente a rota!</p>";
}

// 9. Conclusão e Diagnóstico
echo "<hr>";
echo "<h2>9. Conclusão e Diagnóstico</h2>";

$problemas = [];
$warnings = [];

// Verificar arquivos
if (!$temImport || !$temRota) {
    $problemas[] = "Arquivo public/index.php não contém a rota /admin/categorias ou o import do controller";
} else {
    echo "<p class='success'>✅ Arquivo <code>public/index.php</code> contém rotas de categorias</p>";
}

if (!file_exists($controllerPath)) {
    $problemas[] = "Controller CategoriaController não existe";
} else {
    echo "<p class='success'>✅ Controller existe e pode ser carregado</p>";
}

if (!file_exists($viewPath)) {
    $problemas[] = "View admin/categorias/index-content.php não existe";
} else {
    echo "<p class='success'>✅ View existe</p>";
}

// Verificar URI processada
if (isset($uri) && $uri !== '/admin/categorias') {
    $warnings[] = "URI processada diferente do esperado: <code>{$uri}</code> (esperado: <code>/admin/categorias</code>)";
}

if (empty($problemas)) {
    echo "<p class='success'>✅ <strong>Todos os arquivos necessários estão presentes.</strong></p>";
    
    if (!empty($warnings)) {
        echo "<p class='warning'><strong>⚠️ AVISOS:</strong></p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li class='warning'>{$warning}</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li><strong>Verificar logs do PHP</strong> ao acessar <code>/admin/categorias</code> em produção</li>";
    echo "<li><strong>Comparar logs</strong> entre <code>/admin/produtos</code> (funciona) e <code>/admin/categorias</code> (404)</li>";
    echo "<li><strong>Verificar se a rota está na lista</strong> do log <code>[DEBUG ROUTER] Rotas GET registradas</code></li>";
    echo "<li>Se a rota estiver na lista mas ainda retornar 404, o problema é no <strong>matching do Router</strong></li>";
    echo "<li>Se a rota NÃO estiver na lista, verificar se o código de registro está sendo executado</li>";
    echo "</ol>";
} else {
    echo "<p class='error'><strong>❌ Problemas identificados:</strong></p>";
    echo "<ul>";
    foreach ($problemas as $problema) {
        echo "<li class='error'>{$problema}</li>";
    }
    echo "</ul>";
    
    if (!empty($warnings)) {
        echo "<p class='warning'><strong>⚠️ AVISOS:</strong></p>";
        echo "<ul>";
        foreach ($warnings as $warning) {
            echo "<li class='warning'>{$warning}</li>";
        }
        echo "</ul>";
    }
}

echo "</body></html>";

