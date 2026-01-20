<?php

namespace App\Services\Shipping\Providers;

use App\Services\Shipping\ShippingProviderInterface;
use App\Services\Shipping\CorreiosTokenService;

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
            error_log("[SHIP] CorreiosProvider: CEP origem ou destino vazio");
            throw new \RuntimeException('CEP de origem ou destino não informado.');
        }

        // Limpar CEPs (remover caracteres não numéricos)
        $cepOrigem = preg_replace('/\D/', '', $cepOrigem);
        $cepDestino = preg_replace('/\D/', '', $cepDestino);

        if (strlen($cepOrigem) !== 8 || strlen($cepDestino) !== 8) {
            error_log("[SHIP] CorreiosProvider: CEP inválido (origem: {$cepOrigem}, destino: {$cepDestino})");
            throw new \RuntimeException('CEP de origem ou destino inválido (deve conter 8 dígitos).');
        }

        // Calcular peso e dimensões totais do pacote (SEM FALLBACK - validação já feita)
        $pesoTotal = $this->calcularPesoTotal($itens);
        $dimensoes = $this->calcularDimensoes($itens);

        // Validar unidades e valores mínimos para API dos Correios
        // Peso em kg (float)
        $pesoTotal = max(0.1, (float)$pesoTotal); // Mínimo 0.1 kg
        
        // Dimensões em cm (inteiros)
        $dimensoes['comprimento'] = max(16, (int)round($dimensoes['comprimento'])); // Mínimo 16 cm
        $dimensoes['largura'] = max(11, (int)round($dimensoes['largura'])); // Mínimo 11 cm
        $dimensoes['altura'] = max(2, (int)round($dimensoes['altura'])); // Mínimo 2 cm

        // Valores máximos
        $pesoTotal = min(30.0, $pesoTotal); // Máximo 30 kg (PAC/SEDEX padrão)
        
        // Validar se após ajustes ainda está inválido
        if ($pesoTotal <= 0 || $dimensoes['comprimento'] <= 0 || $dimensoes['largura'] <= 0 || $dimensoes['altura'] <= 0) {
            error_log("[SHIP] CorreiosProvider: Dados inválidos para cálculo de frete: peso={$pesoTotal}, dimensoes=" . json_encode($dimensoes));
            throw new \RuntimeException('Dados inválidos para cálculo de frete (peso ou dimensões inválidos).');
        }
        
        // Preparar dados para cotação (SEM FALLBACK - validação já feita)
        $produtos = [];
        foreach ($itens as $item) {
            $produtos[] = [
                'id' => $item['produto_id'] ?? 0,
                'width' => max(11, (float)($item['largura'] ?? 0)),
                'height' => max(2, (float)($item['altura'] ?? 0)),
                'length' => max(16, (float)($item['comprimento'] ?? 0)),
                'weight' => max(0.1, (float)($item['peso'] ?? 0)),
                'insurance_value' => 0, // Sem seguro
                'quantity' => (int)($item['quantidade'] ?? 1),
            ];
        }

        // Verificar modo de integração
        $modoIntegracao = $correiosConfig['modo_integracao'] ?? 'cws';
        
        // Validar credenciais conforme modo
        $credenciais = $correiosConfig['credenciais'] ?? [];
        $usuario = $credenciais['usuario'] ?? $config['usuario'] ?? '';
        
        if ($modoIntegracao === 'cws') {
            // Modo CWS: exige usuario + codigo_acesso_apis (ignora senha)
            $codigoAcessoApis = $credenciais['codigo_acesso_apis'] ?? $credenciais['chave_acesso_cws'] ?? '';
            if (empty($usuario) || empty($codigoAcessoApis)) {
                error_log("[SHIP] CorreiosProvider: Credenciais dos Correios não configuradas (modo CWS: usuário e código de acesso às APIs são obrigatórios).");
                throw new \RuntimeException('Credenciais dos Correios não configuradas. Verifique as configurações no painel administrativo.');
            }
        } else {
            // Modo Legado: exige usuario + senha (não implementado ainda)
            error_log("[SHIP] CorreiosProvider: Modo Legado/SIGEP ainda não implementado para cálculo de frete.");
            throw new \RuntimeException('Modo de integração Legado/SIGEP ainda não implementado.');
        }

        try {
            // Chamar API dos Correios (SIGEPWeb/API Preços)
            $opcoesApi = $this->consultarCorreios($cepOrigem, $cepDestino, $pesoTotal, $dimensoes, $correiosConfig);
            
            // Logar resultado bruto da API
            error_log("[SHIP] CorreiosProvider::consultarCorreios() - Opções retornadas pela API: " . count($opcoesApi));
            if (!empty($opcoesApi)) {
                error_log("[SHIP] CorreiosProvider::consultarCorreios() - Resposta API: " . json_encode($opcoesApi));
            } else {
                error_log("[SHIP] CorreiosProvider::consultarCorreios() - API retornou array vazio (sem opções)");
            }
            
            // Converter resposta da API para formato esperado
            $opcoesFormatadas = $this->formatarOpcoesFrete($opcoesApi);
            
            if (empty($opcoesFormatadas)) {
                error_log("[SHIP] CorreiosProvider::formatarOpcoesFrete() - Opções formatadas ficaram vazias");
                error_log("[SHIP] CorreiosProvider::formatarOpcoesFrete() - Resposta API original: " . json_encode($opcoesApi));
                throw new \RuntimeException('Nenhuma opção de frete disponível para este CEP com os dados informados.');
            }
            
            return $opcoesFormatadas;
        } catch (\Exception $e) {
            // Em caso de erro, logar motivo técnico completo (sem credenciais) e relançar exceção
            $mensagemErro = $e->getMessage();
            // Remover credenciais da mensagem de erro antes de logar
            $mensagemErro = preg_replace('/(usuario|senha|chave|token|credencial)[=:]\s*[^\s,;]+/i', '$1=***', $mensagemErro);
            error_log("[SHIP] CorreiosProvider: ERRO ao calcular frete");
            error_log("[SHIP] CorreiosProvider: Mensagem: " . $mensagemErro);
            error_log("[SHIP] CorreiosProvider: Arquivo: " . $e->getFile());
            error_log("[SHIP] CorreiosProvider: Linha: " . $e->getLine());
            error_log("[SHIP] CorreiosProvider: Stack trace: " . $e->getTraceAsString());
            // Relançar exceção para que seja tratada pelo ShippingService/ShippingController
            throw $e;
        } catch (\Throwable $e) {
            // Capturar qualquer outro erro (Error, etc)
            error_log("[SHIP] CorreiosProvider: ERRO GRAVE (Throwable)");
            error_log("[SHIP] CorreiosProvider: Mensagem: " . $e->getMessage());
            error_log("[SHIP] CorreiosProvider: Arquivo: " . $e->getFile());
            error_log("[SHIP] CorreiosProvider: Linha: " . $e->getLine());
            error_log("[SHIP] CorreiosProvider: Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Calcula peso total do carrinho (SEM FALLBACK)
     * 
     * @param array $itens Itens com peso e quantidade
     * @return float Peso total em kg
     * @throws \RuntimeException Se algum item não tiver peso
     */
    private function calcularPesoTotal(array $itens): float
    {
        $pesoTotal = 0.0;

        foreach ($itens as $item) {
            $peso = (float)($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);
            
            if ($peso <= 0) {
                $produtoId = $item['produto_id'] ?? 'N/A';
                throw new \RuntimeException("Item sem peso cadastrado: produto_id={$produtoId}");
            }
            
            $pesoTotal += $peso * $quantidade;
        }

        if ($pesoTotal <= 0) {
            throw new \RuntimeException("Peso total inválido após cálculo");
        }

        return max(0.1, $pesoTotal); // Mínimo 0.1 kg para API
    }

    /**
     * Calcula dimensões totais do pacote (SEM FALLBACK)
     * 
     * Estratégia: pega a maior dimensão de cada eixo (comprimento, largura, altura)
     * e soma as alturas proporcionais ao peso
     * 
     * @param array $itens Itens com dimensões
     * @return array ['comprimento' => float, 'largura' => float, 'altura' => float] em cm
     * @throws \RuntimeException Se algum item não tiver dimensões
     */
    private function calcularDimensoes(array $itens): array
    {
        $maiorComprimento = 0;
        $maiorLargura = 0;
        $somaAltura = 0;
        $pesoTotal = 0;
        $totalItens = 0;

        foreach ($itens as $item) {
            $comprimento = (float)($item['comprimento'] ?? 0);
            $largura = (float)($item['largura'] ?? 0);
            $altura = (float)($item['altura'] ?? 0);
            $peso = (float)($item['peso'] ?? 0);
            $quantidade = (int)($item['quantidade'] ?? 1);

            // Validar que todas as dimensões estão presentes e válidas
            if ($comprimento <= 0 || $largura <= 0 || $altura <= 0) {
                $produtoId = $item['produto_id'] ?? 'N/A';
                throw new \RuntimeException("Item sem dimensões cadastradas: produto_id={$produtoId}");
            }

            // Validar unidades: dimensões devem estar em cm (valores razoáveis)
            // Se dimensão > 200cm, provavelmente está em mm, dividir por 10
            if ($comprimento > 200) $comprimento = $comprimento / 10;
            if ($largura > 200) $largura = $largura / 10;
            if ($altura > 200) $altura = $altura / 10;

            $maiorComprimento = max($maiorComprimento, $comprimento);
            $maiorLargura = max($maiorLargura, $largura);
            
            // Somar altura proporcional ao peso
            if ($peso > 0) {
                $pesoTotal += $peso * $quantidade;
                $somaAltura += $altura * $peso * $quantidade;
            } else {
                $somaAltura += $altura * $quantidade;
            }
            
            $totalItens += $quantidade;
        }

        if ($maiorComprimento <= 0 || $maiorLargura <= 0 || $totalItens <= 0) {
            throw new \RuntimeException("Dimensões inválidas após cálculo");
        }

        // Altura média ponderada
        $alturaMedia = $pesoTotal > 0 
            ? ($somaAltura / $pesoTotal)
            : ($somaAltura / $totalItens);

        return [
            'comprimento' => max(16, $maiorComprimento),
            'largura' => max(11, $maiorLargura),
            'altura' => max(2, $alturaMedia),
        ];
    }

    /**
     * Consulta API dos Correios para cotação de frete (Preço v3 + Prazo v3)
     * 
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param float $pesoTotal Peso total em kg
     * @param array $dimensoes Dimensões (comprimento, largura, altura) em cm
     * @param array $config Configurações com credenciais
     * @return array Resposta da API com preços e prazos
     * @throws \Exception Em caso de erro na API
     */
    private function consultarCorreios(string $cepOrigem, string $cepDestino, float $pesoTotal, array $dimensoes, array $config): array
    {
        // Verificar modo de integração
        $modoIntegracao = $config['modo_integracao'] ?? 'cws';
        
        if ($modoIntegracao !== 'cws') {
            throw new \Exception('Modo de integração Legado/SIGEP ainda não implementado para cálculo de frete.');
        }
        
        // Obter credenciais (modo CWS)
        $credenciais = $config['credenciais'] ?? [];
        $usuario = $credenciais['usuario'] ?? '';
        // Priorizar codigo_acesso_apis (novo), depois chave_acesso_cws (compatibilidade)
        $codigoAcessoApis = $credenciais['codigo_acesso_apis'] ?? $credenciais['chave_acesso_cws'] ?? '';
        $contrato = $credenciais['contrato'] ?? null;

        if (empty($usuario) || empty($codigoAcessoApis)) {
            throw new \Exception('Credenciais incompletas: usuário e código de acesso às APIs são obrigatórios no modo CWS.');
        }

        // Obter token via CorreiosTokenService (com contrato se disponível)
        try {
            $token = CorreiosTokenService::getToken($usuario, $codigoAcessoApis, null, $contrato);
        } catch (\Exception $e) {
            error_log("Erro ao obter token Correios CWS: " . $e->getMessage());
            throw new \Exception('Erro ao autenticar na API Correios CWS: ' . $e->getMessage());
        }

        // Preparar dados para cotação
        // API Preço v1 usa códigos diferentes: 03220 (SEDEX) e 03298 (PAC)
        // Códigos legacy 40126/40096 são para modo legado/SIGEP
        $servicosHabilitados = $config['servicos'] ?? ['pac' => true, 'sedex' => true];
        
        // Filtrar serviços habilitados (usar códigos da API Preço v1)
        $servicosParaCotar = [];
        if ($servicosHabilitados['pac'] ?? true) {
            // Usar código configurado ou padrão 03298 (PAC na API Preço v1)
            $codigoPac = $config['credenciais']['codigo_servico_pac'] ?? '03298';
            $servicosParaCotar[$codigoPac] = 'PAC';
        }
        if ($servicosHabilitados['sedex'] ?? true) {
            // Usar código configurado ou padrão 03220 (SEDEX na API Preço v1)
            $codigoSedex = $config['credenciais']['codigo_servico_sedex'] ?? '03220';
            $servicosParaCotar[$codigoSedex] = 'SEDEX';
        }

        if (empty($servicosParaCotar)) {
            throw new \Exception('Nenhum serviço habilitado para cotação.');
        }

        $resultados = [];

        // Consultar Preço v3 e Prazo v3 para cada serviço
        foreach ($servicosParaCotar as $codigoServico => $nomeServico) {
            try {
                // Consultar Preço v1 (usa códigos 03298=PAC, 03220=SEDEX)
                $preco = $this->consultarPrecoV3($token, $cepOrigem, $cepDestino, $pesoTotal, $dimensoes, $codigoServico);
                
                // Converter código da API Preço v1 para código da API Prazo v3
                $codigoPrazoV3 = $this->converterCodigoPrecoParaPrazo($codigoServico);
                
                // Consultar Prazo v3 (usa códigos 40126=PAC, 40096=SEDEX)
                $prazo = $this->consultarPrazoV3($token, $cepOrigem, $cepDestino, $codigoPrazoV3);

                // Combinar resultados
                $resultados[] = [
                    'codigo' => $codigoServico, // Manter código da API Preço v1 para referência
                    'codigo_prazo_v3' => $codigoPrazoV3, // Código usado na API Prazo v3
                    'nome' => $nomeServico,
                    'preco' => $preco,
                    'prazo' => $prazo,
                ];
            } catch (\Exception $e) {
                // Logar erro mas continuar com outros serviços
                error_log("Erro ao consultar serviço {$codigoServico} ({$nomeServico}): " . $e->getMessage());
                // Não adiciona ao array, mas continua processando outros serviços
            }
        }

        if (empty($resultados)) {
            throw new \Exception('Não foi possível obter cotações dos Correios. Verifique as credenciais e tente novamente.');
        }

        return $resultados;
    }

    /**
     * Consulta Preço v1 da API Correios CWS (API Preço v3 não existe, usar v1)
     * 
     * psObjeto = peso em gramas (string numérica), não JSON
     * coProduto = código do serviço (03220=SEDEX, 03298=PAC na API Preço v1)
     * 
     * @param string $token Token de autenticação
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param float $pesoTotal Peso em kg
     * @param array $dimensoes Dimensões (comprimento, largura, altura) em cm
     * @param string $codigoServico Código do serviço (03298=PAC, 03220=SEDEX na API Preço v1)
     * @return float Preço do frete
     * @throws \Exception
     */
    private function consultarPrecoV3(string $token, string $cepOrigem, string $cepDestino, float $pesoTotal, array $dimensoes, string $codigoServico): float
    {
        // API Preço v1 usa GET com query parameters
        // psObjeto = peso em gramas (string numérica)
        // Converter kg para gramas (multiplicar por 1000 e arredondar)
        $psObjeto = (string)round(max(0.1, $pesoTotal) * 1000);
        $tpObjeto = '2'; // Tipo do objeto (manual usa "2")
        
        // Valores mínimos conforme manual
        $comprimento = max(16, $dimensoes['comprimento']);
        $largura = max(11, $dimensoes['largura']);
        $altura = max(2, $dimensoes['altura']);
        
        $url = 'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico . 
               '?cepOrigem=' . urlencode($cepOrigem) .
               '&cepDestino=' . urlencode($cepDestino) .
               '&psObjeto=' . urlencode($psObjeto) .
               '&tpObjeto=' . urlencode($tpObjeto) .
               '&comprimento=' . urlencode((string)$comprimento) .
               '&largura=' . urlencode((string)$largura) .
               '&altura=' . urlencode((string)$altura);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log("Erro ao consultar Preço v1 Correios: " . $curlError);
            throw new \Exception('Erro ao consultar preço: ' . $curlError);
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['msgs'][0] ?? "Erro ao consultar Preço v1 (HTTP {$httpCode})";
            error_log("Erro Preço v1 Correios: HTTP {$httpCode} - {$errorMsg}");
            throw new \Exception("Erro ao consultar preço (HTTP {$httpCode}): {$errorMsg}");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Erro ao decodificar resposta Preço v1: " . json_last_error_msg());
            throw new \Exception('Resposta inválida da API de preço');
        }

        // Extrair preço da resposta
        // API Preço v1 retorna preço no campo 'pcFinal' (pode vir como string com vírgula)
        $preco = $data['pcFinal'] ?? $data['preco'] ?? $data['valor'] ?? $data['vlrFrete'] ?? $data['valorFrete'] ?? null;

        if ($preco === null) {
            error_log("Preço não encontrado na resposta Preço v1: " . substr($response, 0, 200));
            throw new \Exception('Preço não retornado pela API');
        }
        
        // Converter string com vírgula para float (ex: "17,52" -> 17.52)
        if (is_string($preco)) {
            $preco = str_replace(',', '.', $preco);
        }
        
        if (!is_numeric($preco)) {
            error_log("Preço inválido na resposta Preço v1: " . substr($response, 0, 200));
            throw new \Exception('Preço inválido retornado pela API');
        }

        return (float)$preco;
    }

    /**
     * Consulta Prazo v3 da API Correios CWS (com fallback para v1 se v3 retornar 404)
     * 
     * @param string $token Token de autenticação
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param string $codigoServico Código do serviço (40126=PAC, 40096=SEDEX para v3, ou 03298=PAC, 03220=SEDEX para v1)
     * @return int Prazo em dias úteis
     * @throws \Exception
     */
    private function consultarPrazoV3(string $token, string $cepOrigem, string $cepDestino, string $codigoServico): int
    {
        $baseUrl = 'https://api.correios.com.br';
        
        // Tentar API Prazo v3 primeiro
        $urlV3 = $baseUrl . '/prazo/v3/nacional/' . $codigoServico;

        // Preparar payload
        $payload = [
            'cepOrigem' => $cepOrigem,
            'cepDestino' => $cepDestino,
        ];

        $ch = curl_init($urlV3);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Erro cURL: " . $curlError);
            throw new \Exception('Erro ao consultar prazo: ' . $curlError);
        }

        // Se v3 retornar 404, fazer fallback para v1 (GET com query string)
        if ($httpCode === 404) {
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Prazo v3 retornou 404, fazendo fallback para Prazo v1");
            
            // Converter código v3 para v1 se necessário
            // API Prazo v1 usa os mesmos códigos da API Preço v1: 03298 (PAC), 03220 (SEDEX)
            $codigoServicoV1 = $this->converterCodigoPrazoV3ParaV1($codigoServico) ?? $codigoServico;
            
            $urlV1 = $baseUrl . '/prazo/v1/nacional/' . $codigoServicoV1 . 
                     '?cepOrigem=' . urlencode($cepOrigem) .
                     '&cepDestino=' . urlencode($cepDestino);
            
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Tentando Prazo v1: {$urlV1}");

            $ch = curl_init($urlV1);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response === false || !empty($curlError)) {
                error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Erro cURL v1: " . $curlError);
                throw new \Exception('Erro ao consultar prazo v1: ' . $curlError);
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - ERRO HTTP v1: {$httpCode}");
                error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Resposta bruta v1 (resumida): " . substr($response, 0, 500));
                throw new \Exception("Erro ao consultar prazo v1 (HTTP {$httpCode})");
            }
        } elseif ($httpCode !== 200 && $httpCode !== 201) {
            // Se v3 retornou outro erro (não 404)
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - ERRO HTTP v3: {$httpCode}");
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Resposta bruta v3 (resumida): " . substr($response, 0, 500));
            throw new \Exception("Erro ao consultar prazo v3 (HTTP {$httpCode})");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Erro ao decodificar resposta: " . json_last_error_msg());
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Resposta bruta: " . substr($response, 0, 500));
            throw new \Exception('Resposta inválida da API de prazo');
        }

        // Extrair prazo da resposta
        // Formato pode variar, tentar campos comuns
        $prazo = $data['prazoEntrega'] ?? $data['prazo'] ?? $data['diasUteis'] ?? $data['prazoEntregaDias'] ?? null;

        if ($prazo === null || !is_numeric($prazo)) {
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Prazo não encontrado na resposta");
            error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Resposta completa: " . json_encode($data));
            throw new \Exception('Prazo não retornado pela API');
        }

        error_log("[SHIP] CorreiosProvider::consultarPrazoV3() - Prazo retornado: {$prazo} dias úteis");

        return (int)$prazo;
    }

    /**
     * Converte código da API Prazo v3 para código da API Prazo v1
     * 
     * API Prazo v3: 40126 (PAC), 40096 (SEDEX)
     * API Prazo v1: 03298 (PAC), 03220 (SEDEX) - usa os mesmos códigos da API Preço v1
     * 
     * @param string $codigoPrazoV3 Código da API Prazo v3
     * @return string|null Código da API Prazo v1 ou null se não mapeado
     */
    private function converterCodigoPrazoV3ParaV1(string $codigoPrazoV3): ?string
    {
        $conversao = [
            '40126' => '03298', // PAC: Prazo v3 -> Prazo v1
            '40096' => '03220', // SEDEX: Prazo v3 -> Prazo v1
        ];
        
        return $conversao[$codigoPrazoV3] ?? null;
    }

    /**
     * Formata resposta da API dos Correios para formato esperado pelo sistema
     * 
     * @param array $respostaApi Resposta da API dos Correios (array de resultados)
     * @return array Opções de frete formatadas
     */
    private function formatarOpcoesFrete(array $respostaApi): array
    {
        $opcoes = [];

        if (!is_array($respostaApi) || empty($respostaApi)) {
            return [];
        }

        // Mapear códigos de serviço para códigos do sistema
        // Suporta códigos da API Preço v1 (03298, 03220) e da API Prazo v3 (40126, 40096)
        $codigoMap = [
            '03298' => 'correios_pac',    // API Preço v1 - PAC
            '03220' => 'correios_sedex',  // API Preço v1 - SEDEX
            '40126' => 'correios_pac',    // API Prazo v3 - PAC
            '40096' => 'correios_sedex',  // API Prazo v3 - SEDEX
        ];

        // Processar cada resultado da API
        foreach ($respostaApi as $resultado) {
            $codigoServico = $resultado['codigo'] ?? '';
            $nomeServico = $resultado['nome'] ?? '';
            $preco = $resultado['preco'] ?? 0;
            $prazo = $resultado['prazo'] ?? 0;

            // Verificar se serviço é válido
            if (empty($codigoServico) || !isset($codigoMap[$codigoServico])) {
                error_log("Código de serviço não reconhecido: {$codigoServico}");
                continue;
            }

            // Usar código da API Prazo v3 como código_servico padrão
            // Se for código da API Preço v1, usar o código da API Prazo v3 equivalente
            $codigoServicoPadrao = $resultado['codigo_prazo_v3'] ?? $this->converterCodigoPrecoParaPrazo($codigoServico) ?? $codigoServico;
            
            // Criar opção formatada (padronizado)
            $opcoes[] = [
                'codigo' => $codigoMap[$codigoServico],
                'codigo_servico' => $codigoServicoPadrao, // Código padrão (40126, 40096) para compatibilidade
                'codigo_servico_preco' => $codigoServico, // Código da API Preço v1 (03298, 03220)
                'nome_servico' => $nomeServico, // Nome do serviço (PAC, SEDEX)
                'titulo' => $nomeServico, // Compatibilidade com formato antigo
                'preco' => (float)$preco, // Preço em decimal
                'valor' => (float)$preco, // Compatibilidade com formato antigo
                'prazo' => (int)$prazo, // Prazo em dias (inteiro)
                'prazo_formatado' => $this->formatarPrazo((int)$prazo), // Prazo formatado (string)
                'descricao' => 'Correios',
            ];
        }

        // Ordenar por menor preço (default)
        usort($opcoes, function($a, $b) {
            return ($a['preco'] ?? $a['valor'] ?? 0) <=> ($b['preco'] ?? $b['valor'] ?? 0);
        });

        return $opcoes;
    }

    /**
     * Converte código da API Preço v1 para código da API Prazo v3
     * 
     * API Preço v1: 03298 (PAC), 03220 (SEDEX)
     * API Prazo v3: 40126 (PAC), 40096 (SEDEX)
     * 
     * @param string $codigoPrecoV1 Código da API Preço v1
     * @return string|null Código da API Prazo v3 ou null se não mapeado
     */
    private function converterCodigoPrecoParaPrazo(string $codigoPrecoV1): ?string
    {
        $conversao = [
            '03298' => '40126', // PAC: Preço v1 -> Prazo v3
            '03220' => '40096', // SEDEX: Preço v1 -> Prazo v3
        ];
        
        return $conversao[$codigoPrecoV1] ?? null;
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
