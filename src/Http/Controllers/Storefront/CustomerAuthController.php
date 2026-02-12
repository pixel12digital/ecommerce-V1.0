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

        // Capturar redirect da URL (?redirect=/checkout)
        $redirectUrl = trim($_GET['redirect'] ?? '');
        if (!empty($redirectUrl)) {
            $_SESSION['customer_auth_redirect'] = $redirectUrl;
        }
        $finalRedirect = $_SESSION['customer_auth_redirect'] ?? '/minha-conta';
        
        // Se já estiver logado, redirecionar para o destino (checkout ou dashboard)
        if (isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id'])) {
            unset($_SESSION['customer_auth_redirect']);
            $this->redirect($finalRedirect);
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
            'redirectUrl' => $finalRedirect,
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

        if (!$customer) {
            $theme = ThemeConfig::getFullThemeConfig();
            $tenant = TenantContext::tenant();
            $cartTotalItems = CartService::getTotalItems();
            $cartSubtotal = CartService::getSubtotal();
            
            $this->view('storefront/customers/login', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'errors' => ['E-mail ou senha incorretos.'],
                'email' => $email,
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Conta sem senha (criada automaticamente no checkout) — redirecionar para primeiro acesso
        if (empty($customer['password_hash'])) {
            $_SESSION['first_access_message'] = 'Sua conta ainda não possui senha. Informe seu e-mail abaixo para receber o link de criação de senha.';
            $_SESSION['first_access_message_type'] = 'error';
            $this->redirect('/minha-conta/primeiro-acesso');
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

        // Redirecionar: prioridade para POST redirect, depois sessão, depois dashboard
        $redirectUrl = trim($_POST['redirect'] ?? '');
        if (empty($redirectUrl)) {
            $redirectUrl = $_SESSION['customer_auth_redirect'] ?? '/minha-conta';
        }
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

    /**
     * Exibe formulário "Primeiro acesso" - solicita email para enviar link de criação de senha
     */
    public function showFirstAccessForm(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $message = $_SESSION['first_access_message'] ?? null;
        $messageType = $_SESSION['first_access_message_type'] ?? 'error';
        unset($_SESSION['first_access_message'], $_SESSION['first_access_message_type']);

        $theme = ThemeConfig::getFullThemeConfig();
        $tenant = TenantContext::tenant();
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        $this->view('storefront/customers/first-access', [
            'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
            'theme' => $theme,
            'message' => $message,
            'messageType' => $messageType,
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    /**
     * Processa solicitação de primeiro acesso - envia email com link para criar senha
     */
    public function firstAccess(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $email = trim($_POST['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['first_access_message'] = 'Informe um e-mail válido.';
            $_SESSION['first_access_message_type'] = 'error';
            $this->redirect('/minha-conta/primeiro-acesso');
            return;
        }

        // Buscar cliente por email
        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE tenant_id = :tenant_id AND email = :email 
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'email' => $email]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Sempre mostrar mensagem de sucesso (segurança: não revelar se email existe)
        $_SESSION['first_access_message'] = 'Se este e-mail estiver cadastrado, você receberá um link para criar sua senha.';
        $_SESSION['first_access_message_type'] = 'success';

        if ($customer) {
            // Gerar token único
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Salvar token no banco
            $stmt = $db->prepare("
                UPDATE customers SET 
                    password_reset_token = :token, 
                    password_reset_expires = :expires,
                    updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id
            ");
            $stmt->execute([
                'token' => $token,
                'expires' => $expiresAt,
                'id' => $customer['id'],
                'tenant_id' => $tenantId,
            ]);

            // Enviar email com link
            $tenant = TenantContext::tenant();
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? $tenant->slug . '.com.br';
            $link = "{$protocol}://{$host}/minha-conta/criar-senha/{$token}";

            $storeName = $tenant->name ?? 'Loja';
            $subject = "Criar sua senha - {$storeName}";
            $body = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>";
            $body .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>";
            $body .= "<h2 style='color: #2E7D32;'>{$storeName}</h2>";
            $body .= "<p>Olá, <strong>" . htmlspecialchars($customer['name']) . "</strong>!</p>";
            $body .= "<p>Você solicitou a criação de senha para sua conta.</p>";
            $body .= "<p>Clique no botão abaixo para definir sua senha:</p>";
            $body .= "<p style='text-align: center; margin: 30px 0;'>";
            $body .= "<a href='{$link}' style='background: #2E7D32; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block;'>Criar minha senha</a>";
            $body .= "</p>";
            $body .= "<p style='color: #666; font-size: 0.85rem;'>Se você não solicitou isso, ignore este e-mail.</p>";
            $body .= "<p style='color: #666; font-size: 0.85rem;'>Este link expira em 24 horas.</p>";
            $body .= "<hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>";
            $body .= "<p style='color: #999; font-size: 0.8rem;'>Se o botão não funcionar, copie e cole este link no navegador:<br>{$link}</p>";
            $body .= "</div></body></html>";

            \App\Services\EmailService::send($customer['email'], $subject, $body);
        }

        $this->redirect('/minha-conta/primeiro-acesso');
    }

    /**
     * Exibe formulário para definir senha (via token do email)
     */
    public function showSetPasswordForm(string $token): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Validar token
        $stmt = $db->prepare("
            SELECT id, name, email FROM customers 
            WHERE tenant_id = :tenant_id 
            AND password_reset_token = :token 
            AND password_reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'token' => $token]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        $theme = ThemeConfig::getFullThemeConfig();
        $tenant = TenantContext::tenant();
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        if (!$customer) {
            $this->view('storefront/customers/set-password', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'token' => $token,
                'tokenValid' => false,
                'customer' => null,
                'errors' => ['Link inválido ou expirado. Solicite um novo link.'],
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        $this->view('storefront/customers/set-password', [
            'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
            'theme' => $theme,
            'token' => $token,
            'tokenValid' => true,
            'customer' => $customer,
            'errors' => [],
            'cartTotalItems' => $cartTotalItems,
            'cartSubtotal' => $cartSubtotal,
        ]);
    }

    /**
     * Processa definição de senha
     */
    public function setPassword(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validar token
        $stmt = $db->prepare("
            SELECT id, name, email FROM customers 
            WHERE tenant_id = :tenant_id 
            AND password_reset_token = :token 
            AND password_reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->execute(['tenant_id' => $tenantId, 'token' => $token]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        $theme = ThemeConfig::getFullThemeConfig();
        $tenant = TenantContext::tenant();
        $cartTotalItems = CartService::getTotalItems();
        $cartSubtotal = CartService::getSubtotal();

        if (!$customer) {
            $this->view('storefront/customers/set-password', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'token' => $token,
                'tokenValid' => false,
                'customer' => null,
                'errors' => ['Link inválido ou expirado. Solicite um novo link.'],
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        $errors = [];
        if (empty($password)) {
            $errors[] = 'Senha é obrigatória.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'As senhas não coincidem.';
        }

        if (!empty($errors)) {
            $this->view('storefront/customers/set-password', [
                'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
                'theme' => $theme,
                'token' => $token,
                'tokenValid' => true,
                'customer' => $customer,
                'errors' => $errors,
                'cartTotalItems' => $cartTotalItems,
                'cartSubtotal' => $cartSubtotal,
            ]);
            return;
        }

        // Salvar senha e limpar token
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            UPDATE customers SET 
                password_hash = :password_hash,
                password_reset_token = NULL,
                password_reset_expires = NULL,
                updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'password_hash' => $passwordHash,
            'id' => $customer['id'],
            'tenant_id' => $tenantId,
        ]);

        // Login automático
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        $_SESSION['customer_email'] = $customer['email'];

        $_SESSION['customer_auth_message'] = 'Senha criada com sucesso! Você já está logado.';
        $_SESSION['customer_auth_message_type'] = 'success';

        $this->redirect('/minha-conta');
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


