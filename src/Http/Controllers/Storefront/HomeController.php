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
            'footer_cnpj' => ThemeConfig::get('footer_cnpj', ''),
            
            // Redes sociais
            'footer_social_instagram' => ThemeConfig::get('footer_social_instagram', ''),
            'footer_social_facebook' => ThemeConfig::get('footer_social_facebook', ''),
            'footer_social_youtube' => ThemeConfig::get('footer_social_youtube', ''),
            
            // Menu
            'menu_main' => ThemeConfig::getMainMenu(),
            
            // Logo
            'logo_url' => ThemeConfig::get('logo_url', ''),
        ];

        // Verificar configuração de ocultar produtos com estoque zero
        $ocultarEstoqueZero = ThemeConfig::get('catalogo_ocultar_estoque_zero', '0');
        $estoqueCondition = '';
        if ($ocultarEstoqueZero === '1') {
            $estoqueCondition = " AND (gerencia_estoque = 0 OR (gerencia_estoque = 1 AND quantidade_estoque > 0))";
        }
        
        // Primeiro, tentar buscar produtos em destaque
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE tenant_id = :tenant_id 
            AND status = 'publish'
            AND exibir_no_catalogo = 1
            AND destaque = 1
            {$estoqueCondition}
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
                AND exibir_no_catalogo = 1
                {$estoqueCondition}
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

        // Buscar todas as categorias para o menu (categorias com produtos visíveis)
        // Critério: categoria tem produtos visíveis OU alguma subcategoria tem produtos visíveis
        $allCategories = $this->getCategoriesWithVisibleProducts($db, $tenantId, $ocultarEstoqueZero);
        
        // Adicionar "Sem Categoria" se houver produtos sem categoria visíveis
        $produtosSemCategoria = $this->getProdutosSemCategoriaCount($db, $tenantId, $ocultarEstoqueZero);
        if ($produtosSemCategoria > 0) {
            array_unshift($allCategories, [
                'categoria_id' => null,
                'categoria_nome' => 'Sem Categoria',
                'categoria_slug' => 'sem-categoria',
                'categoria_pai_id' => null,
                'label' => 'Sem Categoria',
            ]);
        }

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
                $estoqueConditionSection = '';
                if ($ocultarEstoqueZero === '1') {
                    $estoqueConditionSection = " AND (p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
                }
                
                $stmt = $db->prepare("
                    SELECT p.*
                    FROM produtos p
                    INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_join
                    WHERE p.tenant_id = :tenant_id_where
                    AND p.status = 'publish'
                    AND p.exibir_no_catalogo = 1
                    AND pc.categoria_id = :categoria_id
                    {$estoqueConditionSection}
                    ORDER BY p.data_criacao DESC
                    LIMIT :limit
                ");
                $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
                $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
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
            'allCategories' => $allCategories,
            'sections' => $sectionsComProdutos,
            'heroBanners' => $heroBanners,
            'portraitBanners' => $portraitBanners,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    /**
     * Busca todas as categorias que têm produtos visíveis no catálogo
     * Inclui categorias pai se elas têm produtos visíveis OU se alguma subcategoria tem produtos visíveis
     * 
     * @param \PDO $db Conexão com o banco
     * @param int $tenantId ID do tenant
     * @param string $ocultarEstoqueZero Configuração de ocultar estoque zero ('0' ou '1')
     * @return array Lista de categorias com produtos visíveis (incluindo hierarquia)
     */
    private function getCategoriesWithVisibleProducts(\PDO $db, int $tenantId, string $ocultarEstoqueZero): array
    {
        // Condição de estoque (mesma regra do catálogo)
        $estoqueCondition = '';
        if ($ocultarEstoqueZero === '1') {
            $estoqueCondition = " AND (p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
        }

        // Buscar categorias que têm produtos visíveis diretamente
        // OU que têm subcategorias com produtos visíveis
        $sql = "
            SELECT DISTINCT
                c.id,
                c.nome as categoria_nome,
                c.slug as categoria_slug,
                c.categoria_pai_id,
                COALESCE(cp.nome, '') as categoria_pai_nome,
                COALESCE(cp.slug, '') as categoria_pai_slug
            FROM categorias c
            LEFT JOIN categorias cp ON cp.id = c.categoria_pai_id AND cp.tenant_id = :tenant_id_pai
            INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = :tenant_id_pc
            INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = :tenant_id_prod
            WHERE c.tenant_id = :tenant_id_cat
            AND p.status = 'publish'
            AND p.exibir_no_catalogo = 1
            {$estoqueCondition}
            
            UNION
            
            -- Categorias pai que têm subcategorias com produtos visíveis
            SELECT DISTINCT
                cp.id,
                cp.nome as categoria_nome,
                cp.slug as categoria_slug,
                cp.categoria_pai_id,
                COALESCE(cpp.nome, '') as categoria_pai_nome,
                COALESCE(cpp.slug, '') as categoria_pai_slug
            FROM categorias cp
            LEFT JOIN categorias cpp ON cpp.id = cp.categoria_pai_id AND cpp.tenant_id = :tenant_id_pai2
            INNER JOIN categorias c ON c.categoria_pai_id = cp.id AND c.tenant_id = :tenant_id_sub
            INNER JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = :tenant_id_pc2
            INNER JOIN produtos p ON p.id = pc.produto_id AND p.tenant_id = :tenant_id_prod2
            WHERE cp.tenant_id = :tenant_id_cat2
            AND cp.categoria_pai_id IS NULL
            AND p.status = 'publish'
            AND p.exibir_no_catalogo = 1
            {$estoqueCondition}
            
            ORDER BY categoria_pai_id IS NULL DESC, categoria_nome ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':tenant_id_pai', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_pc', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_prod', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_cat', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_pai2', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_sub', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_pc2', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_prod2', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_cat2', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        
        $categorias = $stmt->fetchAll();
        
        // Formatar para o formato esperado pelo menu
        $formatted = [];
        foreach ($categorias as $cat) {
            $formatted[] = [
                'categoria_id' => $cat['id'],
                'categoria_nome' => $cat['categoria_nome'],
                'categoria_slug' => $cat['categoria_slug'],
                'categoria_pai_id' => $cat['categoria_pai_id'],
                'label' => $cat['categoria_nome'], // Para compatibilidade com o template
            ];
        }
        
        return $formatted;
    }

    /**
     * Conta produtos sem categoria que são visíveis no catálogo
     * 
     * @param \PDO $db Conexão com o banco
     * @param int $tenantId ID do tenant
     * @param string $ocultarEstoqueZero Configuração de ocultar estoque zero ('0' ou '1')
     * @return int Número de produtos sem categoria visíveis
     */
    private function getProdutosSemCategoriaCount(\PDO $db, int $tenantId, string $ocultarEstoqueZero): int
    {
        $estoqueCondition = '';
        if ($ocultarEstoqueZero === '1') {
            $estoqueCondition = " AND (gerencia_estoque = 0 OR (gerencia_estoque = 1 AND quantidade_estoque > 0))";
        }

        $sql = "
            SELECT COUNT(DISTINCT p.id) as total
            FROM produtos p
            LEFT JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id
            WHERE p.tenant_id = :tenant_id_prod
            AND p.status = 'publish'
            AND p.exibir_no_catalogo = 1
            AND pc.produto_id IS NULL
            {$estoqueCondition}
        ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_prod', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        
        return (int)($result['total'] ?? 0);
    }
}

