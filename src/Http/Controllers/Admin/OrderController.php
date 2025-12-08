<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class OrderController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de filtro
        $status = $_GET['status'] ?? '';
        $q = trim($_GET['q'] ?? '');
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;

        // Montar query
        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($status) && $status !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        if (!empty($q)) {
            $where[] = '(numero_pedido LIKE :q OR cliente_nome LIKE :q OR cliente_email LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereClause = implode(' AND ', $where);

        // Contar total
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM pedidos 
            WHERE {$whereClause}
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Buscar pedidos
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE {$whereClause}
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $pedidos = $stmt->fetchAll();

        $totalPages = ceil($total / $perPage);

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/orders/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Pedidos',
            'pedidos' => $pedidos,
            'filtros' => [
                'status' => $status,
                'q' => $q,
            ],
            'paginacao' => [
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'hasPrev' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
            ],
        ]);
    }

    public function show(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Pedido não encontrado']);
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE tenant_id = :tenant_id 
            AND pedido_id = :pedido_id
            ORDER BY id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'pedido_id' => $pedido['id']
        ]);
        $itens = $stmt->fetchAll();

        // Status disponíveis
        $statusDisponiveis = ['pending', 'paid', 'canceled', 'shipped', 'completed'];

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/orders/show-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Pedido #' . $pedido['numero_pedido'],
            'pedido' => $pedido,
            'itens' => $itens,
            'statusDisponiveis' => $statusDisponiveis,
        ]);
    }

    public function updateStatus(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $novoStatus = $_POST['status'] ?? '';
        $statusValidos = ['pending', 'paid', 'canceled', 'shipped', 'completed'];

        if (!in_array($novoStatus, $statusValidos)) {
            $this->redirect("/admin/pedidos/{$id}?error=status_invalido");
            return;
        }

        // Verificar se pedido existe e pertence ao tenant
        $stmt = $db->prepare("
            SELECT id FROM pedidos 
            WHERE id = :id 
            AND tenant_id = :tenant_id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        
        if (!$stmt->fetch()) {
            $this->redirect("/admin/pedidos?error=pedido_nao_encontrado");
            return;
        }

        // Atualizar status
        $stmt = $db->prepare("
            UPDATE pedidos 
            SET status = :status, updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'status' => $novoStatus,
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);

        $this->redirect("/admin/pedidos/{$id}?success=status_atualizado");
    }
}


