<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página não encontrada</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            text-align: center;
        }
        .error-container {
            background: white;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { font-size: 4rem; color: #c33; margin-bottom: 1rem; }
        p { color: #666; margin-bottom: 2rem; }
        a { color: #023A8D; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <p><?= isset($message) ? htmlspecialchars($message) : 'Página não encontrada' ?></p>
        <?php
        $basePath = '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
            $basePath = '/ecommerce-v1.0/public';
        }
        ?>
        <a href="<?= $basePath ?>/"><i class="bi bi-arrow-left icon"></i> Voltar para a home</a>
    </div>
</body>
</html>

