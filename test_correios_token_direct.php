<?php
/**
 * Teste direto do token Correios CWS
 * Testa com as credenciais fornecidas pelo usuário
 */

$usuario = '9912730642'; // Assumindo que o usuário é o mesmo que o contrato, ou pode ser diferente
$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

echo "=== Teste de Token Correios CWS ===\n\n";
echo "Usuário: {$usuario}\n";
echo "Código de Acesso: " . substr($codigoAcessoApis, 0, 10) . "...\n";
echo "Contrato: {$contrato}\n\n";

// Teste 1: Endpoint sem contrato
echo "--- Teste 1: Endpoint /autentica (sem contrato) ---\n";
$url1 = 'https://api.correios.com.br/token/v1/autentica';
$auth = base64_encode($usuario . ':' . $codigoAcessoApis);

$ch1 = curl_init($url1);
curl_setopt_array($ch1, [
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
    CURLOPT_VERBOSE => true,
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
$curlError1 = curl_error($ch1);
$curlInfo1 = curl_getinfo($ch1);
curl_close($ch1);

echo "HTTP Code: {$httpCode1}\n";
if ($curlError1) {
    echo "Erro cURL: {$curlError1}\n";
}
echo "Response: " . substr($response1, 0, 500) . "\n";
echo "Response completa: " . $response1 . "\n\n";

// Teste 2: Endpoint com contrato
echo "--- Teste 2: Endpoint /autentica/contrato (com contrato) ---\n";
$url2 = 'https://api.correios.com.br/token/v1/autentica/contrato';
$body2 = json_encode(['numero' => $contrato]);

$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => $body2,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_VERBOSE => true,
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlError2 = curl_error($ch2);
$curlInfo2 = curl_getinfo($ch2);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
if ($curlError2) {
    echo "Erro cURL: {$curlError2}\n";
}
echo "Body enviado: {$body2}\n";
echo "Response: " . substr($response2, 0, 500) . "\n";
echo "Response completa: " . $response2 . "\n\n";

// Decodificar respostas
if ($httpCode1 === 200 || $httpCode1 === 201) {
    $data1 = json_decode($response1, true);
    echo "Token (teste 1): " . ($data1['token'] ?? $data1['access_token'] ?? 'NÃO ENCONTRADO') . "\n";
    if (isset($data1['expiraEm'])) {
        echo "Expira em: " . date('Y-m-d H:i:s', $data1['expiraEm']) . "\n";
    }
}

if ($httpCode2 === 200 || $httpCode2 === 201) {
    $data2 = json_decode($response2, true);
    echo "Token (teste 2): " . ($data2['token'] ?? $data2['access_token'] ?? 'NÃO ENCONTRADO') . "\n";
    if (isset($data2['expiraEm'])) {
        echo "Expira em: " . date('Y-m-d H:i:s', $data2['expiraEm']) . "\n";
    }
}

echo "\n=== Fim do teste ===\n";
