<?php

namespace App\Http\Middleware;

use App\Core\Middleware;
use App\Tenant\TenantContext;

class TenantResolverMiddleware extends Middleware
{
    public function handle(): bool
    {
        try {
            $config = require __DIR__ . '/../../../config/app.php';
            $mode = $config['mode'] ?? 'single';

            if ($mode === 'single') {
                $defaultTenantId = $config['default_tenant_id'] ?? 1;
                TenantContext::setFixedTenant($defaultTenantId);
            } else {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                try {
                    TenantContext::resolveFromHost($host);
                } catch (\RuntimeException $e) {
                    http_response_code(503);
                    echo "<h1>Loja Indisponível</h1><p>Esta loja não está disponível no momento.</p><p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            http_response_code(500);
            echo "<h1>Erro ao Resolver Tenant</h1><p>" . htmlspecialchars($e->getMessage()) . "</p>";
            return false;
        }
    }
}

