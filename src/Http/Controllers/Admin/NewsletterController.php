<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class NewsletterController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $q = $_GET['q'] ?? '';

        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($q)) {
            $where[] = '(email LIKE :q OR nome LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT * FROM newsletter_inscricoes 
            WHERE {$whereClause}
            ORDER BY created_at DESC
        ");
        $stmt->execute($params);
        $inscricoes = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/newsletter/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Newsletter',
            'inscricoes' => $inscricoes,
            'filtro' => ['q' => $q]
        ]);
    }
}


