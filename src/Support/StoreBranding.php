<?php

namespace App\Support;

use App\Services\ThemeConfig;
use App\Tenant\TenantContext;

class StoreBranding
{
    /**
     * Obtém informações de branding da loja (logo + nome)
     * 
     * @return array Array com 'logo_url' e 'store_name'
     */
    public static function getBranding(): array
    {
        $tenant = TenantContext::tenant();
        $tenantId = $tenant->id ?? null;

        // Obter logo da loja (mesma chave usada em /admin/tema e sidebar admin)
        $logoUrl = ThemeConfig::get('logo_url', '');

        $storeName = $tenant->name ?? 'Loja';

        return [
            'logo_url'   => !empty($logoUrl) ? $logoUrl : null,
            'store_name' => $storeName,
        ];
    }
}

