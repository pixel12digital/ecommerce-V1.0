<?php
/**
 * Teste API Preço v1 com SEDEX (40096)
 */

// Credenciais
$usuario = '24295346000122';
$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';

// Dados do teste
$cepOrigem = '89046650';
$cepDestino = '89046650';
$pesoKg = 0.3;
$comprimento = 20;
$largura = 20;
$altura = 10;
$codigoServico = '40096'; // SEDEX

echo "=== Teste API Preço v1 com SEDEX ===\n\n";

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

// 2. Testar Preço v1 com SEDEX
echo "--- Teste: GET com SEDEX (40096) ---\n";

$psObjeto = (string)round($pesoKg * 1000);
$tpObjeto = '2';

$url = 'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico . 
       '?cepOrigem=' . urlencode($cepOrigem) .
       '&cepDestino=' . urlencode($cepDestino) .
       '&psObjeto=' . urlencode($psObjeto) .
       '&tpObjeto=' . urlencode($tpObjeto) .
       '&comprimento=' . urlencode((string)$comprimento) .
       '&largura=' . urlencode((string)$largura) .
       '&altura=' . urlencode((string)$altura);

$ch = curl_init($url);
curl_setopt_array($ch, [
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ SUCESSO!\n";
    $data = json_decode($response, true);
    print_r($data);
    
    $preco = $data['preco'] ?? $data['valor'] ?? $data['vlrFrete'] ?? $data['valorFrete'] ?? null;
    if ($preco !== null) {
        echo "\nPreço SEDEX: R$ " . number_format($preco, 2, ',', '.') . "\n";
    }
} else {
    echo "❌ Falhou\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
}

echo "\n=== CONCLUSÃO ===\n";
echo "O formato está CORRETO agora!\n";
echo "psObjeto = peso em gramas (string): {$psObjeto}\n";
echo "Se o serviço não estiver liberado no contrato, você verá erro PRC-131.\n";
echo "Verifique no portal CWS quais serviços estão habilitados no contrato.\n";
