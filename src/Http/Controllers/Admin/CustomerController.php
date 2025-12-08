<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class CustomerController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de busca e filtros
        $q = trim($_GET['q'] ?? '');
        $dataInicio = trim($_GET['data_inicio'] ?? '');
        $dataFim = trim($_GET['data_fim'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;

        // Montar query base (usando alias c para consistência)
        $where = ['c.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        // Busca por nome, email ou documento
        if (!empty($q)) {
            $where[] = "(c.name LIKE :q OR c.email LIKE :q OR c.document LIKE :q)";
            $params['q'] = '%' . $q . '%';
        }

        // Filtro por data de criação
        if (!empty($dataInicio)) {
            $where[] = "DATE(c.created_at) >= :data_inicio";
            $params['data_inicio'] = $dataInicio;
        }
        if (!empty($dataFim)) {
            $where[] = "DATE(c.created_at) <= :data_fim";
            $params['data_fim'] = $dataFim;
        }

        $whereClause = implode(' AND ', $where);

        // Contar total de registros
        $stmtCount = $db->prepare("
            SELECT COUNT(*) as total 
            FROM customers c
            WHERE {$whereClause}
        ");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Calcular paginação
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        // Buscar clientes
        $stmt = $db->prepare("
            SELECT 
                c.*,
                (SELECT COUNT(*) FROM pedidos p 
                 WHERE p.tenant_id = c.tenant_id 
                 AND p.customer_id = c.id) as total_pedidos
            FROM customers c
            WHERE {$whereClause}
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        // Bind todos os parâmetros
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $clientes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Preparar dados para view
        $filtros = [
            'q' => $q,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];

        $paginacao = [
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'perPage' => $perPage,
            'hasPrev' => $page > 1,
            'hasNext' => $page < $totalPages,
        ];

        $message = $_SESSION['customer_message'] ?? null;
        $messageType = $_SESSION['customer_message_type'] ?? 'success';
        unset($_SESSION['customer_message'], $_SESSION['customer_message_type']);

        $this->viewWithLayout('admin/layouts/store', 'admin/customers/index-content', [
            'pageTitle' => 'Clientes',
            'clientes' => $clientes,
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

        // Buscar cliente
        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE id = :id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
        ]);
        $cliente = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$cliente) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Cliente não encontrado']);
            return;
        }

        // Buscar endereços do cliente
        $stmt = $db->prepare("
            SELECT * FROM customer_addresses 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            ORDER BY is_default DESC, created_at ASC
        ");
        $stmt->execute([
            'customer_id' => $id,
            'tenant_id' => $tenantId,
        ]);
        $enderecos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Buscar pedidos do cliente
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute([
            'customer_id' => $id,
            'tenant_id' => $tenantId,
        ]);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Calcular estatísticas
        $totalPedidos = count($pedidos);
        
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(total_geral), 0) as total_gasto 
            FROM pedidos 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'customer_id' => $id,
            'tenant_id' => $tenantId,
        ]);
        $totalGasto = (float)$stmt->fetchColumn();

        $dataUltimoPedido = null;
        if ($totalPedidos > 0) {
            $dataUltimoPedido = $pedidos[0]['created_at'];
        }

        $estatisticas = [
            'total_pedidos' => $totalPedidos,
            'total_gasto' => $totalGasto,
            'data_ultimo_pedido' => $dataUltimoPedido,
        ];

        $this->viewWithLayout('admin/layouts/store', 'admin/customers/show-content', [
            'pageTitle' => 'Cliente: ' . htmlspecialchars($cliente['name']),
            'cliente' => $cliente,
            'enderecos' => $enderecos,
            'pedidos' => $pedidos,
            'estatisticas' => $estatisticas,
        ]);
    }
}


