<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Categoria em Destaque - Store Admin</title>
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
        }
        .btn-primary {
            background: #F7931E;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    <div class="header">
        <h2>Editar Categoria em Destaque</h2>
        <div>
            <a href="<?= $basePath ?>/admin/home">Home da Loja</a> | 
            <a href="<?= $basePath ?>/admin/home/categorias-pills">Categorias em Destaque</a> | 
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <form method="POST" action="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>">
                <div class="form-group">
                    <label>Categoria *</label>
                    <select name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $pill['categoria_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Label (opcional)</label>
                    <input type="text" name="label" value="<?= htmlspecialchars($pill['label'] ?? '') ?>" placeholder="Ex: Bonés">
                </div>
                <div class="form-group">
                    <label>Caminho do Ícone (opcional)</label>
                    <input type="text" name="icone_path" value="<?= htmlspecialchars($pill['icone_path'] ?? '') ?>" placeholder="Ex: /images/icons/bone.png">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" name="ordem" value="<?= $pill['ordem'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ativo" value="1" <?= $pill['ativo'] ? 'checked' : '' ?>> Ativo
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="<?= $basePath ?>/admin/home/categorias-pills" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>


