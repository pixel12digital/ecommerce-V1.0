<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Services\Payment\PaymentService;

class PaymentWebhookController extends Controller
{
    /**
     * Webhook da Cielo — recebe notificação de mudança de status do pagamento
     * URL: POST /api/payment/webhook/cielo
     * 
     * A Cielo envia: { "PaymentId": "xxx", "ChangeType": "1" }
     * ChangeType 1 = mudança de status do pagamento
     */
    public function cieloWebhook(): void
    {
        $input = file_get_contents('php://input');
        error_log("Cielo Webhook received: " . $input);

        $data = json_decode($input, true);
        if (!is_array($data)) {
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'reason' => 'invalid json']);
            return;
        }

        $paymentId = $data['PaymentId'] ?? null;
        $changeType = $data['ChangeType'] ?? null;

        if (empty($paymentId)) {
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'reason' => 'no payment id']);
            return;
        }

        $db = Database::getConnection();

        // Buscar pedido pelo código de transação (PaymentId da Cielo)
        $stmt = $db->prepare("
            SELECT p.*, t.id as t_id
            FROM pedidos p
            JOIN tenants t ON t.id = p.tenant_id
            WHERE p.codigo_transacao = :payment_id
            AND p.status = 'pending'
            LIMIT 1
        ");
        $stmt->execute(['payment_id' => $paymentId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            error_log("Cielo Webhook: pedido não encontrado ou já processado para PaymentId={$paymentId}");
            http_response_code(200);
            echo json_encode(['status' => 'ignored', 'reason' => 'order not found or already processed']);
            return;
        }

        // Consultar status atualizado na Cielo
        $tenantId = (int)$pedido['tenant_id'];

        // Carregar config do tenant
        $stmtGw = $db->prepare("
            SELECT config_json FROM tenant_gateways 
            WHERE tenant_id = :tid AND tipo = 'payment' AND ativo = 1 AND codigo = 'cielo'
            LIMIT 1
        ");
        $stmtGw->execute(['tid' => $tenantId]);
        $gwRow = $stmtGw->fetch(\PDO::FETCH_ASSOC);

        if (!$gwRow) {
            error_log("Cielo Webhook: gateway config não encontrado para tenant={$tenantId}");
            http_response_code(200);
            echo json_encode(['status' => 'error', 'reason' => 'gateway config not found']);
            return;
        }

        $config = json_decode($gwRow['config_json'], true) ?: [];
        $provider = new \App\Services\Payment\Providers\CieloPaymentProvider();

        try {
            $result = $provider->consultarPagamento($paymentId, $config);
        } catch (\Exception $e) {
            error_log("Cielo Webhook: erro ao consultar - " . $e->getMessage());
            http_response_code(200);
            echo json_encode(['status' => 'error', 'reason' => $e->getMessage()]);
            return;
        }

        $novoStatus = $result['status'] ?? null;
        if ($novoStatus && $novoStatus !== 'pending') {
            $stmtUp = $db->prepare("
                UPDATE pedidos SET status = :status, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tid AND status = 'pending'
            ");
            $stmtUp->execute([
                'status' => $novoStatus,
                'id' => $pedido['id'],
                'tid' => $tenantId,
            ]);
            error_log("Cielo Webhook: Pedido #{$pedido['numero_pedido']} atualizado para '{$novoStatus}' (Cielo status: {$result['cielo_status']})");
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok', 'new_status' => $novoStatus]);
    }

    /**
     * API: Consulta status do pagamento de um pedido (usado pelo frontend via AJAX polling)
     * GET /api/payment/status/{numero_pedido}
     */
    public function checkStatus(string $numeroPedido): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json');

        $tenantId = \App\Tenant\TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE numero_pedido = :num AND tenant_id = :tid
            LIMIT 1
        ");
        $stmt->execute(['num' => $numeroPedido, 'tid' => $tenantId]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            echo json_encode(['error' => 'Pedido não encontrado']);
            return;
        }

        // Se ainda pendente, consultar Cielo
        if ($pedido['status'] === 'pending' && !empty($pedido['codigo_transacao'])) {
            $novoStatus = PaymentService::consultarEAtualizarStatus($tenantId, $pedido);
            $pedido['status'] = $novoStatus ?? $pedido['status'];
        }

        echo json_encode([
            'status' => $pedido['status'],
            'status_label' => \App\Support\LangHelper::orderStatusLabel($pedido['status']),
        ]);
    }
}
