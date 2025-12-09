<?php
use App\Support\StoreBranding;

// Obter branding da loja
$branding = StoreBranding::getBranding();
$logoUrl = $branding['logo_url'] ?? null;
$storeName = $branding['store_name'] ?? 'Loja';

// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Store Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        /* Bloco de branding no login admin */
        .pg-admin-login-brand {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 24px;
            text-align: center;
        }
        .pg-admin-login-logo {
            background-color: #ffffff;
            padding: 8px 12px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            margin-bottom: 12px;
        }
        .pg-admin-login-logo img {
            display: block;
            max-height: 40px;
            max-width: 180px;
            object-fit: contain;
        }
        .pg-admin-login-logo-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #333333;
            font-size: 16px;
        }
        .pg-admin-login-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .pg-admin-login-store {
            font-size: 18px;
            font-weight: 600;
            color: #333333;
        }
        .pg-admin-login-subtitle {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #888888;
        }
        
        h1 { margin-bottom: 1.5rem; color: #333; display: none; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #555; }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #2E7D32;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }
        button:hover { background: #1B5E20; }
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="pg-admin-login-brand">
            <?php if ($logoUrl): ?>
                <div class="pg-admin-login-logo">
                    <img src="<?= $basePath . htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($storeName) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="pg-admin-login-logo-placeholder" style="display: none;">
                        <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="pg-admin-login-logo pg-admin-login-logo-placeholder">
                    <span><?= strtoupper(substr($storeName, 0, 2)) ?></span>
                </div>
            <?php endif; ?>
            
            <div class="pg-admin-login-text">
                <div class="pg-admin-login-store">
                    <?= htmlspecialchars($storeName) ?>
                </div>
                <div class="pg-admin-login-subtitle">
                    Store Admin
                </div>
            </div>
        </div>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= $basePath ?>/admin/login">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>

