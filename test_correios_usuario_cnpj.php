<?php
/**
 * Teste com usuário CNPJ fornecido
 */

$usuario = '24295346000122';
$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

echo "=== Teste com Usuário CNPJ ===\n\n";
echo "Usuário: {$usuario}\n";
echo "Código de Acesso: " . substr($codigoAcessoApis, 0, 15) . "...\n";
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
]);

$response1 = curl_exec($ch1);
$httpCode1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
curl_close($ch1);

echo "HTTP Code: {$httpCode1}\n";
if ($httpCode1 === 200 || $httpCode1 === 201) {
    echo "✅ SUCESSO!\n";
    $data1 = json_decode($response1, true);
    echo "Token: " . ($data1['token'] ?? $data1['access_token'] ?? 'NÃO ENCONTRADO') . "\n";
    if (isset($data1['expiraEm'])) {
        $expiraEm = is_numeric($data1['expiraEm']) ? $data1['expiraEm'] : strtotime($data1['expiraEm']);
        echo "Expira em: " . date('Y-m-d H:i:s', $expiraEm) . "\n";
    }
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response1, 0, 200) . "\n";
}
echo "\n";

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
]);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$httpCode2}\n";
if ($httpCode2 === 200 || $httpCode2 === 201) {
    echo "✅ SUCESSO!\n";
    $data2 = json_decode($response2, true);
    echo "Token: " . ($data2['token'] ?? $data2['access_token'] ?? 'NÃO ENCONTRADO') . "\n";
    if (isset($data2['expiraEm'])) {
        $expiraEm = is_numeric($data2['expiraEm']) ? $data2['expiraEm'] : strtotime($data2['expiraEm']);
        echo "Expira em: " . date('Y-m-d H:i:s', $expiraEm) . "\n";
    }
    echo "\nResponse completa:\n";
    print_r($data2);
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response2, 0, 200) . "\n";
    if (!empty($response2)) {
        $errorData = json_decode($response2, true);
        if ($errorData) {
            echo "\nErro detalhado:\n";
            print_r($errorData);
        }
    }
}

echo "\n=== Fim do teste ===\n";
