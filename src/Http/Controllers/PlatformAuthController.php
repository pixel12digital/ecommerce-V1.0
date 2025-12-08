<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class PlatformAuthController extends Controller
{
    public function showLogin(): void
    {
        $auth = new AuthService();
        if ($auth->isPlatformAuthenticated()) {
            $this->redirect('/admin/platform');
        }

        $this->view('admin/platform/login');
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->view('admin/platform/login', ['error' => 'Email e senha são obrigatórios']);
            return;
        }

        $auth = new AuthService();
        if ($auth->loginPlatformUser($email, $password)) {
            $this->redirect('/admin/platform');
        } else {
            $this->view('admin/platform/login', ['error' => 'Credenciais inválidas']);
        }
    }

    public function logout(): void
    {
        $auth = new AuthService();
        $auth->logout();
        $this->redirect('/admin/platform/login');
    }
}



