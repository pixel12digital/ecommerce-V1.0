<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<?php
// Preparar dados do tenant com fallbacks seguros
$tenantName = $tenant->name ?? 'Loja';
$tenantSlug = $tenant->slug ?? '-';
$tenantStatus = $tenant->status ?? 'active';
$tenantPlan = $tenant->plan ?? null;

// Tradução de status para PT-BR
switch ($tenantStatus) {
    case 'active':
        $tenantStatusLabel = 'Ativa';
        break;
    case 'inactive':
        $tenantStatusLabel = 'Inativa';
        break;
    case 'pending':
        $tenantStatusLabel = 'Pendente';
        break;
    case 'suspended':
        $tenantStatusLabel = 'Suspensa';
        break;
    default:
        $tenantStatusLabel = ucfirst($tenantStatus);
        break;
}

// Tradução do plano para PT-BR
switch ($tenantPlan) {
    case 'basic':
        $tenantPlanLabel = 'Básico';
        break;
    case 'pro':
        $tenantPlanLabel = 'Profissional';
        break;
    case 'premium':
        $tenantPlanLabel = 'Premium';
        break;
    case 'enterprise':
        $tenantPlanLabel = 'Enterprise';
        break;
    default:
        $tenantPlanLabel = $tenantPlan ? ucfirst($tenantPlan) : '—';
        break;
}
?>

<div class="dashboard-welcome">
    <div class="welcome-card">
        <h2>Bem-vindo</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Loja:</span>
                <span class="info-value"><?= htmlspecialchars($tenantName) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Slug:</span>
                <span class="info-value"><?= htmlspecialchars($tenantSlug) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= htmlspecialchars($tenantStatusLabel) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Plano:</span>
                <span class="info-value"><?= htmlspecialchars($tenantPlanLabel) ?></span>
            </div>
        </div>
    </div>
    
    <?php
    // Verificar permissões do usuário logado
    use App\Services\StoreUserService;
    
    $currentUserId = StoreUserService::getCurrentUserId();
    $canManageProducts = $currentUserId && StoreUserService::can($currentUserId, 'manage_products');
    $canManageOrders = $currentUserId && StoreUserService::can($currentUserId, 'manage_orders');
    $canManageHomePage = $currentUserId && StoreUserService::can($currentUserId, 'manage_home_page');
    $canManageTheme = $currentUserId && StoreUserService::can($currentUserId, 'manage_theme');
    $canManageGateways = $currentUserId && StoreUserService::can($currentUserId, 'manage_gateways');
    $canManageStoreUsers = $currentUserId && StoreUserService::can($currentUserId, 'manage_store_users');
    $canManageCustomers = $currentUserId && StoreUserService::can($currentUserId, 'manage_customers');
    $canManageReviews = $currentUserId && StoreUserService::can($currentUserId, 'manage_reviews');
    $canManageNewsletter = $currentUserId && StoreUserService::can($currentUserId, 'manage_newsletter');
    $canManageMedia = $currentUserId && StoreUserService::can($currentUserId, 'manage_media');
    ?>
    <div class="quick-actions">
        <h3>Ações Rápidas</h3>
        <div class="action-buttons">
            <?php if ($canManageProducts): ?>
                <a href="<?= $basePath ?>/admin/produtos" class="action-btn">
                    <i class="bi bi-bag action-icon"></i>
                    <span class="action-text">Produtos</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageOrders): ?>
                <a href="<?= $basePath ?>/admin/pedidos" class="action-btn">
                    <i class="bi bi-receipt action-icon"></i>
                    <span class="action-text">Pedidos</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageCustomers): ?>
                <a href="<?= $basePath ?>/admin/clientes" class="action-btn">
                    <i class="bi bi-people action-icon"></i>
                    <span class="action-text">Clientes</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageHomePage): ?>
                <a href="<?= $basePath ?>/admin/home" class="action-btn">
                    <i class="bi bi-house-door action-icon"></i>
                    <span class="action-text">Home da Loja</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageTheme): ?>
                <a href="<?= $basePath ?>/admin/tema" class="action-btn">
                    <i class="bi bi-palette action-icon"></i>
                    <span class="action-text">Tema da Loja</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageGateways): ?>
                <a href="<?= $basePath ?>/admin/configuracoes/gateways" class="action-btn">
                    <i class="bi bi-credit-card action-icon"></i>
                    <span class="action-text">Gateways</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageNewsletter): ?>
                <a href="<?= $basePath ?>/admin/newsletter" class="action-btn">
                    <i class="bi bi-envelope action-icon"></i>
                    <span class="action-text">Newsletter</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageMedia): ?>
                <a href="<?= $basePath ?>/admin/midias" class="action-btn">
                    <i class="bi bi-image action-icon"></i>
                    <span class="action-text">Biblioteca de Mídia</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageReviews): ?>
                <a href="<?= $basePath ?>/admin/avaliacoes" class="action-btn">
                    <i class="bi bi-star action-icon"></i>
                    <span class="action-text">Avaliações</span>
                </a>
            <?php endif; ?>
            
            <?php if ($canManageStoreUsers): ?>
                <a href="<?= $basePath ?>/admin/usuarios" class="action-btn">
                    <i class="bi bi-shield-check action-icon"></i>
                    <span class="action-text">Usuários e Perfis</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.dashboard-welcome {
    max-width: 1200px;
}
.welcome-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.welcome-card h2 {
    margin-bottom: 1.5rem;
    color: #333;
    font-size: 1.75rem;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.info-label {
    font-weight: 600;
    color: #666;
    font-size: 0.875rem;
}
.info-value {
    color: #333;
    font-size: 1rem;
}
.quick-actions {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.quick-actions h3 {
    margin-bottom: 1.5rem;
    color: #333;
    font-size: 1.5rem;
}
.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: #F7931E;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: center;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.action-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: white;
}
.action-text {
    font-weight: 600;
    font-size: 0.95rem;
}
</style>


