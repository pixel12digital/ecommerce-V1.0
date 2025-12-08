<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class ProductReviewController extends Controller
{
    public function store(string $slug): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se cliente está logado
        if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
            $_SESSION['review_message'] = 'Você precisa estar logado para avaliar um produto.';
            $_SESSION['review_message_type'] = 'error';
            $this->redirect("/minha-conta/login?redirect=" . urlencode("/produto/{$slug}"));
            return;
        }

        $customerId = (int)$_SESSION['customer_id'];
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar produto por slug
        $stmt = $db->prepare("
            SELECT id FROM produtos 
            WHERE slug = :slug 
            AND tenant_id = :tenant_id 
            AND status = 'publish'
            LIMIT 1
        ");
        $stmt->execute([
            'slug' => $slug,
            'tenant_id' => $tenantId,
        ]);
        $produto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$produto) {
            $_SESSION['review_message'] = 'Produto não encontrado.';
            $_SESSION['review_message_type'] = 'error';
            $this->redirect("/produto/{$slug}");
            return;
        }

        $produtoId = (int)$produto['id'];

        // Validar input
        $nota = isset($_POST['nota']) ? (int)$_POST['nota'] : 0;
        $titulo = trim($_POST['titulo'] ?? '');
        $comentario = trim($_POST['comentario'] ?? '');

        if ($nota < 1 || $nota > 5) {
            $_SESSION['review_message'] = 'Nota inválida. Selecione uma nota de 1 a 5.';
            $_SESSION['review_message_type'] = 'error';
            $this->redirect("/produto/{$slug}");
            return;
        }

        if (strlen($titulo) > 150) {
            $titulo = substr($titulo, 0, 150);
        }

        if (strlen($comentario) > 5000) {
            $comentario = substr($comentario, 0, 5000);
        }

        // Verificar se cliente já comprou este produto
        $stmt = $db->prepare("
            SELECT p.id as pedido_id
            FROM pedido_itens pi
            INNER JOIN pedidos p ON p.id = pi.pedido_id
            WHERE p.tenant_id = :tenant_id
            AND p.customer_id = :customer_id
            AND pi.produto_id = :produto_id
            AND p.status IN ('paid', 'completed', 'shipped')
            ORDER BY p.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'produto_id' => $produtoId,
        ]);
        $compra = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$compra) {
            $_SESSION['review_message'] = 'Você precisa ter comprado este produto para avaliá-lo.';
            $_SESSION['review_message_type'] = 'error';
            $this->redirect("/produto/{$slug}");
            return;
        }

        $pedidoId = $compra['pedido_id'] ?? null;

        // Verificar se já existe avaliação ativa (pendente ou aprovada)
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
            'produto_id' => $produtoId,
            'customer_id' => $customerId,
        ]);

        if ($stmt->fetch()) {
            $_SESSION['review_message'] = 'Você já avaliou este produto.';
            $_SESSION['review_message_type'] = 'error';
            $this->redirect("/produto/{$slug}");
            return;
        }

        // Inserir avaliação
        $stmt = $db->prepare("
            INSERT INTO produto_avaliacoes 
            (tenant_id, produto_id, customer_id, pedido_id, nota, titulo, comentario, status, created_at, updated_at)
            VALUES 
            (:tenant_id, :produto_id, :customer_id, :pedido_id, :nota, :titulo, :comentario, 'pendente', NOW(), NOW())
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produtoId,
            'customer_id' => $customerId,
            'pedido_id' => $pedidoId,
            'nota' => $nota,
            'titulo' => !empty($titulo) ? $titulo : null,
            'comentario' => !empty($comentario) ? $comentario : null,
        ]);

        $_SESSION['review_message'] = 'Avaliação enviada com sucesso! Ela será revisada antes de ser publicada.';
        $_SESSION['review_message_type'] = 'success';
        $this->redirect("/produto/{$slug}");
    }
}


