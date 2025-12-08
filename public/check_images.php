<?php
/**
 * Script para verificar estrutura de imagens no JSON
 * Acesse: http://localhost/ecommerce-v1.0/public/check_images.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
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

$paths = require __DIR__ . '/../config/paths.php';
$exportPath = $paths['exportacao_produtos_path'];
$jsonFile = $exportPath . '/produtos-completo.json';
$imagesPath = $exportPath . '/images';

echo "<h1>Verificação de Imagens</h1>";
echo "<pre>";

// Verificar arquivo JSON
if (!file_exists($jsonFile)) {
    echo "ERRO: Arquivo JSON não encontrado: {$jsonFile}\n";
    exit;
}

// Verificar pasta de imagens
if (!is_dir($imagesPath)) {
    echo "ERRO: Pasta de imagens não encontrada: {$imagesPath}\n";
    exit;
}

$jsonContent = file_get_contents($jsonFile);
$produtos = json_decode($jsonContent, true);

if (!$produtos) {
    echo "ERRO: Não foi possível decodificar o JSON\n";
    exit;
}

echo "Total de produtos no JSON: " . count($produtos) . "\n\n";

// Contar produtos com imagens
$produtosComImagens = 0;
$totalImagens = 0;
$produtosSemImagens = 0;
$exemplosImagens = [];

foreach ($produtos as $produto) {
    if (isset($produto['images']) && is_array($produto['images']) && !empty($produto['images'])) {
        $produtosComImagens++;
        $totalImagens += count($produto['images']);
        
        // Guardar exemplos
        if (count($exemplosImagens) < 3) {
            $exemplosImagens[] = [
                'produto' => $produto['name'] ?? 'N/A',
                'images' => $produto['images']
            ];
        }
    } else {
        $produtosSemImagens++;
    }
}

echo "1. Produtos COM imagens: {$produtosComImagens}\n";
echo "2. Produtos SEM imagens: {$produtosSemImagens}\n";
echo "3. Total de imagens no JSON: {$totalImagens}\n\n";

// Verificar arquivos na pasta images
$arquivosImages = glob($imagesPath . '/*');
$totalArquivos = count($arquivosImages);
echo "4. Arquivos na pasta images/: {$totalArquivos}\n\n";

// Exemplos de estrutura de imagens
if (!empty($exemplosImagens)) {
    echo "5. Exemplos de estrutura de imagens:\n";
    foreach ($exemplosImagens as $exemplo) {
        echo "\n   Produto: " . htmlspecialchars($exemplo['produto']) . "\n";
        foreach ($exemplo['images'] as $idx => $img) {
            echo "   Imagem " . ($idx + 1) . ":\n";
            echo "     src: " . ($img['src'] ?? 'N/A') . "\n";
            $urlParts = parse_url($img['src'] ?? '');
            $fileName = basename($urlParts['path'] ?? '');
            echo "     nome arquivo extraído: {$fileName}\n";
            $sourceFile = $imagesPath . '/' . $fileName;
            echo "     arquivo existe? " . (file_exists($sourceFile) ? 'SIM' : 'NÃO') . "\n";
            if ($idx >= 2) break; // Limitar exemplos
        }
    }
}

// Verificar alguns arquivos da pasta
echo "\n6. Primeiros 5 arquivos na pasta images/:\n";
$arquivos = array_slice($arquivosImages, 0, 5);
foreach ($arquivos as $arquivo) {
    echo "   " . basename($arquivo) . "\n";
}

echo "</pre>";
echo "<p><a href='/ecommerce-v1.0/public/'>← Voltar</a></p>";



