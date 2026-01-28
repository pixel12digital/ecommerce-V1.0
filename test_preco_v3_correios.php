<?php
/**
 * Teste direto da API Preço v3 dos Correios
 */

// Credenciais
$usuario = '24295346000122';
$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

// Dados do teste
$cepOrigem = '89046650'; // CEP de origem (Blumenau)
$cepDestino = '89046650'; // CEP de destino (mesmo para teste)
$peso = 0.3;
$comprimento = 20;
$largura = 20;
$altura = 10;
$codigoServico = '40126'; // PAC

echo "=== Teste API Preço v3 Correios ===\n\n";

// 1. Gerar token primeiro
echo "1. Gerando token...\n";
$urlToken = 'https://api.correios.com.br/token/v1/autentica/contrato';
$auth = base64_encode($usuario . ':' . $codigoAcessoApis);
$bodyToken = json_encode(['numero' => $contrato]);

$chToken = curl_init($urlToken);
curl_setopt_array($chToken, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => $bodyToken,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$responseToken = curl_exec($chToken);
$httpCodeToken = curl_getinfo($chToken, CURLINFO_HTTP_CODE);
curl_close($chToken);

if ($httpCodeToken !== 200 && $httpCodeToken !== 201) {
    echo "❌ Erro ao gerar token: HTTP {$httpCodeToken}\n";
    exit(1);
}

$dataToken = json_decode($responseToken, true);
$token = $dataToken['token'] ?? null;

if (empty($token)) {
    echo "❌ Token não encontrado na resposta\n";
    exit(1);
}

echo "✅ Token gerado: " . substr($token, 0, 20) . "...\n\n";

// 2. Testar diferentes URLs possíveis
$urlsPossiveis = [
    'https://api.correios.com.br/preco/v3/nacional/' . $codigoServico,
    'https://api.correios.com.br/preco/v3/' . $codigoServico,
    'https://api.correios.com.br/preco/v3',
    'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico,
];

$payload = [
    'cepOrigem' => $cepOrigem,
    'cepDestino' => $cepDestino,
    'peso' => $peso,
    'comprimento' => $comprimento,
    'largura' => $largura,
    'altura' => $altura,
];

echo "2. Testando URLs de Preço v3...\n\n";

foreach ($urlsPossiveis as $url) {
    echo "--- Testando: {$url} ---\n";
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200 || $httpCode === 201) {
        echo "✅ SUCESSO!\n";
        $data = json_decode($response, true);
        echo "Response:\n";
        print_r($data);
        echo "\n";
        break;
    } else {
        echo "❌ Falhou\n";
        if (!empty($response)) {
            echo "Response: " . substr($response, 0, 200) . "\n";
        }
        if ($curlError) {
            echo "Erro cURL: {$curlError}\n";
        }
        echo "\n";
    }
}

echo "\n=== Fim do teste ===\n";
