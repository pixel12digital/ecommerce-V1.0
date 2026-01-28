<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Recuperar título base do painel a partir dos settings
    $adminTitleBase = \App\Services\ThemeConfig::get('admin_title_base', 'Store Admin');
    ?>
    <title><?= $pageTitle ?? $adminTitleBase ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Fase 12 – Paleta do Ponto do Golfe no Admin */
        :root {
            /* Cores base do painel admin, alinhadas com o front Ponto do Golfe */
            --pg-admin-sidebar-bg:   #2E7D32;  /* verde principal do front */
            --pg-admin-sidebar-hover:#3A9A42;  /* variação para hover/ativo */
            --pg-admin-sidebar-text: #F5F5F5;  /* textos na sidebar */
            --pg-admin-sidebar-muted:#C0C0C0;  /* textos menos importantes/labels */
            --pg-admin-topbar-bg:    #FFFFFF;
            --pg-admin-topbar-text:  #222222;
            --pg-admin-primary:      #F7931E;  /* laranja de destaque da marca */
            --pg-admin-primary-hover:#d67f1a;
            --pg-admin-border-subtle:#E4E4E4;
            --pg-admin-bg-main:      #F5F5F7;
            --pg-admin-card-bg:      #FFFFFF;
        }
        
        /* Fase 10 – Ajustes layout Admin */
        /* CSS comum do Admin - Fase 10 */
        .admin-table {
            background: var(--pg-admin-card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
        }
        .admin-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table thead {
            background: #f8f8f8;
        }
        .admin-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .admin-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .admin-table tbody tr:hover {
            background: #f9f9f9;
        }
        .admin-filters {
            background: var(--pg-admin-card-bg);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-filters form {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .admin-filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 200px;
            flex: 1;
        }
        .admin-filter-group label {
            font-weight: 600;
            color: #555;
            font-size: 0.875rem;
        }
        .admin-filter-group input,
        .admin-filter-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .admin-filter-group input:focus,
        .admin-filter-group select:focus {
            outline: none;
            border-color: var(--pg-admin-sidebar-bg);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        .admin-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }
        .admin-btn-primary {
            background: var(--pg-admin-primary);
            color: white;
        }
        .admin-btn-primary:hover {
            background: var(--pg-admin-primary-hover);
            transform: translateY(-1px);
        }
        .admin-status-badge {
            padding: 0.375rem 0.875rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }
        .admin-status-badge.publish,
        .admin-status-badge.paid,
        .admin-status-badge.completed,
        .admin-status-badge.aprovado {
            background: #d4edda;
            color: #155724;
        }
        .admin-status-badge.draft,
        .admin-status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        .admin-status-badge.canceled,
        .admin-status-badge.rejeitado {
            background: #f8d7da;
            color: #721c24;
        }
        .admin-status-badge.shipped {
            background: #d1ecf1;
            color: #0c5460;
        }
        .admin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--pg-admin-card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-pagination a,
        .admin-pagination span {
            padding: 0.5rem 1rem;
            background: var(--pg-admin-card-bg);
            border: 1px solid var(--pg-admin-border-subtle);
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .admin-pagination a:hover {
            background: var(--pg-admin-sidebar-bg);
            color: white;
            border-color: var(--pg-admin-sidebar-bg);
        }
        .admin-pagination .current {
            background: var(--pg-admin-sidebar-bg);
            color: white;
            border-color: var(--pg-admin-sidebar-bg);
            font-weight: 600;
        }
        .admin-pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        .admin-pagination-info {
            color: #666;
            margin: 0 1rem;
        }
        .admin-image-placeholder {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.75rem;
            border: 1px solid #e0e0e0;
        }
        .admin-image-placeholder .icon {
            font-size: 1.5rem;
            color: #ccc;
            margin-bottom: 0.25rem;
        }
        .admin-empty-message {
            background: var(--pg-admin-card-bg);
            padding: 3rem;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-empty-message .icon {
            font-size: 3rem;
            color: #ccc;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .admin-empty-message p {
            color: #666;
            font-size: 1.125rem;
            margin: 0;
        }
        .admin-alert {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .admin-alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .admin-alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .admin-alert .icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        /* Formulários padronizados */
        .admin-form {
            background: var(--pg-admin-card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-form-section {
            background: var(--pg-admin-card-bg);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--pg-admin-border-subtle);
            margin-bottom: 2rem;
        }
        .admin-form-section-title {
            font-size: 1.375rem;
            font-weight: 700;
            border-bottom: 1px solid var(--pg-admin-border-subtle);
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .admin-form-group {
            margin-bottom: 1.5rem;
        }
        .admin-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }
        .admin-form-group input[type="text"],
        .admin-form-group input[type="email"],
        .admin-form-group input[type="tel"],
        .admin-form-group input[type="number"],
        .admin-form-group input[type="url"],
        .admin-form-group input[type="date"],
        .admin-form-group select,
        .admin-form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .admin-form-group input:focus,
        .admin-form-group select:focus,
        .admin-form-group textarea:focus {
            outline: none;
            border-color: var(--pg-admin-sidebar-bg);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        .admin-form-group input::placeholder,
        .admin-form-group textarea::placeholder {
            color: #999;
        }
        .admin-form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .admin-form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--pg-admin-bg-main);
            overflow-x: hidden;
        }
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .admin-sidebar {
            width: 240px;
            background: var(--pg-admin-sidebar-bg);
            color: var(--pg-admin-sidebar-text);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .admin-sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        /* Brand/Logo na Sidebar */
        .pg-admin-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Container do logo na sidebar (cartão branco sobre o fundo verde) */
        .pg-admin-brand-logo {
            flex-shrink: 0;
            background-color: #ffffff;
            padding: 6px 10px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            max-width: 160px;
        }
        .pg-admin-brand-logo img {
            display: block;
            max-height: 32px;
            max-width: 140px;
            object-fit: contain;
        }
        .pg-admin-brand-logo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background-color: #ffffff;
            color: #333333;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }
        .pg-admin-brand-text {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        .pg-admin-brand-store {
            font-size: 15px;
            font-weight: 600;
            color: var(--pg-admin-sidebar-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pg-admin-brand-subtitle {
            font-size: 11px;
            color: var(--pg-admin-sidebar-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .sidebar-header .store-name {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
        }
        .sidebar-menu li {
            margin: 0;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: var(--pg-admin-sidebar-text);
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            border-left: 3px solid transparent;
            opacity: 0.85;
        }
        .sidebar-menu a:hover {
            background: var(--pg-admin-sidebar-hover);
            color: white;
            opacity: 1;
        }
        .sidebar-menu a.active {
            background: var(--pg-admin-sidebar-hover);
            color: white;
            font-weight: 600;
            border-left-color: var(--pg-admin-primary);
            opacity: 1;
        }
        .sidebar-menu a .icon {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: 240px;
            display: flex;
            flex-direction: column;
            transition: margin-left 0.3s ease;
        }
        .admin-main.sidebar-collapsed {
            margin-left: 0;
        }
        /* Topbar */
        .admin-topbar {
            background: var(--pg-admin-topbar-bg);
            color: var(--pg-admin-topbar-text);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--pg-admin-border-subtle);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
            padding: 0.5rem;
        }
        .menu-toggle .icon {
            font-size: 1.5rem;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .topbar-right a {
            color: #666;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .topbar-right a:hover {
            background: #f0f0f0;
        }
        .logout-link {
            color: #dc3545 !important;
        }
        .logout-link:hover {
            background: #fee !important;
        }
        /* Content */
        .admin-content {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body class="admin-layout">
    <?php
    // Obter tenant se não foi passado
    if (!isset($tenant)) {
        $tenant = \App\Tenant\TenantContext::tenant();
    }
    
    // Obter caminho base se necessário
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    
    // Detectar basePath de forma mais robusta
    // Verificar REQUEST_URI primeiro
    if (strpos($requestUri, '/ecommerce-v1.0/public') !== false) {
        $basePath = '/ecommerce-v1.0/public';
    } 
    // Verificar SCRIPT_NAME
    elseif (isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/ecommerce-v1.0/public') !== false) {
        $basePath = '/ecommerce-v1.0/public';
    }
    // Verificar PHP_SELF
    elseif (isset($_SERVER['PHP_SELF']) && strpos($_SERVER['PHP_SELF'], '/ecommerce-v1.0/public') !== false) {
        $basePath = '/ecommerce-v1.0/public';
    }
    // Em produção (Hostinger), basePath deve ser vazio (DocumentRoot = public_html/)
    // Se nenhum contiver /ecommerce-v1.0/public, assumir produção (basePath vazio)
    else {
        $basePath = ''; // Produção: DocumentRoot aponta para raiz
    }
    
    // Determinar rota atual para highlight do menu
    $currentPath = parse_url($requestUri, PHP_URL_PATH);
    if (strpos($currentPath, $basePath) === 0) {
        $currentPath = substr($currentPath, strlen($basePath));
    }
    $currentPath = rtrim($currentPath, '/') ?: '/';
    
    // Helper para verificar se um link está ativo
    $isActive = function($path) use ($currentPath) {
        if ($path === '/admin' && $currentPath === '/admin') {
            return true;
        }
        if ($path !== '/admin' && strpos($currentPath, $path) === 0) {
            return true;
        }
        return false;
    };
    ?>
    <!-- DEBUG-STORE-LAYOUT: versão categorias v2 -->
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="sidebar">
            <?php
            // Obter logo da loja
            $logoUrl = \App\Services\ThemeConfig::get('logo_url', '');
            
            // Obter nome da loja: priorizar admin_store_name (settings), depois tenant->name, depois 'Loja'
            $adminStoreName = \App\Services\ThemeConfig::get('admin_store_name', '');
            $storeName = !empty($adminStoreName) 
                ? htmlspecialchars($adminStoreName)
                : htmlspecialchars($tenant->name ?? 'Loja');
            
            // basePath já foi definido acima (linhas 558-578)
            ?>
            <div class="pg-admin-brand">
                <?php if (!empty($logoUrl)): ?>
                    <div class="pg-admin-brand-logo">
                        <img src="<?= $basePath . htmlspecialchars($logoUrl) ?>" alt="<?= $storeName ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="pg-admin-brand-logo-placeholder" style="display: none;">
                            <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="pg-admin-brand-logo pg-admin-brand-logo-placeholder">
                        <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                    </div>
                <?php endif; ?>
                <div class="pg-admin-brand-text">
                    <span class="pg-admin-brand-store"><?= $storeName ?></span>
                    <span class="pg-admin-brand-subtitle">Store Admin</span>
                </div>
            </div>
            <?php
            // Verificar permissões do usuário logado para o menu
            $currentUserId = \App\Services\StoreUserService::getCurrentUserId();
            $canViewDashboard = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'view_dashboard');
            $canManageOrders = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_orders');
            $canManageCustomers = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_customers');
            $canManageProducts = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_products');
            // DEBUG: Log de permissões para diagnóstico
            if (isset($_GET['debug_menu'])) {
                error_log('[DEBUG MENU] currentUserId: ' . ($currentUserId ?: 'null'));
                error_log('[DEBUG MENU] canManageProducts: ' . ($canManageProducts ? 'true' : 'false'));
            }
            $canManageReviews = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_reviews');
            $canManageHomePage = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_home_page');
            $canManageTheme = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_theme');
            $canManageGateways = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_gateways');
            $canManageNewsletter = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_newsletter');
            $canManageMedia = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_media');
            $canManageStoreUsers = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_store_users');
            ?>
            <ul class="sidebar-menu">
                <?php if ($canViewDashboard): ?>
                <li>
                    <a href="<?= $basePath ?>/admin" class="<?= $isActive('/admin') && $currentPath === '/admin' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageOrders): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/pedidos" class="<?= $isActive('/admin/pedidos') ? 'active' : '' ?>">
                        <i class="bi bi-receipt icon"></i>
                        <span>Pedidos</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageCustomers): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/clientes" class="<?= $isActive('/admin/clientes') ? 'active' : '' ?>">
                        <i class="bi bi-people icon"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageProducts): ?>
                <!-- DEBUG: Menu Produtos/Categorias - canManageProducts = true -->
                <li>
                    <a href="<?= $basePath ?>/admin/produtos" class="<?= $isActive('/admin/produtos') && !$isActive('/admin/categorias') ? 'active' : '' ?>">
                        <i class="bi bi-box-seam icon"></i>
                        <span>Produtos</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/categorias" class="<?= $isActive('/admin/categorias') ? 'active' : '' ?>" style="padding-left: 2.5rem;">
                        <i class="bi bi-tags icon"></i>
                        <span>Categorias</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/atributos" class="<?= $isActive('/admin/atributos') ? 'active' : '' ?>" style="padding-left: 2.5rem;">
                        <i class="bi bi-list-ul icon"></i>
                        <span>Atributos</span>
                    </a>
                </li>
                <?php else: ?>
                <!-- DEBUG: Menu Produtos/Categorias - canManageProducts = false (usuário: <?= $currentUserId ?: 'não logado' ?>) -->
                <?php endif; ?>
                <?php if ($canManageReviews): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/avaliacoes" class="<?= $isActive('/admin/avaliacoes') ? 'active' : '' ?>">
                        <i class="bi bi-star icon"></i>
                        <span>Avaliações</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageHomePage): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/home" class="<?= $isActive('/admin/home') ? 'active' : '' ?>">
                        <i class="bi bi-house icon"></i>
                        <span>Home da Loja</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php
                // Verificar se usuário tem permissão para gerenciar usuários
                $currentUserId = \App\Services\StoreUserService::getCurrentUserId();
                $canManageStoreUsers = $currentUserId && \App\Services\StoreUserService::can($currentUserId, 'manage_store_users');
                if ($canManageStoreUsers):
                ?>
                <li>
                    <a href="<?= $basePath ?>/admin/usuarios" class="<?= $isActive('/admin/usuarios') ? 'active' : '' ?>">
                        <i class="bi bi-shield-check icon"></i>
                        <span>Usuários e Perfis</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageStoreUsers): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/usuarios" class="<?= $isActive('/admin/usuarios') ? 'active' : '' ?>">
                        <i class="bi bi-shield-check icon"></i>
                        <span>Usuários e Perfis</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageTheme): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/tema" class="<?= $isActive('/admin/tema') ? 'active' : '' ?>">
                        <i class="bi bi-palette icon"></i>
                        <span>Tema da Loja</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageGateways): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/configuracoes/gateways" class="<?= $isActive('/admin/configuracoes/gateways') ? 'active' : '' ?>">
                        <i class="bi bi-credit-card icon"></i>
                        <span>Gateways</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageNewsletter): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/newsletter" class="<?= $isActive('/admin/newsletter') ? 'active' : '' ?>">
                        <i class="bi bi-envelope icon"></i>
                        <span>Newsletter</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($canManageMedia): ?>
                <li>
                    <a href="<?= $basePath ?>/admin/midias" class="<?= $isActive('/admin/midias') ? 'active' : '' ?>">
                        <i class="bi bi-images icon"></i>
                        <span>Biblioteca de Mídia</span>
                    </a>
                </li>
                <?php endif; ?>
                <li style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="<?= $basePath ?>/" target="_blank" style="color: var(--pg-admin-primary); font-weight: 600;">
                        <i class="bi bi-eye icon"></i>
                        <span>Ver Site</span>
                    </a>
                </li>
            </ul>
        </aside>
        
        <div class="admin-main">
            <header class="admin-topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()"><i class="bi bi-list icon"></i></button>
                    <h1 class="page-title"><?= $pageTitle ?? 'Dashboard' ?></h1>
                </div>
                <div class="topbar-right">
                    <a href="<?= $basePath ?>/admin/logout" class="logout-link">Sair</a>
                </div>
            </header>
            
            <main class="admin-content">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // Fechar sidebar ao clicar fora (mobile)
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    </script>
    
    <!-- Editor de texto rico (CKEditor 5 Classic) para campos de conteúdo - Fase 11.2 -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ClassicEditor === 'undefined') {
                console.warn('CKEditor 5 não foi carregado. Verifique o CDN.');
                return;
            }

            const textareas = document.querySelectorAll('textarea.pg-richtext:not([data-ckeditor-initialized])');

            textareas.forEach(function (textarea) {
                // Marcar como inicializado antes de criar para evitar duplicação
                textarea.setAttribute('data-ckeditor-initialized', 'true');
                
                ClassicEditor.create(textarea, {
                    toolbar: [
                        'undo', 'redo',
                        '|', 'bold', 'italic', 'underline',
                        '|', 'heading',
                        '|', 'bulletedList', 'numberedList',
                        '|', 'alignment',
                        '|', 'link'
                    ],
                    heading: {
                        options: [
                            { model: 'paragraph', title: 'Parágrafo', class: 'ck-heading_paragraph' },
                            { model: 'heading2', title: 'Título Médio', class: 'ck-heading_heading2' },
                            { model: 'heading3', title: 'Subtítulo', class: 'ck-heading_heading3' }
                        ]
                    }
                }).catch(function (error) {
                    console.error('Erro ao inicializar CKEditor 5 para um campo .pg-richtext', error);
                    // Remover marcação em caso de erro para permitir nova tentativa
                    textarea.removeAttribute('data-ckeditor-initialized');
                });
            });
        });
    </script>
    <style>
        /* Ajustes de CSS para CKEditor 5 */
        .pg-richtext + .ck-editor {
            margin-top: 4px;
        }
        .ck-editor__editable_inline {
            min-height: 220px;
        }
    </style>
    <!-- Media Picker - Componente genérico para escolha de mídia -->
    <script>
        // Definir basePath globalmente para o Media Picker
        window.basePath = '<?= htmlspecialchars($basePath) ?>';
    </script>
    <?php
    /**
     * Helper para gerar caminho de assets do admin
     * Detecta automaticamente o ambiente (dev vs produção)
     * 
     * Em dev: /ecommerce-v1.0/public/admin/js/media-picker.js
     * Em produção: /admin/js/media-picker.js (DocumentRoot = public_html/)
     */
    function admin_asset_path($relativePath) {
        // Remover barra inicial se existir
        $relativePath = ltrim($relativePath, '/');
        
        // Detectar se estamos em desenvolvimento local
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        // Se REQUEST_URI ou SCRIPT_NAME contém /ecommerce-v1.0/public, estamos em dev
        if (strpos($requestUri, '/ecommerce-v1.0/public') !== false || 
            strpos($scriptName, '/ecommerce-v1.0/public') !== false) {
            return '/ecommerce-v1.0/public/admin/' . $relativePath;
        }
        
        // Em produção na Hostinger:
        // - DocumentRoot aponta para public_html/ (raiz do projeto)
        // - Arquivos físicos estão em public_html/public/admin/js/...
        // - Para acessar via URL, precisamos usar /public/admin/...
        // Isso porque o Apache resolve URLs baseado no DocumentRoot
        return '/public/admin/' . $relativePath;
    }
    
    $mediaPickerPath = admin_asset_path('js/media-picker.js');
    ?>
    <script src="<?= htmlspecialchars($mediaPickerPath) ?>" onerror="console.error('Erro ao carregar media-picker.js:', this.src);"></script>
    <script>
        // Debug: verificar se o script foi carregado
        window.addEventListener('load', function() {
            if (typeof window.openMediaLibrary === 'function') {
                console.log('[Layout] media-picker.js carregado com sucesso');
            } else {
                console.error('[Layout] media-picker.js NÃO foi carregado ou não está disponível');
            }
        });
    </script>
</body>
</html>


