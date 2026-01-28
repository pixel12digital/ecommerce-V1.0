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
            $tipo = $_POST['tipo'] ?? 'simple';
            $goVariations = isset($_POST['go_variations']) && $_POST['go_variations'] == '1';
            
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
            
            // Para produto variável, estoque é sempre 0 e gerencia_estoque = 0
            // O estoque real é gerenciado por variação
            if ($tipo === 'variable') {
                $quantidadeEstoque = 0;
                $gerenciaEstoque = 0;
                $statusEstoque = 'outofstock';
            } else {
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
            }
            
            $permitePedidosFalta = isset($_POST['permite_pedidos_falta']) ? 1 : 0;
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            // Processar dimensões e peso (opcionais)
            $peso = !empty($_POST['peso']) ? (float)$_POST['peso'] : null;
            $comprimento = !empty($_POST['comprimento']) ? (float)$_POST['comprimento'] : null;
            $largura = !empty($_POST['largura']) ? (float)$_POST['largura'] : null;
            $altura = !empty($_POST['altura']) ? (float)$_POST['altura'] : null;

            if (empty($nome)) {
                throw new \Exception('Nome do produto é obrigatório');
            }

            // Preço principal: usar preco_promocional se existir, senão preco_regular
            $precoPrincipal = $precoPromocional ?? $precoRegular;

            $stmt = $db->prepare("
                INSERT INTO produtos (
                    tenant_id, nome, slug, sku, tipo, status, exibir_no_catalogo,
                    preco, preco_regular, preco_promocional, data_promocao_inicio, data_promocao_fim,
                    quantidade_estoque, status_estoque, gerencia_estoque, permite_pedidos_falta,
                    descricao_curta, descricao, peso, comprimento, largura, altura,
                    created_at, updated_at
                ) VALUES (
                    :tenant_id, :nome, :slug, :sku, :tipo, :status, :exibir_no_catalogo,
                    :preco, :preco_regular, :preco_promocional, :data_promocao_inicio, :data_promocao_fim,
                    :quantidade_estoque, :status_estoque, :gerencia_estoque, :permite_pedidos_falta,
                    :descricao_curta, :descricao, :peso, :comprimento, :largura, :altura,
                    NOW(), NOW()
                )
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'nome' => $nome,
                'slug' => $slug,
                'sku' => $sku,
                'tipo' => $tipo,
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
                'peso' => $peso,
                'comprimento' => $comprimento,
                'largura' => $largura,
                'altura' => $altura
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
            
            // Se for produto variável e go_variations = 1, redirecionar para edição com âncora
            if ($tipo === 'variable' && $goVariations) {
                $this->redirect('/admin/produtos/' . $produtoId . '/editar#atributos');
            } else {
                $this->redirect('/admin/produtos/' . $produtoId . '/editar');
            }
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

        // Buscar atributos do produto (se tipo = variable)
        $atributosProduto = [];
        $atributosProdutoIds = [];
        $termosPorAtributo = [];
        $variacoes = [];

        if ($produto['tipo'] === 'variable') {
            // Buscar atributos associados ao produto
            $stmt = $db->prepare("
                SELECT a.*, pa.atributo_id, pa.usado_para_variacao, pa.ordem
                FROM produto_atributos pa
                INNER JOIN atributos a ON a.id = pa.atributo_id
                WHERE pa.produto_id = :produto_id AND pa.tenant_id = :tenant_id
                ORDER BY pa.ordem ASC, a.ordem ASC
            ");
            $stmt->execute(['produto_id' => $produto['id'], 'tenant_id' => $tenantId]);
            $atributosProduto = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $atributosProdutoIds = array_column($atributosProduto, 'atributo_id');

            // Para cada atributo, buscar termos selecionados
            // Garantir que a coluna imagem_produto existe antes de fazer SELECT
            $this->ensureImagemProdutoColumn($db);
            
            foreach ($atributosProduto as $attr) {
                // Garantir que atributo_id existe (pode ser 'id' ou 'atributo_id')
                $atributoId = $attr['atributo_id'] ?? $attr['id'] ?? null;
                if (!$atributoId) {
                    continue; // Pular se não tiver ID
                }
                
                $stmtTermos = $db->prepare("
                    SELECT at.*, pat.id as produto_atributo_termo_id, pat.imagem_produto
                    FROM produto_atributo_termos pat
                    INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
                    WHERE pat.produto_id = :produto_id
                    AND pat.atributo_id = :atributo_id
                    AND pat.tenant_id = :tenant_id
                    ORDER BY at.ordem ASC, at.nome ASC
                ");
                $stmtTermos->execute([
                    'produto_id' => $produto['id'],
                    'atributo_id' => $atributoId,
                    'tenant_id' => $tenantId
                ]);
                $termosPorAtributo[$atributoId] = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);
            }

            // Buscar variações do produto
            $stmt = $db->prepare("
                SELECT pv.*,
                       GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|') as signature
                FROM produto_variacoes pv
                LEFT JOIN produto_variacao_atributos pva ON pva.variacao_id = pv.id
                WHERE pv.produto_id = :produto_id AND pv.tenant_id = :tenant_id
                GROUP BY pv.id
                ORDER BY pv.id ASC
            ");
            $stmt->execute(['produto_id' => $produto['id'], 'tenant_id' => $tenantId]);
            $variacoesRaw = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Para cada variação, buscar atributos detalhados
            foreach ($variacoesRaw as $variacao) {
                $stmtAttr = $db->prepare("
                    SELECT a.nome as atributo_nome, at.nome as termo_nome, pva.atributo_id, pva.atributo_termo_id
                    FROM produto_variacao_atributos pva
                    INNER JOIN atributos a ON a.id = pva.atributo_id
                    INNER JOIN atributo_termos at ON at.id = pva.atributo_termo_id
                    WHERE pva.variacao_id = :variacao_id AND pva.tenant_id = :tenant_id
                    ORDER BY a.ordem ASC, at.ordem ASC
                ");
                $stmtAttr->execute(['variacao_id' => $variacao['id'], 'tenant_id' => $tenantId]);
                $variacao['atributos'] = $stmtAttr->fetchAll(\PDO::FETCH_ASSOC);
                $variacoes[] = $variacao;
            }
        }

        // Buscar todos os atributos disponíveis (para seleção)
        $stmt = $db->prepare("
            SELECT * FROM atributos 
            WHERE tenant_id = :tenant_id 
            ORDER BY ordem ASC, nome ASC
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $todosAtributos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Para cada atributo disponível, buscar seus termos
        $termosPorAtributoDisponivel = [];
        foreach ($todosAtributos as $attr) {
            $stmtTermos = $db->prepare("
                SELECT * FROM atributo_termos 
                WHERE atributo_id = :atributo_id AND tenant_id = :tenant_id 
                ORDER BY ordem ASC, nome ASC
            ");
            $stmtTermos->execute(['atributo_id' => $attr['id'], 'tenant_id' => $tenantId]);
            $termosPorAtributoDisponivel[$attr['id']] = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);
        }

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
            'atributosProduto' => $atributosProduto,
            'atributosProdutoIds' => $atributosProdutoIds,
            'termosPorAtributo' => $termosPorAtributo,
            'todosAtributos' => $todosAtributos,
            'termosPorAtributoDisponivel' => $termosPorAtributoDisponivel,
            'variacoes' => $variacoes,
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
            
            $tipo = $_POST['tipo'] ?? 'simple';
            
            // Para produto variável, estoque é sempre 0 e gerencia_estoque = 0
            // O estoque real é gerenciado por variação
            if ($tipo === 'variable') {
                $quantidadeEstoque = 0;
                $gerenciaEstoque = 0;
                $statusEstoque = 'outofstock';
                $permitePedidosFalta = 0; // Backorder é por variação
            } else {
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
            }
            
            $exibirNoCatalogo = isset($_POST['exibir_no_catalogo']) ? 1 : 0;
            $descricaoCurta = $_POST['descricao_curta'] ?? '';
            $descricao = $_POST['descricao'] ?? '';

            // Processar dimensões e peso (opcionais)
            $peso = !empty($_POST['peso']) ? (float)$_POST['peso'] : null;
            $comprimento = !empty($_POST['comprimento']) ? (float)$_POST['comprimento'] : null;
            $largura = !empty($_POST['largura']) ? (float)$_POST['largura'] : null;
            $altura = !empty($_POST['altura']) ? (float)$_POST['altura'] : null;

            // Preço principal: usar preco_promocional se existir, senão preco_regular
            $precoPrincipal = $precoPromocional ?? $precoRegular;

            $stmt = $db->prepare("
                UPDATE produtos SET
                    nome = :nome,
                    slug = :slug,
                    sku = :sku,
                    tipo = :tipo,
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
                    peso = :peso,
                    comprimento = :comprimento,
                    largura = :largura,
                    altura = :altura,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'nome' => $nome,
                'slug' => $slug,
                'sku' => $sku,
                'tipo' => $tipo,
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
                'peso' => $peso,
                'comprimento' => $comprimento,
                'largura' => $largura,
                'altura' => $altura,
                'id' => $id,
                'tenant_id' => $tenantId
            ]);

            // Processar atributos do produto (se tipo = variable)
            if ($tipo === 'variable') {
                $this->processProductAttributes($db, $tenantId, $id);
            } else {
                // Se não é variável, remover atributos e variações
                $stmt = $db->prepare("DELETE FROM produto_atributos WHERE produto_id = :produto_id AND tenant_id = :tenant_id");
                $stmt->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
                $stmt = $db->prepare("DELETE FROM produto_atributo_termos WHERE produto_id = :produto_id AND tenant_id = :tenant_id");
                $stmt->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
            }

            // 2. Processar imagem de destaque
            $this->processMainImage($db, $tenantId, $id, $produto);

            // 3. Processar galeria
            $this->processGallery($db, $tenantId, $id);

            // 4. Processar vídeos
            $this->processVideos($db, $tenantId, $id);

            // 5. Processar variações em lote (se tipo = variable)
            if ($tipo === 'variable' && !empty($_POST['variacoes_json'])) {
                $variacoes = json_decode($_POST['variacoes_json'], true);
                if (is_array($variacoes)) {
                    foreach ($variacoes as $variacaoData) {
                        $variacaoId = (int)($variacaoData['id'] ?? 0);
                        if ($variacaoId <= 0) continue;

                        $sku = trim($variacaoData['sku'] ?? '');
                        $precoRegularStr = trim($variacaoData['preco_regular'] ?? '');
                        $precoPromocionalStr = trim($variacaoData['preco_promocional'] ?? '');
                        
                        $precoRegular = null;
                        if (!empty($precoRegularStr)) {
                            $precoRegularStr = str_replace(',', '.', $precoRegularStr);
                            $precoRegular = (float)$precoRegularStr;
                        }
                        
                        $precoPromocional = null;
                        if (!empty($precoPromocionalStr)) {
                            $precoPromocionalStr = str_replace(',', '.', $precoPromocionalStr);
                            $precoPromocional = (float)$precoPromocionalStr;
                        }
                        
                        $gerenciaEstoque = isset($variacaoData['gerencia_estoque']) && $variacaoData['gerencia_estoque'] == 1 ? 1 : 0;
                        $quantidadeEstoque = (int)($variacaoData['quantidade_estoque'] ?? 0);
                        $status = $variacaoData['status'] ?? 'publish';
                        $permitePedidosFalta = $variacaoData['permite_pedidos_falta'] ?? 'no';

                        // Processar upload de imagem da variação
                        $imagemPath = null;
                        $imagemKey = 'variacoes_' . $variacaoId . '_imagem';
                        $imagemPathKey = 'variacoes_' . $variacaoId . '_imagem_path';
                        
                        // Verificar se veio arquivo novo
                        if (isset($_FILES['variacoes']['name'][$variacaoId]['imagem']) && 
                            $_FILES['variacoes']['error'][$variacaoId]['imagem'] === UPLOAD_ERR_OK) {
                            $imagemPath = $this->processVariationImageUpload($db, $tenantId, $variacaoId, $id);
                        } elseif (isset($variacaoData['imagem_path']) && !empty($variacaoData['imagem_path'])) {
                            $imagemPath = $variacaoData['imagem_path'];
                        }

                        // Calcular status_estoque
                        $statusEstoque = 'instock';
                        if ($gerenciaEstoque == 1) {
                            $statusEstoque = ($quantidadeEstoque > 0) ? 'instock' : 'outofstock';
                        }

                        $stmt = $db->prepare("
                            UPDATE produto_variacoes 
                            SET sku = :sku,
                                preco_regular = :preco_regular,
                                preco_promocional = :preco_promocional,
                                gerencia_estoque = :gerencia_estoque,
                                quantidade_estoque = :quantidade_estoque,
                                status_estoque = :status_estoque,
                                permite_pedidos_falta = :permite_pedidos_falta,
                                imagem = :imagem,
                                status = :status,
                                updated_at = NOW()
                            WHERE id = :variacao_id 
                            AND produto_id = :produto_id
                            AND tenant_id = :tenant_id
                        ");
                        $stmt->execute([
                            'variacao_id' => $variacaoId,
                            'produto_id' => $id,
                            'tenant_id' => $tenantId,
                            'sku' => !empty($sku) ? $sku : null,
                            'preco_regular' => $precoRegular,
                            'preco_promocional' => $precoPromocional,
                            'gerencia_estoque' => $gerenciaEstoque,
                            'quantidade_estoque' => $quantidadeEstoque,
                            'status_estoque' => $statusEstoque,
                            'permite_pedidos_falta' => $permitePedidosFalta,
                            'imagem' => $imagemPath,
                            'status' => $status
                        ]);
                    }
                }
            }

            // 6. Atualizar categorias (sync: remover antigas e adicionar novas)
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

    /**
     * Processa upload de swatch (imagem miniatura do termo)
     */
    private function processSwatchUpload($db, $tenantId, $fileKey, $termoId): ?string
    {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/atributos/swatches';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        $file = $_FILES[$fileKey];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $fileName = $this->sanitizeFileName($file['name']);
        $info = pathinfo($fileName);
        $fileName = 'swatch_' . $termoId . '_' . time() . '.' . $info['extension'];
        $destFile = $uploadsPath . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destFile)) {
            return "/uploads/tenants/{$tenantId}/atributos/swatches/{$fileName}";
        }

        return null;
    }

    /**
     * Processa upload de imagem do produto para um termo específico
     */
    private function processProductImageUpload($db, $tenantId, $fileKey, $produtoId): ?string
    {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        $file = $_FILES[$fileKey];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $fileName = $this->sanitizeFileName($file['name']);
        $info = pathinfo($fileName);
        $fileName = 'produto_' . $produtoId . '_termo_' . time() . '.' . $info['extension'];
        $destFile = $uploadsPath . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destFile)) {
            return "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
        }

        return null;
    }

    /**
     * Processa upload de imagem da variação
     */
    private function processVariationImageUpload($db, $tenantId, $variacaoId, $produtoId): ?string
    {
        if (!isset($_FILES['variacoes']['name'][$variacaoId]['imagem']) || 
            $_FILES['variacoes']['error'][$variacaoId]['imagem'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $paths = require __DIR__ . '/../../../../config/paths.php';
        $uploadsBasePath = $paths['uploads_produtos_base_path'];
        $uploadsPath = $uploadsBasePath . '/' . $tenantId . '/produtos';
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
        }

        $file = [
            'name' => $_FILES['variacoes']['name'][$variacaoId]['imagem'],
            'type' => $_FILES['variacoes']['type'][$variacaoId]['imagem'],
            'tmp_name' => $_FILES['variacoes']['tmp_name'][$variacaoId]['imagem'],
            'error' => $_FILES['variacoes']['error'][$variacaoId]['imagem'],
            'size' => $_FILES['variacoes']['size'][$variacaoId]['imagem']
        ];

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            return null;
        }

        $fileName = $this->sanitizeFileName($file['name']);
        $info = pathinfo($fileName);
        $fileName = 'variacao_' . $variacaoId . '_' . time() . '.' . $info['extension'];
        $destFile = $uploadsPath . '/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $destFile)) {
            return "/uploads/tenants/{$tenantId}/produtos/{$fileName}";
        }

        return null;
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

    /**
     * Gera variações para um produto variável (IDEMPOTENTE)
     */
    public function generateVariations(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar produto
        $stmt = $db->prepare("SELECT * FROM produtos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $produto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$produto || $produto['tipo'] !== 'variable') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Produto não é variável']);
            return;
        }

        try {
            $db->beginTransaction();

            // Buscar atributos marcados como usado_para_variacao
            $stmt = $db->prepare("
                SELECT pa.atributo_id, a.nome as atributo_nome
                FROM produto_atributos pa
                INNER JOIN atributos a ON a.id = pa.atributo_id
                WHERE pa.produto_id = :produto_id 
                AND pa.tenant_id = :tenant_id
                AND pa.usado_para_variacao = 1
                ORDER BY pa.ordem ASC, a.ordem ASC
            ");
            $stmt->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
            $atributosVariacao = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($atributosVariacao)) {
                $db->rollBack();
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Nenhum atributo marcado para variação. Selecione atributos e marque "Usar para gerar variações" na seção "Atributos do Produto".'
                ]);
                exit;
            }

            // Para cada atributo, buscar termos selecionados
            $atributosComTermos = [];
            foreach ($atributosVariacao as $attr) {
                $stmtTermos = $db->prepare("
                    SELECT at.id as termo_id, at.nome as termo_nome
                    FROM produto_atributo_termos pat
                    INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
                    WHERE pat.produto_id = :produto_id
                    AND pat.atributo_id = :atributo_id
                    AND pat.tenant_id = :tenant_id
                    ORDER BY at.ordem ASC, at.nome ASC
                ");
                $stmtTermos->execute([
                    'produto_id' => $id,
                    'atributo_id' => $attr['atributo_id'],
                    'tenant_id' => $tenantId
                ]);
                $termos = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);

                if (empty($termos)) {
                    continue; // Pular atributos sem termos
                }

                $atributosComTermos[] = [
                    'atributo_id' => (int)$attr['atributo_id'],
                    'atributo_nome' => $attr['atributo_nome'],
                    'termos' => $termos
                ];
            }

            if (empty($atributosComTermos)) {
                $db->rollBack();
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false, 
                    'message' => 'Nenhum termo selecionado para os atributos de variação. Selecione pelo menos um termo para cada atributo marcado para variação.'
                ]);
                exit;
            }

            // Gerar produto cartesiano das listas de termos
            $combinacoes = $this->cartesianProduct($atributosComTermos);

            // Buscar assinaturas existentes (prioriza coluna signature, fallback para GROUP_CONCAT)
            $stmt = $db->prepare("
                SELECT pv.id as variacao_id,
                       COALESCE(
                           pv.signature,
                           (SELECT GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|')
                            FROM produto_variacao_atributos pva
                            WHERE pva.variacao_id = pv.id)
                       ) as signature
                FROM produto_variacoes pv
                WHERE pv.produto_id = :produto_id AND pv.tenant_id = :tenant_id
            ");
            $stmt->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
            $variacoesExistentes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $assinaturasExistentes = [];
            foreach ($variacoesExistentes as $v) {
                if (!empty($v['signature'])) {
                    $assinaturasExistentes[$v['signature']] = (int)$v['variacao_id'];
                }
            }

            $criadas = 0;
            $ignoradas = 0;

            // Criar variações apenas para combinações novas
            foreach ($combinacoes as $combinacao) {
                // Montar assinatura ordenada
                $assinatura = $this->buildVariationSignature($combinacao);

                // Verificar se já existe
                if (isset($assinaturasExistentes[$assinatura])) {
                    $ignoradas++;
                    continue;
                }

                // Criar variação com signature
                $stmtVariacao = $db->prepare("
                    INSERT INTO produto_variacoes (
                        tenant_id, produto_id, gerencia_estoque, quantidade_estoque, 
                        status_estoque, permite_pedidos_falta, status, signature, created_at, updated_at
                    ) VALUES (
                        :tenant_id, :produto_id, :gerencia_estoque, 0,
                        'instock', :permite_pedidos_falta, 'publish', :signature, NOW(), NOW()
                    )
                ");
                $stmtVariacao->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $id,
                    'gerencia_estoque' => $produto['gerencia_estoque'] ?? 1,
                    'permite_pedidos_falta' => $produto['permite_pedidos_falta'] ?? 'no',
                    'signature' => $assinatura
                ]);

                $variacaoId = $db->lastInsertId();

                // Criar registros de atributos da variação
                foreach ($combinacao as $item) {
                    $stmtAttr = $db->prepare("
                        INSERT INTO produto_variacao_atributos (
                            tenant_id, variacao_id, atributo_id, atributo_termo_id, created_at, updated_at
                        ) VALUES (
                            :tenant_id, :variacao_id, :atributo_id, :atributo_termo_id, NOW(), NOW()
                        )
                    ");
                    $stmtAttr->execute([
                        'tenant_id' => $tenantId,
                        'variacao_id' => $variacaoId,
                        'atributo_id' => $item['atributo_id'],
                        'atributo_termo_id' => $item['termo_id'] // termo_id do array = atributo_termo_id na tabela
                    ]);
                }

                $criadas++;
            }

            $db->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Variações geradas: {$criadas} criadas, {$ignoradas} já existiam",
                'criadas' => $criadas,
                'ignoradas' => $ignoradas
            ]);
            exit;

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
            exit;
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Erro no banco de dados: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Salva variações em lote
     */
    public function saveVariationsBulk(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $variacoes = json_decode($_POST['variacoes'] ?? '[]', true);

        if (empty($variacoes)) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma variação enviada']);
            return;
        }

        try {
            $db->beginTransaction();

            foreach ($variacoes as $variacaoData) {
                $variacaoId = (int)($variacaoData['id'] ?? 0);
                if ($variacaoId <= 0) continue;

                $sku = trim($variacaoData['sku'] ?? '');
                $precoRegular = !empty($variacaoData['preco_regular']) ? (float)$variacaoData['preco_regular'] : null;
                $precoPromocional = !empty($variacaoData['preco_promocional']) ? (float)$variacaoData['preco_promocional'] : null;
                $gerenciaEstoque = isset($variacaoData['gerencia_estoque']) ? 1 : 0;
                $quantidadeEstoque = (int)($variacaoData['quantidade_estoque'] ?? 0);
                $permitePedidosFalta = $variacaoData['permite_pedidos_falta'] ?? 'no';
                $status = $variacaoData['status'] ?? 'publish';

                // Calcular status_estoque
                $statusEstoque = 'instock';
                if ($gerenciaEstoque == 1) {
                    $statusEstoque = ($quantidadeEstoque > 0) ? 'instock' : 'outofstock';
                }

                $stmt = $db->prepare("
                    UPDATE produto_variacoes 
                    SET sku = :sku,
                        preco_regular = :preco_regular,
                        preco_promocional = :preco_promocional,
                        gerencia_estoque = :gerencia_estoque,
                        quantidade_estoque = :quantidade_estoque,
                        status_estoque = :status_estoque,
                        permite_pedidos_falta = :permite_pedidos_falta,
                        status = :status,
                        updated_at = NOW()
                    WHERE id = :variacao_id 
                    AND produto_id = :produto_id
                    AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'variacao_id' => $variacaoId,
                    'produto_id' => $id,
                    'tenant_id' => $tenantId,
                    'sku' => !empty($sku) ? $sku : null,
                    'preco_regular' => $precoRegular,
                    'preco_promocional' => $precoPromocional,
                    'gerencia_estoque' => $gerenciaEstoque,
                    'quantidade_estoque' => $quantidadeEstoque,
                    'status_estoque' => $statusEstoque,
                    'permite_pedidos_falta' => $permitePedidosFalta,
                    'status' => $status
                ]);
            }

            $db->commit();

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Variações atualizadas com sucesso']);

        } catch (\Exception $e) {
            $db->rollBack();
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Gera produto cartesiano de arrays
     */
    private function cartesianProduct(array $arrays): array
    {
        if (empty($arrays)) {
            return [];
        }

        $result = [[]];
        foreach ($arrays as $array) {
            $temp = [];
            foreach ($result as $product) {
                foreach ($array['termos'] as $termo) {
                    $temp[] = array_merge($product, [[
                        'atributo_id' => $array['atributo_id'],
                        'atributo_nome' => $array['atributo_nome'],
                        'termo_id' => (int)$termo['termo_id'],
                        'termo_nome' => $termo['termo_nome']
                    ]]);
                }
            }
            $result = $temp;
        }

        return $result;
    }

    /**
     * Salva apenas os atributos do produto (endpoint AJAX)
     */
    public function saveAttributes(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar produto
        $stmt = $db->prepare("SELECT * FROM produtos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $produto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$produto || $produto['tipo'] !== 'variable') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Produto não é variável']);
            return;
        }

        try {
            $db->beginTransaction();

            // Processar atributos (mesma lógica do update)
            $this->processProductAttributes($db, $tenantId, $id);

            $db->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Atributos salvos com sucesso!'
            ]);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Salva atributos e gera variações em uma única operação
     */
    public function saveAttributesAndGenerateVariations(int $id): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }

        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar produto
        $stmt = $db->prepare("SELECT * FROM produtos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $produto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$produto || $produto['tipo'] !== 'variable') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Produto não é variável']);
            return;
        }

        try {
            $db->beginTransaction();

            // 1. Salvar atributos
            $this->processProductAttributes($db, $tenantId, $id);

            // 2. Gerar variações (lógica extraída do generateVariations)
            $this->generateVariationsInternal($db, $tenantId, $id);

            $db->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Atributos salvos e variações geradas com sucesso!'
            ]);

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Processa atributos do produto (extraído do método update para reutilização)
     */
    private function processProductAttributes($db, $tenantId, $produtoId): void
    {
        // Remover atributos antigos
        $stmt = $db->prepare("DELETE FROM produto_atributos WHERE produto_id = :produto_id AND tenant_id = :tenant_id");
        $stmt->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);

        // Remover termos antigos
        $stmt = $db->prepare("DELETE FROM produto_atributo_termos WHERE produto_id = :produto_id AND tenant_id = :tenant_id");
        $stmt->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);

        // Adicionar novos atributos
        if (!empty($_POST['atributos']) && is_array($_POST['atributos'])) {
            $ordem = 0;
            foreach ($_POST['atributos'] as $atributoId) {
                $atributoId = (int)$atributoId;
                if ($atributoId <= 0) continue;

                $usadoParaVariacao = isset($_POST['atributos_para_variacao'][$atributoId]) ? 1 : 0;

                $stmt = $db->prepare("
                    INSERT INTO produto_atributos (tenant_id, produto_id, atributo_id, usado_para_variacao, ordem, created_at, updated_at)
                    VALUES (:tenant_id, :produto_id, :atributo_id, :usado_para_variacao, :ordem, NOW(), NOW())
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'produto_id' => $produtoId,
                    'atributo_id' => $atributoId,
                    'usado_para_variacao' => $usadoParaVariacao,
                    'ordem' => $ordem++
                ]);

                // Adicionar termos selecionados para este atributo
                $termosKey = 'atributo_' . $atributoId . '_termos';
                if (!empty($_POST[$termosKey]) && is_array($_POST[$termosKey])) {
                    foreach ($_POST[$termosKey] as $termoId) {
                        $termoId = (int)$termoId;
                        if ($termoId <= 0) continue;

                        // Processar upload de swatch (se houver)
                        $swatchPath = null;
                        $swatchKey = 'atributo_' . $atributoId . '_termo_' . $termoId . '_swatch';
                        $swatchPathKey = 'atributo_' . $atributoId . '_termo_' . $termoId . '_swatch_path';
                        
                        if (isset($_FILES[$swatchKey]) && $_FILES[$swatchKey]['error'] === UPLOAD_ERR_OK) {
                            $swatchPath = $this->processSwatchUpload($db, $tenantId, $swatchKey, $termoId);
                        } elseif (isset($_POST[$swatchPathKey]) && !empty($_POST[$swatchPathKey])) {
                            $swatchPath = $_POST[$swatchPathKey];
                        }

                        // Se swatch foi enviado, atualizar atributo_termos
                        if ($swatchPath) {
                            $stmtUpdateTermo = $db->prepare("
                                UPDATE atributo_termos 
                                SET imagem = :imagem, updated_at = NOW()
                                WHERE id = :termo_id AND tenant_id = :tenant_id
                            ");
                            $stmtUpdateTermo->execute([
                                'imagem' => $swatchPath,
                                'termo_id' => $termoId,
                                'tenant_id' => $tenantId
                            ]);
                        }

                        // Processar upload de imagem do produto para este termo (se houver)
                        $produtoImagePath = null;
                        $produtoImageKey = 'atributo_' . $atributoId . '_termo_' . $termoId . '_produto_image';
                        $produtoImagePathKey = 'atributo_' . $atributoId . '_termo_' . $termoId . '_produto_image_path';
                        
                        if (isset($_FILES[$produtoImageKey]) && $_FILES[$produtoImageKey]['error'] === UPLOAD_ERR_OK) {
                            $produtoImagePath = $this->processProductImageUpload($db, $tenantId, $produtoImageKey, $produtoId);
                        } elseif (isset($_POST[$produtoImagePathKey]) && !empty($_POST[$produtoImagePathKey])) {
                            $produtoImagePath = $_POST[$produtoImagePathKey];
                        }

                        // Processar hex color (se atributo for tipo color)
                        $hexKey = 'atributo_' . $atributoId . '_termo_' . $termoId . '_hex_text';
                        if (isset($_POST[$hexKey]) && !empty($_POST[$hexKey])) {
                            $hexColor = trim($_POST[$hexKey]);
                            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $hexColor)) {
                                $stmtUpdateColor = $db->prepare("
                                    UPDATE atributo_termos 
                                    SET valor_cor = :valor_cor, updated_at = NOW()
                                    WHERE id = :termo_id AND tenant_id = :tenant_id
                                ");
                                $stmtUpdateColor->execute([
                                    'valor_cor' => strtoupper($hexColor),
                                    'termo_id' => $termoId,
                                    'tenant_id' => $tenantId
                                ]);
                            }
                        }

                        // Verificar se a coluna imagem_produto existe
                        $this->ensureImagemProdutoColumn($db);
                        
                        $stmtTermo = $db->prepare("
                            INSERT INTO produto_atributo_termos (tenant_id, produto_id, atributo_id, atributo_termo_id, imagem_produto, created_at, updated_at)
                            VALUES (:tenant_id, :produto_id, :atributo_id, :termo_id, :imagem_produto, NOW(), NOW())
                            ON DUPLICATE KEY UPDATE imagem_produto = :imagem_produto_update, updated_at = NOW()
                        ");
                        $stmtTermo->execute([
                            'tenant_id' => $tenantId,
                            'produto_id' => $produtoId,
                            'atributo_id' => $atributoId,
                            'termo_id' => $termoId,
                            'imagem_produto' => $produtoImagePath,
                            'imagem_produto_update' => $produtoImagePath
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Gera variações internamente (extraído do generateVariations para reutilização)
     */
    private function generateVariationsInternal($db, $tenantId, $produtoId): void
    {
        // Buscar atributos marcados como usado_para_variacao
        $stmt = $db->prepare("
            SELECT pa.atributo_id, a.nome as atributo_nome
            FROM produto_atributos pa
            INNER JOIN atributos a ON a.id = pa.atributo_id
            WHERE pa.produto_id = :produto_id 
            AND pa.tenant_id = :tenant_id
            AND pa.usado_para_variacao = 1
            ORDER BY pa.ordem ASC, a.ordem ASC
        ");
        $stmt->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);
        $atributosVariacao = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($atributosVariacao)) {
            throw new \Exception('Nenhum atributo marcado para variação. Selecione atributos e marque "Usar para gerar variações".');
        }

        // Para cada atributo, buscar termos selecionados
        $atributosComTermos = [];
        foreach ($atributosVariacao as $attr) {
            $stmtTermos = $db->prepare("
                SELECT at.id as termo_id, at.nome as termo_nome
                FROM produto_atributo_termos pat
                INNER JOIN atributo_termos at ON at.id = pat.atributo_termo_id
                WHERE pat.produto_id = :produto_id
                AND pat.atributo_id = :atributo_id
                AND pat.tenant_id = :tenant_id
                ORDER BY at.ordem ASC, at.nome ASC
            ");
            $stmtTermos->execute([
                'produto_id' => $produtoId,
                'atributo_id' => $attr['atributo_id'],
                'tenant_id' => $tenantId
            ]);
            $termos = $stmtTermos->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($termos)) {
                continue;
            }

            $atributosComTermos[] = [
                'atributo_id' => (int)$attr['atributo_id'],
                'atributo_nome' => $attr['atributo_nome'],
                'termos' => $termos
            ];
        }

        if (empty($atributosComTermos)) {
            throw new \Exception('Nenhum termo selecionado para os atributos de variação.');
        }

        // Gerar produto cartesiano
        $combinacoes = $this->cartesianProduct($atributosComTermos);

        // Buscar assinaturas existentes
        $stmt = $db->prepare("
            SELECT pv.id as variacao_id,
                   COALESCE(
                       pv.signature,
                       (SELECT GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|')
                        FROM produto_variacao_atributos pva
                        WHERE pva.variacao_id = pv.id)
                   ) as signature
            FROM produto_variacoes pv
            WHERE pv.produto_id = :produto_id AND pv.tenant_id = :tenant_id
        ");
        $stmt->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);
        $variacoesExistentes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $assinaturasExistentes = [];
        foreach ($variacoesExistentes as $v) {
            if (!empty($v['signature'])) {
                $assinaturasExistentes[$v['signature']] = (int)$v['variacao_id'];
            }
        }

        $criadas = 0;
        $ignoradas = 0;

        // Criar variações apenas para combinações novas
        foreach ($combinacoes as $combinacao) {
            $assinatura = $this->buildVariationSignature($combinacao);

            if (isset($assinaturasExistentes[$assinatura])) {
                $ignoradas++;
                continue;
            }

            // Buscar produto para pegar configurações padrão
            $stmtProduto = $db->prepare("SELECT * FROM produtos WHERE id = :id AND tenant_id = :tenant_id LIMIT 1");
            $stmtProduto->execute(['id' => $produtoId, 'tenant_id' => $tenantId]);
            $produto = $stmtProduto->fetch(\PDO::FETCH_ASSOC);

            // Criar variação
            $stmtVariacao = $db->prepare("
                INSERT INTO produto_variacoes (
                    tenant_id, produto_id, gerencia_estoque, quantidade_estoque, 
                    status_estoque, permite_pedidos_falta, status, signature, created_at, updated_at
                ) VALUES (
                    :tenant_id, :produto_id, :gerencia_estoque, 0,
                    'instock', :permite_pedidos_falta, 'publish', :signature, NOW(), NOW()
                )
            ");
            $stmtVariacao->execute([
                'tenant_id' => $tenantId,
                'produto_id' => $produtoId,
                'gerencia_estoque' => $produto['gerencia_estoque'] ?? 1,
                'permite_pedidos_falta' => $produto['permite_pedidos_falta'] ?? 'no',
                'signature' => $assinatura
            ]);

            $variacaoId = $db->lastInsertId();

            // Criar registros de atributos da variação
            foreach ($combinacao as $item) {
                $stmtAttr = $db->prepare("
                    INSERT INTO produto_variacao_atributos (
                        tenant_id, variacao_id, atributo_id, atributo_termo_id, created_at, updated_at
                    ) VALUES (
                        :tenant_id, :variacao_id, :atributo_id, :atributo_termo_id, NOW(), NOW()
                    )
                ");
                $stmtAttr->execute([
                    'tenant_id' => $tenantId,
                    'variacao_id' => $variacaoId,
                    'atributo_id' => $item['atributo_id'],
                    'atributo_termo_id' => $item['termo_id']
                ]);
            }

            $criadas++;
        }

        if ($criadas == 0 && $ignoradas > 0) {
            throw new \Exception("Todas as variações já existem. {$ignoradas} variações já estavam cadastradas.");
        }
    }

    /**
     * Garante que a coluna imagem_produto existe na tabela produto_atributo_termos
     */
    private function ensureImagemProdutoColumn($db): void
    {
        try {
            // Verificar se a coluna já existe
            $stmt = $db->query("SHOW COLUMNS FROM produto_atributo_termos LIKE 'imagem_produto'");
            if ($stmt->rowCount() == 0) {
                // Adicionar coluna imagem_produto
                $db->exec("
                    ALTER TABLE produto_atributo_termos
                    ADD COLUMN imagem_produto VARCHAR(255) NULL COMMENT 'Imagem do produto associada a este termo (para troca na loja)'
                    AFTER atributo_termo_id
                ");
            }
        } catch (\Exception $e) {
            // Se der erro, apenas logar (não quebrar o fluxo)
            error_log("Erro ao verificar/criar coluna imagem_produto: " . $e->getMessage());
        }
    }

    /**
     * Monta assinatura ordenada de uma combinação de atributos
     */
    private function buildVariationSignature(array $combinacao): string
    {
        // Ordenar por atributo_id
        usort($combinacao, function($a, $b) {
            return $a['atributo_id'] <=> $b['atributo_id'];
        });

        // Montar string "atributo_id:atributo_termo_id|atributo_id:atributo_termo_id|..."
        // Nota: $item['termo_id'] é o ID de atributo_termos, que corresponde a atributo_termo_id na tabela
        $parts = [];
        foreach ($combinacao as $item) {
            $parts[] = $item['atributo_id'] . ':' . $item['termo_id'];
        }

        return implode('|', $parts);
    }
}

