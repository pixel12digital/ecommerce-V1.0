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
            $basePath = $this->detectBasePath();
            $url = $basePath . $url;
        }
        
        header("Location: {$url}");
        exit;
    }

    /**
     * Detecta o caminho base da aplicação a partir do REQUEST_URI ou HTTP_REFERER.
     * Usado em redirects e links para manter consistência com a URL atual.
     *
     * Prioridade:
     * 1. BASE_PATH no .env (configuração explícita)
     * 2. REQUEST_URI ou HTTP_REFERER (mesmo padrão da página atual)
     * 3. '' (vazio) - Hostinger com DocumentRoot em public_html usa /admin/... sem /public
     */
    protected function detectBasePath(): string
    {
        // 1. Configuração explícita no .env (ex: BASE_PATH=/public para subpasta)
        $envBase = $_ENV['BASE_PATH'] ?? null;
        if ($envBase !== null && $envBase !== '') {
            return rtrim($envBase, '/');
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $requestUri = strtok($requestUri, '?'); // Remover query string

        if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
            return '/ecommerce-v1.0/public';
        }
        if (strpos($requestUri, '/public') === 0) {
            return '/public';
        }

        // Em POST, REQUEST_URI pode não ter o prefixo (ex: form action="/admin/produtos")
        // Usar HTTP_REFERER para manter o mesmo padrão da página de origem
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '') {
            $refererPath = parse_url($referer, PHP_URL_PATH);
            if ($refererPath && strpos($refererPath, '/ecommerce-v1.0/public') === 0) {
                return '/ecommerce-v1.0/public';
            }
            if ($refererPath && strpos($refererPath, '/public') === 0) {
                return '/public';
            }
        }

        // Hostinger: DocumentRoot em public_html → URLs são /admin/... (sem /public)
        // Desenvolvimento local em subpasta: usar '' para /pontodogolfe/ ou similar
        return '';
    }
}

