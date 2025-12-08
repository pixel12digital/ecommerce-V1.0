<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizações do Sistema</title>
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
        h1 { margin-bottom: 1.5rem; }
        .version-info {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .version-label {
            font-weight: 600;
            color: #555;
        }
        .version-value {
            font-size: 1.5rem;
            color: #023A8D;
            font-weight: bold;
        }
        .pending-list {
            list-style: none;
            margin: 1rem 0;
        }
        .pending-list li {
            padding: 0.5rem;
            background: #fff3cd;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
        .no-pending {
            color: #28a745;
            font-weight: 600;
        }
        button {
            background: #F7931E;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 1rem;
        }
        button:hover { background: #e6851a; }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Atualizações do Sistema</h2>
        <a href="/admin"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    <div class="container">
        <div class="card">
            <h1>Versão Atual</h1>
            <div class="version-info">
                <div class="version-label">Versão instalada:</div>
                <div class="version-value"><?= htmlspecialchars($currentVersion) ?></div>
            </div>
        </div>

        <div class="card">
            <h1>Migrations Pendentes</h1>
            <?php if (empty($pendingMigrations)): ?>
                <p class="no-pending"><i class="bi bi-check-circle-fill icon" style="color: #28a745;"></i> Nenhuma migration pendente. Sistema está atualizado!</p>
            <?php else: ?>
                <p>Encontradas <?= count($pendingMigrations) ?> migration(s) pendente(s):</p>
                <ul class="pending-list">
                    <?php foreach ($pendingMigrations as $migration): ?>
                        <li><?= htmlspecialchars($migration) ?></li>
                    <?php endforeach; ?>
                </ul>
                <form method="POST" action="/admin/system/updates/run">
                    <button type="submit">Rodar Migrations Pendentes</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

