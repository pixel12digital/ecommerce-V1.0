<?php

namespace App\Services\Shipping;

use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Shipping\Providers\SimpleShippingProvider;
use App\Services\Shipping\Providers\CorreiosProvider;

class ShippingService
{
    /**
     * Calcula opções de frete disponíveis usando o provider configurado
     * 
     * @param int $tenantId ID do tenant
     * @param string $cep CEP de destino
     * @param float $subtotal Subtotal do pedido
     * @param array $itens Array de itens do carrinho (formato: ['produto_id' => ['quantidade' => int, 'preco_unitario' => float]])
     * @return array Array de opções de frete
     */
    public static function calcularFrete(int $tenantId, string $cep, float $subtotal, array $itens): array
    {
        $provider = self::getProvider($tenantId);
        $config = self::getProviderConfig($tenantId, 'shipping');

        // Buscar dimensões e peso dos produtos
        $itensComDimensoes = self::enriquecerItensComDimensoes($tenantId, $itens);

        $pedido = [
            'subtotal' => $subtotal,
            'itens' => $itensComDimensoes,
        ];

        $endereco = [
            'cep' => $cep,
            'zipcode' => $cep,
        ];

        return $provider->calcularOpcoesFrete($pedido, $endereco, $config);
    }

    /**
     * Enriquece os itens do carrinho com dimensões e peso dos produtos
     * 
     * @param int $tenantId ID do tenant
     * @param array $itens Itens do carrinho
     * @return array Itens enriquecidos com dimensões
     */
    private static function enriquecerItensComDimensoes(int $tenantId, array $itens): array
    {
        if (empty($itens)) {
            return [];
        }

        $db = Database::getConnection();
        $produtoIds = array_keys($itens);
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));

        $stmt = $db->prepare("
            SELECT id, peso, comprimento, largura, altura, preco
            FROM produtos
            WHERE id IN ({$placeholders}) AND tenant_id = ?
        ");
        $stmt->execute(array_merge($produtoIds, [$tenantId]));
        $produtos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Criar mapa de produtos por ID
        $produtosMap = [];
        foreach ($produtos as $produto) {
            $produtosMap[$produto['id']] = $produto;
        }

        // Enriquecer itens com dimensões
        $itensEnriquecidos = [];
        foreach ($itens as $produtoId => $item) {
            $produto = $produtosMap[$produtoId] ?? null;
            
            $itemEnriquecido = [
                'produto_id' => $produtoId,
                'quantidade' => $item['quantidade'] ?? 1,
                'preco_unitario' => $item['preco_unitario'] ?? ($produto['preco'] ?? 0),
                'peso' => $produto['peso'] ?? null,
                'comprimento' => $produto['comprimento'] ?? null,
                'largura' => $produto['largura'] ?? null,
                'altura' => $produto['altura'] ?? null,
            ];

            $itensEnriquecidos[] = $itemEnriquecido;
        }

        return $itensEnriquecidos;
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
            'correios' => \App\Services\Shipping\Providers\CorreiosProvider::class,
        ];

        $providerClass = $providers[$codigo] ?? SimpleShippingProvider::class;

        if (!class_exists($providerClass)) {
            throw new \RuntimeException("Provider de frete não encontrado: {$codigo}");
        }

        return new $providerClass();
    }

    /**
     * Obtém configuração JSON do provider (método público)
     * 
     * @param int $tenantId ID do tenant
     * @param string $tipo Tipo do gateway ('payment' ou 'shipping')
     * @return array Configuração decodificada
     */
    public static function getProviderConfig(int $tenantId, string $tipo): array
    {
        $gateway = self::getGatewayConfig($tenantId, $tipo);
        $configJson = $gateway['config_json'] ?? null;

        if (empty($configJson)) {
            return [];
        }

        $config = json_decode($configJson, true);
        return is_array($config) ? $config : [];
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

}


