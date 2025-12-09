<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Admin - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
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
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        h1 { margin-bottom: 1rem; }
        .info-item {
            margin-bottom: 0.5rem;
        }
        .info-label {
            font-weight: 600;
            color: #555;
        }
        .link-button {
            display: inline-block;
            background: #F7931E;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 1rem;
        }
        .link-button:hover {
            background: #e6851a;
        }
    </style>
</head>
<body>
    <?php
    // Obter caminho base se necessário
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    <div class="header">
        <h2>Store Admin - <?= htmlspecialchars($tenant->name) ?></h2>
        <a href="<?= $basePath ?>/admin/logout">Sair</a>
    </div>
    <div class="container">
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
        <div class="card">
            <h1>Bem-vindo</h1>
            <div class="info-item">
                <span class="info-label">Loja:</span> <?= htmlspecialchars($tenantName) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Slug:</span> <?= htmlspecialchars($tenantSlug) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span> <?= htmlspecialchars($tenantStatusLabel) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Plano:</span> <?= htmlspecialchars($tenantPlanLabel) ?>
            </div>
            <a href="<?= $basePath ?>/admin/system/updates" class="link-button">Atualizações do Sistema</a>
            <a href="<?= $basePath ?>/admin/produtos" class="link-button">Produtos</a>
            <a href="<?= $basePath ?>/admin/home" class="link-button">Home da Loja</a>
            <a href="<?= $basePath ?>/admin/pedidos" class="link-button">Pedidos</a>
            <a href="<?= $basePath ?>/admin/tema" class="link-button">Tema da Loja</a>
        </div>
    </div>
</body>
</html>

