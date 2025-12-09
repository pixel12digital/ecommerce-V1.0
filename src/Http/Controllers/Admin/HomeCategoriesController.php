<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class HomeCategoriesController extends Controller
{
    private function sanitizeFileName($fileName): string
    {
        // Remove caracteres especiais e espaços
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        // Remove múltiplos underscores
        $fileName = preg_replace('/_+/', '_', $fileName);
        return $fileName;
    }
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar categorias em destaque configuradas
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
            'pageTitle' => 'Categorias em Destaque',
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

        // Processar upload de imagem se houver
        if (!empty($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['icon_upload'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            
            if (in_array($file['type'], $allowedTypes)) {
                $paths = require __DIR__ . '/../../../../config/paths.php';
                $uploadsBasePath = $paths['uploads_produtos_base_path'];
                $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/category-pills';
                
                if (!is_dir($uploadsPath)) {
                    mkdir($uploadsPath, 0755, true);
                }
                
                $fileName = $this->sanitizeFileName($file['name']);
                $destFile = $uploadsPath . '/' . $fileName;
                
                // Se arquivo já existe, adicionar timestamp
                if (file_exists($destFile)) {
                    $info = pathinfo($fileName);
                    $fileName = $info['filename'] . '_' . time() . '.' . $info['extension'];
                    $destFile = $uploadsPath . '/' . $fileName;
                }
                
                if (move_uploaded_file($file['tmp_name'], $destFile)) {
                    $iconePath = "/uploads/tenants/{$tenantId}/category-pills/{$fileName}";
                }
            }
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
            'pageTitle' => 'Editar Categoria em Destaque',
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
            SELECT id, icone_path FROM home_category_pills 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $existing = $stmt->fetch();
        if (!$existing) {
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

        // Processar upload de imagem se houver
        if (!empty($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['icon_upload'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            
            if (in_array($file['type'], $allowedTypes)) {
                $paths = require __DIR__ . '/../../../../config/paths.php';
                $uploadsBasePath = $paths['uploads_produtos_base_path'];
                $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/category-pills';
                
                if (!is_dir($uploadsPath)) {
                    mkdir($uploadsPath, 0755, true);
                }
                
                $fileName = $this->sanitizeFileName($file['name']);
                $destFile = $uploadsPath . '/' . $fileName;
                
                // Se arquivo já existe, adicionar timestamp
                if (file_exists($destFile)) {
                    $info = pathinfo($fileName);
                    $fileName = $info['filename'] . '_' . time() . '.' . $info['extension'];
                    $destFile = $uploadsPath . '/' . $fileName;
                }
                
                if (move_uploaded_file($file['tmp_name'], $destFile)) {
                    $iconePath = "/uploads/tenants/{$tenantId}/category-pills/{$fileName}";
                    
                    // Remover imagem antiga se existir e for diferente da nova
                    if (!empty($existing['icone_path']) && $existing['icone_path'] !== $iconePath) {
                        $oldPath = dirname(__DIR__, 3) . '/public' . $existing['icone_path'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
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

    public function listarImagensExistentes(): void
    {
        $tenantId = TenantContext::id();
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        
        $arquivos = [];
        
        // Expandir escopo: buscar em múltiplas pastas
        $pastas = [
            'category-pills' => 'Categorias em Destaque',
            'produtos' => 'Produtos',
            'logo' => 'Logos',
        ];
        
        foreach ($pastas as $pasta => $label) {
            $baseDir = $uploadsBasePath . '/' . $tenantId . '/' . $pasta;
            $baseUrl = "/uploads/tenants/{$tenantId}/{$pasta}";
            
            if (is_dir($baseDir)) {
                $handle = opendir($baseDir);
                if ($handle) {
                    while (($file = readdir($handle)) !== false) {
                        if ($file === '.' || $file === '..') {
                            continue;
                        }

                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                            continue;
                        }

                        $arquivos[] = [
                            'name' => $file,
                            'url'  => $baseUrl . '/' . $file,
                            'folder' => $pasta,
                            'folderLabel' => $label,
                        ];
                    }
                    closedir($handle);
                }
            }
        }

        // Ordenar por nome
        usort($arquivos, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'files'   => $arquivos,
        ]);
        exit;
    }
}


