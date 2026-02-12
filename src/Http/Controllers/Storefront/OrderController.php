<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\Payment\PaymentService;
use App\Services\CartService;
use App\Services\ThemeConfig;

class OrderController extends Controller
{
    public function thankYou(string $numeroPedido): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE tenant_id = :tenant_id 
            AND numero_pedido = :numero_pedido
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'numero_pedido' => $numeroPedido
        ]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Pedido não encontrado']);
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE tenant_id = :tenant_id 
            AND pedido_id = :pedido_id
            ORDER BY id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'pedido_id' => $pedido['id']
        ]);
        $itens = $stmt->fetchAll();

        // Se pedido está pendente e tem código de transação, consultar Cielo para atualizar
        if ($pedido['status'] === 'pending' && !empty($pedido['codigo_transacao'])) {
            $novoStatus = PaymentService::consultarEAtualizarStatus($tenantId, $pedido);
            if ($novoStatus && $novoStatus !== $pedido['status']) {
                $pedido['status'] = $novoStatus;
            }
        }

        // Buscar instruções de pagamento
        $instrucoesPagamento = PaymentService::getInstrucoes($pedido['metodo_pagamento']);

        // Decodificar payment_details (ex: QR PIX Cielo)
        $paymentDetails = [];
        if (!empty($pedido['payment_details'])) {
            $decoded = json_decode($pedido['payment_details'], true);
            $paymentDetails = is_array($decoded) ? $decoded : [];
        }

        $tenant = TenantContext::tenant();
        $theme = ThemeConfig::getFullThemeConfig();

        // Verificar se o cliente logado já tem senha definida
        $customerId = $_SESSION['customer_id'] ?? null;
        $customerNeedPassword = false;
        if ($customerId) {
            $stmtC = $db->prepare("SELECT id, password_hash FROM customers WHERE id = :id AND tenant_id = :tid LIMIT 1");
            $stmtC->execute(['id' => (int)$customerId, 'tid' => $tenantId]);
            $customerData = $stmtC->fetch(\PDO::FETCH_ASSOC);
            if ($customerData && empty($customerData['password_hash'])) {
                $customerNeedPassword = true;
            }
        }

        $this->view('storefront/orders/thank_you', [
            'pedido' => $pedido,
            'itens' => $itens,
            'instrucoesPagamento' => $instrucoesPagamento,
            'paymentDetails' => $paymentDetails,
            'customerNeedPassword' => $customerNeedPassword,
            'customerId' => $customerId,
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'theme' => $theme,
            'cartTotalItems' => CartService::getTotalItems(),
            'cartSubtotal' => CartService::getSubtotal(),
        ]);
    }

    /**
     * POST: Criar senha direto na página de confirmação (primeiro acesso sem email)
     */
    public function setPasswordDirect(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $customerId = $_SESSION['customer_id'] ?? null;
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $numeroPedido = trim($_POST['numero_pedido'] ?? '');

        if (!$customerId) {
            $this->redirect('/minha-conta/login');
            return;
        }

        $errors = [];
        if (empty($password)) {
            $errors[] = 'Senha é obrigatória.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            $_SESSION['password_errors'] = $errors;
            $this->redirect("/pedido/{$numeroPedido}/confirmacao");
            return;
        }

        // Salvar senha
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            UPDATE customers SET 
                password_hash = :password_hash,
                updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id AND (password_hash IS NULL OR password_hash = '')
        ");
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => (int)$customerId,
            'tenant_id' => $tenantId,
        ]);

        $_SESSION['password_created'] = true;
        $this->redirect("/pedido/{$numeroPedido}/confirmacao");
    }
}


