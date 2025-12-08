<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Core\Database;

class PlatformDashboardController extends Controller
{
    public function index(): void
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT id, name, slug, status, plan, created_at FROM tenants ORDER BY id DESC");
        $tenants = $stmt->fetchAll();

        $this->view('admin/platform/dashboard', ['tenants' => $tenants]);
    }

    public function editTenant(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $tenant = $stmt->fetch();

        if (!$tenant) {
            http_response_code(404);
            echo "Tenant não encontrado";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $slug = $_POST['slug'] ?? '';
            $status = $_POST['status'] ?? 'active';
            $plan = $_POST['plan'] ?? 'basic';

            $stmt = $db->prepare("
                UPDATE tenants 
                SET name = :name, slug = :slug, status = :status, plan = :plan 
                WHERE id = :id
            ");
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'status' => $status,
                'plan' => $plan,
                'id' => $id
            ]);

            $this->redirect('/admin/platform');
        }

        $this->view('admin/platform/edit_tenant', ['tenant' => $tenant]);
    }
}



