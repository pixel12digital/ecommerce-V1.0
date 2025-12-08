<?php
/**
 * Layout base do Admin - Fase 10
 * 
 * Layout padronizado para todas as páginas do admin com sidebar, header e conteúdo.
 * 
 * Variáveis esperadas:
 * - $pageTitle: Título da página
 * - $basePath: Caminho base da aplicação
 * - $currentRoute: Rota atual (para destacar item ativo no menu)
 * - $content: Conteúdo HTML da página (via ob_start/ob_get_clean)
 */
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$pageTitle = $pageTitle ?? 'Admin';
$currentRoute = $currentRoute ?? '';
$tenantName = $tenantName ?? 'Loja';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Store Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Fase 10 – Ajustes layout Admin */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        /* Header */
        .admin-header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .admin-header h2 {
            font-size: 1.25rem;
            font-weight: 700;
        }
        .admin-header-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .admin-header-nav a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            transition: background 0.2s;
            font-size: 0.9rem;
        }
        .admin-header-nav a:hover {
            background: rgba(255,255,255,0.1);
        }
        .admin-header-nav a i {
            font-size: 1rem;
        }
        
        /* Container principal */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        
        /* Sidebar */
        .admin-sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 4px rgba(0,0,0,0.05);
            padding: 1.5rem 0;
            position: sticky;
            top: 0;
            height: calc(100vh - 70px);
            overflow-y: auto;
        }
        .admin-sidebar-menu {
            list-style: none;
        }
        .admin-sidebar-menu li {
            margin-bottom: 0.25rem;
        }
        .admin-sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1.5rem;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .admin-sidebar-menu a:hover {
            background: #f5f5f5;
            color: #023A8D;
        }
        .admin-sidebar-menu a.active {
            background: #e3f2fd;
            color: #023A8D;
            font-weight: 600;
            border-left: 3px solid #023A8D;
        }
        .admin-sidebar-menu a i {
            font-size: 1.125rem;
            width: 20px;
            text-align: center;
            color: inherit;
        }
        
        /* Conteúdo */
        .admin-content {
            flex: 1;
            padding: 2rem;
            max-width: 1400px;
            width: 100%;
        }
        .admin-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-page-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #333;
        }
        .admin-page-actions {
            display: flex;
            gap: 1rem;
        }
        
        /* Mensagens de feedback */
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
        .admin-alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .admin-alert .icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        /* Botões padronizados */
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .admin-btn-secondary {
            background: #6c757d;
            color: white;
        }
        .admin-btn-secondary:hover {
            background: #5a6268;
        }
        .admin-btn-outline {
            background: transparent;
            color: #023A8D;
            border: 1px solid #023A8D;
        }
        .admin-btn-outline:hover {
            background: #023A8D;
            color: white;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: static;
                padding: 1rem 0;
            }
            .admin-sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                padding: 0 1rem;
            }
            .admin-sidebar-menu li {
                margin-bottom: 0;
                flex: 1;
                min-width: calc(50% - 0.25rem);
            }
            .admin-sidebar-menu a {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
            .admin-content {
                padding: 1.5rem;
            }
            .admin-page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .admin-header {
                padding: 1rem 1.5rem;
                flex-wrap: wrap;
            }
            .admin-header-nav {
                gap: 1rem;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h2>Store Admin - <?= htmlspecialchars($tenantName) ?></h2>
        <nav class="admin-header-nav">
            <a href="<?= $basePath ?>/admin">
                <i class="bi bi-speedometer2 icon"></i>
                Dashboard
            </a>
            <a href="<?= $basePath ?>/admin/logout">
                <i class="bi bi-box-arrow-right icon"></i>
                Sair
            </a>
        </nav>
    </header>
    
    <div class="admin-container">
        <aside class="admin-sidebar">
            <nav>
                <ul class="admin-sidebar-menu">
                    <li>
                        <a href="<?= $basePath ?>/admin" class="<?= $currentRoute === 'dashboard' ? 'active' : '' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/produtos" class="<?= strpos($currentRoute, 'produtos') !== false ? 'active' : '' ?>">
                            <i class="bi bi-box-seam"></i>
                            <span>Produtos</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/pedidos" class="<?= strpos($currentRoute, 'pedidos') !== false ? 'active' : '' ?>">
                            <i class="bi bi-receipt"></i>
                            <span>Pedidos</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/clientes" class="<?= strpos($currentRoute, 'clientes') !== false ? 'active' : '' ?>">
                            <i class="bi bi-people"></i>
                            <span>Clientes</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/avaliacoes" class="<?= strpos($currentRoute, 'avaliacoes') !== false ? 'active' : '' ?>">
                            <i class="bi bi-star"></i>
                            <span>Avaliações</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/home" class="<?= strpos($currentRoute, 'home') !== false ? 'active' : '' ?>">
                            <i class="bi bi-house"></i>
                            <span>Home da Loja</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/tema" class="<?= strpos($currentRoute, 'tema') !== false ? 'active' : '' ?>">
                            <i class="bi bi-palette"></i>
                            <span>Tema da Loja</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/gateways" class="<?= strpos($currentRoute, 'gateways') !== false ? 'active' : '' ?>">
                            <i class="bi bi-credit-card"></i>
                            <span>Gateways</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/admin/newsletter" class="<?= strpos($currentRoute, 'newsletter') !== false ? 'active' : '' ?>">
                            <i class="bi bi-envelope"></i>
                            <span>Newsletter</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <main class="admin-content">
            <?= $content ?? '' ?>
        </main>
    </div>
</body>
</html>


