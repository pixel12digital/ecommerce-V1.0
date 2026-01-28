<?php
/**
 * Teste API Preço v1 com formato correto
 */

// Credenciais
$usuario = '24295346000122';
$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

// Dados do teste
$cepOrigem = '89046650';
$cepDestino = '89046650';
$peso = 0.3;
$comprimento = 20;
$largura = 20;
$altura = 10;
$codigoServico = '40126'; // PAC

echo "=== Teste API Preço v1 com formato correto ===\n\n";

// 1. Gerar token
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

$dataToken = json_decode($responseToken, true);
$token = $dataToken['token'] ?? null;

if (empty($token)) {
    echo "❌ Erro ao gerar token\n";
    exit(1);
}

echo "Token gerado\n\n";

// 2. Testar com psObjeto (formato objeto)
echo "--- Teste 1: POST com psObjeto ---\n";
$url1 = 'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico;

// Formato psObjeto (objeto JSON)
$payload1 = [
    'psObjeto' => [
        'cepOrigem' => $cepOrigem,
        'cepDestino' => $cepDestino,
        'peso' => $peso,
        'comprimento' => $comprimento,
        'largura' => $largura,
        'altura' => $altura,
    ]
];

$ch1 = curl_init($url1);
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload1),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP Code: {$httpCode1}\n";
if ($httpCode1 === 200 || $httpCode1 === 201) {
    echo "✅ SUCESSO!\n";
    $data1 = json_decode($response1, true);
    print_r($data1);
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response1, 0, 500) . "\n";
}
echo "\n";

// 3. Testar formato direto (sem psObjeto)
echo "--- Teste 2: POST formato direto ---\n";
$payload2 = [
    'cepOrigem' => $cepOrigem,
    'cepDestino' => $cepDestino,
    'peso' => $peso,
    'comprimento' => $comprimento,
    'largura' => $largura,
    'altura' => $altura,
];

$ch2 = curl_init($url1);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload2),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
if ($httpCode2 === 200 || $httpCode2 === 201) {
    echo "✅ SUCESSO!\n";
    $data2 = json_decode($response2, true);
    print_r($data2);
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response2, 0, 500) . "\n";
}

echo "\n=== Fim do teste ===\n";
