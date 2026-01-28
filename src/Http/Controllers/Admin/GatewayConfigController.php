<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Shipping\CorreiosTokenService;
use App\Services\Shipping\Providers\CorreiosProvider;

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
                    // Garantir modo_integracao padrão se não existir
                    if (!isset($shippingConfig['modo_integracao'])) {
                        $shippingConfig['modo_integracao'] = 'cws';
                    }
                    // Mascarar senha ao carregar (só mostra se usuário preencher novamente)
                    if (isset($shippingConfig['credenciais']['senha']) && !empty($shippingConfig['credenciais']['senha'])) {
                        $shippingConfig['credenciais']['senha_masked'] = true;
                        $shippingConfig['credenciais']['senha'] = '********'; // Não sobrescreverá no banco se não mudar
                    }
                    // Mascarar código de acesso às APIs ao carregar (novo campo)
                    if (isset($shippingConfig['credenciais']['codigo_acesso_apis']) && !empty($shippingConfig['credenciais']['codigo_acesso_apis'])) {
                        $shippingConfig['credenciais']['codigo_acesso_apis_masked'] = true;
                        $shippingConfig['credenciais']['codigo_acesso_apis'] = '********';
                    }
                    // Compatibilidade: mascarar chave de acesso CWS antiga também
                    if (isset($shippingConfig['credenciais']['chave_acesso_cws']) && !empty($shippingConfig['credenciais']['chave_acesso_cws'])) {
                        $shippingConfig['credenciais']['chave_acesso_cws_masked'] = true;
                        $shippingConfig['credenciais']['chave_acesso_cws'] = '********';
                        // Se não tiver codigo_acesso_apis, migrar chave_acesso_cws para ele (compatibilidade)
                        if (!isset($shippingConfig['credenciais']['codigo_acesso_apis_masked'])) {
                            $shippingConfig['credenciais']['codigo_acesso_apis_masked'] = true;
                        }
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

        // Buscar gateway de frete atual do banco (se existir)
        $stmt = $db->prepare("
            SELECT tipo, codigo, config_json, ativo 
            FROM tenant_gateways 
            WHERE tenant_id = :tenant_id 
            AND tipo = 'shipping'
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $shippingGateway = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Se não existir, criar estrutura padrão
        if (!$shippingGateway) {
            $shippingGateway = ['codigo' => $shippingGatewayCode, 'config_json' => null, 'ativo' => 1];
        }

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
            
            // Verificar se JSON override está habilitado
            $jsonOverrideEnabled = isset($_POST['correios_json_override_enabled']) && $_POST['correios_json_override_enabled'] === '1';
            
            // Se tiver JSON avançado E toggle habilitado, JSON avançado tem prioridade
            if ($jsonOverrideEnabled && !empty($shippingConfigJson)) {
                $decoded = json_decode($shippingConfigJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors[] = 'JSON avançado de frete inválido: ' . json_last_error_msg() . '. Verifique a sintaxe JSON.';
                } else {
                    // Validar estrutura básica do JSON
                    if (!is_array($decoded)) {
                        $errors[] = 'JSON avançado deve ser um objeto JSON válido.';
                    } else {
                        // JSON avançado sobrescreve campos específicos
                        $shippingConfigJson = json_encode(['correios' => $decoded], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            } else {
                // Usar campos específicos montados (ignorar JSON se toggle não estiver habilitado)
                if ($jsonOverrideEnabled && !empty($shippingConfigJson)) {
                    // Toggle habilitado mas JSON vazio ou inválido - usar campos específicos
                    $shippingConfigJson = json_encode(['correios' => $correiosConfig], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    // Toggle desabilitado - sempre usar campos específicos
                    $shippingConfigJson = json_encode(['correios' => $correiosConfig], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
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
                    codigo = VALUES(codigo),
                    config_json = VALUES(config_json),
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
                    codigo = VALUES(codigo),
                    config_json = VALUES(config_json),
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
        // Ler modo de integração
        $modoIntegracao = trim($post['correios_modo_integracao'] ?? 'cws');
        if (!in_array($modoIntegracao, ['cws', 'legado'])) {
            $modoIntegracao = 'cws'; // Default
        }
        
        // Ler dados do remetente
        $cepOrigem = preg_replace('/\D/', '', $post['correios_cep_origem'] ?? '');
        $remetenteNome = trim($post['correios_remetente_nome'] ?? '');
        $remetenteTelefone = trim($post['correios_remetente_telefone'] ?? '');
        $remetenteDocumento = trim($post['correios_remetente_documento'] ?? '');
        
        // Ler credenciais
        // Se campo estiver disabled, usar valor do hidden
        $usuario = trim($post['correios_usuario'] ?? $post['correios_usuario_keep'] ?? '');
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
        
        // Ler código de acesso às APIs (novo campo) ou chave de acesso CWS (antigo, para compatibilidade)
        $codigoAcessoApisNovo = trim($post['correios_codigo_acesso_apis'] ?? '');
        $chaveAcessoCwsNova = trim($post['correios_chave_acesso_cws'] ?? ''); // Compatibilidade com campo antigo
        
        // Se código vazio ou mascarado, manter o anterior (se existir)
        $codigoAcessoApis = '';
        if (empty($codigoAcessoApisNovo) || $codigoAcessoApisNovo === '********' || strlen($codigoAcessoApisNovo) < 3) {
            // Buscar código anterior do banco (novo campo ou campo antigo migrado)
            if (!empty($shippingGateway['config_json'])) {
                $decoded = json_decode($shippingGateway['config_json'], true);
                if (is_array($decoded)) {
                    $correiosAtual = $decoded['correios'] ?? $decoded;
                    // Priorizar codigo_acesso_apis, depois chave_acesso_cws (compatibilidade)
                    if (isset($correiosAtual['credenciais']['codigo_acesso_apis']) && !empty($correiosAtual['credenciais']['codigo_acesso_apis'])) {
                        $codigoAcessoApis = $correiosAtual['credenciais']['codigo_acesso_apis'];
                    } elseif (isset($correiosAtual['credenciais']['chave_acesso_cws']) && !empty($correiosAtual['credenciais']['chave_acesso_cws'])) {
                        // Migrar chave_acesso_cws antiga para codigo_acesso_apis
                        $codigoAcessoApis = $correiosAtual['credenciais']['chave_acesso_cws'];
                    }
                }
            }
        } else {
            // Usar código novo (só se tiver pelo menos 3 caracteres para evitar valores acidentais)
            $codigoAcessoApis = $codigoAcessoApisNovo;
        }
        
        // Se ainda não tiver código e veio chave_acesso_cws antiga no POST, usar ela (compatibilidade)
        if (empty($codigoAcessoApis) && !empty($chaveAcessoCwsNova) && $chaveAcessoCwsNova !== '********' && strlen($chaveAcessoCwsNova) >= 3) {
            $codigoAcessoApis = $chaveAcessoCwsNova;
        }
        
        // Ler contrato (priorizar campo do modo ativo, depois campos avançados/legado)
        $contrato = '';
        if ($modoIntegracao === 'cws') {
            // Modo CWS: usar campo correios_contrato (do campo CWS)
            $contrato = trim($post['correios_contrato'] ?? '');
            // Se vazio, tentar campos avançados (compatibilidade)
            if (empty($contrato)) {
                $contrato = trim($post['correios_contrato_advanced'] ?? '');
            }
        } else {
            // Modo Legado: usar campo correios_contrato_legado
            $contrato = trim($post['correios_contrato_legado'] ?? '');
            // Se vazio, tentar campo avançado (compatibilidade)
            if (empty($contrato)) {
                $contrato = trim($post['correios_contrato_advanced'] ?? '');
            }
        }
        
        // Se ainda vazio, buscar do banco (pode estar salvo)
        if (empty($contrato) && !empty($shippingGateway['config_json'])) {
            $decoded = json_decode($shippingGateway['config_json'], true);
            if (is_array($decoded)) {
                $correiosAtual = $decoded['correios'] ?? $decoded;
                if (isset($correiosAtual['credenciais']['contrato']) && !empty($correiosAtual['credenciais']['contrato'])) {
                    $contrato = trim($correiosAtual['credenciais']['contrato']);
                }
            }
        }
        
        
        // Validações
        if (empty($cepOrigem) || strlen($cepOrigem) !== 8 || $cepOrigem === '00000000') {
            $errors[] = 'Informe o CEP de origem válido (8 dígitos).';
        }
        
        if (empty($remetenteNome)) {
            $errors[] = 'Nome do remetente é obrigatório.';
        }
        
        if (empty($usuario)) {
            $errors[] = 'Usuário dos Correios é obrigatório.';
        }
        
        // Validações por modo de integração
        if ($modoIntegracao === 'cws') {
            // Modo CWS: exige usuario + codigo_acesso_apis + contrato, NÃO exige senha
            if (empty($codigoAcessoApis)) {
                $temCodigoAnterior = false;
                if (!empty($shippingGateway['config_json'])) {
                    $decoded = json_decode($shippingGateway['config_json'], true);
                    if (is_array($decoded)) {
                        $correiosAtual = $decoded['correios'] ?? $decoded;
                        if ((isset($correiosAtual['credenciais']['codigo_acesso_apis']) && !empty($correiosAtual['credenciais']['codigo_acesso_apis'])) ||
                            (isset($correiosAtual['credenciais']['chave_acesso_cws']) && !empty($correiosAtual['credenciais']['chave_acesso_cws']))) {
                            $temCodigoAnterior = true;
                        }
                    }
                }
                if (!$temCodigoAnterior) {
                    $errors[] = 'Código de acesso às APIs (CWS) é obrigatório no modo CWS.';
                }
            }
            
            // Contrato obrigatório no modo CWS (mas só validar se realmente não existir no banco)
            if (empty($contrato)) {
                $temContratoAnterior = false;
                if (!empty($shippingGateway['config_json'])) {
                    $decoded = json_decode($shippingGateway['config_json'], true);
                    if (is_array($decoded)) {
                        $correiosAtual = $decoded['correios'] ?? $decoded;
                        if (isset($correiosAtual['credenciais']['contrato']) && !empty($correiosAtual['credenciais']['contrato'])) {
                            $temContratoAnterior = true;
                            // Usar contrato do banco se não veio no POST
                            $contrato = trim($correiosAtual['credenciais']['contrato']);
                        }
                    }
                }
                if (!$temContratoAnterior) {
                    $errors[] = 'Nº do contrato (Correios) é obrigatório no modo CWS.';
                }
            }
            
            // Senha não é obrigatória no modo CWS (pode estar vazia)
        } else {
            // Modo Legado/SIGEP: exige usuario + senha
            if (empty($senha)) {
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
                    $errors[] = 'Senha dos Correios é obrigatória no modo Legado/SIGEP.';
                }
            }
            // Chave CWS não é obrigatória no modo legado (pode estar vazia)
        }
        
        // Validar serviços habilitados
        $pacHabilitado = isset($post['correios_servico_pac']) && $post['correios_servico_pac'] === '1';
        $sedexHabilitado = isset($post['correios_servico_sedex']) && $post['correios_servico_sedex'] === '1';
        
        if (!$pacHabilitado && !$sedexHabilitado) {
            $errors[] = 'Pelo menos um serviço deve estar habilitado (PAC ou SEDEX).';
        }
        
        // Montar estrutura
        $config = [
            'modo_integracao' => $modoIntegracao,
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
                'codigo_acesso_apis' => $codigoAcessoApis,
                // Manter chave_acesso_cws para compatibilidade (será migrado automaticamente)
                'chave_acesso_cws' => $codigoAcessoApis, // Mesmo valor para compatibilidade
                'cartao_postagem' => trim($post['correios_cartao_postagem'] ?? ''),
                'contrato' => $contrato,
                'codigo_administrativo' => trim($post['correios_codigo_administrativo'] ?? ''),
                'diretoria' => trim($post['correios_diretoria'] ?? ''),
                // Códigos de serviço para API Preço v1 (padrões: 03298=PAC, 03220=SEDEX)
                'codigo_servico_pac' => trim($post['correios_codigo_servico_pac'] ?? '03298'),
                'codigo_servico_sedex' => trim($post['correios_codigo_servico_sedex'] ?? '03220'),
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

    /**
     * Endpoint de teste da conexão Correios CWS
     * 
     * Testa:
     * - Geração de token
     * - Consulta Preço v3
     * - Consulta Prazo v3
     */
    public function testCorreios(): void
    {
        // Limpar qualquer output anterior (erros PHP, warnings, etc)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Iniciar output buffering para capturar erros
        ob_start();
        
        // Desabilitar exibição de erros para não quebrar JSON
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $tenantId = TenantContext::id();
            $db = Database::getConnection();
            
            $usuario = trim($_POST['usuario'] ?? '');
            $codigoAcessoApisNovo = trim($_POST['codigo_acesso_apis'] ?? '');
            $contrato = trim($_POST['contrato'] ?? '');
            $cepOrigem = preg_replace('/\D/', '', $_POST['cep_origem'] ?? '');
            $cepDestino = preg_replace('/\D/', '', $_POST['cep_destino'] ?? '');
            $peso = (float)($_POST['peso'] ?? 0.3);
            $comprimento = (int)($_POST['comprimento'] ?? 20);
            $largura = (int)($_POST['largura'] ?? 20);
            $altura = (int)($_POST['altura'] ?? 10);
            $servicoPac = ($_POST['servico_pac'] ?? '0') === '1';
            $servicoSedex = ($_POST['servico_sedex'] ?? '0') === '1';

            // Se código vazio, buscar do banco (pode estar salvo e mascarado)
            $codigoAcessoApis = '';
            if (empty($codigoAcessoApisNovo) || $codigoAcessoApisNovo === '********' || strlen($codigoAcessoApisNovo) < 3) {
                // Buscar código do banco
                $stmt = $db->prepare("
                    SELECT config_json 
                    FROM tenant_gateways 
                    WHERE tenant_id = :tenant_id 
                    AND tipo = 'shipping'
                    AND codigo = 'correios'
                    LIMIT 1
                ");
                $stmt->execute(['tenant_id' => $tenantId]);
                $gateway = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($gateway && !empty($gateway['config_json'])) {
                    $decoded = json_decode($gateway['config_json'], true);
                    if (is_array($decoded)) {
                        $correiosAtual = $decoded['correios'] ?? $decoded;
                        
                        // Buscar codigo_acesso_apis (novo) ou chave_acesso_cws (antigo, compatibilidade)
                        if (isset($correiosAtual['credenciais']['codigo_acesso_apis']) && !empty($correiosAtual['credenciais']['codigo_acesso_apis'])) {
                            $codigoAcessoApis = trim($correiosAtual['credenciais']['codigo_acesso_apis']);
                        } elseif (isset($correiosAtual['credenciais']['chave_acesso_cws']) && !empty($correiosAtual['credenciais']['chave_acesso_cws'])) {
                            $codigoAcessoApis = trim($correiosAtual['credenciais']['chave_acesso_cws']);
                        }
                        
                        // Validar que não é a máscara
                        if ($codigoAcessoApis === '********' || strlen($codigoAcessoApis) < 10) {
                            $codigoAcessoApis = '';
                        }
                        
                        // Se não tiver contrato no POST, buscar do banco
                        if (empty($contrato) && isset($correiosAtual['credenciais']['contrato'])) {
                            $contrato = trim($correiosAtual['credenciais']['contrato']);
                        }
                    }
                }
            } else {
                // Usar código novo fornecida
                $codigoAcessoApis = trim($codigoAcessoApisNovo);
            }

            // Limpar espaços em branco
            $usuario = trim($usuario);
            $codigoAcessoApis = trim($codigoAcessoApis);
            $contrato = trim($contrato);
            
            // Validações
            if (empty($usuario)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuário é obrigatório.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (empty($codigoAcessoApis)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Código de acesso às APIs é obrigatório. Preencha o campo ou salve a configuração primeiro.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            if (empty($contrato)) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Nº do contrato é obrigatório. Preencha o campo ou salve a configuração primeiro.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }
            
            // Validar comprimento mínimo (código geralmente tem pelo menos 10 caracteres)
            if (strlen($codigoAcessoApis) < 10) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Código de acesso às APIs parece estar incompleto. Verifique se copiou o código completo do portal CWS.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if (empty($cepOrigem) || strlen($cepOrigem) !== 8) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'CEP de origem inválido (deve ter 8 dígitos).'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if (empty($cepDestino) || strlen($cepDestino) !== 8) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'CEP de destino inválido (deve ter 8 dígitos).'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if (!$servicoPac && !$servicoSedex) {
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Habilite pelo menos um serviço (PAC ou SEDEX) antes de testar.'
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // 1. Testar geração de token (usando mesma lógica do TokenService)
            $tokenOk = false;
            $tokenExpiraEm = null;
            $endpointUsado = !empty($contrato) ? 'autentica/contrato' : 'autentica';
            $statusHttpToken = null;
            $token = null;
            
            try {
                $token = CorreiosTokenService::getToken($usuario, $codigoAcessoApis, $tenantId, $contrato);
                
                // Verificar se token foi gerado com sucesso
                if (empty($token)) {
                    throw new \Exception('Token vazio retornado pelo serviço');
                }
                
                // Token gerado com sucesso
                $tokenOk = true;
                $statusHttpToken = 200; // Ou 201, ambos são sucesso
                $tokenExpiraEm = time() + 3600; // Padrão 1 hora
                // Tentar obter endpoint usado (será 'autentica' ou 'autentica/contrato')
                $endpointUsado = !empty($contrato) ? 'autentica/contrato' : 'autentica';
                
            } catch (\Exception $e) {
                $tokenOk = false;
                // Tentar extrair status HTTP da mensagem de erro
                if (preg_match('/HTTP (\d+)/', $e->getMessage(), $matches)) {
                    $statusHttpToken = (int)$matches[1];
                }
                
                // Se falhou no token, não continuar com testes de preço/prazo
                ob_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao gerar token: ' . $e->getMessage(),
                    'token_ok' => false,
                    'endpoint_usado' => $endpointUsado,
                    'status_http_token' => $statusHttpToken,
                    'opcoes' => [],
                    'erros' => ['Token: ' . $e->getMessage()],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            // 2. Testar serviços habilitados
            // API Preço v1 usa códigos diferentes: 03220 (SEDEX) e 03298 (PAC)
            $opcoes = [];
            $opcoesDetalhadas = []; // Informações detalhadas para diagnóstico
            $erros = [];
            
            // Buscar códigos de serviço configurados ou usar padrões da API Preço v1
            $codigoPac = '03298'; // Padrão API Preço v1 para PAC
            $codigoSedex = '03220'; // Padrão API Preço v1 para SEDEX
            
            // Tentar buscar do banco se existir configuração
            try {
                $stmt = $db->prepare("
                    SELECT config_json 
                    FROM tenant_gateways 
                    WHERE tenant_id = :tenant_id 
                    AND tipo = 'shipping'
                    AND codigo = 'correios'
                    LIMIT 1
                ");
                $stmt->execute(['tenant_id' => $tenantId]);
                $gateway = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if ($gateway && !empty($gateway['config_json'])) {
                    $decoded = json_decode($gateway['config_json'], true);
                    if (is_array($decoded)) {
                        $correiosAtual = $decoded['correios'] ?? $decoded;
                        if (isset($correiosAtual['credenciais']['codigo_servico_pac']) && !empty($correiosAtual['credenciais']['codigo_servico_pac'])) {
                            $codigoPac = $correiosAtual['credenciais']['codigo_servico_pac'];
                        }
                        if (isset($correiosAtual['credenciais']['codigo_servico_sedex']) && !empty($correiosAtual['credenciais']['codigo_servico_sedex'])) {
                            $codigoSedex = $correiosAtual['credenciais']['codigo_servico_sedex'];
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignorar erro ao buscar configuração, usar padrões
            }

            if ($servicoPac) {
                $resultadoPac = [
                    'servico' => 'PAC',
                    'codigo_servico' => $codigoPac,
                    'preco' => null,
                    'prazo' => null,
                    'preco_info' => null,
                    'prazo_info' => null,
                ];
                
                try {
                    $resultadoPreco = $this->testarPrecoV3($token, $cepOrigem, $cepDestino, $codigoPac, $peso, $comprimento, $largura, $altura);
                    $resultadoPac['preco_info'] = [
                        'url' => $resultadoPreco['url'],
                        'status' => $resultadoPreco['status'],
                        'erro' => $resultadoPreco['erro'],
                        'params' => $resultadoPreco['params'],
                    ];
                    
                    if ($resultadoPreco['erro'] === null && $resultadoPreco['preco'] !== null) {
                        $resultadoPac['preco'] = $resultadoPreco['preco'];
                    } else {
                        $erros[] = 'PAC (Preço): ' . ($resultadoPreco['erro'] ?? 'Preço não retornado');
                    }
                } catch (\Exception $e) {
                    $erros[] = 'PAC (Preço): ' . $e->getMessage();
                }
                
                try {
                    $resultadoPrazo = $this->testarPrazoV3($token, $cepOrigem, $cepDestino, $codigoPac);
                    $resultadoPac['prazo_info'] = [
                        'url' => $resultadoPrazo['url'],
                        'status' => $resultadoPrazo['status'],
                        'erro' => $resultadoPrazo['erro'],
                        'params' => $resultadoPrazo['params'],
                        'attempted' => $resultadoPrazo['attempted'],
                        'used' => $resultadoPrazo['used'],
                    ];
                    
                    if ($resultadoPrazo['erro'] === null && $resultadoPrazo['prazo'] !== null) {
                        $resultadoPac['prazo'] = $resultadoPrazo['prazo'];
                    } else {
                        $erros[] = 'PAC (Prazo): ' . ($resultadoPrazo['erro'] ?? 'Prazo não retornado');
                    }
                } catch (\Exception $e) {
                    $erros[] = 'PAC (Prazo): ' . $e->getMessage();
                }
                
                // Adicionar à lista de opções apenas se preço E prazo foram obtidos com sucesso
                if ($resultadoPac['preco'] !== null && $resultadoPac['prazo'] !== null) {
                    $opcoes[] = [
                        'servico' => 'PAC',
                        'preco' => $resultadoPac['preco'],
                        'prazo' => $resultadoPac['prazo'],
                        'codigo_servico' => $codigoPac,
                    ];
                }
                
                // Sempre incluir informações detalhadas no retorno
                $opcoesDetalhadas[] = $resultadoPac;
            }

            if ($servicoSedex) {
                $resultadoSedex = [
                    'servico' => 'SEDEX',
                    'codigo_servico' => $codigoSedex,
                    'preco' => null,
                    'prazo' => null,
                    'preco_info' => null,
                    'prazo_info' => null,
                ];
                
                try {
                    $resultadoPreco = $this->testarPrecoV3($token, $cepOrigem, $cepDestino, $codigoSedex, $peso, $comprimento, $largura, $altura);
                    $resultadoSedex['preco_info'] = [
                        'url' => $resultadoPreco['url'],
                        'status' => $resultadoPreco['status'],
                        'erro' => $resultadoPreco['erro'],
                        'params' => $resultadoPreco['params'],
                    ];
                    
                    if ($resultadoPreco['erro'] === null && $resultadoPreco['preco'] !== null) {
                        $resultadoSedex['preco'] = $resultadoPreco['preco'];
                    } else {
                        $erros[] = 'SEDEX (Preço): ' . ($resultadoPreco['erro'] ?? 'Preço não retornado');
                    }
                } catch (\Exception $e) {
                    $erros[] = 'SEDEX (Preço): ' . $e->getMessage();
                }
                
                try {
                    $resultadoPrazo = $this->testarPrazoV3($token, $cepOrigem, $cepDestino, $codigoSedex);
                    $resultadoSedex['prazo_info'] = [
                        'url' => $resultadoPrazo['url'],
                        'status' => $resultadoPrazo['status'],
                        'erro' => $resultadoPrazo['erro'],
                        'params' => $resultadoPrazo['params'],
                        'attempted' => $resultadoPrazo['attempted'],
                        'used' => $resultadoPrazo['used'],
                    ];
                    
                    if ($resultadoPrazo['erro'] === null && $resultadoPrazo['prazo'] !== null) {
                        $resultadoSedex['prazo'] = $resultadoPrazo['prazo'];
                    } else {
                        $erros[] = 'SEDEX (Prazo): ' . ($resultadoPrazo['erro'] ?? 'Prazo não retornado');
                    }
                } catch (\Exception $e) {
                    $erros[] = 'SEDEX (Prazo): ' . $e->getMessage();
                }
                
                // Adicionar à lista de opções apenas se preço E prazo foram obtidos com sucesso
                if ($resultadoSedex['preco'] !== null && $resultadoSedex['prazo'] !== null) {
                    $opcoes[] = [
                        'servico' => 'SEDEX',
                        'preco' => $resultadoSedex['preco'],
                        'prazo' => $resultadoSedex['prazo'],
                        'codigo_servico' => $codigoSedex,
                    ];
                }
                
                // Sempre incluir informações detalhadas no retorno
                $opcoesDetalhadas[] = $resultadoSedex;
            }

            // Limpar qualquer output anterior (erros PHP, warnings, etc)
            ob_clean();
            
            // Garantir que token_ok seja true se token foi gerado com sucesso
            // (mesmo que preço falhe por serviço não liberado)
            // Se chegou aqui sem exceção, o token foi gerado
            $tokenOkFinal = !empty($token);
            
            // Determinar mensagem baseado no resultado
            $message = 'Teste realizado com sucesso!';
            if (!$tokenOkFinal) {
                $message = 'Erro ao gerar token';
            } elseif (count($opcoes) === 0 && count($erros) > 0) {
                $message = 'Token gerado com sucesso, mas nenhuma opção de frete disponível. Verifique se os serviços estão habilitados no contrato.';
            } elseif (count($opcoes) === 0) {
                $message = 'Token gerado com sucesso, mas nenhuma opção de frete retornada.';
            }
            
            // Retornar resultado com informações detalhadas
            $retorno = [
                'success' => $tokenOkFinal && count($opcoes) > 0,
                'message' => $message,
                'token' => [
                    'endpoint_usado' => $endpointUsado,
                    'status_http_token' => $statusHttpToken ?? ($tokenOkFinal ? 200 : null),
                ],
                'token_ok' => $tokenOkFinal,
                'token_expira_em' => $tokenExpiraEm ?? (time() + 3600),
                'opcoes' => $opcoes,
                'opcoes_detalhadas' => $opcoesDetalhadas ?? [],
                'erros' => $erros,
            ];
            
            echo json_encode($retorno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit;
        } catch (\Exception $e) {
            // Logar erro sem expor credenciais
            $mensagemErro = $e->getMessage();
            // Remover credenciais da mensagem antes de logar
            $mensagemErroLog = preg_replace('/(usuario|senha|chave|token|credencial)[=:]\s*[^\s,;]+/i', '$1=***', $mensagemErro);
            error_log("Erro no teste Correios CWS: " . $mensagemErroLog);
            
            // Limpar qualquer output anterior
            ob_clean();
            
            // Mensagem amigável para o usuário (sem credenciais)
            $mensagemUsuario = $mensagemErro;
            if (strpos($mensagemErro, '401') !== false || strpos($mensagemErro, 'Credenciais inválidas') !== false) {
                $mensagemUsuario = 'Credenciais inválidas. Verifique se o Usuário e a Chave de Acesso CWS estão corretos e foram salvos corretamente.';
            } elseif (strpos($mensagemErro, '403') !== false) {
                $mensagemUsuario = 'Acesso negado. Verifique se a Chave de Acesso CWS tem permissão para gerar tokens no portal CWS.';
            } elseif (strpos($mensagemErro, 'obrigatórios') !== false) {
                $mensagemUsuario = $mensagemErro; // Manter mensagem de validação original
            }
            
            // Tentar extrair status HTTP e endpoint da mensagem de erro
            $statusHttpToken = null;
            $endpointUsado = 'autentica';
            if (preg_match('/HTTP (\d+)/', $mensagemErro, $matches)) {
                $statusHttpToken = (int)$matches[1];
            }
            
            echo json_encode([
                'success' => false,
                'message' => $mensagemUsuario,
                'token_ok' => false,
                'endpoint_usado' => $endpointUsado,
                'status_http_token' => $statusHttpToken,
                'opcoes' => [],
                'erros' => [$mensagemUsuario],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } finally {
            // Restaurar configurações de erro
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }

    /**
     * Testa consulta Preço v1 (API Preço v3 não existe, usar v1)
     * 
     * Retorna array com informações detalhadas para diagnóstico
     * 
     * @return array ['preco' => float|null, 'url' => string, 'status' => int, 'erro' => string|null, 'params' => array]
     */
    private function testarPrecoV3(string $token, string $cepOrigem, string $cepDestino, string $codigoServico, float $peso = 0.3, int $comprimento = 20, int $largura = 20, int $altura = 10): array
    {
        // API Preço v1 usa GET com query parameters
        // psObjeto = peso em gramas (string numérica)
        // Converter kg para gramas (multiplicar por 1000 e arredondar)
        $psObjeto = (string)round(max(0.1, $peso) * 1000);
        $tpObjeto = '2'; // Tipo do objeto (manual usa "2")
        
        // Valores mínimos conforme manual
        $comprimento = max(16, $comprimento);
        $largura = max(11, $largura);
        $altura = max(2, $altura);
        
        // Construir URL completa (sem token)
        $baseUrl = 'https://api.correios.com.br/preco/v1/nacional/' . $codigoServico;
        $url = $baseUrl . 
               '?cepOrigem=' . urlencode($cepOrigem) .
               '&cepDestino=' . urlencode($cepDestino) .
               '&psObjeto=' . urlencode($psObjeto) .
               '&tpObjeto=' . urlencode($tpObjeto) .
               '&comprimento=' . urlencode((string)$comprimento) .
               '&largura=' . urlencode((string)$largura) .
               '&altura=' . urlencode((string)$altura);

        // Log server-side
        error_log("Teste Preço v1 - base_url: https://api.correios.com.br, versao_preco: v1, coProduto: {$codigoServico}, cepOrigem: {$cepOrigem}, cepDestino: {$cepDestino}, psObjeto: {$psObjeto}g");

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

        $result = [
            'preco' => null,
            'url' => $url,
            'status' => $httpCode,
            'erro' => null,
            'params' => [
                'coProduto' => $codigoServico,
                'cepOrigem' => $cepOrigem,
                'cepDestino' => $cepDestino,
                'psObjeto' => $psObjeto . 'g',
                'tpObjeto' => $tpObjeto,
                'comprimento' => $comprimento,
                'largura' => $largura,
                'altura' => $altura,
            ],
        ];

        if ($response === false || !empty($curlError)) {
            $result['erro'] = "Erro ao consultar Preço v1: {$curlError}";
            return $result;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['msgs'][0] ?? "Erro ao consultar Preço v1 (HTTP {$httpCode})";
            $result['erro'] = "Erro ao consultar Preço v1 (HTTP {$httpCode}): {$errorMsg}";
            return $result;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['erro'] = 'Resposta inválida da API de preço';
            return $result;
        }

        // API Preço v1 retorna preço no campo 'pcFinal' (pode vir como string com vírgula)
        $preco = $data['pcFinal'] ?? $data['preco'] ?? $data['valor'] ?? $data['vlrFrete'] ?? $data['valorFrete'] ?? null;
        
        if ($preco !== null) {
            // Converter string com vírgula para float (ex: "17,52" -> 17.52)
            if (is_string($preco)) {
                $preco = str_replace(',', '.', $preco);
            }
            $result['preco'] = (float)$preco;
        }
        
        return $result;
    }

    /**
     * Testa consulta Prazo v3 (ou v1 se v3 não existir)
     * 
     * Retorna array com informações detalhadas para diagnóstico
     * 
     * @param string $token Token de autenticação
     * @param string $cepOrigem CEP de origem
     * @param string $cepDestino CEP de destino
     * @param string $codigoServico Código do serviço (03298=PAC, 03220=SEDEX na API Preço v1)
     * @return array ['prazo' => int|null, 'url' => string, 'status' => int, 'erro' => string|null, 'params' => array, 'attempted' => array, 'used' => string]
     */
    private function testarPrazoV3(string $token, string $cepOrigem, string $cepDestino, string $codigoServico): array
    {
        $baseUrl = 'https://api.correios.com.br';
        $attempted = [];
        $used = null;
        
        // Tentar API Prazo v3 primeiro
        $urlV3 = $baseUrl . '/prazo/v3/nacional/' . $codigoServico;
        $attempted[] = 'v3';
        
        // Log server-side
        error_log("Teste Prazo v3 - base_url: {$baseUrl}, versao_prazo: v3, coProduto: {$codigoServico}, cepOrigem: {$cepOrigem}, cepDestino: {$cepDestino}");

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

        $result = [
            'prazo' => null,
            'url' => $urlV3,
            'status' => $httpCode,
            'erro' => null,
            'params' => [
                'coProduto' => $codigoServico,
                'cepOrigem' => $cepOrigem,
                'cepDestino' => $cepDestino,
            ],
            'attempted' => $attempted,
            'used' => null,
        ];

        // Se v3 retornar 404, fazer fallback para v1 (GET com query string)
        if ($httpCode === 404) {
            $urlV1 = $baseUrl . '/prazo/v1/nacional/' . $codigoServico . 
                     '?cepOrigem=' . urlencode($cepOrigem) .
                     '&cepDestino=' . urlencode($cepDestino);
            $attempted[] = 'v1';
            $used = 'v1';
            
            // Log server-side
            error_log("Teste Prazo v3 retornou 404, tentando fallback para v1 - base_url: {$baseUrl}, versao_prazo: v1, coProduto: {$codigoServico}");

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
            
            $result['url'] = $urlV1;
            $result['status'] = $httpCode;
            $result['attempted'] = $attempted;
            $result['used'] = $used;
        } else {
            $used = 'v3';
            $result['used'] = $used;
        }

        if ($response === false || !empty($curlError)) {
            $result['erro'] = "Erro ao consultar Prazo: {$curlError}";
            return $result;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['msgs'][0] ?? "Erro ao consultar Prazo (HTTP {$httpCode})";
            $result['erro'] = "Erro ao consultar Prazo (HTTP {$httpCode}): {$errorMsg}";
            return $result;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $result['erro'] = 'Resposta inválida da API de prazo';
            return $result;
        }

        $prazo = $data['prazoEntrega'] ?? $data['prazo'] ?? $data['diasUteis'] ?? $data['prazoEntregaDias'] ?? null;

        if ($prazo === null || !is_numeric($prazo)) {
            $result['erro'] = 'Prazo não retornado pela API';
            return $result;
        }

        $result['prazo'] = (int)$prazo;
        return $result;
    }
}




