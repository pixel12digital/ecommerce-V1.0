<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Domain\Auth\Role;
use App\Services\StoreUserService;
use App\Tenant\TenantContext;

class StoreUsersController extends Controller
{
    /**
     * Lista todos os usuários da loja
     */
    public function index(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT 
                su.*,
                GROUP_CONCAT(r.slug) as roles
            FROM store_users su
            LEFT JOIN store_user_roles sur ON su.id = sur.store_user_id
            LEFT JOIN roles r ON sur.role_id = r.id
            WHERE su.tenant_id = :tenant_id
            GROUP BY su.id
            ORDER BY su.name
        ");
        $stmt->execute(['tenant_id' => $tenantId]);
        $users = $stmt->fetchAll();

        // Adicionar role slug para cada usuário
        foreach ($users as &$user) {
            $user['role_slug'] = StoreUserService::getRoleSlug($user['id']);
            $user['role_name'] = null;
            if ($user['role_slug']) {
                $role = Role::findBySlug($user['role_slug']);
                $user['role_name'] = $role ? $role->getName() : null;
            }
        }

        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/users/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Usuários da Loja',
            'users' => $users
        ]);
    }

    /**
     * Exibe formulário para criar novo usuário
     */
    public function create(): void
    {
        $roles = Role::getByScope('store');
        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Novo Usuário',
            'user' => null,
            'roles' => $roles
        ]);
    }

    /**
     * Salva novo usuário
     */
    public function store(): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleSlug = $_POST['role'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nome é obrigatório';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail válido é obrigatório';
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        }

        if (empty($roleSlug)) {
            $errors[] = 'Perfil de acesso é obrigatório';
        }

        // Verificar se email já existe para este tenant
        if (!empty($email)) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM store_users 
                WHERE tenant_id = :tenant_id AND email = :email
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'email' => $email]);
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                $errors[] = 'Este e-mail já está em uso';
            }
        }

        if (!empty($errors)) {
            $roles = Role::getByScope('store');
            $tenant = TenantContext::tenant();
            
            $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
                'tenant' => $tenant,
                'pageTitle' => 'Novo Usuário',
                'user' => null,
                'roles' => $roles,
                'errors' => $errors,
                'formData' => $_POST
            ]);
            return;
        }

        try {
            $db->beginTransaction();

            // Criar usuário
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO store_users (tenant_id, name, email, password_hash, role) 
                VALUES (:tenant_id, :name, :email, :password_hash, :role)
            ");
            $stmt->execute([
                'tenant_id' => $tenantId,
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $roleSlug // Manter compatibilidade com coluna antiga
            ]);

            $userId = $db->lastInsertId();

            // Atribuir role
            StoreUserService::assignRole($userId, $roleSlug);

            $db->commit();

            $_SESSION['admin_message'] = 'Usuário criado com sucesso!';
            $_SESSION['admin_message_type'] = 'success';
            $this->redirect('/admin/usuarios');
        } catch (\Exception $e) {
            $db->rollBack();
            $roles = Role::getByScope('store');
            $tenant = TenantContext::tenant();
            
            $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
                'tenant' => $tenant,
                'pageTitle' => 'Novo Usuário',
                'user' => null,
                'roles' => $roles,
                'errors' => ['Erro ao criar usuário: ' . $e->getMessage()],
                'formData' => $_POST
            ]);
        }
    }

    /**
     * Exibe formulário para editar usuário
     */
    public function edit(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM store_users 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }

        // Obter role atual
        $user['role_slug'] = StoreUserService::getRoleSlug($user['id']);

        $roles = Role::getByScope('store');
        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Usuário',
            'user' => $user,
            'roles' => $roles
        ]);
    }

    /**
     * Atualiza usuário
     */
    public function update(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Verificar se usuário existe e pertence ao tenant
        $stmt = $db->prepare("
            SELECT * FROM store_users 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleSlug = $_POST['role'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nome é obrigatório';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail válido é obrigatório';
        }

        // Verificar se email já existe para outro usuário do mesmo tenant
        if (!empty($email)) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM store_users 
                WHERE tenant_id = :tenant_id AND email = :email AND id != :id
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'email' => $email, 'id' => $id]);
            $result = $stmt->fetch();
            if ($result['count'] > 0) {
                $errors[] = 'Este e-mail já está em uso';
            }
        }

        if (!empty($password) && strlen($password) < 6) {
            $errors[] = 'Senha deve ter no mínimo 6 caracteres';
        }

        if (empty($roleSlug)) {
            $errors[] = 'Perfil de acesso é obrigatório';
        }

        if (!empty($errors)) {
            $user['role_slug'] = StoreUserService::getRoleSlug($user['id']);
            $roles = Role::getByScope('store');
            $tenant = TenantContext::tenant();
            
            $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
                'tenant' => $tenant,
                'pageTitle' => 'Editar Usuário',
                'user' => array_merge($user, $_POST),
                'roles' => $roles,
                'errors' => $errors
            ]);
            return;
        }

        try {
            $db->beginTransaction();

            // Atualizar usuário
            $updateData = [
                'id' => $id,
                'tenant_id' => $tenantId,
                'name' => $name,
                'email' => $email,
                'role' => $roleSlug // Manter compatibilidade
            ];

            if (!empty($password)) {
                $updateData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    UPDATE store_users 
                    SET name = :name, email = :email, password_hash = :password_hash, role = :role
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
            } else {
                $stmt = $db->prepare("
                    UPDATE store_users 
                    SET name = :name, email = :email, role = :role
                    WHERE id = :id AND tenant_id = :tenant_id
                ");
            }

            $stmt->execute($updateData);

            // Atualizar role
            StoreUserService::assignRole($id, $roleSlug);

            $db->commit();

            $_SESSION['admin_message'] = 'Usuário atualizado com sucesso!';
            $_SESSION['admin_message_type'] = 'success';
            $this->redirect('/admin/usuarios');
        } catch (\Exception $e) {
            $db->rollBack();
            $user['role_slug'] = StoreUserService::getRoleSlug($user['id']);
            $roles = Role::getByScope('store');
            $tenant = TenantContext::tenant();
            
            $this->viewWithLayout('admin/layouts/store', 'admin/users/form-content', [
                'tenant' => $tenant,
                'pageTitle' => 'Editar Usuário',
                'user' => array_merge($user, $_POST),
                'roles' => $roles,
                'errors' => ['Erro ao atualizar usuário: ' . $e->getMessage()]
            ]);
        }
    }

    /**
     * Exclui usuário
     */
    public function destroy(int $id): void
    {
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Verificar se usuário existe e pertence ao tenant
        $stmt = $db->prepare("
            SELECT * FROM store_users 
            WHERE id = :id AND tenant_id = :tenant_id
        ");
        $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }

        // Não permitir excluir o próprio usuário
        $currentUserId = StoreUserService::getCurrentUserId();
        if ($id == $currentUserId) {
            $_SESSION['admin_message'] = 'Você não pode excluir seu próprio usuário.';
            $_SESSION['admin_message_type'] = 'error';
            $this->redirect('/admin/usuarios');
            return;
        }

        try {
            $db->beginTransaction();

            // Excluir associações de roles (CASCADE já faz isso, mas sendo explícito)
            $stmt = $db->prepare("DELETE FROM store_user_roles WHERE store_user_id = :id");
            $stmt->execute(['id' => $id]);

            // Excluir usuário
            $stmt = $db->prepare("DELETE FROM store_users WHERE id = :id AND tenant_id = :tenant_id");
            $stmt->execute(['id' => $id, 'tenant_id' => $tenantId]);

            $db->commit();

            $_SESSION['admin_message'] = 'Usuário excluído com sucesso!';
            $_SESSION['admin_message_type'] = 'success';
            $this->redirect('/admin/usuarios');
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['admin_message'] = 'Erro ao excluir usuário: ' . $e->getMessage();
            $_SESSION['admin_message_type'] = 'error';
            $this->redirect('/admin/usuarios');
        }
    }
}

