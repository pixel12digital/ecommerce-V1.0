<?php

namespace App\Domain\Auth;

use App\Core\Database;

class Permission
{
    private int $id;
    private string $slug;
    private string $name;
    private ?string $description;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->slug = $data['slug'];
        $this->name = $data['name'];
        $this->description = $data['description'] ?? null;
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

    /**
     * Busca permission por slug
     */
    public static function findBySlug(string $slug): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM permissions WHERE slug = :slug LIMIT 1");
        $stmt->execute(['slug' => $slug]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new self($data);
    }

    /**
     * Busca permission por ID
     */
    public static function findById(int $id): ?self
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM permissions WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if (!$data) {
            return null;
        }

        return new self($data);
    }

    /**
     * Retorna todas as permissions
     */
    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM permissions ORDER BY name");
        $results = $stmt->fetchAll();

        return array_map(function ($data) {
            return new self($data);
        }, $results);
    }
}

