<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class ProductReviewController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de filtro
        $status = $_GET['status'] ?? '';
        $produtoId = !empty($_GET['produto_id']) ? (int)$_GET['produto_id'] : null;
        $nota = !empty($_GET['nota']) ? (int)$_GET['nota'] : null;
        $q = trim($_GET['q'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        // Montar query base
        $where = ['pa.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];
        $joins = [];

        // Filtro por status
        if (!empty($status) && in_array($status, ['pendente', 'aprovado', 'rejeitado'])) {
            $where[] = 'pa.status = :status';
            $params['status'] = $status;
        }

        // Filtro por produto
        if ($produtoId !== null) {
            $where[] = 'pa.produto_id = :produto_id';
            $params['produto_id'] = $produtoId;
        }

        // Filtro por nota
        if ($nota !== null && $nota >= 1 && $nota <= 5) {
            $where[] = 'pa.nota = :nota';
            $params['nota'] = $nota;
        }

        // Busca por nome do produto ou cliente
        if (!empty($q)) {
            $joins[] = "LEFT JOIN produtos p ON p.id = pa.produto_id AND p.tenant_id = pa.tenant_id";
            $joins[] = "LEFT JOIN customers c ON c.id = pa.customer_id AND c.tenant_id = pa.tenant_id";
            $where[] = "(p.nome LIKE :q OR c.name LIKE :q OR c.email LIKE :q)";
            $params['q'] = '%' . $q . '%';
        } else {
            $joins[] = "LEFT JOIN produtos p ON p.id = pa.produto_id AND p.tenant_id = pa.tenant_id";
            $joins[] = "LEFT JOIN customers c ON c.id = pa.customer_id AND c.tenant_id = pa.tenant_id";
        }

        $whereClause = implode(' AND ', $where);
        $joinClause = !empty($joins) ? implode(' ', $joins) : '';

        // Contar total
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM produto_avaliacoes pa
            {$joinClause}
            WHERE {$whereClause}
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        // Calcular paginação
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        // Buscar avaliações
        $stmt = $db->prepare("
            SELECT 
                pa.*,
                p.nome as produto_nome,
                p.slug as produto_slug,
                c.name as customer_name,
                c.email as customer_email
            FROM produto_avaliacoes pa
            {$joinClause}
            WHERE {$whereClause}
            ORDER BY pa.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Buscar produtos para filtro
        $stmt = $db->prepare("
            SELECT id, nome FROM produtos 
            WHERE tenant_id = :tenant_id 
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $produtos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $filtros = [
            'status' => $status,
            'produto_id' => $produtoId,
            'nota' => $nota,
            'q' => $q,
        ];

        $paginacao = [
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $totalPages,
        ];

        $message = $_SESSION['review_message'] ?? null;
        $messageType = $_SESSION['review_message_type'] ?? 'success';
        unset($_SESSION['review_message'], $_SESSION['review_message_type']);

        $this->viewWithLayout('admin/layouts/store', 'admin/product-reviews/index-content', [
            'pageTitle' => 'Avaliações de Produtos',
            'avaliacoes' => $avaliacoes,
            'produtos' => $produtos,
            'filtros' => $filtros,
            'paginacao' => $paginacao,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function show(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar avaliação
        $stmt = $db->prepare("
            SELECT 
                pa.*,
                p.nome as produto_nome,
                p.slug as produto_slug,
                c.name as customer_name,
                c.email as customer_email,
                ped.numero_pedido
            FROM produto_avaliacoes pa
            LEFT JOIN produtos p ON p.id = pa.produto_id AND p.tenant_id = pa.tenant_id
            LEFT JOIN customers c ON c.id = pa.customer_id AND c.tenant_id = pa.tenant_id
            LEFT JOIN pedidos ped ON ped.id = pa.pedido_id AND ped.tenant_id = pa.tenant_id
            WHERE pa.id = :id 
            AND pa.tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);
        $avaliacao = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$avaliacao) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Avaliação não encontrada']);
            return;
        }

        $this->viewWithLayout('admin/layouts/store', 'admin/product-reviews/show-content', [
            'pageTitle' => 'Avaliação #' . $id,
            'avaliacao' => $avaliacao,
        ]);
    }

    public function approve(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE produto_avaliacoes 
            SET status = 'aprovado', updated_at = NOW()
            WHERE id = :id 
            AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['review_message'] = 'Avaliação aprovada com sucesso!';
            $_SESSION['review_message_type'] = 'success';
        } else {
            $_SESSION['review_message'] = 'Avaliação não encontrada.';
            $_SESSION['review_message_type'] = 'error';
        }

        $this->redirect('/admin/avaliacoes');
    }

    public function reject(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            UPDATE produto_avaliacoes 
            SET status = 'rejeitado', updated_at = NOW()
            WHERE id = :id 
            AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['review_message'] = 'Avaliação rejeitada.';
            $_SESSION['review_message_type'] = 'success';
        } else {
            $_SESSION['review_message'] = 'Avaliação não encontrada.';
            $_SESSION['review_message_type'] = 'error';
        }

        $this->redirect('/admin/avaliacoes');
    }
}


