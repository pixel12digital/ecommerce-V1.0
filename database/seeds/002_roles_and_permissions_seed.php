<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;

$db = Database::getConnection();

try {
    $db->beginTransaction();

    // Inserir Roles
    $roles = [
        [
            'slug' => 'store_admin',
            'name' => 'Administrador da Loja',
            'description' => 'Acesso total ao painel administrativo da loja. Possui todas as permissões.',
            'scope' => 'store'
        ],
        [
            'slug' => 'store_manager',
            'name' => 'Gerente da Loja',
            'description' => 'Gerencia produtos, pedidos, clientes e conteúdo da loja. Não tem acesso a configurações sensíveis.',
            'scope' => 'store'
        ],
        [
            'slug' => 'customer',
            'name' => 'Cliente',
            'description' => 'Cliente da loja. Acesso apenas à área "Minha Conta".',
            'scope' => 'customer'
        ]
    ];

    foreach ($roles as $role) {
        $stmt = $db->prepare("
            INSERT INTO roles (slug, name, description, scope) 
            VALUES (:slug, :name, :description, :scope)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                description = VALUES(description),
                scope = VALUES(scope)
        ");
        $stmt->execute($role);
    }

    // Inserir Permissions
    $permissions = [
        [
            'slug' => 'view_dashboard',
            'name' => 'Visualizar Dashboard',
            'description' => 'Permite visualizar o dashboard do painel administrativo.'
        ],
        [
            'slug' => 'manage_orders',
            'name' => 'Gerenciar Pedidos',
            'description' => 'Permite visualizar, editar status e gerenciar pedidos.'
        ],
        [
            'slug' => 'manage_products',
            'name' => 'Gerenciar Produtos',
            'description' => 'Permite criar, editar, excluir e gerenciar produtos.'
        ],
        [
            'slug' => 'manage_customers',
            'name' => 'Gerenciar Clientes',
            'description' => 'Permite visualizar e gerenciar informações de clientes.'
        ],
        [
            'slug' => 'manage_reviews',
            'name' => 'Gerenciar Avaliações',
            'description' => 'Permite aprovar, rejeitar e gerenciar avaliações de produtos.'
        ],
        [
            'slug' => 'manage_home_page',
            'name' => 'Gerenciar Home da Loja',
            'description' => 'Permite configurar banners, categorias em destaque e seções da home.'
        ],
        [
            'slug' => 'manage_theme',
            'name' => 'Gerenciar Tema da Loja',
            'description' => 'Permite alterar cores, textos, logo e configurações do tema.'
        ],
        [
            'slug' => 'manage_gateways',
            'name' => 'Gerenciar Gateways de Pagamento',
            'description' => 'Permite configurar gateways de pagamento e métodos de pagamento.'
        ],
        [
            'slug' => 'manage_newsletter',
            'name' => 'Gerenciar Newsletter',
            'description' => 'Permite visualizar e gerenciar inscrições de newsletter.'
        ],
        [
            'slug' => 'manage_media',
            'name' => 'Gerenciar Biblioteca de Mídia',
            'description' => 'Permite fazer upload, organizar e gerenciar arquivos de mídia.'
        ],
        [
            'slug' => 'manage_store_settings',
            'name' => 'Gerenciar Configurações da Loja',
            'description' => 'Permite alterar configurações gerais e sensíveis da loja.'
        ],
        [
            'slug' => 'manage_store_users',
            'name' => 'Gerenciar Usuários e Perfis',
            'description' => 'Permite criar, editar usuários e gerenciar perfis de acesso.'
        ]
    ];

    foreach ($permissions as $permission) {
        $stmt = $db->prepare("
            INSERT INTO permissions (slug, name, description) 
            VALUES (:slug, :name, :description)
            ON DUPLICATE KEY UPDATE 
                name = VALUES(name),
                description = VALUES(description)
        ");
        $stmt->execute($permission);
    }

    // Buscar IDs dos roles e permissions
    $roleIds = [];
    $permissionIds = [];

    foreach (['store_admin', 'store_manager', 'customer'] as $roleSlug) {
        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = :slug");
        $stmt->execute(['slug' => $roleSlug]);
        $role = $stmt->fetch();
        if ($role) {
            $roleIds[$roleSlug] = $role['id'];
        }
    }

    foreach ($permissions as $perm) {
        $stmt = $db->prepare("SELECT id FROM permissions WHERE slug = :slug");
        $stmt->execute(['slug' => $perm['slug']]);
        $permission = $stmt->fetch();
        if ($permission) {
            $permissionIds[$perm['slug']] = $permission['id'];
        }
    }

    // Mapear permissões para store_admin (TODAS)
    if (isset($roleIds['store_admin'])) {
        foreach ($permissionIds as $permId) {
            $stmt = $db->prepare("
                INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                VALUES (:role_id, :permission_id)
            ");
            $stmt->execute([
                'role_id' => $roleIds['store_admin'],
                'permission_id' => $permId
            ]);
        }
    }

    // Mapear permissões para store_manager
    if (isset($roleIds['store_manager'])) {
        $managerPermissions = [
            'view_dashboard',
            'manage_orders',
            'manage_products',
            'manage_customers',
            'manage_reviews',
            'manage_home_page',
            'manage_media',
            'manage_newsletter'
        ];

        foreach ($managerPermissions as $permSlug) {
            if (isset($permissionIds[$permSlug])) {
                $stmt = $db->prepare("
                    INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                    VALUES (:role_id, :permission_id)
                ");
                $stmt->execute([
                    'role_id' => $roleIds['store_manager'],
                    'permission_id' => $permissionIds[$permSlug]
                ]);
            }
        }
    }

    // Migrar roles antigos de store_users para store_user_roles
    // Se existir coluna 'role' na tabela store_users
    $stmt = $db->query("SHOW COLUMNS FROM store_users LIKE 'role'");
    if ($stmt->rowCount() > 0) {
        // Buscar todos os store_users com role antigo
        $stmt = $db->query("SELECT id, role FROM store_users WHERE role IS NOT NULL");
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $oldRole = $user['role'];
            $newRoleSlug = null;

            // Mapear roles antigos para novos
            if ($oldRole === 'owner') {
                $newRoleSlug = 'store_admin';
            } elseif (in_array($oldRole, ['manager', 'staff'])) {
                $newRoleSlug = 'store_manager';
            }

            if ($newRoleSlug && isset($roleIds[$newRoleSlug])) {
                // Verificar se já existe associação
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) as count 
                    FROM store_user_roles 
                    WHERE store_user_id = :user_id
                ");
                $checkStmt->execute(['user_id' => $user['id']]);
                $exists = $checkStmt->fetch();

                if ($exists['count'] == 0) {
                    // Criar associação
                    $insertStmt = $db->prepare("
                        INSERT INTO store_user_roles (store_user_id, role_id) 
                        VALUES (:user_id, :role_id)
                    ");
                    $insertStmt->execute([
                        'user_id' => $user['id'],
                        'role_id' => $roleIds[$newRoleSlug]
                    ]);
                }
            }
        }
    }

    $db->commit();
    echo "Roles e permissions criados com sucesso!\n";
} catch (\Exception $e) {
    $db->rollBack();
    echo "Erro ao criar roles e permissions: " . $e->getMessage() . "\n";
    throw $e;
}

