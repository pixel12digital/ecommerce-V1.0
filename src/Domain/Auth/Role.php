<?php

namespace App\Domain\Auth;

use App\Core\Database;

class Role
{
    private int $id;
    private string $slug;
    private string $name;
    private ?string $description;
    private string $scope;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->slug = $data['slug'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
        $this->scope = $data['scope'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Busca role por slug
     */
    public static function findBySlug(string $slug): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM roles WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new self($data);
    }

    /**
     * Busca role por ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new self($data);
    }

    /**
     * Retorna todas as permissions deste role
     */
    public function getPermissions(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.* 
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.name
        ");
        $stmt->execute(['role_id' => $this->id]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica se o role tem uma permission específica
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // store_admin tem todas as permissões
        if ($this->slug === 'store_admin') {
            return true;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM role_permissions rp
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id AND p.slug = :permission_slug
        ");
        $stmt->execute([
            'role_id' => $this->id,
            'permission_slug' => $permissionSlug
        ]);
        $result = $stmt->fetch();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Retorna todos os roles com um scope específico
     */
    public static function getByScope(string $scope): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM roles WHERE scope = :scope ORDER BY name");
        $stmt->execute(['scope' => $scope]);
        $results = $stmt->fetchAll();

        return array_map(function ($data) {
            return new self($data);
        }, $results);
    }

    /**
     * Retorna todos os roles
     */
    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM roles ORDER BY scope, name");
        $results = $stmt->fetchAll();

        return array_map(function ($data) {
            return new self($data);
        }, $results);
    }
}

