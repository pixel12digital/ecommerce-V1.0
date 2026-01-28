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
                    <div class="form-group" style="margin-top: 1rem; padding: 1rem; background: #f0f7ff; border-radius: 6px; border: 1px solid var(--pg-color-primary);">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600; color: var(--pg-color-primary);">
                            <input type="checkbox" name="criar_conta" id="criar_conta" value="1" 
                                   <?= !empty($formData['criar_conta']) ? 'checked' : '' ?> 
                                   onchange="togglePasswordField()">
                            <span>Criar uma conta para acompanhar seus pedidos</span>
                        </label>
                        <div id="passwordField" style="margin-top: 1rem; display: none;">
                            <label for="senha_conta" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Senha *</label>
                            <input type="password" name="senha_conta" id="senha_conta" 
                                   placeholder="Mínimo de 6 caracteres" minlength="6"
                                   value="<?= htmlspecialchars($formData['senha_conta'] ?? '') ?>">
                            <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">Mínimo de 6 caracteres</small>
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
                <?php if (!empty($freteErro)): ?>
                    <div class="shipping-error" style="padding: 1rem; background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; margin-bottom: 1rem; color: #856404;">
                        <i class="bi bi-exclamation-triangle" style="margin-right: 0.5rem;"></i>
                        <?= htmlspecialchars($freteErro) ?>
                    </div>
                <?php endif; ?>
                <div class="shipping-options">
                    <?php if (empty($opcoesFrete)): ?>
                        <?php if (empty($freteErro)): ?>
                            <p style="color: #666; font-size: 0.9rem; padding: 1rem; text-align: center;">
                                Informe o CEP de entrega para calcular o frete.
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php foreach ($opcoesFrete as $opcao): ?>
                            <label class="option-card" onclick="selectShipping(this)">
                                <input type="radio" name="metodo_frete" value="<?= htmlspecialchars($opcao['codigo']) ?>" required>
                                <div>
                                    <div class="option-title"><?= htmlspecialchars($opcao['titulo']) ?></div>
                                    <div class="option-desc">
                                        R$ <?= number_format($opcao['valor'], 2, ',', '.') ?> - <?= htmlspecialchars($opcao['prazo']) ?>
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
        function selectShipping(element) {
            document.querySelectorAll(\'.shipping-options .option-card\').forEach(card => {
                card.classList.remove(\'selected\');
            });
            element.classList.add(\'selected\');
            updateSummary();
        }
        
        function selectPayment(element) {
            document.querySelectorAll(\'.payment-options .option-card\').forEach(card => {
                card.classList.remove(\'selected\');
            });
            element.classList.add(\'selected\');
        }
        
        function updateSummary() {
            const freteSelected = document.querySelector(\'input[name="metodo_frete"]:checked\');
            if (freteSelected) {
                const opcoes = ' . json_encode($opcoesFrete) . ';
                const opcao = opcoes.find(o => o.codigo === freteSelected.value);
                if (opcao) {
                    const subtotal = ' . $subtotal . ';
                    const frete = parseFloat(opcao.valor);
                    const total = subtotal + frete;
                    
                    document.getElementById(\'freteSummary\').innerHTML = `
                        <span>Frete:</span>
                        <span>R$ ${opcao.valor.toFixed(2).replace(\'.\', \',\')}</span>
                    `;
                    document.getElementById(\'totalSummary\').innerHTML = `
                        <span>Total:</span>
                        <span>R$ ${total.toFixed(2).replace(\'.\', \',\')}</span>
                    `;
                }
            }
        }
        
        // Atualizar resumo quando frete for selecionado
        document.querySelectorAll(\'input[name="metodo_frete"]\').forEach(radio => {
            radio.addEventListener(\'change\', updateSummary);
        });
        
        // Mostrar/ocultar campo de senha baseado no checkbox
        function togglePasswordField() {
            const checkbox = document.getElementById(\'criar_conta\');
            const passwordField = document.getElementById(\'passwordField\');
            const passwordInput = document.getElementById(\'senha_conta\');
            
            if (checkbox && passwordField && passwordInput) {
                if (checkbox.checked) {
                    passwordField.style.display = \'block\';
                    passwordInput.required = true;
                } else {
                    passwordField.style.display = \'none\';
                    passwordInput.required = false;
                    passwordInput.value = \'\';
                }
            }
        }
        
        // Inicializar estado do campo de senha ao carregar
        document.addEventListener(\'DOMContentLoaded\', function() {
            togglePasswordField();
            
            // Carregar CEP do localStorage se não estiver preenchido
            const cepInput = document.getElementById(\'entrega_cep\');
            if (cepInput && !cepInput.value) {
                const savedCEP = localStorage.getItem(\'cart_shipping_cep\');
                if (savedCEP) {
                    cepInput.value = savedCEP;
                }
            }
        });
    </script>
';

// Configurar variáveis para o layout base
$pageTitle = 'Checkout – ' . htmlspecialchars($loja['nome']);
$showCategoryStrip = false;
$showNewsletter = false;

// Incluir o layout base
include __DIR__ . '/../layouts/base.php';
