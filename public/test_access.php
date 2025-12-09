<?php
/**
 * Arquivo de teste para verificar acesso e configuração
 * Acesse: https://pontodogolfeoutlet.com.br/test_access.php
 */

echo "<h1>Teste de Acesso - E-commerce</h1>";
echo "<hr>";

echo "<h2>1. Informações do Servidor</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "</pre>";

echo "<h2>2. Verificação de Arquivos</h2>";
echo "<pre>";
$rootPath = dirname(__DIR__);
echo "Raiz do projeto: {$rootPath}\n";
echo "Arquivo .env existe: " . (file_exists($rootPath . '/.env') ? 'SIM' : 'NÃO') . "\n";
echo "Arquivo public/index.php existe: " . (file_exists(__DIR__ . '/index.php') ? 'SIM' : 'NÃO') . "\n";
echo "Arquivo public/.htaccess existe: " . (file_exists(__DIR__ . '/.htaccess') ? 'SIM' : 'NÃO') . "\n";
echo "Pasta vendor existe: " . (is_dir($rootPath . '/vendor') ? 'SIM' : 'NÃO') . "\n";
echo "</pre>";

echo "<h2>3. Verificação de Permissões</h2>";
echo "<pre>";
echo "Permissões de public/: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
echo "Permissões de public/index.php: " . substr(sprintf('%o', fileperms(__DIR__ . '/index.php')), -4) . "\n";
echo "Permissões de public/.htaccess: " . (file_exists(__DIR__ . '/.htaccess') ? substr(sprintf('%o', fileperms(__DIR__ . '/.htaccess')), -4) : 'NÃO EXISTE') . "\n";
echo "</pre>";

echo "<h2>4. Teste de Mod_Rewrite</h2>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "<pre>";
    echo "mod_rewrite habilitado: " . (in_array('mod_rewrite', $modules) ? 'SIM' : 'NÃO') . "\n";
    echo "</pre>";
} else {
    echo "<p>Não foi possível verificar módulos do Apache (função apache_get_modules não disponível)</p>";
}

echo "<h2>5. Teste de Autoloader</h2>";
$autoloadPath = $rootPath . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    echo "<p style='color: green;'>✅ Autoloader carregado com sucesso!</p>";
} else {
    echo "<p style='color: red;'>❌ Autoloader não encontrado. Execute: composer install</p>";
}

echo "<h2>6. Teste de Conexão com Banco</h2>";
if (file_exists($rootPath . '/.env')) {
    // Carregar .env
    $envFile = $rootPath . '/.env';
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
    
    $config = require $rootPath . '/config/database.php';
    echo "<pre>";
    echo "DB Host: " . $config['host'] . "\n";
    echo "DB Name: " . $config['name'] . "\n";
    echo "DB User: " . $config['user'] . "\n";
    echo "</pre>";
    
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        echo "<p style='color: green;'>✅ Conexão com banco de dados OK!</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erro ao conectar: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Arquivo .env não encontrado!</p>";
}

echo "<hr>";
echo "<p><strong>Se todos os testes passaram, o problema pode ser no .htaccess ou nas permissões do Apache.</strong></p>";
echo "<p><a href='/'>Tentar acessar a home</a> | <a href='/admin/login'>Tentar acessar admin</a></p>";

