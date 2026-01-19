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
        error_log("ShippingService::calcularFrete() - Iniciando");
        error_log("ShippingService::calcularFrete() - Tenant ID: {$tenantId}");
        error_log("ShippingService::calcularFrete() - CEP: {$cep}");
        error_log("ShippingService::calcularFrete() - Subtotal: {$subtotal}");
        error_log("ShippingService::calcularFrete() - Número de itens: " . count($itens));
        
        // Validar se todos os itens têm peso e dimensões (SEM FALLBACK)
        $validacao = self::validarDadosItens($tenantId, $itens);
        if (!$validacao['valido']) {
            error_log("ShippingService::calcularFrete() - Validação falhou");
            throw new \RuntimeException('Alguns produtos não possuem peso/dimensões cadastrados. Produtos faltando: ' . json_encode($validacao['produtos_faltando']));
        }

        error_log("ShippingService::calcularFrete() - Validação OK");

        $provider = self::getProvider($tenantId);
        $config = self::getProviderConfig($tenantId, 'shipping');
        
        error_log("ShippingService::calcularFrete() - Provider: " . get_class($provider));

        // ENRIQUECER: Buscar dimensões e peso dos produtos do banco (DADOS REAIS)
        // Isso garante que sempre usamos dados reais cadastrados no admin, não dependendo do frontend
        $itensComDimensoes = self::enriquecerItensComDimensoes($tenantId, $itens);
        
        // Validar peso e dimensões após enriquecer (garantir que vieram do banco e são > 0)
        foreach ($itensComDimensoes as $itemEnriquecido) {
            $peso = self::normalizarNumero($itemEnriquecido['peso'] ?? 0);
            $comprimento = self::normalizarNumero($itemEnriquecido['comprimento'] ?? 0);
            $largura = self::normalizarNumero($itemEnriquecido['largura'] ?? 0);
            $altura = self::normalizarNumero($itemEnriquecido['altura'] ?? 0);
            
            if ($peso <= 0) {
                throw new \RuntimeException("Produto ID {$itemEnriquecido['produto_id']} sem peso cadastrado no banco (peso: {$peso})");
            }
            
            if ($comprimento <= 0 || $largura <= 0 || $altura <= 0) {
                throw new \RuntimeException("Produto ID {$itemEnriquecido['produto_id']} sem dimensões cadastradas no banco (CxLxA: {$comprimento}x{$largura}x{$altura})");
            }
        }
        
        error_log("ShippingService::calcularFrete() - Validação pós-enriquecimento OK");

        // Calcular peso total e dimensões para validação e logs
        $pesoTotal = 0.0;
        $dimensoesTotal = ['comprimento' => 0, 'largura' => 0, 'altura' => 0];
        
        foreach ($itensComDimensoes as $item) {
            $pesoItem = self::normalizarNumero($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);
            $pesoTotal += $pesoItem * $quantidade;
            
            $comprimento = self::normalizarNumero($item['comprimento'] ?? 0);
            $largura = self::normalizarNumero($item['largura'] ?? 0);
            $altura = self::normalizarNumero($item['altura'] ?? 0);
            
            $dimensoesTotal['comprimento'] = max($dimensoesTotal['comprimento'], $comprimento);
            $dimensoesTotal['largura'] = max($dimensoesTotal['largura'], $largura);
            $dimensoesTotal['altura'] += $altura * $quantidade; // Soma alturas
        }
        
        // Obter CEP de origem da config
        $correiosConfig = $config['correios'] ?? $config;
        $cepOrigem = $correiosConfig['origem']['cep'] ?? $correiosConfig['cep_origem'] ?? '';
        
        // Validar CEP de origem
        if (empty($cepOrigem)) {
            error_log("[SHIP] ERRO: CEP de origem vazio");
            throw new \RuntimeException('CEP de origem da loja não configurado. Configure em Gateways → Frete → Correios.');
        }
        
        // Validar peso total
        if ($pesoTotal <= 0) {
            error_log("[SHIP] ERRO: Peso total inválido ({$pesoTotal}kg)");
            throw new \RuntimeException("Peso inválido para cálculo de frete (peso total: {$pesoTotal}kg)");
        }
        
        // Validar dimensões
        if ($dimensoesTotal['comprimento'] <= 0 || $dimensoesTotal['largura'] <= 0 || $dimensoesTotal['altura'] <= 0) {
            error_log("[SHIP] ERRO: Dimensões inválidas - " . json_encode($dimensoesTotal));
            throw new \RuntimeException("Dimensões inválidas para cálculo de frete (CxLxA: {$dimensoesTotal['comprimento']}x{$dimensoesTotal['largura']}x{$dimensoesTotal['altura']}cm)");
        }
        
        // Logar parâmetros REAIS antes de chamar o provider
        error_log("[SHIP] ========================================");
        error_log("[SHIP] PARÂMETROS DO CÁLCULO DE FRETE");
        error_log("[SHIP] ========================================");
        error_log("[SHIP] provider=" . get_class($provider));
        error_log("[SHIP] tenant_id={$tenantId}");
        error_log("[SHIP] cep_origem={$cepOrigem} cep_destino={$cep}");
        error_log("[SHIP] peso_total=" . var_export($pesoTotal, true) . " kg");
        error_log("[SHIP] dimensoes=" . json_encode($dimensoesTotal) . " (CxLxA em cm)");
        error_log("[SHIP] itens_enriquecidos=" . json_encode($itensComDimensoes));
        
        // Logar serviços habilitados se existir
        $servicosHabilitados = $correiosConfig['servicos'] ?? null;
        if ($servicosHabilitados !== null) {
            error_log("[SHIP] servicos_habilitados=" . json_encode($servicosHabilitados));
        }
        error_log("[SHIP] ========================================");

        $pedido = [
            'subtotal' => $subtotal,
            'itens' => $itensComDimensoes,
        ];

        $endereco = [
            'cep' => $cep,
            'zipcode' => $cep,
        ];

        error_log("ShippingService::calcularFrete() - Chamando provider->calcularOpcoesFrete()");
        
        try {
            $resultado = $provider->calcularOpcoesFrete($pedido, $endereco, $config);
            
            if (empty($resultado)) {
                error_log("[SHIP] ERRO: Provider retornou array vazio (sem opções de frete)");
                throw new \RuntimeException('Sem opções de frete para este CEP com os dados informados (peso/dimensões).');
            }
            
            error_log("ShippingService::calcularFrete() - Sucesso. Opções retornadas: " . count($resultado));
            return $resultado;
        } catch (\Exception $e) {
            error_log("ShippingService::calcularFrete() - ERRO no provider: " . $e->getMessage());
            error_log("ShippingService::calcularFrete() - Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Valida se todos os itens do carrinho possuem peso e dimensões cadastrados (DADOS REAIS DO BANCO)
     * 
     * Usa a MESMA lógica de busca do banco que enriquecerItensComDimensoes para garantir consistência.
     * Busca dados reais cadastrados no admin, não dependendo do frontend.
     * 
     * @param int $tenantId ID do tenant
     * @param array $itens Itens do carrinho (formato: ['produto_id' => ['quantidade' => int, 'preco_unitario' => float]])
     * @return array ['valido' => bool, 'produtos_faltando' => array] Produtos sem dados
     */
    public static function validarDadosItens(int $tenantId, array $itens): array
    {
        if (empty($itens)) {
            return ['valido' => false, 'produtos_faltando' => []];
        }

        error_log("ShippingService::validarDadosItens() - Validando " . count($itens) . " itens");

        $db = Database::getConnection();
        $produtoIds = array_keys($itens);
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));

        // Buscar dados REAIS do banco (mesma query que enriquecerItensComDimensoes)
        $stmt = $db->prepare("
            SELECT id, nome, peso, comprimento, largura, altura
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

        $produtosFaltando = [];
        foreach ($itens as $produtoId => $item) {
            $produto = $produtosMap[$produtoId] ?? null;
            
            // Verificar se produto existe e pertence ao tenant
            if (!$produto) {
                error_log("ShippingService::validarDadosItens() - Produto ID {$produtoId} não encontrado ou não pertence ao tenant");
                $produtosFaltando[] = [
                    'produto_id' => (int)$produtoId,
                    'nome' => 'Produto não encontrado ou não pertence ao tenant',
                ];
                continue;
            }

            // Validar peso (converter para float como no enriquecimento)
            $peso = (float)($produto['peso'] ?? 0);
            if ($peso <= 0) {
                error_log("ShippingService::validarDadosItens() - Produto ID {$produtoId} sem peso cadastrado (peso={$peso})");
                $produtosFaltando[] = [
                    'produto_id' => (int)$produtoId,
                    'nome' => $produto['nome'] ?? 'Produto #' . $produtoId,
                    'falta_peso' => true,
                ];
                continue;
            }

            // Validar dimensões (comprimento, largura, altura) - converter para float como no enriquecimento
            $comprimento = (float)($produto['comprimento'] ?? 0);
            $largura = (float)($produto['largura'] ?? 0);
            $altura = (float)($produto['altura'] ?? 0);

            if ($comprimento <= 0 || $largura <= 0 || $altura <= 0) {
                error_log("ShippingService::validarDadosItens() - Produto ID {$produtoId} sem dimensões cadastradas (C={$comprimento}, L={$largura}, A={$altura})");
                $produtosFaltando[] = [
                    'produto_id' => (int)$produtoId,
                    'nome' => $produto['nome'] ?? 'Produto #' . $produtoId,
                    'falta_dimensoes' => true,
                    'dimensoes' => [
                        'comprimento' => $comprimento,
                        'largura' => $largura,
                        'altura' => $altura,
                    ],
                ];
            } else {
                error_log("ShippingService::validarDadosItens() - Produto ID {$produtoId} OK - Peso: {$peso}kg, Dimensões: {$comprimento}x{$largura}x{$altura}cm");
            }
        }

        $valido = empty($produtosFaltando);
        error_log("ShippingService::validarDadosItens() - Validação concluída: " . ($valido ? 'VÁLIDO' : 'INVÁLIDO - ' . count($produtosFaltando) . ' produtos faltando'));

        return [
            'valido' => $valido,
            'produtos_faltando' => $produtosFaltando,
        ];
    }

    /**
     * Normaliza número convertendo vírgula para ponto antes de float
     * 
     * @param mixed $valor Valor a normalizar (string "0,20" ou float 0.20)
     * @return float Valor normalizado como float
     */
    private static function normalizarNumero($valor): float
    {
        if (is_string($valor)) {
            // Converter vírgula para ponto antes de float
            $valor = str_replace(',', '.', $valor);
        }
        return (float)$valor;
    }

    /**
     * Enriquece os itens do carrinho com dimensões e peso dos produtos (DADOS REAIS DO BANCO)
     * 
     * Busca peso e dimensões do banco de dados, garantindo que sejam dados reais cadastrados no admin.
     * Normaliza números (converte vírgula para ponto).
     * 
     * @param int $tenantId ID do tenant
     * @param array $itens Itens do carrinho (formato: ['produto_id' => ['quantidade' => int, 'preco_unitario' => float]])
     * @return array Itens enriquecidos com dimensões e peso do banco
     * @throws \RuntimeException Se algum produto não existir ou não pertencer ao tenant
     */
    private static function enriquecerItensComDimensoes(int $tenantId, array $itens): array
    {
        if (empty($itens)) {
            return [];
        }

        error_log("ShippingService::enriquecerItensComDimensoes() - Iniciando enriquecimento de " . count($itens) . " itens");

        $db = Database::getConnection();
        $produtoIds = array_keys($itens);
        $placeholders = implode(',', array_fill(0, count($produtoIds), '?'));

        // Buscar dados REAIS do banco de dados
        $stmt = $db->prepare("
            SELECT id, nome, peso, comprimento, largura, altura, preco
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

        // Verificar se todos os produtos foram encontrados
        $produtosNaoEncontrados = [];
        foreach ($produtoIds as $produtoId) {
            if (!isset($produtosMap[$produtoId])) {
                $produtosNaoEncontrados[] = $produtoId;
            }
        }

        if (!empty($produtosNaoEncontrados)) {
            error_log("ShippingService::enriquecerItensComDimensoes() - Produtos não encontrados: " . implode(', ', $produtosNaoEncontrados));
            throw new \RuntimeException('Produtos não encontrados ou não pertencem ao tenant: ' . implode(', ', $produtosNaoEncontrados));
        }

        // Enriquecer itens com dimensões e peso do banco (DADOS REAIS)
        $itensEnriquecidos = [];
        foreach ($itens as $produtoId => $item) {
            $produto = $produtosMap[$produtoId];
            
            // Normalizar e converter para float (garantir tipo correto, converter vírgula para ponto)
            $peso = self::normalizarNumero($produto['peso'] ?? 0);
            $comprimento = self::normalizarNumero($produto['comprimento'] ?? 0);
            $largura = self::normalizarNumero($produto['largura'] ?? 0);
            $altura = self::normalizarNumero($produto['altura'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);
            
            $itemEnriquecido = [
                'produto_id' => (int)$produtoId,
                'quantidade' => $quantidade,
                'preco_unitario' => (float)($item['preco_unitario'] ?? ($produto['preco'] ?? 0)),
                'peso' => $peso,
                'comprimento' => $comprimento,
                'largura' => $largura,
                'altura' => $altura,
            ];

            $itensEnriquecidos[] = $itemEnriquecido;
            
            // Log resumido após enriquecer (apenas para debug)
            error_log("ShippingService::enriquecerItensComDimensoes() - Produto ID: {$produtoId}, Peso: {$peso}kg, Dimensões: {$comprimento}x{$largura}x{$altura}cm, Qty: {$quantidade}");
        }

        error_log("ShippingService::enriquecerItensComDimensoes() - Enriquecimento concluído para " . count($itensEnriquecidos) . " itens");

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


