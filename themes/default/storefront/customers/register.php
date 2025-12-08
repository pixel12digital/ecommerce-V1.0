<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$errors = $errors ?? [];
$formData = $formData ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Minha Conta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .register-header p {
            color: #666;
            font-size: 0.9rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2E7D32;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background: #2E7D32;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary:hover {
            background: #1B5E20;
        }
        .alert-error {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .register-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .register-footer a {
            color: #2E7D32;
            text-decoration: none;
        }
        .register-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="bi bi-person-plus"></i> Cadastro</h1>
            <p>Crie sua conta</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= $basePath ?>/minha-conta/registrar">
            <div class="form-group">
                <label for="name">Nome Completo *</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Telefone</label>
                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
            </div>

            <div class="form-group">
                <label for="document">CPF/CNPJ</label>
                <input type="text" id="document" name="document" value="<?= htmlspecialchars($formData['document'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Senha *</label>
                <input type="password" id="password" name="password" required minlength="6">
                <small style="color: #666; font-size: 0.875rem;">Mínimo de 6 caracteres</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar Senha *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6">
            </div>

            <button type="submit" class="btn-primary">Cadastrar</button>
        </form>

        <div class="register-footer">
            <p>Já tem conta? <a href="<?= $basePath ?>/minha-conta/login">Faça login</a></p>
            <p><a href="<?= $basePath ?>">← Voltar para a loja</a></p>
        </div>
    </div>
</body>
</html>


