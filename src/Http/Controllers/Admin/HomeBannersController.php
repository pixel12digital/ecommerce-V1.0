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
        // Receber tipo via query string se vier de uma aba específica
        $tipoInicial = $_GET['tipo'] ?? '';
        if (!in_array($tipoInicial, ['hero', 'portrait'])) {
            $tipoInicial = 'hero'; // Default
        }
        $this->viewWithLayout('admin/layouts/store', 'admin/home/banners-form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Novo Banner',
            'banner' => null,
            'tipoInicial' => $tipoInicial
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

        if (!in_array($tipo, ['hero', 'portrait'])) {
            $this->redirect('/admin/home/banners?error=1');
            return;
        }
        
        // Para banners Hero, imagem_desktop é opcional (pode ser apenas texto/CTA)
        // Para banners Portrait, imagem_desktop é obrigatória
        if ($tipo === 'portrait' && empty($imagemDesktop)) {
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
            'imagem_desktop' => $imagemDesktop ?: null,
            'imagem_mobile' => $imagemMobile ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        // Redirecionar mantendo o filtro de tipo se existir
        $redirectUrl = '/admin/home/banners?success=1';
        if (!empty($tipo)) {
            $redirectUrl .= '&tipo=' . urlencode($tipo);
        }
        $this->redirect($redirectUrl);
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
            'banner' => $banner,
            'tipoInicial' => $banner['tipo'] // Passar tipoInicial para manter consistência
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

        if (!in_array($tipo, ['hero', 'portrait'])) {
            $this->redirect("/admin/home/banners/{$id}/editar?error=1");
            return;
        }
        
        // Para banners Hero, imagem_desktop é opcional (pode ser apenas texto/CTA)
        // Para banners Portrait, imagem_desktop é obrigatória
        if ($tipo === 'portrait' && empty($imagemDesktop)) {
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
            'imagem_desktop' => $imagemDesktop ?: null,
            'imagem_mobile' => $imagemMobile ?: null,
            'ordem' => $ordem,
            'ativo' => $ativo
        ]);

        // Redirecionar mantendo o filtro de tipo se existir
        $redirectUrl = '/admin/home/banners?success=1';
        if (!empty($tipo)) {
            $redirectUrl .= '&tipo=' . urlencode($tipo);
        }
        $this->redirect($redirectUrl);
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

    public function reordenar(): void
    {
        // Limpar qualquer saída anterior e definir headers JSON imediatamente
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Desabilitar exibição de erros
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', 0);
        
        // Definir header JSON
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(200);
        
        try {
            // Verificar se tenant foi resolvido
            try {
                $tenantId = TenantContext::id();
            } catch (\Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Erro de autenticação. Faça login novamente.']);
                exit;
            }
            
            $db = Database::getConnection();

            // Pegar dados do POST
            $tipo = $_POST['tipo'] ?? '';
            $ids = $_POST['ids'] ?? [];
            
            // Garantir que ids é array
            if (!is_array($ids)) {
                $ids = [];
            }

            // Validar dados
            if (empty($tipo) || !in_array($tipo, ['hero', 'portrait']) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
                exit;
            }

            // Converter IDs para inteiros e filtrar inválidos
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            $ids = array_values($ids); // Reindexar array
            
            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Nenhum ID válido fornecido']);
                exit;
            }

            // Validar que todos os IDs pertencem ao tenant e ao tipo correto
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare("
                SELECT id FROM banners 
                WHERE id IN ($placeholders) 
                AND tenant_id = ? 
                AND tipo = ?
            ");
            $params = array_merge($ids, [$tenantId, $tipo]);
            $stmt->execute($params);
            $validIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');

            if (count($validIds) !== count($ids)) {
                echo json_encode(['success' => false, 'message' => 'Alguns banners não foram encontrados']);
                exit;
            }

            // Atualizar ordem de cada banner
            $stmt = $db->prepare("
                UPDATE banners 
                SET ordem = :ordem, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id AND tipo = :tipo
            ");

            foreach ($ids as $index => $id) {
                $stmt->execute([
                    'ordem' => $index + 1,
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'tipo' => $tipo
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso']);
            exit;
            
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Erro ao atualizar ordem: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            exit;
        } finally {
            // Restaurar configurações
            error_reporting($oldErrorReporting);
            ini_set('display_errors', $oldDisplayErrors);
        }
    }
}


