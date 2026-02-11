<?php
// Base path
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

// Carregar dados necessários para o layout base
if (empty($loja) || empty($loja['nome'])) {
    $tenant = \App\Tenant\TenantContext::tenant();
    $loja = ['nome' => $tenant['nome'] ?? 'Loja'];
}

// Carregar menu_main se não estiver definido
if (empty($theme['menu_main'])) {
    $theme['menu_main'] = \App\Services\ThemeConfig::getMainMenu();
}

// Carregar configurações adicionais do tema se necessário
if (empty($theme['topbar_text'])) {
    $theme['topbar_text'] = \App\Services\ThemeConfig::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe');
}
if (empty($theme['newsletter_title'])) {
    $theme['newsletter_title'] = \App\Services\ThemeConfig::get('newsletter_title', 'Receba nossas ofertas');
}
if (empty($theme['newsletter_subtitle'])) {
    $theme['newsletter_subtitle'] = \App\Services\ThemeConfig::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas');
}
if (empty($theme['footer_phone'])) {
    $theme['footer_phone'] = \App\Services\ThemeConfig::get('footer_phone', '');
}
if (empty($theme['footer_whatsapp'])) {
    $theme['footer_whatsapp'] = \App\Services\ThemeConfig::get('footer_whatsapp', '');
}
if (empty($theme['footer_email'])) {
    $theme['footer_email'] = \App\Services\ThemeConfig::get('footer_email', '');
}
if (empty($theme['footer_address'])) {
    $theme['footer_address'] = \App\Services\ThemeConfig::get('footer_address', '');
}
if (!isset($theme['footer_cnpj']) || $theme['footer_cnpj'] === '') {
    $theme['footer_cnpj'] = \App\Services\ThemeConfig::get('footer_cnpj', '');
}
if (empty($theme['footer_social_instagram'])) {
    $theme['footer_social_instagram'] = \App\Services\ThemeConfig::get('footer_social_instagram', '');
}
if (empty($theme['footer_social_facebook'])) {
    $theme['footer_social_facebook'] = \App\Services\ThemeConfig::get('footer_social_facebook', '');
}
if (empty($theme['footer_social_youtube'])) {
    $theme['footer_social_youtube'] = \App\Services\ThemeConfig::get('footer_social_youtube', '');
}

// Capturar conteúdo principal em $content
ob_start();
?>

<div class="checkout-header-banner">
    <div class="checkout-container">
        <h1>Checkout</h1>
        <a href="<?= $basePath ?>/carrinho"><i class="bi bi-arrow-left icon"></i> Voltar ao Carrinho</a>
    </div>
</div>

<div class="checkout-container">
    <form method="POST" action="<?= $basePath ?>/checkout" id="checkoutForm">
        <div>
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <strong>Erro ao processar:</strong>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Dados do Cliente -->
            <div class="form-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                    <h3 class="section-title" style="margin: 0;">Dados do Cliente</h3>
                    <?php if (!$customer): ?>
                        <a href="<?= $basePath ?>/minha-conta/login?redirect=<?= urlencode($basePath . '/checkout') ?>" 
                           style="color: var(--pg-color-primary); text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                            <i class="bi bi-person icon"></i> Já tem cadastro? Faça login
                        </a>
                    <?php else: ?>
                        <span style="color: var(--pg-color-primary); font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; font-weight: 500;">
                            <i class="bi bi-check-circle icon"></i> Logado como <?= htmlspecialchars($customer['name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label>Nome Completo *</label>
                    <input type="text" name="cliente_nome" 
                           value="<?= htmlspecialchars($formData['cliente_nome'] ?? ($customer['name'] ?? '')) ?>" 
                           required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>E-mail *</label>
                        <input type="email" name="cliente_email" 
                               value="<?= htmlspecialchars($formData['cliente_email'] ?? ($customer['email'] ?? '')) ?>" 
                               required>
                    </div>
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" name="cliente_telefone" 
                               value="<?= htmlspecialchars($formData['cliente_telefone'] ?? ($customer['phone'] ?? '')) ?>" 
                               placeholder="(00) 00000-0000">
                    </div>
                </div>
                <?php if (!$customer): ?>
                    <div style="margin-top: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem; color: #555;">
                            <input type="checkbox" name="criar_conta" id="criar_conta" value="1" 
                                   <?= !empty($formData['criar_conta']) ? 'checked' : '' ?> 
                                   onchange="togglePasswordField()"
                                   style="width: 16px; height: 16px; accent-color: var(--pg-color-primary);">
                            <span>Criar conta <span style="color: #999; font-weight: 400;">(opcional)</span></span>
                        </label>
                        <small style="color: #999; font-size: 0.8rem; margin-left: 1.6rem; display: block; margin-top: 2px;">Acompanhe pedidos e compre mais rápido.</small>
                        <div id="passwordField" style="margin-top: 0.75rem; display: none;">
                            <label for="senha_conta" style="display: block; margin-bottom: 0.35rem; font-weight: 500; color: #555; font-size: 0.9rem;">Senha *</label>
                            <input type="password" name="senha_conta" id="senha_conta" 
                                   placeholder="Mínimo de 6 caracteres" minlength="6"
                                   value="<?= htmlspecialchars($formData['senha_conta'] ?? '') ?>">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Endereço de Entrega -->
            <div class="form-section">
                <h3 class="section-title">Endereço de Entrega</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="entrega_cep">CEP *</label>
                        <input type="text" id="entrega_cep" name="entrega_cep" value="<?= htmlspecialchars($formData['entrega_cep'] ?? ($_GET['cep'] ?? '')) ?>" 
                               placeholder="00000-000" required maxlength="9" aria-label="CEP">
                    </div>
                    <div class="form-group">
                        <label for="entrega_estado">Estado *</label>
                        <select id="entrega_estado" name="entrega_estado" required>
                            <option value="">Selecione</option>
                            <option value="AC" <?= ($formData['entrega_estado'] ?? '') === 'AC' ? 'selected' : '' ?>>Acre</option>
                            <option value="AL" <?= ($formData['entrega_estado'] ?? '') === 'AL' ? 'selected' : '' ?>>Alagoas</option>
                            <option value="AP" <?= ($formData['entrega_estado'] ?? '') === 'AP' ? 'selected' : '' ?>>Amapá</option>
                            <option value="AM" <?= ($formData['entrega_estado'] ?? '') === 'AM' ? 'selected' : '' ?>>Amazonas</option>
                            <option value="BA" <?= ($formData['entrega_estado'] ?? '') === 'BA' ? 'selected' : '' ?>>Bahia</option>
                            <option value="CE" <?= ($formData['entrega_estado'] ?? '') === 'CE' ? 'selected' : '' ?>>Ceará</option>
                            <option value="DF" <?= ($formData['entrega_estado'] ?? '') === 'DF' ? 'selected' : '' ?>>Distrito Federal</option>
                            <option value="ES" <?= ($formData['entrega_estado'] ?? '') === 'ES' ? 'selected' : '' ?>>Espírito Santo</option>
                            <option value="GO" <?= ($formData['entrega_estado'] ?? '') === 'GO' ? 'selected' : '' ?>>Goiás</option>
                            <option value="MA" <?= ($formData['entrega_estado'] ?? '') === 'MA' ? 'selected' : '' ?>>Maranhão</option>
                            <option value="MT" <?= ($formData['entrega_estado'] ?? '') === 'MT' ? 'selected' : '' ?>>Mato Grosso</option>
                            <option value="MS" <?= ($formData['entrega_estado'] ?? '') === 'MS' ? 'selected' : '' ?>>Mato Grosso do Sul</option>
                            <option value="MG" <?= ($formData['entrega_estado'] ?? '') === 'MG' ? 'selected' : '' ?>>Minas Gerais</option>
                            <option value="PA" <?= ($formData['entrega_estado'] ?? '') === 'PA' ? 'selected' : '' ?>>Pará</option>
                            <option value="PB" <?= ($formData['entrega_estado'] ?? '') === 'PB' ? 'selected' : '' ?>>Paraíba</option>
                            <option value="PR" <?= ($formData['entrega_estado'] ?? '') === 'PR' ? 'selected' : '' ?>>Paraná</option>
                            <option value="PE" <?= ($formData['entrega_estado'] ?? '') === 'PE' ? 'selected' : '' ?>>Pernambuco</option>
                            <option value="PI" <?= ($formData['entrega_estado'] ?? '') === 'PI' ? 'selected' : '' ?>>Piauí</option>
                            <option value="RJ" <?= ($formData['entrega_estado'] ?? '') === 'RJ' ? 'selected' : '' ?>>Rio de Janeiro</option>
                            <option value="RN" <?= ($formData['entrega_estado'] ?? '') === 'RN' ? 'selected' : '' ?>>Rio Grande do Norte</option>
                            <option value="RS" <?= ($formData['entrega_estado'] ?? '') === 'RS' ? 'selected' : '' ?>>Rio Grande do Sul</option>
                            <option value="RO" <?= ($formData['entrega_estado'] ?? '') === 'RO' ? 'selected' : '' ?>>Rondônia</option>
                            <option value="RR" <?= ($formData['entrega_estado'] ?? '') === 'RR' ? 'selected' : '' ?>>Roraima</option>
                            <option value="SC" <?= ($formData['entrega_estado'] ?? '') === 'SC' ? 'selected' : '' ?>>Santa Catarina</option>
                            <option value="SP" <?= ($formData['entrega_estado'] ?? '') === 'SP' ? 'selected' : '' ?>>São Paulo</option>
                            <option value="SE" <?= ($formData['entrega_estado'] ?? '') === 'SE' ? 'selected' : '' ?>>Sergipe</option>
                            <option value="TO" <?= ($formData['entrega_estado'] ?? '') === 'TO' ? 'selected' : '' ?>>Tocantins</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Logradouro *</label>
                    <input type="text" name="entrega_logradouro" value="<?= htmlspecialchars($formData['entrega_logradouro'] ?? '') ?>" required>
                </div>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Número *</label>
                        <input type="text" name="entrega_numero" value="<?= htmlspecialchars($formData['entrega_numero'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Complemento</label>
                        <input type="text" name="entrega_complemento" value="<?= htmlspecialchars($formData['entrega_complemento'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Bairro *</label>
                        <input type="text" name="entrega_bairro" value="<?= htmlspecialchars($formData['entrega_bairro'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Cidade *</label>
                    <input type="text" name="entrega_cidade" value="<?= htmlspecialchars($formData['entrega_cidade'] ?? '') ?>" required>
                </div>
            </div>
            
            <!-- Frete - Fase 10 -->
            <div class="form-section">
                <h3 class="section-title">Opções de Frete</h3>
                <div id="checkout-shipping-error" style="padding: 1rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 1rem; color: #856404; display: <?= !empty($freteErro) ? 'block' : 'none' ?>;">
                    <i class="bi bi-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                    <span id="checkout-shipping-error-text"><?= !empty($freteErro) ? htmlspecialchars($freteErro) : '' ?></span>
                </div>
                <div id="checkout-shipping-loading" style="display: none; padding: 1rem; text-align: center; color: #666;">
                    <i class="bi bi-hourglass-split"></i> Calculando frete...
                </div>
                <div id="checkout-shipping-options" class="shipping-options">
                    <?php if (empty($opcoesFrete)): ?>
                        <?php if (empty($freteErro)): ?>
                            <div id="checkout-shipping-placeholder" style="padding: 1.5rem; text-align: center;">
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 0.75rem;">
                                    Preencha o CEP no endereço de entrega acima para calcular o frete.
                                </p>
                                <button type="button" onclick="document.getElementById('entrega_cep').scrollIntoView({behavior:'smooth',block:'center'});setTimeout(function(){document.getElementById('entrega_cep').focus();},400);" style="background: var(--pg-color-primary); color: #fff; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; cursor: pointer; font-size: 0.9rem;">
                                    <i class="bi bi-geo-alt" style="margin-right: 0.3rem;"></i> Preencher CEP
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($opcoesFrete as $opcao): ?>
                            <label class="option-card" onclick="selectShipping(this)">
                                <input type="radio" name="metodo_frete" value="<?= htmlspecialchars($opcao['codigo']) ?>" required>
                                <div>
                                    <div class="option-title"><?= htmlspecialchars($opcao['titulo']) ?></div>
                                    <div class="option-desc">
                                        R$ <?= number_format($opcao['valor'], 2, ',', '.') ?> - <?php
                                            $prazo = $opcao['prazo'];
                                            if (is_numeric($prazo)) {
                                                echo (int)$prazo === 1 ? '1 dia útil' : (int)$prazo . ' dias úteis';
                                            } else {
                                                echo htmlspecialchars($prazo);
                                            }
                                        ?>
                                        <?php if (!empty($opcao['descricao'])): ?>
                                            <br><?= htmlspecialchars($opcao['descricao']) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Pagamento - Fase 10 -->
            <div class="form-section">
                <h3 class="section-title">Forma de Pagamento</h3>
                <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                    Após finalizar o pedido, você receberá as instruções de pagamento por e-mail.
                </p>
                <div class="payment-options">
                    <?php foreach ($metodosPagamento as $metodo): ?>
                        <label class="option-card" onclick="selectPayment(this)">
                            <input type="radio" name="metodo_pagamento" value="<?= htmlspecialchars($metodo['codigo']) ?>" required>
                            <div>
                                <div class="option-title"><?= htmlspecialchars($metodo['titulo']) ?></div>
                                <div class="option-desc"><?= htmlspecialchars($metodo['descricao']) ?></div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                
                <!-- Campos do Cartão de Crédito (visíveis apenas quando cartão é selecionado) -->
                <div id="credit-card-fields" style="display: none; margin-top: 1.25rem; padding: 1.25rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: #333;">Dados do Cartão</h4>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="card_number">Número do Cartão *</label>
                        <input type="text" id="card_number" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" autocomplete="cc-number" style="font-family: monospace; letter-spacing: 1px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label for="card_holder">Nome no Cartão *</label>
                        <input type="text" id="card_holder" name="card_holder" placeholder="NOME COMO ESTÁ NO CARTÃO" autocomplete="cc-name" style="text-transform: uppercase;">
                    </div>
                    <div class="form-row" style="margin-bottom: 1rem;">
                        <div class="form-group">
                            <label for="card_expiry">Validade *</label>
                            <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/AAAA" maxlength="7" autocomplete="cc-exp">
                        </div>
                        <div class="form-group">
                            <label for="card_cvv">CVV *</label>
                            <input type="text" id="card_cvv" name="card_cvv" placeholder="000" maxlength="4" autocomplete="cc-csc" style="font-family: monospace;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="card_installments">Parcelas</label>
                        <select id="card_installments" name="card_installments">
                            <option value="1">1x sem juros</option>
                            <option value="2">2x sem juros</option>
                            <option value="3">3x sem juros</option>
                        </select>
                    </div>
                    <p style="margin: 0.75rem 0 0 0; font-size: 0.8rem; color: #888;">
                        <i class="bi bi-lock" style="margin-right: 0.25rem;"></i> Pagamento seguro processado pela Cielo.
                    </p>
                </div>
            </div>
            
            <!-- Observações - Fase 10 -->
            <div class="form-section">
                <h3 class="section-title">Observações (Opcional)</h3>
                <div class="form-group">
                    <label for="observacoes">Alguma observação sobre o pedido?</label>
                    <textarea id="observacoes" name="observacoes" rows="4" 
                              placeholder="Ex: Horário preferencial de entrega, instruções especiais, etc."><?= htmlspecialchars($formData['observacoes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Resumo -->
        <div class="summary">
            <h3 class="section-title">Resumo do Pedido</h3>
            
            <div style="max-height: 300px; overflow-y: auto; margin-bottom: 1rem;">
                <?php foreach ($cart['items'] as $item): ?>
                    <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                        <span style="font-size: 0.9rem;">
                            <?= htmlspecialchars($item['nome']) ?> × <?= $item['quantidade'] ?>
                        </span>
                        <span style="font-size: 0.9rem;">
                            R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="summary-item">
                <span>Subtotal:</span>
                <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
            </div>
            <div class="summary-item" id="freteSummary">
                <span>Frete:</span>
                <span>Selecione um frete</span>
            </div>
            <div class="summary-item total" id="totalSummary">
                <span>Total:</span>
                <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="bi bi-check-circle icon"></i>
                Finalizar Pedido
            </button>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: #666; text-align: center; line-height: 1.5;">
                Ao finalizar, você receberá um e-mail com as instruções de pagamento e o número do seu pedido.
            </p>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();

// CSS específico da página de checkout
$additionalStyles = '
    body {
        background: #f5f5f5;
    }
    
    .checkout-header-banner {
        background: var(--pg-color-primary);
        color: white;
        padding: 1rem 2rem;
        margin-bottom: 2rem;
    }
    .checkout-header-banner .checkout-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .checkout-header-banner h1 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    .checkout-header-banner a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .checkout-header-banner a:hover {
        text-decoration: underline;
    }
    
    .checkout-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }
    .form-section {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .section-title {
        font-size: 1.375rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #333;
        border-bottom: 2px solid var(--pg-color-primary);
        padding-bottom: 0.75rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #555;
        font-size: 0.95rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.875rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--pg-color-primary);
        box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
    }
    .form-group input::placeholder,
    .form-group textarea::placeholder {
        color: #999;
    }
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    .form-row-3 {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 1rem;
    }
    .shipping-options,
    .payment-options {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .option-card {
        border: 2px solid #ddd;
        border-radius: 4px;
        padding: 1rem;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .option-card:hover {
        border-color: var(--pg-color-primary);
    }
    .option-card.selected {
        border-color: var(--pg-color-primary);
        background: #f0f7ff;
    }
    .option-card input[type="radio"] {
        margin-right: 0.5rem;
    }
    .option-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .option-desc {
        font-size: 0.9rem;
        color: #666;
    }
    .summary {
        background: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: sticky;
        top: 2rem;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #eee;
    }
    .summary-item.total {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--pg-color-primary);
        border-bottom: none;
        margin-top: 0.5rem;
    }
    .btn-submit {
        width: 100%;
        padding: 1.125rem;
        background: var(--pg-color-secondary);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 1.125rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .btn-submit:hover {
        background: #e6851a;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .btn-submit:active {
        transform: translateY(0);
    }
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
    }
    .error-list {
        list-style: none;
        margin-top: 0.5rem;
    }
    
    /* Responsivo */
    @media (max-width: 768px) {
        .checkout-header-banner {
            padding: 1rem 1.5rem;
        }
        .checkout-container {
            grid-template-columns: 1fr;
            margin: 1.5rem auto;
            padding: 0 1rem;
        }
        .form-section {
            padding: 1.5rem;
        }
        .form-row,
        .form-row-3 {
            grid-template-columns: 1fr;
        }
        .summary {
            position: static;
            margin-top: 2rem;
        }
        .section-title {
            font-size: 1.25rem;
        }
    }
';

// Scripts adicionais
$additionalScripts = '
    <script>
        (function() {
            var subtotalValue = ' . $subtotal . ';
            var currentShippingOptions = ' . json_encode($opcoesFrete) . ';
            var lastCalculatedCep = "' . htmlspecialchars($cep ?? '') . '";
            var calculatingFrete = false;
            
            function selectShipping(element) {
                document.querySelectorAll(".shipping-options .option-card").forEach(function(card) {
                    card.classList.remove("selected");
                });
                element.classList.add("selected");
                updateSummaryFromSelection();
            }
            window.selectShipping = selectShipping;
            
            function selectPayment(element) {
                document.querySelectorAll(".payment-options .option-card").forEach(function(card) {
                    card.classList.remove("selected");
                });
                element.classList.add("selected");
                
                // Mostrar/ocultar campos de cartão
                var radio = element.querySelector("input[type=radio]");
                var ccFields = document.getElementById("credit-card-fields");
                if (ccFields) {
                    var isCreditCard = radio && radio.value === "cielo_credit_card";
                    ccFields.style.display = isCreditCard ? "block" : "none";
                    // Tornar campos obrigatórios apenas quando visíveis
                    var ccInputs = ccFields.querySelectorAll("input[type=text]");
                    ccInputs.forEach(function(inp) {
                        inp.required = isCreditCard;
                    });
                }
            }
            window.selectPayment = selectPayment;
            
            function updateSummaryFromSelection() {
                var freteSelected = document.querySelector(\'input[name="metodo_frete"]:checked\');
                if (freteSelected && currentShippingOptions && currentShippingOptions.length > 0) {
                    var opcao = null;
                    for (var i = 0; i < currentShippingOptions.length; i++) {
                        if (currentShippingOptions[i].codigo === freteSelected.value) {
                            opcao = currentShippingOptions[i];
                            break;
                        }
                    }
                    if (opcao) {
                        var frete = parseFloat(opcao.valor);
                        var total = subtotalValue + frete;
                        document.getElementById("freteSummary").innerHTML = "<span>Frete:</span><span>R$ " + formatMoney(frete) + "</span>";
                        document.getElementById("totalSummary").innerHTML = "<span>Total:</span><span>R$ " + formatMoney(total) + "</span>";
                    }
                }
            }
            
            function formatMoney(value) {
                return parseFloat(value).toFixed(2).replace(".", ",");
            }
            
            function escapeHtml(text) {
                var div = document.createElement("div");
                div.textContent = text;
                return div.innerHTML;
            }
            
            function formatPrazo(prazo) {
                if (!prazo && prazo !== 0) return "A consultar";
                var num = parseInt(prazo, 10);
                if (!isNaN(num)) {
                    return num === 1 ? "1 dia útil" : num + " dias úteis";
                }
                return String(prazo);
            }
            
            // Auto-preencher endereço via ViaCEP
            function buscarEnderecoPorCep(cep) {
                cep = cep.replace(/\D/g, "");
                if (cep.length !== 8) return;
                fetch("https://viacep.com.br/ws/" + cep + "/json/")
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.erro) return;
                    if (data.logradouro) {
                        var logradouro = document.querySelector("input[name=entrega_logradouro]");
                        if (logradouro && !logradouro.value) logradouro.value = data.logradouro;
                    }
                    if (data.bairro) {
                        var bairro = document.querySelector("input[name=entrega_bairro]");
                        if (bairro && !bairro.value) bairro.value = data.bairro;
                    }
                    if (data.localidade) {
                        var cidade = document.querySelector("input[name=entrega_cidade]");
                        if (cidade) cidade.value = data.localidade;
                    }
                    if (data.uf) {
                        var estado = document.getElementById("entrega_estado");
                        if (estado) estado.value = data.uf;
                    }
                })
                .catch(function() {});
            }
            
            // Calcular frete via AJAX
            function calcularFreteCheckout(cep) {
                cep = cep.replace(/\D/g, "");
                if (cep.length !== 8 || calculatingFrete) return;
                if (cep === lastCalculatedCep && currentShippingOptions && currentShippingOptions.length > 0) return;
                
                calculatingFrete = true;
                lastCalculatedCep = cep;
                
                var loadingDiv = document.getElementById("checkout-shipping-loading");
                var optionsDiv = document.getElementById("checkout-shipping-options");
                var errorDiv = document.getElementById("checkout-shipping-error");
                var placeholder = document.getElementById("checkout-shipping-placeholder");
                
                if (loadingDiv) loadingDiv.style.display = "block";
                if (optionsDiv) optionsDiv.style.display = "none";
                if (errorDiv) errorDiv.style.display = "none";
                
                fetch("/api/shipping/calculate", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ cepDestino: cep })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    calculatingFrete = false;
                    if (loadingDiv) loadingDiv.style.display = "none";
                    
                    if (data.success && data.opcoes && data.opcoes.length > 0) {
                        // Converter formato da API para formato do template
                        currentShippingOptions = [];
                        var html = "";
                        data.opcoes.forEach(function(opcao) {
                            currentShippingOptions.push({
                                codigo: opcao.codigo || opcao.codigo_servico,
                                titulo: opcao.servico,
                                valor: opcao.preco,
                                prazo: opcao.prazo
                            });
                            var prazoText = formatPrazo(opcao.prazo);
                            html += \'<label class="option-card" onclick="selectShipping(this)">\'
                                + \'<input type="radio" name="metodo_frete" value="\' + escapeHtml(opcao.codigo || opcao.codigo_servico) + \'" required>\'
                                + \'<div>\'
                                + \'<div class="option-title">\' + escapeHtml(opcao.servico) + \'</div>\'
                                + \'<div class="option-desc">R$ \' + formatMoney(opcao.preco) + \' - \' + escapeHtml(prazoText) + \'</div>\'
                                + \'</div>\'
                                + \'</label>\';
                        });
                        if (optionsDiv) {
                            optionsDiv.innerHTML = html;
                            optionsDiv.style.display = "flex";
                        }
                        // Reset resumo
                        document.getElementById("freteSummary").innerHTML = "<span>Frete:</span><span>Selecione um frete</span>";
                        document.getElementById("totalSummary").innerHTML = "<span>Total:</span><span>R$ " + formatMoney(subtotalValue) + "</span>";
                    } else {
                        var errorText = data.message || "Não foi possível calcular o frete. Verifique o CEP.";
                        if (errorDiv) {
                            document.getElementById("checkout-shipping-error-text").textContent = errorText;
                            errorDiv.style.display = "block";
                        }
                        if (optionsDiv) {
                            optionsDiv.innerHTML = "";
                            optionsDiv.style.display = "none";
                        }
                        currentShippingOptions = [];
                    }
                })
                .catch(function(err) {
                    calculatingFrete = false;
                    if (loadingDiv) loadingDiv.style.display = "none";
                    if (errorDiv) {
                        document.getElementById("checkout-shipping-error-text").textContent = "Erro ao calcular frete. Tente novamente.";
                        errorDiv.style.display = "block";
                    }
                    console.error("Erro frete:", err);
                });
            }
            
            // Mostrar/ocultar campo de senha
            function togglePasswordField() {
                var checkbox = document.getElementById("criar_conta");
                var passwordField = document.getElementById("passwordField");
                var passwordInput = document.getElementById("senha_conta");
                if (checkbox && passwordField && passwordInput) {
                    if (checkbox.checked) {
                        passwordField.style.display = "block";
                        passwordInput.required = true;
                    } else {
                        passwordField.style.display = "none";
                        passwordInput.required = false;
                        passwordInput.value = "";
                    }
                }
            }
            window.togglePasswordField = togglePasswordField;
            
            // Interceptar submit do form
            document.addEventListener("DOMContentLoaded", function() {
                togglePasswordField();
                
                var cepInput = document.getElementById("entrega_cep");
                
                // Carregar CEP do localStorage se vazio
                if (cepInput && !cepInput.value) {
                    var savedCEP = localStorage.getItem("cart_shipping_cep");
                    if (savedCEP) {
                        cepInput.value = savedCEP;
                        // Calcular frete automaticamente com o CEP salvo
                        calcularFreteCheckout(savedCEP);
                    }
                }
                
                // Calcular frete quando CEP mudar (ao sair do campo ou ao completar 9 chars com máscara)
                if (cepInput) {
                    var cepTimer = null;
                    cepInput.addEventListener("input", function() {
                        var cleanCep = this.value.replace(/\D/g, "");
                        if (cleanCep.length === 8) {
                            clearTimeout(cepTimer);
                            cepTimer = setTimeout(function() {
                                calcularFreteCheckout(cleanCep);
                                buscarEnderecoPorCep(cleanCep);
                            }, 500);
                        }
                    });
                    cepInput.addEventListener("blur", function() {
                        var cleanCep = this.value.replace(/\D/g, "");
                        if (cleanCep.length === 8) {
                            calcularFreteCheckout(cleanCep);
                        }
                    });
                    
                    // Se já tem CEP preenchido (veio do carrinho), calcular e buscar endereço
                    if (cepInput.value && cepInput.value.replace(/\D/g, "").length === 8) {
                        buscarEnderecoPorCep(cepInput.value);
                        if (!currentShippingOptions || currentShippingOptions.length === 0) {
                            calcularFreteCheckout(cepInput.value);
                        }
                    }
                }
                
                // Máscara do número do cartão (0000 0000 0000 0000)
                var cardNumberInput = document.getElementById("card_number");
                if (cardNumberInput) {
                    cardNumberInput.addEventListener("input", function() {
                        var v = this.value.replace(/\D/g, "").substring(0, 16);
                        var formatted = v.replace(/(.{4})/g, "$1 ").trim();
                        this.value = formatted;
                    });
                }
                
                // Máscara da validade (MM/AAAA)
                var cardExpiryInput = document.getElementById("card_expiry");
                if (cardExpiryInput) {
                    cardExpiryInput.addEventListener("input", function() {
                        var v = this.value.replace(/\D/g, "").substring(0, 6);
                        if (v.length >= 3) {
                            this.value = v.substring(0, 2) + "/" + v.substring(2);
                        } else {
                            this.value = v;
                        }
                    });
                }
                
                // Máscara do CVV (apenas números)
                var cardCvvInput = document.getElementById("card_cvv");
                if (cardCvvInput) {
                    cardCvvInput.addEventListener("input", function() {
                        this.value = this.value.replace(/\D/g, "").substring(0, 4);
                    });
                }
                
                // Interceptar submit: exigir seleção de frete
                var checkoutForm = document.querySelector("form");
                if (checkoutForm) {
                    checkoutForm.addEventListener("submit", function(e) {
                        var freteSelected = document.querySelector(\'input[name="metodo_frete"]:checked\');
                        if (!freteSelected) {
                            e.preventDefault();
                            // Scroll até a seção de frete
                            var freteSection = document.getElementById("checkout-shipping-options");
                            if (freteSection) {
                                freteSection.scrollIntoView({ behavior: "smooth", block: "center" });
                            }
                            // Mostrar alerta
                            var errorDiv = document.getElementById("checkout-shipping-error");
                            var errorText = document.getElementById("checkout-shipping-error-text");
                            if (errorDiv && errorText) {
                                // Verificar se tem opções mas não selecionou
                                var hasOptions = document.querySelectorAll(\'input[name="metodo_frete"]\').length > 0;
                                if (hasOptions) {
                                    errorText.textContent = "Selecione uma opção de frete antes de finalizar.";
                                } else {
                                    errorText.textContent = "Informe o CEP de entrega e aguarde o cálculo do frete.";
                                }
                                errorDiv.style.display = "block";
                            }
                            return false;
                        }
                    });
                }
            });
        })();
    </script>
';

// Configurar variáveis para o layout base
$pageTitle = 'Checkout – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
