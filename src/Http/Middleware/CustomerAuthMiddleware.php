<?php

namespace App\Http\Middleware;

use App\Core\Middleware;

class CustomerAuthMiddleware extends Middleware
{
    public function handle(): bool
    {
        // Iniciar sessão se necessário
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Verificar se cliente está logado
        if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
            $basePath = $this->getBasePath();
            $redirectUrl = $basePath . '/minha-conta/login';
            
            // Adicionar mensagem de erro
            $_SESSION['customer_auth_redirect'] = $_SERVER['REQUEST_URI'] ?? '/';
            $_SESSION['customer_auth_message'] = 'Faça login para acessar sua conta.';
            
            header('Location: ' . $redirectUrl);
            exit;
        }

        return true;
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


