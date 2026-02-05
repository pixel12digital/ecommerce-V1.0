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

        // Cartão de crédito: por enquanto retorna pendente (exige coleta de dados no checkout)
        if ($metodoEscolhido === 'credit_card') {
            return new PaymentResult(
                'cielo-cc-pending-' . ($pedido['numero_pedido'] ?? ''),
                'pending',
                [
                    'tipo' => 'cielo_credit_card',
                    'mensagem' => 'Pagamento com cartão de crédito via Cielo em desenvolvimento. Use PIX ou Manual.',
                    'metodo' => 'credit_card',
                ]
            );
        }

        throw new \RuntimeException("Método de pagamento não suportado: {$metodoEscolhido}");
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
