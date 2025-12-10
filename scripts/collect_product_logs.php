<?php
/**
 * Script para coletar e exibir logs relacionados ao ProductController
 * 
 * Uso:
 *   php scripts/collect_product_logs.php
 *   php scripts/collect_product_logs.php --tail 50  (últimas 50 linhas)
 *   php scripts/collect_product_logs.php --product 929  (filtrar por produto)
 *   php scripts/collect_product_logs.php --last-hour  (última hora)
 */

// Configurações
$logFile = ini_get('error_log');
if (empty($logFile)) {
    // Tentar caminhos comuns
    $possiblePaths = [
        __DIR__ . '/../logs/php_error.log',
        __DIR__ . '/../storage/logs/php_error.log',
        '/var/log/php_error.log',
        '/var/log/apache2/error.log',
        '/var/log/httpd/error_log',
        sys_get_temp_dir() . '/php_errors.log',
    ];
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $logFile = $path;
            break;
        }
    }
}

// Se ainda não encontrou, tentar detectar automaticamente
if (empty($logFile) || !file_exists($logFile)) {
    // Tentar encontrar arquivo de log mais recente
    $root = dirname(__DIR__);
    $logDirs = [
        $root . '/logs',
        $root . '/storage/logs',
        '/var/log',
    ];
    
    foreach ($logDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*.log');
            if (!empty($files)) {
                // Pegar o mais recente
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $logFile = $files[0];
                break;
            }
        }
    }
}

// Processar argumentos da linha de comando
$options = getopt('', ['tail:', 'product:', 'last-hour', 'last-minutes:', 'help', 'output:']);

if (isset($options['help'])) {
    echo "Script para coletar logs do ProductController\n\n";
    echo "Uso:\n";
    echo "  php scripts/collect_product_logs.php [opções]\n\n";
    echo "Opções:\n";
    echo "  --tail=N          Mostrar apenas as últimas N linhas\n";
    echo "  --product=ID      Filtrar apenas logs do produto ID\n";
    echo "  --last-hour       Mostrar logs da última hora\n";
    echo "  --last-minutes=N  Mostrar logs dos últimos N minutos\n";
    echo "  --output=file     Salvar saída em arquivo\n";
    echo "  --help            Mostrar esta ajuda\n\n";
    echo "Exemplos:\n";
    echo "  php scripts/collect_product_logs.php --tail 100\n";
    echo "  php scripts/collect_product_logs.php --product 929\n";
    echo "  php scripts/collect_product_logs.php --last-hour --product 929\n";
    exit(0);
}

if (empty($logFile) || !file_exists($logFile)) {
    echo "ERRO: Arquivo de log não encontrado.\n";
    echo "Tentou os seguintes caminhos:\n";
    if (!empty($logFile)) {
        echo "  - {$logFile}\n";
    }
    echo "\nPor favor, especifique o caminho do log editando o script ou definindo error_log no php.ini\n";
    exit(1);
}

echo "Lendo logs de: {$logFile}\n";
echo str_repeat('=', 80) . "\n\n";

// Ler arquivo de log
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    echo "ERRO: Não foi possível ler o arquivo de log.\n";
    exit(1);
}

// Filtrar apenas linhas relacionadas ao ProductController
$filteredLines = [];
foreach ($lines as $line) {
    if (stripos($line, 'ProductController') !== false) {
        $filteredLines[] = $line;
    }
}

// Aplicar filtros adicionais
if (isset($options['product'])) {
    $productId = $options['product'];
    $filteredLines = array_filter($filteredLines, function($line) use ($productId) {
        return strpos($line, "produto {$productId}") !== false || 
               strpos($line, "produto_id = {$productId}") !== false ||
               strpos($line, "produtoId = {$productId}") !== false ||
               strpos($line, "produto {$productId},") !== false;
    });
}

// Filtrar por tempo
if (isset($options['last-hour'])) {
    $cutoffTime = time() - 3600;
    $filteredLines = array_filter($filteredLines, function($line) use ($cutoffTime) {
        // Tentar extrair timestamp da linha (formato comum: [2025-01-XX HH:MM:SS])
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $lineTime = strtotime($matches[1]);
            return $lineTime >= $cutoffTime;
        }
        // Se não conseguir extrair timestamp, incluir a linha (melhor incluir do que excluir)
        return true;
    });
} elseif (isset($options['last-minutes'])) {
    $minutes = (int)$options['last-minutes'];
    $cutoffTime = time() - ($minutes * 60);
    $filteredLines = array_filter($filteredLines, function($line) use ($cutoffTime) {
        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            $lineTime = strtotime($matches[1]);
            return $lineTime >= $cutoffTime;
        }
        return true;
    });
}

// Aplicar limite de linhas (tail)
if (isset($options['tail'])) {
    $tail = (int)$options['tail'];
    $filteredLines = array_slice($filteredLines, -$tail);
}

// Organizar por tipo de log
$organized = [
    'update' => [],
    'processMainImage' => [],
    'processGallery' => [],
    'other' => []
];

foreach ($filteredLines as $line) {
    if (strpos($line, 'ProductController::update') !== false) {
        $organized['update'][] = $line;
    } elseif (strpos($line, 'ProductController::processMainImage') !== false) {
        $organized['processMainImage'][] = $line;
    } elseif (strpos($line, 'ProductController::processGallery') !== false) {
        $organized['processGallery'][] = $line;
    } else {
        $organized['other'][] = $line;
    }
}

// Preparar saída
$output = '';

if (empty($filteredLines)) {
    $output = "Nenhum log encontrado com os filtros aplicados.\n";
} else {
    $output .= "Total de logs encontrados: " . count($filteredLines) . "\n\n";
    
    // Mostrar por categoria
    foreach ($organized as $category => $lines) {
        if (!empty($lines)) {
            $output .= str_repeat('-', 80) . "\n";
            $output .= strtoupper($category) . " (" . count($lines) . " logs)\n";
            $output .= str_repeat('-', 80) . "\n";
            foreach ($lines as $line) {
                $output .= $line . "\n";
            }
            $output .= "\n";
        }
    }
}

// Salvar ou exibir
if (isset($options['output'])) {
    $outputFile = $options['output'];
    file_put_contents($outputFile, $output);
    echo "Logs salvos em: {$outputFile}\n";
} else {
    echo $output;
}

// Estatísticas
if (!empty($filteredLines)) {
    echo "\n" . str_repeat('=', 80) . "\n";
    echo "ESTATÍSTICAS:\n";
    echo "  - Total de logs: " . count($filteredLines) . "\n";
    echo "  - Método update: " . count($organized['update']) . "\n";
    echo "  - Método processMainImage: " . count($organized['processMainImage']) . "\n";
    echo "  - Método processGallery: " . count($organized['processGallery']) . "\n";
    echo "  - Outros: " . count($organized['other']) . "\n";
    
    // Contar erros
    $errors = array_filter($filteredLines, function($line) {
        return stripos($line, 'erro') !== false || 
               stripos($line, 'error') !== false ||
               stripos($line, 'falhou') !== false ||
               stripos($line, 'failed') !== false;
    });
    if (!empty($errors)) {
        echo "  - ERROS encontrados: " . count($errors) . "\n";
    }
}

