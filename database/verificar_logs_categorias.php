<?php
/**
 * Script para verificar logs do updateCategoriesQuick
 * Executar: php database/verificar_logs_categorias.php
 */

// Caminhos possíveis para o log do PHP no Windows/XAMPP
$possibleLogPaths = [
    'C:\xampp\php\logs\php_error.log',
    'C:\xampp\apache\logs\error.log',
    ini_get('error_log'),
    sys_get_temp_dir() . '/php_errors.log',
];

$logFile = null;
foreach ($possibleLogPaths as $path) {
    if (!empty($path) && file_exists($path)) {
        $logFile = $path;
        break;
    }
}

if (!$logFile) {
    echo "Nenhum arquivo de log encontrado nos caminhos:\n";
    foreach ($possibleLogPaths as $path) {
        echo "  - " . ($path ?: '(vazio)') . "\n";
    }
    echo "\nTentando encontrar logs em outros locais...\n\n";
    
    // Tentar encontrar qualquer arquivo .log recente
    $searchDirs = [
        'C:\xampp\php\logs',
        'C:\xampp\apache\logs',
        __DIR__ . '/../logs',
        __DIR__ . '/../storage/logs',
    ];
    
    foreach ($searchDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*.log');
            if (!empty($files)) {
                usort($files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $logFile = $files[0];
                echo "Arquivo de log encontrado: {$logFile}\n\n";
                break;
            }
        }
    }
}

if (!$logFile || !file_exists($logFile)) {
    echo "ERRO: Não foi possível encontrar o arquivo de log do PHP.\n";
    echo "Verifique manualmente os logs em:\n";
    echo "  - C:\\xampp\\php\\logs\\php_error.log\n";
    echo "  - C:\\xampp\\apache\\logs\\error.log\n";
    echo "\nOu verifique o valor de error_log no php.ini:\n";
    echo "  php -i | findstr error_log\n";
    exit(1);
}

echo "Lendo logs de: {$logFile}\n";
echo str_repeat("=", 80) . "\n\n";

// Ler últimas 200 linhas do log
$lines = file($logFile);
$recentLines = array_slice($lines, -200);

$foundLogs = [];
foreach ($recentLines as $line) {
    if (stripos($line, 'updateCategoriesQuick') !== false || 
        stripos($line, 'produto 410') !== false ||
        stripos($line, 'produto_id: 410') !== false ||
        stripos($line, 'SKU 354') !== false) {
        $foundLogs[] = trim($line);
    }
}

if (empty($foundLogs)) {
    echo "Nenhum log relacionado a updateCategoriesQuick ou produto 410 encontrado nas últimas 200 linhas.\n\n";
    echo "Últimas 10 linhas do log:\n";
    echo str_repeat("-", 80) . "\n";
    foreach (array_slice($recentLines, -10) as $line) {
        echo trim($line) . "\n";
    }
} else {
    echo "Logs encontrados relacionados a updateCategoriesQuick:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($foundLogs as $log) {
        echo $log . "\n";
    }
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "Total de logs encontrados: " . count($foundLogs) . "\n";
}



