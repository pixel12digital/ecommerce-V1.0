<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Tenant\TenantContext;
use App\Services\CartService;
use App\Services\Shipping\ShippingService;

class ShippingController extends Controller
{
    /**
     * Valida se os itens do carrinho possuem peso/dimensões antes de calcular frete
     * 
     * POST /api/shipping/validate
     * Body: {}
     * 
     * Retorna JSON:
     * { success: true, valido: bool, produtos_faltando: [...] }
     */
    public function validate(): void
    {
        // Inicializar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = TenantContext::id();
        $cart = CartService::get();
        
        if (empty($cart['items'])) {
            $this->json([
                'success' => true,
                'valido' => false,
                'produtos_faltando' => [],
                'message' => 'Carrinho está vazio',
            ], 200);
            return;
        }

        $validacao = ShippingService::validarDadosItens($tenantId, $cart['items']);
        
        $this->json([
            'success' => true,
            'valido' => $validacao['valido'],
            'produtos_faltando' => $validacao['produtos_faltando'],
        ], 200);
    }

    /**
     * Calcula opções de frete via AJAX
     * 
     * POST /api/shipping/calculate
     * Body: { cepDestino: string }
     * 
     * Retorna JSON:
     * { success: true, opcoes: [...], errors: [] }
     * ou
     * { success: false, message: string, errors: [], produtos_faltando: [...] }
     */
    public function calculate(): void
    {
        // Garantir que sempre retornamos JSON, mesmo em caso de erro fatal
        try {
            // Inicializar sessão se necessário
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $tenantId = TenantContext::id();
        } catch (\Throwable $e) {
            // Capturar qualquer erro fatal antes de qualquer processamento
            error_log("ShippingController::calculate() - ERRO FATAL inicial: " . $e->getMessage());
            error_log("ShippingController::calculate() - Stack trace: " . $e->getTraceAsString());
            
            $this->json([
                'success' => false,
                'message' => 'Erro interno ao calcular frete. Tente novamente.',
                'errors' => []
            ], 500);
            return;
        }
        
        // Validar método HTTP
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Método não permitido',
                'errors' => ['Apenas POST é permitido']
            ], 405);
            return;
        }

        // Ler JSON do body ou dados do POST
        $input = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];
        } else {
            $input = $_POST;
        }

        $cepDestino = trim($input['cepDestino'] ?? $input['cep'] ?? '');

        // Validar CEP
        $errors = [];
        if (empty($cepDestino)) {
            $errors[] = 'CEP é obrigatório';
        } else {
            // Limpar CEP (remover caracteres não numéricos)
            $cepLimpo = preg_replace('/\D/', '', $cepDestino);
            if (strlen($cepLimpo) !== 8) {
                $errors[] = 'CEP deve conter 8 dígitos';
            } else {
                $cepDestino = $cepLimpo;
            }
        }

        // Validar carrinho não vazio
        $cart = CartService::get();
        if (empty($cart['items'])) {
            $errors[] = 'Carrinho está vazio';
        }

        if (!empty($errors)) {
            $this->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $errors
            ], 400);
            return;
        }

        try {
            // Log payload recebido para debug
            error_log("ShippingController::calculate() - Iniciando cálculo de frete");
            error_log("ShippingController::calculate() - CEP destino: " . $cepDestino);
            error_log("ShippingController::calculate() - Tenant ID: " . $tenantId);
            
            // Obter dados do carrinho
            $subtotal = CartService::getSubtotal();
            $itens = $cart['items'];
            
            error_log("ShippingController::calculate() - Subtotal: " . $subtotal);
            error_log("ShippingController::calculate() - Itens do carrinho: " . json_encode($itens));
            
            // Calcular frete usando o serviço existente
            $opcoesFrete = ShippingService::calcularFrete($tenantId, $cepDestino, $subtotal, $itens);
            
            error_log("ShippingController::calculate() - Opções de frete retornadas: " . count($opcoesFrete));

            // Formatar resposta
            $opcoesFormatadas = [];
            foreach ($opcoesFrete as $opcao) {
                $opcoesFormatadas[] = [
                    'codigo' => $opcao['codigo'] ?? '',
                    'codigo_servico' => $opcao['codigo_servico'] ?? $opcao['codigo'] ?? '',
                    'servico' => $opcao['titulo'] ?? $opcao['nome_servico'] ?? '',
                    'preco' => (float)($opcao['valor'] ?? $opcao['preco'] ?? 0),
                    'prazo' => $opcao['prazo'] ?? $opcao['prazo_formatado'] ?? 'A consultar',
                    'descricao' => $opcao['descricao'] ?? ''
                ];
            }

            // Se não houver opções de frete, retornar erro
            if (empty($opcoesFormatadas)) {
                error_log("ShippingController::calculate() - Nenhuma opção de frete retornada para CEP: {$cepDestino}");
                $this->json([
                    'success' => false,
                    'message' => 'Sem opções de frete para este CEP com os dados informados (peso/dimensões).',
                    'errors' => ['Sem opções de frete para este CEP com os dados informados (peso/dimensões).']
                ], 400);
                return;
            }

            $this->json([
                'success' => true,
                'opcoes' => $opcoesFormatadas,
                'errors' => []
            ]);

        } catch (\RuntimeException $e) {
            // Erro de validação (produtos sem peso/dimensões)
            $mensagem = $e->getMessage();
            $produtosFaltando = [];
            
            // Tentar extrair produtos faltando da mensagem
            if (preg_match('/Produtos faltando: (.+)$/', $mensagem, $matches)) {
                $produtosFaltando = json_decode($matches[1], true) ?? [];
            }
            
            error_log("ShippingController::calculate() - Validação de frete falhou: " . $mensagem);
            error_log("ShippingController::calculate() - Stack trace: " . $e->getTraceAsString());
            
            $this->json([
                'success' => false,
                'message' => 'Não é possível calcular o frete porque alguns produtos não possuem peso/dimensões cadastrados.',
                'errors' => ['Produtos sem peso/dimensões cadastrados'],
                'produtos_faltando' => $produtosFaltando,
            ], 400);
        } catch (\TypeError $e) {
            // Erro de tipo (ex: esperava int, recebeu string)
            error_log("ShippingController::calculate() - ERRO DE TIPO");
            error_log("ShippingController::calculate() - Mensagem: " . $e->getMessage());
            error_log("ShippingController::calculate() - Arquivo: " . $e->getFile());
            error_log("ShippingController::calculate() - Linha: " . $e->getLine());
            error_log("ShippingController::calculate() - Stack trace: " . $e->getTraceAsString());
            
            $this->json([
                'success' => false,
                'message' => 'Erro de tipo de dados ao calcular frete. Verifique os logs.',
                'errors' => []
            ], 400);
        } catch (\Exception $e) {
            // Logar erro técnico completo para debug
            error_log("ShippingController::calculate() - ERRO FATAL ao calcular frete");
            error_log("ShippingController::calculate() - Mensagem: " . $e->getMessage());
            error_log("ShippingController::calculate() - Arquivo: " . $e->getFile());
            error_log("ShippingController::calculate() - Linha: " . $e->getLine());
            error_log("ShippingController::calculate() - Stack trace: " . $e->getTraceAsString());
            
            // Garantir resposta JSON mesmo em caso de erro
            $this->json([
                'success' => false,
                'message' => 'Não foi possível calcular o frete no momento. Verifique o CEP e tente novamente.',
                'errors' => []
            ], 500);
        } catch (\Throwable $e) {
            // Capturar qualquer outro erro (Error, etc)
            error_log("ShippingController::calculate() - ERRO GRAVE (Throwable)");
            error_log("ShippingController::calculate() - Mensagem: " . $e->getMessage());
            error_log("ShippingController::calculate() - Arquivo: " . $e->getFile());
            error_log("ShippingController::calculate() - Linha: " . $e->getLine());
            error_log("ShippingController::calculate() - Stack trace: " . $e->getTraceAsString());
            
            $this->json([
                'success' => false,
                'message' => 'Erro interno ao calcular frete. Tente novamente.',
                'errors' => []
            ], 500);
        }
    }
}
