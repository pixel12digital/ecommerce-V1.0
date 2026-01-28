<?php
/**
 * Script de Auditoria - Detectar produtos "duplicados" que deveriam virar variações
 * 
 * IMPORTANTE: Somente SELECT. Nenhuma alteração é feita no banco de dados.
 * 
 * Uso: php database/auditar_produtos_variacoes_cli.php
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

use App\Core\Database;

$db = Database::getConnection();

echo "========================================\n";
echo "AUDITORIA - Produtos para Variações\n";
echo "========================================\n";
echo "⚠️  SOMENTE SELECT. Nenhuma alteração será feita.\n\n";

// Buscar todos os produtos (apenas simples, excluir já variáveis)
$stmt = $db->query("
    SELECT 
        id, tenant_id, nome, sku, tipo,
        COALESCE(preco_promocional, preco_regular, preco) as preco_final,
        preco_regular, preco_promocional,
        quantidade_estoque, gerencia_estoque, status_estoque,
        status, descricao, descricao_curta, imagem_principal
    FROM produtos
    WHERE tipo = 'simple' AND status = 'publish'
    ORDER BY tenant_id, nome ASC
");
$produtos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

echo "Total de produtos simples encontrados: " . count($produtos) . "\n\n";

// Listas de variações comuns
$cores = ['vermelho', 'vermelha', 'azul', 'azuis', 'preta', 'preto', 'pretos', 'pretas', 
          'branco', 'branca', 'brancos', 'brancas', 'amarelo', 'amarela', 'amarelos', 'amarelas',
          'verde', 'verdes', 'rosa', 'rosas', 'cinza', 'cinzas', 'marrom', 'marrons',
          'bege', 'beges', 'laranja', 'laranjas', 'roxo', 'roxos', 'roxa', 'roxas',
          'dourado', 'dourada', 'prateado', 'prateada'];

$tamanhos = ['pp', 'p', 'm', 'g', 'gg', 'xg', 'xgg', 'xxg', 'xxxg', 
             'plus', 'infantil', 'adulto', 'un', 'unico', 'unica',
             'pequeno', 'pequena', 'medio', 'media', 'grande', 'extra grande'];

$numeracoes = [];
for ($i = 30; $i <= 50; $i++) {
    $numeracoes[] = (string)$i;
}

/**
 * Normaliza nome removendo acentos, pontuação e variações
 */
function normalizeName($nome) {
    // Lowercase e trim
    $nome = mb_strtolower(trim($nome), 'UTF-8');
    
    // Remover acentos
    $nome = str_replace(
        ['á', 'à', 'ã', 'â', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'õ', 'ô', 'ö', 'ú', 'ù', 'û', 'ü', 'ç'],
        ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c'],
        $nome
    );
    
    // Remover pontuação comum
    $nome = preg_replace('/[-.\/,;:]/', ' ', $nome);
    
    // Remover espaços múltiplos
    $nome = preg_replace('/\s+/', ' ', $nome);
    $nome = trim($nome);
    
    return $nome;
}

/**
 * Extrai base name removendo variações conhecidas
 */
function extractBaseName($nomeNormalizado, &$coresDetectadas, &$tamanhosDetectados, &$numeracoesDetectadas, $cores, $tamanhos, $numeracoes) {
    
    $palavras = explode(' ', $nomeNormalizado);
    $baseWords = [];
    
    foreach ($palavras as $palavra) {
        $palavra = trim($palavra);
        if (empty($palavra)) continue;
        
        // Verificar cor
        if (in_array($palavra, $cores)) {
            $coresDetectadas[] = ucfirst($palavra);
            continue;
        }
        
        // Verificar tamanho
        if (in_array($palavra, $tamanhos)) {
            $tamanhosDetectados[] = strtoupper($palavra);
            continue;
        }
        
        // Verificar numeração (34-46 ou número isolado)
        if (preg_match('/^(\d+)(-\d+)?$/', $palavra, $matches)) {
            if (isset($matches[2])) {
                // Faixa (ex: 34-46)
                $numeracoesDetectadas[] = $palavra;
            } elseif (in_array($matches[1], $numeracoes)) {
                // Número único (ex: 42)
                $numeracoesDetectadas[] = $matches[1];
            }
            continue;
        }
        
        // Verificar padrão SKU comum (ex: -P, -M, -Vermelho)
        if (preg_match('/^[a-z]{1,2}$/i', $palavra) && strlen($palavra) <= 2) {
            // Pode ser tamanho abreviado, mas não removemos aqui
        }
        
        $baseWords[] = $palavra;
    }
    
    return implode(' ', $baseWords);
}

/**
 * Calcula similaridade entre descrições
 */
function similarityScore($desc1, $desc2) {
    if (empty($desc1) || empty($desc2)) return 0;
    
    $desc1 = normalizeName($desc1);
    $desc2 = normalizeName($desc2);
    
    similar_text($desc1, $desc2, $percent);
    return $percent;
}

/**
 * Calcula confiança do grupo
 */
function calculateConfidence($grupo, $produtos) {
    $score = 0;
    $notes = [];
    
    // +30 se base_name igual (já agrupados)
    $score += 30;
    
    // +20 se descrições parecidas
    $descricoes = [];
    foreach ($grupo['items'] as $itemId) {
        $prod = $produtos[$itemId];
        if (!empty($prod['descricao'])) {
            $descricoes[] = $prod['descricao'];
        }
    }
    
    if (count($descricoes) >= 2) {
        $similarities = [];
        for ($i = 0; $i < count($descricoes) - 1; $i++) {
            for ($j = $i + 1; $j < count($descricoes); $j++) {
                $sim = similarityScore($descricoes[$i], $descricoes[$j]);
                $similarities[] = $sim;
            }
        }
        $avgSim = array_sum($similarities) / count($similarities);
        if ($avgSim > 80) {
            $score += 20;
        } elseif ($avgSim < 50) {
            $notes[] = "Descrições muito diferentes";
        }
    }
    
    // +20 se imagens parecidas (mesma imagem principal)
    $imagens = [];
    foreach ($grupo['items'] as $itemId) {
        $prod = $produtos[$itemId];
        if (!empty($prod['imagem_principal'])) {
            $imagens[] = $prod['imagem_principal'];
        }
    }
    
    if (count($imagens) >= 2) {
        $uniqueImagens = array_unique($imagens);
        if (count($uniqueImagens) == 1) {
            $score += 20;
        } elseif (count($uniqueImagens) < count($imagens)) {
            $score += 10;
        } else {
            $notes[] = "Imagens diferentes";
        }
    }
    
    // +10 se preços iguais ou muito próximos
    $precos = [];
    foreach ($grupo['items'] as $itemId) {
        $prod = $produtos[$itemId];
        $precos[] = (float)$prod['preco_final'];
    }
    
    if (count($precos) >= 2) {
        $uniquePrecos = array_unique($precos);
        if (count($uniquePrecos) == 1) {
            $score += 10;
        } else {
            $minPreco = min($precos);
            $maxPreco = max($precos);
            $diff = abs($maxPreco - $minPreco);
            if ($diff <= 5.00) {
                $score += 5;
            } else {
                $notes[] = "Preços divergentes (diferença de R$ " . number_format($diff, 2, ',', '.') . ")";
            }
        }
    }
    
    // -20 se categorias diferentes (verificar depois se necessário)
    
    return [
        'score' => min(100, max(0, $score)),
        'notes' => $notes
    ];
}

// Agrupar produtos por base_name
$grupos = [];
$produtosMap = [];

foreach ($produtos as $prod) {
    $produtosMap[$prod['id']] = $prod;
    
    $nomeNorm = normalizeName($prod['nome']);
    $coresDetectadas = [];
    $tamanhosDetectados = [];
    $numeracoesDetectadas = [];
    
    $baseName = extractBaseName($nomeNorm, $coresDetectadas, $tamanhosDetectados, $numeracoesDetectadas, $cores, $tamanhos, $numeracoes);
    
    if (empty($baseName)) {
        continue; // Pular se não sobrou nada
    }
    
    $key = $prod['tenant_id'] . '|' . $baseName;
    
    if (!isset($grupos[$key])) {
        $grupos[$key] = [
            'tenant_id' => $prod['tenant_id'],
            'base_name' => $baseName,
            'items' => [],
            'cores' => [],
            'tamanhos' => [],
            'numeracoes' => []
        ];
    }
    
    $grupos[$key]['items'][] = $prod['id'];
    
    // Acumular variações detectadas
    foreach ($coresDetectadas as $cor) {
        if (!in_array($cor, $grupos[$key]['cores'])) {
            $grupos[$key]['cores'][] = $cor;
        }
    }
    foreach ($tamanhosDetectados as $tam) {
        if (!in_array($tam, $grupos[$key]['tamanhos'])) {
            $grupos[$key]['tamanhos'][] = $tam;
        }
    }
    foreach ($numeracoesDetectadas as $num) {
        if (!in_array($num, $grupos[$key]['numeracoes'])) {
            $grupos[$key]['numeracoes'][] = $num;
        }
    }
}

// Filtrar grupos com 2+ produtos e calcular confiança
$gruposFinais = [];
foreach ($grupos as $key => $grupo) {
    if (count($grupo['items']) < 2) {
        continue;
    }
    
    $conf = calculateConfidence($grupo, $produtosMap);
    
    // Determinar nome do produto pai sugerido (nome mais comum ou com maior estoque)
    $nomes = [];
    $estoques = [];
    foreach ($grupo['items'] as $itemId) {
        $prod = $produtosMap[$itemId];
        $nomes[] = $prod['nome'];
        $estoques[$prod['id']] = (int)$prod['quantidade_estoque'];
    }
    
    $nomeMaisComum = '';
    $maxCount = 0;
    foreach (array_count_values($nomes) as $nome => $count) {
        if ($count > $maxCount) {
            $maxCount = $count;
            $nomeMaisComum = $nome;
        }
    }
    
    // Se houver empate, usar o com maior estoque
    if ($maxCount == 1) {
        arsort($estoques);
        $idMaiorEstoque = array_key_first($estoques);
        $nomeMaisComum = $produtosMap[$idMaiorEstoque]['nome'];
    }
    
    // Normalizar nome sugerido (capitalizar primeira letra de cada palavra)
    $nomeSugerido = ucwords($grupo['base_name']);
    
    // Montar atributos detectados
    $atributos = [];
    if (!empty($grupo['cores'])) {
        $atributos['Cor'] = array_unique($grupo['cores']);
    }
    if (!empty($grupo['tamanhos'])) {
        $atributos['Tamanho'] = array_unique($grupo['tamanhos']);
    }
    if (!empty($grupo['numeracoes'])) {
        $atributos['Numeração'] = array_unique($grupo['numeracoes']);
    }
    
    // Montar lista de itens
    $items = [];
    foreach ($grupo['items'] as $itemId) {
        $prod = $produtosMap[$itemId];
        $items[] = [
            'id' => (int)$prod['id'],
            'nome' => $prod['nome'],
            'sku' => $prod['sku'] ?? null,
            'preco' => (float)$prod['preco_final'],
            'preco_regular' => (float)$prod['preco_regular'],
            'preco_promocional' => $prod['preco_promocional'] ? (float)$prod['preco_promocional'] : null,
            'estoque' => (int)$prod['quantidade_estoque'],
            'status' => $prod['status']
        ];
    }
    
    $gruposFinais[] = [
        'tenant_id' => (int)$grupo['tenant_id'],
        'base_name' => $grupo['base_name'],
        'confidence' => $conf['score'],
        'suggested_parent_name' => $nomeSugerido,
        'detected_attributes' => $atributos,
        'items' => $items,
        'notes' => $conf['notes']
    ];
}

// Produtos órfãos (não entraram em nenhum grupo)
$idsEmGrupos = [];
foreach ($gruposFinais as $grupo) {
    foreach ($grupo['items'] as $item) {
        $idsEmGrupos[] = $item['id'];
    }
}

$orfas = [];
foreach ($produtos as $prod) {
    if (!in_array($prod['id'], $idsEmGrupos)) {
        $orfas[] = [
            'id' => (int)$prod['id'],
            'nome' => $prod['nome'],
            'sku' => $prod['sku'] ?? null,
            'preco' => (float)$prod['preco_final']
        ];
    }
}

// Ordenar grupos por confiança e tamanho
usort($gruposFinais, function($a, $b) {
    $scoreA = $a['confidence'] * 10 + count($a['items']);
    $scoreB = $b['confidence'] * 10 + count($b['items']);
    return $scoreB <=> $scoreA;
});

// Preparar resultado
$resultado = [
    'generated_at' => date('Y-m-d H:i:s'),
    'total_produtos_analisados' => count($produtos),
    'total_grupos_encontrados' => count($gruposFinais),
    'total_produtos_em_grupos' => count($idsEmGrupos),
    'total_produtos_orfas' => count($orfas),
    'groups' => $gruposFinais,
    'orphans' => $orfas
];

// Salvar JSON
$storageDir = __DIR__ . '/../storage/reports';
if (!is_dir($storageDir)) {
    $storageDir = sys_get_temp_dir();
}

$jsonFile = $storageDir . '/auditoria_variacoes_' . date('Y-m-d_His') . '.json';
file_put_contents($jsonFile, json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "✅ Análise concluída!\n\n";
echo "Resumo:\n";
echo "  - Produtos analisados: " . count($produtos) . "\n";
echo "  - Grupos encontrados: " . count($gruposFinais) . "\n";
echo "  - Produtos em grupos: " . count($idsEmGrupos) . "\n";
echo "  - Produtos órfãos: " . count($orfas) . "\n";
echo "\n";
echo "📄 JSON salvo em: " . $jsonFile . "\n\n";

// TOP 20 por confiança
echo "TOP 20 Grupos (por confiança):\n";
echo str_repeat("-", 80) . "\n";
$top20 = array_slice($gruposFinais, 0, 20);
foreach ($top20 as $idx => $grupo) {
    echo sprintf("%2d. [Conf: %3d%%] %s (%d itens)\n", 
        $idx + 1, 
        $grupo['confidence'],
        $grupo['suggested_parent_name'],
        count($grupo['items'])
    );
    if (!empty($grupo['detected_attributes'])) {
        foreach ($grupo['detected_attributes'] as $attr => $terms) {
            echo "     → {$attr}: " . implode(', ', $terms) . "\n";
        }
    }
}

echo "\n";
echo "TOP 20 Precisa Revisão (confiança < 60):\n";
echo str_repeat("-", 80) . "\n";
$revisao = array_filter($gruposFinais, function($g) { return $g['confidence'] < 60; });
$revisao = array_slice($revisao, 0, 20);
foreach ($revisao as $idx => $grupo) {
    echo sprintf("%2d. [Conf: %3d%%] %s (%d itens)\n", 
        $idx + 1, 
        $grupo['confidence'],
        $grupo['suggested_parent_name'],
        count($grupo['items'])
    );
    if (!empty($grupo['notes'])) {
        echo "     ⚠️  " . implode(', ', $grupo['notes']) . "\n";
    }
}

echo "\n✅ Relatório completo salvo em JSON.\n";
