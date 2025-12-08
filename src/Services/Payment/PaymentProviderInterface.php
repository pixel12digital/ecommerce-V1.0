<?php

namespace App\Services\Payment;

interface PaymentProviderInterface
{
    /**
     * Cria a cobrança/pagamento para um pedido.
     * Pode ser só local (manual) ou chamar API externa.
     *
     * @param array $pedido Dados do pedido (id, numero_pedido, total_geral, etc.)
     * @param array $cliente Dados do cliente (nome, email, telefone, etc.)
     * @param string $metodoEscolhido Método escolhido pelo cliente (ex: 'pix', 'credit_card', etc.)
     * @param array $config Configurações do gateway (do config_json)
     * @return PaymentResult DTO com código de transação, status inicial e dados para exibição
     */
    public function createPayment(array $pedido, array $cliente, string $metodoEscolhido, array $config = []): PaymentResult;
}


