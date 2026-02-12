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

        if ($metodoEscolhido === 'boleto') {
            return $this->criarPagamentoBoleto($baseUrl, $merchantId, $merchantKey, $pedido, $cliente, $amount, $config);
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
        // Aceitar MM/AA ou MM/AAAA
        if (empty($cardExpiry) || !preg_match('/^\d{2}\/\d{2,4}$/', $cardExpiry)) {
            throw new \RuntimeException('Validade do cartão inválida. Use o formato MM/AAAA.');
        }
        if (empty($cardCvv) || strlen($cardCvv) < 3) {
            throw new \RuntimeException('CVV do cartão inválido.');
        }

        // Extrair mês e ano da validade
        $expiryParts = explode('/', $cardExpiry);
        $expiryMonth = $expiryParts[0];
        $expiryYear = $expiryParts[1];
        // Converter ano de 2 dígitos para 4 dígitos (ex: 26 -> 2026)
        if (strlen($expiryYear) === 2) {
            $expiryYear = '20' . $expiryYear;
        }

        // Validar mês (01-12)
        $monthInt = (int)$expiryMonth;
        if ($monthInt < 1 || $monthInt > 12) {
            throw new \RuntimeException('Mês de validade inválido. Informe um mês entre 01 e 12.');
        }

        // Validar se cartão não está vencido
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        $yearInt = (int)$expiryYear;
        if ($yearInt < $currentYear || ($yearInt === $currentYear && $monthInt < $currentMonth)) {
            throw new \RuntimeException('Cartão vencido. A data de validade informada (' . $cardExpiry . ') já passou.');
        }

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

        $logFile = dirname(__DIR__, 4) . '/cielo_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " REQUEST: OrderId={$merchantOrderId}, Brand={$brand}, Last4=" . substr($cardNumber, -4) . ", Expiry={$expiryMonth}/{$expiryYear}, Installments={$installments}, Amount={$amount}\n", FILE_APPEND);

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

        file_put_contents($logFile, date('Y-m-d H:i:s') . " RESPONSE: HTTP={$httpCode}, Body=" . substr($response, 0, 500) . "\n", FILE_APPEND);

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
            error_log("Cielo CC Denied: Status={$status}, ReturnCode={$returnCode}, ReturnMessage={$returnMessage}, PaymentId={$paymentId}");
            $errorMsg = $this->traduzirErroCielo($returnCode, $returnMessage);
            throw new \RuntimeException($errorMsg);
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

    private function criarPagamentoBoleto(
        string $baseUrl,
        string $merchantId,
        string $merchantKey,
        array $pedido,
        array $cliente,
        int $amount,
        array $config = []
    ): PaymentResult {
        $merchantOrderId = (string)($pedido['numero_pedido'] ?? 'pedido-' . ($pedido['id'] ?? time()));

        $cieloConfig = $config['cielo'] ?? $config;
        $boletoProvider = $cieloConfig['boleto_provider'] ?? 'Bradesco2';
        $boletoAssignor = $cieloConfig['boleto_assignor'] ?? 'Ponto do Golfe';
        $boletoDaysToExpire = (int)($cieloConfig['boleto_days_to_expire'] ?? 3);

        $expirationDate = date('Y-m-d', strtotime("+{$boletoDaysToExpire} days"));

        $payload = [
            'MerchantOrderId' => $merchantOrderId,
            'Customer' => [
                'Name' => $cliente['nome'] ?? 'Cliente',
                'Email' => $cliente['email'] ?? '',
                'Identity' => preg_replace('/\D/', '', $cliente['cpf'] ?? $cliente['documento'] ?? ''),
                'Address' => [
                    'Street' => $cliente['logradouro'] ?? '',
                    'Number' => $cliente['numero'] ?? 'S/N',
                    'District' => $cliente['bairro'] ?? '',
                    'City' => $cliente['cidade'] ?? '',
                    'State' => $cliente['estado'] ?? '',
                    'ZipCode' => preg_replace('/\D/', '', $cliente['cep'] ?? ''),
                    'Country' => 'BRA',
                ],
            ],
            'Payment' => [
                'Type' => 'Boleto',
                'Amount' => $amount,
                'Provider' => $boletoProvider,
                'Assignor' => $boletoAssignor,
                'ExpirationDate' => $expirationDate,
                'Identification' => preg_replace('/\D/', '', $cliente['cpf'] ?? $cliente['documento'] ?? ''),
                'Instructions' => 'Não receber após o vencimento.',
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
            error_log("Cielo Boleto Error: HTTP {$httpCode} - " . json_encode($data));
            throw new \RuntimeException('Cielo: ' . $msg);
        }

        $payment = $data['Payment'] ?? [];
        $status = (int)($payment['Status'] ?? 0);
        $paymentId = $payment['PaymentId'] ?? null;
        $boletoUrl = $payment['Url'] ?? null;
        $barCodeNumber = $payment['BarCodeNumber'] ?? null;
        $digitableLine = $payment['DigitableLine'] ?? null;

        $codigoTransacao = $paymentId ? (string)$paymentId : 'cielo-boleto-' . $merchantOrderId;
        $statusInicial = ($status === 1) ? 'pending' : 'pending';

        $dadosExibicao = [
            'tipo' => 'cielo_boleto',
            'mensagem' => 'Boleto gerado com sucesso! Pague até ' . date('d/m/Y', strtotime($expirationDate)) . '.',
            'metodo' => 'boleto',
            'boleto_url' => $boletoUrl,
            'bar_code_number' => $barCodeNumber,
            'digitable_line' => $digitableLine,
            'expiration_date' => $expirationDate,
            'payment_id' => $paymentId,
        ];

        return new PaymentResult($codigoTransacao, $statusInicial, $dadosExibicao);
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

        $cpf = preg_replace('/\D/', '', $cliente['cpf'] ?? $cliente['documento'] ?? '');

        $payload = [
            'MerchantOrderId' => $merchantOrderId,
            'Customer' => [
                'Name' => $cliente['nome'] ?? 'Cliente',
                'Identity' => $cpf,
                'IdentityType' => 'CPF',
            ],
            'Payment' => [
                'Type' => 'Pix',
                'Amount' => $amount,
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
            error_log("Cielo PIX Error: HTTP {$httpCode} - Response: " . $response);
            error_log("Cielo PIX Payload: " . json_encode($payload));
            $msg = 'Erro HTTP ' . $httpCode;
            if (is_array($data)) {
                if (isset($data['ModelState']) && is_array($data['ModelState'])) {
                    $msg = implode(' ', array_merge(...array_values($data['ModelState'])));
                } elseif (!empty($data['Message'])) {
                    $msg = $data['Message'];
                } elseif (!empty($data['message'])) {
                    $msg = $data['message'];
                } elseif (!empty($data[0]['Message'])) {
                    $msg = $data[0]['Message'];
                }
            }
            throw new \RuntimeException('Erro no pagamento PIX: ' . $msg . '. Verifique as credenciais Cielo.');
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

    /**
     * Consulta o status de um pagamento na Cielo pelo PaymentId
     */
    public function consultarPagamento(string $paymentId, array $config = []): array
    {
        $cieloConfig = $config['cielo'] ?? $config;
        $merchantId = trim($cieloConfig['merchant_id'] ?? '');
        $merchantKey = trim($cieloConfig['merchant_key'] ?? '');
        $ambiente = $cieloConfig['ambiente'] ?? 'sandbox';

        if (empty($merchantId) || empty($merchantKey)) {
            throw new \RuntimeException('Credenciais Cielo não configuradas.');
        }

        $queryUrl = $ambiente === 'producao'
            ? 'https://apiquery.cieloecommerce.cielo.com.br'
            : 'https://apiquerysandbox.cieloecommerce.cielo.com.br';

        $url = $queryUrl . '/1/sales/' . $paymentId;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'MerchantId: ' . $merchantId,
                'MerchantKey: ' . $merchantKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Cielo Query Error: " . $curlError);
            return ['status' => null, 'error' => $curlError];
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || $httpCode >= 400) {
            error_log("Cielo Query Error: HTTP {$httpCode} - " . substr($response, 0, 500));
            return ['status' => null, 'error' => 'HTTP ' . $httpCode];
        }

        $payment = $data['Payment'] ?? [];
        $cieloStatus = (int)($payment['Status'] ?? -1);

        // Mapear status Cielo para status do sistema
        // 0=NotFinished, 1=Authorized, 2=PaymentConfirmed, 3=Denied, 10=Voided, 11=Refunded, 12=Pending, 13=Aborted
        $statusMap = [
            0  => 'pending',
            1  => 'paid',
            2  => 'paid',
            3  => 'canceled',
            10 => 'canceled',
            11 => 'canceled',
            12 => 'pending',
            13 => 'canceled',
        ];

        return [
            'cielo_status' => $cieloStatus,
            'status' => $statusMap[$cieloStatus] ?? 'pending',
            'payment_id' => $payment['PaymentId'] ?? $paymentId,
            'type' => $payment['Type'] ?? '',
        ];
    }

    private function traduzirErroCielo(string $returnCode, string $returnMessage): string
    {
        $mapa = [
            '05' => 'Pagamento não autorizado pelo banco emissor. Entre em contato com seu banco ou tente outro cartão.',
            '57' => 'Cartão não permite esse tipo de transação. Tente outro cartão.',
            '78' => 'Cartão bloqueado. Entre em contato com seu banco.',
            '99' => 'Tempo esgotado na comunicação com o banco. Tente novamente.',
            '77' => 'Cartão cancelado. Tente outro cartão.',
            '70' => 'Cartão cancelado. Tente outro cartão.',
            '51' => 'Saldo insuficiente. Tente outro cartão ou método de pagamento.',
            '54' => 'Cartão vencido. Verifique a data de validade ou use outro cartão.',
            '56' => 'Cartão não encontrado no banco emissor. Verifique os dados ou tente outro cartão.',
            '61' => 'Valor acima do limite permitido. Tente um valor menor ou outro cartão.',
            '62' => 'Transação não permitida para este cartão. Tente outro cartão.',
            '63' => 'Transação não permitida. Violação de segurança.',
            '65' => 'Limite de tentativas excedido. Aguarde alguns minutos e tente novamente.',
            '14' => 'Número do cartão inválido. Verifique e tente novamente.',
            '41' => 'Cartão bloqueado por perda. Entre em contato com seu banco.',
            '43' => 'Cartão bloqueado por roubo. Entre em contato com seu banco.',
            'BP' => 'Cartão não identificado ou não é permitido para esta transação.',
            'N7' => 'Código de segurança (CVV) inválido. Verifique e tente novamente.',
            'BV' => 'Cartão vencido. Verifique a data de validade ou use outro cartão.',
        ];

        if (isset($mapa[$returnCode])) {
            return $mapa[$returnCode];
        }

        $lower = mb_strtolower($returnMessage);
        if (strpos($lower, 'expir') !== false || strpos($lower, 'validade') !== false) {
            return 'Cartão vencido ou data de validade incorreta. Verifique e tente novamente.';
        }
        if (strpos($lower, 'not authorized') !== false || strpos($lower, 'denied') !== false) {
            return 'Pagamento não autorizado pelo banco. Tente outro cartão ou método de pagamento.';
        }

        return 'Pagamento não autorizado (código ' . $returnCode . '). Verifique os dados do cartão ou tente outro método de pagamento.';
    }
}
