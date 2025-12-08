<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Services\ThemeConfig;

class ThemeController extends Controller
{
    public function edit(): void
    {
        // Carregar todas as configurações atuais
        $config = [
            // Cores
            'theme_color_primary' => ThemeConfig::get('theme_color_primary', '#2E7D32'),
            'theme_color_secondary' => ThemeConfig::get('theme_color_secondary', '#F7931E'),
            'theme_color_topbar_bg' => ThemeConfig::get('theme_color_topbar_bg', '#1a1a1a'),
            'theme_color_topbar_text' => ThemeConfig::get('theme_color_topbar_text', '#ffffff'),
            'theme_color_header_bg' => ThemeConfig::get('theme_color_header_bg', '#ffffff'),
            'theme_color_header_text' => ThemeConfig::get('theme_color_header_text', '#333333'),
            'theme_color_footer_bg' => ThemeConfig::get('theme_color_footer_bg', '#1a1a1a'),
            'theme_color_footer_text' => ThemeConfig::get('theme_color_footer_text', '#ffffff'),
            
            // Textos e identidade
            'topbar_text' => ThemeConfig::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe'),
            'newsletter_title' => ThemeConfig::get('newsletter_title', 'Receba nossas ofertas'),
            'newsletter_subtitle' => ThemeConfig::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas'),
            
            // Contato e endereço
            'footer_phone' => ThemeConfig::get('footer_phone', ''),
            'footer_whatsapp' => ThemeConfig::get('footer_whatsapp', ''),
            'footer_email' => ThemeConfig::get('footer_email', ''),
            'footer_address' => ThemeConfig::get('footer_address', ''),
            
            // Redes sociais
            'footer_social_instagram' => ThemeConfig::get('footer_social_instagram', ''),
            'footer_social_facebook' => ThemeConfig::get('footer_social_facebook', ''),
            'footer_social_youtube' => ThemeConfig::get('footer_social_youtube', ''),
            
            // Menu principal
            'theme_menu_main' => ThemeConfig::getJson('theme_menu_main', [
                ['label' => 'Home', 'url' => '/', 'enabled' => true],
                ['label' => 'Sobre', 'url' => '/sobre', 'enabled' => true],
                ['label' => 'Loja', 'url' => '/produtos', 'enabled' => true],
                ['label' => 'Minha conta', 'url' => '/minha-conta', 'enabled' => true],
                ['label' => 'Carrinho', 'url' => '/carrinho', 'enabled' => true],
                ['label' => 'Frete/Prazos', 'url' => '/frete-prazos', 'enabled' => false],
            ]),
        ];

        $tenant = \App\Tenant\TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/theme/edit-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Tema da Loja',
            'config' => $config
        ]);
    }

    public function update(): void
    {
        // Validar e salvar cores
        $colorKeys = [
            'theme_color_primary',
            'theme_color_secondary',
            'theme_color_topbar_bg',
            'theme_color_topbar_text',
            'theme_color_header_bg',
            'theme_color_header_text',
            'theme_color_footer_bg',
            'theme_color_footer_text',
        ];

        foreach ($colorKeys as $key) {
            $value = $_POST[$key] ?? '';
            if (!empty($value)) {
                ThemeConfig::set($key, $value);
            }
        }

        // Salvar textos
        $textKeys = [
            'topbar_text',
            'newsletter_title',
            'newsletter_subtitle',
            'footer_phone',
            'footer_whatsapp',
            'footer_email',
            'footer_address',
            'footer_social_instagram',
            'footer_social_facebook',
            'footer_social_youtube',
        ];

        foreach ($textKeys as $key) {
            $value = $_POST[$key] ?? '';
            ThemeConfig::set($key, $value);
        }

        // Salvar menu principal
        $menuItems = [];
        $menuLabels = $_POST['menu_label'] ?? [];
        $menuUrls = $_POST['menu_url'] ?? [];
        // Checkboxes só aparecem no POST se estiverem marcados
        // O valor do checkbox é o índice do item
        $enabledIndices = [];
        if (isset($_POST['menu_enabled']) && is_array($_POST['menu_enabled'])) {
            foreach ($_POST['menu_enabled'] as $index) {
                $enabledIndices[(int)$index] = true;
            }
        }

        for ($i = 0; $i < count($menuLabels); $i++) {
            if (!empty($menuLabels[$i]) && !empty($menuUrls[$i])) {
                $menuItems[] = [
                    'label' => $menuLabels[$i],
                    'url' => $menuUrls[$i],
                    'enabled' => isset($enabledIndices[$i]),
                ];
            }
        }

        ThemeConfig::set('theme_menu_main', $menuItems);
        ThemeConfig::clearCache();

        $this->redirect('/admin/tema?success=1');
    }
}


