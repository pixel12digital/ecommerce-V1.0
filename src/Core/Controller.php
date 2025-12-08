<?php

namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/../../themes/default/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }

        require $viewPath;
    }

    protected function viewWithLayout(string $layout, string $view, array $data = []): void
    {
        // Capturar o conteúdo da view
        ob_start();
        extract($data);
        $viewPath = __DIR__ . '/../../themes/default/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View não encontrada: {$view}");
        }
        
        require $viewPath;
        $content = ob_get_clean();
        
        // Adicionar o conteúdo aos dados do layout
        $data['content'] = $content;
        
        // Renderizar o layout
        extract($data);
        $layoutPath = __DIR__ . '/../../themes/default/' . $layout . '.php';
        
        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout não encontrado: {$layout}");
        }

        require $layoutPath;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url): void
    {
        // Se a URL não começar com http, adicionar caminho base se necessário
        if (strpos($url, 'http') !== 0) {
            // Verificar se há um caminho base no REQUEST_URI
            $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
            $basePath = '';
            
            // Se o REQUEST_URI contém /ecommerce-v1.0/public, usar como base
            if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
                $basePath = '/ecommerce-v1.0/public';
            }
            
            $url = $basePath . $url;
        }
        
        header("Location: {$url}");
        exit;
    }
}

