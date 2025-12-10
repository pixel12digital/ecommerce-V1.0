<?php

namespace App\Services;

use App\Core\Database;
use App\Domain\Auth\Role;

class StoreUserService
{
    /**
     * Obtém o role slug do usuário
     */
    public static function getRoleSlug(int $storeUserId): ?string
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT r.slug 
            FROM roles r
            INNER JOIN store_user_roles sur ON r.id = sur.role_id
            WHERE sur.store_user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['user_id' => $storeUserId]);
        $result = $stmt->fetch();

        return $result['slug'] ?? null;
    }

    /**
     * Verifica se o usuário tem um role específico
     */
    public static function hasRole(int $storeUserId, string $roleSlug): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM store_user_roles sur
            INNER JOIN roles r ON sur.role_id = r.id
            WHERE sur.store_user_id = :user_id AND r.slug = :role_slug
        ");
        $stmt->execute([
            'user_id' => $storeUserId,
            'role_slug' => $roleSlug
        ]);
        $result = $stmt->fetch();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Verifica se o usuário tem uma permission específica
     */
    public static function can(int $storeUserId, string $permissionSlug): bool
    {
        // Buscar role do usuário
        $roleSlug = self::getRoleSlug($storeUserId);
        
        if (!$roleSlug) {
            return false;
        }

        // Se for store_admin, tem todas as permissões
        if ($roleSlug === 'store_admin') {
            return true;
        }

        // Buscar role e verificar permission
        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            return false;
        }

        return $role->hasPermission($permissionSlug);
    }

    /**
     * Obtém o role do usuário
     */
    public static function getRole(int $storeUserId): ?Role
    {
        $roleSlug = self::getRoleSlug($storeUserId);
        if (!$roleSlug) {
            return null;
        }

        return Role::findBySlug($roleSlug);
    }

    /**
     * Atribui um role ao usuário (remove roles antigos e adiciona o novo)
     * @param int $storeUserId ID do usuário
     * @param string $roleSlug Slug do role
     * @param \PDO|null $db Conexão do banco (opcional, para usar transação existente)
     * @return bool True em caso de sucesso, false caso contrário
     */
    public static function assignRole(int $storeUserId, string $roleSlug, ?\PDO $db = null): bool
    {
        $role = Role::findBySlug($roleSlug);
        if (!$role) {
            throw new \InvalidArgumentException("Role com slug '{$roleSlug}' não encontrado.");
        }

        if ($db === null) {
            $db = Database::getConnection();
        }
        
        try {
            // Remover roles antigos
            $stmt = $db->prepare("DELETE FROM store_user_roles WHERE store_user_id = :user_id");
            $stmt->execute(['user_id' => $storeUserId]);

            // Adicionar novo role
            $stmt = $db->prepare("
                INSERT INTO store_user_roles (store_user_id, role_id) 
                VALUES (:user_id, :role_id)
            ");
            $stmt->execute([
                'user_id' => $storeUserId,
                'role_id' => $role->getId()
            ]);

            return true;
        } catch (\PDOException $e) {
            throw new \RuntimeException("Erro ao atribuir role ao usuário: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Obtém o usuário logado da sessão
     */
    public static function getCurrentUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['store_user_id'] ?? null;
    }

    /**
     * Verifica se o usuário logado tem uma permission
     */
    public static function currentUserCan(string $permissionSlug): bool
    {
        $userId = self::getCurrentUserId();
        if (!$userId) {
            return false;
        }

        return self::can($userId, $permissionSlug);
    }
}

