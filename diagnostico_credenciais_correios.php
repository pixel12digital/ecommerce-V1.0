<?php
/**
 * Diagnóstico detalhado de credenciais Correios CWS
 */

echo "=== DIAGNÓSTICO DE CREDENCIAIS CORREIOS CWS ===\n\n";

echo "INSTRUÇÕES:\n";
echo "1. O 'Usuário' deve ser o mesmo que você usa para fazer login no portal CWS (Meu Correios)\n";
echo "2. Pode ser seu e-mail, CPF/CNPJ ou ID de usuário\n";
echo "3. O 'Código de acesso às APIs' é gerado em: CWS > Gestão de acesso a API's > Gerar/Regenerar código de acesso\n";
echo "4. O 'Nº do contrato' é o número do seu contrato com os Correios\n";
echo "5. O usuário pode ser DIFERENTE do número do contrato\n\n";

echo "Por favor, informe:\n";
echo "- Qual é o usuário que você usa para fazer login no portal CWS?\n";
echo "- Esse usuário é o mesmo número do contrato (9912730642)?\n";
echo "- Ou é um e-mail/CPF/CNPJ diferente?\n\n";

echo "=== TESTE COM AS CREDENCIAIS FORNECECIDAS ===\n\n";

$codigoAcessoApis = '9qv3jdLbDSGqtJoI2y6kbjQebb44jtpnxdNGVUuJ';
$contrato = '9912730642';
$usuario = '9912730642'; // Assumindo que é o mesmo do contrato

echo "Usuário testado: {$usuario}\n";
echo "Contrato: {$contrato}\n";
echo "Código de Acesso: " . substr($codigoAcessoApis, 0, 15) . "...\n\n";

// Teste com endpoint de contrato
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
$curlError = curl_error($ch);
curl_close($ch);

echo "Resultado:\n";
echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ SUCESSO! Token gerado com sucesso.\n";
    $data = json_decode($response, true);
    if (isset($data['token'])) {
        echo "Token: " . substr($data['token'], 0, 20) . "...\n";
    }
    if (isset($data['expiraEm'])) {
        echo "Expira em: " . date('Y-m-d H:i:s', $data['expiraEm']) . "\n";
    }
} else {
    echo "❌ ERRO: Credenciais inválidas ou sem permissão.\n\n";
    echo "POSSÍVEIS CAUSAS:\n";
    echo "1. O usuário '{$usuario}' pode estar incorreto\n";
    echo "   → Verifique qual é o usuário que você usa para fazer login no portal CWS\n";
    echo "   → Pode ser um e-mail, CPF/CNPJ ou ID diferente do número do contrato\n\n";
    echo "2. O código de acesso pode estar incorreto ou expirado\n";
    echo "   → Gere um novo código em: CWS > Gestão de acesso a API's > Gerar/Regenerar código de acesso\n\n";
    echo "3. O código de acesso pode não ter permissão para acessar o contrato {$contrato}\n";
    echo "   → Verifique no portal CWS se o código tem acesso ao contrato\n\n";
    echo "4. O contrato pode não estar ativo ou configurado corretamente\n";
    echo "   → Verifique no portal CWS se o contrato está ativo\n\n";
}

echo "\n=== PRÓXIMOS PASSOS ===\n";
echo "1. Confirme qual é o usuário correto (e-mail/CPF/CNPJ usado no login do Meu Correios)\n";
echo "2. Verifique se o código de acesso foi gerado corretamente\n";
echo "3. Verifique se o código tem permissão para o contrato\n";
echo "4. Teste novamente com as credenciais corretas\n";
