<?php
/**
 * Script alternativo para gerar autoloader básico
 * Use apenas se não conseguir instalar o Composer
 * Execute: php generate_autoload.php
 */

$vendorDir = __DIR__ . '/vendor';
$autoloadFile = $vendorDir . '/autoload.php';

// Criar diretório vendor se não existir
if (!is_dir($vendorDir)) {
    mkdir($vendorDir, 0755, true);
}

// Gerar autoloader básico
$autoloadContent = <<<'PHP'
<?php
/**
 * Autoloader básico gerado automaticamente
 * Para usar Composer completo, execute: composer install
 */

spl_autoload_register(function ($class) {
    // Namespace App\
    if (strpos($class, 'App\\') === 0) {
        $class = substr($class, 4); // Remove 'App\'
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
});
PHP;

file_put_contents($autoloadFile, $autoloadContent);

echo "✓ Autoloader básico criado em: {$autoloadFile}\n";
echo "\n";
echo "⚠️  ATENÇÃO: Este é um autoloader básico.\n";
echo "   Para usar o Composer completo (recomendado), instale o Composer e execute:\n";
echo "   composer install\n";
echo "\n";
echo "📚 Veja: docs/INSTALACAO_COMPOSER.md\n";



