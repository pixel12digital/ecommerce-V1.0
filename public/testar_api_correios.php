<?php
/**
 * Script para testar conexão com API Correios CWS
 * Busca credenciais do banco e executa testes completos
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
use App\Services\Shipping\CorreiosTokenService;
use App\Tenant\TenantContext;

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Teste API Correios</title>";
echo "<style>
body{font-family:Arial,sans-serif;padding:20px;max-width:1000px;margin:0 auto;background:#f5f5f5;}
.container{background:white;padding:30px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
h1{color:#0066cc;margin-top:0;}
h2{color:#333;border-bottom:2px solid #0066cc;padding-bottom:10px;}
.step{margin:20px 0;padding:15px;border-left:4px solid #ddd;background:#fafafa;border-radius:4px;}
.step.ok{border-left-color:#28a745;background:#d4edda;}
.step.error{border-left-color:#dc3545;background:#f8d7da;}
.step.warning{border-left-color:#ffc107;background:#fff3cd;}
.step-title{font-weight:600;margin-bottom:10px;color:#333;}
.step-content{color:#666;font-size:14px;}
pre{background:#f4f4f4;padding:15px;border-radius:5px;overflow-x:auto;font-size:12px;}
code{background:#f4f4f4;padding:2px 6px;border-radius:3px;font-family:monospace;}
.success{color:#28a745;font-weight:600;}
.error{color:#dc3545;font-weight:600;}
.warning{color:#856404;font-weight:600;}
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🧪 Teste de Conexão API Correios CWS</h1>";

try {
    $db = Database::getConnection();
    echo "<div class='step ok'><div class='step-title'>✅ Conectado ao banco remoto</div></div>";
    
    // Buscar credenciais do banco
    $tenantId = 1; // Default
    try {
        $tenantId = TenantContext::id();
    } catch (\Exception $e) {
        // Usar default
    }
    
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
        throw new \Exception('Configuração Correios não encontrada no banco. Configure primeiro no painel admin.');
    }
    
    $json = json_decode($gateway['config_json'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception('JSON inválido: ' . json_last_error_msg());
    }
    
    $correios = $json['correios'] ?? $json;
    $credenciais = $correios['credenciais'] ?? [];
    $origem = $correios['origem'] ?? [];
    
    $usuario = $credenciais['usuario'] ?? '';
    $chaveCws = $credenciais['chave_acesso_cws'] ?? '';
    $cepOrigem = $origem['cep'] ?? '';
    
    if (empty($usuario) || empty($chaveCws)) {
        throw new \Exception('Usuário ou Chave de Acesso CWS não encontrados no banco.');
    }
    
    if (empty($cepOrigem) || $cepOrigem === '00000000') {
        throw new \Exception('CEP de origem inválido ou não configurado.');
    }
    
    echo "<div class='step ok'>";
    echo "<div class='step-title'>📋 Credenciais Encontradas</div>";
    echo "<div class='step-content'>";
    echo "Usuário: " . substr($usuario, 0, 3) . "***<br>";
    echo "Chave CWS: " . substr($chaveCws, 0, 10) . "... (tamanho: " . strlen($chaveCws) . " caracteres)<br>";
    echo "CEP Origem: {$cepOrigem}<br>";
    echo "</div></div>";
    
    // Teste 1: Gerar Token
    echo "<div class='step'>";
    echo "<div class='step-title'>[1/3] 🔑 Gerando Token de Autenticação</div>";
    echo "<div class='step-content'>";
    
    try {
        $token = CorreiosTokenService::getToken($usuario, $chaveCws, $tenantId);
        
        if (empty($token)) {
            throw new \Exception('Token vazio retornado');
        }
        
        echo "<span class='success'>✅ Token gerado com sucesso!</span><br>";
        echo "Token: <code>" . substr($token, 0, 30) . "..." . substr($token, -20) . "</code><br>";
        echo "Tamanho: " . strlen($token) . " caracteres";
        
        $tokenOk = true;
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro ao gerar token: " . htmlspecialchars($e->getMessage()) . "</span>";
        $tokenOk = false;
        $token = null;
    }
    
    echo "</div></div>";
    
    if (!$tokenOk) {
        echo "<div class='step error'>";
        echo "<div class='step-title'>❌ Teste Interrompido</div>";
        echo "<div class='step-content'>Não foi possível gerar o token. Verifique as credenciais.</div>";
        echo "</div>";
        echo "</div></body></html>";
        exit;
    }
    
    // Teste 2: Consultar Preço v3 (PAC)
    echo "<div class='step'>";
    echo "<div class='step-title'>[2/3] 💰 Consultando Preço v3 (PAC - 40126)</div>";
    echo "<div class='step-content'>";
    
    $cepDestino = '20040020'; // CEP de teste (Centro, RJ)
    $peso = 0.3;
    $comprimento = 20;
    $largura = 20;
    $altura = 10;
    
    try {
        $url = 'https://api.correios.com.br/preco/v3/nacional/40126';
        
        $payload = [
            'cepOrigem' => $cepOrigem,
            'cepDestino' => $cepDestino,
            'peso' => $peso,
            'comprimento' => $comprimento,
            'largura' => $largura,
            'altura' => $altura,
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curlError)) {
            throw new \Exception('Erro de conexão: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = @json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['mensagem']) 
                ? $errorData['mensagem'] 
                : "HTTP {$httpCode}";
            throw new \Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Resposta JSON inválida: ' . json_last_error_msg());
        }
        
        $preco = $data['preco'] ?? $data['valor'] ?? null;
        
        echo "<span class='success'>✅ Preço consultado com sucesso!</span><br>";
        echo "CEP Origem: {$cepOrigem}<br>";
        echo "CEP Destino: {$cepDestino}<br>";
        echo "Peso: {$peso} kg<br>";
        echo "Dimensões: {$comprimento} x {$largura} x {$altura} cm<br>";
        
        if ($preco !== null) {
            echo "<strong>Preço: R$ " . number_format($preco, 2, ',', '.') . "</strong><br>";
        }
        
        echo "<details><summary>Ver resposta completa</summary>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
        echo "</details>";
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro ao consultar preço: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    
    echo "</div></div>";
    
    // Teste 3: Consultar Prazo v3 (PAC)
    echo "<div class='step'>";
    echo "<div class='step-title'>[3/3] ⏱️ Consultando Prazo v3 (PAC - 40126)</div>";
    echo "<div class='step-content'>";
    
    try {
        $url = 'https://api.correios.com.br/prazo/v3/nacional/40126';
        
        $payload = [
            'cepOrigem' => $cepOrigem,
            'cepDestino' => $cepDestino,
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false || !empty($curlError)) {
            throw new \Exception('Erro de conexão: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $errorData = @json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['mensagem']) 
                ? $errorData['mensagem'] 
                : "HTTP {$httpCode}";
            throw new \Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Resposta JSON inválida: ' . json_last_error_msg());
        }
        
        $prazo = $data['prazo'] ?? $data['prazoEntrega'] ?? null;
        
        echo "<span class='success'>✅ Prazo consultado com sucesso!</span><br>";
        echo "CEP Origem: {$cepOrigem}<br>";
        echo "CEP Destino: {$cepDestino}<br>";
        
        if ($prazo !== null) {
            echo "<strong>Prazo: {$prazo} dia(s)</strong><br>";
        }
        
        echo "<details><summary>Ver resposta completa</summary>";
        echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . "</pre>";
        echo "</details>";
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro ao consultar prazo: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    
    echo "</div></div>";
    
    // Resumo final
    echo "<div class='step ok'>";
    echo "<div class='step-title'>✅ Teste Concluído</div>";
    echo "<div class='step-content'>";
    echo "<p><strong>Todos os testes foram executados!</strong></p>";
    echo "<p>Se todos os testes passaram, a integração com a API Correios CWS está funcionando corretamente.</p>";
    echo "<p>Você pode agora usar o sistema para calcular fretes no checkout.</p>";
    echo "</div></div>";
    
} catch (\Exception $e) {
    echo "<div class='step error'>";
    echo "<div class='step-title'>❌ Erro</div>";
    echo "<div class='step-content'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
}

echo "</div></body></html>";
