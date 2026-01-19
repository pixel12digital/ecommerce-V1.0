<?php

// Tratamento de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Router;
use App\Http\Middleware\TenantResolverMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CustomerAuthMiddleware;
use App\Http\Middleware\CheckPermissionMiddleware;
use App\Http\Controllers\PlatformAuthController;
use App\Http\Controllers\StoreAuthController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\StoreDashboardController;
use App\Http\Controllers\SystemUpdatesController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\ProductReviewController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\CustomerAuthController;
use App\Http\Controllers\Storefront\CustomerController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\HomeConfigController;
use App\Http\Controllers\Admin\HomeCategoriesController;
use App\Http\Controllers\Admin\HomeSectionsController;
use App\Http\Controllers\Admin\HomeBannersController;
use App\Http\Controllers\Admin\CategoriaController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\MediaLibraryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\ProductReviewController as AdminProductReviewController;
use App\Http\Controllers\Admin\GatewayConfigController;
use App\Http\Controllers\Admin\StoreUsersController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Storefront\NewsletterController as StorefrontNewsletterController;
use App\Http\Controllers\Storefront\StaticPageController;

// Obter URI e método
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Remover query string da URI
$uri = parse_url($uri, PHP_URL_PATH);

/**
 * Detectar e remover caminho base automaticamente
 * 
 * Suporta múltiplos cenários:
 * - Desenvolvimento local: /ecommerce-v1.0/public/...
 * - Produção com DocumentRoot em public/: /... (sem prefixo)
 * - Produção com DocumentRoot na raiz + index.php fallback: /... (sem prefixo)
 * - Produção com DocumentRoot na raiz + .htaccess: /public/... (remove /public)
 * 
 * Usa SCRIPT_NAME para detectar o caminho base de forma mais robusta,
 * evitando dependência de strings hardcoded ou configurações específicas.
 */
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$scriptDir = rtrim($scriptDir, '/');

// Se SCRIPT_NAME indica que estamos em um subdiretório, remover da URI
if ($scriptDir !== '' && $scriptDir !== '/') {
    // Se a URI começa com o diretório do script, removê-lo
    if (strpos($uri, $scriptDir) === 0) {
        $uri = substr($uri, strlen($scriptDir));
    }
}

// Fallback: remover prefixos conhecidos se ainda estiverem presentes
// (para compatibilidade com instalações antigas ou configurações específicas)
if (strpos($uri, '/ecommerce-v1.0/public') === 0) {
    $uri = substr($uri, strlen('/ecommerce-v1.0/public'));
} elseif (strpos($uri, '/public') === 0 && $uri !== '/public' && $uri !== '/public/') {
    $uri = substr($uri, strlen('/public'));
}

$uri = rtrim($uri, '/') ?: '/';

// DEBUG: Log da URI processada
error_log('[DEBUG INDEX] URI após processamento: ' . $uri);
error_log('[DEBUG INDEX] URI original (REQUEST_URI): ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

// Rotas que não precisam de tenant resolvido (login e migrations)
$publicRoutes = ['/admin/platform/login', '/admin/login', '/migrations'];

// Resolver tenant apenas se não for rota pública
if (!in_array($uri, $publicRoutes) && strpos($uri, '/admin/platform/login') !== 0 && strpos($uri, '/admin/login') !== 0) {
    try {
        $tenantResolver = new TenantResolverMiddleware();
        if (!$tenantResolver->handle()) {
            exit;
        }
    } catch (\Exception $e) {
        // Se falhar ao resolver tenant, permite continuar para rotas públicas
        if (!in_array($uri, $publicRoutes)) {
            http_response_code(503);
            echo "<h1>Erro ao Resolver Tenant</h1><p>{$e->getMessage()}</p>";
            exit;
        }
    }
}

$router = new Router();

// DEBUG: Log de requisição
error_log('[DEBUG INDEX] REQUEST_URI = ' . ($_SERVER['REQUEST_URI'] ?? ''));
error_log('[DEBUG INDEX] SCRIPT_NAME = ' . ($_SERVER['SCRIPT_NAME'] ?? ''));
error_log('[DEBUG INDEX] PHP_SELF = ' . ($_SERVER['PHP_SELF'] ?? ''));

// Rotas públicas de autenticação
$router->get('/admin/platform/login', PlatformAuthController::class . '@showLogin');
$router->post('/admin/platform/login', PlatformAuthController::class . '@login');
$router->get('/admin/platform/logout', PlatformAuthController::class . '@logout', [AuthMiddleware::class]);

$router->get('/admin/login', StoreAuthController::class . '@showLogin');
$router->post('/admin/login', StoreAuthController::class . '@login');
$router->get('/admin/logout', StoreAuthController::class . '@logout', [AuthMiddleware::class]);

// Rotas protegidas - Platform Admin
$router->get('/admin/platform', PlatformDashboardController::class . '@index', [
    AuthMiddleware::class => [true, false]
]);
$router->get('/admin/platform/tenants/{id}/edit', PlatformDashboardController::class . '@editTenant', [
    AuthMiddleware::class => [true, false]
]);
$router->post('/admin/platform/tenants/{id}/edit', PlatformDashboardController::class . '@editTenant', [
    AuthMiddleware::class => [true, false]
]);

// Rotas protegidas - Store Admin
$router->get('/admin', StoreDashboardController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'view_dashboard'
]);
$router->get('/admin/system/updates', SystemUpdatesController::class . '@index', [
    AuthMiddleware::class => [false, true]
]);
$router->post('/admin/system/updates/run', SystemUpdatesController::class . '@runMigrations', [
    AuthMiddleware::class => [false, true]
]);

// Rotas Admin - Catálogo
$router->get('/admin/produtos', AdminProductController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/produtos/novo', AdminProductController::class . '@create', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/produtos', AdminProductController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/produtos/{id}', AdminProductController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/produtos/{id}', AdminProductController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/produtos/{id}/toggle-status', AdminProductController::class . '@toggleStatus', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/produtos/{id}/atualizar-categorias', AdminProductController::class . '@updateCategoriesQuick', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/produtos/{id}/excluir', AdminProductController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);

// Rotas Admin - Categorias
$router->get('/admin/categorias', CategoriaController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/categorias/criar', CategoriaController::class . '@create', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/criar', CategoriaController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->get('/admin/categorias/{id}/editar', CategoriaController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/{id}/editar', CategoriaController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
$router->post('/admin/categorias/{id}/excluir', CategoriaController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
error_log('[DEBUG INDEX] Todas as rotas de categorias registradas');

// Rotas Admin - Tema
$router->get('/admin/tema', ThemeController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_theme'
]);
$router->post('/admin/tema', ThemeController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_theme'
]);

// Rotas Admin - Home (Configuração Agregadora)
$router->get('/admin/home', HomeConfigController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);

// Rotas Admin - Home (Categorias em Destaque)
$router->get('/admin/home/categorias-pills', HomeCategoriesController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/categorias-pills', HomeCategoriesController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->get('/admin/home/categorias-pills/{id}/editar', HomeCategoriesController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/categorias-pills/{id}', HomeCategoriesController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/categorias-pills/{id}/excluir', HomeCategoriesController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->get('/admin/home/categorias-pills/midia', HomeCategoriesController::class . '@listarImagensExistentes', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);

// Rotas Admin - Biblioteca de Mídia
$router->get('/admin/midias', MediaLibraryController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_media'
]);
$router->get('/admin/midias/listar', MediaLibraryController::class . '@listar', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_media'
]);
$router->post('/admin/midias/upload', MediaLibraryController::class . '@upload', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_media'
]);

// Rotas Admin - Home (Seções de Categorias)
$router->get('/admin/home/secoes-categorias', HomeSectionsController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/secoes-categorias', HomeSectionsController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);

// Rotas Admin - Home (Banners)
// IMPORTANTE: Rotas específicas devem vir ANTES de rotas com parâmetros dinâmicos
$router->get('/admin/home/banners', HomeBannersController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->get('/admin/home/banners/novo', HomeBannersController::class . '@create', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/banners/novo', HomeBannersController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
// Rota específica ANTES da rota com {id}
$router->post('/admin/home/banners/reordenar', HomeBannersController::class . '@reordenar', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->get('/admin/home/banners/{id}/editar', HomeBannersController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/banners/{id}', HomeBannersController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);
$router->post('/admin/home/banners/{id}/excluir', HomeBannersController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_home_page'
]);

// Rotas Admin - Newsletter
$router->get('/admin/newsletter', AdminNewsletterController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_newsletter'
]);

// Rotas Admin - Pedidos
$router->get('/admin/pedidos', AdminOrderController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->get('/admin/pedidos/{id}', AdminOrderController::class . '@show', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->post('/admin/pedidos/{id}/status', AdminOrderController::class . '@updateStatus', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->post('/admin/pedidos/{id}/frete/gerar-etiqueta', AdminOrderController::class . '@gerarEtiqueta', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->get('/admin/pedidos/{id}/frete/imprimir-etiqueta', AdminOrderController::class . '@imprimirEtiqueta', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->get('/admin/pedidos/{id}/frete/etiqueta/pdf', AdminOrderController::class . '@etiquetaPdf', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->get('/admin/pedidos/{id}/envio/declaracao-conteudo', AdminOrderController::class . '@declaracaoConteudo', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);
$router->post('/admin/pedidos/{id}/documento-envio', AdminOrderController::class . '@updateDocumentoEnvio', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_orders'
]);

// Rotas Admin - Clientes
$router->get('/admin/clientes', AdminCustomerController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_customers'
]);
$router->get('/admin/clientes/{id}', AdminCustomerController::class . '@show', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_customers'
]);

// Rotas Admin - Avaliações de Produtos
$router->get('/admin/avaliacoes', AdminProductReviewController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_reviews'
]);
$router->get('/admin/avaliacoes/{id}', AdminProductReviewController::class . '@show', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_reviews'
]);
$router->post('/admin/avaliacoes/{id}/aprovar', AdminProductReviewController::class . '@approve', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_reviews'
]);
$router->post('/admin/avaliacoes/{id}/rejeitar', AdminProductReviewController::class . '@reject', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_reviews'
]);

// Rotas Admin - Configurações / Gateways
$router->get('/admin/configuracoes/gateways', GatewayConfigController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_gateways'
]);
$router->post('/admin/configuracoes/gateways', GatewayConfigController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_gateways'
]);

// Rotas Admin - Usuários e Perfis
$router->get('/admin/usuarios', StoreUsersController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->get('/admin/usuarios/novo', StoreUsersController::class . '@create', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->post('/admin/usuarios', StoreUsersController::class . '@store', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->get('/admin/usuarios/{id}/editar', StoreUsersController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->post('/admin/usuarios/{id}', StoreUsersController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->post('/admin/usuarios/{id}/excluir', StoreUsersController::class . '@destroy', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);

// Rotas Admin - Perfis de Acesso
$router->get('/admin/usuarios/perfis', RolesController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->get('/admin/usuarios/perfis/{id}/editar', RolesController::class . '@edit', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);
$router->post('/admin/usuarios/perfis/{id}', RolesController::class . '@update', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_store_users'
]);

// Rotas Públicas - Newsletter
$router->post('/newsletter/inscrever', StorefrontNewsletterController::class . '@store');

// Rotas públicas - Loja
$router->get('/', HomeController::class . '@index');
$router->get('/produtos', ProductController::class . '@index');
$router->get('/categoria/{slug}', ProductController::class . '@category');
$router->get('/produto/{slug}', ProductController::class . '@show');
$router->post('/produto/{slug}/avaliar', ProductReviewController::class . '@store');

// Rotas públicas - Páginas Institucionais
$router->get('/sobre', StaticPageController::class . '@sobre');
$router->get('/contato', StaticPageController::class . '@contato');
$router->post('/contato', StaticPageController::class . '@enviarContato');
$router->get('/trocas-e-devolucoes', StaticPageController::class . '@trocasDevolucoes');
$router->get('/frete-prazos', StaticPageController::class . '@fretePrazos');
$router->get('/formas-de-pagamento', StaticPageController::class . '@formasPagamento');
$router->get('/faq', StaticPageController::class . '@faq');
$router->get('/politica-de-privacidade', StaticPageController::class . '@politicaPrivacidade');
$router->get('/termos-de-uso', StaticPageController::class . '@termosUso');
$router->get('/politica-de-cookies', StaticPageController::class . '@politicaCookies');
$router->get('/seja-parceiro', StaticPageController::class . '@sejaParceiro');

// Rotas públicas - Carrinho
$router->get('/carrinho', CartController::class . '@index');
$router->post('/carrinho/adicionar', CartController::class . '@add');
$router->post('/carrinho/atualizar', CartController::class . '@update');
$router->post('/carrinho/remover', CartController::class . '@remove');
$router->post('/carrinho/esvaziar', CartController::class . '@clear');

// Rotas públicas - Checkout
$router->get('/checkout', CheckoutController::class . '@index');
$router->post('/checkout', CheckoutController::class . '@process');

// Rotas públicas - Pedidos
$router->get('/pedido/{numero_pedido}/confirmacao', OrderController::class . '@thankYou');

// Rotas públicas - Autenticação de Cliente
$router->get('/minha-conta/login', CustomerAuthController::class . '@showLoginForm');
$router->post('/minha-conta/login', CustomerAuthController::class . '@login');
$router->get('/minha-conta/registrar', CustomerAuthController::class . '@showRegisterForm');
$router->post('/minha-conta/registrar', CustomerAuthController::class . '@register');
$router->get('/minha-conta/logout', CustomerAuthController::class . '@logout');

// Rota pública - Verificação de Migrations (para desenvolvimento)
$router->get('/migrations', function() {
    require __DIR__ . '/migrations.php';
});

// Rota para verificação de imagens (protegida por sessão do admin)
$router->get('/scripts/check-product-images', function() {
    // Iniciar sessão se necessário
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar se está autenticado no admin
    $isAuthenticated = isset($_SESSION['user_id']) && isset($_SESSION['tenant_id']);
    
    // Se não estiver autenticado, redirecionar para login
    if (!$isAuthenticated) {
        // Salvar URL de retorno
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: /admin/login');
        exit;
    }
    
    // Executar script diretamente (sem middleware adicional)
    require __DIR__ . '/../scripts/check_product_images_web.php';
});

// Rotas protegidas - Área do Cliente
$router->get('/minha-conta', CustomerController::class . '@dashboard', [CustomerAuthMiddleware::class]);
$router->get('/minha-conta/pedidos', CustomerController::class . '@orders', [CustomerAuthMiddleware::class]);
$router->get('/minha-conta/pedidos/{codigo}', CustomerController::class . '@orderShow', [CustomerAuthMiddleware::class]);
$router->get('/minha-conta/enderecos', CustomerController::class . '@addresses', [CustomerAuthMiddleware::class]);
$router->post('/minha-conta/enderecos', CustomerController::class . '@saveAddress', [CustomerAuthMiddleware::class]);
$router->get('/minha-conta/enderecos/excluir/{id}', CustomerController::class . '@deleteAddress', [CustomerAuthMiddleware::class]);
$router->get('/minha-conta/perfil', CustomerController::class . '@profile', [CustomerAuthMiddleware::class]);
$router->post('/minha-conta/perfil', CustomerController::class . '@updateProfile', [CustomerAuthMiddleware::class]);

// Executar router
error_log('[DEBUG INDEX] Antes de dispatch - Method: ' . $method . ', URI: ' . $uri);
error_log('[DEBUG INDEX] Total de rotas antes do dispatch: ' . (method_exists($router, 'getRoutes') ? count($router->getRoutes()) : 'N/A'));
try {
    $router->dispatch($method, $uri);
    error_log('[DEBUG INDEX] Dispatch concluído com sucesso');
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<h1>Erro Interno</h1>";
    
    // Detectar ambiente local (localhost ou 127.0.0.1)
    $isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) 
               || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
               || ($_ENV['APP_DEBUG'] ?? false) === 'true' 
               || ($_ENV['APP_DEBUG'] ?? false) === true;
    
    if ($isLocal) {
        echo "<pre style='background: #f5f5f5; padding: 1rem; border: 1px solid #ddd; border-radius: 4px; overflow: auto;'>";
        echo "<strong>Mensagem:</strong>\n" . htmlspecialchars($e->getMessage()) . "\n\n";
        echo "<strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "\n";
        echo "<strong>Linha:</strong> " . $e->getLine() . "\n\n";
        echo "<strong>Stack Trace:</strong>\n" . htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
    } else {
        echo "<p>Ocorreu um erro. Entre em contato com o administrador.</p>";
    }
}

