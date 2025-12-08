<?php
/**
 * Script para identificar todas as categorias existentes nos produtos
 * Acesse via: http://localhost/ecommerce-v1.0/public/listar_categorias_produtos.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Tenant\TenantContext;

// Inicializar contexto do tenant (assumindo que está no contexto de um tenant)
// Se não estiver, você pode especificar manualmente ou listar todos os tenants

try {
    $db = Database::getConnection();
    
    // Verificar se há tenant no contexto
    $tenantId = null;
    try {
        $tenantId = TenantContext::id();
        $tenant = TenantContext::tenant();
        echo "<h1>Categorias dos Produtos - Tenant: " . htmlspecialchars($tenant->name ?? 'N/A') . "</h1>";
    } catch (\Exception $e) {
        // Se não houver tenant no contexto, listar todos os tenants
        echo "<h1>Categorias dos Produtos - Todos os Tenants</h1>";
    }
    
    // Query para buscar todas as categorias que estão associadas a produtos
    if ($tenantId) {
        // Filtrar por tenant específico
        $sql = "
            SELECT 
                c.id,
                c.nome,
                c.slug,
                c.descricao,
                c.categoria_pai_id,
                COUNT(DISTINCT pc.produto_id) as total_produtos,
                COUNT(DISTINCT CASE WHEN p.status = 'publish' THEN p.id END) as produtos_publicados,
                GROUP_CONCAT(DISTINCT hcp.label ORDER BY hcp.ordem SEPARATOR ', ') as labels_home
            FROM categorias c
            INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = :tenant_id
            INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = :tenant_id
            LEFT JOIN home_category_pills hcp ON hcp.categoria_id = c.id AND hcp.tenant_id = :tenant_id
            WHERE c.tenant_id = :tenant_id
            GROUP BY c.id, c.nome, c.slug, c.descricao, c.categoria_pai_id
            ORDER BY total_produtos DESC, c.nome ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(['tenant_id' => $tenantId]);
    } else {
        // Listar todos os tenants
        $sql = "
            SELECT 
                t.id as tenant_id,
                t.name as tenant_name,
                c.id as categoria_id,
                c.nome,
                c.slug,
                c.descricao,
                c.categoria_pai_id,
                COUNT(DISTINCT pc.produto_id) as total_produtos,
                COUNT(DISTINCT CASE WHEN p.status = 'publish' THEN p.id END) as produtos_publicados,
                GROUP_CONCAT(DISTINCT hcp.label ORDER BY hcp.ordem SEPARATOR ', ') as labels_home
            FROM tenants t
            INNER JOIN categorias c ON c.tenant_id = t.id
            INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = t.id
            INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = t.id
            LEFT JOIN home_category_pills hcp ON hcp.categoria_id = c.id AND hcp.tenant_id = t.id
            GROUP BY t.id, t.name, c.id, c.nome, c.slug, c.descricao, c.categoria_pai_id
            ORDER BY t.name ASC, total_produtos DESC, c.nome ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
    }
    
    // Buscar também categorias configuradas na home (mesmo sem produtos)
    if ($tenantId) {
        $sqlHome = "
            SELECT 
                hcp.id as pill_id,
                hcp.label,
                hcp.icone_path,
                hcp.ordem,
                hcp.ativo,
                c.id as categoria_id,
                c.nome as categoria_nome,
                c.slug as categoria_slug
            FROM home_category_pills hcp
            LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id
            WHERE hcp.tenant_id = :tenant_id AND hcp.ativo = 1
            ORDER BY hcp.ordem ASC, hcp.id ASC
        ";
        $stmtHome = $db->prepare($sqlHome);
        $stmtHome->execute(['tenant_id' => $tenantId]);
    } else {
        $sqlHome = "
            SELECT 
                t.id as tenant_id,
                t.name as tenant_name,
                hcp.id as pill_id,
                hcp.label,
                hcp.icone_path,
                hcp.ordem,
                hcp.ativo,
                c.id as categoria_id,
                c.nome as categoria_nome,
                c.slug as categoria_slug
            FROM tenants t
            INNER JOIN home_category_pills hcp ON hcp.tenant_id = t.id
            LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = t.id
            WHERE hcp.ativo = 1
            ORDER BY t.name ASC, hcp.ordem ASC, hcp.id ASC
        ";
        $stmtHome = $db->prepare($sqlHome);
        $stmtHome->execute();
    }
    $categoriasHome = $stmtHome->fetchAll(\PDO::FETCH_ASSOC);
    
    $categorias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($categorias)) {
        echo "<p style='color: #666;'>Nenhuma categoria encontrada associada a produtos.</p>";
        exit;
    }
    
    // Estatísticas gerais
    $totalCategorias = count($categorias);
    $totalProdutos = array_sum(array_column($categorias, 'total_produtos'));
    $totalProdutosPublicados = array_sum(array_column($categorias, 'produtos_publicados'));
    
    // Verificar produtos sem categoria
    if ($tenantId) {
        $sqlSemCategoria = "
            SELECT 
                COUNT(*) as total_sem_categoria,
                COUNT(CASE WHEN status = 'publish' THEN 1 END) as publicados_sem_categoria
            FROM produtos p
            WHERE p.tenant_id = :tenant_id
            AND NOT EXISTS (
                SELECT 1 FROM produto_categorias pc 
                WHERE pc.produto_id = p.id AND pc.tenant_id = :tenant_id
            )
        ";
        $stmtSemCategoria = $db->prepare($sqlSemCategoria);
        $stmtSemCategoria->execute(['tenant_id' => $tenantId]);
    } else {
        $sqlSemCategoria = "
            SELECT 
                t.id as tenant_id,
                t.name as tenant_name,
                COUNT(*) as total_sem_categoria,
                COUNT(CASE WHEN p.status = 'publish' THEN 1 END) as publicados_sem_categoria
            FROM tenants t
            INNER JOIN produtos p ON p.tenant_id = t.id
            WHERE NOT EXISTS (
                SELECT 1 FROM produto_categorias pc 
                WHERE pc.produto_id = p.id AND pc.tenant_id = t.id
            )
            GROUP BY t.id, t.name
        ";
        $stmtSemCategoria = $db->prepare($sqlSemCategoria);
        $stmtSemCategoria->execute();
    }
    $produtosSemCategoria = $stmtSemCategoria->fetchAll(\PDO::FETCH_ASSOC);
    
    echo "<div style='background: #f0f0f0; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;'>";
    echo "<h2>Estatísticas Gerais</h2>";
    echo "<p><strong>Total de Categorias com Produtos:</strong> {$totalCategorias}</p>";
    echo "<p><strong>Total de Produtos (todas as categorias):</strong> {$totalProdutos}</p>";
    echo "<p><strong>Total de Produtos Publicados:</strong> {$totalProdutosPublicados}</p>";
    
    // Mostrar produtos sem categoria
    if ($tenantId) {
        $semCategoria = $produtosSemCategoria[0] ?? null;
        if ($semCategoria && $semCategoria['total_sem_categoria'] > 0) {
            echo "<p style='color: #d32f2f; margin-top: 1rem;'><strong>⚠️ Produtos SEM Categoria:</strong> {$semCategoria['total_sem_categoria']} (Publicados: {$semCategoria['publicados_sem_categoria']})</p>";
        } else {
            echo "<p style='color: #2e7d32; margin-top: 1rem;'><strong>✅ Todos os produtos têm categoria</strong></p>";
        }
    } else {
        $totalSemCategoria = array_sum(array_column($produtosSemCategoria, 'total_sem_categoria'));
        if ($totalSemCategoria > 0) {
            echo "<p style='color: #d32f2f; margin-top: 1rem;'><strong>⚠️ Total de Produtos SEM Categoria (todos os tenants):</strong> {$totalSemCategoria}</p>";
        } else {
            echo "<p style='color: #2e7d32; margin-top: 1rem;'><strong>✅ Todos os produtos têm categoria</strong></p>";
        }
    }
    echo "</div>";
    
    // Agrupar por tenant se não houver tenant específico
    if (!$tenantId) {
        $categoriasPorTenant = [];
        foreach ($categorias as $cat) {
            $tenantKey = $cat['tenant_id'];
            if (!isset($categoriasPorTenant[$tenantKey])) {
                $categoriasPorTenant[$tenantKey] = [
                    'tenant_name' => $cat['tenant_name'],
                    'categorias' => []
                ];
            }
            $categoriasPorTenant[$tenantKey]['categorias'][] = $cat;
        }
        
        foreach ($categoriasPorTenant as $tenantKey => $data) {
            echo "<h2 style='margin-top: 2rem; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;'>";
            echo "Tenant: " . htmlspecialchars($data['tenant_name']);
            echo "</h2>";
            
            echo "<table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>";
            echo "<thead>";
            echo "<tr style='background: #023A8D; color: white;'>";
            echo "<th style='padding: 0.75rem; text-align: left;'>ID</th>";
            echo "<th style='padding: 0.75rem; text-align: left;'>Nome</th>";
            echo "<th style='padding: 0.75rem; text-align: left;'>Slug</th>";
            echo "<th style='padding: 0.75rem; text-align: left;'>Descrição</th>";
            echo "<th style='padding: 0.75rem; text-align: center;'>Total Produtos</th>";
            echo "<th style='padding: 0.75rem; text-align: center;'>Publicados</th>";
            echo "<th style='padding: 0.75rem; text-align: left;'>Categoria Pai</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($data['categorias'] as $cat) {
                echo "<tr style='border-bottom: 1px solid #ddd;'>";
                echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($cat['categoria_id']) . "</td>";
                echo "<td style='padding: 0.75rem;'><strong>" . htmlspecialchars($cat['nome']) . "</strong></td>";
                echo "<td style='padding: 0.75rem;'><code>" . htmlspecialchars($cat['slug']) . "</code></td>";
                echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($cat['descricao'] ?: '-') . "</td>";
                echo "<td style='padding: 0.75rem; text-align: center;'><strong>" . $cat['total_produtos'] . "</strong></td>";
                echo "<td style='padding: 0.75rem; text-align: center;'>" . $cat['produtos_publicados'] . "</td>";
                echo "<td style='padding: 0.75rem;'>" . ($cat['categoria_pai_id'] ? 'ID: ' . $cat['categoria_pai_id'] : '-') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            
            // Mostrar categorias da home para este tenant (se houver)
            $categoriasHomeTenant = array_filter($categoriasHome, function($pill) use ($tenantKey) {
                return isset($pill['tenant_id']) && $pill['tenant_id'] == $tenantKey;
            });
            
            if (!empty($categoriasHomeTenant)) {
                $semCategoriaCount = 0;
                foreach ($categoriasHomeTenant as $pill) {
                    $labelExibido = $pill['label'] ?: $pill['categoria_nome'] ?: '';
                    if (strtolower($labelExibido) === 'array' || empty($pill['categoria_id'])) {
                        $semCategoriaCount++;
                    }
                }
                
                echo "<h3 style='margin-top: 2rem; border-bottom: 2px solid #F7931E; padding-bottom: 0.5rem;'>Categorias Configuradas na Home (Pills) - " . htmlspecialchars($data['tenant_name']) . "</h3>";
                if ($semCategoriaCount > 0) {
                    echo "<p style='color: #d32f2f; background: #ffebee; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;'><strong>⚠️ Atenção:</strong> {$semCategoriaCount} categoria(s) configurada(s) na home estão SEM categoria associada (mostram 'Array').</p>";
                }
                echo "<table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>";
                echo "<thead>";
                echo "<tr style='background: #F7931E; color: white;'>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Ordem</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Label (exibido)</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Categoria</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Slug</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Ícone</th>";
                echo "<th style='padding: 0.75rem; text-align: center;'>Status</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                foreach ($categoriasHomeTenant as $pill) {
                    $labelExibido = $pill['label'] ?: $pill['categoria_nome'] ?: '(sem nome)';
                    $semCategoria = (strtolower($labelExibido) === 'array' || empty($pill['categoria_id']));
                    $corLinha = $semCategoria ? 'background: #ffebee;' : '';
                    echo "<tr style='border-bottom: 1px solid #ddd; {$corLinha}'>";
                    echo "<td style='padding: 0.75rem;'>" . $pill['ordem'] . "</td>";
                    echo "<td style='padding: 0.75rem;'><strong>" . htmlspecialchars($labelExibido) . "</strong>";
                    if ($semCategoria) {
                        echo " <span style='color: #d32f2f; font-weight: bold;'>⚠️ SEM CATEGORIA</span>";
                    } elseif ($pill['label'] && $pill['label'] !== $pill['categoria_nome']) {
                        echo " <small style='color: #666;'>(label customizado)</small>";
                    }
                    echo "</td>";
                    echo "<td style='padding: 0.75rem;'>" . ($pill['categoria_nome'] ? htmlspecialchars($pill['categoria_nome']) : '<span style="color: #d32f2f; font-weight: bold;">⚠️ SEM CATEGORIA</span>') . "</td>";
                    echo "<td style='padding: 0.75rem;'><code>" . htmlspecialchars($pill['categoria_slug'] ?: '-') . "</code></td>";
                    echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($pill['icone_path'] ?: '-') . "</td>";
                    echo "<td style='padding: 0.75rem; text-align: center;'>" . ($pill['ativo'] ? '✅ Ativo' : '❌ Inativo') . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
            }
        }
    } else {
        // Exibir para um tenant específico
        echo "<table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>";
        echo "<thead>";
        echo "<tr style='background: #023A8D; color: white;'>";
        echo "<th style='padding: 0.75rem; text-align: left;'>ID</th>";
        echo "<th style='padding: 0.75rem; text-align: left;'>Nome</th>";
        echo "<th style='padding: 0.75rem; text-align: left;'>Slug</th>";
        echo "<th style='padding: 0.75rem; text-align: left;'>Descrição</th>";
        echo "<th style='padding: 0.75rem; text-align: center;'>Total Produtos</th>";
        echo "<th style='padding: 0.75rem; text-align: center;'>Publicados</th>";
        echo "<th style='padding: 0.75rem; text-align: left;'>Categoria Pai</th>";
            echo "<th style='padding: 0.75rem; text-align: left;'>Label na Home</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($categorias as $cat) {
                echo "<tr style='border-bottom: 1px solid #ddd;'>";
                echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($cat['id']) . "</td>";
                echo "<td style='padding: 0.75rem;'><strong>" . htmlspecialchars($cat['nome']) . "</strong></td>";
                echo "<td style='padding: 0.75rem;'><code>" . htmlspecialchars($cat['slug']) . "</code></td>";
                echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($cat['descricao'] ?: '-') . "</td>";
                echo "<td style='padding: 0.75rem; text-align: center;'><strong>" . $cat['total_produtos'] . "</strong></td>";
                echo "<td style='padding: 0.75rem; text-align: center;'>" . $cat['produtos_publicados'] . "</td>";
                echo "<td style='padding: 0.75rem;'>" . ($cat['categoria_pai_id'] ? 'ID: ' . $cat['categoria_pai_id'] : '-') . "</td>";
                echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($cat['labels_home'] ?: '-') . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            
            // Mostrar categorias configuradas na home
            if (!empty($categoriasHome)) {
                // Contar quantas estão sem categoria
                $semCategoriaCount = 0;
                foreach ($categoriasHome as $pill) {
                    $labelExibido = $pill['label'] ?: $pill['categoria_nome'] ?: '';
                    if (strtolower($labelExibido) === 'array' || empty($pill['categoria_id'])) {
                        $semCategoriaCount++;
                    }
                }
                
                echo "<h3 style='margin-top: 3rem; border-bottom: 2px solid #F7931E; padding-bottom: 0.5rem;'>Categorias Configuradas na Home (Pills)</h3>";
                if ($semCategoriaCount > 0) {
                    echo "<p style='color: #d32f2f; background: #ffebee; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;'><strong>⚠️ Atenção:</strong> {$semCategoriaCount} categoria(s) configurada(s) na home estão SEM categoria associada (mostram 'Array').</p>";
                }
                echo "<table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>";
                echo "<thead>";
                echo "<tr style='background: #F7931E; color: white;'>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Ordem</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Label (exibido)</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Categoria</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Slug</th>";
                echo "<th style='padding: 0.75rem; text-align: left;'>Ícone</th>";
                echo "<th style='padding: 0.75rem; text-align: center;'>Status</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                
                foreach ($categoriasHome as $pill) {
                    $labelExibido = $pill['label'] ?: $pill['categoria_nome'] ?: '(sem nome)';
                    $semCategoria = (strtolower($labelExibido) === 'array' || empty($pill['categoria_id']));
                    $corLinha = $semCategoria ? 'background: #ffebee;' : '';
                    echo "<tr style='border-bottom: 1px solid #ddd; {$corLinha}'>";
                    echo "<td style='padding: 0.75rem;'>" . $pill['ordem'] . "</td>";
                    echo "<td style='padding: 0.75rem;'><strong>" . htmlspecialchars($labelExibido) . "</strong>";
                    if ($semCategoria) {
                        echo " <span style='color: #d32f2f; font-weight: bold;'>⚠️ SEM CATEGORIA</span>";
                    } elseif ($pill['label'] && $pill['label'] !== $pill['categoria_nome']) {
                        echo " <small style='color: #666;'>(label customizado)</small>";
                    }
                    echo "</td>";
                    echo "<td style='padding: 0.75rem;'>" . ($pill['categoria_nome'] ? htmlspecialchars($pill['categoria_nome']) : '<span style="color: #d32f2f; font-weight: bold;">⚠️ SEM CATEGORIA</span>') . "</td>";
                    echo "<td style='padding: 0.75rem;'><code>" . htmlspecialchars($pill['categoria_slug'] ?: '-') . "</code></td>";
                    echo "<td style='padding: 0.75rem;'>" . htmlspecialchars($pill['icone_path'] ?: '-') . "</td>";
                    echo "<td style='padding: 0.75rem; text-align: center;'>" . ($pill['ativo'] ? '✅ Ativo' : '❌ Inativo') . "</td>";
                    echo "</tr>";
                }
                
                echo "</tbody>";
                echo "</table>";
            }
    }
    
    // Exportar para CSV (opcional)
    echo "<div style='margin-top: 2rem; padding: 1rem; background: #f0f0f0; border-radius: 8px;'>";
    echo "<h3>Exportar Dados</h3>";
    echo "<a href='?export=csv' style='display: inline-block; padding: 0.75rem 1.5rem; background: #F7931E; color: white; text-decoration: none; border-radius: 4px;'>Exportar para CSV</a>";
    echo "</div>";
    
    // Export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="categorias_produtos_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        if ($tenantId) {
            fputcsv($output, ['ID', 'Nome', 'Slug', 'Descrição', 'Total Produtos', 'Produtos Publicados', 'Categoria Pai ID'], ';');
        } else {
            fputcsv($output, ['Tenant ID', 'Tenant Nome', 'Categoria ID', 'Nome', 'Slug', 'Descrição', 'Total Produtos', 'Produtos Publicados', 'Categoria Pai ID'], ';');
        }
        
        // Data
        foreach ($categorias as $cat) {
            if ($tenantId) {
                fputcsv($output, [
                    $cat['id'],
                    $cat['nome'],
                    $cat['slug'],
                    $cat['descricao'] ?: '',
                    $cat['total_produtos'],
                    $cat['produtos_publicados'],
                    $cat['categoria_pai_id'] ?: ''
                ], ';');
            } else {
                fputcsv($output, [
                    $cat['tenant_id'],
                    $cat['tenant_name'],
                    $cat['categoria_id'],
                    $cat['nome'],
                    $cat['slug'],
                    $cat['descricao'] ?: '',
                    $cat['total_produtos'],
                    $cat['produtos_publicados'],
                    $cat['categoria_pai_id'] ?: ''
                ], ';');
            }
        }
        
        fclose($output);
        exit;
    }
    
} catch (\Exception $e) {
    echo "<div style='background: #fee; color: #c33; padding: 1rem; border-radius: 8px; border: 1px solid #fcc;'>";
    echo "<h2>Erro</h2>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

?>
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1400px;
    margin: 2rem auto;
    padding: 0 2rem;
    background: #f5f5f5;
}
table {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
tbody tr:hover {
    background: #f8f8f8;
}
code {
    background: #f0f0f0;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.9em;
}
</style>


