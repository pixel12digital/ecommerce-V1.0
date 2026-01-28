<?php
/**
 * Página de teste de conexão Correios CWS
 * Acesse via: http://localhost/ecommerce-v1.0/public/test_correios.php?tenant_id=1
 */

// Incluir autoload
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Shipping\CorreiosTokenService;
use App\Core\Database;
use App\Tenant\TenantContext;

// Headers
header('Content-Type: text/html; charset=utf-8');

// Obter tenant_id
$tenantId = $_GET['tenant_id'] ?? null;

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão Correios CWS</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0066cc;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            background: #0066cc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        button:hover {
            background: #0052a3;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            display: none;
        }
        .result.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .result.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .result.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .step {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
            border-radius: 4px;
        }
        .step-title {
            font-weight: 600;
            color: #0066cc;
            margin-bottom: 5px;
        }
        .step-content {
            color: #666;
            font-size: 14px;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔌 Teste de Conexão Correios CWS</h1>
        
        <form id="testForm" method="POST">
            <div class="form-group">
                <label for="tenant_id">Tenant ID:</label>
                <input type="text" id="tenant_id" name="tenant_id" value="<?= htmlspecialchars($tenantId ?? '1') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="usuario">Usuário (Meu Correios):</label>
                <input type="text" id="usuario" name="usuario" placeholder="Seu login do Meu Correios (deixe vazio para buscar do banco)">
                <small style="color: #666; font-size: 12px;">Deixe vazio para buscar automaticamente do banco de dados</small>
            </div>
            
            <div class="form-group">
                <label for="chave_acesso_cws">Chave de Acesso CWS:</label>
                <input type="password" id="chave_acesso_cws" name="chave_acesso_cws" placeholder="Sua chave de acesso CWS (deixe vazio para buscar do banco)">
                <small style="color: #666; font-size: 12px;">Deixe vazio para buscar automaticamente do banco de dados</small>
            </div>
            
            <button type="submit" id="btnTest">Executar Teste</button>
        </form>
        
        <div id="result" class="result"><?= $testResult ?? '' ?></div>
    </div>

    <?php
    $testResult = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $tenantId = $_POST['tenant_id'] ?? null;
            $usuario = trim($_POST['usuario'] ?? '');
            $chaveAcessoCws = trim($_POST['chave_acesso_cws'] ?? '');
            
            // Se não foram fornecidos, buscar do banco
            if (empty($usuario) || empty($chaveAcessoCws)) {
                $db = Database::getConnection();
                
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
                
                if ($gateway && !empty($gateway['config_json'])) {
                    $decoded = json_decode($gateway['config_json'], true);
                    if (is_array($decoded)) {
                        $correiosAtual = $decoded['correios'] ?? $decoded;
                        $credenciais = $correiosAtual['credenciais'] ?? [];
                        
                        $usuario = $usuario ?: ($credenciais['usuario'] ?? '');
                        $chaveAcessoCws = $chaveAcessoCws ?: ($credenciais['chave_acesso_cws'] ?? '');
                    }
                }
            }
            
            if (empty($usuario) || empty($chaveAcessoCws)) {
                throw new \Exception('Usuário e Chave de Acesso CWS são obrigatórios. Preencha os campos ou salve a configuração no admin primeiro.');
            }
            
            $result = [];
            
            // Teste 1: Gerar Token
            $result[] = '<div class="step"><div class="step-title">[1/3] Gerando token...</div>';
            try {
                $token = CorreiosTokenService::getToken($usuario, $chaveAcessoCws, $tenantId);
                $result[] = '<div class="step-content">✓ Token gerado com sucesso!<br>Token: ' . substr($token, 0, 20) . '...' . substr($token, -10) . '</div></div>';
                
                // Teste 2: Consultar Preço
                $result[] = '<div class="step"><div class="step-title">[2/3] Consultando Preço v3 (PAC)...</div>';
                try {
                    $url = 'https://api.correios.com.br/preco/v3/nacional/40126';
                    $payload = [
                        'cepOrigem' => '01310100',
                        'cepDestino' => '20040020',
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
                    
                    if ($httpCode === 200) {
                        $data = json_decode($response, true);
                        $preco = $data['preco'] ?? $data['valor'] ?? 'N/A';
                        $result[] = '<div class="step-content">✓ Preço consultado: R$ ' . number_format($preco, 2, ',', '.') . '</div></div>';
                    } else {
                        $result[] = '<div class="step-content">⚠ HTTP ' . $httpCode . '</div></div>';
                    }
                } catch (\Exception $e) {
                    $result[] = '<div class="step-content">⚠ Erro: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                }
                
                // Teste 3: Consultar Prazo
                $result[] = '<div class="step"><div class="step-title">[3/3] Consultando Prazo v3 (PAC)...</div>';
                try {
                    $url = 'https://api.correios.com.br/prazo/v3/nacional/40126';
                    $payload = [
                        'cepOrigem' => '01310100',
                        'cepDestino' => '20040020',
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
                    
                    if ($httpCode === 200) {
                        $data = json_decode($response, true);
                        $prazo = $data['prazo'] ?? $data['prazoEntrega'] ?? 'N/A';
                        $result[] = '<div class="step-content">✓ Prazo consultado: ' . $prazo . ' dia(s)</div></div>';
                    } else {
                        $result[] = '<div class="step-content">⚠ HTTP ' . $httpCode . '</div></div>';
                    }
                } catch (\Exception $e) {
                    $result[] = '<div class="step-content">⚠ Erro: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
                }
                
                $testResult = '<div class="result success" style="display: block;">';
                $testResult .= '<strong>✓ Teste Concluído com Sucesso!</strong><br><br>';
                $testResult .= implode('', $result);
                $testResult .= '</div>';
                
            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                if (strpos($errorMsg, '401') !== false || strpos($errorMsg, 'Credenciais inválidas') !== false) {
                    $errorMsg = 'Credenciais inválidas. Verifique se o Usuário e a Chave de Acesso CWS estão corretos.';
                }
                
                $testResult = '<div class="result error" style="display: block;">';
                $testResult .= '<strong>✗ Erro no Teste</strong><br><br>';
                $testResult .= htmlspecialchars($errorMsg);
                $testResult .= '</div>';
            }
            
        } catch (\Exception $e) {
            $testResult = '<div class="result error" style="display: block;">';
            $testResult .= '<strong>✗ Erro</strong><br><br>';
            $testResult .= htmlspecialchars($e->getMessage());
            $testResult .= '</div>';
        }
    }
    ?>
</body>
</html>
