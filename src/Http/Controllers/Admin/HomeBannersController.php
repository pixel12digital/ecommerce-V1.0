<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class HomeBannersController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $tipo = $_GET['tipo'] ?? '';

        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($tipo) && in_array($tipo, ['hero', 'portrait'])) {
            $where[] = 'tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT * FROM banners 
            WHERE {$whereClause}
            ORDER BY tipo ASC, ordem ASC, id ASC
        ");
        $stmt->execute($params);
        $banners = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/banners-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Banners',
            'banners' => $banners,
            'tipoFiltro' => $tipo
        ]);
    }

    public function create(): void
    {
        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/banners-form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Novo Banner',
            'banner' => null
        ]);
    }

    public function store(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $tipo = $_POST['tipo'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $ctaLabel = trim($_POST['cta_label'] ?? '');
        $ctaUrl = trim($_POST['cta_url'] ?? '');
        $imagemDesktop = trim($_POST['imagem_desktop'] ?? '');
        $imagemMobile = trim($_POST['imagem_mobile'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!in_array($tipo, ['hero', 'portrait']) || empty($imagemDesktop)) {
            $this->redirect('/admin/home/banners?error=1');
            return;
        }

        $stmt = $db->prepare("
            INSERT INTO banners 
            (tenant_id, tipo, titulo, subtitulo, cta_label, cta_url, imagem_desktop, imagem_mobile, ordem, ativo, created_at, updated_at)
            VALUES (:tenant_id, :tipo, :titulo, :subtitulo, :cta_label, :cta_url, :imagem_desktop, :imagem_mobile, :ordem, :ativo, NOW(), NOW())
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'tipo' => $tipo,
            'titulo' => $titulo ?: null,
            'subtitulo' => $subtitulo ?: null,
            'cta_label' => $ctaLabel ?: null,
            'cta_url' => $ctaUrl ?: null,
            'imagem_desktop' => $imagemDesktop,
            'imagem_mobile' => $imagemMobile ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        $this->redirect('/admin/home/banners?success=1');
    }

    public function edit(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM banners 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $banner = $stmt->fetch();

        if (!$banner) {
            $this->redirect('/admin/home/banners?error=2');
            return;
        }

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/banners-form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Banner',
            'banner' => $banner
        ]);
    }

    public function update(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Verificar se pertence ao tenant
        $stmt = $db->prepare("
            SELECT id FROM banners 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        if (!$stmt->fetch()) {
            $this->redirect('/admin/home/banners?error=2');
            return;
        }

        $tipo = $_POST['tipo'] ?? '';
        $titulo = trim($_POST['titulo'] ?? '');
        $subtitulo = trim($_POST['subtitulo'] ?? '');
        $ctaLabel = trim($_POST['cta_label'] ?? '');
        $ctaUrl = trim($_POST['cta_url'] ?? '');
        $imagemDesktop = trim($_POST['imagem_desktop'] ?? '');
        $imagemMobile = trim($_POST['imagem_mobile'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!in_array($tipo, ['hero', 'portrait']) || empty($imagemDesktop)) {
            $this->redirect("/admin/home/banners/{$id}/editar?error=1");
            return;
        }

        $stmt = $db->prepare("
            UPDATE banners 
            SET tipo = :tipo,
                titulo = :titulo,
                subtitulo = :subtitulo,
                cta_label = :cta_label,
                cta_url = :cta_url,
                imagem_desktop = :imagem_desktop,
                imagem_mobile = :imagem_mobile,
                ordem = :ordem,
                ativo = :ativo,
                updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId,
            'tipo' => $tipo,
            'titulo' => $titulo ?: null,
            'subtitulo' => $subtitulo ?: null,
            'cta_label' => $ctaLabel ?: null,
            'cta_url' => $ctaUrl ?: null,
            'imagem_desktop' => $imagemDesktop,
            'imagem_mobile' => $imagemMobile ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        $this->redirect('/admin/home/banners?success=1');
    }

    public function destroy(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM banners 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

        $this->redirect('/admin/home/banners?success=1');
    }
}


