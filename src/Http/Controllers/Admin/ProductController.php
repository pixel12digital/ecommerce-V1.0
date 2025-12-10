<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Support\LangHelper;

class ProductController extends Controller
{
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Parâmetros de filtro
        $q = $_GET['q'] ?? '';
        $status = $_GET['status'] ?? '';
        $somenteComImagem = isset($_GET['somente_com_imagem']) && $_GET['somente_com_imagem'] == '1';
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;
        
        // Parâmetros de ordenação
        $sort = $_GET['sort'] ?? '';
        $direction = strtolower($_GET['direction'] ?? 'asc');
        $orderBy = 'data_criacao DESC'; // Padrão
        
        // Validar e aplicar ordenação por nome
        if ($sort === 'name' && in_array($direction, ['asc', 'desc'])) {
            $orderBy = 'nome ' . strtoupper($direction);
        }

        // Construir query com filtros
        $where = ['tenant_id = :tenant_id'];
        $params = [];

        if (!empty($q)) {
            // Se for numérico, pode ser ID ou SKU
            if (is_numeric($q)) {
                $where[] = '(id = :q_id OR nome LIKE :q_nome OR sku LIKE :q_sku)';
                $params['q_id'] = (int)$q;
                $params['q_nome'] = '%' . $q . '%';
                $params['q_sku'] = '%' . $q . '%';
            } else {
                // Buscar apenas por nome ou SKU
                $where[] = '(nome LIKE :q_nome OR sku LIKE :q_sku)';
                $params['q_nome'] = '%' . $q . '%';
                $params['q_sku'] = '%' . $q . '%';
            }
        }

        // Só filtrar por status se não estiver vazio e não for "todos"
        if (!empty($status) && $status !== 'todos') {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        // Filtro "Somente produtos com imagem"
        if ($somenteComImagem) {
            $where[] = 'imagem_principal IS NOT NULL AND imagem_principal != \'\'';
        }

        $whereClause = implode(' AND ', $where);

        // Contar total
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM produtos 
            WHERE {$whereClause}
        ");
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            // Se for q_id, usar PARAM_INT, senão PARAM_STR
            $paramType = ($key === 'q_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Buscar produtos
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ");
        
        // Bind dos parâmetros WHERE
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            // Se for q_id, usar PARAM_INT, senão PARAM_STR
            $paramType = ($key === 'q_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        
        // Bind dos parâmetros LIMIT e OFFSET
        $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        $stmt->execute();
        $produtos = $stmt->fetchAll();

        // Buscar imagem principal e categorias para cada produto
        foreach ($produtos as &$produto) {
            // Primeiro tentar usar imagem_principal do produto
            if (!empty($produto['imagem_principal'])) {
                $produto['imagem_principal_data'] = [
                    'caminho_arquivo' => $produto['imagem_principal'],
                    'tipo' => 'main'
                ];
            } else {
                // Se não tiver, buscar na tabela produto_imagens
                $stmtImg = $db->prepare("
                    SELECT * FROM produto_imagens 
                    WHERE tenant_id = :tenant_id 
                    AND produto_id = :produto_id 
                    ORDER BY tipo = 'main' DESC, ordem ASC, id ASC 
                    LIMIT 1
                ");
                $stmtImg->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $produto['id']
                ]);
                $imagem = $stmtImg->fetch();
                $produto['imagem_principal_data'] = $imagem ? $imagem : null;
            }

            // Buscar categorias do produto
            $stmtCat = $db->prepare("
                SELECT c.nome 
                FROM categorias c
                INNER JOIN produto_categorias pc ON pc.categoria_id = c.id
                WHERE pc.tenant_id = :tenant_id AND pc.produto_id = :produto_id
                ORDER BY c.nome ASC
                LIMIT 5
            ");
            $stmtCat->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produto['id']
            ]);
            $categorias = $stmtCat->fetchAll();
            $produto['categorias'] = array_column($categorias, 'nome');
        }

        $totalPages = ceil($total / $perPage);

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/products/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Produtos',
            'produtos' => $produtos,
            'paginacao' => [
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => $currentPage,
                'hasPrev' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
                'perPage' => $perPage
            ],
            'filtros' => [
                'q' => $q,
                'status' => $status,
                'somente_com_imagem' => $somenteComImagem
            ],
            'ordenacao' => [
                'sort' => $sort,
                'direction' => $direction
            ]
        ]);
    }

    public function show(int $id): void
    {
        // Redirecionar para edit (mantém compatibilidade)
        $this->edit($id);
    }

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

        // Buscar todas as categorias do tenant
        $stmt = $db->prepare("
            SELECT id, nome, slug
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categorias = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        
        $this->viewWithLayout('admin/layouts/store', 'admin/products/create-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Novo Produto',
            'categorias' => $categorias,
            'message' => $_SESSION['product_create_message'] ?? null,
            'messageType' => $_SESSION['product_create_message_type'] ?? null,
            'formData' => $_SESSION['product_create_form_data'] ?? []
        ]);

        // Limpar mensagens e dados do formulário da sessão
        unset($_SESSION['product_create_message']);
        unset($_SESSION['product_create_message_type']);
        unset($_SESSION['product_create_form_data']);
    }

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
            $db->beginTransaction();

            // 1. Validar e criar produto
            $nome = trim($_POST['nome'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug) && !empty($nome)) {
                $slug = $this->generateSlug($nome);
            }
            $sku = trim($_POST['sku'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            $precoRegular = !empty($_POST['preco_regular']) ? (float)$_POST['preco_regular'] : 0;
            $precoPromocional = !empty($_POST['preco_promocional']) ? (float)$_POST['preco_promocional'] : null;
            $dataPromocaoInicio = !empty($_POST['data_promocao_inicio']) ? $_POST['data_promocao_inicio'] : null;
            $dataPromocaoFim = !empty($_POST['data_promocao_fim']) ? $_POST['data_promocao_fim'] : null;
            $quantidadeEstoque = !empty($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
            $statusEstoque = $_POST['status_estoque'] ?? 'outofstock';
            $gerenciaEstoque = isset($_POST['gerencia_estoque']) ? 1 : 0;
            $permitePedidosFalta = isset($_POST['permite_pedidos_falta']) ? 1 : 0;
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            if (empty($nome)) {
                throw new \Exception('Nome do produto é obrigatório');
            }

            $stmt = $db->prepare("
                INSERT INTO produtos (
                    tenant_id, nome, slug, sku, status, exibir_no_catalogo,
                    preco_regular, preco_promocional, data_promocao_inicio, data_promocao_fim,
                    quantidade_estoque, status_estoque, gerencia_estoque, permite_pedidos_falta,
                    descricao_curta, descricao, created_at, updated_at
                ) VALUES (
                    :tenant_id, :nome, :slug, :sku, :status, :exibir_no_catalogo,
                    :preco_regular, :preco_promocional, :data_promocao_inicio, :data_promocao_fim,
                    :quantidade_estoque, :status_estoque, :gerencia_estoque, :permite_pedidos_falta,
                    :descricao_curta, :descricao, NOW(), NOW()
                )
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'slug' => $slug,
                'sku' => $sku,
                'status' => $status,
                'exibir_no_catalogo' => $exibirNoCatalogo,
                'preco_regular' => $precoRegular,
                'preco_promocional' => $precoPromocional,
                'data_promocao_inicio' => $dataPromocaoInicio,
                'data_promocao_fim' => $dataPromocaoFim,
                'quantidade_estoque' => $quantidadeEstoque,
                'status_estoque' => $statusEstoque,
                'gerencia_estoque' => $gerenciaEstoque,
                'permite_pedidos_falta' => $permitePedidosFalta,
                'descricao_curta' => $descricaoCurta,
                'descricao' => $descricao
            ]);

            $produtoId = $db->lastInsertId();

            // 2. Processar imagem de destaque
            $this->processMainImage($db, $tenantId, $produtoId, ['imagem_principal' => null]);

            // 3. Processar galeria
            $this->processGallery($db, $tenantId, $produtoId);

            // 4. Processar vídeos
            $this->processVideos($db, $tenantId, $produtoId);

            // 5. Vincular categorias
            if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
                $categoriaIds = array_map('intval', $_POST['categorias']);
                
                // Validar que todas as categorias pertencem ao tenant
                $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
                $stmt = $db->prepare("
                    SELECT id FROM categorias 
                    WHERE id IN ({$placeholders}) AND tenant_id = ?
                ");
                $stmt->execute(array_merge($categoriaIds, [$tenantId]));
                $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
                
                // Inserir relações
                $stmt = $db->prepare("
                    INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                foreach ($validCategoriaIds as $categoriaId) {
                    $stmt->execute([$tenantId, $produtoId, $categoriaId]);
                }
            }

            $db->commit();
            $_SESSION['product_edit_message'] = 'Produto criado com sucesso!';
            $_SESSION['product_edit_message_type'] = 'success';
            header('Location: ' . $this->getBasePath() . '/admin/produtos/' . $produtoId);
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['product_create_message'] = 'Erro ao criar produto: ' . $e->getMessage();
            $_SESSION['product_create_message_type'] = 'error';
            $_SESSION['product_create_form_data'] = $_POST;
            header('Location: ' . $this->getBasePath() . '/admin/produtos/novo');
            exit;
        }
    }

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

        // Buscar produto
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE id = :id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId
        ]);
        $produto = $stmt->fetch();

        if (!$produto) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Produto não encontrado']);
            return;
        }

        // Buscar todas as imagens
        $stmt = $db->prepare("
            SELECT * FROM produto_imagens 
            WHERE tenant_id = :tenant_id 
            AND produto_id = :produto_id 
            ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $imagens = $stmt->fetchAll();

        // Separar imagem principal e galeria
        $imagemPrincipal = null;
        $galeria = [];
        foreach ($imagens as $img) {
            if ($img['tipo'] === 'main') {
                $imagemPrincipal = $img;
            } else {
                $galeria[] = $img;
            }
        }

        // Buscar vídeos
        $stmt = $db->prepare("
            SELECT * FROM produto_videos 
            WHERE tenant_id = :tenant_id 
            AND produto_id = :produto_id 
            ORDER BY ordem ASC, id ASC
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $videos = $stmt->fetchAll();

        // Buscar categorias do produto
        $stmt = $db->prepare("
            SELECT c.* 
            FROM categorias c
            JOIN produto_categorias pc ON pc.categoria_id = c.id
            WHERE pc.tenant_id = :tenant_id_pc
            AND c.tenant_id = :tenant_id_c
            AND pc.produto_id = :produto_id
            ORDER BY c.nome ASC
        ");
        $stmt->execute([
            'tenant_id_pc' => $tenantId,
            'tenant_id_c' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $categoriasProduto = $stmt->fetchAll();
        $categoriasProdutoIds = array_column($categoriasProduto, 'id');

        // Buscar todas as categorias do tenant para o formulário
        $stmt = $db->prepare("
            SELECT id, nome, slug
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $todasCategorias = $stmt->fetchAll();

        // Buscar tags (somente leitura por enquanto)
        $stmt = $db->prepare("
            SELECT t.* 
            FROM tags t
            JOIN produto_tags pt ON pt.tag_id = t.id
            WHERE pt.tenant_id = :tenant_id_pt
            AND t.tenant_id = :tenant_id_t
            AND pt.produto_id = :produto_id
            ORDER BY t.nome ASC
        ");
        $stmt->execute([
            'tenant_id_pt' => $tenantId,
            'tenant_id_t' => $tenantId,
            'produto_id' => $produto['id']
        ]);
        $tags = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        
        $this->viewWithLayout('admin/layouts/store', 'admin/products/edit-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Produto: ' . $produto['nome'],
            'produto' => $produto,
            'imagemPrincipal' => $imagemPrincipal,
            'galeria' => $galeria,
            'videos' => $videos,
            'categorias' => $categoriasProduto,
            'categoriasProdutoIds' => $categoriasProdutoIds,
            'todasCategorias' => $todasCategorias,
            'tags' => $tags,
            'message' => $_SESSION['product_edit_message'] ?? null,
            'messageType' => $_SESSION['product_edit_message_type'] ?? null
        ]);

        // Limpar mensagens da sessão
        unset($_SESSION['product_edit_message']);
        unset($_SESSION['product_edit_message_type']);
    }

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

        // Buscar produto
        $stmt = $db->prepare("
            SELECT * FROM produtos 
            WHERE id = :id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId
        ]);
        $produto = $stmt->fetch();

        if (!$produto) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Produto não encontrado']);
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Atualizar dados básicos do produto
            $nome = trim($_POST['nome'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug) && !empty($nome)) {
                $slug = $this->generateSlug($nome);
            }
            $sku = trim($_POST['sku'] ?? '');
            $status = $_POST['status'] ?? 'draft';
            $precoRegular = !empty($_POST['preco_regular']) ? (float)$_POST['preco_regular'] : 0;
            $precoPromocional = !empty($_POST['preco_promocional']) ? (float)$_POST['preco_promocional'] : null;
            $dataPromocaoInicio = !empty($_POST['data_promocao_inicio']) ? $_POST['data_promocao_inicio'] : null;
            $dataPromocaoFim = !empty($_POST['data_promocao_fim']) ? $_POST['data_promocao_fim'] : null;
            $quantidadeEstoque = !empty($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
            $statusEstoque = $_POST['status_estoque'] ?? 'outofstock';
            $gerenciaEstoque = isset($_POST['gerencia_estoque']) ? 1 : 0;
            $permitePedidosFalta = isset($_POST['permite_pedidos_falta']) ? 1 : 0;
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            $stmt = $db->prepare("
                UPDATE produtos SET
                    nome = :nome,
                    slug = :slug,
                    sku = :sku,
                    status = :status,
                    exibir_no_catalogo = :exibir_no_catalogo,
                    preco_regular = :preco_regular,
                    preco_promocional = :preco_promocional,
                    data_promocao_inicio = :data_promocao_inicio,
                    data_promocao_fim = :data_promocao_fim,
                    quantidade_estoque = :quantidade_estoque,
                    status_estoque = :status_estoque,
                    gerencia_estoque = :gerencia_estoque,
                    permite_pedidos_falta = :permite_pedidos_falta,
                    descricao_curta = :descricao_curta,
                    descricao = :descricao,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'nome' => $nome,
                'slug' => $slug,
                'sku' => $sku,
                'status' => $status,
                'exibir_no_catalogo' => $exibirNoCatalogo,
                'preco_regular' => $precoRegular,
                'preco_promocional' => $precoPromocional,
                'data_promocao_inicio' => $dataPromocaoInicio,
                'data_promocao_fim' => $dataPromocaoFim,
                'quantidade_estoque' => $quantidadeEstoque,
                'status_estoque' => $statusEstoque,
                'gerencia_estoque' => $gerenciaEstoque,
                'permite_pedidos_falta' => $permitePedidosFalta,
                'descricao_curta' => $descricaoCurta,
                'descricao' => $descricao,
                'id' => $id,
                'tenant_id' => $tenantId
            ]);

            // 2. Processar imagem de destaque
            $this->processMainImage($db, $tenantId, $id, $produto);

            // 3. Processar galeria
            $this->processGallery($db, $tenantId, $id);

            // 4. Processar vídeos
            $this->processVideos($db, $tenantId, $id);

            // 5. Atualizar categorias (sync: remover antigas e adicionar novas)
            // Remover todas as categorias atuais do produto
            $stmt = $db->prepare("
                DELETE FROM produto_categorias 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $id
            ]);

            // Adicionar novas categorias se houver
            if (!empty($_POST['categorias']) && is_array($_POST['categorias'])) {
                $categoriaIds = array_map('intval', $_POST['categorias']);
                
                // Validar que todas as categorias pertencem ao tenant
                if (!empty($categoriaIds)) {
                    $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
                    $stmt = $db->prepare("
                        SELECT id FROM categorias 
                        WHERE id IN ({$placeholders}) AND tenant_id = ?
                    ");
                    $stmt->execute(array_merge($categoriaIds, [$tenantId]));
                    $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
                    
                    // Inserir relações
                    $stmt = $db->prepare("
                        INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");
                    foreach ($validCategoriaIds as $categoriaId) {
                        $stmt->execute([$tenantId, $id, $categoriaId]);
                    }
                }
            }

            $db->commit();
            $_SESSION['product_edit_message'] = 'Produto atualizado com sucesso!';
            $_SESSION['product_edit_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['product_edit_message'] = 'Erro ao atualizar produto: ' . $e->getMessage();
            $_SESSION['product_edit_message_type'] = 'error';
        }

        header('Location: ' . $this->getBasePath() . '/admin/produtos/' . $id);
        exit;
    }

    private function processMainImage($db, $tenantId, $produtoId, $produto): void
    {
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        // Verificar se veio caminho de imagem da biblioteca (prioridade sobre upload)
        if (!empty($_POST['imagem_destaque_path']) && is_string($_POST['imagem_destaque_path'])) {
            $imagePath = trim($_POST['imagem_destaque_path']);
            
            // Validar que o caminho é válido e pertence ao tenant
            // Aceita caminhos da pasta produtos ou outras pastas do tenant
            $tenantPath = "/uploads/tenants/{$tenantId}/";
            if (strpos($imagePath, $tenantPath) === 0) {
                
                // Verificar se arquivo existe fisicamente
                $filePath = __DIR__ . '/../../public' . $imagePath;
                if (file_exists($filePath)) {
                    // Remover antiga main (virar gallery)
                    $stmt = $db->prepare("
                        SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                        FROM produto_imagens 
                        WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                    ");
                    $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                    $result = $stmt->fetch();
                    $novaOrdem = ($result['max_ordem'] ?? 0) + 1;
                    
                    $stmt = $db->prepare("
                        UPDATE produto_imagens 
                        SET tipo = 'gallery', ordem = :ordem
                        WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'main'
                    ");
                    $stmt->execute([
                        'ordem' => $novaOrdem,
                        'tenant_id' => $tenantId, 
                        'produto_id' => $produtoId
                    ]);

                    // Criar nova main usando caminho da biblioteca
                    $fileSize = filesize($filePath);
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $filePath);
                    finfo_close($finfo);

                    $stmt = $db->prepare("
                        INSERT INTO produto_imagens (
                            tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                            mime_type, tamanho_arquivo
                        ) VALUES (
                            :tenant_id, :produto_id, 'main', 0, :caminho_arquivo,
                            :mime_type, :tamanho_arquivo
                        )
                    ");
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId,
                        'caminho_arquivo' => $imagePath,
                        'mime_type' => $mimeType,
                        'tamanho_arquivo' => $fileSize
                    ]);

                    // Atualizar produtos.imagem_principal
                    $stmt = $db->prepare("
                        UPDATE produtos 
                        SET imagem_principal = :imagem_principal 
                        WHERE id = :id AND tenant_id = :tenant_id
                    ");
                    $stmt->execute([
                        'imagem_principal' => $imagePath,
                        'id' => $produtoId,
                        'tenant_id' => $tenantId
                    ]);
                }
            }
        }
        // Verificar se veio arquivo novo (upload direto)
        elseif (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagem_destaque'];
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                throw new \Exception('Tipo de arquivo não permitido. Use apenas imagens (JPG, PNG, GIF, WEBP).');
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
                $relativePath = "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
                
                // Remover antiga main (virar gallery)
                // Primeiro buscar maior ordem da galeria
                $stmt = $db->prepare("
                    SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                    FROM produto_imagens 
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                ");
                $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                $result = $stmt->fetch();
                $novaOrdem = ($result['max_ordem'] ?? 0) + 1;
                
                $stmt = $db->prepare("
                    UPDATE produto_imagens 
                    SET tipo = 'gallery', ordem = :ordem
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'main'
                ");
                $stmt->execute([
                    'ordem' => $novaOrdem,
                    'tenant_id' => $tenantId, 
                    'produto_id' => $produtoId
                ]);

                // Criar nova main
                $fileSize = filesize($destFile);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $destFile);
                finfo_close($finfo);

                $stmt = $db->prepare("
                    INSERT INTO produto_imagens (
                        tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                        mime_type, tamanho_arquivo
                    ) VALUES (
                        :tenant_id, :produto_id, 'main', 0, :caminho_arquivo,
                        :mime_type, :tamanho_arquivo
                    )
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId,
                    'caminho_arquivo' => $relativePath,
                    'mime_type' => $mimeType,
                    'tamanho_arquivo' => $fileSize
                ]);

                // Atualizar produtos.imagem_principal
                $stmt = $db->prepare("
                    UPDATE produtos 
                    SET imagem_principal = :imagem_principal 
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'imagem_principal' => $relativePath,
                    'id' => $produtoId,
                    'tenant_id' => $tenantId
                ]);
            }
        }

        // Verificar se veio seleção de imagem da galeria
        if (!empty($_POST['main_from_gallery_id'])) {
            $galleryId = (int)$_POST['main_from_gallery_id'];
            
            // Buscar imagem da galeria
            $stmt = $db->prepare("
                SELECT * FROM produto_imagens 
                WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'id' => $galleryId,
                'tenant_id' => $tenantId,
                'produto_id' => $produtoId
            ]);
            $imagem = $stmt->fetch();

            if ($imagem) {
                // Remover antiga main (virar gallery)
                // Primeiro buscar maior ordem da galeria
                $stmt = $db->prepare("
                    SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                    FROM produto_imagens 
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                ");
                $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                $result = $stmt->fetch();
                $novaOrdem = ($result['max_ordem'] ?? 0) + 1;
                
                $stmt = $db->prepare("
                    UPDATE produto_imagens 
                    SET tipo = 'gallery', ordem = :ordem
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'main' AND id != :new_main_id
                ");
                $stmt->execute([
                    'ordem' => $novaOrdem,
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId,
                    'new_main_id' => $galleryId
                ]);

                // Nova main
                $stmt = $db->prepare("
                    UPDATE produto_imagens 
                    SET tipo = 'main', ordem = 0 
                    WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                ");
                $stmt->execute([
                    'id' => $galleryId,
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId
                ]);

                // Atualizar produtos.imagem_principal
                $stmt = $db->prepare("
                    UPDATE produtos 
                    SET imagem_principal = :imagem_principal 
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'imagem_principal' => $imagem['caminho_arquivo'],
                    'id' => $produtoId,
                    'tenant_id' => $tenantId
                ]);
            }
        }
    }

    private function processGallery($db, $tenantId, $produtoId): void
    {
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        // Remover imagens marcadas
        if (!empty($_POST['remove_imagens']) && is_array($_POST['remove_imagens'])) {
            foreach ($_POST['remove_imagens'] as $imgId) {
                $imgId = (int)$imgId;
                // Buscar caminho antes de deletar
                $stmt = $db->prepare("
                    SELECT caminho_arquivo FROM produto_imagens 
                    WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                ");
                $stmt->execute(['id' => $imgId, 'tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                $img = $stmt->fetch();
                
                // Buscar registro antes de deletar
                $stmt = $db->prepare("
                    SELECT tipo, caminho_arquivo FROM produto_imagens 
                    WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                ");
                $stmt->execute(['id' => $imgId, 'tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                $img = $stmt->fetch();
                
                if ($img && $img['tipo'] !== 'main') {
                    // Deletar arquivo físico
                    $filePath = __DIR__ . '/../../public' . $img['caminho_arquivo'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    
                    // Deletar registro
                    $stmt = $db->prepare("
                        DELETE FROM produto_imagens 
                        WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                    ");
                    $stmt->execute(['id' => $imgId, 'tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                }
            }
        }

        // Processar caminhos de imagens da biblioteca (prioridade sobre upload)
        if (!empty($_POST['galeria_paths']) && is_array($_POST['galeria_paths'])) {
            // Buscar maior ordem atual
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $result = $stmt->fetch();
            $ordem = ($result['max_ordem'] ?? 0) + 1;

            foreach ($_POST['galeria_paths'] as $imagePath) {
                $imagePath = trim($imagePath);
                
                // Validar que o caminho é válido e pertence ao tenant
                // Aceita caminhos de qualquer pasta do tenant (produtos, banners, etc.)
                $tenantPath = "/uploads/tenants/{$tenantId}/";
                if (!empty($imagePath) && strpos($imagePath, $tenantPath) === 0) {
                    
                    // Verificar se arquivo existe fisicamente
                    $filePath = __DIR__ . '/../../public' . $imagePath;
                    if (file_exists($filePath)) {
                        // Verificar se imagem já não está na galeria deste produto
                        $stmtCheck = $db->prepare("
                            SELECT COUNT(*) as count 
                            FROM produto_imagens 
                            WHERE tenant_id = :tenant_id AND produto_id = :produto_id 
                            AND caminho_arquivo = :caminho
                        ");
                        $stmtCheck->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'caminho' => $imagePath
                        ]);
                        $exists = $stmtCheck->fetch()['count'] > 0;
                        
                        if (!$exists) {
                            $fileSize = filesize($filePath);
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $filePath);
                            finfo_close($finfo);

                            $stmt = $db->prepare("
                                INSERT INTO produto_imagens (
                                    tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                                    mime_type, tamanho_arquivo
                                ) VALUES (
                                    :tenant_id, :produto_id, 'gallery', :ordem, :caminho_arquivo,
                                    :mime_type, :tamanho_arquivo
                                )
                            ");
                            $stmt->execute([
                                'tenant_id' => $tenantId,
                                'produto_id' => $produtoId,
                                'ordem' => $ordem++,
                                'caminho_arquivo' => $imagePath,
                                'mime_type' => $mimeType,
                                'tamanho_arquivo' => $fileSize
                            ]);
                        }
                    }
                }
            }
        }
        
        // Processar upload de novas imagens (se não veio da biblioteca)
        if (!empty($_FILES['galeria']['name'][0])) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            
            // Buscar maior ordem atual
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $result = $stmt->fetch();
            $ordem = ($result['max_ordem'] ?? 0) + 1;

            foreach ($_FILES['galeria']['name'] as $key => $name) {
                if ($_FILES['galeria']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['galeria']['name'][$key],
                        'type' => $_FILES['galeria']['type'][$key],
                        'tmp_name' => $_FILES['galeria']['tmp_name'][$key],
                        'error' => $_FILES['galeria']['error'][$key],
                        'size' => $_FILES['galeria']['size'][$key]
                    ];

                    if (!in_array($file['type'], $allowedTypes)) {
                        continue;
                    }

                    $fileName = $this->sanitizeFileName($file['name']);
                    $destFile = $uploadsPath . '/' . $fileName;
                    
                    if (file_exists($destFile)) {
                        $info = pathinfo($fileName);
                        $fileName = $info['filename'] . '_' . time() . '_' . $key . '.' . $info['extension'];
                        $destFile = $uploadsPath . '/' . $fileName;
                    }

                    if (move_uploaded_file($file['tmp_name'], $destFile)) {
                        $relativePath = "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
                        $fileSize = filesize($destFile);
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $destFile);
                        finfo_close($finfo);

                        $stmt = $db->prepare("
                            INSERT INTO produto_imagens (
                                tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                                mime_type, tamanho_arquivo
                            ) VALUES (
                                :tenant_id, :produto_id, 'gallery', :ordem, :caminho_arquivo,
                                :mime_type, :tamanho_arquivo
                            )
                        ");
                        $stmt->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'ordem' => $ordem++,
                            'caminho_arquivo' => $relativePath,
                            'mime_type' => $mimeType,
                            'tamanho_arquivo' => $fileSize
                        ]);
                    }
                }
            }
        }

        // Atualizar ordem das imagens da galeria (após remoção e upload)
        if (!empty($_POST['galeria_ordem']) && is_array($_POST['galeria_ordem'])) {
            foreach ($_POST['galeria_ordem'] as $imagemId => $novaOrdem) {
                $imagemId = (int)$imagemId;
                $novaOrdem = (int)$novaOrdem;
                
                // Verificar se a imagem existe e pertence ao produto/tenant
                $stmt = $db->prepare("
                    SELECT id FROM produto_imagens 
                    WHERE id = :id 
                    AND tenant_id = :tenant_id 
                    AND produto_id = :produto_id
                    AND tipo = 'gallery'
                ");
                $stmt->execute([
                    'id' => $imagemId,
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId
                ]);
                
                if ($stmt->fetch()) {
                    // Atualizar ordem
                    $stmt = $db->prepare("
                        UPDATE produto_imagens 
                        SET ordem = :ordem 
                        WHERE id = :id 
                        AND tenant_id = :tenant_id 
                        AND produto_id = :produto_id
                        AND tipo = 'gallery'
                    ");
                    $stmt->execute([
                        'ordem' => $novaOrdem,
                        'id' => $imagemId,
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId
                    ]);
                }
            }
        }
    }

    private function processVideos($db, $tenantId, $produtoId): void
    {
        // Atualizar vídeos existentes
        if (!empty($_POST['videos']) && is_array($_POST['videos'])) {
            foreach ($_POST['videos'] as $videoId => $videoData) {
                $videoId = (int)$videoId;
                $titulo = trim($videoData['titulo'] ?? '');
                $url = trim($videoData['url'] ?? '');
                $ativo = isset($videoData['ativo']) ? 1 : 0;

                if (!empty($url)) {
                    $stmt = $db->prepare("
                        UPDATE produto_videos 
                        SET titulo = :titulo, url = :url, ativo = :ativo, updated_at = NOW()
                        WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                    ");
                    $stmt->execute([
                        'titulo' => $titulo ?: null,
                        'url' => $url,
                        'ativo' => $ativo,
                        'id' => $videoId,
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId
                    ]);
                }
            }
        }

        // Remover vídeos marcados
        if (!empty($_POST['remove_videos']) && is_array($_POST['remove_videos'])) {
            foreach ($_POST['remove_videos'] as $videoId) {
                $videoId = (int)$videoId;
                $stmt = $db->prepare("
                    DELETE FROM produto_videos 
                    WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                ");
                $stmt->execute([
                    'id' => $videoId,
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId
                ]);
            }
        }

        // Adicionar novos vídeos
        if (!empty($_POST['novo_videos']) && is_array($_POST['novo_videos'])) {
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                FROM produto_videos 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $result = $stmt->fetch();
            $ordem = ($result['max_ordem'] ?? 0) + 1;

            foreach ($_POST['novo_videos'] as $novoVideo) {
                $titulo = trim($novoVideo['titulo'] ?? '');
                $url = trim($novoVideo['url'] ?? '');

                if (!empty($url)) {
                    $stmt = $db->prepare("
                        INSERT INTO produto_videos (
                            tenant_id, produto_id, titulo, url, ordem, ativo
                        ) VALUES (
                            :tenant_id, :produto_id, :titulo, :url, :ordem, 1
                        )
                    ");
                    $stmt->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId,
                        'titulo' => $titulo ?: null,
                        'url' => $url,
                        'ordem' => $ordem++
                    ]);
                }
            }
        }
    }

    private function sanitizeFileName($fileName): string
    {
        $fileName = basename($fileName);
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        return $fileName;
    }

    private function generateSlug($text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        return $text;
    }

    private function getBasePath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
            return '/ecommerce-v1.0/public';
        }
        return '';
    }
}

