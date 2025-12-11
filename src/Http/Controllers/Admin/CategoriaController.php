<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;

class CategoriaController extends Controller
{
    /**
     * Lista todas as categorias do tenant com hierarquia
     */
    public function index(): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Busca opcional
        $q = trim($_GET['q'] ?? '');

        // Buscar todas as categorias do tenant
        $where = ['c.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $tenantId];

        if (!empty($q)) {
            $where[] = '(c.nome LIKE :q OR c.slug LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $whereClause = implode(' AND ', $where);

        try {
            $stmt = $db->prepare("
                SELECT c.*, 
                       COUNT(DISTINCT pc.produto_id) as total_produtos,
                       COUNT(DISTINCT filhos.id) as total_subcategorias,
                       MAX(pai.nome) as categoria_pai_nome
                FROM categorias c
                LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id
                LEFT JOIN categorias filhos ON filhos.categoria_pai_id = c.id AND filhos.tenant_id = c.tenant_id
                LEFT JOIN categorias pai ON pai.id = c.categoria_pai_id AND pai.tenant_id = c.tenant_id
                WHERE {$whereClause}
                GROUP BY c.id
                ORDER BY c.nome ASC
            ");
            
            foreach ($params as $key => $value) {
                $paramType = ($key === 'tenant_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
                $stmt->bindValue(':' . $key, $value, $paramType);
            }
            $stmt->execute();
            $categoriasFlat = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Log do erro para debug
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            error_log("Query: SELECT c.*, COUNT(DISTINCT pc.produto_id) as total_produtos, COUNT(DISTINCT filhos.id) as total_subcategorias, MAX(pai.nome) as categoria_pai_nome FROM categorias c LEFT JOIN produto_categorias pc ON pc.categoria_id = c.id AND pc.tenant_id = c.tenant_id LEFT JOIN categorias filhos ON filhos.categoria_pai_id = c.id AND filhos.tenant_id = c.tenant_id LEFT JOIN categorias pai ON pai.id = c.categoria_pai_id AND pai.tenant_id = c.tenant_id WHERE {$whereClause} GROUP BY c.id ORDER BY c.nome ASC");
            error_log("Params: " . print_r($params, true));
            throw $e; // Re-lançar para ser capturado pelo handler global
        }

        // Garantir que categoriasFlat é array (mesmo que vazio)
        if (!is_array($categoriasFlat)) {
            $categoriasFlat = [];
        }

        // Construir árvore hierárquica
        $categoriasTree = $this->buildCategoryTree($categoriasFlat);

        // Buscar categorias para select (formatação hierárquica)
        $categoriasForSelect = $this->buildCategorySelectOptions($categoriasFlat);

        $tenant = TenantContext::tenant();
        
        $this->viewWithLayout('admin/layouts/store', 'admin/categorias/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Categorias',
            'categoriasTree' => $categoriasTree ?? [],
            'categoriasFlat' => $categoriasFlat,
            'categoriasForSelect' => $categoriasForSelect ?? [],
            'filtros' => ['q' => $q],
            'message' => $_SESSION['categoria_message'] ?? null,
            'messageType' => $_SESSION['categoria_message_type'] ?? null
        ]);

        // Limpar mensagens da sessão
        unset($_SESSION['categoria_message']);
        unset($_SESSION['categoria_message_type']);
    }

    /**
     * Exibe formulário de criação
     */
    public function create(): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar todas as categorias para o select de categoria pai
        $stmt = $db->prepare("
            SELECT id, nome, slug, categoria_pai_id
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFlat = $stmt->fetchAll();

        $categoriasForSelect = $this->buildCategorySelectOptions($categoriasFlat, null);

        $tenant = TenantContext::tenant();
        
        $this->viewWithLayout('admin/layouts/store', 'admin/categorias/form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Nova Categoria',
            'categoria' => null,
            'categoriasForSelect' => $categoriasForSelect,
            'message' => $_SESSION['categoria_message'] ?? null,
            'messageType' => $_SESSION['categoria_message_type'] ?? null,
            'formData' => $_SESSION['categoria_form_data'] ?? []
        ]);

        // Limpar mensagens e dados do formulário da sessão
        unset($_SESSION['categoria_message']);
        unset($_SESSION['categoria_message_type']);
        unset($_SESSION['categoria_form_data']);
    }

    /**
     * Salva nova categoria
     */
    public function store(): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        try {
            $nome = trim($_POST['nome'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $categoriaPaiId = !empty($_POST['categoria_pai_id']) ? (int)$_POST['categoria_pai_id'] : null;

            // Validações
            if (empty($nome)) {
                throw new \Exception('Nome da categoria é obrigatório');
            }

            // Gerar slug se não fornecido
            if (empty($slug)) {
                $slug = $this->generateSlug($nome);
            } else {
                $slug = $this->generateSlug($slug);
            }

            // Validar slug único por tenant
            $stmt = $db->prepare("
                SELECT id FROM categorias 
                WHERE tenant_id = :tenant_id AND slug = :slug
                LIMIT 1
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'slug' => $slug]);
            if ($stmt->fetch()) {
                throw new \Exception('Já existe uma categoria com este slug. Escolha outro.');
            }

            // Validar categoria pai se informada
            if ($categoriaPaiId !== null) {
                $stmt = $db->prepare("
                    SELECT id FROM categorias 
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute(['id' => $categoriaPaiId, 'tenant_id' => $tenantId]);
                if (!$stmt->fetch()) {
                    throw new \Exception('Categoria pai inválida ou não pertence ao tenant');
                }
            }

            // Inserir categoria
            $stmt = $db->prepare("
                INSERT INTO categorias (tenant_id, nome, slug, descricao, categoria_pai_id, created_at, updated_at)
                VALUES (:tenant_id, :nome, :slug, :descricao, :categoria_pai_id, NOW(), NOW())
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'slug' => $slug,
                'descricao' => $descricao ?: null,
                'categoria_pai_id' => $categoriaPaiId
            ]);

            $_SESSION['categoria_message'] = 'Categoria criada com sucesso!';
            $_SESSION['categoria_message_type'] = 'success';
            $this->redirect('/admin/categorias');
        } catch (\Exception $e) {
            $_SESSION['categoria_message'] = 'Erro ao criar categoria: ' . $e->getMessage();
            $_SESSION['categoria_message_type'] = 'error';
            $_SESSION['categoria_form_data'] = $_POST;
            $this->redirect('/admin/categorias/criar');
        }
    }

    /**
     * Exibe formulário de edição
     */
    public function edit(int $id): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar categoria
        $stmt = $db->prepare("
            SELECT * FROM categorias 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $categoria = $stmt->fetch();

        if (!$categoria) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Categoria não encontrada']);
            return;
        }

        // Buscar todas as categorias para o select (excluindo a própria e seus descendentes)
        $stmt = $db->prepare("
            SELECT id, nome, slug, categoria_pai_id
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFlat = $stmt->fetchAll();

        $categoriasForSelect = $this->buildCategorySelectOptions($categoriasFlat, $id);

        $tenant = TenantContext::tenant();
        
        $this->viewWithLayout('admin/layouts/store', 'admin/categorias/form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Categoria',
            'categoria' => $categoria,
            'categoriasForSelect' => $categoriasForSelect,
            'message' => $_SESSION['categoria_message'] ?? null,
            'messageType' => $_SESSION['categoria_message_type'] ?? null,
            'formData' => $_SESSION['categoria_form_data'] ?? []
        ]);

        // Limpar mensagens e dados do formulário da sessão
        unset($_SESSION['categoria_message']);
        unset($_SESSION['categoria_message_type']);
        unset($_SESSION['categoria_form_data']);
    }

    /**
     * Atualiza categoria existente
     */
    public function update(int $id): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        try {
            // Verificar se categoria existe e pertence ao tenant
            $stmt = $db->prepare("
                SELECT * FROM categorias 
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $categoriaExistente = $stmt->fetch();

            if (!$categoriaExistente) {
                throw new \Exception('Categoria não encontrada');
            }

            $nome = trim($_POST['nome'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $categoriaPaiId = !empty($_POST['categoria_pai_id']) ? (int)$_POST['categoria_pai_id'] : null;

            // Validações
            if (empty($nome)) {
                throw new \Exception('Nome da categoria é obrigatório');
            }

            // Gerar slug se não fornecido
            if (empty($slug)) {
                $slug = $this->generateSlug($nome);
            } else {
                $slug = $this->generateSlug($slug);
            }

            // Validar slug único por tenant (ignorando a própria categoria)
            $stmt = $db->prepare("
                SELECT id FROM categorias 
                WHERE tenant_id = :tenant_id AND slug = :slug AND id != :id
                LIMIT 1
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'slug' => $slug, 'id' => $id]);
            if ($stmt->fetch()) {
                throw new \Exception('Já existe uma categoria com este slug. Escolha outro.');
            }

            // Validar categoria pai se informada
            if ($categoriaPaiId !== null) {
                // Não pode ser pai de si mesma
                if ($categoriaPaiId == $id) {
                    throw new \Exception('Uma categoria não pode ser pai de si mesma');
                }

                // Verificar se pertence ao tenant
                $stmt = $db->prepare("
                    SELECT id FROM categorias 
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute(['id' => $categoriaPaiId, 'tenant_id' => $tenantId]);
                if (!$stmt->fetch()) {
                    throw new \Exception('Categoria pai inválida ou não pertence ao tenant');
                }

                // Verificar se não está tentando criar loop (categoria pai não pode ser descendente)
                if ($this->isDescendant($db, $tenantId, $categoriaPaiId, $id)) {
                    throw new \Exception('Não é possível definir esta categoria como pai. Isso criaria um loop na hierarquia.');
                }
            }

            // Atualizar categoria
            $stmt = $db->prepare("
                UPDATE categorias 
                SET nome = :nome, 
                    slug = :slug, 
                    descricao = :descricao, 
                    categoria_pai_id = :categoria_pai_id,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'slug' => $slug,
                'descricao' => $descricao ?: null,
                'categoria_pai_id' => $categoriaPaiId
            ]);

            $_SESSION['categoria_message'] = 'Categoria atualizada com sucesso!';
            $_SESSION['categoria_message_type'] = 'success';
            $this->redirect('/admin/categorias');
        } catch (\Exception $e) {
            $_SESSION['categoria_message'] = 'Erro ao atualizar categoria: ' . $e->getMessage();
            $_SESSION['categoria_message_type'] = 'error';
            $_SESSION['categoria_form_data'] = $_POST;
            $this->redirect('/admin/categorias/' . $id . '/editar');
        }
    }

    /**
     * Exclui categoria (com validações)
     */
    public function destroy(int $id): void
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        try {
            // Verificar se categoria existe e pertence ao tenant
            $stmt = $db->prepare("
                SELECT * FROM categorias 
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $categoria = $stmt->fetch();

            if (!$categoria) {
                throw new \Exception('Categoria não encontrada');
            }

            // Verificar se possui subcategorias
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM categorias 
                WHERE categoria_pai_id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $totalSubcategorias = $stmt->fetch()['total'];

            if ($totalSubcategorias > 0) {
                throw new \Exception('Não é possível excluir uma categoria que possui subcategorias. Remova ou mova as subcategorias primeiro.');
            }

            // Verificar se possui produtos vinculados
            $stmt = $db->prepare("
                SELECT COUNT(*) as total FROM produto_categorias 
                WHERE categoria_id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
            $totalProdutos = $stmt->fetch()['total'];

            if ($totalProdutos > 0) {
                throw new \Exception('Não é possível excluir uma categoria que possui produtos vinculados. Remova os produtos desta categoria primeiro.');
            }

            // Excluir categoria
            $stmt = $db->prepare("
                DELETE FROM categorias 
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

            $_SESSION['categoria_message'] = 'Categoria excluída com sucesso!';
            $_SESSION['categoria_message_type'] = 'success';
            $this->redirect('/admin/categorias');
        } catch (\Exception $e) {
            $_SESSION['categoria_message'] = 'Erro ao excluir categoria: ' . $e->getMessage();
            $_SESSION['categoria_message_type'] = 'error';
            $this->redirect('/admin/categorias');
        }
    }

    /**
     * Constrói árvore hierárquica de categorias
     */
    private function buildCategoryTree(array $categorias): array
    {
        if (empty($categorias)) {
            return [];
        }

        $tree = [];
        $indexed = [];

        // Indexar todas as categorias
        foreach ($categorias as $cat) {
            if (!isset($cat['id'])) {
                continue; // Pular itens inválidos
            }
            $indexed[$cat['id']] = $cat;
            $indexed[$cat['id']]['filhos'] = [];
        }

        // Construir árvore
        foreach ($indexed as $id => $cat) {
            $categoriaPaiId = $cat['categoria_pai_id'] ?? null;
            if ($categoriaPaiId === null) {
                $tree[] = &$indexed[$id];
            } else {
                if (isset($indexed[$categoriaPaiId])) {
                    $indexed[$categoriaPaiId]['filhos'][] = &$indexed[$id];
                }
            }
        }

        return $tree;
    }

    /**
     * Constrói opções hierárquicas para select (com indentação)
     */
    private function buildCategorySelectOptions(array $categorias, ?int $excludeId = null): array
    {
        if (empty($categorias)) {
            return [];
        }

        $tree = $this->buildCategoryTree($categorias);
        $options = [];
        
        $this->flattenTreeForSelect($tree, $options, 0, $excludeId);
        
        return $options;
    }

    /**
     * Achatamento recursivo da árvore para select
     */
    private function flattenTreeForSelect(array $tree, array &$options, int $level, ?int $excludeId = null): void
    {
        if (empty($tree)) {
            return;
        }

        foreach ($tree as $cat) {
            // Pular a categoria que está sendo editada e seus descendentes
            if ($excludeId !== null && isset($cat['id']) && $cat['id'] == $excludeId) {
                continue;
            }

            if (!isset($cat['id']) || !isset($cat['nome'])) {
                continue; // Pular itens inválidos
            }

            $prefix = str_repeat('-- ', $level);
            $options[] = [
                'id' => $cat['id'],
                'nome' => $prefix . $cat['nome'],
                'level' => $level,
                'categoria_pai_id' => $cat['categoria_pai_id'] ?? null
            ];

            // Processar filhos recursivamente (mas não se for a categoria excluída)
            if (!empty($cat['filhos']) && is_array($cat['filhos']) && ($excludeId === null || $cat['id'] != $excludeId)) {
                $this->flattenTreeForSelect($cat['filhos'], $options, $level + 1, $excludeId);
            }
        }
    }

    /**
     * Verifica se uma categoria é descendente de outra
     */
    private function isDescendant($db, int $tenantId, int $possibleAncestorId, int $categoryId): bool
    {
        $currentId = $possibleAncestorId;
        $visited = [];

        while ($currentId !== null) {
            if ($currentId == $categoryId) {
                return true; // Encontrou loop
            }

            if (isset($visited[$currentId])) {
                break; // Evitar loop infinito
            }
            $visited[$currentId] = true;

            $stmt = $db->prepare("
                SELECT categoria_pai_id FROM categorias 
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute(['id' => $currentId, 'tenant_id' => $tenantId]);
            $result = $stmt->fetch();

            if (!$result) {
                break;
            }

            $currentId = $result['categoria_pai_id'];
        }

        return false;
    }

    /**
     * Gera slug a partir de texto
     */
    private function generateSlug($text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }
}

