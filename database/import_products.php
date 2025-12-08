<?php

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
    echo "Certifique-se de que a pasta exportacao-produtos/ existe na raiz do projeto.\n";
    exit(1);
}

// Resolver tenant
$appMode = $_ENV['APP_MODE'] ?? 'single';
$defaultTenantId = (int)($_ENV['DEFAULT_TENANT_ID'] ?? 1);

// Verificar parâmetro --tenant na linha de comando
$tenantId = $defaultTenantId;
foreach ($argv as $arg) {
    if (strpos($arg, '--tenant=') === 0) {
        $tenantId = (int)substr($arg, 9);
        break;
    }
}

try {
    TenantContext::setFixedTenant($tenantId);
    $tenant = TenantContext::tenant();
    echo "Importando para tenant: {$tenant->name} (ID: {$tenant->id})\n\n";
} catch (\Exception $e) {
    echo "ERRO ao resolver tenant: {$e->getMessage()}\n";
    exit(1);
}

$db = Database::getConnection();

// Verificar se já existem produtos para este tenant
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE tenant_id = :tenant_id");
$stmt->execute(['tenant_id' => $tenantId]);
$produtosExistentes = (int)$stmt->fetch()['total'];

if ($produtosExistentes > 0) {
    echo "⚠️  ATENÇÃO: Já existem {$produtosExistentes} produtos no tenant '{$tenant->name}' (ID: {$tenant->id}).\n";
    echo "   Se você já importou antes, não é necessário rodar novamente.\n";
    echo "   O script irá pular produtos já existentes (verificando por id_original_wp).\n";
    echo "   Continuando mesmo assim...\n\n";
}

// Ler JSON
echo "Lendo arquivo JSON...\n";
$jsonContent = file_get_contents($jsonFile);
$produtos = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "ERRO ao decodificar JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

$totalProdutos = count($produtos);
echo "Total de produtos encontrados no JSON: {$totalProdutos}\n\n";

// Mapas para relacionamentos
$categoriasMap = []; // wp_id => local_id
$tagsMap = []; // wp_id => local_id
$produtosMap = []; // wp_id => local_id

// Coletar todas as categorias e tags únicas
echo "Coletando categorias e tags...\n";
$categoriasUnicas = [];
$tagsUnicas = [];

foreach ($produtos as $produto) {
    // Categorias
    if (isset($produto['categories']) && is_array($produto['categories'])) {
        foreach ($produto['categories'] as $cat) {
            if (isset($cat['id'])) {
                $categoriasUnicas[$cat['id']] = $cat;
            }
        }
    }
    
    // Tags
    if (isset($produto['tags']) && is_array($produto['tags'])) {
        foreach ($produto['tags'] as $tag) {
            if (isset($tag['id'])) {
                $tagsUnicas[$tag['id']] = $tag;
            }
        }
    }
}

echo "Categorias únicas encontradas: " . count($categoriasUnicas) . "\n";
echo "Tags únicas encontradas: " . count($tagsUnicas) . "\n\n";

// Importar categorias
echo "Importando categorias...\n";
$categoriasInseridas = 0;
$categoriasExistentes = 0;

$db->beginTransaction();
try {
    foreach ($categoriasUnicas as $wpId => $cat) {
        // Verificar se já existe
        $stmt = $db->prepare("SELECT id FROM categorias WHERE tenant_id = :tenant_id AND id_original_wp = :wp_id LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'wp_id' => $wpId]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $categoriasMap[$wpId] = $existente['id'];
            $categoriasExistentes++;
            continue;
        }
        
        // Verificar por slug também
        $stmt = $db->prepare("SELECT id FROM categorias WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'slug' => $cat['slug'] ?? '']);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $categoriasMap[$wpId] = $existente['id'];
            $categoriasExistentes++;
            continue;
        }
        
        // Inserir categoria
        $stmt = $db->prepare("
            INSERT INTO categorias (tenant_id, id_original_wp, nome, slug, descricao, categoria_pai_id)
            VALUES (:tenant_id, :wp_id, :nome, :slug, :descricao, NULL)
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'wp_id' => $wpId,
            'nome' => $cat['name'] ?? '',
            'slug' => $cat['slug'] ?? '',
            'descricao' => $cat['description'] ?? null
        ]);
        
        $categoriasMap[$wpId] = $db->lastInsertId();
        $categoriasInseridas++;
    }
    
    // Ajustar categorias pai (segundo passo)
    foreach ($categoriasUnicas as $wpId => $cat) {
        if (isset($cat['parent']) && $cat['parent'] > 0 && isset($categoriasMap[$cat['parent']])) {
            $stmt = $db->prepare("
                UPDATE categorias 
                SET categoria_pai_id = :pai_id 
                WHERE id = :id
            ");
            $stmt->execute([
                'pai_id' => $categoriasMap[$cat['parent']],
                'id' => $categoriasMap[$wpId]
            ]);
        }
    }
    
    $db->commit();
    echo "✓ Categorias processadas: " . count($categoriasMap) . " (inseridas: {$categoriasInseridas}, já existiam: {$categoriasExistentes})\n\n";
} catch (\Exception $e) {
    $db->rollBack();
    echo "ERRO ao importar categorias: {$e->getMessage()}\n";
    exit(1);
}

// Importar tags
echo "Importando tags...\n";
$tagsInseridas = 0;
$tagsExistentes = 0;

$db->beginTransaction();
try {
    foreach ($tagsUnicas as $wpId => $tag) {
        // Verificar se já existe
        $stmt = $db->prepare("SELECT id FROM tags WHERE tenant_id = :tenant_id AND id_original_wp = :wp_id LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'wp_id' => $wpId]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $tagsMap[$wpId] = $existente['id'];
            $tagsExistentes++;
            continue;
        }
        
        // Verificar por slug
        $stmt = $db->prepare("SELECT id FROM tags WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'slug' => $tag['slug'] ?? '']);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $tagsMap[$wpId] = $existente['id'];
            $tagsExistentes++;
            continue;
        }
        
        // Inserir tag
        $stmt = $db->prepare("
            INSERT INTO tags (tenant_id, id_original_wp, nome, slug)
            VALUES (:tenant_id, :wp_id, :nome, :slug)
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'wp_id' => $wpId,
            'nome' => $tag['name'] ?? '',
            'slug' => $tag['slug'] ?? ''
        ]);
        
        $tagsMap[$wpId] = $db->lastInsertId();
        $tagsInseridas++;
    }
    
    $db->commit();
    echo "✓ Tags processadas: " . count($tagsMap) . " (inseridas: {$tagsInseridas}, já existiam: {$tagsExistentes})\n\n";
} catch (\Exception $e) {
    $db->rollBack();
    echo "ERRO ao importar tags: {$e->getMessage()}\n";
    exit(1);
}

// Criar diretório de uploads se não existir
$uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
if (!is_dir($uploadsPath)) {
    mkdir($uploadsPath, 0755, true);
    echo "Diretório de uploads criado: {$uploadsPath}\n";
}

// Função auxiliar para converter valor numérico seguro
function safeNumeric($value) {
    if ($value === null || $value === '' || $value === false) {
        return null;
    }
    $num = is_numeric($value) ? (float)$value : null;
    return $num !== null && $num > 0 ? $num : null;
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

// Importar produtos
echo "Importando produtos...\n";
$importados = 0;
$pulados = 0;
$erros = 0;

foreach ($produtos as $index => $produto) {
    $progress = $index + 1;
    echo "\rProcessando produto {$progress}/{$totalProdutos} - ID WP: " . ($produto['id'] ?? 'N/A');
    
    try {
        $db->beginTransaction();
        
        // Verificar se produto já existe
        $stmt = $db->prepare("SELECT id FROM produtos WHERE tenant_id = :tenant_id AND id_original_wp = :wp_id LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'wp_id' => $produto['id']]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            $produtosMap[$produto['id']] = $existente['id'];
            $pulados++;
            $db->rollBack();
            continue;
        }
        
        // Ajustar slug se duplicado
        $slug = $produto['slug'] ?? '';
        $slugOriginal = $slug;
        $suffix = 1;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM produtos WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
            $stmt->execute(['tenant_id' => $tenantId, 'slug' => $slug]);
            if (!$stmt->fetch()) {
                break;
            }
            $slug = $slugOriginal . '-' . $suffix;
            $suffix++;
        }
        
        // Preparar dados do produto
        $dadosProduto = [
            'tenant_id' => $tenantId,
            'id_original_wp' => $produto['id'],
            'nome' => $produto['name'] ?? '',
            'slug' => $slug,
            'sku' => $produto['sku'] ?? null,
            'tipo' => $produto['type'] ?? 'simple',
            'status' => $produto['status'] ?? 'publish',
            'preco' => safeNumeric($produto['price']) ?? 0.00,
            'preco_regular' => safeNumeric($produto['regular_price']) ?? 0.00,
            'preco_promocional' => safeNumeric($produto['sale_price']),
            'data_promocao_inicio' => $produto['date_on_sale_from'] ?? null,
            'data_promocao_fim' => $produto['date_on_sale_to'] ?? null,
            'gerencia_estoque' => ($produto['manage_stock'] ?? false) ? 1 : 0,
            'quantidade_estoque' => (int)($produto['stock_quantity'] ?? 0),
            'status_estoque' => $produto['stock_status'] ?? 'instock',
            'permite_pedidos_falta' => $produto['backorders'] ?? 'no',
            'peso' => safeNumeric($produto['weight']),
            'comprimento' => safeNumeric($produto['length']),
            'largura' => safeNumeric($produto['width']),
            'altura' => safeNumeric($produto['height']),
            'descricao' => $produto['description'] ?? null,
            'descricao_curta' => $produto['short_description'] ?? null,
            'imagem_principal' => null, // Será preenchido depois
            'destaque' => ($produto['featured'] ?? false) ? 1 : 0,
            'visibilidade_catalogo' => $produto['catalog_visibility'] ?? 'visible',
            'status_imposto' => $produto['tax_status'] ?? 'taxable',
            'data_criacao' => $produto['date_created'] ?? date('Y-m-d H:i:s'),
            'data_modificacao' => $produto['date_modified'] ?? null
        ];
        
        // Inserir produto
        $stmt = $db->prepare("
            INSERT INTO produtos (
                tenant_id, id_original_wp, nome, slug, sku, tipo, status,
                preco, preco_regular, preco_promocional, data_promocao_inicio, data_promocao_fim,
                gerencia_estoque, quantidade_estoque, status_estoque, permite_pedidos_falta,
                peso, comprimento, largura, altura,
                descricao, descricao_curta, imagem_principal,
                destaque, visibilidade_catalogo, status_imposto,
                data_criacao, data_modificacao
            ) VALUES (
                :tenant_id, :id_original_wp, :nome, :slug, :sku, :tipo, :status,
                :preco, :preco_regular, :preco_promocional, :data_promocao_inicio, :data_promocao_fim,
                :gerencia_estoque, :quantidade_estoque, :status_estoque, :permite_pedidos_falta,
                :peso, :comprimento, :largura, :altura,
                :descricao, :descricao_curta, :imagem_principal,
                :destaque, :visibilidade_catalogo, :status_imposto,
                :data_criacao, :data_modificacao
            )
        ");
        $stmt->execute($dadosProduto);
        
        $produtoId = $db->lastInsertId();
        $produtosMap[$produto['id']] = $produtoId;
        
        // Processar imagens
        // Estrutura: images.main (objeto) e images.gallery (array)
        $imagemPrincipal = null;
        $ordemImagem = 0;
        
        // Processar imagem principal (images.main)
        if (isset($produto['images']['main']) && is_array($produto['images']['main'])) {
            $img = $produto['images']['main'];
            
            // Usar local_path se disponível, senão extrair da URL
            if (isset($img['local_path']) && !empty($img['local_path'])) {
                // local_path é relativo à pasta images/, ex: "images/main_13873_xxx.jpg"
                $localPath = $img['local_path'];
                // Remover prefixo "images/" se existir
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
                
                // Copiar arquivo
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
                        'titulo' => $img['name'] ?? null,
                        'legenda' => null,
                        'mime_type' => $mimeType,
                        'tamanho_arquivo' => $fileSize
                    ]);
                    
                    $imagemPrincipal = $relativePath;
                }
            }
        }
        
        // Processar imagens de galeria (images.gallery)
        if (isset($produto['images']['gallery']) && is_array($produto['images']['gallery'])) {
            foreach ($produto['images']['gallery'] as $img) {
                if (!is_array($img)) {
                    continue;
                }
                
                // Usar local_path se disponível, senão extrair da URL
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
                    
                    // Copiar arquivo
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
                            'titulo' => $img['name'] ?? null,
                            'legenda' => null,
                            'mime_type' => $mimeType,
                            'tamanho_arquivo' => $fileSize
                        ]);
                    }
                }
            }
        }
        
        // Atualizar imagem principal do produto
        if ($imagemPrincipal) {
            $stmt = $db->prepare("UPDATE produtos SET imagem_principal = :imagem WHERE id = :id");
            $stmt->execute(['imagem' => $imagemPrincipal, 'id' => $produtoId]);
        }
        
        // Relacionar categorias
        if (isset($produto['categories']) && is_array($produto['categories'])) {
            foreach ($produto['categories'] as $cat) {
                if (isset($cat['id']) && isset($categoriasMap[$cat['id']])) {
                    try {
                        $stmt = $db->prepare("
                            INSERT IGNORE INTO produto_categorias (tenant_id, produto_id, categoria_id)
                            VALUES (:tenant_id, :produto_id, :categoria_id)
                        ");
                        $stmt->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'categoria_id' => $categoriasMap[$cat['id']]
                        ]);
                    } catch (\Exception $e) {
                        // Ignorar duplicatas
                    }
                }
            }
        }
        
        // Relacionar tags
        if (isset($produto['tags']) && is_array($produto['tags'])) {
            foreach ($produto['tags'] as $tag) {
                if (isset($tag['id']) && isset($tagsMap[$tag['id']])) {
                    try {
                        $stmt = $db->prepare("
                            INSERT IGNORE INTO produto_tags (tenant_id, produto_id, tag_id)
                            VALUES (:tenant_id, :produto_id, :tag_id)
                        ");
                        $stmt->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'tag_id' => $tagsMap[$tag['id']]
                        ]);
                    } catch (\Exception $e) {
                        // Ignorar duplicatas
                    }
                }
            }
        }
        
        // Importar metadados
        if (isset($produto['custom_meta']) && is_array($produto['custom_meta'])) {
            foreach ($produto['custom_meta'] as $key => $value) {
                $valorFinal = is_array($value) || is_object($value) ? json_encode($value) : $value;
                
                $stmt = $db->prepare("
                    INSERT INTO produto_meta (tenant_id, produto_id, chave, valor)
                    VALUES (:tenant_id, :produto_id, :chave, :valor)
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId,
                    'chave' => $key,
                    'valor' => $valorFinal
                ]);
            }
        }
        
        $db->commit();
        $importados++;
        
    } catch (\Exception $e) {
        $db->rollBack();
        $erros++;
        echo "\nERRO ao importar produto ID WP {$produto['id']}: {$e->getMessage()}\n";
    }
}

echo "\n\n";
echo str_repeat("=", 60) . "\n";
echo "IMPORTAÇÃO CONCLUÍDA!\n";
echo str_repeat("=", 60) . "\n";
echo "\nResumo:\n";
echo "  Produtos processados: {$totalProdutos}\n";
echo "    ✓ Inseridos: {$importados}\n";
echo "    ⊘ Pulados (já existiam): {$pulados}\n";
echo "    ✗ Erros: {$erros}\n";
echo "\n  Categorias: " . count($categoriasMap);
if (isset($categoriasInseridas) && isset($categoriasExistentes)) {
    echo " (inseridas: {$categoriasInseridas}, já existiam: {$categoriasExistentes})";
}
echo "\n";
echo "  Tags: " . count($tagsMap);
if (isset($tagsInseridas) && isset($tagsExistentes)) {
    echo " (inseridas: {$tagsInseridas}, já existiam: {$tagsExistentes})";
}
echo "\n";

// Verificar total final no banco
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produtos WHERE tenant_id = :tenant_id");
$stmt->execute(['tenant_id' => $tenantId]);
$totalFinal = (int)$stmt->fetch()['total'];

echo "\n  Total de produtos no tenant após importação: {$totalFinal}\n";
echo str_repeat("=", 60) . "\n";

if ($pulados > 0) {
    echo "\n💡 Dica: {$pulados} produtos foram pulados porque já existiam.\n";
    echo "   Isso é normal se você já executou a importação antes.\n";
    echo "   O script evita duplicatas verificando por id_original_wp.\n";
}

