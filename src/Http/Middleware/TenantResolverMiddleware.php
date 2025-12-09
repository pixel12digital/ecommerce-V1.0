<?php

namespace App\Http\Middleware;

use App\Core\Middleware;
use App\Tenant\TenantContext;

/**
 * TenantResolverMiddleware
 * 
 * Resolve o tenant atual da requisição baseado no modo de operação:
 * 
 * - APP_MODE=single: Usa tenant fixo (DEFAULT_TENANT_ID)
 *   Ideal para instalações independentes (uma loja por servidor)
 *   Não requer cadastro de domínio em tenant_domains
 * 
 * - APP_MODE=multi: Resolve tenant pelo domínio (HTTP_HOST)
 *   Ideal para plataformas SaaS (múltiplas lojas)
 *   Requer cadastro de domínios em tenant_domains
 * 
 * Compatível com ambos os modos e não possui lógica específica por domínio.
 * O comportamento é determinado exclusivamente pela configuração APP_MODE.
 */
class TenantResolverMiddleware extends Middleware
{
    public function handle(): bool
    {
        try {
            $config = require __DIR__ . '/../../../config/app.php';
            $mode = $config['mode'] ?? 'single';

            if ($mode === 'single') {
                // Modo single: usar tenant fixo (instalações independentes)
                $defaultTenantId = $config['default_tenant_id'] ?? 1;
                TenantContext::setFixedTenant($defaultTenantId);
            } else {
                // Modo multi: resolver tenant pelo domínio (plataforma SaaS)
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

