<?php

namespace App\Services\Shipping\Providers;

use App\Services\Shipping\ShippingProviderInterface;

/**
 * Provider de frete via Correios (contrato direto)
 * 
 * Suporta:
 * - Cálculo de frete PAC e SEDEX (sem seguro)
 * - Via API dos Correios (SIGEPWeb/API Preços conforme contrato)
 * 
 * ⚠️ NOTA: Implementação aguarda definição da API dos Correios (SIGEPWeb ou API específica do contrato)
 */
class CorreiosProvider implements ShippingProviderInterface
{
    /**
     * Calcula opções de frete para o pedido via Correios (API direta)
     *
     * @param array $pedido Dados do pedido:
     *   - 'subtotal' => float (subtotal do pedido)
     *   - 'itens' => array (cada item contém: produto_id, quantidade, preco_unitario, peso, comprimento, largura, altura)
     * @param array $endereco Endereço de entrega:
     *   - 'cep' => string (CEP de destino)
     *   - 'zipcode' => string (CEP de destino, formato alternativo)
     * @param array $config Configurações do gateway:
     *   - 'cep_origem' => string (CEP de origem - obrigatório)
     *   - 'usuario' => string (Usuário API Correios - obrigatório)
     *   - 'senha' => string (Senha API Correios - obrigatório)
     *   - 'codigo_administrativo' => string (Código administrativo do contrato - opcional)
     *   - 'cartao_postagem' => string (Cartão de postagem - opcional)
     * @return array Lista de opções de frete
     */
    public function calcularOpcoesFrete(array $pedido, array $endereco, array $config = []): array
    {
        // Ler configuração do Correios (formato: {'correios': {...}})
        $correiosConfig = $config['correios'] ?? $config;
        
        $cepOrigem = $correiosConfig['origem']['cep'] ?? $correiosConfig['cep_origem'] ?? '';
        $cepDestino = $endereco['cep'] ?? $endereco['zipcode'] ?? '';
        $itens = $pedido['itens'] ?? [];

        // Validar CEPs
        if (empty($cepOrigem) || empty($cepDestino)) {
            return [];
        }

        // Limpar CEPs (remover caracteres não numéricos)
        $cepOrigem = preg_replace('/\D/', '', $cepOrigem);
        $cepDestino = preg_replace('/\D/', '', $cepDestino);

        if (strlen($cepOrigem) !== 8 || strlen($cepDestino) !== 8) {
            return [];
        }

        // Calcular peso e dimensões totais do pacote
        $pesoTotal = $this->calcularPesoTotal($itens);
        $dimensoes = $this->calcularDimensoes($itens);

        // Valores mínimos para API dos Correios
        $pesoTotal = max(0.1, $pesoTotal); // Mínimo 0.1 kg
        $dimensoes['comprimento'] = max(16, $dimensoes['comprimento']); // Mínimo 16 cm
        $dimensoes['largura'] = max(11, $dimensoes['largura']); // Mínimo 11 cm
        $dimensoes['altura'] = max(2, $dimensoes['altura']); // Mínimo 2 cm

        // Valores máximos
        $pesoTotal = min(30, $pesoTotal); // Máximo 30 kg (PAC/SEDEX padrão)
        
        // Preparar dados para cotação
        $produtos = [];
        foreach ($itens as $item) {
            $produtos[] = [
                'id' => $item['produto_id'] ?? 0,
                'width' => max(11, $item['largura'] ?? $dimensoes['largura']),
                'height' => max(2, $item['altura'] ?? $dimensoes['altura']),
                'length' => max(16, $item['comprimento'] ?? $dimensoes['comprimento']),
                'weight' => max(0.1, $item['peso'] ?? ($pesoTotal / count($itens))),
                'insurance_value' => 0, // Sem seguro
                'quantity' => $item['quantidade'] ?? 1,
            ];
        }

        // Se não houver itens com dimensões, usar dimensões totais
        if (empty($produtos)) {
            $produtos[] = [
                'id' => 0,
                'width' => $dimensoes['largura'],
                'height' => $dimensoes['altura'],
                'length' => $dimensoes['comprimento'],
                'weight' => $pesoTotal,
                'insurance_value' => 0,
                'quantity' => 1,
            ];
        }

        // Validar credenciais Correios
        $credenciais = $correiosConfig['credenciais'] ?? [];
        $usuario = $credenciais['usuario'] ?? $config['usuario'] ?? '';
        $senha = $credenciais['senha'] ?? $config['senha'] ?? '';
        
        if (empty($usuario) || empty($senha)) {
            error_log("Credenciais dos Correios não configuradas para cálculo de frete.");
            return [];
        }

        try {
            // Chamar API dos Correios (SIGEPWeb/API Preços)
            $opcoesApi = $this->consultarCorreios($cepOrigem, $cepDestino, $pesoTotal, $dimensoes, $correiosConfig);
            
            // Converter resposta da API para formato esperado
            return $this->formatarOpcoesFrete($opcoesApi);
        } catch (\Exception $e) {
            // Em caso de erro, logar e retornar array vazio
            error_log("Erro ao calcular frete via Correios: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula peso total do carrinho
     * 
     * @param array $itens Itens com peso e quantidade
     * @return float Peso total em kg
     */
    private function calcularPesoTotal(array $itens): float
    {
        $pesoTotal = 0.0;

        foreach ($itens as $item) {
            $peso = (float)($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);
            
            if ($peso > 0) {
                $pesoTotal += $peso * $quantidade;
            }
        }

        return max(0.1, $pesoTotal); // Mínimo 0.1 kg
    }

    /**
     * Calcula dimensões totais do pacote
     * 
     * Estratégia: pega a maior dimensão de cada eixo (comprimento, largura, altura)
     * e soma as alturas proporcionais ao peso
     * 
     * @param array $itens Itens com dimensões
     * @return array ['comprimento' => float, 'largura' => float, 'altura' => float] em cm
     */
    private function calcularDimensoes(array $itens): array
    {
        $maiorComprimento = 0;
        $maiorLargura = 0;
        $somaAltura = 0;
        $pesoTotal = 0;

        foreach ($itens as $item) {
            $comprimento = (float)($item['comprimento'] ?? 0);
            $largura = (float)($item['largura'] ?? 0);
            $altura = (float)($item['altura'] ?? 0);
            $peso = (float)($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);

            if ($comprimento > 0 && $largura > 0 && $altura > 0) {
                $maiorComprimento = max($maiorComprimento, $comprimento);
                $maiorLargura = max($maiorLargura, $largura);
                
                // Somar altura proporcional ao peso
                if ($peso > 0) {
                    $pesoTotal += $peso * $quantidade;
                    $somaAltura += $altura * $peso * $quantidade;
                } else {
                    $somaAltura += $altura * $quantidade;
                }
            }
        }

        // Se não houver dimensões, usar valores padrão
        if ($maiorComprimento === 0 || $maiorLargura === 0) {
            return [
                'comprimento' => 20.0,
                'largura' => 20.0,
                'altura' => 10.0,
            ];
        }

        // Altura média ponderada
        $alturaMedia = $pesoTotal > 0 
            ? ($somaAltura / $pesoTotal) 
            : max(2, $somaAltura);

        return [
            'comprimento' => max(16, $maiorComprimento),
            'largura' => max(11, $maiorLargura),
            'altura' => max(2, $alturaMedia),
        ];
    }

    /**
     * Consulta API dos Correios para cotação de frete
     * 
     * ⚠️ AGUARDANDO: Implementação da API dos Correios conforme contrato do cliente
     * 
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param float $pesoTotal Peso total em kg
     * @param array $dimensoes Dimensões (comprimento, largura, altura) em cm
     * @param array $config Configurações com credenciais
     * @return array Resposta da API
     * @throws \Exception Em caso de erro na API
     */
    private function consultarCorreios(string $cepOrigem, string $cepDestino, float $pesoTotal, array $dimensoes, array $config): array
    {
        // ⚠️ IMPLEMENTAR: Chamada à API dos Correios (SIGEPWeb/API Preços)
        // Necessário:
        // - URL/endpoint da API (ex: https://apps.correios.com.br/calculador-remoto-de-precos-e-prazos/...)
        // - Formato de autenticação (usuário/senha, SOAP, REST, etc)
        // - Formato do payload esperado
        // - Formato da resposta (SOAP XML ou JSON)
        
        // Por enquanto, retorna array vazio (não quebra o sistema, apenas não calcula frete)
        // Quando implementado, deve retornar opções PAC e SEDEX
        
        throw new \Exception('Consulta de frete via API dos Correios ainda não implementada. Aguardando definição da API do contrato.');
    }

    /**
     * Formata resposta da API dos Correios para formato esperado pelo sistema
     * 
     * @param array $respostaApi Resposta da API dos Correios
     * @return array Opções de frete formatadas
     */
    private function formatarOpcoesFrete(array $respostaApi): array
    {
        $opcoes = [];

        // ⚠️ Ajustar conforme formato de resposta da API dos Correios
        // Exemplo esperado: array com serviços PAC e SEDEX
        
        if (!is_array($respostaApi)) {
            return [];
        }

        // Processar resposta e criar opções PAC e SEDEX
        // Formato esperado de cada opção:
        // [
        //   'codigo' => 'correios_pac' ou 'correios_sedex',
        //   'titulo' => 'PAC' ou 'SEDEX',
        //   'valor' => float (valor do frete),
        //   'prazo' => string (prazo em dias úteis),
        //   'descricao' => 'Correios'
        // ]

        // Por enquanto, retorna array vazio (será preenchido quando API estiver implementada)
        return $opcoes;
    }

    /**
     * Formata prazo de entrega em dias úteis
     * 
     * @param int $diasUteis Número de dias úteis
     * @return string Prazo formatado
     */
    private function formatarPrazo(int $diasUteis): string
    {
        if ($diasUteis <= 0) {
            return 'A consultar';
        }

        if ($diasUteis === 1) {
            return '1 dia útil';
        }

        return "{$diasUteis} dias úteis";
    }
}
