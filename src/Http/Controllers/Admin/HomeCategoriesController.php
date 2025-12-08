<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class HomeCategoriesController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar bolotas configuradas
        $stmt = $db->prepare("
            SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
            FROM home_category_pills hcp
            LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
            WHERE hcp.tenant_id = :tenant_id_where
            ORDER BY hcp.ordem ASC, hcp.id ASC
        ");
        $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $pills = $stmt->fetchAll();

        // Buscar todas as categorias para o select
        $stmt = $db->prepare("
            SELECT id, nome, slug
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categorias = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/categories-pills-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Bolotas de Categorias',
            'pills' => $pills,
            'categorias' => $categorias
        ]);
    }

    public function store(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $iconePath = trim($_POST['icone_path'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($categoriaId <= 0) {
            $this->redirect('/admin/home/categorias-pills?error=1');
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO home_category_pills 
            (tenant_id, categoria_id, label, icone_path, ordem, ativo, created_at, updated_at)
            VALUES (:tenant_id, :categoria_id, :label, :icone_path, :ordem, :ativo, NOW(), NOW())
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'categoria_id' => $categoriaId,
            'label' => $label ?: null,
            'icone_path' => $iconePath ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        $this->redirect('/admin/home/categorias-pills?success=1');
    }

    public function edit(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM home_category_pills 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $pill = $stmt->fetch();

        if (!$pill) {
            $this->redirect('/admin/home/categorias-pills?error=2');
            return;
        }

        // Buscar todas as categorias para o select
        $stmt = $db->prepare("
            SELECT id, nome, slug
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categorias = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/categories-pills-edit-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Bolota',
            'pill' => $pill,
            'categorias' => $categorias
        ]);
    }

    public function update(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Verificar se pertence ao tenant
        $stmt = $db->prepare("
            SELECT id FROM home_category_pills 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        if (!$stmt->fetch()) {
            $this->redirect('/admin/home/categorias-pills?error=2');
            return;
        }

        $categoriaId = (int)($_POST['categoria_id'] ?? 0);
        $label = trim($_POST['label'] ?? '');
        $iconePath = trim($_POST['icone_path'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($categoriaId <= 0) {
            $this->redirect('/admin/home/categorias-pills?error=1');
            return;
        }

        $stmt = $db->prepare("
            UPDATE home_category_pills 
            SET categoria_id = :categoria_id,
                label = :label,
                icone_path = :icone_path,
                ordem = :ordem,
                ativo = :ativo,
                updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'categoria_id' => $categoriaId,
            'label' => $label ?: null,
            'icone_path' => $iconePath ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        $this->redirect('/admin/home/categorias-pills?success=1');
    }

    public function destroy(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM home_category_pills 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

        $this->redirect('/admin/home/categorias-pills?success=1');
    }
}


