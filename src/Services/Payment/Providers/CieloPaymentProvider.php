<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\PaymentResult;

class CieloPaymentProvider implements PaymentProviderInterface
{
    public function createPayment(array $pedido, array $cliente, string $metodoEscolhido, array $config = []): PaymentResult
    {
        $cieloConfig = $config['cielo'] ?? $config;
        $merchantId = trim($cieloConfig['merchant_id'] ?? '');
        $merchantKey = trim($cieloConfig['merchant_key'] ?? '');
        $ambiente = $cieloConfig['ambiente'] ?? 'sandbox';

        if (empty($merchantId) || empty($merchantKey)) {
            throw new \RuntimeException('Credenciais Cielo não configuradas. Configure em Admin → Integrações → Gateway de Pagamento.');
        }

        $baseUrl = $ambiente === 'producao'
            ? 'https://api.cieloecommerce.cielo.com.br'
            : 'https://apisandbox.cieloecommerce.cielo.com.br';

        $amount = (int) round((float)($pedido['total_geral'] ?? 0) * 100);
        if ($amount < 1) {
            throw new \RuntimeException('Valor do pedido inválido para pagamento.');
        }

        if ($metodoEscolhido === 'pix') {
            return $this->criarPagamentoPix($baseUrl, $merchantId, $merchantKey, $pedido, $cliente, $amount);
        }

        if ($metodoEscolhido === 'credit_card') {
            return $this->criarPagamentoCartao($baseUrl, $merchantId, $merchantKey, $pedido, $cliente, $amount, $config);
        }

        throw new \RuntimeException("Método de pagamento não suportado: {$metodoEscolhido}");
    }

    private function criarPagamentoCartao(
        string $baseUrl,
        string $merchantId,
        string $merchantKey,
        array $pedido,
        array $cliente,
        int $amount,
        array $config = []
    ): PaymentResult {
        $merchantOrderId = (string)($pedido['numero_pedido'] ?? 'pedido-' . ($pedido['id'] ?? time()));

        // Dados do cartão vêm do POST
        $cardNumber = preg_replace('/\D/', '', trim($_POST['card_number'] ?? ''));
        $cardHolder = strtoupper(trim($_POST['card_holder'] ?? ''));
        $cardExpiry = trim($_POST['card_expiry'] ?? '');
        $cardCvv = trim($_POST['card_cvv'] ?? '');
        $installments = max(1, (int)($_POST['card_installments'] ?? 1));

        if (empty($cardNumber) || strlen($cardNumber) < 13) {
            throw new \RuntimeException('Número do cartão inválido.');
        }
        if (empty($cardHolder)) {
            throw new \RuntimeException('Nome do titular do cartão é obrigatório.');
        }
        if (empty($cardExpiry) || !preg_match('/^\d{2}\/\d{4}$/', $cardExpiry)) {
            throw new \RuntimeException('Validade do cartão inválida. Use o formato MM/AAAA.');
        }
        if (empty($cardCvv) || strlen($cardCvv) < 3) {
            throw new \RuntimeException('CVV do cartão inválido.');
        }

        // Extrair mês e ano da validade
        $expiryParts = explode('/', $cardExpiry);
        $expiryMonth = $expiryParts[0];
        $expiryYear = $expiryParts[1];

        // Detectar bandeira pelo número do cartão
        $brand = $this->detectCardBrand($cardNumber);

        $payload = [
            'MerchantOrderId' => $merchantOrderId,
            'Customer' => [
                'Name' => $cliente['nome'] ?? 'Cliente',
                'Email' => $cliente['email'] ?? '',
            ],
            'Payment' => [
                'Type' => 'CreditCard',
                'Amount' => $amount,
                'Installments' => $installments,
                'SoftDescriptor' => 'PontoGolfe',
                'Capture' => true,
                'CreditCard' => [
                    'CardNumber' => $cardNumber,
                    'Holder' => $cardHolder,
                    'ExpirationDate' => $expiryMonth . '/' . $expiryYear,
                    'SecurityCode' => $cardCvv,
                    'Brand' => $brand,
                ],
            ],
        ];

        $url = $baseUrl . '/1/sales/';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'MerchantId: ' . $merchantId,
                'MerchantKey: ' . $merchantKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('Erro ao conectar com Cielo: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Resposta inválida da Cielo. Tente novamente.');
        }

        if ($httpCode >= 400) {
            $msg = $data['message'] ?? $data['Message'] ?? 'Erro HTTP ' . $httpCode;
            if (isset($data['ModelState']) && is_array($data['ModelState'])) {
                $msg = implode(' ', array_merge(...array_values($data['ModelState'])));
            }
            error_log("Cielo CC Error: HTTP {$httpCode} - " . json_encode($data));
            throw new \RuntimeException('Cielo: ' . $msg);
        }

        $payment = $data['Payment'] ?? [];
        $status = (int)($payment['Status'] ?? 0);
        $paymentId = $payment['PaymentId'] ?? null;
        $returnCode = $payment['ReturnCode'] ?? '';
        $returnMessage = $payment['ReturnMessage'] ?? '';
        $tid = $payment['Tid'] ?? '';

        // Status 2 = PaymentConfirmed (capturado), 1 = Authorized
        if ($status !== 1 && $status !== 2) {
            $errorMsg = !empty($returnMessage) ? $returnMessage : 'Pagamento não autorizado.';
            error_log("Cielo CC Denied: Status={$status}, ReturnCode={$returnCode}, ReturnMessage={$returnMessage}");
            throw new \RuntimeException('Pagamento recusado: ' . $errorMsg);
        }

        $codigoTransacao = $paymentId ? (string)$paymentId : 'cielo-cc-' . $merchantOrderId;
        $statusInicial = ($status === 2) ? 'paid' : 'pending';

        $dadosExibicao = [
            'tipo' => 'cielo_credit_card',
            'mensagem' => 'Pagamento com cartão de crédito aprovado!',
            'metodo' => 'credit_card',
            'payment_id' => $paymentId,
            'tid' => $tid,
            'return_code' => $returnCode,
            'return_message' => $returnMessage,
            'brand' => $brand,
            'installments' => $installments,
            'last_four' => substr($cardNumber, -4),
        ];

        return new PaymentResult($codigoTransacao, $statusInicial, $dadosExibicao);
    }

    /**
     * Detecta a bandeira do cartão pelo número
     */
    private function detectCardBrand(string $cardNumber): string
    {
        $number = preg_replace('/\D/', '', $cardNumber);

        if (preg_match('/^4/', $number)) return 'Visa';
        if (preg_match('/^5[1-5]/', $number)) return 'Master';
        if (preg_match('/^(636368|438935|504175|451416|636297|5067|4576|4011|506699)/', $number)) return 'Elo';
        if (preg_match('/^3[47]/', $number)) return 'Amex';
        if (preg_match('/^(6011|622|64|65)/', $number)) return 'Discover';
        if (preg_match('/^(301|305|36|38)/', $number)) return 'Diners';
        if (preg_match('/^(384100|384140|384160|606282|637095|637568|637599|637609|637612)/', $number)) return 'Aura';
        if (preg_match('/^(6062|3841)/', $number)) return 'Hipercard';

        return 'Visa'; // fallback
    }

    private function criarPagamentoPix(
        string $baseUrl,
        string $merchantId,
        string $merchantKey,
        array $pedido,
        array $cliente,
        int $amount
    ): PaymentResult {
        $merchantOrderId = (string)($pedido['numero_pedido'] ?? 'pedido-' . ($pedido['id'] ?? time()));

        $payload = [
            'MerchantOrderId' => $merchantOrderId,
            'Customer' => [
                'Name' => $cliente['nome'] ?? 'Cliente',
                'Email' => $cliente['email'] ?? '',
            ],
            'Payment' => [
                'Type' => 'Pix',
                'Provider' => 'Cielo30',
                'Amount' => $amount,
                'QrCodeExpiration' => 86400,
            ],
        ];

        $url = $baseUrl . '/1/sales/';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'MerchantId: ' . $merchantId,
                'MerchantKey: ' . $merchantKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('Erro ao conectar com Cielo: ' . $curlError);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Resposta inválida da Cielo. Tente novamente.');
        }

        if ($httpCode >= 400) {
            $msg = $data['message'] ?? $data['Message'] ?? 'Erro HTTP ' . $httpCode;
            if (isset($data['ModelState']) && is_array($data['ModelState'])) {
                $msg = implode(' ', array_merge(...array_values($data['ModelState'])));
            }
            throw new \RuntimeException('Cielo: ' . $msg);
        }

        $payment = $data['Payment'] ?? [];
        $status = (int)($payment['Status'] ?? 0);
        $paymentId = $payment['PaymentId'] ?? $data['PaymentId'] ?? null;
        $qrCodeBase64 = $payment['QrCodeBase64Image'] ?? $payment['QrCodeBase64'] ?? null;
        $qrCodeString = $payment['QrCodeString'] ?? null;

        $codigoTransacao = $paymentId ? (string) $paymentId : 'cielo-pix-' . $merchantOrderId;
        $statusInicial = $status === 2 ? 'paid' : ($status === 0 ? 'pending' : 'pending');

        $dadosExibicao = [
            'tipo' => 'cielo_pix',
            'mensagem' => 'Escaneie o QR Code ou copie o código PIX para pagar.',
            'metodo' => 'pix',
            'qr_code_base64' => $qrCodeBase64,
            'qr_code_string' => $qrCodeString,
            'payment_id' => $paymentId,
        ];

        return new PaymentResult($codigoTransacao, $statusInicial, $dadosExibicao);
    }
}
