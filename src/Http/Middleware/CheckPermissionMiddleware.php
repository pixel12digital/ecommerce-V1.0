<?php

namespace App\Http\Middleware;

use App\Core\Middleware;
use App\Services\AuthService;
use App\Services\StoreUserService;

class CheckPermissionMiddleware extends Middleware
{
    private string $permissionSlug;

    public function __construct(string $permissionSlug)
    {
        $this->permissionSlug = $permissionSlug;
    }

    public function handle(): bool
    {
        // Primeiro verificar se está autenticado
        $auth = new AuthService();
        if (!$auth->isStoreAuthenticated()) {
            $basePath = $this->getBasePath();
            header('Location: ' . $basePath . '/admin/login');
            exit;
        }

        // Obter ID do usuário logado
        $userId = StoreUserService::getCurrentUserId();
        if (!$userId) {
            $this->denyAccess('Usuário não encontrado na sessão.');
            return false;
        }

        // Verificar se tem a permissão
        if (!StoreUserService::can($userId, $this->permissionSlug)) {
            $this->denyAccess('Você não tem permissão para acessar esta página.');
            return false;
        }

        return true;
    }

    private function denyAccess(string $message): void
    {
        http_response_code(403);
        
        // Se for requisição AJAX, retornar JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        }

        // Caso contrário, exibir página HTML de erro
        $basePath = $this->getBasePath();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Acesso Negado</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    margin: 0;
                    background: #f5f5f5;
                }
                .error-container {
                    background: white;
                    padding: 3rem;
                    border-radius: 8px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    text-align: center;
                    max-width: 500px;
                }
                h1 {
                    color: #d32f2f;
                    margin-bottom: 1rem;
                }
                p {
                    color: #666;
                    margin-bottom: 2rem;
                }
                a {
                    display: inline-block;
                    padding: 0.75rem 1.5rem;
                    background: #F7931E;
                    color: white;
                    text-decoration: none;
                    border-radius: 4px;
                    transition: background 0.2s;
                }
                a:hover {
                    background: #d67f1a;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>403 - Acesso Negado</h1>
                <p><?= htmlspecialchars($message) ?></p>
                <a href="<?= htmlspecialchars($basePath) ?>/admin">Voltar ao Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    private function getBasePath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
            return '/ecommerce-v1.0/public';
        }
        return '';
    }
}

