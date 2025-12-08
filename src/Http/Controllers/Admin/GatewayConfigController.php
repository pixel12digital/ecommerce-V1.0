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

        $message = $_SESSION['gateway_message'] ?? null;
        $messageType = $_SESSION['gateway_message_type'] ?? 'success';
        unset($_SESSION['gateway_message'], $_SESSION['gateway_message_type']);

        $this->viewWithLayout('admin/layouts/store', 'admin/gateways/index-content', [
            'pageTitle' => 'Integrações / Gateways',
            'paymentGateway' => $paymentGateway,
            'shippingGateway' => $shippingGateway,
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

        if (!empty($shippingConfigJson)) {
            $decoded = json_decode($shippingConfigJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = 'JSON de configuração de frete inválido: ' . json_last_error_msg();
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
}


