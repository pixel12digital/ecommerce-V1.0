# Relatório: Sistema de Perfis e Permissões da Loja

## Visão Geral

Este documento descreve a implementação de um sistema de perfis e permissões no estilo WordPress para o painel administrativo da loja (`/admin`). O sistema utiliza o conceito de **roles** (perfis) e **permissions** (permissões/capabilities), permitindo controle granular de acesso às funcionalidades do painel.

**Status:** ✅ Implementação Completa

**Data de Implementação:** 2024

## Resumo Executivo

O sistema de perfis e permissões foi totalmente implementado, incluindo:

- ✅ 4 novas tabelas no banco de dados (roles, permissions, role_permissions, store_user_roles)
- ✅ Models para Role e Permission
- ✅ Service StoreUserService com métodos de verificação de permissões
- ✅ Middleware CheckPermissionMiddleware para proteção de rotas
- ✅ Controllers para gerenciar usuários e perfis
- ✅ Views completas para interface de gerenciamento
- ✅ Integração em todas as rotas administrativas
- ✅ Dashboard e menu lateral filtrados por permissões
- ✅ Seed inicial com roles e permissions configurados

## Arquitetura

### Modelo de Dados

O sistema é baseado em 4 tabelas principais:

1. **`roles`** - Perfis de acesso (ex: Administrador da Loja, Gerente da Loja)
2. **`permissions`** - Permissões individuais (ex: manage_products, view_dashboard)
3. **`role_permissions`** - Tabela pivot que associa permissões aos perfis
4. **`store_user_roles`** - Tabela pivot que associa perfis aos usuários da loja

### Fluxo de Autorização

```
Usuário (store_user) 
  → Possui um Role (via store_user_roles)
    → Role possui Permissions (via role_permissions)
      → Middleware verifica se usuário tem a permission necessária
        → Permite ou bloqueia acesso (403)
```

## Tabelas do Banco de Dados

### 1. Tabela `roles`

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    scope VARCHAR(50) NOT NULL,  -- 'store' ou 'customer'
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_scope (scope),
    INDEX idx_slug (slug)
)
```

**Roles iniciais:**
- `store_admin` - "Administrador da Loja" (scope: store)
- `store_manager` - "Gerente da Loja" (scope: store)
- `customer` - "Cliente" (scope: customer) - Para uso futuro

### 2. Tabela `permissions`

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
)
```

**Permissions iniciais:**
- `view_dashboard` - "Visualizar Dashboard"
- `manage_orders` - "Gerenciar Pedidos"
- `manage_products` - "Gerenciar Produtos"
- `manage_customers` - "Gerenciar Clientes"
- `manage_reviews` - "Gerenciar Avaliações"
- `manage_home_page` - "Gerenciar Home da Loja"
- `manage_theme` - "Gerenciar Tema da Loja"
- `manage_gateways` - "Gerenciar Gateways de Pagamento"
- `manage_newsletter` - "Gerenciar Newsletter"
- `manage_media` - "Gerenciar Biblioteca de Mídia"
- `manage_store_settings` - "Gerenciar Configurações da Loja"
- `manage_store_users` - "Gerenciar Usuários e Perfis"

### 3. Tabela `role_permissions` (Pivot)

```sql
CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
)
```

### 4. Tabela `store_user_roles` (Pivot)

```sql
CREATE TABLE store_user_roles (
    store_user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (store_user_id, role_id),
    FOREIGN KEY (store_user_id) REFERENCES store_users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
)
```

**Nota:** Embora a tabela suporte N:N, na prática cada usuário terá apenas um role ativo.

## Mapeamento Inicial de Permissões

### Role: `store_admin` (Administrador da Loja)
**Tem TODAS as permissões:**
- view_dashboard
- manage_orders
- manage_products
- manage_customers
- manage_reviews
- manage_home_page
- manage_theme
- manage_gateways
- manage_newsletter
- manage_media
- manage_store_settings
- manage_store_users

### Role: `store_manager` (Gerente da Loja)
**Tem as seguintes permissões:**
- view_dashboard
- manage_orders
- manage_products
- manage_customers
- manage_reviews
- manage_home_page
- manage_media
- manage_newsletter

**NÃO tem:**
- manage_theme
- manage_gateways
- manage_store_settings
- manage_store_users

### Role: `customer` (Cliente)
- Por enquanto sem uso no painel admin
- Registrado no banco para futura expansão

## Models

### 1. `App\Domain\Auth\Role`

**Métodos principais:**
- `findBySlug(string $slug): ?Role`
- `getPermissions(): array` - Retorna todas as permissions do role
- `hasPermission(string $permissionSlug): bool`

### 2. `App\Domain\Auth\Permission`

**Métodos principais:**
- `findBySlug(string $slug): ?Permission`
- `getAll(): array` - Retorna todas as permissions

### 3. Helper para StoreUser

**Métodos a serem adicionados (via helper ou service):**
- `getRoleSlug(): ?string` - Retorna o slug do role do usuário
- `hasRole(string $roleSlug): bool` - Verifica se usuário tem o role
- `can(string $permissionSlug): bool` - Verifica se usuário tem a permission

## Middleware

### `App\Http\Middleware\CheckPermissionMiddleware`

**Responsabilidades:**
1. Verificar se usuário está autenticado (via `AuthMiddleware`)
2. Obter o usuário logado da sessão
3. Verificar se o usuário possui a permission necessária
4. Se não tiver, retornar 403 com mensagem amigável
5. Se tiver, permitir acesso

**Uso nas rotas:**
```php
$router->get('/admin/produtos', AdminProductController::class . '@index', [
    AuthMiddleware::class => [false, true],
    CheckPermissionMiddleware::class => 'manage_products'
]);
```

## Rotas Protegidas por Permissões

Todas as rotas abaixo são protegidas por `AuthMiddleware` (autenticação) e `CheckPermissionMiddleware` (autorização):

| Rota | Permission | Controller | Métodos |
|------|-----------|------------|---------|
| `/admin` | `view_dashboard` | `StoreDashboardController@index` | GET |
| `/admin/produtos` | `manage_products` | `AdminProductController@index` | GET |
| `/admin/produtos/{id}` | `manage_products` | `AdminProductController@edit` | GET |
| `/admin/produtos/{id}` | `manage_products` | `AdminProductController@update` | POST |
| `/admin/pedidos` | `manage_orders` | `AdminOrderController@index` | GET |
| `/admin/pedidos/{id}` | `manage_orders` | `AdminOrderController@show` | GET |
| `/admin/pedidos/{id}/status` | `manage_orders` | `AdminOrderController@updateStatus` | POST |
| `/admin/clientes` | `manage_customers` | `AdminCustomerController@index` | GET |
| `/admin/clientes/{id}` | `manage_customers` | `AdminCustomerController@show` | GET |
| `/admin/avaliacoes` | `manage_reviews` | `AdminProductReviewController@index` | GET |
| `/admin/avaliacoes/{id}` | `manage_reviews` | `AdminProductReviewController@show` | GET |
| `/admin/avaliacoes/{id}/aprovar` | `manage_reviews` | `AdminProductReviewController@approve` | POST |
| `/admin/avaliacoes/{id}/rejeitar` | `manage_reviews` | `AdminProductReviewController@reject` | POST |
| `/admin/home` | `manage_home_page` | `HomeConfigController@index` | GET |
| `/admin/home/categorias-pills` | `manage_home_page` | `HomeCategoriesController@index` | GET |
| `/admin/home/categorias-pills` | `manage_home_page` | `HomeCategoriesController@store` | POST |
| `/admin/home/categorias-pills/{id}/editar` | `manage_home_page` | `HomeCategoriesController@edit` | GET |
| `/admin/home/categorias-pills/{id}` | `manage_home_page` | `HomeCategoriesController@update` | POST |
| `/admin/home/categorias-pills/{id}/excluir` | `manage_home_page` | `HomeCategoriesController@destroy` | POST |
| `/admin/home/banners` | `manage_home_page` | `HomeBannersController@index` | GET |
| `/admin/home/banners/novo` | `manage_home_page` | `HomeBannersController@create` | GET |
| `/admin/home/banners/novo` | `manage_home_page` | `HomeBannersController@store` | POST |
| `/admin/home/banners/{id}/editar` | `manage_home_page` | `HomeBannersController@edit` | GET |
| `/admin/home/banners/{id}` | `manage_home_page` | `HomeBannersController@update` | POST |
| `/admin/home/banners/{id}/excluir` | `manage_home_page` | `HomeBannersController@destroy` | POST |
| `/admin/home/secoes-categorias` | `manage_home_page` | `HomeSectionsController@index` | GET |
| `/admin/home/secoes-categorias` | `manage_home_page` | `HomeSectionsController@update` | POST |
| `/admin/tema` | `manage_theme` | `ThemeController@edit` | GET |
| `/admin/tema` | `manage_theme` | `ThemeController@update` | POST |
| `/admin/configuracoes/gateways` | `manage_gateways` | `GatewayConfigController@index` | GET |
| `/admin/configuracoes/gateways` | `manage_gateways` | `GatewayConfigController@store` | POST |
| `/admin/newsletter` | `manage_newsletter` | `NewsletterController@index` | GET |
| `/admin/midias` | `manage_media` | `MediaLibraryController@index` | GET |
| `/admin/midias/listar` | `manage_media` | `MediaLibraryController@listar` | GET |
| `/admin/midias/upload` | `manage_media` | `MediaLibraryController@upload` | POST |
| `/admin/usuarios` | `manage_store_users` | `StoreUsersController@index` | GET |
| `/admin/usuarios/novo` | `manage_store_users` | `StoreUsersController@create` | GET |
| `/admin/usuarios` | `manage_store_users` | `StoreUsersController@store` | POST |
| `/admin/usuarios/{id}/editar` | `manage_store_users` | `StoreUsersController@edit` | GET |
| `/admin/usuarios/{id}` | `manage_store_users` | `StoreUsersController@update` | POST |
| `/admin/usuarios/{id}/excluir` | `manage_store_users` | `StoreUsersController@destroy` | POST |
| `/admin/usuarios/perfis` | `manage_store_users` | `RolesController@index` | GET |
| `/admin/usuarios/perfis/{id}/editar` | `manage_store_users` | `RolesController@edit` | GET |
| `/admin/usuarios/perfis/{id}` | `manage_store_users` | `RolesController@update` | POST |

## Telas de Gerenciamento

### 1. Lista de Perfis (`/admin/usuarios/perfis`)

**Acesso:** Requer `manage_store_users`

**Funcionalidades:**
- Lista todos os roles com `scope = 'store'`
- Colunas: Nome, Slug, Escopo, Ações
- Botão "Editar permissões" para cada perfil

### 2. Editar Permissões do Perfil (`/admin/usuarios/perfis/{id}/editar`)

**Acesso:** Requer `manage_store_users`

**Funcionalidades:**
- Exibe nome do perfil (editável, exceto para `store_admin`)
- Lista todas as permissions com checkbox
- Para `store_admin`: todas marcadas e desabilitadas (com aviso)
- Para outros roles: checkboxes habilitados
- Salvar atualiza `role_permissions` em transação

### 3. Gerenciar Usuários da Loja (`/admin/usuarios`)

**Acesso:** Requer `manage_store_users`

**Funcionalidades:**
- Lista todos os `store_users` do tenant atual
- Ao criar/editar usuário, campo "Perfil de Acesso" (select)
- Opções: "Administrador da Loja" (store_admin), "Gerente da Loja" (store_manager)
- Ao salvar, atualiza `store_user_roles`

## Integração com Dashboard

O dashboard (`/admin`) exibe apenas os cards/links que o usuário tem permissão para acessar:

- Card "Produtos" → verifica `manage_products`
- Card "Pedidos" → verifica `manage_orders`
- Card "Clientes" → verifica `manage_customers`
- Card "Home da Loja" → verifica `manage_home_page`
- Card "Tema da Loja" → verifica `manage_theme`
- Card "Gateways" → verifica `manage_gateways`
- Card "Newsletter" → verifica `manage_newsletter`
- Card "Biblioteca de Mídia" → verifica `manage_media`
- Card "Avaliações" → verifica `manage_reviews`
- Card "Usuários e Perfis" → verifica `manage_store_users`

O menu lateral também é filtrado dinamicamente, mostrando apenas os itens que o usuário pode acessar.

## Migração de Dados Antigos

Se existir coluna `role` na tabela `store_users` com valores antigos:
- `owner` → `store_admin`
- `manager` → `store_manager`
- `staff` → `store_manager` (ou criar role específico)

A coluna `role` antiga pode ser mantida por compatibilidade, mas o sistema passará a usar `store_user_roles`.

## Checklist de Validação

### Cenário 1: Usuário Administrador da Loja

- [ ] Consegue acessar todas as seções do painel
- [ ] Consegue ver e editar perfis e permissões
- [ ] Vê todos os cards no dashboard
- [ ] Não recebe erro 403 em nenhuma rota protegida

### Cenário 2: Usuário Gerente da Loja

- [ ] Consegue acessar: Dashboard, Produtos, Pedidos, Clientes, Home da Loja, Newsletter, Biblioteca de Mídia
- [ ] NÃO consegue acessar: Tema da Loja, Gateways, Usuários/Perfis, Configurações sensíveis
- [ ] Recebe 403 ao tentar acessar `/admin/tema` diretamente
- [ ] Não vê cards bloqueados no dashboard

### Cenário 3: Cliente

- [ ] Não consegue acessar `/admin` (redirecionado para login)
- [ ] Área "Minha conta" (`/minha-conta`) funciona normalmente
- [ ] Não é afetado pelo sistema de permissões do admin

### Cenário 4: Edição de Perfis

- [ ] Administrador consegue editar permissões do Gerente
- [ ] Administrador NÃO consegue desmarcar permissões do próprio perfil (store_admin)
- [ ] Alterações são salvas corretamente
- [ ] Usuários com role atualizado perdem/acessam funcionalidades imediatamente

## Arquivos Criados/Modificados

### Migrations
- `database/migrations/040_create_roles_table.php`
- `database/migrations/041_create_permissions_table.php`
- `database/migrations/042_create_role_permissions_table.php`
- `database/migrations/043_create_store_user_roles_table.php`
- `database/seeds/002_roles_and_permissions_seed.php`

### Models
- `src/Domain/Auth/Role.php`
- `src/Domain/Auth/Permission.php`
- `src/Services/StoreUserService.php` (helper para métodos de permissão)

### Middleware
- `src/Http/Middleware/CheckPermissionMiddleware.php`

### Controllers
- `src/Http/Controllers/Admin/StoreUsersController.php` (novo)
- `src/Http/Controllers/Admin/RolesController.php` (novo)

### Views
- `themes/default/admin/users/index-content.php`
- `themes/default/admin/users/form-content.php`
- `themes/default/admin/users/roles/index-content.php`
- `themes/default/admin/users/roles/edit-content.php`

### Modificações
- `public/index.php` - Adicionar rotas protegidas com CheckPermissionMiddleware e novas rotas de usuários/perfis
- `themes/default/admin/store/dashboard-content.php` - Filtrar cards por permissão
- `themes/default/admin/layouts/store.php` - Filtrar itens do menu lateral por permissão e adicionar link "Usuários e Perfis"
- `src/Core/Router.php` - Suporte para middleware com parâmetro string

## Considerações de Segurança

1. **Role store_admin é imutável:** Não pode ter permissões removidas via UI
2. **Validação no backend:** Sempre validar permissões no controller, não apenas no middleware
3. **Transações:** Operações de atualização de permissões devem ser atômicas
4. **Logs:** Considerar registrar alterações de permissões para auditoria (futuro)

## Próximos Passos (Futuro)

1. Sistema de logs de auditoria para alterações de permissões
2. Permissões mais granulares (ex: `view_products` vs `manage_products`)
3. Permissões customizadas por tenant
4. Sistema de herança de permissões
5. Interface para criar novos roles customizados

