<?php

namespace App\Services;

use App\Core\Database;
use App\Tenant\TenantContext;

class AuthService
{
    public function loginPlatformUser(string $email, string $password): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM platform_users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->startSession();
        $_SESSION['platform_user_id'] = $user['id'];
        $_SESSION['platform_user_email'] = $user['email'];
        $_SESSION['platform_user_role'] = $user['role'];

        return true;
    }

    public function loginStoreUser(string $email, string $password): bool
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM store_users 
            WHERE email = :email AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->startSession();
        $_SESSION['store_user_id'] = $user['id'];
        $_SESSION['store_user_email'] = $user['email'];
        $_SESSION['store_user_role'] = $user['role'];
        $_SESSION['store_user_tenant_id'] = $user['tenant_id'];

        return true;
    }

    public function logout(): void
    {
        $this->startSession();
        session_destroy();
    }

    public function isPlatformAuthenticated(): bool
    {
        $this->startSession();
        return isset($_SESSION['platform_user_id']);
    }

    public function isStoreAuthenticated(): bool
    {
        $this->startSession();
        return isset($_SESSION['store_user_id']);
    }

    public function getPlatformUserId(): ?int
    {
        $this->startSession();
        return $_SESSION['platform_user_id'] ?? null;
    }

    public function getStoreUserId(): ?int
    {
        $this->startSession();
        return $_SESSION['store_user_id'] ?? null;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/app.php';
            session_name($config['session_name']);
            session_start();
        }
    }
}



