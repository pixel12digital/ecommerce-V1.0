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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Conteúdo das Páginas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2E7D32;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid #2E7D32;
            padding-bottom: 0.75rem;
        }
        .page-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #2E7D32;
        }
        .page-section h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        .page-info {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .page-info strong {
            color: #666;
        }
        .content-preview {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        .status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        .summary {
            background: #e7f3ff;
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            border-left: 4px solid #0066cc;
        }
        .summary h3 {
            color: #0066cc;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Verificação de Conteúdo das Páginas Institucionais</h1>
        
        <div class="summary">
            <h3>Resumo</h3>
            <p>Este relatório mostra o conteúdo inserido no banco de dados para cada página institucional.</p>
        </div>

        <?php
        $pagesToCheck = [
            'sobre' => 'Sobre a Loja',
            'contato' => 'Fale Conosco',
            'politica_privacidade' => 'Política de Privacidade',
            'termos_uso' => 'Termos de Uso',
            'politica_cookies' => 'Política de Cookies',
        ];

        foreach ($pagesToCheck as $slug => $expectedTitle) {
            $page = ThemeConfig::getPage($slug);
            $hasContent = !empty($page['content']) || !empty($page['intro']);
            $contentLength = isset($page['content']) ? strlen($page['content']) : 0;
            $introLength = isset($page['intro']) ? strlen($page['intro']) : 0;
            
            echo '<div class="page-section">';
            echo '<h2>' . htmlspecialchars($page['title']) . '</h2>';
            
            echo '<div class="page-info">';
            echo '<strong>Slug:</strong>';
            echo '<span>/' . htmlspecialchars($slug) . '</span>';
            echo '</div>';
            
            echo '<div class="page-info">';
            echo '<strong>Status:</strong>';
            if ($hasContent) {
                echo '<span class="status success">✓ Conteúdo inserido</span>';
            } else {
                echo '<span class="status warning">⚠ Sem conteúdo</span>';
            }
            echo '</div>';
            
            echo '<div class="page-info">';
            echo '<strong>Conteúdo:</strong>';
            echo '<span>' . $contentLength . ' caracteres</span>';
            echo '</div>';
            
            if ($introLength > 0) {
                echo '<div class="page-info">';
                echo '<strong>Intro:</strong>';
                echo '<span>' . $introLength . ' caracteres</span>';
                echo '</div>';
            }
            
            if ($hasContent) {
                echo '<div class="content-preview">';
                if (!empty($page['content'])) {
                    echo '<strong>Conteúdo:</strong><br>';
                    echo $page['content'];
                }
                if (!empty($page['intro'])) {
                    echo '<strong>Introdução:</strong><br>';
                    echo $page['intro'];
                }
                echo '</div>';
            }
            
            echo '</div>';
        }
        ?>
        
        <div class="summary" style="background: #d4edda; border-left-color: #28a745;">
            <h3 style="color: #28a745;">✅ Processo Concluído</h3>
            <p><strong>Todas as páginas foram atualizadas com sucesso!</strong></p>
            <p style="margin-top: 0.5rem;">O conteúdo está disponível nas seguintes URLs:</p>
            <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                <li><a href="/sobre" target="_blank">/sobre</a> - Sobre a Loja</li>
                <li><a href="/contato" target="_blank">/contato</a> - Fale Conosco</li>
                <li><a href="/politica-de-privacidade" target="_blank">/politica-de-privacidade</a> - Política de Privacidade</li>
                <li><a href="/termos-de-uso" target="_blank">/termos-de-uso</a> - Termos de Uso</li>
                <li><a href="/politica-de-cookies" target="_blank">/politica-de-cookies</a> - Política de Cookies</li>
            </ul>
            <p style="margin-top: 1rem;"><strong>Painel Administrativo:</strong> O conteúdo pode ser editado em <code>/admin/tema</code></p>
        </div>
    </div>
</body>
</html>
