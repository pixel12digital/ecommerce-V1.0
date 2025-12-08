<?php

namespace App\Services\Payment;

class PaymentResult
{
    public ?string $codigoTransacao;
    public string $statusInicial;
    public array $dadosExibicao;

    public function __construct(
        ?string $codigoTransacao = null,
        string $statusInicial = 'pending',
        array $dadosExibicao = []
    ) {
        $this->codigoTransacao = $codigoTransacao;
        $this->statusInicial = $statusInicial;
        $this->dadosExibicao = $dadosExibicao;
    }

    public function toArray(): array
    {
        return [
            'codigo_transacao' => $this->codigoTransacao,
            'status_inicial' => $this->statusInicial,
            'dados_exibicao' => $this->dadosExibicao,
        ];
    }
}


