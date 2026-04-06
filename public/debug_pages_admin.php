<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente do .env (banco remoto)
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
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            if (!empty($name)) {
                $_ENV[$name] = $value;
            }
        }
    }
}

use App\Services\ThemeConfig;
use App\Tenant\TenantContext;

// Inicializar tenant
$config = require __DIR__ . '/../config/app.php';
$mode = $config['mode'] ?? 'single';

if ($mode === 'single') {
    $defaultTenantId = $config['default_tenant_id'] ?? 1;
    TenantContext::setFixedTenant($defaultTenantId);
} else {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    TenantContext::resolveFromHost($host);
}

header('Content-Type: text/html; charset=utf-8');

$pages = ThemeConfig::getPages();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Páginas Admin</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .page { background: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 4px solid #2E7D32; }
        .page h3 { margin-top: 0; color: #2E7D32; }
        .field { margin-bottom: 10px; }
        .field strong { color: #666; }
        .content { background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Debug - Conteúdo das Páginas (ThemeConfig::getPages())</h1>
    
    <?php foreach ($pages as $slug => $page): ?>
        <div class="page">
            <h3><?php echo htmlspecialchars($slug); ?></h3>
            
            <div class="field">
                <strong>Title:</strong> <?php echo htmlspecialchars($page['title'] ?? 'N/A'); ?>
            </div>
            
            <?php if (isset($page['content'])): ?>
                <div class="field">
                    <strong>Content (<?php echo strlen($page['content']); ?> chars):</strong>
                    <div class="content"><?php echo htmlspecialchars(substr($page['content'], 0, 500)); ?><?php echo strlen($page['content']) > 500 ? '...' : ''; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($page['intro'])): ?>
                <div class="field">
                    <strong>Intro (<?php echo strlen($page['intro']); ?> chars):</strong>
                    <div class="content"><?php echo htmlspecialchars(substr($page['intro'], 0, 500)); ?><?php echo strlen($page['intro']) > 500 ? '...' : ''; ?></div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($page['items']) && is_array($page['items'])): ?>
                <div class="field">
                    <strong>Items (<?php echo count($page['items']); ?>):</strong>
                    <?php foreach ($page['items'] as $idx => $item): ?>
                        <div class="content">
                            <strong>Q<?php echo $idx + 1; ?>:</strong> <?php echo htmlspecialchars($item['question'] ?? 'N/A'); ?><br>
                            <strong>A:</strong> <?php echo htmlspecialchars(substr($item['answer'] ?? '', 0, 100)); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    
    <hr>
    <h2>Raw JSON</h2>
    <pre style="background: white; padding: 15px; border-radius: 5px; overflow-x: auto;"><?php echo json_encode($pages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?></pre>
</body>
</html>
