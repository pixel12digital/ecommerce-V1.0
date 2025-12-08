<?php
/**
 * Script de teste para verificar se as rotas estão sendo processadas corretamente
 * Acesse: http://localhost/ecommerce-v1.0/public/test_route.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste de Rotas</h1>";

echo "<h2>Variáveis do Servidor</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "</pre>";

echo "<h2>Teste de URI Parse</h2>";
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$parsed = parse_url($uri, PHP_URL_PATH);
echo "<pre>";
echo "URI original: {$uri}\n";
echo "URI parseada: {$parsed}\n";
echo "</pre>";

echo "<h2>Teste de .htaccess</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "✓ Arquivo .htaccess existe<br>";
    echo "<pre>" . htmlspecialchars(file_get_contents(__DIR__ . '/.htaccess')) . "</pre>";
} else {
    echo "✗ Arquivo .htaccess não encontrado<br>";
}

echo "<h2>Links de Teste</h2>";
echo "<ul>";
echo "<li><a href='/ecommerce-v1.0/public/'>Home</a></li>";
echo "<li><a href='/ecommerce-v1.0/public/index.php/admin/login'>Login via index.php</a></li>";
echo "<li><a href='/ecommerce-v1.0/public/admin/login'>Login via rota</a></li>";
echo "<li><a href='/ecommerce-v1.0/public/test.php'>Test.php</a></li>";
echo "</ul>";



