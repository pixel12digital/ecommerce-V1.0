<?php

namespace App\Tenant;

class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $status,
        public readonly string $plan,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null
    ) {}
}



