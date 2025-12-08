<?php

namespace App\Tenant;

use App\Core\Database;

class TenantRepository
{
    public function findByDomain(string $domain): ?Tenant
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT t.* 
            FROM tenants t
            INNER JOIN tenant_domains td ON t.id = td.tenant_id
            WHERE td.domain = :domain
            AND t.status = 'active'
            LIMIT 1
        ");
        
        $stmt->execute(['domain' => $domain]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapToTenant($row);
    }

    public function findById(int $id): ?Tenant
    {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM tenants WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return $this->mapToTenant($row);
    }

    private function mapToTenant(array $row): Tenant
    {
        return new Tenant(
            id: (int)$row['id'],
            name: $row['name'],
            slug: $row['slug'],
            status: $row['status'],
            plan: $row['plan'],
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null
        );
    }
}



