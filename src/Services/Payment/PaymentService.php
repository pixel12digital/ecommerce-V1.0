<?php

namespace App\Services\Payment;

use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Payment\Providers\ManualPaymentProvider;
use App\Services\Payment\Providers\CieloPaymentProvider;

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
        $gateway = self::getGatewayConfig($tenantId, 'payment');
        $codigo = $gateway['codigo'] ?? 'manual';

        $metodos = [];

        if ($codigo === 'cielo') {
            $metodos[] = [
                'codigo' => 'cielo_credit_card',
                'titulo' => 'Cartão de Crédito (Cielo)',
                'descricao' => 'Pague com cartão de crédito via Cielo. Visa, Master, Elo e mais.',
                'icone' => 'credit_card'
            ];
            $metodos[] = [
                'codigo' => 'cielo_pix',
                'titulo' => 'PIX (Cielo)',
                'descricao' => 'Pague com PIX via Cielo. QR Code será exibido após finalizar.',
                'icone' => 'pix'
            ];

            // Boleto: só exibir se o provider de boleto estiver configurado
            $config = self::getProviderConfig($tenantId, 'payment');
            $cieloConfig = $config['cielo'] ?? $config;
            $boletoProvider = $cieloConfig['boleto_provider'] ?? '';
            if (!empty($boletoProvider)) {
                $metodos[] = [
                    'codigo' => 'cielo_boleto',
                    'titulo' => 'Boleto Bancário (Cielo)',
                    'descricao' => 'Gere um boleto bancário. O boleto será exibido após finalizar.',
                    'icone' => 'boleto'
                ];
            }

            return $metodos;
        }

        $metodos[] = [
            'codigo' => 'manual_pix',
            'titulo' => 'PIX / Transferência',
            'descricao' => 'Você receberá as instruções de pagamento após finalizar o pedido.',
            'icone' => 'pix'
        ];
        return $metodos;
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
        
        // Extrair apenas o método base (ex: 'manual_pix' -> 'pix', 'cielo_pix' -> 'pix')
        $metodoBase = preg_replace('/^(manual_|cielo_)/', '', $metodoEscolhido) ?: $metodoEscolhido;
        
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
            'cielo' => CieloPaymentProvider::class,
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
        if ($metodo === 'cielo_pix') {
            return 'O QR Code PIX será exibido na próxima tela. Escaneie ou copie o código para pagar.';
        }
        if ($metodo === 'cielo_credit_card') {
            return 'Pagamento processado com cartão de crédito via Cielo.';
        }
        if ($metodo === 'cielo_boleto') {
            return 'O boleto bancário será exibido na próxima tela. Pague até a data de vencimento.';
        }
        return 'Instruções de pagamento não disponíveis.';
    }
}


