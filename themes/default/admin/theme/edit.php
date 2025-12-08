<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tema da Loja - Store Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a { color: white; text-decoration: none; }
        .header h2 { font-size: 1.25rem; }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .card h3 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input[type="color"] {
            width: 80px;
            height: 40px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .menu-table {
            width: 100%;
            border-collapse: collapse;
        }
        .menu-table th,
        .menu-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .menu-table th {
            background: #f8f8f8;
            font-weight: 600;
        }
        .menu-table input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .menu-table input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .btn-save {
            background: #F7931E;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }
        .btn-save:hover {
            background: #e6851a;
        }
        .success-message {
            background: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            margin-left: 0.5rem;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <?php
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    <div class="header">
        <h2>Tema da Loja</h2>
        <a href="<?= $basePath ?>/admin"><i class="bi bi-arrow-left icon"></i> Voltar ao Dashboard</a>
    </div>
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                Tema salvo com sucesso!
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/admin/tema">
            <!-- Seção Cores -->
            <div class="card">
                <h3>Cores do Tema</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Cor Primária</label>
                        <input type="color" name="theme_color_primary" value="<?= htmlspecialchars($config['theme_color_primary']) ?>" id="color_primary">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_primary']) ?>" onchange="document.getElementById('color_primary').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Cor Secundária</label>
                        <input type="color" name="theme_color_secondary" value="<?= htmlspecialchars($config['theme_color_secondary']) ?>" id="color_secondary">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_secondary']) ?>" onchange="document.getElementById('color_secondary').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fundo Topbar</label>
                        <input type="color" name="theme_color_topbar_bg" value="<?= htmlspecialchars($config['theme_color_topbar_bg']) ?>" id="color_topbar_bg">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_topbar_bg']) ?>" onchange="document.getElementById('color_topbar_bg').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Texto Topbar</label>
                        <input type="color" name="theme_color_topbar_text" value="<?= htmlspecialchars($config['theme_color_topbar_text']) ?>" id="color_topbar_text">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_topbar_text']) ?>" onchange="document.getElementById('color_topbar_text').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fundo Header</label>
                        <input type="color" name="theme_color_header_bg" value="<?= htmlspecialchars($config['theme_color_header_bg']) ?>" id="color_header_bg">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_header_bg']) ?>" onchange="document.getElementById('color_header_bg').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Texto Header</label>
                        <input type="color" name="theme_color_header_text" value="<?= htmlspecialchars($config['theme_color_header_text']) ?>" id="color_header_text">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_header_text']) ?>" onchange="document.getElementById('color_header_text').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Fundo Footer</label>
                        <input type="color" name="theme_color_footer_bg" value="<?= htmlspecialchars($config['theme_color_footer_bg']) ?>" id="color_footer_bg">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_footer_bg']) ?>" onchange="document.getElementById('color_footer_bg').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                    <div class="form-group">
                        <label>Texto Footer</label>
                        <input type="color" name="theme_color_footer_text" value="<?= htmlspecialchars($config['theme_color_footer_text']) ?>" id="color_footer_text">
                        <input type="text" value="<?= htmlspecialchars($config['theme_color_footer_text']) ?>" onchange="document.getElementById('color_footer_text').value = this.value" style="width: calc(100% - 100px); margin-left: 0.5rem;">
                    </div>
                </div>
            </div>

            <!-- Seção Layout / Textos -->
            <div class="card">
                <h3>Layout / Textos</h3>
                <div class="form-group">
                    <label>Texto da Topbar</label>
                    <input type="text" name="topbar_text" value="<?= htmlspecialchars($config['topbar_text']) ?>" placeholder="Ex: Frete grátis acima de R$ 299 | Troca garantida em até 7 dias">
                </div>
                <div class="form-group">
                    <label>Título Newsletter</label>
                    <input type="text" name="newsletter_title" value="<?= htmlspecialchars($config['newsletter_title']) ?>" placeholder="Ex: Receba nossas ofertas">
                </div>
                <div class="form-group">
                    <label>Subtítulo Newsletter</label>
                    <input type="text" name="newsletter_subtitle" value="<?= htmlspecialchars($config['newsletter_subtitle']) ?>" placeholder="Ex: Cadastre-se e receba promoções exclusivas">
                </div>
            </div>

            <!-- Seção Contato e Endereço -->
            <div class="card">
                <h3>Contato e Endereço</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="tel" name="footer_phone" value="<?= htmlspecialchars($config['footer_phone']) ?>" placeholder="(11) 1234-5678">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp</label>
                        <input type="tel" name="footer_whatsapp" value="<?= htmlspecialchars($config['footer_whatsapp']) ?>" placeholder="(11) 98765-4321">
                    </div>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="footer_email" value="<?= htmlspecialchars($config['footer_email']) ?>" placeholder="contato@loja.com.br">
                </div>
                <div class="form-group">
                    <label>Endereço</label>
                    <textarea name="footer_address" placeholder="Rua, número, bairro, cidade - UF, CEP"><?= htmlspecialchars($config['footer_address']) ?></textarea>
                </div>
            </div>

            <!-- Seção Redes Sociais -->
            <div class="card">
                <h3>Redes Sociais</h3>
                <div class="form-group">
                    <label>Instagram</label>
                    <input type="text" name="footer_social_instagram" value="<?= htmlspecialchars($config['footer_social_instagram']) ?>" placeholder="https://instagram.com/loja">
                </div>
                <div class="form-group">
                    <label>Facebook</label>
                    <input type="text" name="footer_social_facebook" value="<?= htmlspecialchars($config['footer_social_facebook']) ?>" placeholder="https://facebook.com/loja">
                </div>
                <div class="form-group">
                    <label>YouTube</label>
                    <input type="text" name="footer_social_youtube" value="<?= htmlspecialchars($config['footer_social_youtube']) ?>" placeholder="https://youtube.com/@loja">
                </div>
            </div>

            <!-- Seção Menu Principal -->
            <div class="card">
                <h3>Menu Principal</h3>
                <table class="menu-table">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Ativo</th>
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
                                    <input type="text" name="menu_label[]" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="Ex: Home">
                                </td>
                                <td>
                                    <input type="text" name="menu_url[]" value="<?= htmlspecialchars($item['url'] ?? '') ?>" placeholder="Ex: /">
                                </td>
                                <td>
                                    <input type="checkbox" name="menu_enabled[]" value="<?= $index ?>" <?= (isset($item['enabled']) && $item['enabled']) ? 'checked' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="btn-save">Salvar Tema</button>
        </form>
    </div>
</body>
</html>


