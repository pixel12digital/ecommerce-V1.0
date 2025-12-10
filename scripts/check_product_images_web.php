<?php
/**
 * Script WEB para verificar imagens de um produto no banco de dados remoto
 * 
 * Acesse via: https://pontodogolfeoutlet.com.br/scripts/check_product_images_web.php?produto=929
 * 
 * Ou via SSH: php scripts/check_product_images_web.php produto=929
 */

// Verificar se está sendo executado via web ou CLI
if (php_sapi_name() === 'cli') {
    // CLI - processar argumentos
    $productId = null;
    $tenantId = 1;
    
    foreach ($argv as $arg) {
        if (strpos($arg, 'produto=') === 0) {
            $productId = (int)explode('=', $arg)[1];
        } elseif (strpos($arg, 'tenant=') === 0) {
            $tenantId = (int)explode('=', $arg)[1];
        }
    }
} else {
    // WEB - processar GET
    $productId = isset($_GET['produto']) ? (int)$_GET['produto'] : null;
    $tenantId = isset($_GET['tenant']) ? (int)$_GET['tenant'] : 1;
    
    // Headers para resposta HTML
    header('Content-Type: text/html; charset=utf-8');
}

if (!$productId) {
    $error = "ERRO: ID do produto não fornecido.";
    if (php_sapi_name() === 'cli') {
        echo $error . "\n";
        echo "Uso: php scripts/check_product_images_web.php produto=929 [tenant=1]\n";
        echo "Ou acesse: https://pontodogolfeoutlet.com.br/scripts/check_product_images_web.php?produto=929\n";
    } else {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Verificar Imagens do Produto</title></head><body>";
        echo "<h1>Verificar Imagens do Produto</h1>";
        echo "<p style='color: red;'>{$error}</p>";
        echo "<p>Use: <code>?produto=929</code> ou <code>?produto=929&tenant=1</code></p>";
        echo "</body></html>";
    }
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configuração do banco
$dbConfig = require __DIR__ . '/../config/database.php';

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
    
    // Buscar informações do produto
    $stmt = $db->prepare("
        SELECT id, nome, slug, imagem_principal, status 
        FROM produtos 
        WHERE id = :id AND tenant_id = :tenant_id
    ");
    $stmt->execute(['id' => $productId, 'tenant_id' => $tenantId]);
    $produto = $stmt->fetch();
    
    if (!$produto) {
        $error = "ERRO: Produto ID {$productId} não encontrado para tenant {$tenantId}.";
        if (php_sapi_name() === 'cli') {
            echo $error . "\n";
        } else {
            echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Erro</title></head><body>";
            echo "<h1>Erro</h1><p style='color: red;'>{$error}</p></body></html>";
        }
        exit(1);
    }
    
    // Buscar todas as imagens do produto
    $stmt = $db->prepare("
        SELECT id, tipo, ordem, caminho_arquivo, mime_type, tamanho_arquivo, created_at
        FROM produto_imagens 
        WHERE tenant_id = :tenant_id AND produto_id = :produto_id
        ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
    ");
    $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $productId]);
    $imagens = $stmt->fetchAll();
    
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
    
    // Verificar as 4 imagens do print
    $expectedImages = [
        'IMG-20251206-WA0050.jpg',
        'IMG-20251206-WA0052.jpg',
        'IMG-20251206-WA0054.jpg',
        'IMG-20251206-WA0055.jpg'
    ];
    
    $foundImages = [];
    foreach ($expectedImages as $filename) {
        foreach ($imagens as $img) {
            if (strpos($img['caminho_arquivo'], $filename) !== false) {
                $foundImages[$filename] = $img;
                break;
            }
        }
    }
    
    // Output
    if (php_sapi_name() === 'cli') {
        // CLI output
        echo "Conectado ao banco: {$dbConfig['host']}/{$database}\n";
        echo str_repeat('=', 80) . "\n\n";
        echo "PRODUTO: {$produto['nome']} (ID: {$produto['id']})\n";
        echo "Imagem Principal (campo produtos.imagem_principal): " . ($produto['imagem_principal'] ?? 'NULL') . "\n";
        echo "TOTAL DE IMAGENS NO BANCO: " . count($imagens) . "\n\n";
        
        if ($imagemPrincipal) {
            echo "📸 IMAGEM PRINCIPAL: {$imagemPrincipal['caminho_arquivo']}\n\n";
        }
        
        echo "🖼️  GALERIA (" . count($galeria) . " imagens):\n";
        foreach ($galeria as $index => $img) {
            echo "  " . ($index + 1) . ". {$img['caminho_arquivo']} (ID: {$img['id']}, Ordem: {$img['ordem']})\n";
        }
        
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "VERIFICAÇÃO DAS 4 IMAGENS DO PRINT:\n";
        foreach ($expectedImages as $filename) {
            if (isset($foundImages[$filename])) {
                echo "✅ {$filename} - ENCONTRADA\n";
            } else {
                echo "❌ {$filename} - NÃO ENCONTRADA\n";
            }
        }
    } else {
        // HTML output
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verificar Imagens - Produto <?= $productId ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                h1 { color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 10px; }
                h2 { color: #555; margin-top: 30px; }
                .info { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 15px 0; }
                .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 5px 0; }
                .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #023A8D; color: white; }
                tr:hover { background: #f5f5f5; }
                .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 0.85em; }
                .badge-success { background: #28a745; color: white; }
                .badge-danger { background: #dc3545; color: white; }
                .badge-info { background: #17a2b8; color: white; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Verificação de Imagens - Produto <?= $productId ?></h1>
                
                <div class="info">
                    <strong>Produto:</strong> <?= htmlspecialchars($produto['nome']) ?><br>
                    <strong>Slug:</strong> <?= htmlspecialchars($produto['slug']) ?><br>
                    <strong>Status:</strong> <?= htmlspecialchars($produto['status']) ?><br>
                    <strong>Imagem Principal (campo produtos.imagem_principal):</strong> 
                    <?= $produto['imagem_principal'] ? htmlspecialchars($produto['imagem_principal']) : '<span style="color: #999;">NULL</span>' ?>
                </div>
                
                <h2>Resumo</h2>
                <div class="info">
                    <strong>Total de imagens no banco:</strong> <?= count($imagens) ?><br>
                    <strong>Imagem principal:</strong> <?= $imagemPrincipal ? '1' : '0' ?><br>
                    <strong>Imagens na galeria:</strong> <?= count($galeria) ?>
                </div>
                
                <?php if ($imagemPrincipal): ?>
                <h2>📸 Imagem Principal</h2>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Caminho</th>
                        <th>Tipo MIME</th>
                        <th>Tamanho</th>
                        <th>Criada em</th>
                    </tr>
                    <tr>
                        <td><?= $imagemPrincipal['id'] ?></td>
                        <td><?= htmlspecialchars($imagemPrincipal['caminho_arquivo']) ?></td>
                        <td><?= htmlspecialchars($imagemPrincipal['mime_type']) ?></td>
                        <td><?= number_format($imagemPrincipal['tamanho_arquivo'] / 1024, 2) ?> KB</td>
                        <td><?= htmlspecialchars($imagemPrincipal['created_at']) ?></td>
                    </tr>
                </table>
                <?php else: ?>
                <div class="error">⚠️ Nenhuma imagem principal encontrada.</div>
                <?php endif; ?>
                
                <h2>🖼️ Galeria (<?= count($galeria) ?> imagens)</h2>
                <?php if (!empty($galeria)): ?>
                <table>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>Ordem</th>
                        <th>Caminho</th>
                        <th>Tipo MIME</th>
                        <th>Tamanho</th>
                        <th>Criada em</th>
                    </tr>
                    <?php foreach ($galeria as $index => $img): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $img['id'] ?></td>
                        <td><?= $img['ordem'] ?></td>
                        <td><?= htmlspecialchars($img['caminho_arquivo']) ?></td>
                        <td><?= htmlspecialchars($img['mime_type']) ?></td>
                        <td><?= number_format($img['tamanho_arquivo'] / 1024, 2) ?> KB</td>
                        <td><?= htmlspecialchars($img['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="error">⚠️ Nenhuma imagem na galeria.</div>
                <?php endif; ?>
                
                <h2>Verificação das 4 Imagens do Print</h2>
                <?php foreach ($expectedImages as $filename): ?>
                    <?php if (isset($foundImages[$filename])): ?>
                        <div class="success">
                            ✅ <strong><?= htmlspecialchars($filename) ?></strong> - ENCONTRADA<br>
                            ID: <?= $foundImages[$filename]['id'] ?> | 
                            Tipo: <?= $foundImages[$filename]['tipo'] ?> | 
                            Ordem: <?= $foundImages[$filename]['ordem'] ?><br>
                            Caminho: <?= htmlspecialchars($foundImages[$filename]['caminho_arquivo']) ?>
                        </div>
                    <?php else: ?>
                        <div class="error">
                            ❌ <strong><?= htmlspecialchars($filename) ?></strong> - NÃO ENCONTRADA
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <p><a href="?produto=<?= $productId ?>&tenant=<?= $tenantId ?>">Atualizar</a> | 
                    <a href="?produto=<?= $productId + 1 ?>">Próximo Produto</a> | 
                    <a href="?produto=<?= $productId - 1 ?>">Produto Anterior</a></p>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
} catch (PDOException $e) {
    $error = "ERRO ao conectar ao banco de dados: " . $e->getMessage();
    if (php_sapi_name() === 'cli') {
        echo $error . "\n";
    } else {
        echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Erro</title></head><body>";
        echo "<h1>Erro</h1><p style='color: red;'>{$error}</p></body></html>";
    }
    exit(1);
}

