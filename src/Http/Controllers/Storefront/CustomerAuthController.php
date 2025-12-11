<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Services\ThemeConfig;
use App\Services\CartService;

class CustomerAuthController extends Controller
{
    public function showLoginForm(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Se já estiver logado, redirecionar para dashboard
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $this->redirect('/minha-conta');
            return;
        }

        $message = $_SESSION['customer_auth_message'] ?? null;
        $messageType = $_SESSION['customer_auth_message_type'] ?? 'error';
        unset($_SESSION['customer_auth_message'], $_SESSION['customer_auth_message_type']);

        $theme = ThemeConfig::getFullThemeConfig();
        $tenant = TenantContext::tenant();
        
        // Dados do carrinho para o header
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();
        
        $this->view('storefront/customers/login', [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'message' => $message,
            'messageType' => $messageType,
            'redirectUrl' => $_SESSION['customer_auth_redirect'] ?? '/minha-conta',
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    public function login(): void
    {
        session_start();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];

        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }

        if (empty($password)) {
            $errors[] = 'Senha é obrigatória';
        }

        if (!empty($errors)) {
            // Carregar variáveis necessárias para o layout base
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/login', [
                'loja' => [
                    'nome' => $tenant->name,
                    'slug' => $tenant->slug
                ],
                'theme' => $theme,
                'errors' => $errors,
                'email' => $email,
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Buscar cliente por email e tenant_id
        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE tenant_id = :tenant_id 
            AND email = :email 
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$customer || empty($customer['password_hash'])) {
            // Carregar variáveis necessárias para o layout base
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/login', [
                'loja' => [
                    'nome' => $tenant->name,
                    'slug' => $tenant->slug
                ],
                'theme' => $theme,
                'errors' => ['E-mail ou senha incorretos'],
                'email' => $email,
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Verificar senha
        if (!password_verify($password, $customer['password_hash'])) {
            // Carregar variáveis necessárias para o layout base
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/login', [
                'loja' => [
                    'nome' => $tenant->name,
                    'slug' => $tenant->slug
                ],
                'theme' => $theme,
                'errors' => ['E-mail ou senha incorretos'],
                'email' => $email,
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Login bem-sucedido
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        $_SESSION['customer_email'] = $customer['email'];

        // Redirecionar
        $redirectUrl = $_SESSION['customer_auth_redirect'] ?? '/minha-conta';
        unset($_SESSION['customer_auth_redirect']);

        $this->redirect($redirectUrl);
    }

    public function showRegisterForm(): void
    {
        session_start();
        
        // Se já estiver logado, redirecionar para dashboard
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            $this->redirect('/minha-conta');
            return;
        }

        // Carregar variáveis necessárias para o layout base
        $theme = ThemeConfig::getFullThemeConfig();
        $tenant = TenantContext::tenant();
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        $this->view('storefront/customers/register', [
            'loja' => [
                'nome' => $tenant->name,
                'slug' => $tenant->slug
            ],
            'theme' => $theme,
            'errors' => [],
            'formData' => [],
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    public function register(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $document = trim($_POST['document'] ?? '');

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nome é obrigatório';
        }

        if (empty($email)) {
            $errors[] = 'E-mail é obrigatório';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }

        if (empty($password)) {
            $errors[] = 'Senha é obrigatória';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'As senhas não coincidem';
        }

        if (!empty($errors)) {
            // Carregar variáveis necessárias para o layout base
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/register', [
                'loja' => [
                    'nome' => $tenant->name,
                    'slug' => $tenant->slug
                ],
                'theme' => $theme,
                'errors' => $errors,
                'formData' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'document' => $document,
                ],
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Verificar se email já existe para este tenant
        $stmt = $db->prepare("
            SELECT id FROM customers 
            WHERE tenant_id = :tenant_id 
            AND email = :email 
            LIMIT 1
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'email' => $email,
        ]);

        if ($stmt->fetch()) {
            // Carregar variáveis necessárias para o layout base
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/register', [
                'loja' => [
                    'nome' => $tenant->name,
                    'slug' => $tenant->slug
                ],
                'theme' => $theme,
                'errors' => ['Este e-mail já está cadastrado'],
                'formData' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'document' => $document,
                ],
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Criar cliente
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $db->prepare("
            INSERT INTO customers (
                tenant_id, name, email, password_hash, phone, document, created_at, updated_at
            ) VALUES (
                :tenant_id, :name, :email, :password_hash, :phone, :document, NOW(), NOW()
            )
        ");
        $stmt->execute([
            'tenant_id' => $tenantId,
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'phone' => $phone ?: null,
            'document' => $document ?: null,
        ]);

        $customerId = $db->lastInsertId();

        // Login automático após registro
        session_start();
        $_SESSION['customer_id'] = $customerId;
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;

        // Redirecionar para dashboard
        $this->redirect('/minha-conta?registered=1');
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
        session_destroy();

        $this->redirect('/');
    }
}


