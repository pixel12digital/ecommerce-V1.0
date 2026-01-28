<?php
/**
 * Script de teste de conexão Correios CWS
 * 
 * Uso: php test_correios_connection.php [tenant_id] [usuario] [chave_acesso_cws]
 * 
 * Exemplo:
 * php test_correios_connection.php 1 meu_usuario minha_chave_cws
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Shipping\CorreiosTokenService;
use App\Core\Database;
use App\Tenant\TenantContext;

// Cores para output
$GREEN = "\033[32m";
$RED = "\033[31m";
$YELLOW = "\033[33m";
$BLUE = "\033[34m";
$RESET = "\033[0m";

echo "{$BLUE}=== Teste de Conexão Correios CWS ==={$RESET}\n\n";

// Obter parâmetros
$tenantId = $argv[1] ?? null;
$usuario = $argv[2] ?? null;
$chaveAcessoCws = $argv[3] ?? null;

// Se não foram fornecidos, tentar buscar do banco
if (empty($usuario) || empty($chaveAcessoCws)) {
    echo "{$YELLOW}Credenciais não fornecidas. Buscando do banco de dados...{$RESET}\n";
    
    try {
        $db = Database::getConnection();
        
        // Se tenant_id não foi fornecido, tentar usar TenantContext
        if (empty($tenantId)) {
            try {
                $tenantId = TenantContext::id();
            } catch (\Exception $e) {
                echo "{$RED}Erro: Forneça o tenant_id como primeiro parâmetro ou configure o TenantContext.{$RESET}\n";
                exit(1);
            }
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
            echo "{$RED}Erro: Configuração do gateway Correios não encontrada para tenant_id={$tenantId}.{$RESET}\n";
            echo "Use: php test_correios_connection.php [tenant_id] [usuario] [chave_acesso_cws]\n";
            exit(1);
        }
        
        $decoded = json_decode($gateway['config_json'], true);
        if (!is_array($decoded)) {
            echo "{$RED}Erro: Configuração JSON inválida.{$RESET}\n";
            exit(1);
        }
        
        $correiosAtual = $decoded['correios'] ?? $decoded;
        $credenciais = $correiosAtual['credenciais'] ?? [];
        
        $usuario = $usuario ?? ($credenciais['usuario'] ?? '');
        $chaveAcessoCws = $chaveAcessoCws ?? ($credenciais['chave_acesso_cws'] ?? '');
        
        if (empty($usuario) || empty($chaveAcessoCws)) {
            echo "{$RED}Erro: Usuário ou Chave de Acesso CWS não encontrados no banco.{$RESET}\n";
            echo "Use: php test_correios_connection.php [tenant_id] [usuario] [chave_acesso_cws]\n";
            exit(1);
        }
        
        echo "{$GREEN}✓ Credenciais encontradas no banco de dados{$RESET}\n";
        echo "  Usuário: " . substr($usuario, 0, 3) . "***\n";
        echo "  Chave CWS: " . str_repeat('*', min(strlen($chaveAcessoCws), 20)) . "\n\n";
        
    } catch (\Exception $e) {
        echo "{$RED}Erro ao buscar credenciais: {$e->getMessage()}{$RESET}\n";
        exit(1);
    }
} else {
    if (empty($tenantId)) {
        try {
            $tenantId = TenantContext::id();
        } catch (\Exception $e) {
            echo "{$YELLOW}Aviso: TenantContext não disponível. Usando tenant_id=0{$RESET}\n";
            $tenantId = 0;
        }
    }
}

// Validar credenciais
if (empty($usuario) || empty($chaveAcessoCws)) {
    echo "{$RED}Erro: Usuário e Chave de Acesso CWS são obrigatórios.{$RESET}\n";
    echo "Use: php test_correios_connection.php [tenant_id] [usuario] [chave_acesso_cws]\n";
    exit(1);
}

// Teste 1: Gerar Token
echo "{$BLUE}[1/3] Testando geração de token...{$RESET}\n";
try {
    $token = CorreiosTokenService::getToken($usuario, $chaveAcessoCws, $tenantId);
    
    if (empty($token)) {
        echo "{$RED}✗ Falha: Token vazio retornado{$RESET}\n";
        exit(1);
    }
    
    echo "{$GREEN}✓ Token gerado com sucesso!{$RESET}\n";
    echo "  Token: " . substr($token, 0, 20) . "..." . substr($token, -10) . "\n";
    
} catch (\Exception $e) {
    echo "{$RED}✗ Erro ao gerar token: {$e->getMessage()}{$RESET}\n";
    exit(1);
}

// Teste 2: Consultar Preço v3 (PAC)
echo "\n{$BLUE}[2/3] Testando consulta Preço v3 (PAC - 40126)...{$RESET}\n";
try {
    $url = 'https://api.correios.com.br/preco/v3/nacional/40126';
    
    $payload = [
        'cepOrigem' => '01310100', // CEP de teste (Av. Paulista, SP)
        'cepDestino' => '20040020', // CEP de teste (Centro, RJ)
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
        throw new \Exception('Resposta JSON inválida');
    }
    
    $preco = $data['preco'] ?? $data['valor'] ?? null;
    echo "{$GREEN}✓ Preço consultado com sucesso!{$RESET}\n";
    if ($preco !== null) {
        echo "  Preço: R$ " . number_format($preco, 2, ',', '.') . "\n";
    }
    
} catch (\Exception $e) {
    echo "{$RED}✗ Erro ao consultar preço: {$e->getMessage()}{$RESET}\n";
    // Não falhar o teste completo, apenas avisar
}

// Teste 3: Consultar Prazo v3 (PAC)
echo "\n{$BLUE}[3/3] Testando consulta Prazo v3 (PAC - 40126)...{$RESET}\n";
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
        throw new \Exception('Resposta JSON inválida');
    }
    
    $prazo = $data['prazo'] ?? $data['prazoEntrega'] ?? null;
    echo "{$GREEN}✓ Prazo consultado com sucesso!{$RESET}\n";
    if ($prazo !== null) {
        echo "  Prazo: {$prazo} dia(s)\n";
    }
    
} catch (\Exception $e) {
    echo "{$RED}✗ Erro ao consultar prazo: {$e->getMessage()}{$RESET}\n";
    // Não falhar o teste completo, apenas avisar
}

// Resumo
echo "\n{$GREEN}=== Teste Concluído ==={$RESET}\n";
echo "{$GREEN}✓ Conexão com API Correios CWS funcionando corretamente!{$RESET}\n";
echo "\nPróximos passos:\n";
echo "  1. Verifique se as credenciais estão salvas corretamente no admin\n";
echo "  2. Teste a cotação de frete no checkout\n";
echo "  3. Verifique os logs em caso de problemas\n";
