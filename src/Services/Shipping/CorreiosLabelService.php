<?php

namespace App\Services\Shipping;

use App\Core\Database;

/**
 * Serviço isolado para geração de etiquetas via Correios (contrato direto)
 * 
 * Responsabilidades:
 * - Criar postagem/envio a partir de pedido
 * - Gerar etiqueta PDF
 * - Obter código de rastreamento
 * - Idempotência (não regenerar se já existe)
 * - Sem seguro (sempre desabilitado)
 * 
 * ⚠️ NOTA: Implementação aguarda definição da API dos Correios (SIGEPWeb ou API específica do contrato)
 */
class CorreiosLabelService
{
    /**
     * Cria/registra postagem a partir de um pedido e retorna dados da etiqueta
     * 
     * @param array $pedido Dados do pedido completo (com itens)
     * @param array $config Configurações do gateway (cep_origem, credenciais Correios, etc)
     * @return array Dados da etiqueta gerada:
     *   - 'postagem_id' => string ID da postagem nos Correios
     *   - 'tracking_code' => string Código de rastreamento (OB)
     *   - 'label_url' => string URL ou caminho do PDF da etiqueta
     *   - 'service_code' => string Código do serviço (PAC=40126, SEDEX=40096)
     *   - 'service_name' => string Nome do serviço (PAC/SEDEX)
     * @throws \Exception Em caso de erro
     */
    public static function createShipmentFromOrder(array $pedido, array $config): array
    {
        // Ler configuração do Correios (formato: {'correios': {...}})
        $correiosConfig = $config['correios'] ?? $config;
        
        $origem = $correiosConfig['origem'] ?? [];
        $credenciais = $correiosConfig['credenciais'] ?? [];
        
        $cepOrigem = $origem['cep'] ?? $config['cep_origem'] ?? '';
        
        if (empty($cepOrigem)) {
            throw new \Exception('CEP de origem não configurado no gateway de frete.');
        }

        // Validar credenciais Correios
        $usuario = $credenciais['usuario'] ?? $config['usuario'] ?? '';
        $senha = $credenciais['senha'] ?? $config['senha'] ?? '';
        
        if (empty($usuario) || empty($senha)) {
            throw new \Exception('Credenciais dos Correios não configuradas (usuário/senha necessários).');
        }

        // Limpar CEPs
        $cepOrigem = preg_replace('/\D/', '', $cepOrigem);
        $cepDestino = preg_replace('/\D/', '', $pedido['entrega_cep'] ?? '');

        if (strlen($cepOrigem) !== 8 || strlen($cepDestino) !== 8) {
            throw new \Exception('CEPs inválidos. Verifique CEP de origem e CEP de destino.');
        }

        // Validar itens do pedido
        $itens = $pedido['itens'] ?? [];
        if (empty($itens)) {
            throw new \Exception('Pedido sem itens. Não é possível gerar etiqueta.');
        }

        // Preparar dados da postagem (passar config completo)
        $postagemData = self::prepararDadosPostagem($pedido, $itens, $cepOrigem, $cepDestino, $correiosConfig);

        // Identificar serviço (PAC ou SEDEX) do método de frete do pedido
        $metodoFrete = $pedido['metodo_frete'] ?? '';
        $serviceCode = self::extrairServiceCode($metodoFrete);

        if (!$serviceCode) {
            throw new \Exception('Método de frete inválido. Use PAC ou SEDEX.');
        }

        try {
            // Criar postagem nos Correios (via SIGEPWeb ou API conforme contrato)
            $postagemId = self::criarPostagem($postagemData, $serviceCode, $correiosConfig);

            // Gerar etiqueta e obter código de rastreamento
            $labelData = self::gerarEtiqueta($postagemId, $correiosConfig);

            return [
                'postagem_id' => $postagemId,
                'tracking_code' => $labelData['tracking_code'] ?? '',
                'label_url' => $labelData['label_url'] ?? '',
                'service_code' => $serviceCode,
                'service_name' => $serviceCode === '40126' ? 'PAC' : 'SEDEX',
            ];
        } catch (\Exception $e) {
            error_log("Erro ao gerar etiqueta Correios para pedido #{$pedido['id']}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtém URL ou caminho para imprimir etiqueta PDF
     * 
     * @param string $postagemId ID da postagem nos Correios
     * @param array $config Configurações do gateway
     * @return string URL ou caminho do PDF da etiqueta
     */
    public static function getLabelPdfUrl(string $postagemId, array $config): string
    {
        // ⚠️ Implementar conforme API dos Correios
        // Pode retornar URL direta ou endpoint interno que serve o PDF
        
        // Por enquanto, retorna endpoint interno que buscará e servirá o PDF
        return "/admin/pedidos/etiqueta/{$postagemId}/pdf";
    }

    /**
     * Obtém dados de rastreamento do envio
     * 
     * @param string $trackingCode Código de rastreamento (OB)
     * @param array $config Configurações do gateway
     * @return array Dados de tracking
     * @throws \Exception
     */
    public static function getTracking(string $trackingCode, array $config): array
    {
        // ⚠️ Implementar consulta de rastreamento via API dos Correios
        // Por enquanto, retorna estrutura básica
        
        throw new \Exception('Consulta de rastreamento ainda não implementada. Aguardando definição da API.');
    }

    /**
     * Prepara dados da postagem a partir do pedido
     */
    private static function prepararDadosPostagem(
        array $pedido, 
        array $itens, 
        string $cepOrigem, 
        string $cepDestino,
        array $config
    ): array {
        // Calcular peso e dimensões totais
        $pesoTotal = 0;
        $dimensoes = ['comprimento' => 20, 'largura' => 20, 'altura' => 10];

        foreach ($itens as $item) {
            $peso = (float)($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);
            if ($peso > 0) {
                $pesoTotal += $peso * $quantidade;
            }
        }

        $pesoTotal = max(0.1, $pesoTotal); // Mínimo 0.1 kg

        // Se tiver dimensões nos itens, calcular
        $maiorComprimento = 0;
        $maiorLargura = 0;
        $somaAltura = 0;

        foreach ($itens as $item) {
            $comprimento = (float)($item['comprimento'] ?? 0);
            $largura = (float)($item['largura'] ?? 0);
            $altura = (float)($item['altura'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);

            if ($comprimento > 0 && $largura > 0 && $altura > 0) {
                $maiorComprimento = max($maiorComprimento, $comprimento);
                $maiorLargura = max($maiorLargura, $largura);
                $somaAltura += $altura * $quantidade;
            }
        }

        if ($maiorComprimento > 0 && $maiorLargura > 0) {
            $dimensoes = [
                'comprimento' => max(16, $maiorComprimento),
                'largura' => max(11, $maiorLargura),
                'altura' => max(2, $somaAltura),
            ];
        }

        // Obter dados do remetente (tenant) - formato correios
        $origemData = $config['origem'] ?? [];
        $enderecoOrigem = $origemData['endereco'] ?? [];
        
        $remetenteNome = $origemData['nome'] ?? 'Loja';
        $remetenteTelefone = $origemData['telefone'] ?? '';
        $remetenteDocumento = $origemData['documento'] ?? '';

        // Montar array da postagem conforme estrutura dos Correios
        // ⚠️ Ajustar formato conforme documentação da API dos Correios (SIGEPWeb)
        return [
            'remetente' => [
                'nome' => $remetenteNome,
                'telefone' => preg_replace('/\D/', '', $remetenteTelefone),
                'email' => '', // Pode ser adicionado no futuro
                'documento' => $remetenteDocumento, // CPF/CNPJ
                'cnpj' => $remetenteDocumento,
                'inscricao_estadual' => '', // Pode ser adicionado depois
                'endereco' => $enderecoOrigem['logradouro'] ?? '',
                'numero' => $enderecoOrigem['numero'] ?? '',
                'complemento' => $enderecoOrigem['complemento'] ?? '',
                'bairro' => $enderecoOrigem['bairro'] ?? '',
                'cidade' => $enderecoOrigem['cidade'] ?? '',
                'estado' => $enderecoOrigem['uf'] ?? '',
                'cep' => $cepOrigem,
            ],
            'destinatario' => [
                'nome' => $pedido['cliente_nome'] ?? '',
                'telefone' => preg_replace('/\D/', '', $pedido['cliente_telefone'] ?? ''),
                'email' => $pedido['cliente_email'] ?? '',
                'documento' => '', // Cliente pode não ter CPF/CNPJ
                'endereco' => $pedido['entrega_logradouro'] ?? '',
                'numero' => $pedido['entrega_numero'] ?? '',
                'complemento' => $pedido['entrega_complemento'] ?? '',
                'bairro' => $pedido['entrega_bairro'] ?? '',
                'cidade' => $pedido['entrega_cidade'] ?? '',
                'estado' => $pedido['entrega_estado'] ?? '',
                'cep' => $cepDestino,
            ],
            'pacote' => [
                'peso' => $pesoTotal,
                'comprimento' => $dimensoes['comprimento'],
                'largura' => $dimensoes['largura'],
                'altura' => $dimensoes['altura'],
                'valor_declarado' => 0, // SEM SEGURO (obrigatório)
            ],
            'opcoes' => [
                'mao_propria' => false,
                'aviso_recebimento' => false,
                'valor_declarado' => 0, // SEM SEGURO
                'seguro' => false, // SEM SEGURO
            ],
        ];
    }

    /**
     * Extrai código do serviço do método de frete
     * correios_pac = 40126 (PAC), correios_sedex = 40096 (SEDEX)
     */
    private static function extrairServiceCode(string $metodoFrete): ?string
    {
        if (stripos($metodoFrete, 'pac') !== false || $metodoFrete === 'correios_pac') {
            return '40126'; // PAC
        }
        
        if (stripos($metodoFrete, 'sedex') !== false || $metodoFrete === 'correios_sedex') {
            return '40096'; // SEDEX
        }

        return null;
    }

    /**
     * Cria postagem nos Correios (via SIGEPWeb ou API conforme contrato)
     * 
     * ⚠️ AGUARDANDO: Implementação da API dos Correios conforme contrato do cliente
     * 
     * @param array $postagemData Dados da postagem
     * @param string $serviceCode Código do serviço (40126=PAC, 40096=SEDEX)
     * @param array $config Configurações com credenciais
     * @return string ID da postagem nos Correios
     * @throws \Exception
     */
    private static function criarPostagem(array $postagemData, string $serviceCode, array $config): string
    {
        // ⚠️ IMPLEMENTAR: Chamada à API dos Correios (SIGEPWeb ou API específica)
        // Necessário:
        // - URL/endpoint da API
        // - Formato de autenticação (usuário/senha, token, etc)
        // - Formato do payload esperado
        // - Formato da resposta
        
        throw new \Exception('Criação de postagem nos Correios ainda não implementada. Aguardando definição da API do contrato.');
    }

    /**
     * Gera etiqueta e obtém código de rastreamento
     * 
     * ⚠️ AGUARDANDO: Implementação da API dos Correios conforme contrato do cliente
     * 
     * @param string $postagemId ID da postagem
     * @param array $config Configurações com credenciais
     * @return array Dados da etiqueta: ['tracking_code' => string, 'label_url' => string]
     * @throws \Exception
     */
    private static function gerarEtiqueta(string $postagemId, array $config): array
    {
        // ⚠️ IMPLEMENTAR: Geração de etiqueta via API dos Correios
        // Pode retornar:
        // - PDF da etiqueta (bytes ou URL)
        // - Código de rastreamento (OB)
        
        throw new \Exception('Geração de etiqueta dos Correios ainda não implementada. Aguardando definição da API do contrato.');
    }
}
