<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolotas de Categorias - Store Admin</title>
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
        .card h3 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
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
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f8f8;
            font-weight: 600;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
        }
        .btn-primary {
            background: #F7931E;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .success-message {
            background: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .error-message {
            background: #f44336;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .icon-preview {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            background: #e0e0e0;
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
        <h2>Bolotas de Categorias</h2>
        <div>
            <a href="<?= $basePath ?>/admin/home">Home da Loja</a> | 
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Operação realizada com sucesso!</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">Erro ao processar. Tente novamente.</div>
        <?php endif; ?>

        <!-- Formulário de Adicionar -->
        <div class="card">
            <h3>Adicionar Nova Bolota</h3>
            <form method="POST" action="<?= $basePath ?>/admin/home/categorias-pills">
                <div class="form-group">
                    <label>Categoria *</label>
                    <select name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Label (opcional - sobrescreve nome da categoria)</label>
                    <input type="text" name="label" placeholder="Ex: Bonés">
                </div>
                <div class="form-group">
                    <label>Caminho do Ícone (opcional)</label>
                    <input type="text" name="icone_path" placeholder="Ex: /images/icons/bone.png">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ordem</label>
                        <input type="number" name="ordem" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ativo" value="1" checked> Ativo
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Adicionar</button>
            </form>
        </div>

        <!-- Lista de Bolotas -->
        <div class="card">
            <h3>Bolotas Configuradas</h3>
            <?php if (empty($pills)): ?>
                <p>Nenhuma bolota configurada ainda.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ordem</th>
                            <th>Ícone</th>
                            <th>Label</th>
                            <th>Categoria</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pills as $pill): ?>
                            <tr>
                                <td><?= $pill['ordem'] ?></td>
                                <td>
                                    <?php if ($pill['icone_path']): ?>
                                        <img src="<?= $basePath ?>/<?= htmlspecialchars($pill['icone_path']) ?>" 
                                             alt="Ícone" class="icon-preview">
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?></td>
                                <td><?= htmlspecialchars($pill['categoria_nome']) ?></td>
                                <td><?= $pill['ativo'] ? '<i class="bi bi-check-circle-fill" style="color: #2e7d32;"></i> Ativo' : '<i class="bi bi-x-circle-fill" style="color: #d32f2f;"></i> Inativo' ?></td>
                                <td>
                                    <a href="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>/editar" 
                                       class="btn btn-secondary">Editar</a>
                                    <form method="POST" 
                                          action="<?= $basePath ?>/admin/home/categorias-pills/<?= $pill['id'] ?>/excluir" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


