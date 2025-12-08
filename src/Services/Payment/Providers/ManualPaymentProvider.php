<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\PaymentResult;

class ManualPaymentProvider implements PaymentProviderInterface
{
    public function createPayment(array $pedido, array $cliente, string $metodoEscolhido, array $config = []): PaymentResult
    {
        // Gerar código de transação simples (opcional)
        $codigoTransacao = 'manual-' . $pedido['numero_pedido'];

        // Status inicial sempre pending para pagamento manual
        $statusInicial = 'pending';

        // Dados para exibição na tela de confirmação
        $dadosExibicao = [
            'tipo' => 'manual',
            'mensagem' => $config['mensagem_instrucoes'] ?? 'Você receberá as instruções de pagamento por e-mail/WhatsApp.',
            'instrucoes' => $config['instrucoes'] ?? null,
            'metodo' => $metodoEscolhido,
        ];

        return new PaymentResult($codigoTransacao, $statusInicial, $dadosExibicao);
    }
}


