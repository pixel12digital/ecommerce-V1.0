<?php

namespace App\Services\Shipping\Providers;

use App\Services\Shipping\ShippingProviderInterface;

class SimpleShippingProvider implements ShippingProviderInterface
{
    public function calcularOpcoesFrete(array $pedido, array $endereco, array $config = []): array
    {
        $subtotal = $pedido['subtotal'] ?? 0;
        $cep = $endereco['cep'] ?? $endereco['zipcode'] ?? '';

        // Configurações padrão (podem ser sobrescritas pelo config_json)
        $limiteFreteGratis = $config['limite_frete_gratis'] ?? 299.00;
        $freteSudeste = $config['frete_sudeste'] ?? 19.90;
        $freteOutrasRegioes = $config['frete_outras_regioes'] ?? 29.90;

        $opcoes = [];

        // Verificar se tem frete grátis
        if ($subtotal >= $limiteFreteGratis) {
            $opcoes[] = [
                'codigo' => 'frete_gratis',
                'titulo' => 'Frete Grátis',
                'valor' => 0.00,
                'prazo' => $config['prazo_frete_gratis'] ?? '7 a 12 dias úteis',
            ];
        }

        // Determinar região pelo CEP (primeiros dígitos)
        $regiao = $this->determinarRegiao($cep);
        $valorFrete = ($regiao === 'sudeste') ? $freteSudeste : $freteOutrasRegioes;
        $prazo = ($regiao === 'sudeste') 
            ? ($config['prazo_sudeste'] ?? '5 a 8 dias úteis')
            : ($config['prazo_outras'] ?? '7 a 10 dias úteis');

        $opcoes[] = [
            'codigo' => 'frete_simples',
            'titulo' => 'Frete Padrão',
            'valor' => $valorFrete,
            'prazo' => $prazo,
        ];

        return $opcoes;
    }

    private function determinarRegiao(string $cep): string
    {
        // Remove caracteres não numéricos
        $cep = preg_replace('/\D/', '', $cep);
        
        if (empty($cep) || strlen($cep) < 2) {
            return 'outras';
        }

        $prefixo = (int)substr($cep, 0, 2);

        // Sudeste: SP (01-09), RJ (20-28), MG (30-39), ES (29)
        if (($prefixo >= 1 && $prefixo <= 9) ||  // SP
            ($prefixo >= 20 && $prefixo <= 28) || // RJ
            ($prefixo >= 30 && $prefixo <= 39) || // MG
            $prefixo == 29) { // ES
            return 'sudeste';
        }

        return 'outras';
    }
}


