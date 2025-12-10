<?php

namespace App\Http\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Domain\Auth\Role;
use App\Domain\Auth\Permission;
use App\Tenant\TenantContext;

class RolesController extends Controller
{
    /**
     * Lista todos os perfis (roles) com scope 'store'
     */
    public function index(): void
    {
        $roles = Role::getByScope('store');
        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/users/roles/index-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Perfis de Acesso',
            'roles' => $roles
        ]);
    }

    /**
     * Exibe formulário para editar permissões de um perfil
     */
    public function edit(int $id): void
    {
        $role = Role::findById($id);
        if (!$role) {
            http_response_code(404);
            echo "Perfil não encontrado";
            return;
        }

        // Buscar todas as permissions
        $allPermissions = Permission::all();
        
        // Buscar permissions do role
        $rolePermissions = $role->getPermissions();
        $rolePermissionIds = array_column($rolePermissions, 'id');

        $tenant = TenantContext::tenant();

        $this->viewWithLayout('admin/layouts/store', 'admin/users/roles/edit-content', [
            'tenant' => $tenant,
            'pageTitle' => 'Editar Permissões - ' . $role->getName(),
            'role' => $role,
            'allPermissions' => $allPermissions,
            'rolePermissionIds' => $rolePermissionIds
        ]);
    }

    /**
     * Atualiza permissões de um perfil
     */
    public function update(int $id): void
    {
        $role = Role::findById($id);
        if (!$role) {
            http_response_code(404);
            echo "Perfil não encontrado";
            return;
        }

        // store_admin não pode ter permissões removidas
        if ($role->getSlug() === 'store_admin') {
            $_SESSION['admin_message'] = 'O perfil Administrador da Loja não pode ser modificado.';
            $_SESSION['admin_message_type'] = 'error';
            $this->redirect('/admin/usuarios/perfis');
            return;
        }

        // Obter permissions selecionadas
        $selectedPermissions = $_POST['permissions'] ?? [];
        $selectedPermissionIds = array_map('intval', $selectedPermissions);

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // Remover todas as permissões atuais
            $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = :role_id");
            $stmt->execute(['role_id' => $id]);

            // Adicionar novas permissões
            if (!empty($selectedPermissionIds)) {
                $stmt = $db->prepare("
                    INSERT INTO role_permissions (role_id, permission_id) 
                    VALUES (:role_id, :permission_id)
                ");
                
                foreach ($selectedPermissionIds as $permissionId) {
                    $stmt->execute([
                        'role_id' => $id,
                        'permission_id' => $permissionId
                    ]);
                }
            }

            $db->commit();

            $_SESSION['admin_message'] = 'Permissões atualizadas com sucesso!';
            $_SESSION['admin_message_type'] = 'success';
            $this->redirect('/admin/usuarios/perfis');
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['admin_message'] = 'Erro ao atualizar permissões: ' . $e->getMessage();
            $_SESSION['admin_message_type'] = 'error';
            $this->redirect('/admin/usuarios/perfis/' . $id . '/editar');
        }
    }
}

