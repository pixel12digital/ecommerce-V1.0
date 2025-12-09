<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Services\ThemeConfig;

class ThemeController extends Controller
{
    private function sanitizeFileName($fileName): string
    {
        // Remove caracteres especiais e espaços
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        // Remove múltiplos underscores
        $fileName = preg_replace('/_+/', '_', $fileName);
        return $fileName;
    }
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
            
            // Informações da loja (painel admin)
            'admin_store_name' => ThemeConfig::get('admin_store_name', ''),
            'admin_title_base' => ThemeConfig::get('admin_title_base', ''),
            
            // Textos e identidade
            'topbar_text' => ThemeConfig::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe'),
            'newsletter_title' => ThemeConfig::get('newsletter_title', 'Receba nossas ofertas'),
            'newsletter_subtitle' => ThemeConfig::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas'),
            
            // Contato e endereço
            'footer_phone' => ThemeConfig::get('footer_phone', ''),
            'footer_whatsapp' => ThemeConfig::get('footer_whatsapp', ''),
            'footer_email' => ThemeConfig::get('footer_email', ''),
            'footer_address' => ThemeConfig::get('footer_address', ''),
            'contact_email' => ThemeConfig::get('contact_email', ''), // E-mail específico para contato (fallback para footer_email)
            
            // Redes sociais
            'footer_social_instagram' => ThemeConfig::get('footer_social_instagram', ''),
            'footer_social_facebook' => ThemeConfig::get('footer_social_facebook', ''),
            'footer_social_youtube' => ThemeConfig::get('footer_social_youtube', ''),
            
            // Menu principal
            'theme_menu_main' => ThemeConfig::getJson('theme_menu_main', [
                ['label' => 'Home', 'url' => '/', 'enabled' => true],
                ['label' => 'Sobre', 'url' => '/sobre', 'enabled' => true],
                ['label' => 'Loja', 'url' => '/produtos', 'enabled' => true],
                ['label' => 'Contato', 'url' => '/contato', 'enabled' => true],
                ['label' => 'Minha conta', 'url' => '/minha-conta', 'enabled' => true],
                ['label' => 'Carrinho', 'url' => '/carrinho', 'enabled' => true],
            ]),
            
            // Configurações do catálogo
            'catalogo_ocultar_estoque_zero' => ThemeConfig::get('catalogo_ocultar_estoque_zero', '0'),
            
            // Logo
            'logo_url' => ThemeConfig::get('logo_url', ''),
            
            // Páginas institucionais
            'pages' => ThemeConfig::getPages(),
            
            // Footer
            'footer' => ThemeConfig::getFooterConfig(),
        ];

        $tenant = \App\Tenant\TenantContext::tenant();
        
        // Preencher admin_store_name com tenant->name se não estiver configurado
        if (empty($config['admin_store_name']) && !empty($tenant->name)) {
            $config['admin_store_name'] = $tenant->name;
        }
        
        // Sugerir admin_title_base baseado no nome da loja se não estiver configurado
        if (empty($config['admin_title_base'])) {
            $suggestedTitle = !empty($config['admin_store_name']) 
                ? $config['admin_store_name'] . ' - Admin'
                : (!empty($tenant->name) ? $tenant->name . ' - Admin' : '');
            // Não salvar, apenas sugerir como placeholder/value default
            $config['admin_title_base'] = $suggestedTitle;
        }
        
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

        // Salvar informações da loja (painel admin)
        $adminStoreName = trim($_POST['admin_store_name'] ?? '');
        $adminTitleBase = trim($_POST['admin_title_base'] ?? '');
        
        // Salvar admin_title_base em tenant_settings
        ThemeConfig::set('admin_title_base', $adminTitleBase);
        
        // Atualizar tenants.name se admin_store_name foi preenchido
        if (!empty($adminStoreName)) {
            $tenantId = \App\Tenant\TenantContext::id();
            $db = \App\Core\Database::getConnection();
            
            // Sanitizar e limitar tamanho
            $adminStoreName = substr(trim($adminStoreName), 0, 150);
            
            $stmt = $db->prepare("
                UPDATE tenants 
                SET name = :name 
                WHERE id = :tenant_id
            ");
            $stmt->execute([
                'name' => $adminStoreName,
                'tenant_id' => $tenantId
            ]);
            
            // Também salvar em tenant_settings para manter sincronizado
            ThemeConfig::set('admin_store_name', $adminStoreName);
        } else {
            // Se veio vazio, limpar o setting (mas não alterar tenants.name para manter compatibilidade)
            ThemeConfig::set('admin_store_name', '');
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
            'contact_email',
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
        
        // Salvar configuração do catálogo
        $catalogoOcultarEstoqueZero = isset($_POST['catalogo_ocultar_estoque_zero']) ? '1' : '0';
        ThemeConfig::set('catalogo_ocultar_estoque_zero', $catalogoOcultarEstoqueZero);
        
        // Processar upload de logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            
            if (in_array($file['type'], $allowedTypes)) {
                $tenantId = \App\Tenant\TenantContext::id();
                
                $paths = require __DIR__ . '/../../../../config/paths.php';
                $uploadsBasePath = $paths['uploads_produtos_base_path'];
                $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/logo';
                
                if (!is_dir($uploadsPath)) {
                    mkdir($uploadsPath, 0755, true);
                }
                
                $fileName = $this->sanitizeFileName($file['name']);
                $destFile = $uploadsPath . '/' . $fileName;
                
                // Se arquivo já existe, adicionar timestamp
                if (file_exists($destFile)) {
                    $info = pathinfo($fileName);
                    $fileName = $info['filename'] . '_' . time() . '.' . $info['extension'];
                    $destFile = $uploadsPath . '/' . $fileName;
                }
                
                if (move_uploaded_file($file['tmp_name'], $destFile)) {
                    // Buscar logo antigo antes de salvar o novo
                    $oldLogo = ThemeConfig::get('logo_url', '');
                    
                    $relativePath = "/uploads/tenants/{$tenantId}/logo/{$fileName}";
                    ThemeConfig::set('logo_url', $relativePath);
                    
                    // Remover logo antigo se existir e for diferente do novo
                    if (!empty($oldLogo) && $oldLogo !== $relativePath) {
                        // Usar o mesmo caminho base configurado em paths.php
                        $oldPath = $uploadsBasePath . '/' . $tenantId . '/logo/' . basename($oldLogo);
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
        }
        
        // Remover logo se solicitado
        if (isset($_POST['remove_logo']) && $_POST['remove_logo'] === '1') {
            $oldLogo = ThemeConfig::get('logo_url', '');
            if (!empty($oldLogo)) {
                $oldPath = dirname(__DIR__, 3) . '/public' . $oldLogo;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
                ThemeConfig::set('logo_url', '');
            }
        }
        
        // Salvar páginas institucionais
        if (isset($_POST['pages']) && is_array($_POST['pages'])) {
            // Whitelist de tags HTML permitidas para prevenir XSS, mantendo formatação básica
            $allowedTags = '<p><h1><h2><h3><h4><h5><h6><strong><b><em><i><u><ul><ol><li><a><br><hr><div><span>';
            
            // Obter páginas atuais para fazer merge
            $currentPages = ThemeConfig::getPages();
            
            $pages = [];
            foreach ($_POST['pages'] as $slug => $pageData) {
                if (isset($pageData['title'])) {
                    $page = [
                        'title' => trim($pageData['title'] ?? ''),
                    ];
                    
                    // Tratamento especial para FAQ (com items)
                    if ($slug === 'faq') {
                        $page['intro'] = !empty($pageData['intro']) ? strip_tags(trim($pageData['intro']), $allowedTags) : '';
                        
                        // Processar items do FAQ
                        $itemsInput = $pageData['items'] ?? [];
                        $normalizedItems = [];
                        
                        if (is_array($itemsInput)) {
                            foreach ($itemsInput as $item) {
                                $question = trim($item['question'] ?? '');
                                $answer = trim($item['answer'] ?? '');
                                
                                // Ignorar linhas totalmente vazias
                                if ($question === '' && $answer === '') {
                                    continue;
                                }
                                
                                $normalizedItems[] = [
                                    'question' => $question,
                                    'answer' => !empty($answer) ? strip_tags($answer, $allowedTags) : '',
                                ];
                            }
                        }
                        
                        $page['items'] = array_values($normalizedItems);
                    } else {
                        // Outras páginas (content ou intro)
                        $page['content'] = !empty($pageData['content']) ? strip_tags(trim($pageData['content']), $allowedTags) : '';
                        $page['intro'] = !empty($pageData['intro']) ? strip_tags(trim($pageData['intro']), $allowedTags) : '';
                    }
                    
                    $pages[$slug] = $page;
                }
            }
            
            // Fazer merge com páginas existentes para não perder dados de outras páginas
            $mergedPages = array_merge($currentPages, $pages);
            
            if (!empty($pages)) {
                ThemeConfig::setPages($mergedPages);
            }
        }
        
        // Salvar configuração do footer
        if (isset($_POST['footer_sections']) && is_array($_POST['footer_sections'])) {
            // Obter defaults para preservar rotas
            $defaultFooter = ThemeConfig::getFooterConfig();
            $footer = ['sections' => []];
            
            foreach ($_POST['footer_sections'] as $sectionKey => $sectionData) {
                if (!isset($sectionData['enabled'])) {
                    continue;
                }
                
                $section = [
                    'title' => trim($sectionData['title'] ?? ''),
                    'enabled' => isset($sectionData['enabled']) ? true : false,
                ];
                
                // Seção de categorias tem limit
                if ($sectionKey === 'categorias' && isset($sectionData['limit'])) {
                    $section['limit'] = (int)$sectionData['limit'];
                }
                
                // Outras seções têm links
                if (in_array($sectionKey, ['ajuda', 'minha_conta', 'institucional']) && isset($sectionData['links'])) {
                    $section['links'] = [];
                    $defaultLinks = $defaultFooter['sections'][$sectionKey]['links'] ?? [];
                    
                    foreach ($sectionData['links'] as $linkKey => $linkData) {
                        if (isset($linkData['enabled'])) {
                            $section['links'][$linkKey] = [
                                'label' => trim($linkData['label'] ?? ''),
                                'enabled' => isset($linkData['enabled']) ? true : false,
                                'route' => $defaultLinks[$linkKey]['route'] ?? '', // Preservar route dos defaults
                            ];
                        }
                    }
                }
                
                $footer['sections'][$sectionKey] = $section;
            }
            
            ThemeConfig::setFooterConfig($footer);
        }
        
        ThemeConfig::clearCache();

        $this->redirect('/admin/tema?success=1');
    }
}


