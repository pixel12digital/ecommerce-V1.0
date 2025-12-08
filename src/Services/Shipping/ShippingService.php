<?php

namespace App\Services\Shipping;

use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Shipping\Providers\SimpleShippingProvider;

class ShippingService
{
    /**
     * Calcula opções de frete disponíveis usando o provider configurado
     * 
     * @param int $tenantId ID do tenant
     * @param string $cep CEP de destino
     * @param float $subtotal Subtotal do pedido
     * @param array $itens Array de itens do carrinho
     * @return array Array de opções de frete
     */
    public static function calcularFrete(int $tenantId, string $cep, float $subtotal, array $itens): array
    {
        $provider = self::getProvider($tenantId);
        $config = self::getProviderConfig($tenantId, 'shipping');

        $pedido = [
            'subtotal' => $subtotal,
            'itens' => $itens,
        ];

        $endereco = [
            'cep' => $cep,
            'zipcode' => $cep,
        ];

        return $provider->calcularOpcoesFrete($pedido, $endereco, $config);
    }

    /**
     * Obtém o valor do frete pelo código
     */
    public static function getValorFrete(string $codigoFrete, int $tenantId, string $cep, float $subtotal, array $itens): float
    {
        $opcoes = self::calcularFrete($tenantId, $cep, $subtotal, $itens);
        
        foreach ($opcoes as $opcao) {
            if ($opcao['codigo'] === $codigoFrete) {
                return (float)$opcao['valor'];
            }
        }
        
        return 0.0;
    }

    /**
     * Obtém o provider de frete configurado para o tenant
     * 
     * @param int $tenantId ID do tenant
     * @return ShippingProviderInterface
     */
    private static function getProvider(int $tenantId): ShippingProviderInterface
    {
        $gateway = self::getGatewayConfig($tenantId, 'shipping');
        $codigo = $gateway['codigo'] ?? 'simples';

        // Mapear código para classe do provider
        $providers = [
            'simples' => SimpleShippingProvider::class,
            // Futuro: 'melhorenvio' => MelhorEnvioProvider::class,
            // Futuro: 'correios' => CorreiosProvider::class,
        ];

        $providerClass = $providers[$codigo] ?? SimpleShippingProvider::class;

        if (!class_exists($providerClass)) {
            throw new \RuntimeException("Provider de frete não encontrado: {$codigo}");
        }

        return new $providerClass();
    }

    /**
     * Obtém configuração do gateway de frete do tenant
     * 
     * @param int $tenantId ID do tenant
     * @param string $tipo Tipo do gateway ('payment' ou 'shipping')
     * @return array Configuração do gateway
     */
    private static function getGatewayConfig(int $tenantId, string $tipo): array
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT codigo, config_json, ativo 
            FROM tenant_gateways 
            WHERE tenant_id = :tenant_id 
            AND tipo = :tipo 
            AND ativo = 1 
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'tipo' => $tipo,
        ]);
        
        $gateway = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$gateway) {
            // Retornar padrão se não encontrar
            return [
                'codigo' => $tipo === 'payment' ? 'manual' : 'simples',
                'config_json' => null,
                'ativo' => 1,
            ];
        }

        return $gateway;
    }

    /**
     * Obtém configuração JSON do provider (decodificado)
     * 
     * @param int $tenantId ID do tenant
     * @param string $tipo Tipo do gateway
     * @return array Configuração decodificada
     */
    private static function getProviderConfig(int $tenantId, string $tipo): array
    {
        $gateway = self::getGatewayConfig($tenantId, $tipo);
        $configJson = $gateway['config_json'] ?? null;

        if (empty($configJson)) {
            return [];
        }

        $config = json_decode($configJson, true);
        return is_array($config) ? $config : [];
    }
}


