<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\CartService;

class CartController extends Controller
{
    public function index(): void
    {
        $cart = CartService::get();
        $subtotal = CartService::getSubtotal();

        $this->view('storefront/cart/index', [
            'cart' => $cart,
            'subtotal' => $subtotal,
        ]);
    }

    public function add(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));

        if ($produtoId <= 0) {
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=produto_invalido');
            return;
        }

        // Buscar produto
        $stmt = $db->prepare("
            SELECT id, nome, slug, preco_regular, preco_promocional
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

        // Determinar preço unitário
        $precoUnitario = $produto['preco_promocional'] 
            ? (float)$produto['preco_promocional'] 
            : (float)$produto['preco_regular'];

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

        // Adicionar ao carrinho
        CartService::addItem($produtoId, [
            'produto_id' => $produtoId,
            'nome' => $produto['nome'],
            'slug' => $produto['slug'],
            'preco_unitario' => $precoUnitario,
            'quantidade' => $quantidade,
            'imagem' => $imagemPath,
        ]);

        // Redirecionar (por padrão volta para a página anterior)
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?added=1');
    }

    public function update(): void
    {
        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirect('/carrinho?error=produto_invalido');
            return;
        }

        CartService::updateItem($produtoId, $quantidade);
        $this->redirect('/carrinho?updated=1');
    }

    public function remove(): void
    {
        $produtoId = (int)($_POST['produto_id'] ?? 0);

        if ($produtoId <= 0) {
            $this->redirect('/carrinho?error=produto_invalido');
            return;
        }

        CartService::removeItem($produtoId);
        $this->redirect('/carrinho?removed=1');
    }

    public function clear(): void
    {
        CartService::clear();
        $this->redirect('/carrinho?cleared=1');
    }
}


