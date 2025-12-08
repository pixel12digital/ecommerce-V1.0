<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\CartService;
use App\Services\Shipping\ShippingService;
use App\Services\Payment\PaymentService;
use App\Services\OrderService;

class CheckoutController extends Controller
{
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

        // Buscar opções de frete (usar CEP padrão para cálculo inicial)
        $cep = $_GET['cep'] ?? '';
        $opcoesFrete = ShippingService::calcularFrete($tenantId, $cep, $subtotal, $cart['items']);

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

        $this->view('storefront/checkout/index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
            'opcoesFrete' => $opcoesFrete,
            'metodosPagamento' => $metodosPagamento,
            'customer' => $customer,
            'customerAddresses' => $customerAddresses,
        ]);
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
            $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $cart['items']);
            $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);

            $this->view('storefront/checkout/index', [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'metodosPagamento' => $metodosPagamento,
                'errors' => $errors,
                'formData' => $_POST,
            ]);
            return;
        }

        // Recalcular valores
        $cart = CartService::get();
        $subtotal = CartService::getSubtotal();
        $valorFrete = ShippingService::getValorFrete($metodoFrete, $tenantId, $entregaCep, $subtotal, $cart['items']);
        $totalDescontos = 0.0; // Por enquanto sem descontos
        $totalGeral = $subtotal + $valorFrete - $totalDescontos;

        // Gerar número do pedido
        $numeroPedido = OrderService::gerarNumeroPedido($tenantId);

        // Verificar se cliente está logado
        $customerId = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']) 
            ? (int)$_SESSION['customer_id'] 
            : null;

        // Se cliente não está logado, verificar se deseja criar conta
        if (!$customerId) {
            $criarConta = isset($_POST['criar_conta']) && $_POST['criar_conta'] == '1';
            $senhaConta = trim($_POST['senha_conta'] ?? '');

            if (!$criarConta) {
                // Cliente não está logado e não marcou checkbox de criar conta
                $errors[] = 'Para finalizar sua compra, faça login ou crie uma conta.';
            } elseif (empty($senhaConta)) {
                // Checkbox marcado mas senha não informada
                $errors[] = 'Senha é obrigatória para criar conta.';
            } elseif (strlen($senhaConta) < 6) {
                // Senha muito curta
                $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
            }
        }

        // Se houver erros, retornar para o formulário
        if (!empty($errors)) {
            $cart = CartService::get();
            $subtotal = CartService::getSubtotal();
            $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $cart['items']);
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

            $this->view('storefront/checkout/index', [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'metodosPagamento' => $metodosPagamento,
                'customer' => $customer,
                'customerAddresses' => $customerAddresses,
                'errors' => $errors,
                'formData' => $_POST,
            ]);
            return;
        }

        // Iniciar transação
        try {
            $db->beginTransaction();

            // Se cliente não está logado mas marcou checkbox, criar conta
            if (!$customerId) {
                $criarConta = isset($_POST['criar_conta']) && $_POST['criar_conta'] == '1';
                $senhaConta = trim($_POST['senha_conta'] ?? '');

                if ($criarConta && !empty($senhaConta)) {
                    // Verificar se email já existe para este tenant
                    $stmt = $db->prepare("
                        SELECT id FROM customers 
                        WHERE tenant_id = :tenant_id 
                        AND email = :email 
                        LIMIT 1
                    ");
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'email' => $clienteEmail,
                    ]);

                    if ($stmt->fetch()) {
                        // Email já existe, fazer login ao invés de criar conta
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
                        
                        if ($existingCustomer && !empty($existingCustomer['password_hash'])) {
                            // Verificar senha
                            if (password_verify($senhaConta, $existingCustomer['password_hash'])) {
                                $customerId = (int)$existingCustomer['id'];
                                $_SESSION['customer_id'] = $customerId;
                                $_SESSION['customer_name'] = $existingCustomer['name'];
                                $_SESSION['customer_email'] = $existingCustomer['email'];
                            } else {
                                throw new \Exception('E-mail já cadastrado. Use a senha correta ou faça login.');
                            }
                        } else {
                            throw new \Exception('E-mail já cadastrado. Faça login para continuar.');
                        }
                    } else {
                        // Criar nova conta
                        $passwordHash = password_hash($senhaConta, PASSWORD_DEFAULT);

                        $stmt = $db->prepare("
                            INSERT INTO customers (
                                tenant_id, name, email, password_hash, phone, created_at, updated_at
                            ) VALUES (
                                :tenant_id, :name, :email, :password_hash, :phone, NOW(), NOW()
                            )
                        ");
                        $stmt->execute([
                            'tenant_id' => $tenantId,
                            'name' => $clienteNome,
                            'email' => $clienteEmail,
                            'password_hash' => $passwordHash,
                            'phone' => $clienteTelefone ?: null,
                        ]);

                        $customerId = (int)$db->lastInsertId();

                        // Login automático do novo cliente
                        $_SESSION['customer_id'] = $customerId;
                        $_SESSION['customer_name'] = $clienteNome;
                        $_SESSION['customer_email'] = $clienteEmail;
                    }
                }
            }

            // Garantir que customer_id não seja null
            if (!$customerId) {
                throw new \Exception('Erro ao processar conta do cliente. Tente novamente.');
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

            // Inserir itens do pedido
            $stmtItem = $db->prepare("
                INSERT INTO pedido_itens (
                    tenant_id, pedido_id, produto_id,
                    nome_produto, sku, quantidade, preco_unitario, total_linha,
                    created_at, updated_at
                ) VALUES (
                    :tenant_id, :pedido_id, :produto_id,
                    :nome_produto, :sku, :quantidade, :preco_unitario, :total_linha,
                    NOW(), NOW()
                )
            ");

            foreach ($cart['items'] as $item) {
                // Buscar SKU do produto
                $stmtSku = $db->prepare("SELECT sku FROM produtos WHERE id = :id AND tenant_id = :tenant_id");
                $stmtSku->execute(['id' => $item['produto_id'], 'tenant_id' => $tenantId]);
                $sku = $stmtSku->fetchColumn();

                $totalLinha = $item['preco_unitario'] * $item['quantidade'];

                $stmtItem->execute([
                    'tenant_id' => $tenantId,
                    'pedido_id' => $pedidoId,
                    'produto_id' => $item['produto_id'],
                    'nome_produto' => $item['nome'],
                    'sku' => $sku ?: null,
                    'quantidade' => $item['quantidade'],
                    'preco_unitario' => $item['preco_unitario'],
                    'total_linha' => $totalLinha,
                ]);
            }

            // Processar pagamento usando o provider configurado
            $cliente = [
                'nome' => $clienteNome,
                'email' => $clienteEmail,
                'telefone' => $clienteTelefone,
            ];
            
            $pedidoData = [
                'id' => $pedidoId,
                'numero_pedido' => $numeroPedido,
                'total_geral' => $totalGeral,
            ];
            
            $paymentResult = PaymentService::processarPagamento($metodoPagamento, $pedidoData, $cliente);
            
            // Atualizar pedido com código de transação e status
            $stmtUpdate = $db->prepare("
                UPDATE pedidos 
                SET codigo_transacao = :codigo_transacao,
                    status = :status,
                    updated_at = NOW()
                WHERE id = :pedido_id 
                AND tenant_id = :tenant_id
            ");
            $stmtUpdate->execute([
                'codigo_transacao' => $paymentResult->codigoTransacao,
                'status' => $paymentResult->statusInicial,
                'pedido_id' => $pedidoId,
                'tenant_id' => $tenantId,
            ]);

            $db->commit();

            // Limpar carrinho
            CartService::clear();

            // Redirecionar para página de confirmação
            $this->redirect("/pedido/{$numeroPedido}/confirmacao");

        } catch (\Exception $e) {
            $db->rollBack();
            // Log do erro (pode melhorar depois)
            error_log("Erro ao processar pedido: " . $e->getMessage());
            
            $cart = CartService::get();
            $subtotal = CartService::getSubtotal();
            $opcoesFrete = ShippingService::calcularFrete($tenantId, $entregaCep, $subtotal, $cart['items']);
            $metodosPagamento = PaymentService::listarMetodosDisponiveis($tenantId);

            $this->view('storefront/checkout/index', [
                'cart' => $cart,
                'subtotal' => $subtotal,
                'opcoesFrete' => $opcoesFrete,
                'metodosPagamento' => $metodosPagamento,
                'errors' => ['Erro ao processar pedido. Tente novamente.'],
                'formData' => $_POST,
            ]);
        }
    }
}


