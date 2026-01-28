<?php
/**
 * Script auxiliar para extrair atributos e variações do relatório de auditoria
 */

require_once __DIR__ . '/../vendor/autoload.php';

$jsonFile = __DIR__ . '/../storage/reports/auditoria_variacoes_2026-01-20_144326.json';

if (!file_exists($jsonFile)) {
    echo "Arquivo não encontrado: $jsonFile\n";
    exit(1);
}

$json = json_decode(file_get_contents($jsonFile), true);

if (!$json) {
    echo "Erro ao ler JSON\n";
    exit(1);
}

echo "========================================\n";
echo "ATRIBUTOS E VARIAÇÕES NECESSÁRIOS\n";
echo "========================================\n\n";

// Consolidar atributos e termos
$atributosConsolidados = [];
$gruposDetalhados = [];

foreach ($json['groups'] as $grupo) {
    // Consolidar atributos
    foreach ($grupo['detected_attributes'] as $attrNome => $termos) {
        if (!isset($atributosConsolidados[$attrNome])) {
            $atributosConsolidados[$attrNome] = [];
        }
        foreach ($termos as $termo) {
            if (!in_array($termo, $atributosConsolidados[$attrNome])) {
                $atributosConsolidados[$attrNome][] = $termo;
            }
        }
    }
    
    // Guardar grupo detalhado
    $gruposDetalhados[] = $grupo;
}

// Ordenar termos
foreach ($atributosConsolidados as $attr => $termos) {
    sort($termos);
    $atributosConsolidados[$attr] = $termos;
}

// Exibir atributos necessários
echo "📋 ATRIBUTOS A CRIAR:\n";
echo str_repeat("=", 50) . "\n\n";

foreach ($atributosConsolidados as $attrNome => $termos) {
    echo "🔹 {$attrNome} (" . count($termos) . " termos):\n";
    foreach ($termos as $termo) {
        echo "   • {$termo}\n";
    }
    echo "\n";
}

// Exibir grupos com variações sugeridas
echo "\n";
echo "📦 GRUPOS DE PRODUTOS (Variações Sugeridas):\n";
echo str_repeat("=", 80) . "\n\n";

// Ordenar por confiança
usort($gruposDetalhados, function($a, $b) {
    return $b['confidence'] <=> $a['confidence'];
});

foreach ($gruposDetalhados as $idx => $grupo) {
    echo sprintf("%d. [Conf: %3d%%] %s\n", $idx + 1, $grupo['confidence'], $grupo['suggested_parent_name']);
    echo "   Produto Pai Sugerido: \"{$grupo['suggested_parent_name']}\"\n";
    echo "   Quantidade de itens: " . count($grupo['items']) . "\n";
    
    if (!empty($grupo['detected_attributes'])) {
        echo "   Atributos/Variações:\n";
        foreach ($grupo['detected_attributes'] as $attr => $termos) {
            echo "     → {$attr}: " . implode(', ', $termos) . "\n";
        }
    }
    
    echo "   Produtos no grupo:\n";
    foreach ($grupo['items'] as $item) {
        $precoStr = number_format($item['preco'], 2, ',', '.');
        $estoqueStr = $item['estoque'] > 0 ? " (Estoque: {$item['estoque']})" : " (Sem estoque)";
        echo "     • ID {$item['id']}: {$item['nome']} - R$ {$precoStr}{$estoqueStr}\n";
    }
    
    if (!empty($grupo['notes'])) {
        echo "   ⚠️  Observações: " . implode(', ', $grupo['notes']) . "\n";
    }
    
    echo "\n";
}

// Resumo
echo "\n";
echo "========================================\n";
echo "RESUMO\n";
echo "========================================\n";
echo "Total de atributos únicos: " . count($atributosConsolidados) . "\n";
echo "Total de grupos: " . count($gruposDetalhados) . "\n";
echo "Total de produtos em grupos: " . count($json['groups']) . "\n";
echo "\n";

// Salvar relatório em texto
$relatorioTxt = ob_get_clean();
ob_start();
echo $relatorioTxt;

$txtFile = __DIR__ . '/../storage/reports/atributos_variacoes_necessarios.txt';
file_put_contents($txtFile, $relatorioTxt);
echo "📄 Relatório detalhado salvo em: {$txtFile}\n";
