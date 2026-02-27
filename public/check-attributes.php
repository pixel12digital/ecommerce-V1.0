<?php
// Script para verificar produtos com atributos por categoria
// Acesse via: https://pontodogolfeoutlet.com.br/check-attributes.php

require __DIR__ . '/../vendor/autoload.php';

// Carregar configuração
$config = require __DIR__ . '/../config/app.php';

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_name($config['session_name']);
    session_start();
}

// Resolver tenant
$host = $_SERVER['HTTP_HOST'] ?? 'pontodogolfeoutlet.com.br';
\App\Tenant\TenantContext::resolveFromHost($host);

$tenantId = \App\Tenant\TenantContext::id();
$db = \App\Core\Database::getConnection();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Produtos com Atributos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2E7D32;
            border-bottom: 3px solid #2E7D32;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            border-left: 4px solid #2E7D32;
            padding-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2E7D32;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-success {
            background: #4CAF50;
            color: white;
        }
        .badge-warning {
            background: #FF9800;
            color: white;
        }
        .badge-info {
            background: #2196F3;
            color: white;
        }
        .summary {
            background: #E8F5E9;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #2E7D32;
        }
        .summary h3 {
            margin-top: 0;
            color: #2E7D32;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Verificação de Produtos com Atributos/Variações</h1>
        
        <?php
        // 1. Total de produtos com atributos
        $stmt = $db->prepare("
            SELECT COUNT(DISTINCT p.id) as total
            FROM produtos p
            INNER JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
            WHERE p.tenant_id = :tenant_id
            AND p.status = 'publish'
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $totalComAtributos = $stmt->fetch()['total'];
        
        // Total geral de produtos
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM produtos
            WHERE tenant_id = :tenant_id
            AND status = 'publish'
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $totalProdutos = $stmt->fetch()['total'];
        ?>
        
        <div class="summary">
            <h3>Resumo Geral</h3>
            <p><strong>Total de produtos publicados:</strong> <?= $totalProdutos ?></p>
            <p><strong>Produtos com atributos:</strong> <?= $totalComAtributos ?> 
                <span class="badge badge-info"><?= $totalProdutos > 0 ? round(($totalComAtributos / $totalProdutos) * 100, 1) : 0 ?>%</span>
            </p>
        </div>

        <h2>📦 Produtos por Categoria</h2>
        <?php
        $stmt = $db->prepare("
            SELECT 
                c.nome as categoria,
                c.slug as categoria_slug,
                COUNT(DISTINCT p.id) as total_produtos,
                COUNT(DISTINCT CASE WHEN pa.id IS NOT NULL THEN p.id END) as produtos_com_atributos,
                COUNT(DISTINCT CASE WHEN p.tipo = 'variable' THEN p.id END) as produtos_variaveis
            FROM categorias c
            LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
            LEFT JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = pc.tenant_id AND p.status = 'publish'
            LEFT JOIN produto_atributos pa ON pa.produto_id = p.id AND pa.tenant_id = p.tenant_id
            WHERE c.tenant_id = :tenant_id
            GROUP BY c.id, c.nome, c.slug
            HAVING total_produtos > 0
            ORDER BY produtos_com_atributos DESC, c.nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Total Produtos</th>
                    <th>Com Atributos</th>
                    <th>Variáveis</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($cat['categoria']) ?></strong></td>
                    <td><?= $cat['total_produtos'] ?></td>
                    <td>
                        <?= $cat['produtos_com_atributos'] ?>
                        <?php if ($cat['produtos_com_atributos'] > 0): ?>
                            <span class="badge badge-success">✓</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $cat['produtos_variaveis'] ?></td>
                    <td>
                        <?php 
                        $percent = $cat['total_produtos'] > 0 ? round(($cat['produtos_com_atributos'] / $cat['total_produtos']) * 100, 1) : 0;
                        echo $percent . '%';
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>🏷️ Atributos Mais Usados</h2>
        <?php
        $stmt = $db->prepare("
            SELECT 
                a.nome as atributo,
                a.tipo,
                COUNT(DISTINCT pa.produto_id) as total_produtos
            FROM atributos a
            INNER JOIN produto_atributos pa ON pa.atributo_id = a.id AND pa.tenant_id = a.tenant_id
            WHERE a.tenant_id = :tenant_id
            GROUP BY a.id, a.nome, a.tipo
            ORDER BY total_produtos DESC
            LIMIT 10
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $atributos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>Atributo</th>
                    <th>Tipo</th>
                    <th>Produtos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($atributos as $attr): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($attr['atributo']) ?></strong></td>
                    <td><span class="badge badge-info"><?= strtoupper($attr['tipo']) ?></span></td>
                    <td><?= $attr['total_produtos'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>📏 Tamanhos Disponíveis por Categoria</h2>
        <?php
        $stmt = $db->prepare("
            SELECT 
                c.nome as categoria,
                GROUP_CONCAT(DISTINCT at.nome ORDER BY at.nome SEPARATOR ', ') as tamanhos
            FROM categorias c
            INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
            INNER JOIN produto_atributo_termos pat ON pat.produto_id = pc.produto_id AND pat.tenant_id = pc.tenant_id
            INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id AND at.tenant_id = pat.tenant_id
            WHERE c.tenant_id = :tenant_id
            GROUP BY c.id, c.nome
            ORDER BY c.nome
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $tamanhosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <table>
            <thead>
                <tr>
                    <th>Categoria</th>
                    <th>Tamanhos Disponíveis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tamanhosPorCategoria as $cat): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($cat['categoria']) ?></strong></td>
                    <td><?= htmlspecialchars($cat['tamanhos']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p style="margin-top: 40px; color: #666; font-size: 0.9em;">
            Gerado em: <?= date('d/m/Y H:i:s') ?>
        </p>
    </div>
</body>
</html>
