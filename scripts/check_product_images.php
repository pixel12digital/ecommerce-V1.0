<?php
/**
 * Script para verificar imagens de um produto no banco de dados remoto
 * 
 * Uso:
 *   php scripts/check_product_images.php 929
 *   php scripts/check_product_images.php 929 --tenant 1
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração do banco
$dbConfig = require __DIR__ . '/../config/database.php';

$productId = isset($argv[1]) ? (int)$argv[1] : null;
$tenantId = isset($argv[2]) && strpos($argv[2], '--tenant') === 0 
    ? (int)explode('=', $argv[2])[1] 
    : 1;

if (!$productId) {
    echo "ERRO: ID do produto não fornecido.\n";
    echo "Uso: php scripts/check_product_images.php [PRODUTO_ID] [--tenant=ID]\n";
    echo "Exemplo: php scripts/check_product_images.php 929\n";
    echo "Exemplo: php scripts/check_product_images.php 929 --tenant=1\n";
    exit(1);
}

try {
    // Conectar ao banco
    $database = $dbConfig['name'] ?? $dbConfig['database'] ?? 'ecommerce_db';
    $dsn = "mysql:host={$dbConfig['host']};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $db = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    echo "Conectado ao banco de dados: {$dbConfig['host']}/{$database}\n";
    echo str_repeat('=', 80) . "\n\n";
    
    // Buscar informações do produto
    $stmt = $db->prepare("
        SELECT id, nome, slug, imagem_principal, status 
        FROM produtos 
        WHERE id = :id AND tenant_id = :tenant_id
    ");
    $stmt->execute(['id' => $productId, 'tenant_id' => $tenantId]);
    $produto = $stmt->fetch();
    
    if (!$produto) {
        echo "ERRO: Produto ID {$productId} não encontrado para tenant {$tenantId}.\n";
        exit(1);
    }
    
    echo "PRODUTO: {$produto['nome']} (ID: {$produto['id']})\n";
    echo "Slug: {$produto['slug']}\n";
    echo "Status: {$produto['status']}\n";
    echo "Imagem Principal (campo produtos.imagem_principal): " . ($produto['imagem_principal'] ?? 'NULL') . "\n";
    echo str_repeat('-', 80) . "\n\n";
    
    // Buscar todas as imagens do produto
    $stmt = $db->prepare("
        SELECT id, tipo, ordem, caminho_arquivo, mime_type, tamanho_arquivo, created_at
        FROM produto_imagens 
        WHERE tenant_id = :tenant_id AND produto_id = :produto_id
        ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
    ");
    $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $productId]);
    $imagens = $stmt->fetchAll();
    
    echo "TOTAL DE IMAGENS NO BANCO: " . count($imagens) . "\n\n";
    
    if (empty($imagens)) {
        echo "⚠️  Nenhuma imagem encontrada na tabela produto_imagens.\n";
    } else {
        // Separar por tipo
        $imagemPrincipal = null;
        $galeria = [];
        
        foreach ($imagens as $img) {
            if ($img['tipo'] === 'main') {
                $imagemPrincipal = $img;
            } else {
                $galeria[] = $img;
            }
        }
        
        // Mostrar imagem principal
        if ($imagemPrincipal) {
            echo "📸 IMAGEM PRINCIPAL:\n";
            echo "  ID: {$imagemPrincipal['id']}\n";
            echo "  Caminho: {$imagemPrincipal['caminho_arquivo']}\n";
            echo "  Tipo MIME: {$imagemPrincipal['mime_type']}\n";
            echo "  Tamanho: " . number_format($imagemPrincipal['tamanho_arquivo'] / 1024, 2) . " KB\n";
            echo "  Criada em: {$imagemPrincipal['created_at']}\n";
            echo "\n";
        } else {
            echo "⚠️  Nenhuma imagem principal encontrada.\n\n";
        }
        
        // Mostrar galeria
        if (!empty($galeria)) {
            echo "🖼️  GALERIA (" . count($galeria) . " imagens):\n";
            foreach ($galeria as $index => $img) {
                echo "  " . ($index + 1) . ". ID: {$img['id']} | Ordem: {$img['ordem']}\n";
                echo "     Caminho: {$img['caminho_arquivo']}\n";
                echo "     Tipo MIME: {$img['mime_type']}\n";
                echo "     Tamanho: " . number_format($img['tamanho_arquivo'] / 1024, 2) . " KB\n";
                echo "     Criada em: {$img['created_at']}\n";
                echo "\n";
            }
        } else {
            echo "⚠️  Nenhuma imagem na galeria.\n\n";
        }
        
        // Verificar se as 4 imagens do print estão no banco
        $expectedImages = [
            'IMG-20251206-WA0050.jpg',
            'IMG-20251206-WA0052.jpg',
            'IMG-20251206-WA0054.jpg',
            'IMG-20251206-WA0055.jpg'
        ];
        
        echo str_repeat('=', 80) . "\n";
        echo "VERIFICAÇÃO DAS 4 IMAGENS DO PRINT:\n";
        echo str_repeat('=', 80) . "\n";
        
        foreach ($expectedImages as $filename) {
            $found = false;
            foreach ($imagens as $img) {
                if (strpos($img['caminho_arquivo'], $filename) !== false) {
                    $found = true;
                    echo "✅ {$filename} - ENCONTRADA\n";
                    echo "   ID: {$img['id']} | Tipo: {$img['tipo']} | Ordem: {$img['ordem']}\n";
                    echo "   Caminho completo: {$img['caminho_arquivo']}\n";
                    break;
                }
            }
            if (!$found) {
                echo "❌ {$filename} - NÃO ENCONTRADA\n";
            }
        }
    }
    
    // Verificar se há imagens duplicadas
    $stmt = $db->prepare("
        SELECT caminho_arquivo, COUNT(*) as count
        FROM produto_imagens 
        WHERE tenant_id = :tenant_id AND produto_id = :produto_id
        GROUP BY caminho_arquivo
        HAVING count > 1
    ");
    $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $productId]);
    $duplicatas = $stmt->fetchAll();
    
    if (!empty($duplicatas)) {
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "⚠️  IMAGENS DUPLICADAS ENCONTRADAS:\n";
        foreach ($duplicatas as $dup) {
            echo "  - {$dup['caminho_arquivo']} (aparece {$dup['count']} vezes)\n";
        }
    }
    
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "Consulta concluída.\n";
    
} catch (PDOException $e) {
    echo "ERRO ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

