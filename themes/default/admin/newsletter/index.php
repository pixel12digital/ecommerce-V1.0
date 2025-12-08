<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter - Store Admin</title>
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
        }
        .search-form {
            margin-bottom: 1.5rem;
        }
        .search-form input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            width: 300px;
        }
        .search-form button {
            padding: 0.75rem 1.5rem;
            background: #F7931E;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
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
        <h2>Inscrições Newsletter</h2>
        <div>
            <a href="<?= $basePath ?>/admin/home">Home da Loja</a> | 
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <form method="GET" class="search-form">
                <input type="text" name="q" value="<?= htmlspecialchars($filtro['q'] ?? '') ?>" 
                       placeholder="Buscar por nome ou e-mail...">
                <button type="submit">Buscar</button>
                <?php if (!empty($filtro['q'])): ?>
                    <a href="<?= $basePath ?>/admin/newsletter" style="margin-left: 1rem;">Limpar</a>
                <?php endif; ?>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Origem</th>
                        <th>Data de Inscrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inscricoes)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: #999;">
                                Nenhuma inscrição encontrada.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inscricoes as $inscricao): ?>
                            <tr>
                                <td><?= htmlspecialchars($inscricao['nome'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($inscricao['email']) ?></td>
                                <td><?= htmlspecialchars($inscricao['origem'] ?: 'home') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($inscricao['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>


