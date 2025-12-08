<?php

namespace App\Support;

class LangHelper
{
    /**
     * Traduz status de produto para PT-BR
     */
    public static function productStatusLabel(string $status): string
    {
        $map = [
            'publish' => 'Ativo',
            'draft' => 'Rascunho',
            'pending' => 'Pendente',
            'private' => 'Privado',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Traduz status de estoque para PT-BR
     */
    public static function stockStatusLabel(?string $status): string
    {
        $map = [
            'instock'     => 'Em estoque',
            'outofstock'  => 'Sem estoque',
            'onbackorder' => 'Sob encomenda',
        ];

        return $map[$status] ?? ($status ?? '');
    }

    /**
     * Traduz valor booleano para Sim/Não
     */
    public static function boolLabel(bool $value, string $yes = 'Sim', string $no = 'Não'): string
    {
        return $value ? $yes : $no;
    }

    /**
     * Traduz status de pedido para PT-BR
     */
    public static function orderStatusLabel(string $status): string
    {
        $map = [
            'pending'   => 'Aguardando Pagamento',
            'paid'      => 'Pago',
            'canceled'  => 'Cancelado',
            'shipped'   => 'Enviado',
            'completed' => 'Concluído',
        ];

        return $map[$status] ?? ucfirst($status);
    }

    /**
     * Traduz status de pedido (versão curta) para PT-BR
     */
    public static function orderStatusLabelShort(string $status): string
    {
        $map = [
            'pending'   => 'Aguardando',
            'paid'      => 'Pago',
            'canceled'  => 'Cancelado',
            'shipped'   => 'Enviado',
            'completed' => 'Concluído',
        ];

        return $map[$status] ?? ucfirst($status);
    }
}


