<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class HomeSectionsController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar seções configuradas
        $stmt = $db->prepare("
            SELECT hcs.*, c.nome as categoria_nome, c.slug as categoria_slug
            FROM home_category_sections hcs
            LEFT JOIN categorias c ON c.id = hcs.categoria_id AND c.tenant_id = :tenant_id_join
            WHERE hcs.tenant_id = :tenant_id_where
            ORDER BY hcs.ordem ASC, hcs.id ASC
        ");
        $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $sections = $stmt->fetchAll();

        // Buscar todas as categorias para o select
        $stmt = $db->prepare("
            SELECT id, nome, slug
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categorias = $stmt->fetchAll();

        // Definir slugs padrão se não existirem
        $defaultSlugs = ['linha_1', 'linha_2', 'linha_3', 'linha_4'];
        $existingSlugs = array_column($sections, 'slug_secao');
        
        foreach ($defaultSlugs as $slug) {
            if (!in_array($slug, $existingSlugs)) {
                // Criar seção padrão
                $stmt = $db->prepare("
                    INSERT INTO home_category_sections 
                    (tenant_id, slug_secao, titulo, subtitulo, categoria_id, quantidade_produtos, ordem, ativo, created_at, updated_at)
                    VALUES (:tenant_id, :slug, :titulo, NULL, 0, 8, :ordem, 0, NOW(), NOW())
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'slug' => $slug,
                    'titulo' => 'Seção ' . str_replace('_', ' ', ucfirst($slug)),
                    'ordem' => (int)str_replace('linha_', '', $slug)
                ]);
            }
        }

        // Buscar novamente após criar defaults
        $stmt = $db->prepare("
            SELECT hcs.*, c.nome as categoria_nome, c.slug as categoria_slug
            FROM home_category_sections hcs
            LEFT JOIN categorias c ON c.id = hcs.categoria_id AND c.tenant_id = :tenant_id_join
            WHERE hcs.tenant_id = :tenant_id_where
            ORDER BY hcs.ordem ASC, hcs.id ASC
        ");
        $stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
        $stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
        $stmt->execute();
        $sections = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/home/sections-categories-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Seções de Categorias',
            'sections' => $sections,
            'categorias' => $categorias
        ]);
    }

    public function update(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $sections = $_POST['sections'] ?? [];

        foreach ($sections as $sectionData) {
            $id = (int)($sectionData['id'] ?? 0);
            $titulo = trim($sectionData['titulo'] ?? '');
            $subtitulo = trim($sectionData['subtitulo'] ?? '');
            $categoriaId = (int)($sectionData['categoria_id'] ?? 0);
            $quantidadeProdutos = (int)($sectionData['quantidade_produtos'] ?? 8);
            $ativo = isset($sectionData['ativo']) ? 1 : 0;

            if ($id > 0) {
                $stmt = $db->prepare("
                    UPDATE home_category_sections 
                    SET titulo = :titulo,
                        subtitulo = :subtitulo,
                        categoria_id = :categoria_id,
                        quantidade_produtos = :quantidade_produtos,
                        ativo = :ativo,
                        updated_at = NOW()
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'titulo' => $titulo,
                    'subtitulo' => $subtitulo ?: null,
                    'categoria_id' => $categoriaId > 0 ? $categoriaId : null,
                    'quantidade_produtos' => $quantidadeProdutos,
                    'ativo' => $ativo
                ]);
            }
        }

        $this->redirect('/admin/home/secoes-categorias?success=1');
    }
}


