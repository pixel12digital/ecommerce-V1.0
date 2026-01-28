<?php
/**
 * Teste API Preço com GET e outras variações
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

echo "=== Teste API Preço com variações ===\n\n";

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

echo "Token gerado: " . substr($token, 0, 20) . "...\n\n";

// 2. Testar com GET e query parameters
echo "--- Teste 1: GET com query parameters ---\n";
$url1 = 'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico . 
        '?cepOrigem=' . $cepOrigem . 
        '&cepDestino=' . $cepDestino . 
        '&peso=' . $peso . 
        '&comprimento=' . $comprimento . 
        '&largura=' . $largura . 
        '&altura=' . $altura;

$ch1 = curl_init($url1);
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "URL: {$url1}\n";
echo "HTTP Code: {$httpCode1}\n";
if ($httpCode1 === 200 || $httpCode1 === 201) {
    echo "✅ SUCESSO!\n";
    $data1 = json_decode($response1, true);
    print_r($data1);
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response1, 0, 300) . "\n";
}
echo "\n";

// 3. Verificar APIs disponíveis no token
echo "--- APIs disponíveis no token ---\n";
if (isset($dataToken['contrato']['apis'])) {
    echo "APIs do contrato:\n";
    foreach ($dataToken['contrato']['apis'] as $api) {
        echo "  - API ID: " . ($api['api'] ?? 'N/A') . "\n";
        if (isset($api['grupos'])) {
            foreach ($api['grupos'] as $grupo) {
                echo "    Grupos: " . ($grupo['co'] ?? 'N/A') . "\n";
            }
        }
    }
    echo "\n";
    echo "Nota: Verifique na documentação dos Correios qual API ID corresponde a Preço v3\n";
    echo "Possíveis IDs: 34, 35, 41, 76, 78, 87, 566, 586, 587\n";
}

echo "\n=== Fim do teste ===\n";
