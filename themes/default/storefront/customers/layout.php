<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$pageTitle = $pageTitle ?? 'Minha Conta';
$customerName = $_SESSION['customer_name'] ?? 'Cliente';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a { color: white; text-decoration: none; }
        .header-logo {
            font-size: 1.5rem;
            font-weight: 700;
        }
        .header-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        .sidebar {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        /* Fase 10 - Ajustes layout storefront */
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            color: #333;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
            font-size: 0.95rem;
        }
        .sidebar-menu a:hover {
            background: #f5f5f5;
        }
        .sidebar-menu a.active {
            background: #e3f2fd;
            color: #023A8D;
            font-weight: 600;
            border-left: 3px solid #023A8D;
        }
        .sidebar-menu a i {
            font-size: 1.25rem;
            color: inherit;
            width: 20px;
            text-align: center;
        }
        .content {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .content-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        .content-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .alert .icon {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        /* Responsivo - Fase 10 */
        /* Responsivo - Fase 10 */
        @media (max-width: 768px) {
            .header {
                padding: 1rem 1.5rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            .container {
                grid-template-columns: 1fr;
                margin: 1.5rem auto;
                padding: 0 1rem;
            }
            .sidebar {
                order: 2;
                margin-top: 1.5rem;
            }
            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .sidebar-menu li {
                margin-bottom: 0;
                flex: 1;
                min-width: calc(50% - 0.25rem);
            }
            .sidebar-menu a {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
            .content {
                padding: 1.5rem;
            }
            .content-header h1 {
                font-size: 1.5rem;
            }
        }
        /* Estilos para formulários da área do cliente */
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
            font-size: 0.95rem;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #023A8D;
            box-shadow: 0 0 0 3px rgba(2, 58, 141, 0.1);
        }
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }
        .form-group input:disabled {
            background: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
        .btn {
            padding: 0.875rem 2rem;
            background: #023A8D;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn:hover {
            background: #012a6b;
            transform: translateY(-1px);
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="<?= $basePath ?>" class="header-logo">Loja</a>
        <div class="header-user">
            <span>Olá, <?= htmlspecialchars($customerName) ?></span>
            <a href="<?= $basePath ?>/minha-conta/logout">Sair</a>
        </div>
    </header>

    <div class="container">
        <aside class="sidebar">
            <nav>
                <ul class="sidebar-menu">
                    <li>
                        <a href="<?= $basePath ?>/minha-conta" class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/minha-conta') === strpos($_SERVER['REQUEST_URI'] ?? '', '/minha-conta/pedidos') ? '' : 'active' ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/minha-conta/pedidos" class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/minha-conta/pedidos') !== false ? 'active' : '' ?>">
                            <i class="bi bi-receipt"></i>
                            <span>Pedidos</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/minha-conta/enderecos" class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/minha-conta/enderecos') !== false ? 'active' : '' ?>">
                            <i class="bi bi-geo-alt"></i>
                            <span>Endereços</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/minha-conta/perfil" class="<?= strpos($_SERVER['REQUEST_URI'] ?? '', '/minha-conta/perfil') !== false ? 'active' : '' ?>">
                            <i class="bi bi-person"></i>
                            <span>Dados da Conta</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $basePath ?>/minha-conta/logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Sair</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="content">
            <?= $content ?? '' ?>
        </main>
    </div>
</body>
</html>


