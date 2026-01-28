<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class AttributeController extends Controller
{
    public function index(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $q = trim($_GET['q'] ?? '');

        $where = ['a.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($q)) {
            $where[] = '(a.nome LIKE :q OR a.slug LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT a.*, 
                   COUNT(DISTINCT at.id) as total_termos,
                   COUNT(DISTINCT pa.produto_id) as total_produtos
            FROM atributos a
            LEFT JOIN atributo_termos at ON at.atributo_id = a.id AND at.tenant_id = a.tenant_id
            LEFT JOIN produto_atributos pa ON pa.atributo_id = a.id AND pa.tenant_id = a.tenant_id
            WHERE {$whereClause}
            GROUP BY a.id
            ORDER BY a.ordem ASC, a.nome ASC
        ");

        foreach ($params as $key => $value) {
            $paramType = ($key === 'tenant_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $atributos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/atributos/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Atributos',
            'atributos' => $atributos,
            'filtros' => ['q' => $q],
            'message' => $_SESSION['atributo_message'] ?? null,
            'messageType' => $_SESSION['atributo_message_type'] ?? null
        ]);

        unset($_SESSION['atributo_message']);
        unset($_SESSION['atributo_message_type']);
    }

    public function create(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/atributos/create-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Criar Atributo',
            'formData' => $_SESSION['atributo_form_data'] ?? [],
            'errors' => $_SESSION['atributo_errors'] ?? []
        ]);

        unset($_SESSION['atributo_form_data']);
        unset($_SESSION['atributo_errors']);
    }

    public function store(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $nome = trim($_POST['nome'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $tipo = $_POST['tipo'] ?? 'select';
        $ordem = (int)($_POST['ordem'] ?? 0);

        $errors = [];

        if (empty($nome)) {
            $errors['nome'] = 'Nome é obrigatório';
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($nome);
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("
                    INSERT INTO atributos (tenant_id, nome, slug, tipo, ordem, created_at, updated_at)
                    VALUES (:tenant_id, :nome, :slug, :tipo, :ordem, NOW(), NOW())
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'nome' => $nome,
                    'slug' => $slug,
                    'tipo' => $tipo,
                    'ordem' => $ordem
                ]);

                // Obter ID antes do commit (importante para lastInsertId funcionar)
                $atributoId = $db->lastInsertId();
                
                if (!$atributoId || $atributoId == 0) {
                    throw new \PDOException('Falha ao obter ID do atributo criado');
                }

                $db->commit();
                
                $_SESSION['atributo_message'] = 'Atributo criado com sucesso! Agora cadastre os termos abaixo.';
                $_SESSION['atributo_message_type'] = 'success';
                
                // Redirecionar para edição com âncora #termos para continuar o fluxo
                $this->redirect("/admin/atributos/{$atributoId}/editar#termos");
                return;

            } catch (\PDOException $e) {
                $db->rollBack();
                if ($e->getCode() == 23000) {
                    $errors['slug'] = 'Este slug já está em uso';
                } else {
                    $errors['_general'] = 'Erro ao criar atributo: ' . $e->getMessage();
                }
            }
        }

        $_SESSION['atributo_form_data'] = $_POST;
        $_SESSION['atributo_errors'] = $errors;
        $this->redirect('/admin/atributos/novo');
    }

    public function edit(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM atributos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $atributo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$atributo) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Atributo não encontrado']);
            return;
        }

        // Buscar termos do atributo
        $stmtTermos = $db->prepare("
            SELECT * FROM atributo_termos 
            WHERE atributo_id = :atributo_id AND tenant_id = :tenant_id 
            ORDER BY ordem ASC, nome ASC
        ");
        $stmtTermos->execute(['atributo_id' => $id, 'tenant_id' => $tenantId]);
        $termos = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);

        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/atributos/edit-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Atributo: ' . $atributo['nome'],
            'atributo' => $atributo,
            'termos' => $termos,
            'message' => $_SESSION['atributo_message'] ?? null,
            'messageType' => $_SESSION['atributo_message_type'] ?? null
        ]);

        unset($_SESSION['atributo_message']);
        unset($_SESSION['atributo_message_type']);
    }

    public function update(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM atributos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $atributo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$atributo) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Atributo não encontrado']);
            return;
        }

        $nome = trim($_POST['nome'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $tipo = $_POST['tipo'] ?? 'select';
        $ordem = (int)($_POST['ordem'] ?? 0);

        $errors = [];

        if (empty($nome)) {
            $errors['nome'] = 'Nome é obrigatório';
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($nome);
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("
                    UPDATE atributos 
                    SET nome = :nome, slug = :slug, tipo = :tipo, ordem = :ordem, updated_at = NOW()
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'nome' => $nome,
                    'slug' => $slug,
                    'tipo' => $tipo,
                    'ordem' => $ordem
                ]);

                $db->commit();

                $_SESSION['atributo_message'] = 'Atributo atualizado com sucesso!';
                $_SESSION['atributo_message_type'] = 'success';
                $this->redirect("/admin/atributos/{$id}/editar");
                return;

            } catch (\PDOException $e) {
                $db->rollBack();
                if ($e->getCode() == 23000) {
                    $errors['slug'] = 'Este slug já está em uso';
                } else {
                    $errors['_general'] = 'Erro ao atualizar atributo: ' . $e->getMessage();
                }
            }
        }

        $_SESSION['atributo_message'] = $errors['_general'] ?? 'Erro ao atualizar atributo';
        $_SESSION['atributo_message_type'] = 'error';
        $this->redirect("/admin/atributos/{$id}/editar");
    }

    public function destroy(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM atributos WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

            $db->commit();

            $_SESSION['atributo_message'] = 'Atributo excluído com sucesso!';
            $_SESSION['atributo_message_type'] = 'success';

        } catch (\PDOException $e) {
            $db->rollBack();
            $_SESSION['atributo_message'] = 'Erro ao excluir atributo: ' . $e->getMessage();
            $_SESSION['atributo_message_type'] = 'error';
        }

        $this->redirect('/admin/atributos');
    }

    public function storeTerm(int $atributoId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $nome = trim($_POST['nome'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $valorCor = trim($_POST['valor_cor'] ?? '');
        $imagem = trim($_POST['imagem'] ?? '');

        if (empty($nome)) {
            $_SESSION['atributo_message'] = 'Nome do termo é obrigatório';
            $_SESSION['atributo_message_type'] = 'error';
            $this->redirect("/admin/atributos/{$atributoId}/editar");
            return;
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($nome);
        }

        try {
            $db->beginTransaction();

            // Calcular ordem automaticamente (última posição)
            $stmtCount = $db->prepare("SELECT COUNT(*) as total FROM atributo_termos WHERE atributo_id = :atributo_id AND tenant_id = :tenant_id");
            $stmtCount->execute(['atributo_id' => $atributoId, 'tenant_id' => $tenantId]);
            $countResult = $stmtCount->fetch(\PDO::FETCH_ASSOC);
            $ordem = (int)($countResult['total'] ?? 0);

            $stmt = $db->prepare("
                INSERT INTO atributo_termos (tenant_id, atributo_id, nome, slug, valor_cor, imagem, ordem, created_at, updated_at)
                VALUES (:tenant_id, :atributo_id, :nome, :slug, :valor_cor, :imagem, :ordem, NOW(), NOW())
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'atributo_id' => $atributoId,
                'nome' => $nome,
                'slug' => $slug,
                'valor_cor' => !empty($valorCor) ? $valorCor : null,
                'imagem' => !empty($imagem) ? $imagem : null,
                'ordem' => $ordem
            ]);

            $termoId = $db->lastInsertId();
            
            if (!$termoId || $termoId == 0) {
                throw new \PDOException('Falha ao obter ID do termo criado');
            }

            $db->commit();

            // Buscar dados completos do termo criado
            $stmtTermo = $db->prepare("SELECT * FROM atributo_termos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
            $stmtTermo->execute(['id' => $termoId, 'tenant_id' => $tenantId]);
            $termoCriado = $stmtTermo->fetch(\PDO::FETCH_ASSOC);

            // Se for requisição AJAX, retornar JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Termo adicionado com sucesso!',
                    'termo' => $termoCriado
                ]);
                exit;
            }

            $_SESSION['atributo_message'] = 'Termo adicionado com sucesso!';
            $_SESSION['atributo_message_type'] = 'success';

        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Se for requisição AJAX, retornar JSON de erro
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao adicionar termo: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $_SESSION['atributo_message'] = 'Erro ao adicionar termo: ' . $e->getMessage();
            $_SESSION['atributo_message_type'] = 'error';
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            // Se for requisição AJAX, retornar JSON de erro
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao adicionar termo: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $_SESSION['atributo_message'] = 'Erro ao adicionar termo: ' . $e->getMessage();
            $_SESSION['atributo_message_type'] = 'error';
        }

        // Redirecionar apenas se não for AJAX
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            $this->redirect("/admin/atributos/{$atributoId}/editar");
        }
    }

    public function updateTerm(int $atributoId, int $termId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $nome = trim($_POST['nome'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $valorCor = trim($_POST['valor_cor'] ?? '');
        $imagem = trim($_POST['imagem'] ?? '');
        $ordem = (int)($_POST['ordem'] ?? 0);

        if (empty($nome)) {
            $_SESSION['atributo_message'] = 'Nome do termo é obrigatório';
            $_SESSION['atributo_message_type'] = 'error';
            $this->redirect("/admin/atributos/{$atributoId}/editar");
            return;
        }

        if (empty($slug)) {
            $slug = $this->generateSlug($nome);
        }

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                UPDATE atributo_termos 
                SET nome = :nome, slug = :slug, valor_cor = :valor_cor, imagem = :imagem, ordem = :ordem, updated_at = NOW()
                WHERE id = :term_id AND atributo_id = :atributo_id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'term_id' => $termId,
                'atributo_id' => $atributoId,
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'slug' => $slug,
                'valor_cor' => !empty($valorCor) ? $valorCor : null,
                'imagem' => !empty($imagem) ? $imagem : null,
                'ordem' => $ordem
            ]);

            $db->commit();

            $_SESSION['atributo_message'] = 'Termo atualizado com sucesso!';
            $_SESSION['atributo_message_type'] = 'success';

        } catch (\PDOException $e) {
            $db->rollBack();
            $_SESSION['atributo_message'] = 'Erro ao atualizar termo: ' . $e->getMessage();
            $_SESSION['atributo_message_type'] = 'error';
        }

        $this->redirect("/admin/atributos/{$atributoId}/editar");
    }

    public function destroyTerm(int $atributoId, int $termId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                DELETE FROM atributo_termos 
                WHERE id = :term_id AND atributo_id = :atributo_id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'term_id' => $termId,
                'atributo_id' => $atributoId,
                'tenant_id' => $tenantId
            ]);

            $db->commit();

            $_SESSION['atributo_message'] = 'Termo excluído com sucesso!';
            $_SESSION['atributo_message_type'] = 'success';

        } catch (\PDOException $e) {
            $db->rollBack();
            $_SESSION['atributo_message'] = 'Erro ao excluir termo: ' . $e->getMessage();
            $_SESSION['atributo_message_type'] = 'error';
        }

        $this->redirect("/admin/atributos/{$atributoId}/editar");
    }

    public function reorderTerms(int $atributoId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Verificar se o atributo existe e pertence ao tenant
        $stmt = $db->prepare("SELECT id FROM atributos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $atributoId, 'tenant_id' => $tenantId]);
        $atributo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$atributo) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Atributo não encontrado']);
            exit;
        }

        // Ler JSON do body
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!isset($data['ordered_term_ids']) || !is_array($data['ordered_term_ids']) || empty($data['ordered_term_ids'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'IDs dos termos não fornecidos ou inválidos']);
            exit;
        }

        try {
            $db->beginTransaction();

            // Validar que todos os termos pertencem ao atributo e tenant
            $termIds = array_map('intval', $data['ordered_term_ids']);
            $placeholders = implode(',', array_fill(0, count($termIds), '?'));
            
            $stmt = $db->prepare("
                SELECT id FROM atributo_termos 
                WHERE id IN ({$placeholders}) 
                AND atributo_id = ? 
                AND tenant_id = ?
            ");
            $stmt->execute(array_merge($termIds, [$atributoId, $tenantId]));
            $validTermIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');

            if (count($validTermIds) !== count($termIds)) {
                $db->rollBack();
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Alguns termos não pertencem a este atributo']);
                exit;
            }

            // Atualizar ordem de cada termo (1..N)
            $stmt = $db->prepare("
                UPDATE atributo_termos 
                SET ordem = :ordem, updated_at = NOW()
                WHERE id = :term_id AND atributo_id = :atributo_id AND tenant_id = :tenant_id
            ");

            foreach ($termIds as $index => $termId) {
                $stmt->execute([
                    'term_id' => $termId,
                    'atributo_id' => $atributoId,
                    'tenant_id' => $tenantId,
                    'ordem' => $index
                ]);
            }

            $db->commit();

            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Ordem atualizada com sucesso']);
            exit;

        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao reordenar termos: ' . $e->getMessage()]);
            exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao reordenar termos: ' . $e->getMessage()]);
            exit;
        }
    }

    private function generateSlug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}
