<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$paymentGateway = $paymentGateway ?? ['codigo' => 'manual', 'config_json' => null];
$shippingGateway = $shippingGateway ?? ['codigo' => 'simples', 'config_json' => null];
$message = $message ?? null;
$messageType = $messageType ?? 'success';
?>
<div class="admin-content-header">
    <h1><i class="bi bi-plug icon"></i> Integrações / Gateways</h1>
    <p>Configure os gateways de pagamento e frete da sua loja</p>
</div>

<?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $messageType ?>" style="margin-bottom: 2rem;">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $basePath ?>/admin/configuracoes/gateways" class="admin-form">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
        <!-- Gateway de Pagamento -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-credit-card icon"></i> Gateway de Pagamento</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="payment_gateway_code">Provedor de Pagamento *</label>
                    <select id="payment_gateway_code" name="payment_gateway_code" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                        <option value="manual" <?= $paymentGateway['codigo'] === 'manual' ? 'selected' : '' ?>>Manual / PIX</option>
                        <!-- Futuro: outras opções aparecerão aqui quando implementadas -->
                        <!-- <option value="mercadopago">Mercado Pago</option> -->
                        <!-- <option value="asaas">Asaas</option> -->
                        <!-- <option value="pagarme">Pagarme</option> -->
                    </select>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                        Por enquanto, apenas "Manual / PIX" está implementado. Outros gateways podem ser adicionados no futuro.
                    </small>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="payment_config_json">Configurações JSON (Opcional)</label>
                    <textarea 
                        id="payment_config_json" 
                        name="payment_config_json" 
                        rows="6" 
                        placeholder='{"mensagem_instrucoes": "Instruções personalizadas", "instrucoes": "Texto adicional"}'
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: monospace;"><?= htmlspecialchars($paymentGateway['config_json'] ?? '') ?></textarea>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                        Configure credenciais e parâmetros específicos do gateway em formato JSON. 
                        Exemplo para Manual: <code>{"mensagem_instrucoes": "Sua mensagem personalizada"}</code>
                    </small>
                </div>
            </div>
        </div>

        <!-- Gateway de Frete -->
        <div class="card">
            <div class="card-header">
                <h2><i class="bi bi-truck icon"></i> Gateway de Frete</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="shipping_gateway_code">Provedor de Frete *</label>
                    <select id="shipping_gateway_code" name="shipping_gateway_code" required style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                        <option value="simples" <?= $shippingGateway['codigo'] === 'simples' ? 'selected' : '' ?>>Frete Simples (Tabelado)</option>
                        <!-- Futuro: outras opções aparecerão aqui quando implementadas -->
                        <!-- <option value="melhorenvio">Melhor Envio</option> -->
                        <!-- <option value="correios">Correios</option> -->
                    </select>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                        Por enquanto, apenas "Frete Simples" está implementado. Outros provedores podem ser adicionados no futuro.
                    </small>
                </div>

                <div class="form-group" style="margin-top: 1.5rem;">
                    <label for="shipping_config_json">Configurações JSON (Opcional)</label>
                    <textarea 
                        id="shipping_config_json" 
                        name="shipping_config_json" 
                        rows="6" 
                        placeholder='{"limite_frete_gratis": 299.00, "frete_sudeste": 19.90, "frete_outras_regioes": 29.90}'
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: monospace;"><?= htmlspecialchars($shippingGateway['config_json'] ?? '') ?></textarea>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                        Configure valores e regras de frete em formato JSON. 
                        Exemplo: <code>{"limite_frete_gratis": 299.00, "frete_sudeste": 19.90, "frete_outras_regioes": 29.90}</code>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: right; margin-top: 2rem;">
        <button type="submit" class="btn-primary">
            <i class="bi bi-save icon"></i> Salvar Configurações
        </button>
    </div>
</form>

<style>
/* Fase 10 – Ajustes layout Admin - Gateways */
.admin-content-header {
    margin-bottom: 2rem;
}
.admin-content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.admin-content-header p {
    color: #666;
    font-size: 1rem;
}
@media (max-width: 768px) {
    .admin-form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>


