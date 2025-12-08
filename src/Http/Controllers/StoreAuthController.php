<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class StoreAuthController extends Controller
{
    public function showLogin(): void
    {
        $auth = new AuthService();
        if ($auth->isStoreAuthenticated()) {
            $this->redirect('/admin');
        }

        $this->view('admin/store/login');
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->view('admin/store/login', ['error' => 'Email e senha são obrigatórios']);
            return;
        }

        // Resolver tenant antes de fazer login
        try {
            $config = require __DIR__ . '/../../../config/app.php';
            $mode = $config['mode'] ?? 'single';
            
            if ($mode === 'single') {
                $defaultTenantId = $config['default_tenant_id'] ?? 1;
                \App\Tenant\TenantContext::setFixedTenant($defaultTenantId);
            } else {
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                \App\Tenant\TenantContext::resolveFromHost($host);
            }
        } catch (\Exception $e) {
            $this->view('admin/store/login', ['error' => 'Erro ao resolver tenant: ' . $e->getMessage()]);
            return;
        }

        $auth = new AuthService();
        if ($auth->loginStoreUser($email, $password)) {
            $this->redirect('/admin');
        } else {
            $this->view('admin/store/login', ['error' => 'Credenciais inválidas']);
        }
    }

    public function logout(): void
    {
        $auth = new AuthService();
        $auth->logout();
        $this->redirect('/admin/login');
    }
}

