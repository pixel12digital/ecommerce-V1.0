<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\ThemeConfig;
use App\Services\CartService;

class ProductController extends Controller
{
    public function index(): void
    {
        $this->renderProductList();
    }

    public function category(string $slugCategoria): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar categoria por slug
        $stmt = $db->prepare("
            SELECT * FROM categorias 
            WHERE tenant_id = :tenant_id AND slug = :slug
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'slug' => $slugCategoria]);
        $categoria = $stmt->fetch();

        if (!$categoria) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Categoria não encontrada']);
            return;
        }

        $this->renderProductList($categoria['id'], $categoria);
    }

    private function renderProductList(?int $categoriaId = null, ?array $categoriaAtual = null): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de filtro
        $q = trim($_GET['q'] ?? '');
        $precoMin = !empty($_GET['preco_min']) ? (float)$_GET['preco_min'] : null;
        $precoMax = !empty($_GET['preco_max']) ? (float)$_GET['preco_max'] : null;
        $ordenar = $_GET['ordenar'] ?? 'novidades';
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 12;
        $offset = ($currentPage - 1) * $perPage;

        // Validar ordenação
        $ordenacoesValidas = ['novidades', 'menor_preco', 'maior_preco', 'mais_vendidos'];
        if (!in_array($ordenar, $ordenacoesValidas)) {
            $ordenar = 'novidades';
        }

        // Montar query base
        $where = ['p.tenant_id = :tenant_id', "p.status = 'publish'", "p.exibir_no_catalogo = 1"];
        $params = ['tenant_id' => $tenantId];
        $joins = [];
        
        // Verificar configuração de ocultar produtos com estoque zero
        $ocultarEstoqueZero = \App\Services\ThemeConfig::get('catalogo_ocultar_estoque_zero', '0');
        if ($ocultarEstoqueZero === '1') {
            // Ocultar produtos que gerenciam estoque e estão com quantidade 0
            // Produtos que não gerenciam estoque continuam aparecendo
            $where[] = "(p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
        }

        // Filtro por categoria (inclui subcategorias automaticamente)
        if ($categoriaId !== null || !empty($_GET['categoria'])) {
            // Buscar categoria por ID ou slug
            $categoriaInfo = null;
            if ($categoriaId !== null) {
                $stmt = $db->prepare("SELECT id, categoria_pai_id FROM categorias WHERE tenant_id = :tenant_id AND id = :categoria_id LIMIT 1");
                $stmt->execute(['tenant_id' => $tenantId, 'categoria_id' => $categoriaId]);
                $categoriaInfo = $stmt->fetch();
            } else {
                $categoriaSlug = $_GET['categoria'];
                $stmt = $db->prepare("SELECT id, categoria_pai_id FROM categorias WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
                $stmt->execute(['tenant_id' => $tenantId, 'slug' => $categoriaSlug]);
                $categoriaInfo = $stmt->fetch();
                if ($categoriaInfo) {
                    $categoriaId = $categoriaInfo['id'];
                }
            }
            
            if ($categoriaInfo) {
                // Se $categoriaAtual não foi passada mas temos categoria via GET, buscar dados completos
                if (!$categoriaAtual && !empty($_GET['categoria'])) {
                    $stmt = $db->prepare("SELECT * FROM categorias WHERE tenant_id = :tenant_id AND id = :categoria_id LIMIT 1");
                    $stmt->execute(['tenant_id' => $tenantId, 'categoria_id' => $categoriaInfo['id']]);
                    $categoriaAtual = $stmt->fetch();
                }
                
                // Buscar subcategorias (filhos diretos)
                $stmt = $db->prepare("SELECT id FROM categorias WHERE tenant_id = :tenant_id AND categoria_pai_id = :categoria_pai_id");
                $stmt->execute(['tenant_id' => $tenantId, 'categoria_pai_id' => $categoriaInfo['id']]);
                $subcategorias = $stmt->fetchAll();
                $subcategoriaIds = array_column($subcategorias, 'id');
                
                // Lista de IDs de categorias para buscar produtos (pai + filhos)
                $categoriaIds = array_merge([$categoriaInfo['id']], $subcategoriaIds);
                
                // Montar IN clause com placeholders
                $placeholders = [];
                foreach ($categoriaIds as $idx => $catId) {
                    $key = "categoria_id_{$idx}";
                    $placeholders[] = ":{$key}";
                    $params[$key] = $catId;
                }
                
                // JOIN com produto_categorias usando IN para incluir pai + filhos
                $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
                $where[] = "pc.categoria_id IN (" . implode(',', $placeholders) . ")";
                $params['tenant_id_pc'] = $tenantId;
            }
        }

        // Filtro de busca
        if (!empty($q)) {
            $where[] = "(p.nome LIKE :q OR p.sku LIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        // Filtro de preço
        if ($precoMin !== null || $precoMax !== null) {
            $precoCondition = "COALESCE(p.preco_promocional, p.preco_regular)";
            if ($precoMin !== null && $precoMax !== null) {
                $where[] = "{$precoCondition} BETWEEN :preco_min AND :preco_max";
                $params['preco_min'] = $precoMin;
                $params['preco_max'] = $precoMax;
            } elseif ($precoMin !== null) {
                $where[] = "{$precoCondition} >= :preco_min";
                $params['preco_min'] = $precoMin;
            } elseif ($precoMax !== null) {
                $where[] = "{$precoCondition} <= :preco_max";
                $params['preco_max'] = $precoMax;
            }
        }

        $whereClause = implode(' AND ', $where);
        $joinClause = !empty($joins) ? implode(' ', $joins) : '';

        // Ordenação
        $orderBy = match($ordenar) {
            'menor_preco' => 'ORDER BY COALESCE(p.preco_promocional, p.preco_regular) ASC',
            'maior_preco' => 'ORDER BY COALESCE(p.preco_promocional, p.preco_regular) DESC',
            'mais_vendidos' => 'ORDER BY p.data_criacao DESC', // Placeholder até ter métrica real
            default => 'ORDER BY p.data_criacao DESC'
        };

        // Contar total
        $countSql = "
            SELECT COUNT(DISTINCT p.id) as total 
            FROM produtos p
            {$joinClause}
            WHERE {$whereClause}
        ";
        $stmt = $db->prepare($countSql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Buscar produtos
        $sql = "
            SELECT DISTINCT p.*
            FROM produtos p
            {$joinClause}
            WHERE {$whereClause}
            {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $produtos = $stmt->fetchAll();

        // Buscar imagem principal para cada produto
        foreach ($produtos as &$produto) {
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

        // Buscar categorias para filtro
        $stmt = $db->prepare("
            SELECT * FROM categorias 
            WHERE tenant_id = :tenant_id 
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFiltro = $stmt->fetchAll();

        // Carregar tema completo para breadcrumb e header
        $theme = ThemeConfig::getFullThemeConfig();
        
        // Garantir propriedades básicas se não existirem
        if (empty($theme['color_primary'])) {
            $theme['color_primary'] = ThemeConfig::getColor('theme_color_primary', '#2E7D32');
        }
        if (empty($theme['color_secondary'])) {
            $theme['color_secondary'] = ThemeConfig::getColor('theme_color_secondary', '#F7931E');
        }
        if (empty($theme['color_topbar_bg'])) {
            $theme['color_topbar_bg'] = ThemeConfig::getColor('theme_color_topbar_bg', '#1a1a1a');
        }
        if (empty($theme['color_topbar_text'])) {
            $theme['color_topbar_text'] = ThemeConfig::getColor('theme_color_topbar_text', '#ffffff');
        }
        if (empty($theme['color_header_bg'])) {
            $theme['color_header_bg'] = ThemeConfig::getColor('theme_color_header_bg', '#ffffff');
        }
        if (empty($theme['color_header_text'])) {
            $theme['color_header_text'] = ThemeConfig::getColor('theme_color_header_text', '#333333');
        }
        if (empty($theme['logo_url'])) {
            $theme['logo_url'] = ThemeConfig::get('logo_url', '');
        }
        if (empty($theme['menu_main'])) {
            $theme['menu_main'] = ThemeConfig::getMainMenu();
        }

        $totalPages = ceil($total / $perPage);

        // Dados do carrinho para o header
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();
        
        // Dados do tenant para o layout base
        $tenant = TenantContext::tenant();

        $this->view('storefront/products/index', [
            'produtos' => $produtos,
            'categoriasFiltro' => $categoriasFiltro,
            'categoriaAtual' => $categoriaAtual,
            'filtrosAtuais' => [
                'q' => $q,
                'categoria' => $categoriaAtual ? $categoriaAtual['slug'] : ($_GET['categoria'] ?? ''),
                'preco_min' => $precoMin,
                'preco_max' => $precoMax,
                'ordenar' => $ordenar,
            ],
            'paginacao' => [
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'hasPrev' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
                'perPage' => $perPage
            ],
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    public function show(string $slug): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar produto
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE tenant_id = :tenant_id 
            AND slug = :slug 
            AND status = 'publish'
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'slug' => $slug
        ]);
        $produto = $stmt->fetch();

        if (!$produto) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Produto não encontrado']);
            return;
        }

        // Buscar todas as imagens
        $stmt = $db->prepare("
            SELECT * FROM produto_imagens 
            WHERE tenant_id = :tenant_id 
            AND produto_id = :produto_id 
            ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $imagens = $stmt->fetchAll();

        // Buscar categorias
        $stmt = $db->prepare("
            SELECT c.* 
            FROM categorias c
            JOIN produto_categorias pc ON pc.categoria_id = c.id
            WHERE pc.tenant_id = :tenant_id_pc
            AND c.tenant_id = :tenant_id_c
            AND pc.produto_id = :produto_id
            ORDER BY c.nome ASC
        ");
        $stmt->execute([
            'tenant_id_pc' => $tenantId,
            'tenant_id_c' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $categorias = $stmt->fetchAll();

        // Buscar vídeos do produto
        $videosRaw = $this->getVideosByProductId($db, $tenantId, $produto['id']);
        
        // Processar vídeos: adicionar informações de embed e thumbnails
        $videos = [];
        foreach ($videosRaw as $video) {
            $videoInfo = $this->processVideoInfo($video['url']);
            $videos[] = array_merge($video, [
                'tipo' => $videoInfo['type'],
                'embed_url' => $videoInfo['embed_url'],
                'thumb_url' => $videoInfo['thumb_url'],
            ]);
        }

        // Buscar atributos e variações (se produto variável)
        $atributos = [];
        $variacoes = [];
        
        if ($produto['tipo'] === 'variable') {
            // Buscar atributos usados para variação
            $stmt = $db->prepare("
                SELECT a.id as atributo_id, a.nome as atributo_nome, a.slug as atributo_slug, a.tipo as atributo_tipo
                FROM produto_atributos pa
                INNER JOIN atributos a ON a.id = pa.atributo_id
                WHERE pa.produto_id = :produto_id 
                AND pa.tenant_id = :tenant_id
                AND pa.usado_para_variacao = 1
                ORDER BY pa.ordem ASC, a.ordem ASC
            ");
            $stmt->execute(['produto_id' => $produto['id'], 'tenant_id' => $tenantId]);
            $atributosRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Para cada atributo, buscar termos disponíveis
            foreach ($atributosRaw as $attr) {
                $stmtTermos = $db->prepare("
                    SELECT at.id as termo_id, at.nome as termo_nome, at.slug as termo_slug, 
                           at.valor_cor, at.imagem as swatch_image, pat.imagem_produto
                    FROM produto_atributo_termos pat
                    INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
                    WHERE pat.produto_id = :produto_id
                    AND pat.atributo_id = :atributo_id
                    AND pat.tenant_id = :tenant_id
                    ORDER BY at.ordem ASC, at.nome ASC
                ");
                $stmtTermos->execute([
                    'produto_id' => $produto['id'],
                    'atributo_id' => $attr['atributo_id'],
                    'tenant_id' => $tenantId
                ]);
                $termos = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);
                
                $atributos[] = [
                    'atributo_id' => (int)$attr['atributo_id'],
                    'atributo_nome' => $attr['atributo_nome'],
                    'atributo_slug' => $attr['atributo_slug'],
                    'atributo_tipo' => $attr['atributo_tipo'],
                    'termos' => $termos
                ];
            }

            // Buscar todas as variações do produto
            $stmt = $db->prepare("
                SELECT pv.*
                FROM produto_variacoes pv
                WHERE pv.produto_id = :produto_id 
                AND pv.tenant_id = :tenant_id
                AND pv.status = 'publish'
                ORDER BY pv.id ASC
            ");
            $stmt->execute(['produto_id' => $produto['id'], 'tenant_id' => $tenantId]);
            $variacoesRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Para cada variação, buscar atributos e montar assinatura
            foreach ($variacoesRaw as $variacao) {
                $stmtAttr = $db->prepare("
                    SELECT pva.atributo_id, pva.atributo_termo_id
                    FROM produto_variacao_atributos pva
                    WHERE pva.variacao_id = :variacao_id AND pva.tenant_id = :tenant_id
                    ORDER BY pva.atributo_id ASC
                ");
                $stmtAttr->execute(['variacao_id' => $variacao['id'], 'tenant_id' => $tenantId]);
                $attrs = $stmtAttr->fetchAll(\PDO::FETCH_ASSOC);
                
                // Montar assinatura (ordenada por atributo_id para garantir consistência)
                // A query já ordena por atributo_id, mas garantimos aqui também
                usort($attrs, function($a, $b) {
                    return $a['atributo_id'] <=> $b['atributo_id'];
                });
                $signatureParts = [];
                foreach ($attrs as $attr) {
                    $signatureParts[] = $attr['atributo_id'] . ':' . $attr['atributo_termo_id'];
                }
                $signature = implode('|', $signatureParts);
                
                // Determinar preço
                $precoRegular = $variacao['preco_regular'] ?? $produto['preco_regular'];
                $precoPromocional = $variacao['preco_promocional'] ?? $produto['preco_promocional'];
                $precoFinal = $precoPromocional ?: $precoRegular;
                
                // Buscar imagem por cor (se variação tiver cor)
                $imagemPorCor = null;
                foreach ($attrs as $attrVar) {
                    $stmtCor = $db->prepare("
                        SELECT pat.imagem_produto
                        FROM produto_atributo_termos pat
                        INNER JOIN atributos a ON a.id = pat.atributo_id
                        WHERE pat.produto_id = :produto_id
                        AND pat.atributo_id = :atributo_id
                        AND pat.atributo_termo_id = :termo_id
                        AND pat.tenant_id = :tenant_id
                        AND a.tipo = 'color'
                        AND pat.imagem_produto IS NOT NULL
                        LIMIT 1
                    ");
                    $stmtCor->execute([
                        'produto_id' => $produto['id'],
                        'atributo_id' => $attrVar['atributo_id'],
                        'termo_id' => $attrVar['atributo_termo_id'],
                        'tenant_id' => $tenantId
                    ]);
                    $corResult = $stmtCor->fetch();
                    if ($corResult && !empty($corResult['imagem_produto'])) {
                        $imagemPorCor = $corResult['imagem_produto'];
                        break;
                    }
                }

                $variacoes[] = [
                    'variacao_id' => (int)$variacao['id'],
                    'signature' => $signature,
                    'price_regular' => (float)$precoRegular,
                    'price_promo' => $precoPromocional ? (float)$precoPromocional : null,
                    'price_final' => (float)$precoFinal,
                    'manage_stock' => (int)($variacao['gerencia_estoque'] ?? 0),
                    'qty' => (int)($variacao['quantidade_estoque'] ?? 0),
                    'backorder' => $variacao['permite_pedidos_falta'] ?? 'no',
                    'image' => $variacao['imagem'] ?? null,
                    'image_by_color' => $imagemPorCor,
                    'status_estoque' => $variacao['status_estoque'] ?? 'instock'
                ];
            }
        }

        // Buscar produtos relacionados
        $produtosRelacionados = [];
        if (!empty($categorias)) {
            $categoriaPrincipalId = $categorias[0]['id'];
            
            $stmt = $db->prepare("
                SELECT DISTINCT p.*
                FROM produtos p
                JOIN produto_categorias pc ON pc.produto_id = p.id
                WHERE p.tenant_id = :tenant_id
                AND p.status = 'publish'
                AND p.exibir_no_catalogo = 1
                AND pc.categoria_id = :categoria_id
                AND pc.tenant_id = :tenant_id_pc
                AND p.id <> :produto_id
                ORDER BY p.data_criacao DESC
                LIMIT 6
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'categoria_id' => $categoriaPrincipalId,
                'tenant_id_pc' => $tenantId,
                'produto_id' => $produto['id']
            ]);
            $produtosRelacionados = $stmt->fetchAll();

            // Buscar imagem principal para cada produto relacionado
            foreach ($produtosRelacionados as &$prodRel) {
                $stmtImg = $db->prepare("
                    SELECT * FROM produto_imagens 
                    WHERE tenant_id = :tenant_id 
                    AND produto_id = :produto_id 
                    ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                    LIMIT 1
                ");
                $stmtImg->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $prodRel['id']
                ]);
                $imagem = $stmtImg->fetch();
                $prodRel['imagem_principal'] = $imagem ? $imagem : null;
            }
        }

        // Carregar tema
        $tenant = TenantContext::tenant();
        $theme = [
            'color_primary' => ThemeConfig::getColor('theme_color_primary', '#2E7D32'),
            'color_secondary' => ThemeConfig::getColor('theme_color_secondary', '#F7931E'),
            'color_header_bg' => ThemeConfig::getColor('theme_color_header_bg', '#ffffff'),
            'color_header_text' => ThemeConfig::getColor('theme_color_header_text', '#333333'),
            'logo_url' => ThemeConfig::get('logo_url', ''),
        ];

        // Buscar avaliações aprovadas do produto
        $stmt = $db->prepare("
            SELECT 
                pa.*,
                c.name as nome_cliente
            FROM produto_avaliacoes pa
            LEFT JOIN customers c ON c.id = pa.customer_id AND c.tenant_id = pa.tenant_id
            WHERE pa.tenant_id = :tenant_id
            AND pa.produto_id = :produto_id
            AND pa.status = 'aprovado'
            ORDER BY pa.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $avaliacoes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular média e total de avaliações
        $stmt = $db->prepare("
            SELECT 
                AVG(nota) as media,
                COUNT(*) as total
            FROM produto_avaliacoes
            WHERE tenant_id = :tenant_id
            AND produto_id = :produto_id
            AND status = 'aprovado'
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $resumo = $stmt->fetch(\PDO::FETCH_ASSOC);
        $avaliacoesResumo = [
            'media' => $resumo['media'] ? (float)$resumo['media'] : 0,
            'total' => (int)$resumo['total']
        ];

        // Verificar se cliente logado pode avaliar
        $clientePodeAvaliar = [
            'permitido' => false,
            'motivo' => null
        ];

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $customerId = (int)$_SESSION['customer_id'];

            // Verificar se já avaliou
            $stmt = $db->prepare("
                SELECT id FROM produto_avaliacoes
                WHERE tenant_id = :tenant_id
                AND produto_id = :produto_id
                AND customer_id = :customer_id
                AND status IN ('pendente', 'aprovado')
                LIMIT 1
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produto['id'],
                'customer_id' => $customerId
            ]);

            if ($stmt->fetch()) {
                $clientePodeAvaliar['permitido'] = false;
                $clientePodeAvaliar['motivo'] = 'já avaliou';
            } else {
                // Verificar se comprou o produto
                $stmt = $db->prepare("
                    SELECT pi.pedido_id
                    FROM pedido_itens pi
                    INNER JOIN pedidos p ON p.id = pi.pedido_id
                    WHERE p.tenant_id = :tenant_id
                    AND p.customer_id = :customer_id
                    AND pi.produto_id = :produto_id
                    AND p.status IN ('paid', 'completed', 'shipped')
                    LIMIT 1
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'produto_id' => $produto['id']
                ]);

                if ($stmt->fetch()) {
                    $clientePodeAvaliar['permitido'] = true;
                } else {
                    $clientePodeAvaliar['permitido'] = false;
                    $clientePodeAvaliar['motivo'] = 'ainda não comprou este produto';
                }
            }
        } else {
            $clientePodeAvaliar['permitido'] = false;
            $clientePodeAvaliar['motivo'] = 'precisa estar logado';
        }

        // Buscar avaliação do cliente atual (se existir)
        $avaliacaoClienteAtual = null;
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $customerId = (int)$_SESSION['customer_id'];
            $stmt = $db->prepare("
                SELECT * FROM produto_avaliacoes
                WHERE tenant_id = :tenant_id
                AND produto_id = :produto_id
                AND customer_id = :customer_id
                LIMIT 1
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produto['id'],
                'customer_id' => $customerId
            ]);
            $avaliacaoClienteAtual = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        }

        // Dados do carrinho para o header
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        // Mensagem flash de avaliação
        $reviewMessage = $_SESSION['review_message'] ?? null;
        $reviewMessageType = $_SESSION['review_message_type'] ?? 'success';
        unset($_SESSION['review_message'], $_SESSION['review_message_type']);

        $this->view('storefront/products/show', [
            'atributos' => $atributos,
            'variacoes' => $variacoes,
            'produto' => $produto,
            'imagens' => $imagens,
            'categorias' => $categorias,
            'videos' => $videos,
            'produtosRelacionados' => $produtosRelacionados,
            'avaliacoes' => $avaliacoes,
            'avaliacoesResumo' => $avaliacoesResumo,
            'clientePodeAvaliar' => $clientePodeAvaliar,
            'avaliacaoClienteAtual' => $avaliacaoClienteAtual,
            'reviewMessage' => $reviewMessage,
            'reviewMessageType' => $reviewMessageType,
            'theme' => $theme,
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    /**
     * Busca vídeos de um produto
     * 
     * @param \PDO $db Conexão com o banco
     * @param int $tenantId ID do tenant
     * @param int $produtoId ID do produto
     * @return array Array de vídeos
     */
    private function getVideosByProductId(\PDO $db, int $tenantId, int $produtoId): array
    {
        $stmt = $db->prepare("
            SELECT * FROM produto_videos 
            WHERE tenant_id = :tenant_id 
            AND produto_id = :produto_id
            AND (ativo = 1 OR ativo IS NULL)
            ORDER BY ordem ASC, id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produtoId
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Processa informações de vídeo (tipo, embed URL, thumbnail)
     * 
     * @param string $url URL do vídeo
     * @return array Array com 'type', 'embed_url', 'thumb_url'
     */
    private function processVideoInfo(string $url): array
    {
        $url = trim($url);
        if (empty($url)) {
            return [
                'type' => 'unknown',
                'embed_url' => '',
                'thumb_url' => ''
            ];
        }
        
        // YouTube
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $videoId = $matches[1];
            return [
                'type' => 'youtube',
                'embed_url' => 'https://www.youtube.com/embed/' . $videoId,
                'thumb_url' => 'https://img.youtube.com/vi/' . $videoId . '/hqdefault.jpg'
            ];
        }
        
        // Vimeo
        if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $matches)) {
            $videoId = $matches[1];
            // Vimeo não fornece thumbnail direto via URL simples, usaremos placeholder
            return [
                'type' => 'vimeo',
                'embed_url' => 'https://player.vimeo.com/video/' . $videoId,
                'thumb_url' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23667eea" width="320" height="180"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="white" font-size="24">Vimeo</text></svg>')
            ];
        }
        
        // MP4 ou outros links diretos
        if (preg_match('/\.(mp4|webm|ogg)(\?.*)?$/i', $url)) {
            return [
                'type' => 'mp4',
                'embed_url' => $url,
                'thumb_url' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23764ba2" width="320" height="180"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="white" font-size="24">MP4</text></svg>')
            ];
        }
        
        // Tentar como MP4 se começar com http/https e não for YouTube/Vimeo
        if (preg_match('/^https?:\/\//', $url)) {
            return [
                'type' => 'mp4',
                'embed_url' => $url,
                'thumb_url' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23764ba2" width="320" height="180"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="white" font-size="24">Vídeo</text></svg>')
            ];
        }
        
        return [
            'type' => 'unknown',
            'embed_url' => $url,
            'thumb_url' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect fill="%23ddd" width="320" height="180"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Vídeo</text></svg>')
        ];
    }
}
