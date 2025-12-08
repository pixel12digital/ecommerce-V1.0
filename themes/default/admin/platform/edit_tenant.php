<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tenant</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        input[type="text"], select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            background: #023A8D;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover { background: #022a6b; }
        .cancel { margin-left: 1rem; background: #6c757d; }
        .cancel:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="header">
        <a href="/admin/platform"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    <div class="container">
        <div class="form-container">
            <h1>Editar Tenant</h1>
            <form method="POST">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($tenant['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($tenant['slug']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?= $tenant['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="suspended" <?= $tenant['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        <option value="trial" <?= $tenant['status'] === 'trial' ? 'selected' : '' ?>>Trial</option>
                        <option value="cancelled" <?= $tenant['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Plano</label>
                    <input type="text" name="plan" value="<?= htmlspecialchars($tenant['plan']) ?>" required>
                </div>
                <button type="submit">Salvar</button>
                <a href="/admin/platform" class="cancel" style="display: inline-block; padding: 0.75rem 2rem; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>

