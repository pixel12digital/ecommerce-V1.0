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
        $categoriaId = isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' 
            ? (int)$_GET['categoria_id'] 
            : null;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 20;
        $offset = ($currentPage - 1) * $perPage;
        
        // Parâmetros de ordenação
        $sort = $_GET['sort'] ?? '';
        $direction = strtolower($_GET['direction'] ?? 'asc');
        $orderBy = 'produtos.data_criacao DESC'; // Padrão
        
        // Validar e aplicar ordenação por nome
        if ($sort === 'name' && in_array($direction, ['asc', 'desc'])) {
            $orderBy = 'produtos.nome ' . strtoupper($direction);
        }

        // Construir query com filtros (qualificar colunas com nome da tabela para evitar ambiguidade)
        $where = ['produtos.tenant_id = :tenant_id'];
        $params = [];

        if (!empty($q)) {
            // Se for numérico, pode ser ID ou SKU
            if (is_numeric($q)) {
                $where[] = '(produtos.id = :q_id OR produtos.nome LIKE :q_nome OR produtos.sku LIKE :q_sku)';
                $params['q_id'] = (int)$q;
                $params['q_nome'] = '%' . $q . '%';
                $params['q_sku'] = '%' . $q . '%';
            } else {
                // Buscar apenas por nome ou SKU
                $where[] = '(produtos.nome LIKE :q_nome OR produtos.sku LIKE :q_sku)';
                $params['q_nome'] = '%' . $q . '%';
                $params['q_sku'] = '%' . $q . '%';
            }
        }

        // Só filtrar por status se não estiver vazio e não for "todos"
        if (!empty($status) && $status !== 'todos') {
            $where[] = 'produtos.status = :status';
            $params['status'] = $status;
        }

        // Filtro "Somente produtos com imagem"
        if ($somenteComImagem) {
            $where[] = 'produtos.imagem_principal IS NOT NULL AND produtos.imagem_principal != \'\'';
        }

        // Construir JOIN e filtro de categoria se necessário
        $joinClause = '';
        $useDistinct = false;
        if ($categoriaId !== null) {
            $joinClause = 'LEFT JOIN produto_categorias pc ON pc.produto_id = produtos.id AND pc.tenant_id = :tenant_id_pc';
            $where[] = 'pc.categoria_id = :categoria_id';
            $params['categoria_id'] = $categoriaId;
            $useDistinct = true;
        }

        $whereClause = implode(' AND ', $where);

        // Contar total
        $distinctClause = $useDistinct ? 'COUNT(DISTINCT produtos.id)' : 'COUNT(*)';
        $countSql = "
            SELECT {$distinctClause} as total 
            FROM produtos 
            {$joinClause}
            WHERE {$whereClause}
        ";
        $stmt = $db->prepare($countSql);
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        if ($categoriaId !== null) {
            $stmt->bindValue(':tenant_id_pc', $tenantId, \PDO::PARAM_INT);
        }
        foreach ($params as $key => $value) {
            // Se for q_id ou categoria_id, usar PARAM_INT, senão PARAM_STR
            $paramType = ($key === 'q_id' || $key === 'categoria_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $paramType);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];

        // Buscar produtos
        $distinctSelect = $useDistinct ? 'DISTINCT ' : '';
        $selectSql = "
            SELECT {$distinctSelect}produtos.* 
            FROM produtos 
            {$joinClause}
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $db->prepare($selectSql);
        
        // Bind dos parâmetros WHERE
        $stmt->bindValue(':tenant_id', $tenantId, \PDO::PARAM_INT);
        if ($categoriaId !== null) {
            $stmt->bindValue(':tenant_id_pc', $tenantId, \PDO::PARAM_INT);
        }
        foreach ($params as $key => $value) {
            // Se for q_id ou categoria_id, usar PARAM_INT, senão PARAM_STR
            $paramType = ($key === 'q_id' || $key === 'categoria_id') ? \PDO::PARAM_INT : \PDO::PARAM_STR;
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

            // Buscar categorias do produto usando método unificado
            $categoriasData = $this->getCategoriasDoProduto($produto['id'], $tenantId);
            $produto['categorias'] = $categoriasData['nomes'];
            $produto['categoria_ids'] = $categoriasData['ids'];
        }

        $totalPages = ceil($total / $perPage);

        // Buscar todas as categorias do tenant para o modal de categorias rápidas
        $stmt = $db->prepare("
            SELECT id, nome, slug, categoria_pai_id
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFlat = $stmt->fetchAll();
        $todasCategorias = $this->buildCategorySelectOptions($categoriasFlat);

        // Buscar lista simples de categorias para o filtro (flat, sem hierarquia)
        $stmt = $db->prepare("
            SELECT id, nome
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFiltro = $stmt->fetchAll();

        $tenant = TenantContext::tenant();
        $this->viewWithLayout('admin/layouts/store', 'admin/products/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Produtos',
            'produtos' => $produtos,
            'todasCategorias' => $todasCategorias,
            'categoriasFiltro' => $categoriasFiltro,
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
                'somente_com_imagem' => $somenteComImagem,
                'categoria_id' => $categoriaId
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

        // Buscar todas as categorias do tenant com hierarquia
        $stmt = $db->prepare("
            SELECT id, nome, slug, categoria_pai_id
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFlat = $stmt->fetchAll();
        
        // Construir lista hierárquica para exibição
        $categorias = $this->buildCategorySelectOptions($categoriasFlat);

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
            
            // Processar preço regular (converter vírgula para ponto)
            $precoRegularStr = trim($_POST['preco_regular'] ?? '0');
            $precoRegularStr = str_replace(',', '.', $precoRegularStr);
            $precoRegular = !empty($precoRegularStr) ? (float)$precoRegularStr : 0;
            
            // Processar preço promocional (converter vírgula para ponto)
            $precoPromocionalStr = trim($_POST['preco_promocional'] ?? '');
            $precoPromocional = null;
            if (!empty($precoPromocionalStr)) {
                $precoPromocionalStr = str_replace(',', '.', $precoPromocionalStr);
                $precoPromocional = (float)$precoPromocionalStr;
            }
            
            $dataPromocaoInicio = !empty($_POST['data_promocao_inicio']) ? $_POST['data_promocao_inicio'] : null;
            $dataPromocaoFim = !empty($_POST['data_promocao_fim']) ? $_POST['data_promocao_fim'] : null;
            $quantidadeEstoque = !empty($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
            $gerenciaEstoque = isset($_POST['gerencia_estoque']) ? 1 : 0;
            
            // Determinar status_estoque baseado em gerencia_estoque e quantidade_estoque
            // Se gerencia_estoque = 1 e quantidade_estoque > 0 → instock
            // Se gerencia_estoque = 1 e quantidade_estoque = 0 → outofstock
            // Se gerencia_estoque = 0 → usar valor do formulário ou padrão instock
            $statusEstoqueInput = $_POST['status_estoque'] ?? null;
            if ($gerenciaEstoque == 1) {
                // Se gerencia estoque está ativo, determinar status baseado na quantidade
                $statusEstoque = ($quantidadeEstoque > 0) ? 'instock' : 'outofstock';
            } else {
                // Se não gerencia estoque, usar valor do formulário ou padrão instock
                $statusEstoque = $statusEstoqueInput ?? 'instock';
            }
            
            $permitePedidosFalta = isset($_POST['permite_pedidos_falta']) ? 1 : 0;
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            if (empty($nome)) {
                throw new \Exception('Nome do produto é obrigatório');
            }

            // Preço principal: usar preco_promocional se existir, senão preco_regular
            $precoPrincipal = $precoPromocional ?? $precoRegular;

            $stmt = $db->prepare("
                INSERT INTO produtos (
                    tenant_id, nome, slug, sku, status, exibir_no_catalogo,
                    preco, preco_regular, preco_promocional, data_promocao_inicio, data_promocao_fim,
                    quantidade_estoque, status_estoque, gerencia_estoque, permite_pedidos_falta,
                    descricao_curta, descricao, created_at, updated_at
                ) VALUES (
                    :tenant_id, :nome, :slug, :sku, :status, :exibir_no_catalogo,
                    :preco, :preco_regular, :preco_promocional, :data_promocao_inicio, :data_promocao_fim,
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
                'preco' => $precoPrincipal,
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

        // Buscar todas as categorias do tenant para o formulário (com hierarquia)
        $stmt = $db->prepare("
            SELECT id, nome, slug, categoria_pai_id
            FROM categorias
            WHERE tenant_id = :tenant_id
            ORDER BY nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $categoriasFlat = $stmt->fetchAll();
        
        // Construir lista hierárquica para exibição
        $todasCategorias = $this->buildCategorySelectOptions($categoriasFlat);

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

        // Capturar contexto de navegação (página, filtros, ordenação) para preservar ao voltar
        $navigationContext = [
            'page' => isset($_GET['page']) ? (int)$_GET['page'] : null,
            'q' => $_GET['q'] ?? '',
            'status' => $_GET['status'] ?? '',
            'categoria_id' => isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '' ? (int)$_GET['categoria_id'] : null,
            'somente_com_imagem' => isset($_GET['somente_com_imagem']) && $_GET['somente_com_imagem'] == '1',
            'sort' => $_GET['sort'] ?? '',
            'direction' => $_GET['direction'] ?? ''
        ];

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
            'messageType' => $_SESSION['product_edit_message_type'] ?? null,
            'navigationContext' => $navigationContext
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
        
        // Log para debug - verificar o que está chegando no POST
        error_log("ProductController::update - Produto ID: {$id}, Tenant ID: {$tenantId}");
        error_log("ProductController::update - POST keys: " . implode(', ', array_keys($_POST)));
        if (isset($_POST['imagem_destaque_path'])) {
            error_log("ProductController::update - imagem_destaque_path recebido: " . var_export($_POST['imagem_destaque_path'], true));
        } else {
            error_log("ProductController::update - imagem_destaque_path NÃO foi enviado no POST");
        }
        if (isset($_POST['galeria_paths'])) {
            error_log("ProductController::update - galeria_paths recebido: " . var_export($_POST['galeria_paths'], true));
        } else {
            error_log("ProductController::update - galeria_paths NÃO foi enviado no POST");
        }

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
            
            // Processar preço regular (converter vírgula para ponto)
            $precoRegularStr = trim($_POST['preco_regular'] ?? '0');
            $precoRegularStr = str_replace(',', '.', $precoRegularStr);
            $precoRegular = !empty($precoRegularStr) ? (float)$precoRegularStr : 0;
            
            // Processar preço promocional (converter vírgula para ponto)
            $precoPromocionalStr = trim($_POST['preco_promocional'] ?? '');
            $precoPromocional = null;
            if (!empty($precoPromocionalStr)) {
                $precoPromocionalStr = str_replace(',', '.', $precoPromocionalStr);
                $precoPromocional = (float)$precoPromocionalStr;
            }
            
            $dataPromocaoInicio = !empty($_POST['data_promocao_inicio']) ? $_POST['data_promocao_inicio'] : null;
            $dataPromocaoFim = !empty($_POST['data_promocao_fim']) ? $_POST['data_promocao_fim'] : null;
            $quantidadeEstoque = !empty($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
            $gerenciaEstoque = isset($_POST['gerencia_estoque']) ? 1 : 0;
            
            // Determinar status_estoque baseado em gerencia_estoque e quantidade_estoque
            // Se gerencia_estoque = 1 e quantidade_estoque > 0 → instock
            // Se gerencia_estoque = 1 e quantidade_estoque = 0 → outofstock
            // Se gerencia_estoque = 0 → usar valor do formulário ou padrão instock
            $statusEstoqueInput = $_POST['status_estoque'] ?? null;
            if ($gerenciaEstoque == 1) {
                // Se gerencia estoque está ativo, determinar status baseado na quantidade
                $statusEstoque = ($quantidadeEstoque > 0) ? 'instock' : 'outofstock';
            } else {
                // Se não gerencia estoque, usar valor do formulário ou padrão instock
                $statusEstoque = $statusEstoqueInput ?? 'instock';
            }
            
            $permitePedidosFalta = isset($_POST['permite_pedidos_falta']) ? 1 : 0;
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            // Preço principal: usar preco_promocional se existir, senão preco_regular
            $precoPrincipal = $precoPromocional ?? $precoRegular;

            $stmt = $db->prepare("
                UPDATE produtos SET
                    nome = :nome,
                    slug = :slug,
                    sku = :sku,
                    status = :status,
                    exibir_no_catalogo = :exibir_no_catalogo,
                    preco = :preco,
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
                'preco' => $precoPrincipal,
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

        // Preservar contexto de navegação (página, filtros, ordenação) ao redirecionar
        $returnTo = $_POST['return_to'] ?? 'edit'; // 'edit' ou 'list'
        $navigationContext = [
            'page' => isset($_POST['nav_page']) && $_POST['nav_page'] !== '' ? (int)$_POST['nav_page'] : null,
            'q' => $_POST['nav_q'] ?? '',
            'status' => $_POST['nav_status'] ?? '',
            'categoria_id' => isset($_POST['nav_categoria_id']) && $_POST['nav_categoria_id'] !== '' ? (int)$_POST['nav_categoria_id'] : null,
            'somente_com_imagem' => isset($_POST['nav_somente_com_imagem']) && $_POST['nav_somente_com_imagem'] == '1',
            'sort' => $_POST['nav_sort'] ?? '',
            'direction' => $_POST['nav_direction'] ?? ''
        ];

        if ($returnTo === 'list') {
            // Redirecionar para listagem preservando contexto
            $queryParams = [];
            if ($navigationContext['page'] !== null && $navigationContext['page'] > 1) {
                $queryParams['page'] = $navigationContext['page'];
            }
            if (!empty($navigationContext['q'])) {
                $queryParams['q'] = $navigationContext['q'];
            }
            if (!empty($navigationContext['status'])) {
                $queryParams['status'] = $navigationContext['status'];
            }
            if ($navigationContext['categoria_id'] !== null) {
                $queryParams['categoria_id'] = $navigationContext['categoria_id'];
            }
            if ($navigationContext['somente_com_imagem']) {
                $queryParams['somente_com_imagem'] = '1';
            }
            if (!empty($navigationContext['sort'])) {
                $queryParams['sort'] = $navigationContext['sort'];
            }
            if (!empty($navigationContext['direction'])) {
                $queryParams['direction'] = $navigationContext['direction'];
            }
            
            $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
            header('Location: ' . $this->getBasePath() . '/admin/produtos' . $queryString);
        } else {
            // Redirecionar para edição preservando contexto
            $queryParams = [];
            if ($navigationContext['page'] !== null && $navigationContext['page'] > 1) {
                $queryParams['page'] = $navigationContext['page'];
            }
            if (!empty($navigationContext['q'])) {
                $queryParams['q'] = $navigationContext['q'];
            }
            if (!empty($navigationContext['status'])) {
                $queryParams['status'] = $navigationContext['status'];
            }
            if ($navigationContext['categoria_id'] !== null) {
                $queryParams['categoria_id'] = $navigationContext['categoria_id'];
            }
            if ($navigationContext['somente_com_imagem']) {
                $queryParams['somente_com_imagem'] = '1';
            }
            if (!empty($navigationContext['sort'])) {
                $queryParams['sort'] = $navigationContext['sort'];
            }
            if (!empty($navigationContext['direction'])) {
                $queryParams['direction'] = $navigationContext['direction'];
            }
            
            $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) : '';
            header('Location: ' . $this->getBasePath() . '/admin/produtos/' . $id . $queryString);
        }
        exit;
    }

    private function processMainImage($db, $tenantId, $produtoId, $produto): void
    {
        error_log("ProductController::processMainImage - Iniciando para produto {$produtoId}, tenant {$tenantId}");
        
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        // Verificar se usuário quer remover a imagem de destaque explicitamente
        $removeFeatured = isset($_POST['remove_featured']) && $_POST['remove_featured'] == '1';
        
        // Verificar se veio caminho de imagem da biblioteca (prioridade sobre upload)
        // IMPORTANTE: Verificar se o campo existe no POST, mesmo que vazio (para limpar imagem)
        if (isset($_POST['imagem_destaque_path']) && is_string($_POST['imagem_destaque_path'])) {
            error_log("ProductController::processMainImage - Campo imagem_destaque_path encontrado: " . var_export($_POST['imagem_destaque_path'], true));
            $imagePath = trim($_POST['imagem_destaque_path']);
            
            // Se o caminho está vazio OU se remove_featured está marcado, remover imagem existente
            if (empty($imagePath) || $removeFeatured) {
                // IMPORTANTE: Remover apenas a associação do produto com a imagem de destaque
                // NÃO apagar o arquivo físico da biblioteca de mídia
                // O arquivo continua disponível na biblioteca e pode ser reutilizado
                
                // Remover registro da imagem principal da tabela produto_imagens
                $stmt = $db->prepare("
                    DELETE FROM produto_imagens 
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'main'
                ");
                $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                error_log("ProductController::processMainImage - ✅ Associação de imagem de destaque removida (desvinculada do produto)");

                // Atualizar produtos.imagem_principal para NULL
                $stmt = $db->prepare("
                    UPDATE produtos 
                    SET imagem_principal = NULL 
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'id' => $produtoId,
                    'tenant_id' => $tenantId
                ]);
                error_log("ProductController::processMainImage - ✅ Campo imagem_principal limpo no produto {$produtoId}");
                error_log("ProductController::processMainImage - ℹ️ Arquivo físico preservado na biblioteca de mídia");
                
                // Retornar após remover imagem
                return;
            }
            
            // Validar que o caminho é válido e pertence ao tenant
            // Aceita caminhos da pasta produtos ou outras pastas do tenant
            $tenantPath = "/uploads/tenants/{$tenantId}/";
            error_log("ProductController::processMainImage - Validando caminho: {$imagePath} contra tenantPath: {$tenantPath}");
            if (strpos($imagePath, $tenantPath) === 0) {
                error_log("ProductController::processMainImage - Caminho válido, verificando arquivo físico");
                
                // Verificar se arquivo existe fisicamente
                // Usar a mesma lógica do config/paths.php para detectar caminho correto
                $paths = require __DIR__ . '/../../../../config/paths.php';
                $root = $paths['root'];
                
                // Tentar caminho de desenvolvimento primeiro
                $devPath = $root . '/public' . $imagePath;
                $prodPath = $root . $imagePath;
                
                // Verificar qual caminho existe
                if (file_exists($devPath)) {
                    $filePath = $devPath;
                } elseif (file_exists($prodPath)) {
                    $filePath = $prodPath;
                } else {
                    // Fallback: usar caminho de desenvolvimento
                    $filePath = $devPath;
                }
                
                error_log("ProductController::processMainImage - Caminho completo do arquivo: {$filePath}");
                error_log("ProductController::processMainImage - Arquivo existe? " . (file_exists($filePath) ? 'SIM' : 'NÃO'));
                
                if (file_exists($filePath)) {
                    // Verificar se já existe uma imagem com esse caminho (independente do tipo)
                    $stmtCheck = $db->prepare("
                        SELECT id, tipo FROM produto_imagens 
                        WHERE tenant_id = :tenant_id AND produto_id = :produto_id 
                        AND caminho_arquivo = :caminho
                        LIMIT 1
                    ");
                    $stmtCheck->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId,
                        'caminho' => $imagePath
                    ]);
                    $existingImage = $stmtCheck->fetch();
                    
                    if ($existingImage) {
                        // Se já existe, apenas atualizar para main
                        $stmt = $db->prepare("
                            UPDATE produto_imagens 
                            SET tipo = 'main', ordem = 0
                            WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                        ");
                        $stmt->execute([
                            'id' => $existingImage['id'],
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId
                        ]);
                    } else {
                        // Remover antiga main (virar gallery) se existir
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
                        try {
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
                        } catch (\Exception $e) {
                            error_log("ProductController::processMainImage - Erro ao inserir imagem principal: " . $e->getMessage() . " (caminho: {$imagePath})");
                            // Não retornar aqui, tentar atualizar produtos.imagem_principal mesmo assim
                        }
                    }

                    // Atualizar produtos.imagem_principal (sempre, mesmo se já existia)
                    try {
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
                        error_log("ProductController::processMainImage - Imagem principal atualizada com sucesso: {$imagePath} para produto {$produtoId}");
                    } catch (\Exception $e) {
                        error_log("ProductController::processMainImage - Erro ao atualizar produtos.imagem_principal: " . $e->getMessage());
                    }
                    
                    // Retornar após processar caminho da biblioteca (não processar upload)
                    return;
                } else {
                    // Arquivo não existe fisicamente - log para debug
                    error_log("ProductController::processMainImage - Arquivo não encontrado: {$filePath} (caminho: {$imagePath})");
                    // Não retornar aqui, deixar tentar upload direto se houver
                }
            } else {
                // Caminho inválido - log para debug
                error_log("ProductController::processMainImage - Caminho inválido ou não pertence ao tenant: {$imagePath} (tenant: {$tenantId}, tenantPath esperado: {$tenantPath})");
                // Não retornar aqui, deixar tentar upload direto se houver
            }
        } else {
            // Campo não foi enviado no POST - log para debug
            error_log("ProductController::processMainImage - Campo imagem_destaque_path não foi enviado no POST. POST keys: " . implode(', ', array_keys($_POST)));
        }
        // Verificar se veio arquivo novo (upload direto)
        if (isset($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK) {
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
        error_log("ProductController::processGallery - Iniciando para produto {$produtoId}, tenant {$tenantId}");
        
        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        // SINCRONIZAÇÃO COMPLETA DA GALERIA
        // O array galeria_paths[] representa o estado DEFINITIVO da galeria do produto
        // Qualquer imagem que não esteja nesse array deve ser removida da galeria (mas não da biblioteca)
        
        if (isset($_POST['galeria_paths']) && is_array($_POST['galeria_paths'])) {
            $galleryPaths = array_map('trim', $_POST['galeria_paths']);
            $galleryPaths = array_filter($galleryPaths, function($path) {
                return !empty($path);
            });
            
            error_log("ProductController::processGallery - 📋 SINCRONIZAÇÃO DA GALERIA");
            error_log("ProductController::processGallery -   Total de caminhos recebidos no POST: " . count($galleryPaths));
            error_log("ProductController::processGallery -   Caminhos: " . var_export($galleryPaths, true));
            
            // 1. Buscar imagens atuais da galeria no banco
            $stmt = $db->prepare("
                SELECT id, caminho_arquivo, ordem 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                ORDER BY ordem ASC
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $currentImages = $stmt->fetchAll();
            $totalBefore = count($currentImages);
            
            error_log("ProductController::processGallery -   Total de imagens no banco ANTES: {$totalBefore}");
            
            // 2. Identificar imagens que devem ser REMOVIDAS (estão no banco mas não estão no POST)
            $pathsToKeep = array_flip($galleryPaths); // Para busca rápida
            $imagesToRemove = [];
            
            foreach ($currentImages as $currentImg) {
                $currentPath = $currentImg['caminho_arquivo'];
                if (!isset($pathsToKeep[$currentPath])) {
                    $imagesToRemove[] = $currentImg;
                }
            }
            
            // 3. Remover imagens que não estão mais na lista (apenas desvincular do produto)
            if (!empty($imagesToRemove)) {
                error_log("ProductController::processGallery - 🗑️ Removendo " . count($imagesToRemove) . " imagens que não estão mais na lista");
                foreach ($imagesToRemove as $imgToRemove) {
                    // IMPORTANTE: Remover apenas a associação do produto com a imagem (tabela produto_imagens)
                    // NÃO apagar o arquivo físico da biblioteca de mídia
                    $stmt = $db->prepare("
                        DELETE FROM produto_imagens 
                        WHERE id = :id AND tenant_id = :tenant_id AND produto_id = :produto_id
                    ");
                    $stmt->execute([
                        'id' => $imgToRemove['id'],
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId
                    ]);
                    error_log("ProductController::processGallery - ✅ Associação removida (imagem desvinculada do produto): ID {$imgToRemove['id']}, caminho: {$imgToRemove['caminho_arquivo']}");
                    error_log("ProductController::processGallery - ℹ️ Arquivo físico preservado na biblioteca de mídia: {$imgToRemove['caminho_arquivo']}");
                }
            } else {
                error_log("ProductController::processGallery - ✅ Nenhuma imagem precisa ser removida");
            }
            
            // 4. Processar caminhos de imagens da biblioteca (adicionar novas ou manter existentes)
            // Buscar maior ordem atual (após remoções)
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(ordem), 0) as max_ordem 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $result = $stmt->fetch();
            $ordem = ($result['max_ordem'] ?? 0) + 1;
            
            // Criar mapa de caminhos existentes para busca rápida (após remoções)
            $existingPaths = [];
            $stmt = $db->prepare("
                SELECT caminho_arquivo 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $existingImagesAfterRemoval = $stmt->fetchAll();
            foreach ($existingImagesAfterRemoval as $existingImg) {
                $existingPaths[$existingImg['caminho_arquivo']] = true;
            }

            $processedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            $isDebug = defined('APP_DEBUG') && APP_DEBUG;
            
            // Processar cada caminho enviado (manter ordem do array)
            foreach ($galleryPaths as $index => $imagePath) {
                
                // Log sempre (não apenas em debug) para rastrear cada imagem
                error_log("ProductController::processGallery - [IMAGEM #{$index}] Iniciando processamento: '{$imagePath}'");
                
                // Pular se vazio
                if (empty($imagePath)) {
                    error_log("ProductController::processGallery - [IMAGEM #{$index}] ⚠️ Caminho vazio, pulando");
                    $skippedCount++;
                    continue;
                }
                
                // Validar que o caminho é válido e pertence ao tenant
                // Aceita caminhos de qualquer pasta do tenant (produtos, banners, etc.)
                $tenantPath = "/uploads/tenants/{$tenantId}/";
                if (strpos($imagePath, $tenantPath) === 0) {
                    
                    // Verificar se arquivo existe fisicamente
                    // Usar a mesma lógica do config/paths.php para detectar caminho correto
                    $paths = require __DIR__ . '/../../../../config/paths.php';
                    $root = $paths['root'];
                    
                    // Tentar caminho de desenvolvimento primeiro
                    $devPath = $root . '/public' . $imagePath;
                    $prodPath = $root . $imagePath;
                    
                    // Verificar qual caminho existe
                    if (file_exists($devPath)) {
                        $filePath = $devPath;
                    } elseif (file_exists($prodPath)) {
                        $filePath = $prodPath;
                    } else {
                        // Fallback: usar caminho de desenvolvimento
                        $filePath = $devPath;
                    }
                    
                    if ($isDebug) {
                        error_log("ProductController::processGallery - Caminho completo do arquivo: {$filePath}");
                        error_log("ProductController::processGallery - Arquivo existe? " . (file_exists($filePath) ? 'SIM' : 'NÃO'));
                    }
                    
                    if (file_exists($filePath)) {
                        // Verificar se imagem já está associada a este produto (busca rápida no mapa)
                        $exists = isset($existingPaths[$imagePath]);
                        
                        // Log detalhado sobre verificação de existência
                        if ($exists) {
                            error_log("ProductController::processGallery - 🔍 [IMAGEM #{$index}] Imagem já existe na GALERIA, será preservada: {$imagePath}");
                        } else {
                            error_log("ProductController::processGallery - 🔍 [IMAGEM #{$index}] Imagem NÃO existe na galeria, será inserida: {$imagePath}");
                        }
                        
                        if (!$exists) {
                            try {
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
                                $currentOrdem = $ordem++;
                                $stmt->execute([
                                    'tenant_id' => $tenantId,
                                    'produto_id' => $produtoId,
                                    'ordem' => $currentOrdem,
                                    'caminho_arquivo' => $imagePath,
                                    'mime_type' => $mimeType,
                                    'tamanho_arquivo' => $fileSize
                                ]);
                                $insertedId = $db->lastInsertId();
                                $processedCount++;
                                // Log sempre (não apenas em debug) para rastrear inserções
                                error_log("ProductController::processGallery - ✅ [IMAGEM #{$index}] INSERIDA COM SUCESSO: {$imagePath} (ordem: {$currentOrdem}, ID inserido: {$insertedId})");
                            } catch (\Exception $e) {
                                error_log("ProductController::processGallery - ❌ Erro ao inserir imagem: " . $e->getMessage() . " (caminho: {$imagePath})");
                                $errorCount++;
                            }
                        } else {
                            // Imagem já existe, apenas preservar (já foi contabilizada em $skippedCount acima)
                            $skippedCount++;
                        }
                    } else {
                        error_log("ProductController::processGallery - ⚠️ [IMAGEM #{$index}] Arquivo não encontrado: {$filePath} (caminho: {$imagePath})");
                        $errorCount++;
                    }
                } else {
                    error_log("ProductController::processGallery - ⚠️ [IMAGEM #{$index}] Caminho inválido: {$imagePath} (tenant: {$tenantId}, tenantPath esperado: {$tenantPath})");
                    $errorCount++;
                }
            }
            
            // 5. Verificar resultado final da sincronização
            $stmt = $db->prepare("
                SELECT COUNT(*) as total 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $totalAfter = $stmt->fetch()['total'];
            
            // Log resumo sempre
            error_log("ProductController::processGallery - 📊 RESUMO FINAL DA SINCRONIZAÇÃO:");
            error_log("ProductController::processGallery -   Total recebido no POST: " . count($galleryPaths));
            error_log("ProductController::processGallery -   Total ANTES: {$totalBefore}");
            error_log("ProductController::processGallery -   Imagens removidas: " . count($imagesToRemove));
            error_log("ProductController::processGallery -   Imagens novas inseridas: {$processedCount}");
            error_log("ProductController::processGallery -   Imagens já existentes (preservadas): {$skippedCount}");
            error_log("ProductController::processGallery -   Imagens com erro: {$errorCount}");
            error_log("ProductController::processGallery -   Total APÓS: {$totalAfter}");
            
            // Verificar se sincronização está correta
            if ($totalAfter == count($galleryPaths)) {
                error_log("ProductController::processGallery - ✅ SINCRONIZAÇÃO CONCLUÍDA: Total no banco ({$totalAfter}) corresponde ao total enviado (" . count($galleryPaths) . ")");
            } else {
                error_log("ProductController::processGallery - ⚠️ ATENÇÃO: Total no banco ({$totalAfter}) difere do total enviado (" . count($galleryPaths) . ")");
            }
            
            // Logs detalhados (apenas em debug)
            if ($isDebug) {
                // Listar todas as imagens da galeria após processamento
                $stmt = $db->prepare("
                    SELECT id, caminho_arquivo, ordem 
                    FROM produto_imagens 
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                    ORDER BY ordem ASC
                ");
                $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
                $allImages = $stmt->fetchAll();
                error_log("ProductController::processGallery - Lista completa de imagens na galeria após sincronização:");
                foreach ($allImages as $img) {
                    error_log("ProductController::processGallery -   - ID: {$img['id']}, Ordem: {$img['ordem']}, Caminho: {$img['caminho_arquivo']}");
                }
            }
        } else {
            error_log("ProductController::processGallery - ⚠️ Campo galeria_paths não foi enviado no POST ou não é array. POST keys: " . implode(', ', array_keys($_POST)));
            error_log("ProductController::processGallery - ℹ️ Se não houver galeria_paths, a galeria não será modificada (mantém estado atual)");
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
                continue; // Pular categorias sem ID
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
     * Constrói opções hierárquicas para select/checkboxes (com indentação)
     */
    private function buildCategorySelectOptions(array $categorias): array
    {
        // Se não houver categorias, retornar array vazio
        if (empty($categorias)) {
            return [];
        }

        $tree = $this->buildCategoryTree($categorias);
        $options = [];
        
        $this->flattenTreeForSelect($tree, $options, 0);
        
        return $options;
    }

    /**
     * Achatamento recursivo da árvore para select/checkboxes
     */
    private function flattenTreeForSelect(array $tree, array &$options, int $level): void
    {
        if (empty($tree)) {
            return;
        }

        foreach ($tree as $cat) {
            if (!isset($cat['id']) || !isset($cat['nome'])) {
                continue; // Pular itens inválidos
            }

            $prefix = str_repeat('-- ', $level);
            $options[] = [
                'id' => $cat['id'],
                'nome' => $prefix . $cat['nome'],
                'nome_original' => $cat['nome'],
                'slug' => $cat['slug'] ?? '',
                'level' => $level,
                'categoria_pai_id' => $cat['categoria_pai_id'] ?? null
            ];

            // Processar filhos recursivamente
            if (!empty($cat['filhos']) && is_array($cat['filhos'])) {
                $this->flattenTreeForSelect($cat['filhos'], $options, $level + 1);
            }
        }
    }

    /**
     * Alterna o status do produto (ativo/inativo)
     */
    public function toggleStatus(int $id): void
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
            SELECT id, status FROM produtos 
            WHERE id = :id AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId
        ]);
        $produto = $stmt->fetch();

        if (!$produto) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
                exit;
            }
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Produto não encontrado']);
            return;
        }

        // Alternar status: publish <-> draft
        $novoStatus = ($produto['status'] === 'publish') ? 'draft' : 'publish';

        // Atualizar no banco
        $stmt = $db->prepare("
            UPDATE produtos 
            SET status = :status, updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'status' => $novoStatus,
            'id' => $id,
            'tenant_id' => $tenantId
        ]);

        // Preparar resposta
        $label = \App\Support\LangHelper::productStatusLabel($novoStatus);
        $badgeClass = $novoStatus === 'publish' ? 'publish' : 'draft';
        $labelHtml = '<span class="admin-status-badge ' . $badgeClass . '">' . htmlspecialchars($label) . '</span>';

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'novo_status' => $novoStatus,
                'label_html' => $labelHtml
            ]);
            exit;
        }

        // Se não for AJAX, redirecionar
        $_SESSION['product_edit_message'] = 'Status do produto atualizado com sucesso!';
        $_SESSION['product_edit_message_type'] = 'success';
        header('Location: ' . $this->getBasePath() . '/admin/produtos');
        exit;
    }

    /**
     * Atualiza categorias do produto rapidamente (via modal)
     */
    public function updateCategoriesQuick(int $id): void
    {
        // Logs sempre ativos (não só para produto 354)
        error_log("=== updateCategoriesQuick chamado === Produto ID: {$id}");
        error_log("POST recebido em updateCategoriesQuick: " . var_export($_POST, true));
        error_log("HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'N/A'));
        error_log("HTTP_ACCEPT: " . ($_SERVER['HTTP_ACCEPT'] ?? 'N/A'));
        error_log("isAjaxRequest(): " . ($this->isAjaxRequest() ? 'SIM' : 'NAO'));
        
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        error_log("Tenant ID obtido: {$tenantId}");
        $db = Database::getConnection();

        // Buscar produto
        $stmt = $db->prepare("
            SELECT id FROM produtos 
            WHERE id = :id AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId
        ]);
        $produto = $stmt->fetch();

        if (!$produto) {
            error_log("ERRO: Produto ID {$id} não encontrado para tenant {$tenantId}");
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
                exit;
            }
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Produto não encontrado']);
            return;
        }

        try {
            $db->beginTransaction();
            error_log("Transação iniciada para produto {$id}");

            // Receber categorias do POST
            // Com categorias[]=7, PHP cria $_POST['categorias'] como array automaticamente
            // Mas mantemos fallback para caso venha como string única
            $categoriaIds = [];
            if (!empty($_POST['categorias'])) {
                if (is_array($_POST['categorias'])) {
                    $categoriaIds = array_map('intval', $_POST['categorias']);
                } else {
                    // Se veio como string única (fallback), converter para array
                    $categoriaIds = [(int)$_POST['categorias']];
                }
            }
            
            error_log("Categorias recebidas (brutas): " . json_encode($_POST['categorias'] ?? null));
            error_log("Tipo recebido: " . gettype($_POST['categorias'] ?? null));
            error_log("Categorias após normalização: " . json_encode($categoriaIds));

            // Validar que todas as categorias pertencem ao tenant
            if (!empty($categoriaIds)) {
                $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
                $stmt = $db->prepare("
                    SELECT id FROM categorias 
                    WHERE id IN ({$placeholders}) AND tenant_id = ?
                ");
                $stmt->execute(array_merge($categoriaIds, [$tenantId]));
                $validCategoriaIds = array_column($stmt->fetchAll(), 'id');
                
                error_log("Categorias válidas para tenant {$tenantId}: " . json_encode($validCategoriaIds));
            } else {
                $validCategoriaIds = [];
                error_log("Nenhuma categoria recebida no POST ou array vazio");
            }

            // Remover todas as categorias atuais do produto
            $stmt = $db->prepare("
                DELETE FROM produto_categorias 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $id
            ]);
            
            $deletedRows = $stmt->rowCount();
            error_log("DELETE produto_categorias executado para produto {$id}, tenant {$tenantId}. Linhas removidas: {$deletedRows}");

            // Inserir novas categorias
            $insertedCount = 0;
            if (!empty($validCategoriaIds)) {
                $stmt = $db->prepare("
                    INSERT INTO produto_categorias (tenant_id, produto_id, categoria_id, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                
                foreach ($validCategoriaIds as $categoriaId) {
                    $result = $stmt->execute([$tenantId, $id, $categoriaId]);
                    if ($result) {
                        $insertedCount++;
                        error_log("INSERT produto_categorias OK - Produto {$id}, Categoria {$categoriaId}, Tenant {$tenantId}");
                    } else {
                        error_log("ERRO INSERT produto_categorias - Produto {$id}, Categoria {$categoriaId}, Tenant {$tenantId}");
                    }
                }
            }
            
            error_log("Total de categorias inseridas para produto {$id}: {$insertedCount}");
            
            // Se houver categorias válidas mas nenhuma foi inserida, lançar exception
            if (!empty($validCategoriaIds) && $insertedCount === 0) {
                throw new \RuntimeException("Nenhuma categoria foi inserida para o produto {$id}, mesmo havendo categorias válidas.");
            }
            
            // Verificar vínculos DEPOIS de inserir (para debug)
            $stmtAfter = $db->prepare("
                SELECT * FROM produto_categorias 
                WHERE produto_id = :produto_id AND tenant_id = :tenant_id
            ");
            $stmtAfter->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
            $vinculosDepois = $stmtAfter->fetchAll();
            error_log("Vínculos DEPOIS do INSERT: " . count($vinculosDepois));
            foreach ($vinculosDepois as $v) {
                error_log("  - produto_id: {$v['produto_id']}, tenant_id: {$v['tenant_id']}, categoria_id: {$v['categoria_id']}");
            }

            $db->commit();
            error_log("Transação commitada com sucesso para produto {$id}");

            // Buscar categorias atualizadas usando método unificado (garante consistência com a listagem)
            // Isso garante que o que retornamos é exatamente o que está no banco depois do INSERT
            $categoriasData = $this->getCategoriasDoProduto($id, $tenantId);
            error_log("Categorias buscadas após INSERT - IDs: " . json_encode($categoriasData['ids']) . ", Nomes: " . json_encode($categoriasData['nomes']));

            if ($this->isAjaxRequest()) {
                error_log("Retornando resposta JSON para requisição AJAX");
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'categorias_labels_html' => $categoriasData['labels_html'],
                    'categoria_ids' => $categoriasData['ids'],
                    'categorias_nomes' => $categoriasData['nomes']
                ]);
                exit;
            }
            
            error_log("Retornando redirecionamento (não é AJAX)");

            $_SESSION['product_edit_message'] = 'Categorias atualizadas com sucesso!';
            $_SESSION['product_edit_message_type'] = 'success';
            header('Location: ' . $this->getBasePath() . '/admin/produtos');
            exit;
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("ERRO em updateCategoriesQuick - Produto {$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao atualizar categorias: ' . $e->getMessage()
                ]);
                exit;
            }

            $_SESSION['product_edit_message'] = 'Erro ao atualizar categorias: ' . $e->getMessage();
            $_SESSION['product_edit_message_type'] = 'error';
            header('Location: ' . $this->getBasePath() . '/admin/produtos');
            exit;
        }
    }

    /**
     * Exclui um produto
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
            $db->beginTransaction();

            // Buscar produto
            $stmt = $db->prepare("
                SELECT id, nome FROM produtos 
                WHERE id = :id AND tenant_id = :tenant_id 
                LIMIT 1
            ");
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $tenantId
            ]);
            $produto = $stmt->fetch();

            if (!$produto) {
                throw new \Exception('Produto não encontrado');
            }

            // Remover vínculos em produto_categorias
            $stmt = $db->prepare("
                DELETE FROM produto_categorias 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $id
            ]);

            // Remover imagens (apenas vínculos, não arquivos físicos)
            $stmt = $db->prepare("
                DELETE FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $id
            ]);

            // Remover vídeos
            $stmt = $db->prepare("
                DELETE FROM produto_videos 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $id
            ]);

            // Remover tags (se houver tabela produto_tags)
            // Nota: Verificar se existe antes de executar
            try {
                $stmt = $db->prepare("
                    DELETE FROM produto_tags 
                    WHERE tenant_id = :tenant_id AND produto_id = :produto_id
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $id
                ]);
            } catch (\Exception $e) {
                // Tabela pode não existir, ignorar
            }

            // Remover o produto
            $stmt = $db->prepare("
                DELETE FROM produtos 
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'id' => $id,
                'tenant_id' => $tenantId
            ]);

            $db->commit();

            $_SESSION['product_edit_message'] = 'Produto excluído com sucesso!';
            $_SESSION['product_edit_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['product_edit_message'] = 'Erro ao excluir produto: ' . $e->getMessage();
            $_SESSION['product_edit_message_type'] = 'error';
        }

        header('Location: ' . $this->getBasePath() . '/admin/produtos');
        exit;
    }

    /**
     * Retorna as categorias de um produto (ids, nomes e HTML dos badges)
     * Método unificado usado tanto na listagem quanto no retorno JSON
     * 
     * @param int $produtoId ID do produto
     * @param int $tenantId ID do tenant
     * @return array ['ids' => [], 'nomes' => [], 'labels_html' => '']
     */
    private function getCategoriasDoProduto(int $produtoId, int $tenantId): array
    {
        $db = Database::getConnection();
        
        // Query unificada: começa de produto_categorias e faz JOIN com categorias
        $stmt = $db->prepare("
            SELECT c.id, c.nome
            FROM produto_categorias pc
            JOIN categorias c
              ON c.id = pc.categoria_id
             AND c.tenant_id = pc.tenant_id
            WHERE pc.produto_id = ?
              AND pc.tenant_id = ?
            ORDER BY c.nome ASC
            LIMIT 5
        ");
        $stmt->execute([$produtoId, $tenantId]);
        $categorias = $stmt->fetchAll();
        
        $ids = array_column($categorias, 'id');
        $nomes = array_column($categorias, 'nome');
        
        // Montar HTML de badges no mesmo padrão da listagem
        $labelsHtml = '';
        $maxBadges = 2;
        
        if (!empty($nomes)) {
            $total = count($nomes);
            $mostradas = array_slice($nomes, 0, $maxBadges);
            
            $labelsHtml = '<div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">';
            foreach ($mostradas as $nome) {
                $labelsHtml .= '<span style="display: inline-block; padding: 0.25rem 0.5rem; background: #e0e0e0; border-radius: 4px; font-size: 0.75rem; color: #555;">' . htmlspecialchars($nome) . '</span>';
            }
            
            if ($total > $maxBadges) {
                $restantes = $total - $maxBadges;
                $labelsHtml .= '<span style="display: inline-block; padding: 0.25rem 0.5rem; background: #f0f0f0; border-radius: 4px; font-size: 0.75rem; color: #999;">+' . $restantes . '</span>';
            }
            
            $labelsHtml .= '</div>';
        } else {
            $labelsHtml = '<span style="color: #999; font-style: italic; font-size: 0.875rem;">Sem categorias</span>';
        }
        
        return [
            'ids' => $ids,
            'nomes' => $nomes,
            'labels_html' => $labelsHtml
        ];
    }

    /**
     * Verifica se a requisição é AJAX
     */
    private function isAjaxRequest(): bool
    {
        $isXmlHttpRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        $isJsonAccept = !empty($_SERVER['HTTP_ACCEPT']) && 
                        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        
        return $isXmlHttpRequest || $isJsonAccept;
    }
}

