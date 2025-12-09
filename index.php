<?php
/**
 * index.php de fallback para hostings que apontam o DocumentRoot para a raiz (public_html)
 * 
 * Este arquivo serve como "ponte" quando o provedor de hospedagem não permite
 * configurar o DocumentRoot para apontar diretamente para public/ ou quando
 * o .htaccess não está sendo processado corretamente.
 * 
 * IMPORTANTE: Este arquivo NÃO substitui public/index.php. Ele apenas redireciona
 * para o front controller real, garantindo que a aplicação funcione mesmo em
 * hostings compartilhados com restrições de configuração.
 * 
 * Compatível com:
 * - Instalações independentes (APP_MODE=single)
 * - Instalações multi-tenant (APP_MODE=multi)
 */

// Evitar loop se, por algum motivo, já estivermos dentro de public/
$publicIndex = __DIR__ . '/public/index.php';

if (!file_exists($publicIndex)) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Erro de Configuração</title>
</head>
<body>
    <h1>Erro de Configuração</h1>
    <p>O arquivo <code>public/index.php</code> não foi encontrado.</p>
    <p>Verifique se a estrutura de diretórios está correta.</p>
</body>
</html>';
    exit;
}

// Incluir o front controller real
require $publicIndex;

