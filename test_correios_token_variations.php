<?php
/**
 * Teste com variações de usuário
 * O usuário pode ser diferente do número do contrato
 */

$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

// Possíveis variações de usuário
$usuariosPossiveis = [
    '9912730642', // Mesmo que o contrato
    '991273064',  // Sem último dígito
    '99127306',   // Sem últimos 2 dígitos
    // Adicione outros possíveis valores aqui
];

echo "=== Teste com variações de usuário ===\n\n";
echo "Código de Acesso: " . substr($codigoAcessoApis, 0, 10) . "...\n";
echo "Contrato: {$contrato}\n\n";

foreach ($usuariosPossiveis as $usuario) {
    echo "--- Testando com usuário: {$usuario} ---\n";
    
    $url = 'https://api.correios.com.br/token/v1/autentica/contrato';
    $auth = base64_encode($usuario . ':' . $codigoAcessoApis);
    $body = json_encode(['numero' => $contrato]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200 || $httpCode === 201) {
        $data = json_decode($response, true);
        echo "✅ SUCESSO! Token: " . ($data['token'] ?? $data['access_token'] ?? 'NÃO ENCONTRADO') . "\n";
        if (isset($data['expiraEm'])) {
            echo "Expira em: " . date('Y-m-d H:i:s', $data['expiraEm']) . "\n";
        }
        echo "\n";
        break; // Parar no primeiro sucesso
    } else {
        echo "❌ Falhou\n";
        echo "Response: " . substr($response, 0, 200) . "\n\n";
    }
}

echo "\n=== IMPORTANTE ===\n";
echo "O usuário correto é o que você usa para fazer login no portal CWS (Meu Correios).\n";
echo "Pode ser:\n";
echo "- Seu e-mail cadastrado\n";
echo "- Seu CPF/CNPJ\n";
echo "- Um ID de usuário específico\n";
echo "\nO número do contrato ({$contrato}) pode ser diferente do usuário.\n";
