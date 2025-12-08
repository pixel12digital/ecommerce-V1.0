<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado das Migrations</title>
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
            margin-bottom: 1rem;
        }
        .result-item {
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .result-success {
            background: #d4edda;
            border-color: #28a745;
        }
        .result-error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .result-migration {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .result-message {
            color: #721c24;
            font-size: 0.9rem;
        }
        a {
            display: inline-block;
            margin-top: 1rem;
            color: #023A8D;
            text-decoration: none;
        }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <a href="/admin/system/updates"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    <div class="container">
        <div class="card">
            <h1>Resultado das Migrations</h1>
            <?php foreach ($results as $result): ?>
                <div class="result-item result-<?= $result['status'] ?>">
                    <div class="result-migration"><?= htmlspecialchars($result['migration']) ?></div>
                    <?php if ($result['status'] === 'success'): ?>
                        <div><i class="bi bi-check-circle-fill icon" style="color: #28a745;"></i> Aplicada com sucesso</div>
                    <?php else: ?>
                        <div class="result-message"><i class="bi bi-x-circle-fill icon" style="color: #dc3545;"></i> Erro: <?= htmlspecialchars($result['message'] ?? 'Erro desconhecido') ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <a href="/admin/system/updates">Ver atualizações</a>
        </div>
    </div>
</body>
</html>

