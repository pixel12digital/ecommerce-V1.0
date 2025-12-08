<?php

namespace App\Services\Shipping;

interface ShippingProviderInterface
{
    /**
     * Calcula opções de frete para o pedido.
     *
     * @param array $pedido Dados do pedido (itens, subtotal, etc.)
     * @param array $endereco Endereço de entrega (cep, cidade, estado, etc.)
     * @param array $config Configurações do gateway (do config_json)
     * @return array Lista de opções de frete no formato:
     * [
     *   [
     *     'codigo' => 'frete_simples',
     *     'titulo' => 'Frete Padrão',
     *     'valor'  => 29.90,
     *     'prazo'  => '5 a 8 dias úteis'
     *   ],
     *   ...
     * ]
     */
    public function calcularOpcoesFrete(array $pedido, array $endereco, array $config = []): array;
}


