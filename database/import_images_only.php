<?php
/**
 * Script para importar apenas as imagens dos produtos já existentes
 * Use quando os produtos já foram importados mas as imagens não
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;
use App\Tenant\TenantContext;

// Configurações
$paths = require __DIR__ . '/../config/paths.php';
$exportPath = $paths['exportacao_produtos_path'];
$uploadsBasePath = $paths['uploads_produtos_base_path'];
$jsonFile = $exportPath . '/produtos-completo.json';
$imagesSourcePath = $exportPath . '/images';

// Verificar se o arquivo JSON existe
if (!file_exists($jsonFile)) {
    echo "ERRO: Arquivo não encontrado: {$jsonFile}\n";
    exit(1);
}

// Resolver tenant
$appMode = $_ENV['APP_MODE'] ?? 'single';
$defaultTenantId = (int)($_ENV['DEFAULT_TENANT_ID'] ?? 1);
$tenantId = $defaultTenantId;

try {
    TenantContext::setFixedTenant($tenantId);
    $tenant = TenantContext::tenant();
    echo "Importando imagens para tenant: {$tenant->name} (ID: {$tenant->id})\n\n";
} catch (\Exception $e) {
    echo "ERRO ao resolver tenant: {$e->getMessage()}\n";
    exit(1);
}

$db = Database::getConnection();

// Criar diretório de uploads se não existir
$uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
if (!is_dir($uploadsPath)) {
    mkdir($uploadsPath, 0755, true);
    echo "Diretório de uploads criado: {$uploadsPath}\n";
}

// Função auxiliar para copiar imagem
function copyImage($source, $dest) {
    if (!file_exists($source)) {
        return false;
    }
    
    $destDir = dirname($dest);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    return copy($source, $dest);
}

// Ler JSON
echo "Lendo arquivo JSON...\n";
$jsonContent = file_get_contents($jsonFile);
$produtos = json_decode($jsonContent, true);

if (!$produtos) {
    echo "ERRO: Não foi possível decodificar o JSON\n";
    exit(1);
}

$totalProdutos = count($produtos);
echo "Total de produtos encontrados no JSON: {$totalProdutos}\n\n";

// Criar mapa de produtos (id_original_wp => id_local)
$stmt = $db->prepare("SELECT id, id_original_wp FROM produtos WHERE tenant_id = :tenant_id");
$stmt->execute(['tenant_id' => $tenantId]);
$produtosDb = $stmt->fetchAll();
$produtosMap = [];
foreach ($produtosDb as $p) {
    $produtosMap[$p['id_original_wp']] = $p['id'];
}

echo "Produtos encontrados no banco: " . count($produtosMap) . "\n\n";

// Processar imagens
echo "Importando imagens...\n";
$imagensProcessadas = 0;
$imagensCopiadas = 0;
$imagensRegistradas = 0;
$produtosComImagens = 0;
$erros = 0;

foreach ($produtos as $index => $produto) {
    $progress = $index + 1;
    $wpId = $produto['id'] ?? null;
    
    if (!isset($produtosMap[$wpId])) {
        continue; // Produto não existe no banco
    }
    
    $produtoId = $produtosMap[$wpId];
    
    // Verificar se já tem imagens
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id");
    $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
    $temImagens = (int)$stmt->fetch()['total'] > 0;
    
    if ($temImagens) {
        continue; // Já tem imagens, pular
    }
    
    echo "\rProcessando produto {$progress}/{$totalProdutos} - ID WP: {$wpId}";
    
    try {
        $db->beginTransaction();
        
        $imagemPrincipal = null;
        $ordemImagem = 0;
        $produtoTemImagem = false;
        
        // Processar imagem principal (images.main)
        if (isset($produto['images']['main']) && is_array($produto['images']['main'])) {
            $img = $produto['images']['main'];
            
            // Usar local_path se disponível
            if (isset($img['local_path']) && !empty($img['local_path'])) {
                $localPath = $img['local_path'];
                $fileName = str_replace('images/', '', $localPath);
                $sourceFile = $imagesSourcePath . '/' . $fileName;
            } else {
                // Fallback: extrair da URL
                $urlParts = parse_url($img['src'] ?? '');
                $fileName = basename($urlParts['path'] ?? '');
                $sourceFile = $imagesSourcePath . '/' . $fileName;
            }
            
            if (!empty($fileName) && file_exists($sourceFile)) {
                $relativePath = "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
                $destFile = $uploadsPath . '/' . $fileName;
                
                $copied = copyImage($sourceFile, $destFile);
                
                if ($copied || file_exists($destFile)) {
                    $fileSize = file_exists($destFile) ? filesize($destFile) : null;
                    $mimeType = null;
                    if (file_exists($destFile)) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $destFile);
                        finfo_close($finfo);
                    }
                    
                    $stmt = $db->prepare("
                        INSERT INTO produto_imagens (
                            tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                            url_original, alt_text, titulo, legenda, mime_type, tamanho_arquivo
                        ) VALUES (
                            :tenant_id, :produto_id, :tipo, :ordem, :caminho_arquivo,
                            :url_original, :alt_text, :titulo, :legenda, :mime_type, :tamanho_arquivo
                        )
                    ");
                    
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId,
                        'tipo' => 'main',
                        'ordem' => $ordemImagem++,
                        'caminho_arquivo' => $relativePath,
                        'url_original' => $img['src'] ?? null,
                        'alt_text' => $img['alt'] ?? null,
                        'titulo' => $img['title'] ?? null,
                        'legenda' => null,
                        'mime_type' => $mimeType,
                        'tamanho_arquivo' => $fileSize
                    ]);
                    
                    $imagemPrincipal = $relativePath;
                    $imagensRegistradas++;
                    $imagensCopiadas++;
                    $produtoTemImagem = true;
                }
            }
        }
        
        // Processar imagens de galeria (images.gallery)
        if (isset($produto['images']['gallery']) && is_array($produto['images']['gallery'])) {
            foreach ($produto['images']['gallery'] as $img) {
                if (!is_array($img)) {
                    continue;
                }
                
                // Usar local_path se disponível
                if (isset($img['local_path']) && !empty($img['local_path'])) {
                    $localPath = $img['local_path'];
                    $fileName = str_replace('images/', '', $localPath);
                    $sourceFile = $imagesSourcePath . '/' . $fileName;
                } else {
                    $urlParts = parse_url($img['src'] ?? '');
                    $fileName = basename($urlParts['path'] ?? '');
                    $sourceFile = $imagesSourcePath . '/' . $fileName;
                }
                
                if (!empty($fileName) && file_exists($sourceFile)) {
                    $relativePath = "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
                    $destFile = $uploadsPath . '/' . $fileName;
                    
                    $copied = copyImage($sourceFile, $destFile);
                    
                    if ($copied || file_exists($destFile)) {
                        $fileSize = file_exists($destFile) ? filesize($destFile) : null;
                        $mimeType = null;
                        if (file_exists($destFile)) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $destFile);
                            finfo_close($finfo);
                        }
                        
                        $stmt = $db->prepare("
                            INSERT INTO produto_imagens (
                                tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                                url_original, alt_text, titulo, legenda, mime_type, tamanho_arquivo
                            ) VALUES (
                                :tenant_id, :produto_id, :tipo, :ordem, :caminho_arquivo,
                                :url_original, :alt_text, :titulo, :legenda, :mime_type, :tamanho_arquivo
                            )
                        ");
                        
                        $stmt->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'tipo' => 'gallery',
                            'ordem' => $ordemImagem++,
                            'caminho_arquivo' => $relativePath,
                            'url_original' => $img['src'] ?? null,
                            'alt_text' => $img['alt'] ?? null,
                            'titulo' => $img['title'] ?? null,
                            'legenda' => null,
                            'mime_type' => $mimeType,
                            'tamanho_arquivo' => $fileSize
                        ]);
                        
                        $imagensRegistradas++;
                        $imagensCopiadas++;
                        $produtoTemImagem = true;
                    }
                }
            }
        }
        
        // Atualizar imagem principal do produto
        if ($imagemPrincipal) {
            $stmt = $db->prepare("UPDATE produtos SET imagem_principal = :imagem WHERE id = :id");
            $stmt->execute(['imagem' => $imagemPrincipal, 'id' => $produtoId]);
        }
        
        if ($produtoTemImagem) {
            $produtosComImagens++;
        }
        
        $imagensProcessadas++;
        $db->commit();
    } catch (\Exception $e) {
        $db->rollBack();
        $erros++;
        echo "\nERRO no produto ID WP {$wpId}: {$e->getMessage()}\n";
    }
}

echo "\n\n";
echo "============================================================\n";
echo "IMPORTAÇÃO DE IMAGENS CONCLUÍDA!\n";
echo "============================================================\n\n";
echo "Resumo:\n";
echo "  Produtos processados: {$imagensProcessadas}\n";
echo "  Produtos com imagens: {$produtosComImagens}\n";
echo "  Imagens copiadas: {$imagensCopiadas}\n";
echo "  Imagens registradas: {$imagensRegistradas}\n";
echo "  Erros: {$erros}\n\n";

// Verificar totais
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id");
$stmt->execute(['tenant_id' => $tenantId]);
$totalImagens = $stmt->fetch()['total'];

$stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE tenant_id = :tenant_id AND imagem_principal IS NOT NULL AND imagem_principal != ''");
$stmt->execute(['tenant_id' => $tenantId]);
$produtosComImagemPrincipal = $stmt->fetch()['total'];

echo "Total de imagens no banco após importação: {$totalImagens}\n";
echo "Total de produtos com imagem_principal: {$produtosComImagemPrincipal}\n";
echo "============================================================\n";



