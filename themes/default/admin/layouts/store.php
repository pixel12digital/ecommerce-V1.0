<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Store Admin' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Fase 10 – Ajustes layout Admin */
        /* CSS comum do Admin - Fase 10 */
        .admin-table {
            background: white;
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
            background: white;
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
            border-color: #023A8D;
            box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
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
            background: #F7931E;
            color: white;
        }
        .admin-btn-primary:hover {
            background: #e6851a;
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
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-pagination a,
        .admin-pagination span {
            padding: 0.5rem 1rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            transition: all 0.2s;
        }
        .admin-pagination a:hover {
            background: #023A8D;
            color: white;
            border-color: #023A8D;
        }
        .admin-pagination .current {
            background: #023A8D;
            color: white;
            border-color: #023A8D;
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
            background: white;
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
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-form-section {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .admin-form-section-title {
            font-size: 1.375rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f0f0f0;
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
            border-color: #023A8D;
            box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
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
            background: #f5f5f5;
            overflow-x: hidden;
        }
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        /* Sidebar */
        .admin-sidebar {
            width: 240px;
            background: #023A8D;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .admin-sidebar.collapsed {
            transform: translateX(-100%);
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
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            font-weight: 600;
            border-left: 3px solid #F7931E;
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
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e0e0e0;
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
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
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
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Store Admin</h2>
                <div class="store-name"><?= htmlspecialchars($tenant->name ?? 'Loja') ?></div>
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="<?= $basePath ?>/admin" class="<?= $isActive('/admin') && $currentPath === '/admin' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2 icon"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/pedidos" class="<?= $isActive('/admin/pedidos') ? 'active' : '' ?>">
                        <i class="bi bi-receipt icon"></i>
                        <span>Pedidos</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/clientes" class="<?= $isActive('/admin/clientes') ? 'active' : '' ?>">
                        <i class="bi bi-people icon"></i>
                        <span>Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/produtos" class="<?= $isActive('/admin/produtos') ? 'active' : '' ?>">
                        <i class="bi bi-box-seam icon"></i>
                        <span>Produtos</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/avaliacoes" class="<?= $isActive('/admin/avaliacoes') ? 'active' : '' ?>">
                        <i class="bi bi-star icon"></i>
                        <span>Avaliações</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/home" class="<?= $isActive('/admin/home') ? 'active' : '' ?>">
                        <i class="bi bi-house icon"></i>
                        <span>Home da Loja</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/tema" class="<?= $isActive('/admin/tema') ? 'active' : '' ?>">
                        <i class="bi bi-palette icon"></i>
                        <span>Tema da Loja</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/configuracoes/gateways" class="<?= $isActive('/admin/configuracoes/gateways') ? 'active' : '' ?>">
                        <i class="bi bi-credit-card icon"></i>
                        <span>Gateways</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $basePath ?>/admin/newsletter" class="<?= $isActive('/admin/newsletter') ? 'active' : '' ?>">
                        <i class="bi bi-envelope icon"></i>
                        <span>Newsletter</span>
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
</body>
</html>


