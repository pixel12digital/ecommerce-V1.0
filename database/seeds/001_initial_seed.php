<?php

use App\Core\Database;

$db = Database::getConnection();

try {
    $db->beginTransaction();

    // Criar tenant demo
    $stmt = $db->prepare("
        INSERT INTO tenants (id, name, slug, status, plan) 
        VALUES (1, 'Loja Demo', 'loja-demo', 'active', 'basic')
        ON DUPLICATE KEY UPDATE name = VALUES(name)
    ");
    $stmt->execute();

    // Criar domínio localhost
    $stmt = $db->prepare("
        INSERT INTO tenant_domains (tenant_id, domain, is_primary, is_custom_domain) 
        VALUES (1, 'localhost', 1, 0)
        ON DUPLICATE KEY UPDATE domain = VALUES(domain)
    ");
    $stmt->execute();

    // Criar platform user
    $platformPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO platform_users (name, email, password_hash, role) 
        VALUES ('Admin Platform', 'admin@platform.local', :password, 'superadmin')
        ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
    ");
    $stmt->execute(['password' => $platformPassword]);

    // Criar store user
    $storePassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO store_users (tenant_id, name, email, password_hash, role) 
        VALUES (1, 'Admin Loja', 'contato@pixel12digital.com.br', :password, 'owner')
        ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), email = VALUES(email)
    ");
    $stmt->execute(['password' => $storePassword]);

    // Criar versão inicial
    $stmt = $db->prepare("
        INSERT INTO system_versions (version) 
        VALUES ('0.1.0')
        ON DUPLICATE KEY UPDATE version = VALUES(version)
    ");
    $stmt->execute();

    // Configurações de tema padrão para tenant_id = 1
    $themeSettings = [
        // Cores
        ['theme_color_primary', '#2E7D32'],
        ['theme_color_secondary', '#F7931E'],
        ['theme_color_topbar_bg', '#1a1a1a'],
        ['theme_color_topbar_text', '#ffffff'],
        ['theme_color_header_bg', '#ffffff'],
        ['theme_color_header_text', '#333333'],
        ['theme_color_footer_bg', '#1a1a1a'],
        ['theme_color_footer_text', '#ffffff'],
        
        // Textos
        ['topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe'],
        ['newsletter_title', 'Receba nossas ofertas'],
        ['newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas'],
        
        // Contato
        ['footer_phone', ''],
        ['footer_whatsapp', ''],
        ['footer_email', ''],
        ['footer_address', ''],
        
        // Redes sociais
        ['footer_social_instagram', ''],
        ['footer_social_facebook', ''],
        ['footer_social_youtube', ''],
        
        // Menu principal
        ['theme_menu_main', json_encode([
            ['label' => 'Home', 'url' => '/', 'enabled' => true],
            ['label' => 'Sobre', 'url' => '/sobre', 'enabled' => true],
            ['label' => 'Loja', 'url' => '/produtos', 'enabled' => true],
            ['label' => 'Minha conta', 'url' => '/minha-conta', 'enabled' => true],
            ['label' => 'Carrinho', 'url' => '/carrinho', 'enabled' => true],
            ['label' => 'Frete/Prazos', 'url' => '/frete-prazos', 'enabled' => false],
        ], JSON_UNESCAPED_UNICODE)],
    ];

    $stmt = $db->prepare("
        INSERT INTO tenant_settings (tenant_id, `key`, value, created_at, updated_at)
        VALUES (1, :key, :value, NOW(), NOW())
        ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
    ");

    foreach ($themeSettings as $setting) {
        $stmt->execute([
            'key' => $setting[0],
            'value' => $setting[1]
        ]);
    }

    $db->commit();
    echo "Seed executado com sucesso!\n";
    echo "Platform Admin: admin@platform.local / admin123\n";
    echo "Store Admin: contato@pixel12digital.com.br / admin123\n";
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}

