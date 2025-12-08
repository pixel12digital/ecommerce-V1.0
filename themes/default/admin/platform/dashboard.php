<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Admin - Dashboard</title>
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
        h1 { margin-bottom: 1.5rem; }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th { background: #f8f9fa; font-weight: 600; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.875rem;
        }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-suspended { background: #f8d7da; color: #721c24; }
        .badge-trial { background: #fff3cd; color: #856404; }
        a { color: #023A8D; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Platform Admin</h2>
        <a href="/admin/platform/logout">Sair</a>
    </div>
    <div class="container">
        <h1>Tenants</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Plano</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tenants as $tenant): ?>
                <tr>
                    <td><?= $tenant['id'] ?></td>
                    <td><?= htmlspecialchars($tenant['name']) ?></td>
                    <td><?= htmlspecialchars($tenant['slug']) ?></td>
                    <td>
                        <span class="badge badge-<?= $tenant['status'] ?>">
                            <?= htmlspecialchars($tenant['status']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($tenant['plan']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($tenant['created_at'])) ?></td>
                    <td><a href="/admin/platform/tenants/<?= $tenant['id'] ?>/edit">Editar</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>



