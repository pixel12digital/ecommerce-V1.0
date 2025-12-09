<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="theme-page">
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">
            Tema salvo com sucesso!
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/admin/tema" class="admin-form" enctype="multipart/form-data">
        <!-- Seção Cores - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Cores do Tema</h3>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="color_primary">Cor Primária</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_primary" name="theme_color_primary" value="<?= htmlspecialchars($config['theme_color_primary']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_primary']) ?>" onchange="document.getElementById('color_primary').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
                <div class="admin-form-group">
                    <label for="color_secondary">Cor Secundária</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_secondary" name="theme_color_secondary" value="<?= htmlspecialchars($config['theme_color_secondary']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_secondary']) ?>" onchange="document.getElementById('color_secondary').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="color_topbar_bg">Fundo Topbar</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_topbar_bg" name="theme_color_topbar_bg" value="<?= htmlspecialchars($config['theme_color_topbar_bg']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_topbar_bg']) ?>" onchange="document.getElementById('color_topbar_bg').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
                <div class="admin-form-group">
                    <label for="color_topbar_text">Texto Topbar</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_topbar_text" name="theme_color_topbar_text" value="<?= htmlspecialchars($config['theme_color_topbar_text']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_topbar_text']) ?>" onchange="document.getElementById('color_topbar_text').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="color_header_bg">Fundo Header</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_header_bg" name="theme_color_header_bg" value="<?= htmlspecialchars($config['theme_color_header_bg']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_header_bg']) ?>" onchange="document.getElementById('color_header_bg').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
                <div class="admin-form-group">
                    <label for="color_header_text">Texto Header</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_header_text" name="theme_color_header_text" value="<?= htmlspecialchars($config['theme_color_header_text']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_header_text']) ?>" onchange="document.getElementById('color_header_text').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
            </div>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="color_footer_bg">Fundo Footer</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_footer_bg" name="theme_color_footer_bg" value="<?= htmlspecialchars($config['theme_color_footer_bg']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_footer_bg']) ?>" onchange="document.getElementById('color_footer_bg').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
                <div class="admin-form-group">
                    <label for="color_footer_text">Texto Footer</label>
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <input type="color" id="color_footer_text" name="theme_color_footer_text" value="<?= htmlspecialchars($config['theme_color_footer_text']) ?>" style="width: 80px; height: 45px; border: 1px solid #ddd; border-radius: 6px; cursor: pointer;">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_footer_text']) ?>" onchange="document.getElementById('color_footer_text').value = this.value" placeholder="#000000" style="flex: 1; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção Logo - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Logo da Loja</h3>
            <div class="admin-form-group">
                <?php if (!empty($config['logo_url'])): ?>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Logo Atual</label>
                        <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0;">
                            <img src="<?= $basePath . htmlspecialchars($config['logo_url']) ?>" alt="Logo" id="current-logo-preview" style="max-height: 80px; max-width: 200px; object-fit: contain; display: block;" onerror="this.style.display='none';">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                                <input type="checkbox" name="remove_logo" value="1" id="remove-logo-checkbox">
                                <span>Remover logo atual</span>
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <label for="logo"><?= empty($config['logo_url']) ? 'Enviar Logo' : 'Substituir Logo' ?></label>
                <input type="file" id="logo" name="logo" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp,image/svg+xml" style="padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; width: 100%;">
                <div id="new-logo-preview" style="margin-top: 1rem; display: none;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #666;">Preview do novo logo:</label>
                    <div style="padding: 1rem; background: #f8f9fa; border-radius: 6px; border: 1px solid #e0e0e0; display: inline-block;">
                        <img id="new-logo-preview-img" src="" alt="Preview" style="max-height: 80px; max-width: 200px; object-fit: contain; display: block;">
                    </div>
                </div>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Formatos aceitos: JPG, PNG, GIF, WEBP, SVG. Tamanho recomendado: até 300px de altura.
                </small>
            </div>
        </div>
        
        <script>
            // Preview do novo logo selecionado
            document.getElementById('logo').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const previewDiv = document.getElementById('new-logo-preview');
                const previewImg = document.getElementById('new-logo-preview-img');
                
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        previewDiv.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewDiv.style.display = 'none';
                }
            });
            
            // Ocultar preview do logo atual quando checkbox de remover estiver marcado
            <?php if (!empty($config['logo_url'])): ?>
            document.getElementById('remove-logo-checkbox').addEventListener('change', function() {
                const currentLogoPreview = document.getElementById('current-logo-preview');
                if (this.checked) {
                    if (currentLogoPreview) {
                        currentLogoPreview.style.opacity = '0.5';
                    }
                } else {
                    if (currentLogoPreview) {
                        currentLogoPreview.style.opacity = '1';
                    }
                }
            });
            <?php endif; ?>
        </script>

        <!-- Seção Layout / Textos - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Layout / Textos</h3>
            <div class="admin-form-group">
                <label for="topbar_text">Texto da Topbar</label>
                <input type="text" id="topbar_text" name="topbar_text" value="<?= htmlspecialchars($config['topbar_text']) ?>" placeholder="Ex: Frete grátis acima de R$ 299 | Troca garantida em até 7 dias">
            </div>
            <div class="admin-form-group">
                <label for="newsletter_title">Título Newsletter</label>
                <input type="text" id="newsletter_title" name="newsletter_title" value="<?= htmlspecialchars($config['newsletter_title']) ?>" placeholder="Ex: Receba nossas ofertas">
            </div>
            <div class="admin-form-group">
                <label for="newsletter_subtitle">Subtítulo Newsletter</label>
                <input type="text" id="newsletter_subtitle" name="newsletter_subtitle" value="<?= htmlspecialchars($config['newsletter_subtitle']) ?>" placeholder="Ex: Cadastre-se e receba promoções exclusivas">
            </div>
        </div>

        <!-- Seção Informações da Loja -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Informações da Loja</h3>
            <div class="admin-form-group">
                <label for="admin_store_name">Nome da loja (painel/admin)</label>
                <input type="text" id="admin_store_name" name="admin_store_name" value="<?= htmlspecialchars($config['admin_store_name'] ?? '') ?>" placeholder="Ex: Ponto do Golfe Outlet" maxlength="150">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Este nome aparece na lateral esquerda do painel admin, abaixo do logotipo.
                </small>
            </div>
            <div class="admin-form-group">
                <label for="store_slug">Slug da loja</label>
                <input type="text" id="store_slug" name="store_slug" value="<?= htmlspecialchars($tenant->slug ?? '') ?>" placeholder="ex: ponto-do-golfe-outlet" maxlength="255" pattern="[a-z0-9-]+">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Usado na identificação interna da loja. Apenas letras minúsculas, números e hífens. Será gerado automaticamente a partir do nome da loja se não for preenchido manualmente.
                </small>
            </div>
            <div class="admin-form-group">
                <label for="admin_title_base">Título base do painel (aba do navegador)</label>
                <input type="text" id="admin_title_base" name="admin_title_base" value="<?= htmlspecialchars($config['admin_title_base'] ?? '') ?>" placeholder="Ex: Ponto do Golfe - Admin" maxlength="150">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Usado como título padrão da aba do navegador quando a página não define um título específico.
                </small>
            </div>
        </div>

        <!-- Seção Contato e Endereço - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Contato e Endereço</h3>
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="footer_phone">Telefone</label>
                    <input type="tel" id="footer_phone" name="footer_phone" value="<?= htmlspecialchars($config['footer_phone']) ?>" placeholder="(11) 1234-5678">
                </div>
                <div class="admin-form-group">
                    <label for="footer_whatsapp">WhatsApp</label>
                    <input type="tel" id="footer_whatsapp" name="footer_whatsapp" value="<?= htmlspecialchars($config['footer_whatsapp']) ?>" placeholder="(11) 98765-4321">
                </div>
            </div>
            <div class="admin-form-group">
                <label for="footer_email">E-mail</label>
                <input type="email" id="footer_email" name="footer_email" value="<?= htmlspecialchars($config['footer_email']) ?>" placeholder="contato@loja.com.br">
            </div>
            <div class="admin-form-group">
                <label for="contact_email">E-mail para contato (opcional)</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($config['contact_email'] ?? '') ?>" placeholder="contato@loja.com.br">
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">Se não preenchido, será usado o e-mail acima. Este e-mail receberá as mensagens do formulário de contato.</small>
            </div>
            <div class="admin-form-group">
                <label for="footer_address">Endereço</label>
                <textarea id="footer_address" name="footer_address" placeholder="Rua, número, bairro, cidade - UF, CEP"><?= htmlspecialchars($config['footer_address']) ?></textarea>
            </div>
        </div>

        <!-- Seção Redes Sociais - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Redes Sociais</h3>
            <div class="admin-form-group">
                <label for="footer_social_instagram">Instagram</label>
                <input type="url" id="footer_social_instagram" name="footer_social_instagram" value="<?= htmlspecialchars($config['footer_social_instagram']) ?>" placeholder="https://instagram.com/loja">
            </div>
            <div class="admin-form-group">
                <label for="footer_social_facebook">Facebook</label>
                <input type="url" id="footer_social_facebook" name="footer_social_facebook" value="<?= htmlspecialchars($config['footer_social_facebook']) ?>" placeholder="https://facebook.com/loja">
            </div>
            <div class="admin-form-group">
                <label for="footer_social_youtube">YouTube</label>
                <input type="url" id="footer_social_youtube" name="footer_social_youtube" value="<?= htmlspecialchars($config['footer_social_youtube']) ?>" placeholder="https://youtube.com/@loja">
            </div>
        </div>

        <!-- Seção Configurações do Catálogo -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Configurações do Catálogo</h3>
            <div class="admin-form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="catalogo_ocultar_estoque_zero" value="1" 
                           <?= $config['catalogo_ocultar_estoque_zero'] === '1' ? 'checked' : '' ?>>
                    <span>Não exibir no catálogo produtos com estoque 0 (quando gerenciam estoque)</span>
                </label>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem; margin-left: 1.75rem;">
                    Quando ativado, produtos que gerenciam estoque e estão com quantidade 0 não aparecerão nas listagens da loja. Produtos que não gerenciam estoque continuam aparecendo normalmente. A página do produto (PDP) continua acessível mesmo quando o produto está oculto.
                </small>
            </div>
        </div>

        <!-- Seção Menu Principal - Fase 10 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Menu Principal</h3>
            <div class="admin-table">
                <table>
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>URL</th>
                            <th style="text-align: center;">Ativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $menuItems = $config['theme_menu_main'];
                        // Garantir que temos pelo menos 6 itens
                        while (count($menuItems) < 6) {
                            $menuItems[] = ['label' => '', 'url' => '', 'enabled' => false];
                        }
                        foreach ($menuItems as $index => $item): 
                        ?>
                            <tr>
                                <td>
                                    <input type="text" name="menu_label[]" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="Ex: Home" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                                </td>
                                <td>
                                    <input type="text" name="menu_url[]" value="<?= htmlspecialchars($item['url'] ?? '') ?>" placeholder="Ex: /" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="menu_enabled[]" value="<?= $index ?>" <?= (isset($item['enabled']) && $item['enabled']) ? 'checked' : '' ?> style="width: 20px; height: 20px; cursor: pointer;">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Seção Footer / Páginas Institucionais - Fase 11 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Footer / Páginas Institucionais</h3>
            
            <?php
            $footer = $config['footer'] ?? [];
            $footerSections = $footer['sections'] ?? [];
            
            // Seção Ajuda
            $ajudaSection = $footerSections['ajuda'] ?? [];
            $ajudaLinks = $ajudaSection['links'] ?? [];
            ?>
            <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; font-size: 1.125rem;">Seção Ajuda</h4>
                <div class="admin-form-group">
                    <label>Título da Seção</label>
                    <input type="text" name="footer_sections[ajuda][title]" value="<?= htmlspecialchars($ajudaSection['title'] ?? 'Ajuda') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="admin-form-group">
                    <label>
                        <input type="checkbox" name="footer_sections[ajuda][enabled]" value="1" <?= (!empty($ajudaSection['enabled'])) ? 'checked' : '' ?>>
                        Exibir seção Ajuda no footer
                    </label>
                </div>
                <div style="margin-top: 1rem;">
                    <strong style="display: block; margin-bottom: 0.75rem;">Links:</strong>
                    <?php
                    $ajudaLinksDefaults = [
                        'contato' => ['label' => 'Fale conosco', 'route' => '/contato'],
                        'trocas_devolucoes' => ['label' => 'Trocas e devoluções', 'route' => '/trocas-e-devolucoes'],
                        'frete_prazos' => ['label' => 'Frete e prazos de entrega', 'route' => '/frete-prazos'],
                        'formas_pagamento' => ['label' => 'Formas de pagamento', 'route' => '/formas-de-pagamento'],
                        'faq' => ['label' => 'Perguntas frequentes (FAQ)', 'route' => '/faq'],
                    ];
                    foreach ($ajudaLinksDefaults as $linkKey => $linkDefault):
                        $linkData = $ajudaLinks[$linkKey] ?? $linkDefault;
                    ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px;">
                            <input type="text" name="footer_sections[ajuda][links][<?= $linkKey ?>][label]" value="<?= htmlspecialchars($linkData['label'] ?? $linkDefault['label']) ?>" placeholder="Label" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="color: #666; font-size: 0.875rem;"><?= htmlspecialchars($linkDefault['route']) ?></span>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="footer_sections[ajuda][links][<?= $linkKey ?>][enabled]" value="1" <?= (!empty($linkData['enabled'])) ? 'checked' : '' ?>>
                                <span>Ativo</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php
            // Seção Minha Conta
            $minhaContaSection = $footerSections['minha_conta'] ?? [];
            $minhaContaLinks = $minhaContaSection['links'] ?? [];
            ?>
            <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; font-size: 1.125rem;">Seção Minha Conta</h4>
                <div class="admin-form-group">
                    <label>Título da Seção</label>
                    <input type="text" name="footer_sections[minha_conta][title]" value="<?= htmlspecialchars($minhaContaSection['title'] ?? 'Minha Conta') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="admin-form-group">
                    <label>
                        <input type="checkbox" name="footer_sections[minha_conta][enabled]" value="1" <?= (!empty($minhaContaSection['enabled'])) ? 'checked' : '' ?>>
                        Exibir seção Minha Conta no footer
                    </label>
                </div>
                <div style="margin-top: 1rem;">
                    <strong style="display: block; margin-bottom: 0.75rem;">Links:</strong>
                    <?php
                    $minhaContaLinksDefaults = [
                        'minha_conta' => ['label' => 'Minha conta', 'route' => '/minha-conta'],
                        'carrinho' => ['label' => 'Carrinho', 'route' => '/carrinho'],
                        'checkout' => ['label' => 'Finalizar compra', 'route' => '/checkout'],
                        'meus_pedidos' => ['label' => 'Meus pedidos', 'route' => '/minha-conta/pedidos'],
                        'meus_dados' => ['label' => 'Meus dados', 'route' => '/minha-conta/perfil'],
                    ];
                    foreach ($minhaContaLinksDefaults as $linkKey => $linkDefault):
                        $linkData = $minhaContaLinks[$linkKey] ?? $linkDefault;
                    ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px;">
                            <input type="text" name="footer_sections[minha_conta][links][<?= $linkKey ?>][label]" value="<?= htmlspecialchars($linkData['label'] ?? $linkDefault['label']) ?>" placeholder="Label" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="color: #666; font-size: 0.875rem;"><?= htmlspecialchars($linkDefault['route']) ?></span>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="footer_sections[minha_conta][links][<?= $linkKey ?>][enabled]" value="1" <?= (!empty($linkData['enabled'])) ? 'checked' : '' ?>>
                                <span>Ativo</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php
            // Seção Institucional
            $institucionalSection = $footerSections['institucional'] ?? [];
            $institucionalLinks = $institucionalSection['links'] ?? [];
            ?>
            <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; font-size: 1.125rem;">Seção Institucional</h4>
                <div class="admin-form-group">
                    <label>Título da Seção</label>
                    <input type="text" name="footer_sections[institucional][title]" value="<?= htmlspecialchars($institucionalSection['title'] ?? 'Institucional') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="admin-form-group">
                    <label>
                        <input type="checkbox" name="footer_sections[institucional][enabled]" value="1" <?= (!empty($institucionalSection['enabled'])) ? 'checked' : '' ?>>
                        Exibir seção Institucional no footer
                    </label>
                </div>
                <div style="margin-top: 1rem;">
                    <strong style="display: block; margin-bottom: 0.75rem;">Links:</strong>
                    <?php
                    $institucionalLinksDefaults = [
                        'sobre' => ['label' => 'Sobre o Ponto do Golfe', 'route' => '/sobre'],
                        'politica_privacidade' => ['label' => 'Política de privacidade', 'route' => '/politica-de-privacidade'],
                        'termos_uso' => ['label' => 'Termos de uso', 'route' => '/termos-de-uso'],
                        'politica_cookies' => ['label' => 'Política de cookies', 'route' => '/politica-de-cookies'],
                        'seja_parceiro' => ['label' => 'Seja parceiro / Atacado', 'route' => '/seja-parceiro'],
                    ];
                    foreach ($institucionalLinksDefaults as $linkKey => $linkDefault):
                        $linkData = $institucionalLinks[$linkKey] ?? $linkDefault;
                    ?>
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem; padding: 0.75rem; background: white; border-radius: 6px;">
                            <input type="text" name="footer_sections[institucional][links][<?= $linkKey ?>][label]" value="<?= htmlspecialchars($linkData['label'] ?? $linkDefault['label']) ?>" placeholder="Label" style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="color: #666; font-size: 0.875rem;"><?= htmlspecialchars($linkDefault['route']) ?></span>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" name="footer_sections[institucional][links][<?= $linkKey ?>][enabled]" value="1" <?= (!empty($linkData['enabled'])) ? 'checked' : '' ?>>
                                <span>Ativo</span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php
            // Seção Categorias
            $categoriasSection = $footerSections['categorias'] ?? [];
            ?>
            <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                <h4 style="margin-bottom: 1rem; font-size: 1.125rem;">Seção Categorias</h4>
                <div class="admin-form-group">
                    <label>Título da Seção</label>
                    <input type="text" name="footer_sections[categorias][title]" value="<?= htmlspecialchars($categoriasSection['title'] ?? 'Categorias') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
                <div class="admin-form-group">
                    <label>
                        <input type="checkbox" name="footer_sections[categorias][enabled]" value="1" <?= (!empty($categoriasSection['enabled'])) ? 'checked' : '' ?>>
                        Exibir seção Categorias no footer
                    </label>
                </div>
                <div class="admin-form-group">
                    <label>Quantidade máxima de categorias a exibir</label>
                    <input type="number" name="footer_sections[categorias][limit]" value="<?= htmlspecialchars($categoriasSection['limit'] ?? 6) ?>" min="1" max="20" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                </div>
            </div>
        </div>

        <!-- Seção Conteúdo das Páginas Institucionais - Fase 11 -->
        <div class="admin-form-section">
            <h3 class="admin-form-section-title">Conteúdo das Páginas Institucionais</h3>
            
            <?php
            $pages = $config['pages'] ?? [];
            $pageSlugs = [
                'sobre' => 'Sobre',
                'contato' => 'Contato',
                'trocas_devolucoes' => 'Trocas e Devoluções',
                'frete_prazos' => 'Frete e Prazos',
                'formas_pagamento' => 'Formas de Pagamento',
                'faq' => 'FAQ',
                'politica_privacidade' => 'Política de Privacidade',
                'termos_uso' => 'Termos de Uso',
                'politica_cookies' => 'Política de Cookies',
                'seja_parceiro' => 'Seja Parceiro',
            ];
            
            foreach ($pageSlugs as $slug => $label):
                $page = $pages[$slug] ?? [];
            ?>
                <?php if ($slug === 'faq'): ?>
                    <!-- FAQ com estrutura dinâmica de perguntas/respostas -->
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.125rem;"><?= htmlspecialchars($label) ?></h4>
                        <div class="admin-form-group">
                            <label>Título da Página</label>
                            <input type="text" name="pages[<?= $slug ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div class="admin-form-group">
                            <label>Texto Introdutório (opcional)</label>
                            <textarea name="pages[<?= $slug ?>][intro]" id="pages-<?= $slug ?>-intro" class="pg-richtext" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"><?= htmlspecialchars($page['intro'] ?? '') ?></textarea>
                            <small style="color: #666; font-size: 0.875rem;">Use o editor acima para um texto breve antes da lista de perguntas.</small>
                        </div>
                        <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #ddd;">
                        <h5 style="margin-bottom: 0.5rem; font-size: 1rem;">Perguntas e Respostas</h5>
                        <p style="color: #666; font-size: 0.875rem; margin-bottom: 1rem;">Cadastre cada dúvida separadamente. No site, as perguntas aparecerão em formato de lista expansível.</p>
                        
                        <div id="faq-items-container">
                            <?php 
                            $faqItems = $page['items'] ?? [];
                            foreach ($faqItems as $index => $item): 
                            ?>
                                <div class="pg-faq-item" data-index="<?= (int)$index ?>" style="margin-bottom: 1rem; padding: 1rem; background: white; border: 1px solid #ddd; border-radius: 6px;">
                                    <div class="admin-form-group">
                                        <label>Pergunta</label>
                                        <input type="text" name="pages[<?= $slug ?>][items][<?= (int)$index ?>][question]" class="form-control" value="<?= htmlspecialchars($item['question'] ?? '', ENT_QUOTES, 'UTF-8') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                                    </div>
                                    <div class="admin-form-group">
                                        <label>Resposta</label>
                                        <textarea name="pages[<?= $slug ?>][items][<?= (int)$index ?>][answer]" class="form-control pg-richtext" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"><?= htmlspecialchars($item['answer'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                    </div>
                                    <button type="button" class="pg-faq-remove-item admin-btn" style="background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                        <i class="bi bi-trash icon"></i> Remover pergunta
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="button" id="faq-add-item" class="admin-btn" style="background: #007bff; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem; margin-top: 0.5rem;">
                            <i class="bi bi-plus-circle icon"></i> Adicionar pergunta
                        </button>
                        
                        <!-- Template oculto para novos itens -->
                        <div id="faq-item-template" style="display: none;">
                            <div class="pg-faq-item" data-index="__INDEX__" style="margin-bottom: 1rem; padding: 1rem; background: white; border: 1px solid #ddd; border-radius: 6px;">
                                <div class="admin-form-group">
                                    <label>Pergunta</label>
                                    <input type="text" name="pages[<?= $slug ?>][items][__INDEX__][question]" class="form-control" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                                </div>
                                <div class="admin-form-group">
                                    <label>Resposta</label>
                                    <textarea name="pages[<?= $slug ?>][items][__INDEX__][answer]" class="form-control pg-richtext" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"></textarea>
                                </div>
                                <button type="button" class="pg-faq-remove-item admin-btn" style="background: #dc3545; color: white; padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.875rem;">
                                    <i class="bi bi-trash icon"></i> Remover pergunta
                                </button>
                            </div>
                        </div>
                    </div>
                <?php elseif ($slug === 'contato'): ?>
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.125rem;"><?= htmlspecialchars($label) ?></h4>
                        <div class="admin-form-group">
                            <label>Título da Página</label>
                            <input type="text" name="pages[<?= $slug ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div class="admin-form-group">
                            <label>Texto Introdutório</label>
                            <textarea name="pages[<?= $slug ?>][intro]" id="pages-<?= $slug ?>-intro" class="pg-richtext" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"><?= htmlspecialchars($page['intro'] ?? '') ?></textarea>
                            <small style="color: #666; font-size: 0.875rem;">Use o editor acima para formatar títulos, parágrafos, listas e links.</small>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                        <h4 style="margin-bottom: 1rem; font-size: 1.125rem;"><?= htmlspecialchars($label) ?></h4>
                        <div class="admin-form-group">
                            <label>Título da Página</label>
                            <input type="text" name="pages[<?= $slug ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;">
                        </div>
                        <div class="admin-form-group">
                            <label>Conteúdo</label>
                            <textarea name="pages[<?= $slug ?>][content]" id="pages-<?= $slug ?>-content" class="pg-richtext" rows="8" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-family: inherit;"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                            <small style="color: #666; font-size: 0.875rem;">Use o editor acima para formatar títulos, parágrafos, listas e links.</small>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="admin-btn admin-btn-primary" style="margin-top: 2rem;">
            <i class="bi bi-check-circle icon"></i>
            Salvar Tema
        </button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var container = document.getElementById('faq-items-container');
    var template = document.getElementById('faq-item-template');
    var addButton = document.getElementById('faq-add-item');
    
    if (!container || !template || !addButton) {
        return;
    }
    
    function getNextIndex() {
        var items = container.querySelectorAll('.pg-faq-item');
        var maxIndex = -1;
        items.forEach(function (item) {
            var idx = parseInt(item.getAttribute('data-index'), 10);
            if (!isNaN(idx) && idx > maxIndex) {
                maxIndex = idx;
            }
        });
        return maxIndex + 1;
    }
    
    function initRichTextFor(element) {
        // Se estiver usando CKEditor 5 Classic com seleção por .pg-richtext:
        if (typeof ClassicEditor !== 'undefined') {
            // Buscar apenas textareas que ainda não foram inicializados
            var textareas = element.querySelectorAll('textarea.pg-richtext:not([data-ckeditor-initialized])');
            textareas.forEach(function (ta) {
                // Marcar como inicializado antes de criar
                ta.setAttribute('data-ckeditor-initialized', 'true');
                
                ClassicEditor.create(ta, {
                    toolbar: [
                        'undo', 'redo',
                        '|', 'bold', 'italic', 'underline',
                        '|', 'heading',
                        '|', 'bulletedList', 'numberedList',
                        '|', 'alignment',
                        '|', 'link'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Parágrafo', class: 'ck-heading_paragraph' },
                            { model: 'heading2', title: 'Título Médio', class: 'ck-heading_heading2' },
                            { model: 'heading3', title: 'Subtítulo', class: 'ck-heading_heading3' }
                        ]
                    }
                }).catch(function (error) {
                    console.error('Erro ao inicializar editor em FAQ', error);
                    // Remover marcação em caso de erro
                    ta.removeAttribute('data-ckeditor-initialized');
                });
            });
        }
    }
    
    // Inicializar editores existentes (apenas se ainda não foram inicializados pelo layout base)
    // Aguardar um pouco para garantir que o layout base já processou
    setTimeout(function() {
        initRichTextFor(container);
    }, 100);
    
    addButton.addEventListener('click', function () {
        var newIndex = getNextIndex();
        var html = template.innerHTML.replace(/__INDEX__/g, newIndex);
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var newItem = wrapper.firstElementChild;
        container.appendChild(newItem);
        initRichTextFor(newItem);
    });
    
    container.addEventListener('click', function (event) {
        if (event.target.classList.contains('pg-faq-remove-item') || event.target.closest('.pg-faq-remove-item')) {
            var btn = event.target.classList.contains('pg-faq-remove-item') ? event.target : event.target.closest('.pg-faq-remove-item');
            var card = btn.closest('.pg-faq-item');
            if (card) {
                card.remove();
            }
        }
    });
});
</script>

<script>
// Gerar slug automaticamente a partir do nome da loja
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('admin_store_name');
    const slugInput = document.getElementById('store_slug');
    
    if (!nameInput || !slugInput) {
        return;
    }
    
    let slugTouched = false;
    
    // Função para converter texto em slug
    function slugify(str) {
        if (!str) {
            return '';
        }
        
        return str
            .toLowerCase()
            .trim()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remove acentos
            .replace(/[^a-z0-9]+/g, '-')      // Troca qualquer coisa não alfanumérica por hífen
            .replace(/^-+|-+$/g, '')          // Remove hífens extras no início/fim
            || 'loja';
    }
    
    // Marca que o usuário mexeu manualmente no slug
    slugInput.addEventListener('input', function () {
        slugTouched = true;
    });
    
    // Marca que o usuário mexeu manualmente no slug ao focar e editar
    slugInput.addEventListener('focus', function () {
        // Se o campo estiver vazio ou igual ao slug gerado, não marcar como "touched"
        // Mas se o usuário começar a digitar, marcar
        const currentValue = slugInput.value;
        const autoGenerated = slugify(nameInput.value);
        if (currentValue !== autoGenerated) {
            slugTouched = true;
        }
    });
    
    // Atualiza slug automaticamente a partir do nome da loja
    nameInput.addEventListener('input', function () {
        if (!slugTouched) {
            slugInput.value = slugify(nameInput.value);
        }
    });
    
    // Se o slug estiver vazio ao carregar, preencher automaticamente
    if (!slugInput.value && nameInput.value) {
        slugInput.value = slugify(nameInput.value);
    }
});
</script>

<style>
/* Fase 10 – Ajustes layout Admin - Tema */
.theme-page {
    max-width: 1200px;
}
.admin-form-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.admin-form-section-title {
    font-size: 1.375rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #f0f0f0;
}
@media (max-width: 768px) {
    .admin-form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>


