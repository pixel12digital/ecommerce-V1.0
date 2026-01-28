<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\CartService;
use App\Services\ThemeConfig;

class CartController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $tenant = TenantContext::tenant();
        
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
            
            // Menu
            'menu_main' => ThemeConfig::getMainMenu(),
            
            // Logo
            'logo_url' => ThemeConfig::get('logo_url', ''),
            
            // Footer
            'footer_phone' => ThemeConfig::get('footer_phone', ''),
            'footer_whatsapp' => ThemeConfig::get('footer_whatsapp', ''),
            'footer_email' => ThemeConfig::get('footer_email', ''),
            'footer_address' => ThemeConfig::get('footer_address', ''),
            'footer_social_instagram' => ThemeConfig::get('footer_social_instagram', ''),
            'footer_social_facebook' => ThemeConfig::get('footer_social_facebook', ''),
            'footer_social_youtube' => ThemeConfig::get('footer_social_youtube', ''),
        ];

        $cart = CartService::get();
        $subtotal = CartService::getSubtotal();
        $cartTotalItems = CartService::getTotalItems();

        $this->view('storefront/cart/index', [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'cart' => $cart,
            'subtotal' => $subtotal,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $subtotal,
        ]);
    }

    public function add(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
        $variacaoId = !empty($_POST['variacao_id']) ? (int)$_POST['variacao_id'] : null;

        if ($produtoId <= 0) {
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=produto_invalido');
            return;
        }

        // Buscar produto
        $stmt = $db->prepare("
            SELECT id, nome, slug, tipo, preco_regular, preco_promocional,
                   gerencia_estoque, quantidade_estoque, status_estoque, permite_pedidos_falta
            FROM produtos 
            WHERE id = :id 
            AND tenant_id = :tenant_id 
            AND status = 'publish'
            LIMIT 1
        ");
        $stmt->execute(['id' => $produtoId, 'tenant_id' => $tenantId]);
        $produto = $stmt->fetch();

        if (!$produto) {
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=produto_nao_encontrado');
            return;
        }

        // Validação: produto variável requer variacao_id
        if ($produto['tipo'] === 'variable') {
            if (empty($variacaoId) || $variacaoId <= 0) {
                // Buscar nomes dos atributos para mensagem amigável
                $stmtAttrs = $db->prepare("
                    SELECT a.nome
                    FROM produto_atributos pa
                    INNER JOIN atributos a ON a.id = pa.atributo_id
                    WHERE pa.produto_id = :produto_id
                    AND pa.tenant_id = :tenant_id
                    AND pa.usado_para_variacao = 1
                    ORDER BY pa.ordem ASC
                ");
                $stmtAttrs->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);
                $atributosNomes = $stmtAttrs->fetchAll(\PDO::FETCH_COLUMN);
                
                $atributosStr = !empty($atributosNomes) 
                    ? implode(' e ', $atributosNomes) 
                    : 'as opções';
                
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=selecione_' . urlencode($atributosStr));
                return;
            }
        }

        $precoUnitario = 0.0;
        $imagemPath = null;
        $atributosString = '';
        $sku = null;

        // Se tem variação, buscar dados da variação
        if ($variacaoId !== null && $variacaoId > 0) {
            // Validar que variação pertence ao produto e ao tenant
            $stmtVariacao = $db->prepare("
                SELECT pv.*, p.tipo as produto_tipo
                FROM produto_variacoes pv
                INNER JOIN produtos p ON p.id = pv.produto_id
                WHERE pv.id = :variacao_id
                AND pv.tenant_id = :tenant_id
                AND pv.produto_id = :produto_id
                AND pv.status = 'publish'
                LIMIT 1
            ");
            $stmtVariacao->execute([
                'variacao_id' => $variacaoId,
                'tenant_id' => $tenantId,
                'produto_id' => $produtoId
            ]);
            $variacao = $stmtVariacao->fetch();

            if (!$variacao) {
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=variacao_invalida');
                return;
            }

            // Validar estoque da variação
            if ($variacao['gerencia_estoque'] == 1) {
                if ($variacao['quantidade_estoque'] < $quantidade) {
                    if ($variacao['permite_pedidos_falta'] === 'no') {
                        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=estoque_insuficiente');
                        return;
                    }
                }
            }

            // Determinar preço: variação tem prioridade, senão herda do produto
            if ($variacao['preco_promocional'] !== null && $variacao['preco_promocional'] > 0) {
                $precoUnitario = (float)$variacao['preco_promocional'];
            } elseif ($variacao['preco_regular'] !== null && $variacao['preco_regular'] > 0) {
                $precoUnitario = (float)$variacao['preco_regular'];
            } else {
                // Herda do produto
                $precoUnitario = $produto['preco_promocional'] 
                    ? (float)$produto['preco_promocional'] 
                    : (float)$produto['preco_regular'];
            }

            // Buscar SKU da variação (ou do produto se não tiver)
            $sku = $variacao['sku'] ?: $produto['sku'] ?? null;

            // Buscar imagem da variação (ou do produto se não tiver)
            if ($variacao['imagem']) {
                $imagemPath = $variacao['imagem'];
            } else {
                $stmtImg = $db->prepare("
                    SELECT caminho_arquivo 
                    FROM produto_imagens 
                    WHERE tenant_id = :tenant_id 
                    AND produto_id = :produto_id 
                    ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                    LIMIT 1
                ");
                $stmtImg->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId
                ]);
                $imagem = $stmtImg->fetch();
                $imagemPath = $imagem ? $imagem['caminho_arquivo'] : null;
            }

            // Buscar atributos da variação para snapshot
            $stmtAtributos = $db->prepare("
                SELECT a.nome as atributo_nome, at.nome as termo_nome
                FROM produto_variacao_atributos pva
                INNER JOIN atributos a ON a.id = pva.atributo_id
                INNER JOIN atributo_termos at ON at.id = pva.atributo_termo_id
                WHERE pva.variacao_id = :variacao_id
                AND pva.tenant_id = :tenant_id
                ORDER BY a.ordem ASC, at.ordem ASC
            ");
            $stmtAtributos->execute([
                'variacao_id' => $variacaoId,
                'tenant_id' => $tenantId
            ]);
            $atributos = $stmtAtributos->fetchAll();
            
            // Montar string de atributos (ex: "Tamanho: P, Cor: Vermelho")
            $atributosParts = [];
            foreach ($atributos as $attr) {
                $atributosParts[] = "{$attr['atributo_nome']}: {$attr['termo_nome']}";
            }
            $atributosString = implode(', ', $atributosParts);

        } else {
            // Produto simples - validar estoque do produto
            if ($produto['gerencia_estoque'] == 1) {
                if ($produto['quantidade_estoque'] < $quantidade) {
                    if ($produto['permite_pedidos_falta'] === 'no') {
                        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=estoque_insuficiente');
                        return;
                    }
                }
            }

            // Determinar preço unitário
            $precoUnitario = $produto['preco_promocional'] 
                ? (float)$produto['preco_promocional'] 
                : (float)$produto['preco_regular'];

            // Buscar SKU do produto
            $sku = $produto['sku'] ?? null;

            // Buscar imagem principal
            $stmtImg = $db->prepare("
                SELECT caminho_arquivo 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id 
                AND produto_id = :produto_id 
                ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                LIMIT 1
            ");
            $stmtImg->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produtoId
            ]);
            $imagem = $stmtImg->fetch();
            $imagemPath = $imagem ? $imagem['caminho_arquivo'] : null;
        }

        // Montar nome do produto (com atributos se for variação)
        $nomeProduto = $produto['nome'];
        if ($atributosString) {
            $nomeProduto .= " ({$atributosString})";
        }

        // Adicionar ao carrinho
        CartService::addItem($produtoId, [
            'produto_id' => $produtoId,
            'variacao_id' => $variacaoId,
            'nome' => $nomeProduto,
            'slug' => $produto['slug'],
            'preco_unitario' => $precoUnitario,
            'quantidade' => $quantidade,
            'imagem' => $imagemPath,
            'atributos' => $atributosString,
            'sku' => $sku,
        ]);

        // Redirecionar (por padrão volta para a página anterior)
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?added=1');
    }

    public function update(): void
    {
        $itemKey = trim($_POST['item_key'] ?? '');
        $quantidade = (int)($_POST['quantidade'] ?? 0);

        if (empty($itemKey)) {
            $this->redirect('/carrinho?error=item_invalido');
            return;
        }

        CartService::updateItem($itemKey, $quantidade);
        $this->redirect('/carrinho?updated=1');
    }

    public function remove(): void
    {
        $itemKey = trim($_POST['item_key'] ?? '');

        if (empty($itemKey)) {
            $this->redirect('/carrinho?error=item_invalido');
            return;
        }

        CartService::removeItem($itemKey);
        $this->redirect('/carrinho?removed=1');
    }

    public function clear(): void
    {
        CartService::clear();
        $this->redirect('/carrinho?cleared=1');
    }
}


