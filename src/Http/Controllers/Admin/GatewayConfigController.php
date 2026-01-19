<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class GatewayConfigController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar configurações atuais
        $stmt = $db->prepare("
            SELECT tipo, codigo, config_json, ativo 
            FROM tenant_gateways 
            WHERE tenant_id = :tenant_id 
            ORDER BY tipo ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $gateways = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $paymentGateway = null;
        $shippingGateway = null;

        foreach ($gateways as $gateway) {
            if ($gateway['tipo'] === 'payment') {
                $paymentGateway = $gateway;
            } elseif ($gateway['tipo'] === 'shipping') {
                $shippingGateway = $gateway;
            }
        }

        // Valores padrão se não existir
        if (!$paymentGateway) {
            $paymentGateway = ['codigo' => 'manual', 'config_json' => null, 'ativo' => 1];
        }
        if (!$shippingGateway) {
            $shippingGateway = ['codigo' => 'simples', 'config_json' => null, 'ativo' => 1];
        }

        // Decodificar config_json para facilitar acesso na view
        $shippingConfig = [];
        if (!empty($shippingGateway['config_json'])) {
            $decoded = json_decode($shippingGateway['config_json'], true);
            if (is_array($decoded)) {
                $shippingConfig = $decoded['correios'] ?? $decoded;
                // Mascarar senha ao carregar (só mostra se usuário preencher novamente)
                if (isset($shippingConfig['credenciais']['senha']) && !empty($shippingConfig['credenciais']['senha'])) {
                    $shippingConfig['credenciais']['senha_masked'] = true;
                    $shippingConfig['credenciais']['senha'] = '********'; // Não sobrescreverá no banco se não mudar
                }
            }
        }

        $message = $_SESSION['gateway_message'] ?? null;
        $messageType = $_SESSION['gateway_message_type'] ?? 'success';
        unset($_SESSION['gateway_message'], $_SESSION['gateway_message_type']);

        $this->viewWithLayout('admin/layouts/store', 'admin/gateways/index-content', [
            'pageTitle' => 'Integrações / Gateways',
            'paymentGateway' => $paymentGateway,
            'shippingGateway' => $shippingGateway,
            'shippingConfig' => $shippingConfig,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function store(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $paymentGatewayCode = trim($_POST['payment_gateway_code'] ?? 'manual');
        $paymentConfigJson = trim($_POST['payment_config_json'] ?? '');
        $shippingGatewayCode = trim($_POST['shipping_gateway_code'] ?? 'simples');
        $shippingConfigJson = trim($_POST['shipping_config_json'] ?? '');

        $errors = [];

        // Validar JSON se fornecido
        if (!empty($paymentConfigJson)) {
            $decoded = json_decode($paymentConfigJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'JSON de configuração de pagamento inválido: ' . json_last_error_msg();
            }
        }

        // Processar configuração de frete (campos específicos ou JSON avançado)
        if ($shippingGatewayCode === 'correios') {
            // Processar campos específicos do Correios
            $correiosConfig = self::processarConfigCorreios($_POST, $shippingGateway, $errors);
            
            // Se tiver JSON avançado e campos específicos, JSON avançado tem prioridade
            if (!empty($shippingConfigJson)) {
                $decoded = json_decode($shippingConfigJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'JSON avançado de frete inválido: ' . json_last_error_msg();
                } else {
                    // JSON avançado sobrescreve campos específicos
                    $shippingConfigJson = json_encode(['correios' => $decoded], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            } else {
                // Usar campos específicos montados
                $shippingConfigJson = json_encode(['correios' => $correiosConfig], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } else {
            // Para outros providers, validar JSON se fornecido
            if (!empty($shippingConfigJson)) {
                $decoded = json_decode($shippingConfigJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'JSON de configuração de frete inválido: ' . json_last_error_msg();
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['gateway_message'] = implode('<br>', $errors);
            $_SESSION['gateway_message_type'] = 'error';
            $this->redirect('/admin/configuracoes/gateways');
            return;
        }

        try {
            $db->beginTransaction();

            // Atualizar/Criar gateway de pagamento
            $stmt = $db->prepare("
                INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json, ativo, created_at, updated_at)
                VALUES (:tenant_id, 'payment', :codigo, :config_json, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    codigo = :codigo,
                    config_json = :config_json,
                    updated_at = NOW()
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'codigo' => $paymentGatewayCode,
                'config_json' => !empty($paymentConfigJson) ? $paymentConfigJson : null,
            ]);

            // Atualizar/Criar gateway de frete
            $stmt = $db->prepare("
                INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json, ativo, created_at, updated_at)
                VALUES (:tenant_id, 'shipping', :codigo, :config_json, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    codigo = :codigo,
                    config_json = :config_json,
                    updated_at = NOW()
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'codigo' => $shippingGatewayCode,
                'config_json' => !empty($shippingConfigJson) ? $shippingConfigJson : null,
            ]);

            $db->commit();
            $_SESSION['gateway_message'] = 'Configurações de gateways salvas com sucesso!';
            $_SESSION['gateway_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['gateway_message'] = 'Erro ao salvar configurações: ' . $e->getMessage();
            $_SESSION['gateway_message_type'] = 'error';
        }

        $this->redirect('/admin/configuracoes/gateways');
    }

    /**
     * Processa campos específicos do Correios e monta estrutura JSON
     * 
     * @param array $post Dados do POST
     * @param array $shippingGateway Configuração atual do gateway
     * @param array &$errors Array de erros (por referência)
     * @return array Configuração processada
     */
    private static function processarConfigCorreios(array $post, array $shippingGateway, array &$errors): array
    {
        // Ler dados do remetente
        $cepOrigem = preg_replace('/\D/', '', $post['correios_cep_origem'] ?? '');
        $remetenteNome = trim($post['correios_remetente_nome'] ?? '');
        $remetenteTelefone = trim($post['correios_remetente_telefone'] ?? '');
        $remetenteDocumento = trim($post['correios_remetente_documento'] ?? '');
        
        // Ler credenciais
        $usuario = trim($post['correios_usuario'] ?? '');
        $senhaNova = trim($post['correios_senha'] ?? '');
        
        // Se senha vazia ou mascarada, manter a anterior (se existir)
        $senha = '';
        if (empty($senhaNova) || $senhaNova === '********' || strlen($senhaNova) < 3) {
            // Buscar senha anterior do banco
            $configAtual = [];
            if (!empty($shippingGateway['config_json'])) {
                $decoded = json_decode($shippingGateway['config_json'], true);
                if (is_array($decoded)) {
                    $correiosAtual = $decoded['correios'] ?? $decoded;
                    if (isset($correiosAtual['credenciais']['senha']) && !empty($correiosAtual['credenciais']['senha'])) {
                        $senha = $correiosAtual['credenciais']['senha'];
                    }
                }
            }
        } else {
            // Usar senha nova
            $senha = $senhaNova;
        }
        
        // Validações
        if (empty($cepOrigem) || strlen($cepOrigem) !== 8) {
            $errors[] = 'CEP de origem deve ter 8 dígitos.';
        }
        
        if (empty($remetenteNome)) {
            $errors[] = 'Nome do remetente é obrigatório.';
        }
        
        if (empty($usuario)) {
            $errors[] = 'Usuário dos Correios é obrigatório.';
        }
        
        // Validar senha: deve ter senha nova OU senha anterior no banco
        if (empty($senha)) {
            // Se não tem senha nova nem anterior, erro
            $temSenhaAnterior = false;
            if (!empty($shippingGateway['config_json'])) {
                $decoded = json_decode($shippingGateway['config_json'], true);
                if (is_array($decoded)) {
                    $correiosAtual = $decoded['correios'] ?? $decoded;
                    if (isset($correiosAtual['credenciais']['senha']) && !empty($correiosAtual['credenciais']['senha'])) {
                        $temSenhaAnterior = true;
                    }
                }
            }
            if (!$temSenhaAnterior) {
                $errors[] = 'Senha dos Correios é obrigatória.';
            }
        }
        
        // Validar serviços habilitados
        $pacHabilitado = isset($post['correios_servico_pac']) && $post['correios_servico_pac'] === '1';
        $sedexHabilitado = isset($post['correios_servico_sedex']) && $post['correios_servico_sedex'] === '1';
        
        if (!$pacHabilitado && !$sedexHabilitado) {
            $errors[] = 'Pelo menos um serviço deve estar habilitado (PAC ou SEDEX).';
        }
        
        // Montar estrutura
        $config = [
            'origem' => [
                'cep' => $cepOrigem,
                'nome' => $remetenteNome,
                'telefone' => preg_replace('/\D/', '', $remetenteTelefone),
                'documento' => preg_replace('/\D/', '', $remetenteDocumento),
                'endereco' => [
                    'logradouro' => trim($post['correios_remetente_logradouro'] ?? ''),
                    'numero' => trim($post['correios_remetente_numero'] ?? ''),
                    'complemento' => trim($post['correios_remetente_complemento'] ?? ''),
                    'bairro' => trim($post['correios_remetente_bairro'] ?? ''),
                    'cidade' => trim($post['correios_remetente_cidade'] ?? ''),
                    'uf' => strtoupper(trim($post['correios_remetente_estado'] ?? '')),
                ],
            ],
            'credenciais' => [
                'usuario' => $usuario,
                'senha' => $senha,
                'cartao_postagem' => trim($post['correios_cartao_postagem'] ?? ''),
                'contrato' => trim($post['correios_contrato'] ?? ''),
                'codigo_administrativo' => trim($post['correios_codigo_administrativo'] ?? ''),
                'diretoria' => trim($post['correios_diretoria'] ?? ''),
            ],
            'servicos' => [
                'pac' => $pacHabilitado,
                'sedex' => $sedexHabilitado,
            ],
            'seguro' => [
                'habilitado' => false, // Sempre desabilitado
            ],
        ];
        
        // Remover campos vazios de endereço para manter JSON limpo
        foreach ($config['origem']['endereco'] as $key => $value) {
            if (empty($value)) {
                unset($config['origem']['endereco'][$key]);
            }
        }
        
        return $config;
    }
}


