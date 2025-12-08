<?php

namespace App\Http\Middleware;

use App\Core\Middleware;
use App\Services\AuthService;

class AuthMiddleware extends Middleware
{
    private bool $requirePlatform;
    private bool $requireStore;

    public function __construct(bool $requirePlatform = false, bool $requireStore = false)
    {
        $this->requirePlatform = $requirePlatform;
        $this->requireStore = $requireStore;
    }

    public function handle(): bool
    {
        $auth = new AuthService();

        // Obter caminho base se necessário
        $basePath = $this->getBasePath();

        if ($this->requirePlatform && !$auth->isPlatformAuthenticated()) {
            header('Location: ' . $basePath . '/admin/platform/login');
            exit;
        }

        if ($this->requireStore && !$auth->isStoreAuthenticated()) {
            header('Location: ' . $basePath . '/admin/login');
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

