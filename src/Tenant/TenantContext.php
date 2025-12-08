<?php

namespace App\Tenant;

use App\Tenant\TenantRepository;

class TenantContext
{
    private static ?Tenant $tenant = null;

    public static function resolveFromHost(string $host): void
    {
        $repository = new TenantRepository();
        $tenant = $repository->findByDomain($host);

        if (!$tenant) {
            throw new \RuntimeException("Tenant não encontrado para o domínio: {$host}");
        }

        self::$tenant = $tenant;
    }

    public static function setFixedTenant(int $tenantId): void
    {
        $repository = new TenantRepository();
        $tenant = $repository->findById($tenantId);

        if (!$tenant) {
            throw new \RuntimeException("Tenant não encontrado com ID: {$tenantId}");
        }

        if ($tenant->status !== 'active') {
            throw new \RuntimeException("Tenant inativo: {$tenantId}");
        }

        self::$tenant = $tenant;
    }

    public static function tenant(): Tenant
    {
        if (self::$tenant === null) {
            throw new \RuntimeException("Tenant não foi resolvido. Chame resolveFromHost() ou setFixedTenant() primeiro.");
        }

        return self::$tenant;
    }

    public static function id(): int
    {
        return self::tenant()->id;
    }

    public static function clear(): void
    {
        self::$tenant = null;
    }
}



