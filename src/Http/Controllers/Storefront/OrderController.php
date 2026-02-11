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

        $this->view('storefront/orders/thank_you', [
            'pedido' => $pedido,
            'itens' => $itens,
            'instrucoesPagamento' => $instrucoesPagamento,
            'paymentDetails' => $paymentDetails,
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'theme' => $theme,
            'cartTotalItems' => CartService::getTotalItems(),
            'cartSubtotal' => CartService::getSubtotal(),
        ]);
    }
}


