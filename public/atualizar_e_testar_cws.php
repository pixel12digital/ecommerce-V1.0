<?php
/**
 * Script para atualizar chave CWS e executar testes
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

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Atualizar e Testar CWS</title>";
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
.success{color:#28a745;font-weight:600;}
.error{color:#dc3545;font-weight:600;}
</style>";
echo "</head><body><div class='container'>";
echo "<h1>🔄 Atualizar Chave CWS e Testar</h1>";

try {
    $db = Database::getConnection();
    
    $tenantId = 1;
    $novaChaveCws = 'cws-ch1_3PdqDcHf6ku1makUEo6MjQyOTUzNDYwMDAxMjI6OTkxMjczMDY0Mg_MTpQbDA6xeA7QGayVLZhAW7';
    
    // Buscar configuração atual
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
        throw new \Exception('Configuração Correios não encontrada.');
    }
    
    $json = json_decode($gateway['config_json'], true);
    $correios = $json['correios'] ?? $json;
    $credenciais = $correios['credenciais'] ?? [];
    $origem = $correios['origem'] ?? [];
    
    $usuario = $credenciais['usuario'] ?? '';
    $cepOrigem = $origem['cep'] ?? '';
    
    if (empty($usuario)) {
        throw new \Exception('Usuário não encontrado no banco.');
    }
    
    // Atualizar chave CWS
    echo "<div class='step ok'>";
    echo "<div class='step-title'>✅ Chave CWS Atualizada</div>";
    echo "<div class='step-content'>";
    echo "Nova chave: <code>" . substr($novaChaveCws, 0, 20) . "...</code> (tamanho: " . strlen($novaChaveCws) . " caracteres)<br>";
    echo "</div></div>";
    
    // Teste 1: Gerar Token
    echo "<div class='step'>";
    echo "<div class='step-title'>[1/3] 🔑 Gerando Token</div>";
    echo "<div class='step-content'>";
    
    try {
        $token = CorreiosTokenService::getToken($usuario, $novaChaveCws, $tenantId);
        
        if (empty($token)) {
            throw new \Exception('Token vazio');
        }
        
        echo "<span class='success'>✅ Token gerado!</span><br>";
        echo "Token: <code>" . substr($token, 0, 30) . "..." . substr($token, -20) . "</code>";
        $tokenOk = true;
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span>";
        $tokenOk = false;
        $token = null;
    }
    
    echo "</div></div>";
    
    if (!$tokenOk) {
        echo "<div class='step error'><div class='step-title'>❌ Teste Interrompido</div></div>";
        echo "</div></body></html>";
        exit;
    }
    
    // Teste 2: Preço
    echo "<div class='step'>";
    echo "<div class='step-title'>[2/3] 💰 Consultando Preço (PAC)</div>";
    echo "<div class='step-content'>";
    
    $cepDestino = '20040020';
    try {
        $url = 'https://api.correios.com.br/preco/v3/nacional/40126';
        $payload = [
            'cepOrigem' => $cepOrigem,
            'cepDestino' => $cepDestino,
            'peso' => 0.3,
            'comprimento' => 20,
            'largura' => 20,
            'altura' => 10,
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorData = @json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['mensagem']) 
                ? $errorData['mensagem'] 
                : "HTTP {$httpCode}";
            throw new \Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        $preco = $data['preco'] ?? $data['valor'] ?? null;
        
        echo "<span class='success'>✅ Preço consultado!</span><br>";
        if ($preco !== null) {
            echo "<strong>Preço: R$ " . number_format($preco, 2, ',', '.') . "</strong>";
        }
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    
    echo "</div></div>";
    
    // Teste 3: Prazo
    echo "<div class='step'>";
    echo "<div class='step-title'>[3/3] ⏱️ Consultando Prazo (PAC)</div>";
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
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $errorData = @json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['mensagem']) 
                ? $errorData['mensagem'] 
                : "HTTP {$httpCode}";
            throw new \Exception($errorMsg);
        }
        
        $data = json_decode($response, true);
        $prazo = $data['prazo'] ?? $data['prazoEntrega'] ?? null;
        
        echo "<span class='success'>✅ Prazo consultado!</span><br>";
        if ($prazo !== null) {
            echo "<strong>Prazo: {$prazo} dia(s)</strong>";
        }
        
    } catch (\Exception $e) {
        echo "<span class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</span>";
    }
    
    echo "</div></div>";
    
    // Resumo
    echo "<div class='step ok'>";
    echo "<div class='step-title'>✅ Testes Concluídos</div>";
    echo "<div class='step-content'>";
    echo "<p><strong>Testes executados com a nova chave CWS!</strong></p>";
    echo "<p>Se todos os testes passaram, a chave está funcionando corretamente.</p>";
    echo "<p><strong>Importante:</strong> Atualize a chave no painel admin para salvar permanentemente.</p>";
    echo "</div></div>";
    
} catch (\Exception $e) {
    echo "<div class='step error'>";
    echo "<div class='step-title'>❌ Erro</div>";
    echo "<div class='step-content'>" . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div>";
}

echo "</div></body></html>";
