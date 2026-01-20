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

<form method="POST" action="<?= $basePath ?>/admin/configuracoes/gateways" class="admin-form" onsubmit="return prepararSubmitFormulario()">
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
                    $modoIntegracao = $correiosConfig['modo_integracao'] ?? 'cws'; // Default: CWS
                    ?>
                    
                    <!-- Modo de Integração -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #0066cc;">
                        <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.125rem; color: #0066cc;">
                            <i class="bi bi-gear-fill icon"></i> Modo de Integração
                        </h3>
                        <div class="form-group">
                            <label for="correios_modo_integracao">Tipo de Integração *</label>
                            <select 
                                id="correios_modo_integracao" 
                                name="correios_modo_integracao" 
                                onchange="toggleCorreiosMode()"
                                style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                required>
                                <option value="cws" <?= $modoIntegracao === 'cws' ? 'selected' : '' ?>>CWS (API v3 - Token)</option>
                                <option value="legado" <?= $modoIntegracao === 'legado' ? 'selected' : '' ?>>Legado/SIGEP</option>
                            </select>
                            <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                                <strong>CWS (recomendado):</strong> Usa APIs modernas Preço v3, Prazo v3 e CEP v3 com autenticação por token.
                                <br><strong>Legado/SIGEP:</strong> Para integrações antigas que ainda usam senha SFE e campos de contrato.
                            </small>
                        </div>
                    </div>
                    
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
                                    onblur="validarCepOrigem(this)"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                    <?= ($shippingGateway['codigo'] ?? '') === 'correios' ? 'required' : '' ?>>
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
                        
                        <details id="correios_endereco_completo" style="margin-top: 1rem;">
                            <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.5rem; background: white; border-radius: 4px; border: 1px solid #ddd;">
                                Endereço Completo do Remetente <small id="correios_endereco_help">(opcional, apenas se necessário para etiqueta)</small>
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
                        
                        <!-- Campos CWS (modo padrão) -->
                        <div id="correios_cws_fields">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="correios_usuario">Usuário (Meu Correios / idCorreios) *</label>
                                    <input 
                                        type="text" 
                                        id="correios_usuario" 
                                        name="correios_usuario" 
                                        value="<?= htmlspecialchars($credenciais['usuario'] ?? '') ?>"
                                        placeholder="Seu ID no portal Correios"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                        <?= ($shippingGateway['codigo'] ?? '') === 'correios' && ($modoIntegracao ?? 'cws') === 'cws' ? 'required' : '' ?>>
                                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                                        Use o login do Meu Correios (conforme cadastro: e-mail/CPF/CNPJ).
                                    </small>
                                </div>
                                
                                <div class="form-group" style="margin: 0;">
                                    <label for="correios_codigo_acesso_apis">Código de acesso às APIs (CWS) *</label>
                                    <?php 
                                    // Compatibilidade: verificar se existe chave_acesso_cws antiga ou codigo_acesso_apis novo
                                    $codigoAcessoMasked = !empty($credenciais['codigo_acesso_apis_masked']) || 
                                                          (!empty($credenciais['chave_acesso_cws_masked']) && empty($credenciais['codigo_acesso_apis']));
                                    if ($codigoAcessoMasked): ?>
                                        <!-- Indicador visual de que o código está salvo -->
                                        <div id="codigo_acesso_salvo_indicator" style="padding: 0.5rem; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 0.5rem; color: #155724; font-size: 0.875rem;">
                                            <i class="bi bi-check-circle" style="color: #28a745; margin-right: 0.5rem;"></i>
                                            <strong>Código salvo no sistema.</strong> Digite um novo código apenas se desejar alterar.
                                        </div>
                                    <?php endif; ?>
                                    <input 
                                        type="password" 
                                        id="correios_codigo_acesso_apis" 
                                        name="correios_codigo_acesso_apis" 
                                        value="<?= $codigoAcessoMasked ? '' : htmlspecialchars($credenciais['codigo_acesso_apis'] ?? $credenciais['chave_acesso_cws'] ?? '') ?>"
                                        placeholder="<?= $codigoAcessoMasked ? 'Digite novo código para alterar (ou deixe vazio para manter)' : 'Código gerado no portal CWS' ?>"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                        <?= ($shippingGateway['codigo'] ?? '') === 'correios' && ($modoIntegracao ?? 'cws') === 'cws' && !$codigoAcessoMasked ? 'required' : '' ?>>
                                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                                        Gerado em: CWS > Gestão de acesso a API's > Gerar/Regenerar código de acesso
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="correios_contrato_cws">Nº do contrato (Correios) *</label>
                                <input 
                                    type="text" 
                                    id="correios_contrato_cws" 
                                    name="correios_contrato" 
                                    value="<?= htmlspecialchars($credenciais['contrato'] ?? '') ?>"
                                    placeholder="Número do contrato com os Correios"
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                    <?= ($shippingGateway['codigo'] ?? '') === 'correios' && ($modoIntegracao ?? 'cws') === 'cws' ? 'required' : '' ?>>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                                    Número do contrato com os Correios (obrigatório no modo CWS).
                                </small>
                            </div>
                            
                            <!-- Botão de Teste -->
                            <div style="margin-top: 1rem; padding: 1rem; background: #f0f8ff; border-radius: 4px; border: 1px solid #b3d9ff;">
                                <h4 style="margin-top: 0; margin-bottom: 1rem; font-size: 1rem; color: #0066cc;">
                                    <i class="bi bi-flash icon"></i> Teste de Conexão
                                </h4>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="teste_cep_destino">CEP Destino (teste) *</label>
                                        <input 
                                            type="text" 
                                            id="teste_cep_destino" 
                                            maxlength="8" 
                                            pattern="[0-9]{8}"
                                            placeholder="01310100"
                                            oninput="this.value = this.value.replace(/\D/g, '')"
                                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="teste_peso">Peso (kg)</label>
                                        <input 
                                            type="number" 
                                            id="teste_peso" 
                                            step="0.1" 
                                            min="0.1" 
                                            value="0.3"
                                            placeholder="0.3"
                                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="teste_dimensoes">Dimensões (C x L x A cm)</label>
                                        <input 
                                            type="text" 
                                            id="teste_dimensoes" 
                                            placeholder="20x20x10"
                                            pattern="\d+x\d+x\d+"
                                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                                        <small style="color: #666; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                                            Formato: 20x20x10 (comprimento x largura x altura)
                                        </small>
                                    </div>
                                </div>
                                
                                <button type="button" id="btn_testar_correios" onclick="testarCorreios()" 
                                        style="padding: 0.75rem 1.5rem; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; font-weight: 600;">
                                    <i class="bi bi-play-circle icon"></i> Testar Conexão Correios
                                </button>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                                    Testa a autenticação (token) e consulta de preço/prazo para os serviços habilitados (PAC e/ou SEDEX).
                                </small>
                                <div id="correios_test_result" style="margin-top: 1rem; display: none;"></div>
                            </div>
                            
                            <!-- Informações sobre Etiquetas (modo CWS) -->
                            <div id="correios_info_etiquetas" style="margin-top: 1rem; padding: 1rem; background: #e7f3ff; border-radius: 4px; border-left: 4px solid #0066cc;">
                                <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1rem; color: #0066cc;">
                                    <i class="bi bi-info-circle icon"></i> Sobre Etiquetas
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; color: #333; font-size: 0.875rem; line-height: 1.6;">
                                    <li><strong>CWS (Token)</strong> calcula frete (Preço/Prazo/CEP).</li>
                                    <li>A emissão automática de etiqueta depende de serviços de postagem habilitados no contrato.</li>
                                    <li>Se seu contrato não tiver esses serviços no CWS, a etiqueta deve ser emitida manualmente no portal do Correios.</li>
                                </ul>
                            </div>
                            
                            <!-- Campos Avançados (ocultos por padrão no modo CWS) -->
                            <details id="correios_advanced_cws" style="margin-top: 1rem;">
                                <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.5rem; background: white; border-radius: 4px; border: 1px solid #ddd;">
                                    Campos Avançados <small>(use apenas se estiver no modo Legado/SIGEP ou etiqueta antiga)</small>
                                </summary>
                                <div style="margin-top: 1rem; padding: 1rem; background: white; border-radius: 4px;">
                                    <div class="form-group" style="margin-bottom: 1rem;">
                                        <label for="correios_senha">Senha (SFE - apenas modo Legado)</label>
                                        <input 
                                            type="password" 
                                            id="correios_senha" 
                                            name="correios_senha" 
                                            value="<?= !empty($credenciais['senha_masked']) ? '' : htmlspecialchars($credenciais['senha'] ?? '') ?>"
                                            placeholder="<?= !empty($credenciais['senha_masked']) ? '******** (digite para alterar)' : 'Senha do SFE (modo Legado)' ?>"
                                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                        <?php if (!empty($credenciais['senha_masked'])): ?>
                                            <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                                                Senha atual mantida. Digite apenas se desejar alterar.
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                        <div class="form-group" style="margin: 0;">
                                            <label for="correios_codigo_servico_pac">Código do serviço PAC (coProduto)</label>
                                            <input type="text" id="correios_codigo_servico_pac" name="correios_codigo_servico_pac" 
                                                   value="<?= htmlspecialchars($credenciais['codigo_servico_pac'] ?? '03298') ?>"
                                                   placeholder="03298"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                            <small style="color: #666; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                                                Padrão: 03298 (API Preço v1). Códigos podem variar por contrato.
                                            </small>
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label for="correios_codigo_servico_sedex">Código do serviço SEDEX (coProduto)</label>
                                            <input type="text" id="correios_codigo_servico_sedex" name="correios_codigo_servico_sedex" 
                                                   value="<?= htmlspecialchars($credenciais['codigo_servico_sedex'] ?? '03220') ?>"
                                                   placeholder="03220"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                            <small style="color: #666; font-size: 0.75rem; display: block; margin-top: 0.25rem;">
                                                Padrão: 03220 (API Preço v1). Códigos podem variar por contrato.
                                            </small>
                                        </div>
                                    </div>
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
                                            <label for="correios_contrato_advanced">Contrato</label>
                                            <input type="text" id="correios_contrato_advanced" name="correios_contrato_advanced" 
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
                        
                        <!-- Campos Legado/SIGEP -->
                        <div id="correios_legado_fields" style="display: none;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="correios_usuario_legado">Usuário *</label>
                                    <input 
                                        type="text" 
                                        id="correios_usuario_legado" 
                                        name="correios_usuario" 
                                        value="<?= htmlspecialchars($credenciais['usuario'] ?? '') ?>"
                                        placeholder="Usuário da API Correios"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                        <?= ($modoIntegracao ?? 'cws') === 'legado' ? 'required' : '' ?>>
                                </div>
                                
                                <div class="form-group" style="margin: 0;">
                                    <label for="correios_senha_legado">Senha *</label>
                                    <input 
                                        type="password" 
                                        id="correios_senha_legado" 
                                        name="correios_senha" 
                                        value="<?= !empty($credenciais['senha_masked']) ? '' : htmlspecialchars($credenciais['senha'] ?? '') ?>"
                                        placeholder="<?= !empty($credenciais['senha_masked']) ? '******** (digite para alterar)' : 'Senha da API Correios' ?>"
                                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;"
                                        <?= ($modoIntegracao ?? 'cws') === 'legado' ? 'required' : '' ?>>
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
                                            <label for="correios_codigo_administrativo_legado">Código Administrativo</label>
                                            <input type="text" id="correios_codigo_administrativo_legado" name="correios_codigo_administrativo" 
                                                   value="<?= htmlspecialchars($credenciais['codigo_administrativo'] ?? '') ?>"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label for="correios_cartao_postagem_legado">Cartão de Postagem</label>
                                            <input type="text" id="correios_cartao_postagem_legado" name="correios_cartao_postagem" 
                                                   value="<?= htmlspecialchars($credenciais['cartao_postagem'] ?? '') ?>"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label for="correios_contrato_legado">Contrato</label>
                                            <input type="text" id="correios_contrato_legado" name="correios_contrato_legado" 
                                                   value="<?= htmlspecialchars($credenciais['contrato'] ?? '') ?>"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                        <div class="form-group" style="margin: 0;">
                                            <label for="correios_diretoria_legado">Diretoria/Unidade</label>
                                            <input type="text" id="correios_diretoria_legado" name="correios_diretoria" 
                                                   value="<?= htmlspecialchars($credenciais['diretoria'] ?? '') ?>"
                                                   style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem;">
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>
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

                    <!-- E) Checklist Pós-Venda (modo CWS) -->
                    <div id="correios_checklist_posvenda" style="margin-bottom: 2rem; padding: 1rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #856404; font-size: 0.875rem;">
                        <h4 style="margin-top: 0; margin-bottom: 0.75rem; font-size: 1rem; color: #856404;">
                            <i class="bi bi-list-check icon"></i> Checklist Pós-Venda
                        </h4>
                        <p style="margin: 0 0 0.75rem 0; color: #666;">
                            Após o cliente finalizar o pedido, siga estes passos:
                        </p>
                        <ol style="margin: 0; padding-left: 1.5rem; color: #333; line-height: 1.8;">
                            <li>Gerar etiqueta no portal do Correios</li>
                            <li>Postar o pacote</li>
                            <li>Informar o código de rastreio no pedido</li>
                        </ol>
                    </div>

                    <!-- E) JSON Avançado (Opcional) -->
                    <details id="correios_json_advanced" style="margin-top: 1.5rem;">
                        <summary style="cursor: pointer; color: #666; font-size: 0.875rem; padding: 0.75rem; background: #f8f9fa; border-radius: 4px; border: 1px solid #ddd;">
                            Configurações JSON Avançadas <small>(apenas para casos especiais)</small>
                        </summary>
                        <div style="margin-top: 1rem;">
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input 
                                        type="checkbox" 
                                        id="correios_json_override_enabled" 
                                        name="correios_json_override_enabled" 
                                        value="1"
                                        onchange="toggleJsonOverride()"
                                        style="width: 1.25rem; height: 1.25rem; cursor: pointer;">
                                    <span style="font-weight: 600; color: #dc3545;">Habilitar override por JSON (avançado)</span>
                                </label>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem; margin-left: 1.75rem;">
                                    ⚠️ Se habilitado, o JSON abaixo sobrescreve TODOS os campos acima. Use com cuidado!
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="shipping_config_json_advanced">JSON Personalizado</label>
                                <textarea 
                                    id="shipping_config_json_advanced" 
                                    name="shipping_config_json" 
                                    rows="8" 
                                    placeholder='{"correios": {...}}'
                                    disabled
                                    style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.85rem; font-family: monospace; background: #f8f9fa; opacity: 0.6;"><?= htmlspecialchars($shippingGateway['codigo'] === 'correios' ? json_encode($correiosConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') ?></textarea>
                                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                                    Este campo só será aplicado se o override estiver habilitado acima.
                                </small>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: right; margin-top: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-top: 2px solid #023A8D;">
        <button type="submit" class="btn-primary" style="padding: 1rem 2rem; font-size: 1.125rem; font-weight: 600; background: linear-gradient(135deg, #023A8D 0%, #0056b3 100%); color: white; border: none; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 6px rgba(2, 58, 141, 0.3); transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 0.75rem;">
            <i class="bi bi-save icon" style="font-size: 1.25rem;"></i> Salvar Configurações
        </button>
        <style>
            .btn-primary:hover {
                background: linear-gradient(135deg, #0056b3 0%, #023A8D 100%) !important;
                box-shadow: 0 6px 12px rgba(2, 58, 141, 0.4) !important;
                transform: translateY(-2px);
            }
            .btn-primary:active {
                transform: translateY(0);
                box-shadow: 0 2px 4px rgba(2, 58, 141, 0.3) !important;
            }
            .btn-primary:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none;
            }
        </style>
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
    
    // Remover required de TODOS os campos Correios quando gateway não for Correios
    const camposCorreios = [
        'correios_usuario',
        'correios_codigo_acesso_apis',
        'correios_contrato_cws',
        'correios_usuario_legado',
        'correios_senha_legado',
        'correios_cep_origem'
    ];
    
    camposCorreios.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            if (selectedValue !== 'correios') {
                campo.removeAttribute('required');
                campo.disabled = true;
            } else {
                campo.disabled = false;
                // O required será gerenciado pela função toggleCorreiosMode()
            }
        }
    });
    
    // Mostrar a seção apropriada
    if (selectedValue === 'simples') {
        document.getElementById('shipping_config_simples').style.display = 'block';
    } else if (selectedValue === 'correios') {
        document.getElementById('shipping_config_correios').style.display = 'block';
        // Re-executar toggleCorreiosMode para aplicar required correto
        toggleCorreiosMode();
    }
}

// Executar ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Primeiro configurar o gateway (isso vai desabilitar campos se não for Correios)
    toggleShippingConfig();
    // Depois configurar o modo Correios (isso vai aplicar required corretamente)
    toggleCorreiosMode();
});

// Controlar exibição de campos baseado no modo de integração Correios
function toggleCorreiosMode() {
    const modoSelect = document.getElementById('correios_modo_integracao');
    if (!modoSelect) return;
    
    const modo = modoSelect.value;
    const camposCws = document.getElementById('correios_cws_fields');
    const camposLegado = document.getElementById('correios_legado_fields');
    const enderecoCompleto = document.getElementById('correios_endereco_completo');
    const enderecoHelp = document.getElementById('correios_endereco_help');
    const jsonAdvanced = document.getElementById('correios_json_advanced');
    const infoEtiquetas = document.getElementById('correios_info_etiquetas');
    const checklistPosvenda = document.getElementById('correios_checklist_posvenda');
    
    if (modo === 'cws') {
        if (camposCws) camposCws.style.display = 'block';
        if (camposLegado) camposLegado.style.display = 'none';
        
        // Ocultar seção de endereço completo (etiqueta) no modo CWS
        if (enderecoCompleto) enderecoCompleto.style.display = 'none';
        if (enderecoHelp) enderecoHelp.textContent = '(opcional, apenas se necessário para etiqueta)';
        
        // Mostrar informações sobre etiquetas e checklist no modo CWS
        if (infoEtiquetas) infoEtiquetas.style.display = 'block';
        if (checklistPosvenda) checklistPosvenda.style.display = 'block';
        
        // Recolher JSON avançado por padrão no modo CWS
        if (jsonAdvanced && !jsonAdvanced.hasAttribute('open')) {
            jsonAdvanced.removeAttribute('open');
        }
        
        // Remover required dos campos legado (importante: campos ocultos não podem ter required)
        const usuarioLegado = document.getElementById('correios_usuario_legado');
        const senhaLegado = document.getElementById('correios_senha_legado');
        if (usuarioLegado) {
            usuarioLegado.removeAttribute('required');
            usuarioLegado.disabled = true; // Desabilitar também para garantir que não seja enviado
        }
        if (senhaLegado) {
            senhaLegado.removeAttribute('required');
            senhaLegado.disabled = true;
        }
        
        // Adicionar required nos campos CWS
        const usuarioCws = document.getElementById('correios_usuario');
        const codigoAcessoCws = document.getElementById('correios_codigo_acesso_apis');
        const contratoCws = document.getElementById('correios_contrato_cws');
        const cepOrigem = document.getElementById('correios_cep_origem');
        if (usuarioCws) {
            usuarioCws.setAttribute('required', 'required');
            usuarioCws.disabled = false;
        }
        if (codigoAcessoCws) {
            // Só adicionar required se não estiver mascarado
            const codigoSalvo = document.getElementById('codigo_acesso_salvo_indicator') === null;
            if (codigoSalvo) {
                codigoAcessoCws.setAttribute('required', 'required');
            }
            codigoAcessoCws.disabled = false;
        }
        if (contratoCws) {
            contratoCws.setAttribute('required', 'required');
            contratoCws.disabled = false;
            contratoCws.removeAttribute('readonly');
        }
        if (cepOrigem) {
            cepOrigem.setAttribute('required', 'required');
            cepOrigem.disabled = false;
        }
    } else {
        if (camposCws) camposCws.style.display = 'none';
        if (camposLegado) camposLegado.style.display = 'block';
        
        // Mostrar seção de endereço completo no modo Legado
        if (enderecoCompleto) enderecoCompleto.style.display = 'block';
        
        // Ocultar informações sobre etiquetas e checklist no modo Legado
        if (infoEtiquetas) infoEtiquetas.style.display = 'none';
        if (checklistPosvenda) checklistPosvenda.style.display = 'none';
        
        // Remover required dos campos CWS (importante: campos ocultos não podem ter required)
        const usuarioCws = document.getElementById('correios_usuario');
        const codigoAcessoCws = document.getElementById('correios_codigo_acesso_apis');
        const contratoCws = document.getElementById('correios_contrato_cws');
        const cepOrigem = document.getElementById('correios_cep_origem');
        if (usuarioCws) {
            usuarioCws.removeAttribute('required');
            usuarioCws.disabled = true; // Desabilitar também para garantir que não seja enviado
        }
        if (codigoAcessoCws) {
            codigoAcessoCws.removeAttribute('required');
            codigoAcessoCws.disabled = true;
        }
        if (contratoCws) {
            contratoCws.removeAttribute('required');
            contratoCws.disabled = true;
            // Garantir que não seja enviado quando desabilitado
            contratoCws.setAttribute('readonly', 'readonly');
        }
        if (cepOrigem) {
            cepOrigem.removeAttribute('required');
            cepOrigem.disabled = true;
        }
        
        // Adicionar required nos campos legado
        const usuarioLegado = document.getElementById('correios_usuario_legado');
        const senhaLegado = document.getElementById('correios_senha_legado');
        if (usuarioLegado) {
            usuarioLegado.setAttribute('required', 'required');
            usuarioLegado.disabled = false;
        }
        if (senhaLegado) {
            senhaLegado.setAttribute('required', 'required');
            senhaLegado.disabled = false;
        }
    }
}

// Controlar habilitação do JSON override
function toggleJsonOverride() {
    const checkbox = document.getElementById('correios_json_override_enabled');
    const textarea = document.getElementById('shipping_config_json_advanced');
    
    if (checkbox && textarea) {
        if (checkbox.checked) {
            textarea.disabled = false;
            textarea.style.opacity = '1';
        } else {
            textarea.disabled = true;
            textarea.style.opacity = '0.6';
        }
    }
}

// Testar conexão Correios
function testarCorreios() {
    const btn = document.getElementById('btn_testar_correios');
    const resultDiv = document.getElementById('correios_test_result');
    const modoSelect = document.getElementById('correios_modo_integracao');
    
    if (!btn || !resultDiv || !modoSelect) return;
    
    const modo = modoSelect.value;
    
    if (modo !== 'cws') {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #fff3cd; border-radius: 4px; color: #856404;">⚠️ Teste disponível apenas no modo CWS.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    const usuario = document.getElementById('correios_usuario')?.value || '';
    const codigoAcesso = document.getElementById('correios_codigo_acesso_apis')?.value || '';
    const contrato = document.getElementById('correios_contrato_cws')?.value || '';
    const cepOrigem = document.getElementById('correios_cep_origem')?.value.replace(/\D/g, '') || '';
    const cepDestino = document.getElementById('teste_cep_destino')?.value.replace(/\D/g, '') || '';
    const peso = parseFloat(document.getElementById('teste_peso')?.value || '0.3');
    const dimensoesStr = document.getElementById('teste_dimensoes')?.value || '20x20x10';
    
    // Verificar se código está salvo (banner verde presente)
    const codigoSalvo = document.getElementById('codigo_acesso_salvo_indicator') !== null;
    
    // Validar campos
    // Se código está salvo (banner verde), pode estar vazia (backend buscará do banco)
    if (!usuario || usuario.trim() === '') {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Preencha o campo Usuário antes de testar.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    if ((!codigoAcesso || codigoAcesso.trim() === '') && !codigoSalvo) {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Preencha o Código de acesso às APIs antes de testar. Se já salvou anteriormente, o código será usada automaticamente.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    if (!contrato || contrato.trim() === '') {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Preencha o Nº do contrato antes de testar.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    if (!cepOrigem || cepOrigem.length !== 8) {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Preencha o CEP de origem (8 dígitos) antes de testar.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    if (!cepDestino || cepDestino.length !== 8) {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Preencha o CEP de destino (8 dígitos) antes de testar.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    // Parse dimensões
    const dimensoesMatch = dimensoesStr.match(/(\d+)x(\d+)x(\d+)/);
    let comprimento = 20, largura = 20, altura = 10;
    if (dimensoesMatch) {
        comprimento = parseInt(dimensoesMatch[1]) || 20;
        largura = parseInt(dimensoesMatch[2]) || 20;
        altura = parseInt(dimensoesMatch[3]) || 10;
    }
    
    // Verificar serviços habilitados
    const pacHabilitado = document.querySelector('input[name="correios_servico_pac"]')?.checked || false;
    const sedexHabilitado = document.querySelector('input[name="correios_servico_sedex"]')?.checked || false;
    
    if (!pacHabilitado && !sedexHabilitado) {
        resultDiv.innerHTML = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">❌ Habilite pelo menos um serviço (PAC ou SEDEX) antes de testar.</div>';
        resultDiv.style.display = 'block';
        return;
    }
    
    // Desabilitar botão e mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split icon"></i> Testando...';
    resultDiv.innerHTML = '<div style="padding: 1rem; background: #d1ecf1; border-radius: 4px; color: #0c5460;">⏳ Testando conexão com API Correios CWS...</div>';
    resultDiv.style.display = 'block';
    
    // Fazer requisição AJAX
    const formData = new FormData();
    formData.append('usuario', usuario);
    formData.append('codigo_acesso_apis', codigoAcesso);
    formData.append('contrato', contrato);
    formData.append('cep_origem', cepOrigem);
    formData.append('cep_destino', cepDestino);
    formData.append('peso', peso);
    formData.append('comprimento', comprimento);
    formData.append('largura', largura);
    formData.append('altura', altura);
    formData.append('servico_pac', pacHabilitado ? '1' : '0');
    formData.append('servico_sedex', sedexHabilitado ? '1' : '0');
    
    fetch('<?= $basePath ?>/admin/gateways/correios/test', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle icon"></i> Testar Conexão Correios';
        
        // Timestamp do teste
        const agora = new Date();
        const timestamp = agora.toISOString().slice(0, 19).replace('T', ' ');
        
        if (data.success) {
            let html = '<div style="padding: 1rem; background: #d4edda; border-radius: 4px; color: #155724;">';
            html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">';
            html += '<strong>✅ Teste realizado com sucesso!</strong>';
            html += '<button type="button" onclick="copiarResultadoTeste()" style="padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">';
            html += '<i class="bi bi-clipboard icon"></i> Copiar resultado (JSON)';
            html += '</button>';
            html += '</div>';
            
            html += '<small style="color: #666; font-size: 0.75rem;">Último teste em: ' + timestamp + '</small><br><br>';
            
            // Endpoint usado
            if (data.endpoint_usado) {
                html += '<strong>🔗 Endpoint:</strong> ' + data.endpoint_usado + '<br><br>';
            }
            
            // Token
            html += '<strong>🔑 Token:</strong> ';
            if (data.token_ok) {
                html += 'Gerado com sucesso';
                if (data.token_expira_em) {
                    const expiraEm = new Date(data.token_expira_em * 1000);
                    html += ' (expira em ' + expiraEm.toLocaleString('pt-BR') + ')';
                }
            } else {
                html += 'Erro ao gerar';
                if (data.status_http_token) {
                    html += ' (HTTP ' + data.status_http_token + ')';
                }
            }
            html += '<br><br>';
            
            // Opções de frete
            if (data.opcoes && data.opcoes.length > 0) {
                html += '<strong>📦 Opções de Frete:</strong><br>';
                html += '<ul style="margin: 0.5rem 0 0 1.5rem; padding: 0;">';
                data.opcoes.forEach(opcao => {
                    html += '<li>';
                    html += '<strong>' + (opcao.servico === 'PAC' ? 'PAC' : 'SEDEX') + ':</strong> ';
                    if (opcao.preco) {
                        html += 'R$ ' + parseFloat(opcao.preco).toFixed(2);
                    } else {
                        html += 'Preço não disponível';
                    }
                    html += ' | ';
                    if (opcao.prazo) {
                        html += opcao.prazo + ' dias úteis';
                    } else {
                        html += 'Prazo não disponível';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            } else {
                html += '<strong>⚠️ Nenhuma opção de frete retornada.</strong><br>';
            }
            
            // Erros
            if (data.erros && data.erros.length > 0) {
                html += '<br><strong>⚠️ Avisos:</strong><br>';
                html += '<ul style="margin: 0.5rem 0 0 1.5rem; padding: 0; color: #856404;">';
                data.erros.forEach(erro => {
                    html += '<li>' + erro + '</li>';
                });
                html += '</ul>';
            }
            
            html += '</div>';
            
            // Armazenar dados para cópia (sem credenciais)
            window.ultimoResultadoTeste = {
                success: data.success,
                message: data.message,
                token_ok: data.token_ok,
                token_expira_em: data.token_expira_em,
                opcoes: data.opcoes || [],
                erros: data.erros || [],
                timestamp: timestamp
            };
            
            resultDiv.innerHTML = html;
        } else {
            let html = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">';
            html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">';
            html += '<strong>❌ Erro no teste</strong>';
            html += '<button type="button" onclick="copiarResultadoTeste()" style="padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">';
            html += '<i class="bi bi-clipboard icon"></i> Copiar resultado (JSON)';
            html += '</button>';
            html += '</div>';
            html += '<small style="color: #666; font-size: 0.75rem;">Último teste em: ' + timestamp + '</small><br><br>';
            html += '<strong>Erro:</strong> ' + (data.message || 'Erro desconhecido');
            html += '</div>';
            
            // Armazenar dados para cópia (sem credenciais)
            window.ultimoResultadoTeste = {
                success: false,
                message: data.message || 'Erro desconhecido',
                token_ok: false,
                opcoes: [],
                erros: data.erros || [data.message || 'Erro desconhecido'],
                timestamp: timestamp
            };
            
            resultDiv.innerHTML = html;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-play-circle icon"></i> Testar Conexão Correios';
        const agora = new Date();
        const timestamp = agora.toISOString().slice(0, 19).replace('T', ' ');
        
        let html = '<div style="padding: 1rem; background: #f8d7da; border-radius: 4px; color: #721c24;">';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">';
        html += '<strong>❌ Erro de conexão</strong>';
        html += '<button type="button" onclick="copiarResultadoTeste()" style="padding: 0.5rem 1rem; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">';
        html += '<i class="bi bi-clipboard icon"></i> Copiar resultado (JSON)';
        html += '</button>';
        html += '</div>';
        html += '<small style="color: #666; font-size: 0.75rem;">Último teste em: ' + timestamp + '</small><br><br>';
        html += '<strong>Erro:</strong> ' + error.message;
        html += '</div>';
        
        // Armazenar dados para cópia
        window.ultimoResultadoTeste = {
            success: false,
            message: error.message,
            token_ok: false,
            opcoes: [],
            erros: [error.message],
            timestamp: timestamp
        };
        
        resultDiv.innerHTML = html;
    });
}

// Copiar resultado do teste (sem credenciais)
function copiarResultadoTeste() {
    if (!window.ultimoResultadoTeste) {
        alert('Nenhum resultado de teste disponível para copiar.');
        return;
    }
    
    const resultado = JSON.stringify(window.ultimoResultadoTeste, null, 2);
    
    // Copiar para clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(resultado).then(() => {
            alert('Resultado copiado para a área de transferência!');
        }).catch(err => {
            console.error('Erro ao copiar:', err);
            fallbackCopyTextToClipboard(resultado);
        });
    } else {
        fallbackCopyTextToClipboard(resultado);
    }
}

// Fallback para navegadores antigos
function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            alert('Resultado copiado para a área de transferência!');
        } else {
            alert('Não foi possível copiar. Selecione o texto manualmente.');
        }
    } catch (err) {
        console.error('Erro ao copiar:', err);
        alert('Não foi possível copiar. Selecione o texto manualmente.');
    }
    
    document.body.removeChild(textArea);
}

// Validar CEP de origem
function validarCepOrigem(input) {
    const cep = input.value.replace(/\D/g, '');
    if (cep === '00000000') {
        input.setCustomValidity('Informe o CEP de origem válido (8 dígitos).');
        input.reportValidity();
    } else {
        input.setCustomValidity('');
    }
}

// Preparar formulário antes de submeter (garantir que campos necessários estejam habilitados)
function prepararSubmitFormulario() {
    const modoSelect = document.getElementById('correios_modo_integracao');
    const shippingGateway = document.getElementById('shipping_gateway_code');
    
    if (shippingGateway && shippingGateway.value === 'correios' && modoSelect) {
        const modo = modoSelect.value;
        
        if (modo === 'cws') {
            // Garantir que campos CWS estejam habilitados
            const contratoCws = document.getElementById('correios_contrato_cws');
            if (contratoCws) {
                contratoCws.disabled = false;
                contratoCws.removeAttribute('readonly');
            }
        }
    }
    
    return true; // Permitir submit
}
</script>


