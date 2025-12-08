<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\ThemeConfig;
use App\Services\CartService;

class HomeController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $tenant = TenantContext::tenant();
        $db = Database::getConnection();

        // Carregar configurações do tema
        $theme = [
            // Cores
            'color_primary' => ThemeConfig::getColor('theme_color_primary', '#2E7D32'),
            'color_secondary' => ThemeConfig::getColor('theme_color_secondary', '#F7931E'),
            'color_topbar_bg' => ThemeConfig::getColor('theme_color_topbar_bg', '#1a1a1a'),
            'color_topbar_text' => ThemeConfig::getColor('theme_color_topbar_text', '#ffffff'),
            'color_header_bg' => ThemeConfig::getColor('theme_color_header_bg', '#ffffff'),
            'color_header_text' => ThemeConfig::getColor('theme_color_header_text', '#333333'),
            'color_footer_bg' => ThemeConfig::getColor('theme_color_footer_bg', '#1a1a1a'),
            'color_footer_text' => ThemeConfig::getColor('theme_color_footer_text', '#ffffff'),
            
            // Textos
            'topbar_text' => ThemeConfig::get('topbar_text', 'Frete grátis acima de R$ 299 | Troca garantida em até 7 dias | Outlet de golfe'),
            'newsletter_title' => ThemeConfig::get('newsletter_title', 'Receba nossas ofertas'),
            'newsletter_subtitle' => ThemeConfig::get('newsletter_subtitle', 'Cadastre-se e receba promoções exclusivas'),
            
            // Contato
            'footer_phone' => ThemeConfig::get('footer_phone', ''),
            'footer_whatsapp' => ThemeConfig::get('footer_whatsapp', ''),
            'footer_email' => ThemeConfig::get('footer_email', ''),
            'footer_address' => ThemeConfig::get('footer_address', ''),
            
            // Redes sociais
            'footer_social_instagram' => ThemeConfig::get('footer_social_instagram', ''),
            'footer_social_facebook' => ThemeConfig::get('footer_social_facebook', ''),
            'footer_social_youtube' => ThemeConfig::get('footer_social_youtube', ''),
            
            // Menu
            'menu_main' => ThemeConfig::getMainMenu(),
        ];

        // Primeiro, tentar buscar produtos em destaque
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE tenant_id = :tenant_id 
            AND status = 'publish'
            AND destaque = 1
            ORDER BY data_criacao DESC 
            LIMIT 8
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $produtos = $stmt->fetchAll();

        // Se não encontrou produtos em destaque, buscar qualquer produto publicado
        if (empty($produtos)) {
            $stmt = $db->prepare("
                SELECT * FROM produtos 
                WHERE tenant_id = :tenant_id 
                AND status = 'publish'
                ORDER BY data_criacao DESC 
                LIMIT 8
            ");
            $stmt->execute(['tenant_id' => $tenantId]);
            $produtos = $stmt->fetchAll();
        }

        // Buscar imagem principal para cada produto
        $produtosDestaque = [];
        foreach ($produtos as $produto) {
            $stmtImg = $db->prepare("
                SELECT * FROM produto_imagens 
                WHERE tenant_id = :tenant_id 
                AND produto_id = :produto_id 
                ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                LIMIT 1
            ");
            $stmtImg->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produto['id']
            ]);
            $imagem = $stmtImg->fetch();
            
            $produto['imagem_principal'] = $imagem ? $imagem : null;
            $produtosDestaque[] = $produto;
        }

        // Buscar bolotas de categorias (faixa de categorias)
        $stmt = $db->prepare("
            SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
            FROM home_category_pills hcp
            LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
            WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
            ORDER BY hcp.ordem ASC, hcp.id ASC
        ");
        $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $categoryPills = $stmt->fetchAll();

        // Buscar seções de categorias
        $stmt = $db->prepare("
            SELECT hcs.*, c.nome as categoria_nome, c.slug as categoria_slug
            FROM home_category_sections hcs
            LEFT JOIN categorias c ON c.id = hcs.categoria_id AND c.tenant_id = :tenant_id_join
            WHERE hcs.tenant_id = :tenant_id_where AND hcs.ativo = 1
            ORDER BY hcs.ordem ASC, hcs.id ASC
        ");
        $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $sections = $stmt->fetchAll();

        // Buscar produtos para cada seção
        $sectionsComProdutos = [];
        foreach ($sections as $section) {
            if ($section['categoria_id'] > 0) {
                // Buscar produtos da categoria
                $stmt = $db->prepare("
                    SELECT p.*
                    FROM produtos p
                    INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id
                    WHERE p.tenant_id = :tenant_id
                    AND p.status = 'publish'
                    AND pc.categoria_id = :categoria_id
                    ORDER BY p.data_criacao DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
                $stmt->bindValue(':categoria_id', $section['categoria_id'], \PDO::PARAM_INT);
                $stmt->bindValue(':limit', $section['quantidade_produtos'], \PDO::PARAM_INT);
                $stmt->execute();
                $produtosSecao = $stmt->fetchAll();

                // Buscar imagem principal para cada produto
                foreach ($produtosSecao as &$produto) {
                    $stmtImg = $db->prepare("
                        SELECT * FROM produto_imagens 
                        WHERE tenant_id = :tenant_id 
                        AND produto_id = :produto_id 
                        ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                        LIMIT 1
                    ");
                    $stmtImg->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produto['id']
                    ]);
                    $imagem = $stmtImg->fetch();
                    $produto['imagem_principal'] = $imagem ? $imagem : null;
                }

                $section['produtos'] = $produtosSecao;
            } else {
                $section['produtos'] = [];
            }
            $sectionsComProdutos[] = $section;
        }

        // Buscar banners hero
        $stmt = $db->prepare("
            SELECT * FROM banners
            WHERE tenant_id = :tenant_id AND tipo = 'hero' AND ativo = 1
            ORDER BY ordem ASC, id ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $heroBanners = $stmt->fetchAll();

        // Buscar banners retrato
        $stmt = $db->prepare("
            SELECT * FROM banners
            WHERE tenant_id = :tenant_id AND tipo = 'portrait' AND ativo = 1
            ORDER BY ordem ASC, id ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $portraitBanners = $stmt->fetchAll();

        // Dados do carrinho para o header
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        $this->view('storefront/home', [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'produtosDestaque' => $produtosDestaque,
            'categoryPills' => $categoryPills,
            'sections' => $sectionsComProdutos,
            'heroBanners' => $heroBanners,
            'portraitBanners' => $portraitBanners,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }
}

