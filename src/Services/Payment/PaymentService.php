<?php

namespace App\Services\Payment;

use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Payment\Providers\ManualPaymentProvider;

class PaymentService
{
    /**
     * Lista métodos de pagamento disponíveis
     * 
     * @param int $tenantId ID do tenant
     * @return array Array de métodos de pagamento
     */
    public static function listarMetodosDisponiveis(int $tenantId): array
    {
        // Por enquanto, apenas método manual/PIX
        // No futuro, pode verificar configurações do gateway para habilitar outros métodos
        return [
            [
                'codigo' => 'manual_pix',
                'titulo' => 'PIX / Transferência',
                'descricao' => 'Você receberá as instruções de pagamento após finalizar o pedido.',
                'icone' => 'pix'
            ]
        ];
    }

    /**
     * Processa o pagamento usando o provider configurado para o tenant
     * 
     * @param string $metodoEscolhido Código do método de pagamento escolhido (ex: 'manual_pix')
     * @param array $pedido Dados do pedido (id, numero_pedido, total_geral, etc.)
     * @param array $cliente Dados do cliente (nome, email, telefone, etc.)
     * @return PaymentResult Resultado do processamento
     */
    public static function processarPagamento(string $metodoEscolhido, array $pedido, array $cliente): PaymentResult
    {
        $tenantId = TenantContext::id();
        $provider = self::getProvider($tenantId);
        $config = self::getProviderConfig($tenantId, 'payment');
        
        // Extrair apenas o método base (ex: 'manual_pix' -> 'pix')
        $metodoBase = str_replace('manual_', '', $metodoEscolhido);
        
        return $provider->createPayment($pedido, $cliente, $metodoBase, $config);
    }

    /**
     * Obtém o provider de pagamento configurado para o tenant
     * 
     * @param int $tenantId ID do tenant
     * @return PaymentProviderInterface
     */
    private static function getProvider(int $tenantId): PaymentProviderInterface
    {
        $gateway = self::getGatewayConfig($tenantId, 'payment');
        $codigo = $gateway['codigo'] ?? 'manual';

        // Mapear código para classe do provider
        $providers = [
            'manual' => ManualPaymentProvider::class,
            // Futuro: 'mercadopago' => MercadoPagoProvider::class,
            // Futuro: 'asaas' => AsaasProvider::class,
            // Futuro: 'pagarme' => PagarmeProvider::class,
        ];

        $providerClass = $providers[$codigo] ?? ManualPaymentProvider::class;

        if (!class_exists($providerClass)) {
            throw new \RuntimeException("Provider de pagamento não encontrado: {$codigo}");
        }

        return new $providerClass();
    }

    /**
     * Obtém configuração do gateway de pagamento do tenant
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

    /**
     * Obtém instruções de pagamento para um método (mantido para compatibilidade)
     */
    public static function getInstrucoes(string $metodo): string
    {
        if ($metodo === 'manual_pix') {
            return 'Enviaremos os dados de pagamento (chave PIX ou dados bancários) por e-mail/WhatsApp em breve. Após o pagamento, seu pedido será processado.';
        }
        
        return 'Instruções de pagamento não disponíveis.';
    }
}


