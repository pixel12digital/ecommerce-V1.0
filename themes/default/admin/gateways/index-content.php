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
    <div style="display: flex; flex-direction: column; gap: 2rem; margin-bottom: 2rem; max-width: 800px;">
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
                    <select id="shipping_gateway_code" name="shipping_gateway_code" required 
                            onchange="toggleShippingConfig()"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                        <option value="simples" <?= $shippingGateway['codigo'] === 'simples' ? 'selected' : '' ?>>Frete Simples (Tabelado)</option>
                        <option value="correios" <?= $shippingGateway['codigo'] === 'correios' ? 'selected' : '' ?>>Correios (Contrato Direto)</option>
                    </select>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                        Selecione o provedor de frete da sua loja.
                    </small>
                </div>

                <!-- Configuração Frete Simples -->
                <div id="shipping_config_simples" class="shipping-config-section" style="display: <?= $shippingGateway['codigo'] === 'simples' ? 'block' : 'none' ?>; margin-top: 1.5rem;">
                    <div class="form-group">
                        <label for="shipping_config_json">Configurações JSON (Opcional)</label>
                        <textarea 
                            id="shipping_config_json" 
                            name="shipping_config_json" 
                            rows="6" 
                            placeholder='{"limite_frete_gratis": 299.00, "frete_sudeste": 19.90, "frete_outras_regioes": 29.90}'
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: monospace;"><?= htmlspecialchars($shippingGateway['codigo'] === 'simples' ? ($shippingGateway['config_json'] ?? '') : '') ?></textarea>
                        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                            Configure valores e regras de frete em formato JSON. 
                            Exemplo: <code>{"limite_frete_gratis": 299.00, "frete_sudeste": 19.90, "frete_outras_regioes": 29.90}</code>
                        </small>
                    </div>
                </div>

                <!-- Configuração Correios -->
                <div id="shipping_config_correios" class="shipping-config-section" style="display: <?= $shippingGateway['codigo'] === 'correios' ? 'block' : 'none' ?>; margin-top: 1.5rem;">
                    <?php 
                    $correiosConfig = $shippingConfig ?? [];
                    $origem = $correiosConfig['origem'] ?? [];
                    $credenciais = $correiosConfig['credenciais'] ?? [];
                    $servicos = $correiosConfig['servicos'] ?? ['pac' => true, 'sedex' => true];
                    ?>
                    
                    <!-- A) Dados do Remetente -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #023A8D;">
                        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.125rem; color: #023A8D;">
                            <i class="bi bi-person-fill icon"></i> Dados do Remetente
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin: 0;">
                                <label for="correios_cep_origem">CEP de Origem * <small style="color: #666;">(8 dígitos)</small></label>
                                <input 
                                    type="text" 
                                    id="correios_cep_origem" 
                                    name="correios_cep_origem" 
                                    value="<?= htmlspecialchars($origem['cep'] ?? '') ?>"
                                    maxlength="8" 
                                    pattern="[0-9]{8}"
                                    placeholder="00000000"
                                    oninput="this.value = this.value.replace(/\D/g, '')"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                    required>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label for="correios_remetente_telefone">Telefone</label>
                                <input 
                                    type="text" 
                                    id="correios_remetente_telefone" 
                                    name="correios_remetente_telefone" 
                                    value="<?= htmlspecialchars($origem['telefone'] ?? '') ?>"
                                    placeholder="(00) 00000-0000"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="correios_remetente_nome">Nome/Razão Social *</label>
                            <input 
                                type="text" 
                                id="correios_remetente_nome" 
                                name="correios_remetente_nome" 
                                value="<?= htmlspecialchars($origem['nome'] ?? '') ?>"
                                placeholder="Nome da empresa ou loja"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                required>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label for="correios_remetente_documento">CPF/CNPJ <small style="color: #666;">(opcional)</small></label>
                            <input 
                                type="text" 
                                id="correios_remetente_documento" 
                                name="correios_remetente_documento" 
                                value="<?= htmlspecialchars($origem['documento'] ?? '') ?>"
                                placeholder="00000000000 ou 00000000000000"
                                oninput="this.value = this.value.replace(/\D/g, '')"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                        </div>
                        
                        <details style="margin-top: 1rem;">
                            <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.5rem; background: white; border-radius: 4px; border: 1px solid #ddd;">
                                Endereço Completo do Remetente <small>(opcional, apenas se necessário para etiqueta)</small>
                            </summary>
                            <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 4px;">
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_logradouro">Logradouro</label>
                                        <input type="text" id="correios_remetente_logradouro" name="correios_remetente_logradouro" 
                                               value="<?= htmlspecialchars($origem['endereco']['logradouro'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_numero">Número</label>
                                        <input type="text" id="correios_remetente_numero" name="correios_remetente_numero" 
                                               value="<?= htmlspecialchars($origem['endereco']['numero'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_complemento">Complemento</label>
                                        <input type="text" id="correios_remetente_complemento" name="correios_remetente_complemento" 
                                               value="<?= htmlspecialchars($origem['endereco']['complemento'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_bairro">Bairro</label>
                                        <input type="text" id="correios_remetente_bairro" name="correios_remetente_bairro" 
                                               value="<?= htmlspecialchars($origem['endereco']['bairro'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_cidade">Cidade</label>
                                        <input type="text" id="correios_remetente_cidade" name="correios_remetente_cidade" 
                                               value="<?= htmlspecialchars($origem['endereco']['cidade'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_remetente_estado">UF</label>
                                        <input type="text" id="correios_remetente_estado" name="correios_remetente_estado" 
                                               value="<?= htmlspecialchars($origem['endereco']['uf'] ?? '') ?>"
                                               maxlength="2" placeholder="SP"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- B) Credenciais do Contrato -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #856404;">
                        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.125rem; color: #856404;">
                            <i class="bi bi-key-fill icon"></i> Credenciais do Contrato Correios
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                            <div class="form-group" style="margin: 0;">
                                <label for="correios_usuario">Usuário *</label>
                                <input 
                                    type="text" 
                                    id="correios_usuario" 
                                    name="correios_usuario" 
                                    value="<?= htmlspecialchars($credenciais['usuario'] ?? '') ?>"
                                    placeholder="Usuário da API Correios"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                    required>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label for="correios_senha">Senha *</label>
                                <input 
                                    type="password" 
                                    id="correios_senha" 
                                    name="correios_senha" 
                                    value="<?= !empty($credenciais['senha_masked']) ? '' : htmlspecialchars($credenciais['senha'] ?? '') ?>"
                                    placeholder="<?= !empty($credenciais['senha_masked']) ? '******** (digite para alterar)' : 'Senha da API Correios' ?>"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                                <?php if (!empty($credenciais['senha_masked'])): ?>
                                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                                        Senha atual mantida. Digite apenas se desejar alterar.
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <details style="margin-top: 1rem;">
                            <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.5rem; background: white; border-radius: 4px; border: 1px solid #ddd;">
                                Campos Opcionais do Contrato <small>(preencha conforme seu contrato)</small>
                            </summary>
                            <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 4px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_codigo_administrativo">Código Administrativo</label>
                                        <input type="text" id="correios_codigo_administrativo" name="correios_codigo_administrativo" 
                                               value="<?= htmlspecialchars($credenciais['codigo_administrativo'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_cartao_postagem">Cartão de Postagem</label>
                                        <input type="text" id="correios_cartao_postagem" name="correios_cartao_postagem" 
                                               value="<?= htmlspecialchars($credenciais['cartao_postagem'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_contrato">Contrato</label>
                                        <input type="text" id="correios_contrato" name="correios_contrato" 
                                               value="<?= htmlspecialchars($credenciais['contrato'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="correios_diretoria">Diretoria/Unidade</label>
                                        <input type="text" id="correios_diretoria" name="correios_diretoria" 
                                               value="<?= htmlspecialchars($credenciais['diretoria'] ?? '') ?>"
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>

                    <!-- C) Serviços Habilitados -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #d4edda; border-radius: 8px; border-left: 4px solid #155724;">
                        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.125rem; color: #155724;">
                            <i class="bi bi-check-circle-fill icon"></i> Serviços Habilitados
                        </h3>
                        <div style="display: flex; gap: 2rem; flex-wrap: wrap;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input 
                                    type="checkbox" 
                                    name="correios_servico_pac" 
                                    value="1" 
                                    <?= ($servicos['pac'] ?? true) ? 'checked' : '' ?>
                                    style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                <span style="font-weight: 600;">PAC</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input 
                                    type="checkbox" 
                                    name="correios_servico_sedex" 
                                    value="1" 
                                    <?= ($servicos['sedex'] ?? true) ? 'checked' : '' ?>
                                    style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                <span style="font-weight: 600;">SEDEX</span>
                            </label>
                        </div>
                        <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                            Selecione os serviços que deseja oferecer no checkout. Pelo menos um deve estar habilitado.
                        </small>
                    </div>

                    <!-- D) Regras Fixas -->
                    <div style="margin-bottom: 2rem; padding: 1rem; background: #e9ecef; border-radius: 8px; font-size: 0.875rem; color: #666;">
                        <strong>Regras do Sistema:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">
                            <li>Seguro: Sempre desativado (não contratado)</li>
                        </ul>
                    </div>

                    <!-- E) JSON Avançado (Opcional) -->
                    <details style="margin-top: 1.5rem;">
                        <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; border: 1px solid #ddd;">
                            Configurações JSON Avançadas <small>(apenas para casos especiais)</small>
                        </summary>
                        <div style="margin-top: 1rem;">
                            <div class="form-group">
                                <label for="shipping_config_json_advanced">JSON Personalizado</label>
                                <textarea 
                                    id="shipping_config_json_advanced" 
                                    name="shipping_config_json" 
                                    rows="8" 
                                    placeholder='{"correios": {...}}'
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; font-family: monospace; background: #f8f9fa;"><?= htmlspecialchars($shippingGateway['codigo'] === 'correios' ? json_encode($correiosConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') ?></textarea>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                                    ⚠️ Se preenchido, este JSON sobrescreve os campos acima. Use apenas se necessário configurar algo específico.
                                </small>
                            </div>
                        </div>
                    </details>
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
.shipping-config-section {
    border-top: 1px solid #eee;
    padding-top: 1.5rem;
}
.form-group {
    margin-bottom: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
}
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}
.card-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}
.card-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.card-body {
    padding: 1.5rem;
}
@media (max-width: 768px) {
    .admin-form > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    .shipping-config-section > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
function toggleShippingConfig() {
    const select = document.getElementById('shipping_gateway_code');
    const selectedValue = select.value;
    
    // Ocultar todas as seções
    document.getElementById('shipping_config_simples').style.display = 'none';
    document.getElementById('shipping_config_correios').style.display = 'none';
    
    // Mostrar a seção apropriada
    if (selectedValue === 'simples') {
        document.getElementById('shipping_config_simples').style.display = 'block';
    } else if (selectedValue === 'correios') {
        document.getElementById('shipping_config_correios').style.display = 'block';
    }
}

// Executar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    toggleShippingConfig();
});
</script>


