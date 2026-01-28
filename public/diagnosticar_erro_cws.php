<?php
/**
 * Script de diagnóstico detalhado para erro de credenciais CWS
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico CWS</title>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;max-width:1000px;margin:0 auto;background:#f5f5f5;}
.container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
h1{color:#0066cc;margin-top:0;}
.step{margin:20px 0;padding:15px;border-left:4px solid #ddd;background:#fafafa;border-radius:4px;}
.step.ok{border-left-color:#28a745;background:#d4edda;}
.step.error{border-left-color:#dc3545;background:#f8d7da;}
.step.warning{border-left-color:#ffc107;background:#fff3cd;}
.step-title{font-weight:600;margin-bottom:10px;color:#333;}
.step-content{color:#666;font-size:14px;}
pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;font-size:12px;}
code{background:#f4f4f4;padding:2px 6px;border-radius:3px;font-family:monospace;}
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🔍 Diagnóstico de Erro CWS</h1>";

try {
    $db = Database::getConnection();
    
    $tenantId = 1;
    
    // Buscar configuração
    $stmt = $db->prepare("
        SELECT config_json 
        FROM tenant_gateways 
        WHERE tenant_id = :tenant_id 
        AND tipo = 'shipping'
        AND codigo = 'correios'
        LIMIT 1
    ");
    $stmt->execute(['tenant_id' => $tenantId]);
    $gateway = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$gateway || empty($gateway['config_json'])) {
        throw new \Exception('Configuração não encontrada.');
    }
    
    $json = json_decode($gateway['config_json'], true);
    $correios = $json['correios'] ?? $json;
    $credenciais = $correios['credenciais'] ?? [];
    
    $usuario = $credenciais['usuario'] ?? '';
    $chaveCws = $credenciais['chave_acesso_cws'] ?? '';
    
    echo "<div class='step ok'>";
    echo "<div class='step-title'>📋 Credenciais do Banco</div>";
    echo "<div class='step-content'>";
    echo "Usuário: <code>" . htmlspecialchars($usuario) . "</code> (tamanho: " . strlen($usuario) . " caracteres)<br>";
    echo "Chave CWS: <code>" . htmlspecialchars($chaveCws) . "</code> (tamanho: " . strlen($chaveCws) . " caracteres)<br>";
    echo "</div></div>";
    
    // Testar com a nova chave fornecida
    $novaChave = 'cws-ch1_3PdqDcHf6ku1makUEo6MjQyOTUzNDYwMDAxMjI6OTkxMjczMDY0Mg_MTpQbDA6xeA7QGayVLZhAW7';
    
    echo "<div class='step warning'>";
    echo "<div class='step-title'>🔄 Testando com Nova Chave</div>";
    echo "<div class='step-content'>";
    echo "Nova chave: <code>" . htmlspecialchars($novaChave) . "</code> (tamanho: " . strlen($novaChave) . " caracteres)<br>";
    echo "</div></div>";
    
    // Teste detalhado de autenticação
    echo "<div class='step'>";
    echo "<div class='step-title'>🔑 Teste de Autenticação Detalhado</div>";
    echo "<div class='step-content'>";
    
    $url = 'https://api.correios.com.br/token/v1/autentica';
    
    // Preparar Basic Auth
    $auth = base64_encode($usuario . ':' . $novaChave);
    
    echo "URL: <code>{$url}</code><br>";
    echo "Método: POST<br>";
    echo "Authorization: Basic <code>" . substr($auth, 0, 20) . "...</code><br>";
    echo "Usuário usado: <code>" . htmlspecialchars($usuario) . "</code><br>";
    echo "Chave usada: <code>" . substr($novaChave, 0, 20) . "...</code><br><br>";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_VERBOSE => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);
    
    echo "<strong>Resultado:</strong><br>";
    
    if ($response === false || !empty($curlError)) {
        echo "<span style='color:red;'>❌ Erro de conexão: " . htmlspecialchars($curlError) . "</span><br>";
    } else {
        echo "HTTP Code: <strong>" . $httpCode . "</strong><br>";
        
        if ($httpCode === 200) {
            echo "<span style='color:green;'>✅ Autenticação bem-sucedida!</span><br>";
            $data = json_decode($response, true);
            if ($data) {
                echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            }
        } else {
            echo "<span style='color:red;'>❌ Erro HTTP {$httpCode}</span><br>";
            echo "<strong>Resposta da API:</strong><br>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
            
            $errorData = @json_decode($response, true);
            if (is_array($errorData)) {
                echo "<strong>Erro decodificado:</strong><br>";
                echo "<pre>" . htmlspecialchars(json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            }
            
            if ($httpCode === 401) {
                echo "<div style='margin-top:15px;padding:15px;background:#fff3cd;border-radius:5px;'>";
                echo "<strong>⚠️ Possíveis causas do erro 401:</strong><ul>";
                echo "<li>A chave CWS pode não estar ativa ainda (aguarde alguns minutos após gerar)</li>";
                echo "<li>O usuário pode estar incorreto</li>";
                echo "<li>A chave CWS pode estar incorreta ou incompleta</li>";
                echo "<li>O formato da autenticação pode estar incorreto</li>";
                echo "</ul></div>";
            }
        }
    }
    
    echo "</div></div>";
    
    // Verificar se há espaços ou caracteres especiais
    echo "<div class='step'>";
    echo "<div class='step-title'>🔍 Verificação de Formato</div>";
    echo "<div class='step-content'>";
    
    $usuarioTrim = trim($usuario);
    $chaveTrim = trim($novaChave);
    
    echo "Usuário original: <code>" . htmlspecialchars($usuario) . "</code> (tamanho: " . strlen($usuario) . ")<br>";
    echo "Usuário após trim: <code>" . htmlspecialchars($usuarioTrim) . "</code> (tamanho: " . strlen($usuarioTrim) . ")<br>";
    if ($usuario !== $usuarioTrim) {
        echo "<span style='color:orange;'>⚠️ Usuário tem espaços em branco!</span><br>";
    }
    
    echo "Chave original: <code>" . htmlspecialchars($novaChave) . "</code> (tamanho: " . strlen($novaChave) . ")<br>";
    echo "Chave após trim: <code>" . htmlspecialchars($chaveTrim) . "</code> (tamanho: " . strlen($chaveTrim) . ")<br>";
    if ($novaChave !== $chaveTrim) {
        echo "<span style='color:orange;'>⚠️ Chave tem espaços em branco!</span><br>";
    }
    
    // Verificar se a chave começa com "cws-"
    if (strpos($chaveTrim, 'cws-') !== 0) {
        echo "<span style='color:orange;'>⚠️ Chave não começa com 'cws-'</span><br>";
    } else {
        echo "<span style='color:green;'>✅ Chave começa com 'cws-'</span><br>";
    }
    
    echo "</div></div>";
    
} catch (\Exception $e) {
    echo "<div class='step error'>";
    echo "<div class='step-title'>❌ Erro</div>";
    echo "<div class='step-content'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
}

echo "</div></body></html>";
