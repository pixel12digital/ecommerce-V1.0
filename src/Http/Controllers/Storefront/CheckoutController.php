<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\CartService;
use App\Services\Shipping\ShippingService;
use App\Services\Payment\PaymentService;
use App\Services\OrderService;
use App\Services\ThemeConfig;

class CheckoutController extends Controller
{
    /**
     * Converte itens do carrinho (chaves v:123 ou p:456) para formato
     * esperado pelo ShippingService (indexado por produto_id inteiro).
     */
    private function converterItensParaFrete(array $cartItems): array
    {
        $itensParaFrete = [];
        foreach ($cartItems as $itemKey => $item) {
            $produtoId = (int)($item['produto_id'] ?? 0);
            if ($produtoId <= 0) {
                if (preg_match('/^p:(\d+)$/', $itemKey, $m)) {
                    $produtoId = (int)$m[1];
                } elseif (preg_match('/^v:(\d+)$/', $itemKey, $m)) {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT produto_id FROM produto_variacoes WHERE id = ? LIMIT 1");
                    $stmt->execute([$m[1]]);
                    $row = $stmt->fetch();
                    $produtoId = $row ? (int)$row['produto_id'] : 0;
                }
            }
            if ($produtoId <= 0) continue;
            if (isset($itensParaFrete[$produtoId])) {
                $itensParaFrete[$produtoId]['quantidade'] += ($item['quantidade'] ?? 1);
            } else {
                $itensParaFrete[$produtoId] = [
                    'produto_id' => $produtoId,
                    'quantidade' => (int)($item['quantidade'] ?? 1),
                    'preco_unitario' => (float)($item['preco_unitario'] ?? 0),
                ];
            }
        }
        return $itensParaFrete;
    }

    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se carrinho está vazio
        if (CartService::isEmpty()) {
            $this->redirect('/carrinho?error=carrinho_vazio');
            return;
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();
        $cart = CartService::get();
        $subtotal = CartService::getSubtotal();

        // Buscar opções de frete (usar CEP do GET ou da sessão)
        $cep = $_GET['cep'] ?? ($_SESSION['checkout_cep'] ?? '');
        $cep = preg_replace('/\D/', '', $cep);
        if (!empty($cep)) {
            $_SESSION['checkout_cep'] = $cep;
        }
        $freteErro = null;
        $freteErroTecnico = null;
        $itensParaFrete = $this->converterItensParaFrete($cart['items']);

        // Verificar se TODOS os itens do carrinho têm frete grátis
        $todosFreteGratis = $this->verificarFreteGratisCarrinho($db, $tenantId, $cart['items']);
        
        try {
            if ($todosFreteGratis) {
                // Frete grátis: criar opção única de frete grátis
                $opcoesFrete = [[
                    'codigo' => 'frete_gratis',
                    'servico' => 'Frete Grátis',
                    'titulo' => 'Frete Grátis',
                    'valor' => 0,
                    'preco' => 0,
                    'prazo' => null,
                ]];
            } else {
                $opcoesFrete = !empty($cep) ? ShippingService::calcularFrete($tenantId, $cep, $subtotal, $itensParaFrete) : [];
            }
            
            // Se não houver opções e CEP foi informado, preparar mensagem amigável
            if (empty($opcoesFrete) && !empty($cep)) {
                $freteErro = "Não foi possível calcular o frete no momento. Verifique o CEP e tente novamente.";
                $freteErroTecnico = "Nenhuma opção de frete retornada para CEP: " . preg_replace('/\D/', '', $cep);
                error_log("Checkout: {$freteErroTecnico}");
            }
        } catch (\Exception $e) {
            // Em caso de erro, logar motivo técnico mas não quebrar checkout
            $freteErro = "Não foi possível calcular o frete no momento. Verifique o CEP e tente novamente.";
            $freteErroTecnico = "Erro ao calcular frete: " . $e->getMessage();
            error_log("Checkout: {$freteErroTecnico}");
            $opcoesFrete = [];
        }

        // Buscar métodos de pagamento
        $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);

        // Verificar se cliente está logado e buscar dados
        $customer = null;
        $customerAddresses = [];
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $customerId = (int)$_SESSION['customer_id'];
            
            // Buscar dados do cliente
            $stmt = $db->prepare("
                SELECT * FROM customers 
                WHERE id = :customer_id 
                AND tenant_id = :tenant_id 
                LIMIT 1
            ");
            $stmt->execute([
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
            ]);
            $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

            // Buscar endereços do cliente
            if ($customer) {
                $stmt = $db->prepare("
                    SELECT * FROM customer_addresses 
                    WHERE customer_id = :customer_id 
                    AND tenant_id = :tenant_id 
                    ORDER BY is_default DESC, created_at ASC
                ");
                $stmt->execute([
                    'customer_id' => $customerId,
                    'tenant_id' => $tenantId,
                ]);
                $customerAddresses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        }

        // Carregar configurações do tema
        $theme = ThemeConfig::getFullThemeConfig();
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();
        $tenant = TenantContext::tenant();

        $this->view('storefront/checkout/index', [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'cart' => $cart,
            'subtotal' => $subtotal,
            'opcoesFrete' => $opcoesFrete,
            'freteErro' => $freteErro,
            'cep' => $cep,
            'metodosPagamento' => $metodosPagamento,
            'customer' => $customer,
            'customerAddresses' => $customerAddresses,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
            'todosFreteGratis' => $todosFreteGratis,
        ]);
    }

    /**
     * Verifica se TODOS os itens do carrinho possuem frete grátis
     */
    private function verificarFreteGratisCarrinho(\PDO $db, int $tenantId, array $cartItems): bool
    {
        if (empty($cartItems)) return false;

        $produtoIds = [];
        foreach ($cartItems as $itemKey => $item) {
            $produtoId = (int)($item['produto_id'] ?? 0);
            if ($produtoId <= 0) {
                if (preg_match('/^p:(\d+)$/', $itemKey, $m)) {
                    $produtoId = (int)$m[1];
                } elseif (preg_match('/^v:(\d+)$/', $itemKey, $m)) {
                    $stmt = $db->prepare("SELECT produto_id FROM produto_variacoes WHERE id = ? LIMIT 1");
                    $stmt->execute([$m[1]]);
                    $row = $stmt->fetch();
                    $produtoId = $row ? (int)$row['produto_id'] : 0;
                }
            }
            if ($produtoId > 0) {
                $produtoIds[$produtoId] = true;
            }
        }

        if (empty($produtoIds)) return false;

        $ids = array_keys($produtoIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("
            SELECT COUNT(*) as total, SUM(CASE WHEN frete_gratis = 1 THEN 1 ELSE 0 END) as com_frete_gratis
            FROM produtos 
            WHERE id IN ({$placeholders}) AND tenant_id = ?
        ");
        $stmt->execute(array_merge($ids, [$tenantId]));
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result && (int)$result['total'] > 0 && (int)$result['com_frete_gratis'] === (int)$result['total'];
    }

    public function process(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Validar carrinho não vazio
        if (CartService::isEmpty()) {
            $this->redirect('/carrinho?error=carrinho_vazio');
            return;
        }

        // Validar campos obrigatórios
        $clienteNome = trim($_POST['cliente_nome'] ?? '');
        $clienteEmail = trim($_POST['cliente_email'] ?? '');
        $clienteCpf = preg_replace('/\D/', '', trim($_POST['cliente_cpf'] ?? ''));
        $clienteTelefone = trim($_POST['cliente_telefone'] ?? '');
        $entregaCep = trim($_POST['entrega_cep'] ?? '');
        $entregaLogradouro = trim($_POST['entrega_logradouro'] ?? '');
        $entregaNumero = trim($_POST['entrega_numero'] ?? '');
        $entregaBairro = trim($_POST['entrega_bairro'] ?? '');
        $entregaCidade = trim($_POST['entrega_cidade'] ?? '');
        $entregaEstado = trim($_POST['entrega_estado'] ?? '');
        $metodoFrete = trim($_POST['metodo_frete'] ?? '');
        $metodoPagamento = trim($_POST['metodo_pagamento'] ?? '');

        $errors = [];

        if (empty($clienteNome)) $errors[] = 'Nome é obrigatório';
        if (empty($clienteEmail) || !filter_var($clienteEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail válido é obrigatório';
        }
        if (empty($clienteCpf) || strlen($clienteCpf) !== 11) {
            $errors[] = 'CPF válido é obrigatório';
        }
        if (empty($entregaCep)) $errors[] = 'CEP é obrigatório';
        if (empty($entregaLogradouro)) $errors[] = 'Logradouro é obrigatório';
        if (empty($entregaNumero)) $errors[] = 'Número é obrigatório';
        if (empty($entregaBairro)) $errors[] = 'Bairro é obrigatório';
        if (empty($entregaCidade)) $errors[] = 'Cidade é obrigatória';
        if (empty($entregaEstado)) $errors[] = 'Estado é obrigatório';
        if (empty($metodoFrete)) $errors[] = 'Método de frete é obrigatório';
        if (empty($metodoPagamento)) $errors[] = 'Método de pagamento é obrigatório';

        if (!empty($errors)) {
            // Redirecionar de volta com erros
            $cart = CartService::get();
            $subtotal = CartService::getSubtotal();
            $opcoesFrete = [];
            $freteErro = '';
            $todosFreteGratis = $this->verificarFreteGratisCarrinho($db, $tenantId, $cart['items']);
            try {
                if ($todosFreteGratis) {
                    $opcoesFrete = [['codigo' => 'frete_gratis', 'servico' => 'Frete Grátis', 'titulo' => 'Frete Grátis', 'valor' => 0, 'preco' => 0, 'prazo' => null]];
                } else {
                    $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $this->converterItensParaFrete($cart['items']));
                }
            } catch (\Exception $ex) {
                $freteErro = 'Não foi possível calcular o frete.';
            }
            $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();

            // Buscar dados do cliente logado para manter estado
            $customer = null;
            $customerAddresses = [];
            if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
                $stmt = $db->prepare("SELECT * FROM customers WHERE id = :cid AND tenant_id = :tid LIMIT 1");
                $stmt->execute(['cid' => (int)$_SESSION['customer_id'], 'tid' => $tenantId]);
                $customer = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($customer) {
                    $stmt = $db->prepare("SELECT * FROM customer_addresses WHERE customer_id = :cid AND tenant_id = :tid ORDER BY is_default DESC");
                    $stmt->execute(['cid' => (int)$_SESSION['customer_id'], 'tid' => $tenantId]);
                    $customerAddresses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                }
            }

            $this->view('storefront/checkout/index', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'freteErro' => $freteErro,
                'cep' => $entregaCep,
                'metodosPagamento' => $metodosPagamento,
                'customer' => $customer,
                'customerAddresses' => $customerAddresses,
                'cartTotalItems' => CartService::getTotalItems(),
                'cartSubtotal' => CartService::getSubtotal(),
                'errors' => $errors,
                'formData' => $_POST,
                'todosFreteGratis' => $todosFreteGratis,
            ]);
            return;
        }

        // Recalcular valores
        $cart = CartService::get();
        $subtotal = CartService::getSubtotal();
        $todosFreteGratis = $this->verificarFreteGratisCarrinho($db, $tenantId, $cart['items']);
        if ($todosFreteGratis && $metodoFrete === 'frete_gratis') {
            $valorFrete = 0.0;
        } else {
            $valorFrete = ShippingService::getValorFrete($metodoFrete, $tenantId, $entregaCep, $subtotal, $this->converterItensParaFrete($cart['items']));
        }
        $totalDescontos = 0.0; // Por enquanto sem descontos
        $totalGeral = $subtotal + $valorFrete - $totalDescontos;

        // Gerar número do pedido
        $numeroPedido = OrderService::gerarNumeroPedido($tenantId);

        // Verificar se cliente está logado
        $customerId = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']) 
            ? (int)$_SESSION['customer_id'] 
            : null;

        // Conta será criada automaticamente se não estiver logado (sem necessidade de senha)

        // Se houver erros, retornar para o formulário
        if (!empty($errors)) {
            $cart = CartService::get();
            $subtotal = CartService::getSubtotal();
            $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $this->converterItensParaFrete($cart['items']));
            $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);

            // Buscar dados do cliente se estiver logado
            $customer = null;
            $customerAddresses = [];
            if ($customerId) {
                $stmt = $db->prepare("
                    SELECT * FROM customers 
                    WHERE id = :customer_id 
                    AND tenant_id = :tenant_id 
                    LIMIT 1
                ");
                $stmt->execute([
                    'customer_id' => $customerId,
                    'tenant_id' => $tenantId,
                ]);
                $customer = $stmt->fetch(\PDO::FETCH_ASSOC);
            }

            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();

            $this->view('storefront/checkout/index', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'freteErro' => '',
                'cep' => $entregaCep,
                'metodosPagamento' => $metodosPagamento,
                'customer' => $customer,
                'customerAddresses' => $customerAddresses,
                'cartTotalItems' => CartService::getTotalItems(),
                'cartSubtotal' => CartService::getSubtotal(),
                'errors' => $errors,
                'formData' => $_POST,
            ]);
            return;
        }

        // Iniciar transação
        try {
            $db->beginTransaction();

            // Criar conta automaticamente se cliente não está logado
            if (!$customerId) {
                // Limpar CPF para comparação (só dígitos)
                $cpfLimpo = preg_replace('/\D/', '', $clienteCpf);

                // Verificar se já existe cadastro por email
                $stmt = $db->prepare("
                    SELECT * FROM customers 
                    WHERE tenant_id = :tenant_id 
                    AND email = :email 
                    LIMIT 1
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'email' => $clienteEmail,
                ]);
                $existingCustomer = $stmt->fetch(\PDO::FETCH_ASSOC);

                // Se não encontrou por email, tentar por CPF
                if (!$existingCustomer && !empty($cpfLimpo)) {
                    $stmt = $db->prepare("
                        SELECT * FROM customers 
                        WHERE tenant_id = :tenant_id 
                        AND REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = :cpf 
                        LIMIT 1
                    ");
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'cpf' => $cpfLimpo,
                    ]);
                    $existingCustomer = $stmt->fetch(\PDO::FETCH_ASSOC);
                }

                if ($existingCustomer) {
                    // Cadastro já existe — usar o existente
                    $customerId = (int)$existingCustomer['id'];
                    $_SESSION['customer_id'] = $customerId;
                    $_SESSION['customer_name'] = $existingCustomer['name'];
                    $_SESSION['customer_email'] = $existingCustomer['email'];

                    // Atualizar dados se necessário (telefone, nome)
                    $stmt = $db->prepare("
                        UPDATE customers SET 
                            name = :name, 
                            phone = :phone,
                            cpf = COALESCE(NULLIF(:cpf, ''), cpf),
                            updated_at = NOW()
                        WHERE id = :id AND tenant_id = :tenant_id
                    ");
                    $stmt->execute([
                        'name' => $clienteNome,
                        'phone' => $clienteTelefone ?: null,
                        'cpf' => $clienteCpf ?: null,
                        'id' => $customerId,
                        'tenant_id' => $tenantId,
                    ]);
                } else {
                    // Criar nova conta automaticamente (sem senha)
                    $stmt = $db->prepare("
                        INSERT INTO customers (
                            tenant_id, name, email, password_hash, phone, cpf, created_at, updated_at
                        ) VALUES (
                            :tenant_id, :name, :email, NULL, :phone, :cpf, NOW(), NOW()
                        )
                    ");
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'name' => $clienteNome,
                        'email' => $clienteEmail,
                        'phone' => $clienteTelefone ?: null,
                        'cpf' => $clienteCpf ?: null,
                    ]);

                    $customerId = (int)$db->lastInsertId();

                    // Login automático do novo cliente
                    $_SESSION['customer_id'] = $customerId;
                    $_SESSION['customer_name'] = $clienteNome;
                    $_SESSION['customer_email'] = $clienteEmail;
                }
            }

            // Garantir que customer_id não seja null
            if (!$customerId) {
                throw new \Exception('Erro ao processar conta do cliente. Tente novamente.');
            }

            // Validar que customer_id existe no banco para este tenant
            $stmtCheck = $db->prepare("SELECT id FROM customers WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
            $stmtCheck->execute(['id' => $customerId, 'tenant_id' => $tenantId]);
            if (!$stmtCheck->fetch()) {
                unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
                throw new \Exception('Sessão expirada. Por favor, faça login novamente e tente finalizar o pedido.');
            }

            // Criar pedido
            $stmt = $db->prepare("
                INSERT INTO pedidos (
                    tenant_id, customer_id, numero_pedido, status,
                    total_produtos, total_frete, total_descontos, total_geral,
                    cliente_nome, cliente_email, cliente_telefone,
                    entrega_cep, entrega_logradouro, entrega_numero, entrega_complemento,
                    entrega_bairro, entrega_cidade, entrega_estado,
                    metodo_pagamento, metodo_frete, observacoes,
                    created_at, updated_at
                ) VALUES (
                    :tenant_id, :customer_id, :numero_pedido, 'pending',
                    :total_produtos, :total_frete, :total_descontos, :total_geral,
                    :cliente_nome, :cliente_email, :cliente_telefone,
                    :entrega_cep, :entrega_logradouro, :entrega_numero, :entrega_complemento,
                    :entrega_bairro, :entrega_cidade, :entrega_estado,
                    :metodo_pagamento, :metodo_frete, :observacoes,
                    NOW(), NOW()
                )
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'numero_pedido' => $numeroPedido,
                'total_produtos' => $subtotal,
                'total_frete' => $valorFrete,
                'total_descontos' => $totalDescontos,
                'total_geral' => $totalGeral,
                'cliente_nome' => $clienteNome,
                'cliente_email' => $clienteEmail,
                'cliente_telefone' => $clienteTelefone ?: null,
                'entrega_cep' => $entregaCep,
                'entrega_logradouro' => $entregaLogradouro,
                'entrega_numero' => $entregaNumero,
                'entrega_complemento' => trim($_POST['entrega_complemento'] ?? '') ?: null,
                'entrega_bairro' => $entregaBairro,
                'entrega_cidade' => $entregaCidade,
                'entrega_estado' => $entregaEstado,
                'metodo_pagamento' => $metodoPagamento,
                'metodo_frete' => $metodoFrete,
                'observacoes' => trim($_POST['observacoes'] ?? '') ?: null,
            ]);

            $pedidoId = $db->lastInsertId();

            // Inserir itens do pedido e decrementar estoque
            $stmtItem = $db->prepare("
                INSERT INTO pedido_itens (
                    tenant_id, pedido_id, produto_id, variacao_id,
                    nome_produto, sku, atributos_json, quantidade, preco_unitario, total_linha,
                    created_at, updated_at
                ) VALUES (
                    :tenant_id, :pedido_id, :produto_id, :variacao_id,
                    :nome_produto, :sku, :atributos_json, :quantidade, :preco_unitario, :total_linha,
                    NOW(), NOW()
                )
            ");

            foreach ($cart['items'] as $item) {
                $variacaoId = isset($item['variacao_id']) && $item['variacao_id'] > 0 
                    ? (int)$item['variacao_id'] 
                    : null;
                
                $produtoId = (int)$item['produto_id'];
                $quantidade = (int)$item['quantidade'];

                // Buscar SKU: priorizar variação, senão produto
                $sku = null;
                if ($variacaoId) {
                    $stmtSku = $db->prepare("
                        SELECT sku FROM produto_variacoes 
                        WHERE id = :variacao_id AND tenant_id = :tenant_id
                    ");
                    $stmtSku->execute(['variacao_id' => $variacaoId, 'tenant_id' => $tenantId]);
                    $sku = $stmtSku->fetchColumn();
                }
                
                if (!$sku) {
                    $stmtSku = $db->prepare("SELECT sku FROM produtos WHERE id = :id AND tenant_id = :tenant_id");
                    $stmtSku->execute(['id' => $produtoId, 'tenant_id' => $tenantId]);
                    $sku = $stmtSku->fetchColumn();
                }

                // Montar atributos_json (snapshot)
                $atributosJson = null;
                if ($variacaoId && !empty($item['atributos'])) {
                    // Buscar atributos completos da variação para snapshot
                    $stmtAtributos = $db->prepare("
                        SELECT a.nome as atributo_nome, a.slug as atributo_slug,
                               at.nome as termo_nome, at.slug as termo_slug, at.valor_cor
                        FROM produto_variacao_atributos pva
                        INNER JOIN atributos a ON a.id = pva.atributo_id
                        INNER JOIN atributo_termos at ON at.id = pva.atributo_termo_id
                        WHERE pva.variacao_id = :variacao_id
                        AND pva.tenant_id = :tenant_id
                        ORDER BY a.ordem ASC, at.ordem ASC
                    ");
                    $stmtAtributos->execute([
                        'variacao_id' => $variacaoId,
                        'tenant_id' => $tenantId
                    ]);
                    $atributos = $stmtAtributos->fetchAll(\PDO::FETCH_ASSOC);
                    if (!empty($atributos)) {
                        $atributosJson = json_encode($atributos, JSON_UNESCAPED_UNICODE);
                    }
                }

                $totalLinha = $item['preco_unitario'] * $quantidade;

                // Inserir item do pedido
                $stmtItem->execute([
                    'tenant_id' => $tenantId,
                    'pedido_id' => $pedidoId,
                    'produto_id' => $produtoId,
                    'variacao_id' => $variacaoId,
                    'nome_produto' => $item['nome'],
                    'sku' => $sku ?: null,
                    'atributos_json' => $atributosJson,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $item['preco_unitario'],
                    'total_linha' => $totalLinha,
                ]);

                // Decrementar estoque (apenas se gerencia_estoque = 1)
                if ($variacaoId) {
                    // Verificar se a variação gerencia estoque
                    $stmtGerencia = $db->prepare("SELECT gerencia_estoque, quantidade_estoque FROM produto_variacoes WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
                    $stmtGerencia->execute(['id' => $variacaoId, 'tenant_id' => $tenantId]);
                    $varInfo = $stmtGerencia->fetch(\PDO::FETCH_ASSOC);

                    if ($varInfo && (int)($varInfo['gerencia_estoque'] ?? 0) === 1) {
                        if ((int)$varInfo['quantidade_estoque'] < $quantidade) {
                            throw new \Exception("Estoque insuficiente para a variação selecionada. Por favor, verifique o carrinho e tente novamente.");
                        }
                        $stmtEstoque = $db->prepare("
                            UPDATE produto_variacoes 
                            SET quantidade_estoque = quantidade_estoque - :qtd1,
                                status_estoque = CASE 
                                    WHEN (quantidade_estoque - :qtd2) <= 0 THEN 'outofstock'
                                    ELSE 'instock'
                                END,
                                updated_at = NOW()
                            WHERE id = :variacao_id
                            AND tenant_id = :tenant_id
                        ");
                        $stmtEstoque->execute([
                            'variacao_id' => $variacaoId,
                            'tenant_id' => $tenantId,
                            'qtd1' => $quantidade,
                            'qtd2' => $quantidade,
                        ]);
                    }
                } else {
                    // Verificar se o produto gerencia estoque
                    $stmtGerencia = $db->prepare("SELECT gerencia_estoque, quantidade_estoque FROM produtos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
                    $stmtGerencia->execute(['id' => $produtoId, 'tenant_id' => $tenantId]);
                    $prodInfo = $stmtGerencia->fetch(\PDO::FETCH_ASSOC);

                    if ($prodInfo && (int)($prodInfo['gerencia_estoque'] ?? 0) === 1) {
                        if ((int)$prodInfo['quantidade_estoque'] < $quantidade) {
                            throw new \Exception("Estoque insuficiente para o produto selecionado. Por favor, verifique o carrinho e tente novamente.");
                        }
                        $stmtEstoque = $db->prepare("
                            UPDATE produtos 
                            SET quantidade_estoque = quantidade_estoque - :qtd1,
                                status_estoque = CASE 
                                    WHEN (quantidade_estoque - :qtd2) <= 0 THEN 'outofstock'
                                    ELSE 'instock'
                                END,
                                updated_at = NOW()
                            WHERE id = :produto_id
                            AND tenant_id = :tenant_id
                        ");
                        $stmtEstoque->execute([
                            'produto_id' => $produtoId,
                            'tenant_id' => $tenantId,
                            'qtd1' => $quantidade,
                            'qtd2' => $quantidade,
                        ]);
                    }
                }
            }

            // Processar pagamento usando o provider configurado
            $cliente = [
                'nome' => $clienteNome,
                'email' => $clienteEmail,
                'cpf' => $clienteCpf,
                'telefone' => $clienteTelefone,
                'cep' => $entregaCep,
                'logradouro' => $entregaLogradouro,
                'numero' => $entregaNumero,
                'bairro' => $entregaBairro,
                'cidade' => $entregaCidade,
                'estado' => $entregaEstado,
            ];
            
            $pedidoData = [
                'id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'total_geral' => $totalGeral,
            ];
            
            $paymentResult = PaymentService::processarPagamento($metodoPagamento, $pedidoData, $cliente);
            
            // Atualizar pedido com código de transação, status e detalhes de pagamento (ex: QR PIX)
            $paymentDetailsJson = !empty($paymentResult->dadosExibicao) ? json_encode($paymentResult->dadosExibicao, JSON_UNESCAPED_UNICODE) : null;
            $stmtUpdate = $db->prepare("
                UPDATE pedidos 
                SET codigo_transacao = :codigo_transacao,
                    status = :status,
                    payment_details = :payment_details,
                    updated_at = NOW()
                WHERE id = :pedido_id 
                AND tenant_id = :tenant_id
            ");
            $stmtUpdate->execute([
                'codigo_transacao' => $paymentResult->codigoTransacao,
                'status' => $paymentResult->statusInicial,
                'payment_details' => $paymentDetailsJson,
                'pedido_id' => $pedidoId,
                'tenant_id' => $tenantId,
            ]);

            // Atualizar CPF do cliente (caso já existisse sem CPF)
            if ($customerId && !empty($clienteCpf)) {
                $stmtCpf = $db->prepare("UPDATE customers SET cpf = :cpf WHERE id = :id AND tenant_id = :tenant_id AND (cpf IS NULL OR cpf = '')");
                $stmtCpf->execute(['cpf' => $clienteCpf, 'id' => $customerId, 'tenant_id' => $tenantId]);
            }

            // Salvar endereço do checkout automaticamente (se não existir igual)
            if ($customerId) {
                $stmtAddr = $db->prepare("
                    SELECT id FROM customer_addresses 
                    WHERE customer_id = :customer_id AND tenant_id = :tenant_id 
                    AND zipcode = :zipcode AND street = :street AND number = :number
                    LIMIT 1
                ");
                $stmtAddr->execute([
                    'customer_id' => $customerId,
                    'tenant_id' => $tenantId,
                    'zipcode' => $entregaCep,
                    'street' => $entregaLogradouro,
                    'number' => $entregaNumero,
                ]);
                if (!$stmtAddr->fetch()) {
                    $stmtNewAddr = $db->prepare("
                        INSERT INTO customer_addresses (
                            tenant_id, customer_id, type, zipcode, street, number, complement,
                            neighborhood, city, state, is_default, created_at, updated_at
                        ) VALUES (
                            :tenant_id, :customer_id, 'shipping', :zipcode, :street, :number, :complement,
                            :neighborhood, :city, :state, 1, NOW(), NOW()
                        )
                    ");
                    $stmtNewAddr->execute([
                        'tenant_id' => $tenantId,
                        'customer_id' => $customerId,
                        'zipcode' => $entregaCep,
                        'street' => $entregaLogradouro,
                        'number' => $entregaNumero,
                        'complement' => trim($_POST['entrega_complemento'] ?? '') ?: null,
                        'neighborhood' => $entregaBairro,
                        'city' => $entregaCidade,
                        'state' => $entregaEstado,
                    ]);
                }
            }

            $db->commit();

            // Limpar carrinho
            CartService::clear();

            // Redirecionar para página de confirmação
            $this->redirect("/pedido/{$numeroPedido}/confirmacao");

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Erro ao processar pedido: " . $e->getMessage());
            
            $cart = CartService::get();
            $subtotal = CartService::getSubtotal();
            $opcoesFrete = [];
            $freteErro = '';
            $todosFreteGratis = $this->verificarFreteGratisCarrinho($db, $tenantId, $cart['items']);
            try {
                if ($todosFreteGratis) {
                    $opcoesFrete = [['codigo' => 'frete_gratis', 'servico' => 'Frete Grátis', 'titulo' => 'Frete Grátis', 'valor' => 0, 'preco' => 0, 'prazo' => null]];
                } else {
                    $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $this->converterItensParaFrete($cart['items']));
                }
            } catch (\Exception $ex) {
                $freteErro = 'Não foi possível calcular o frete.';
            }
            $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();

            // Buscar dados do cliente logado para manter estado
            $customer = null;
            $customerAddresses = [];
            if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
                $stmtC = $db->prepare("SELECT * FROM customers WHERE id = :cid AND tenant_id = :tid LIMIT 1");
                $stmtC->execute(['cid' => (int)$_SESSION['customer_id'], 'tid' => $tenantId]);
                $customer = $stmtC->fetch(\PDO::FETCH_ASSOC);
                if ($customer) {
                    $stmtA = $db->prepare("SELECT * FROM customer_addresses WHERE customer_id = :cid AND tenant_id = :tid ORDER BY is_default DESC");
                    $stmtA->execute(['cid' => (int)$_SESSION['customer_id'], 'tid' => $tenantId]);
                    $customerAddresses = $stmtA->fetchAll(\PDO::FETCH_ASSOC);
                }
            }

            // Usar mensagem diretamente - o CieloPaymentProvider já traduz os erros
            $friendlyMsg = $e->getMessage();

            $this->view('storefront/checkout/index', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'freteErro' => $freteErro,
                'cep' => $entregaCep,
                'metodosPagamento' => $metodosPagamento,
                'customer' => $customer,
                'customerAddresses' => $customerAddresses,
                'cartTotalItems' => CartService::getTotalItems(),
                'cartSubtotal' => CartService::getSubtotal(),
                'errors' => [$friendlyMsg],
                'formData' => $_POST,
                'todosFreteGratis' => $todosFreteGratis,
            ]);
        }
    }

    /**
     * API: Buscar cliente por email para auto-preencher dados no checkout
     */
    public function buscarCliente(): void
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();
        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['found' => false]);
            return;
        }

        $stmt = $db->prepare("
            SELECT name, email, phone, cpf FROM customers 
            WHERE tenant_id = :tenant_id AND email = :email 
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'email' => $email]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$customer) {
            echo json_encode(['found' => false]);
            return;
        }

        // Buscar endereço padrão
        $stmt = $db->prepare("
            SELECT * FROM customer_addresses 
            WHERE customer_id = (SELECT id FROM customers WHERE tenant_id = :tid AND email = :email LIMIT 1)
            AND tenant_id = :tid2
            ORDER BY is_default DESC, id DESC 
            LIMIT 1
        ");
        $stmt->execute(['tid' => $tenantId, 'email' => $email, 'tid2' => $tenantId]);
        $address = $stmt->fetch(\PDO::FETCH_ASSOC);

        $result = [
            'found' => true,
            'name' => $customer['name'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'cpf' => $customer['cpf'] ?? '',
        ];

        if ($address) {
            $result['address'] = [
                'cep' => $address['zipcode'] ?? $address['cep'] ?? '',
                'estado' => $address['state'] ?? $address['estado'] ?? '',
                'cidade' => $address['city'] ?? $address['cidade'] ?? '',
                'bairro' => $address['neighborhood'] ?? $address['bairro'] ?? '',
                'logradouro' => $address['street'] ?? $address['logradouro'] ?? '',
                'numero' => $address['number'] ?? $address['numero'] ?? '',
                'complemento' => $address['complement'] ?? $address['complemento'] ?? '',
            ];
        }

        echo json_encode($result);
    }

    private function friendlyPaymentError(string $errorMsg): string
    {
        $lower = mb_strtolower($errorMsg);

        if (strpos($lower, 'recusado') !== false || strpos($lower, 'denied') !== false || strpos($lower, 'negad') !== false) {
            return 'Pagamento não autorizado pelo seu banco. Verifique os dados do cartão ou tente outro método de pagamento.';
        }
        if (strpos($lower, 'cartão inválido') !== false || strpos($lower, 'card number') !== false) {
            return 'Número do cartão inválido. Verifique e tente novamente.';
        }
        if (strpos($lower, 'cvv') !== false || strpos($lower, 'security code') !== false) {
            return 'Código de segurança (CVV) inválido. Verifique e tente novamente.';
        }
        if (strpos($lower, 'validade') !== false || strpos($lower, 'expir') !== false) {
            return 'Data de validade do cartão inválida. Verifique e tente novamente.';
        }
        if (strpos($lower, 'saldo') !== false || strpos($lower, 'insufficient') !== false) {
            return 'Saldo insuficiente. Tente outro cartão ou método de pagamento.';
        }
        if (strpos($lower, 'timeout') !== false || strpos($lower, 'conectar') !== false) {
            return 'Não foi possível conectar ao serviço de pagamento. Tente novamente em alguns instantes.';
        }
        if (strpos($lower, 'estoque') !== false) {
            return $errorMsg;
        }
        if (strpos($lower, 'sessão') !== false || strpos($lower, 'login') !== false) {
            return $errorMsg;
        }

        return 'Não foi possível processar o pagamento. Verifique os dados e tente novamente.';
    }
}


