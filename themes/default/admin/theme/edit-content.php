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

    <form method="POST" action="<?= $basePath ?>/admin/tema" class="admin-form">
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

        <button type="submit" class="admin-btn admin-btn-primary" style="margin-top: 2rem;">
            <i class="bi bi-check-circle icon"></i>
            Salvar Tema
        </button>
    </form>
</div>

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


