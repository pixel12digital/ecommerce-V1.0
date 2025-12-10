# Relatório: Usuários e Perfis de Acesso

## Visão Geral

Este sistema e-commerce multi-tenant possui três tipos principais de usuários, cada um com seu próprio sistema de autenticação e área de acesso:

1. **Clientes (Customers)** - Usuários finais que compram na loja
2. **Administradores da Loja (Store Users)** - Gerentes e funcionários que administram uma loja específica
3. **Administradores da Plataforma (Platform Users)** - Super administradores que gerenciam a plataforma e todos os tenants

O sistema utiliza autenticação baseada em sessões PHP nativas, sem frameworks de autenticação externos. Cada tipo de usuário possui:
- Tabela própria no banco de dados
- Controller de autenticação específico
- Middleware de proteção de rotas
- Área de acesso isolada (frontend para clientes, `/admin` para loja, `/admin/platform` para plataforma)

---

## Tipos de Usuários

### 1. Cliente (Customer)

**Nome do tipo:** Cliente / Comprador

**Model principal:** Não há model Eloquent/ORM. Os dados são acessados diretamente via PDO na tabela `customers`.

**Tabela do banco:** `customers`

**Estrutura da tabela:**
```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NULL,  -- Pode ser NULL (compra sem cadastro)
    document VARCHAR(20) NULL,        -- CPF/CNPJ
    phone VARCHAR(20) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_email (email)
)
```

**Campos importantes:**
- `id`: Identificador único do cliente
- `tenant_id`: ID do tenant (loja) ao qual o cliente pertence
- `name`: Nome completo do cliente
- `email`: E-mail (único por tenant, não global)
- `password_hash`: Hash da senha (pode ser NULL para compras sem cadastro)
- `document`: CPF ou CNPJ (opcional)
- `phone`: Telefone (opcional)

**Guard / Provider usado:**
- Não utiliza guard Laravel tradicional
- Autenticação via sessão PHP: `$_SESSION['customer_id']`
- Middleware: `CustomerAuthMiddleware`

**Rotas de autenticação:**
- **Login (GET):** `/minha-conta/login` → `CustomerAuthController@showLoginForm`
- **Login (POST):** `/minha-conta/login` → `CustomerAuthController@login`
- **Cadastro (GET):** `/minha-conta/registrar` → `CustomerAuthController@showRegisterForm`
- **Cadastro (POST):** `/minha-conta/registrar` → `CustomerAuthController@register`
- **Logout (GET):** `/minha-conta/logout` → `CustomerAuthController@logout`

**Recuperação de senha:**
- Não implementada no momento

**Áreas de acesso no sistema:**
- **Dashboard:** `/minha-conta` → `CustomerController@dashboard`
- **Pedidos:** `/minha-conta/pedidos` → `CustomerController@orders`
- **Detalhes do Pedido:** `/minha-conta/pedidos/{codigo}` → `CustomerController@orderShow`
- **Endereços:** `/minha-conta/enderecos` → `CustomerController@addresses`
- **Perfil:** `/minha-conta/perfil` → `CustomerController@profile`

**Controllers principais:**
- `App\Http\Controllers\Storefront\CustomerAuthController` - Autenticação
- `App\Http\Controllers\Storefront\CustomerController` - Área do cliente

**Permissões típicas:**
- ✅ Visualizar seus próprios pedidos
- ✅ Editar seu próprio perfil (nome, email, telefone, documento)
- ✅ Gerenciar seus próprios endereços de entrega
- ✅ Adicionar produtos ao carrinho
- ✅ Finalizar compras
- ✅ Avaliar produtos comprados
- ❌ Não acessa painel administrativo
- ❌ Não gerencia produtos, pedidos de outros clientes, configurações da loja

**Sessão:**
Após login bem-sucedido, são criadas as seguintes variáveis de sessão:
- `$_SESSION['customer_id']` - ID do cliente
- `$_SESSION['customer_name']` - Nome do cliente
- `$_SESSION['customer_email']` - E-mail do cliente

**Observações importantes:**
- Cliente pode fazer compra sem cadastro (password_hash pode ser NULL)
- Email é único por tenant (não global), permitindo que o mesmo email exista em diferentes lojas
- Todas as queries incluem filtro por `tenant_id` para garantir isolamento multi-tenant

---

### 2. Administrador da Loja (Store User)

**Nome do tipo:** Admin da Loja / Gerente da Loja / Store Admin

**Model principal:** Não há model Eloquent/ORM. Os dados são acessados diretamente via PDO na tabela `store_users`.

**Tabela do banco:** `store_users`

**Estrutura da tabela:**
```sql
CREATE TABLE store_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_email (tenant_id, email),
    INDEX idx_tenant_id (tenant_id)
)
```

**Campos importantes:**
- `id`: Identificador único do usuário admin
- `tenant_id`: ID do tenant (loja) ao qual o admin pertence (obrigatório)
- `name`: Nome completo do administrador
- `email`: E-mail (único por tenant)
- `password_hash`: Hash da senha (obrigatório)
- `role`: Papel do usuário (`owner`, `manager`, `staff`)

**Roles disponíveis:**
- `owner`: Proprietário da loja (acesso total)
- `manager`: Gerente (acesso amplo, pode gerenciar produtos, pedidos, etc.)
- `staff`: Funcionário (acesso limitado, padrão)

**Guard / Provider usado:**
- Não utiliza guard Laravel tradicional
- Autenticação via sessão PHP: `$_SESSION['store_user_id']`
- Middleware: `AuthMiddleware` com parâmetro `requireStore = true`

**Rotas de autenticação:**
- **Login (GET):** `/admin/login` → `StoreAuthController@showLogin`
- **Login (POST):** `/admin/login` → `StoreAuthController@login`
- **Logout (GET):** `/admin/logout` → `StoreAuthController@logout`

**Recuperação de senha:**
- Não implementada no momento

**Áreas de acesso no sistema:**
Todas as rotas abaixo são protegidas por `AuthMiddleware` com `[false, true]` (requireStore = true):

- **Dashboard:** `/admin` → `StoreDashboardController@index`
- **Produtos:**
  - Listagem: `/admin/produtos` → `AdminProductController@index`
  - Criar: `/admin/produtos/novo` → `AdminProductController@create`
  - Editar: `/admin/produtos/{id}` → `AdminProductController@edit`
  - Atualizar: `POST /admin/produtos/{id}` → `AdminProductController@update`
  - Excluir: `POST /admin/produtos/{id}/excluir` → `AdminProductController@destroy`
- **Pedidos:**
  - Listagem: `/admin/pedidos` → `AdminOrderController@index`
  - Detalhes: `/admin/pedidos/{id}` → `AdminOrderController@show`
  - Atualizar Status: `POST /admin/pedidos/{id}/status` → `AdminOrderController@updateStatus`
- **Clientes:**
  - Listagem: `/admin/clientes` → `AdminCustomerController@index`
  - Detalhes: `/admin/clientes/{id}` → `AdminCustomerController@show`
- **Avaliações:**
  - Listagem: `/admin/avaliacoes` → `AdminProductReviewController@index`
  - Detalhes: `/admin/avaliacoes/{id}` → `AdminProductReviewController@show`
  - Aprovar: `POST /admin/avaliacoes/{id}/aprovar` → `AdminProductReviewController@approve`
  - Rejeitar: `POST /admin/avaliacoes/{id}/rejeitar` → `AdminProductReviewController@reject`
- **Tema da Loja:**
  - Editar: `/admin/tema` → `ThemeController@edit`
  - Atualizar: `POST /admin/tema` → `ThemeController@update`
- **Home da Loja:**
  - Configurações: `/admin/home` → `HomeConfigController@index`
  - Categorias em Destaque: `/admin/home/categorias-pills` → `HomeCategoriesController@index`
  - Banners: `/admin/home/banners` → `HomeBannersController@index`
- **Biblioteca de Mídia:**
  - Listagem: `/admin/midias` → `MediaLibraryController@index`
  - Upload: `POST /admin/midias/upload` → `MediaLibraryController@upload`
  - Listar (AJAX): `/admin/midias/listar` → `MediaLibraryController@listar`
- **Newsletter:**
  - Listagem: `/admin/newsletter` → `NewsletterController@index`
- **Configurações:**
  - Gateways: `/admin/configuracoes/gateways` → `GatewayConfigController@index`
  - Salvar Gateways: `POST /admin/configuracoes/gateways` → `GatewayConfigController@store`
- **Atualizações do Sistema:**
  - Dashboard: `/admin/system/updates` → `SystemUpdatesController@index`
  - Executar Migrations: `POST /admin/system/updates/run` → `SystemUpdatesController@runMigrations`

**Controllers principais:**
- `App\Http\Controllers\StoreAuthController` - Autenticação
- `App\Http\Controllers\StoreDashboardController` - Dashboard
- `App\Http\Controllers\Admin\*` - Todos os controllers da área admin

**Permissões típicas:**
- ✅ Gerenciar produtos (criar, editar, excluir, ativar/desativar)
- ✅ Gerenciar pedidos (visualizar, atualizar status)
- ✅ Visualizar e gerenciar clientes
- ✅ Aprovar/rejeitar avaliações de produtos
- ✅ Configurar tema da loja (cores, textos, logo, páginas institucionais)
- ✅ Gerenciar banners e categorias em destaque da home
- ✅ Gerenciar biblioteca de mídia (upload, organização)
- ✅ Configurar gateways de pagamento
- ✅ Visualizar inscrições de newsletter
- ✅ Executar atualizações do sistema (migrations)
- ❌ Não gerencia outros tenants (apenas o próprio)
- ❌ Não acessa painel da plataforma (`/admin/platform`)

**Sessão:**
Após login bem-sucedido, são criadas as seguintes variáveis de sessão:
- `$_SESSION['store_user_id']` - ID do usuário admin
- `$_SESSION['store_user_email']` - E-mail do usuário
- `$_SESSION['store_user_role']` - Papel do usuário (owner, manager, staff)
- `$_SESSION['store_user_tenant_id']` - ID do tenant ao qual pertence

**Observações importantes:**
- Email é único por tenant (não global)
- Login requer que o tenant seja resolvido primeiro (via `TenantContext`)
- Em modo single-tenant, o tenant é fixo (ID 1 por padrão)
- Em modo multi-tenant, o tenant é resolvido pelo domínio (`HTTP_HOST`)
- O campo `role` existe na tabela, mas atualmente não há verificação de permissões baseada em role no código (todos os admins têm acesso total)

---

### 3. Administrador da Plataforma (Platform User)

**Nome do tipo:** Super Admin / Platform Admin / Master Admin

**Model principal:** Não há model Eloquent/ORM. Os dados são acessados diretamente via PDO na tabela `platform_users`.

**Tabela do banco:** `platform_users`

**Estrutura da tabela:**
```sql
CREATE TABLE platform_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'superadmin',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
)
```

**Campos importantes:**
- `id`: Identificador único do usuário platform
- `name`: Nome completo do administrador
- `email`: E-mail (único globalmente)
- `password_hash`: Hash da senha (obrigatório)
- `role`: Papel do usuário (padrão: `superadmin`)

**Roles disponíveis:**
- `superadmin`: Super administrador (acesso total à plataforma)

**Guard / Provider usado:**
- Não utiliza guard Laravel tradicional
- Autenticação via sessão PHP: `$_SESSION['platform_user_id']`
- Middleware: `AuthMiddleware` com parâmetro `requirePlatform = true`

**Rotas de autenticação:**
- **Login (GET):** `/admin/platform/login` → `PlatformAuthController@showLogin`
- **Login (POST):** `/admin/platform/login` → `PlatformAuthController@login`
- **Logout (GET):** `/admin/platform/logout` → `PlatformAuthController@logout`

**Recuperação de senha:**
- Não implementada no momento

**Áreas de acesso no sistema:**
Todas as rotas abaixo são protegidas por `AuthMiddleware` com `[true, false]` (requirePlatform = true):

- **Dashboard:** `/admin/platform` → `PlatformDashboardController@index`
- **Gerenciar Tenants:**
  - Editar Tenant: `/admin/platform/tenants/{id}/edit` → `PlatformDashboardController@editTenant`
  - Atualizar Tenant: `POST /admin/platform/tenants/{id}/edit` → `PlatformDashboardController@editTenant`

**Controllers principais:**
- `App\Http\Controllers\PlatformAuthController` - Autenticação
- `App\Http\Controllers\PlatformDashboardController` - Dashboard e gerenciamento de tenants

**Permissões típicas:**
- ✅ Visualizar lista de todos os tenants
- ✅ Editar informações de tenants (nome, slug, status, plano)
- ✅ Gerenciar configurações globais da plataforma
- ❌ Não acessa painel individual de cada loja (`/admin`)
- ❌ Não gerencia produtos, pedidos ou clientes de lojas específicas

**Sessão:**
Após login bem-sucedido, são criadas as seguintes variáveis de sessão:
- `$_SESSION['platform_user_id']` - ID do usuário platform
- `$_SESSION['platform_user_email']` - E-mail do usuário
- `$_SESSION['platform_user_role']` - Papel do usuário (superadmin)

**Observações importantes:**
- Email é único globalmente (não por tenant)
- Não possui `tenant_id` (é global à plataforma)
- Usado principalmente no modo multi-tenant para gerenciar todas as lojas
- No modo single-tenant, este painel pode não ser utilizado

---

## Mapa Técnico por Camadas

### Models e Relacionamentos

**Nota:** Este projeto não utiliza ORM (Eloquent/Doctrine). Todas as operações de banco são feitas diretamente via PDO.

**Relacionamentos conceituais:**

1. **Tenant ↔ Store Users (1:N)**
   - Um tenant pode ter múltiplos `store_users`
   - `store_users.tenant_id` → `tenants.id` (FOREIGN KEY com CASCADE)

2. **Tenant ↔ Customers (1:N)**
   - Um tenant pode ter múltiplos `customers`
   - `customers.tenant_id` → `tenants.id` (FOREIGN KEY com CASCADE)

3. **Customer ↔ Orders (1:N)**
   - Um customer pode ter múltiplos pedidos
   - `orders.customer_id` → `customers.id` (relacionamento implícito)

4. **Platform Users**
   - Não possui relacionamento com tenants (é global)

### Guards e Middleware

| Guard/Verificação | Área Protegida | Middleware | Variável de Sessão |
|-------------------|----------------|------------|-------------------|
| `CustomerAuthMiddleware` | `/minha-conta/*` | `CustomerAuthMiddleware` | `$_SESSION['customer_id']` |
| `AuthMiddleware` (requireStore) | `/admin/*` (exceto `/admin/login`) | `AuthMiddleware` com `[false, true]` | `$_SESSION['store_user_id']` |
| `AuthMiddleware` (requirePlatform) | `/admin/platform/*` (exceto `/admin/platform/login`) | `AuthMiddleware` com `[true, false]` | `$_SESSION['platform_user_id']` |

**Detalhamento dos Middlewares:**

1. **CustomerAuthMiddleware** (`src/Http/Middleware/CustomerAuthMiddleware.php`)
   - Verifica se `$_SESSION['customer_id']` existe
   - Se não autenticado, redireciona para `/minha-conta/login`
   - Armazena URL original em `$_SESSION['customer_auth_redirect']` para redirecionamento após login

2. **AuthMiddleware** (`src/Http/Middleware/AuthMiddleware.php`)
   - Aceita dois parâmetros booleanos: `$requirePlatform` e `$requireStore`
   - Se `$requirePlatform = true`: verifica `$_SESSION['platform_user_id']`
   - Se `$requireStore = true`: verifica `$_SESSION['store_user_id']`
   - Redireciona para `/admin/platform/login` ou `/admin/login` conforme necessário

### Tabelas do Banco Relacionadas a Usuários

| Tabela | Descrição | Campos de Usuário | Relacionamento com Tenant |
|--------|-----------|-------------------|---------------------------|
| `customers` | Clientes/compradores da loja | `id`, `name`, `email`, `password_hash`, `document`, `phone` | `tenant_id` (FK, obrigatório) |
| `store_users` | Administradores de cada loja | `id`, `tenant_id`, `name`, `email`, `password_hash`, `role` | `tenant_id` (FK, obrigatório) |
| `platform_users` | Super administradores da plataforma | `id`, `name`, `email`, `password_hash`, `role` | Nenhum (global) |
| `customer_addresses` | Endereços de entrega dos clientes | `id`, `customer_id`, `tenant_id`, `name`, `street`, `city`, `state`, `zipcode` | `tenant_id` (FK) + `customer_id` (FK) |

### Serviço de Autenticação

**Classe:** `App\Services\AuthService`

**Métodos principais:**

1. `loginPlatformUser(string $email, string $password): bool`
   - Autentica usuário da plataforma
   - Busca em `platform_users` por email
   - Verifica senha com `password_verify()`
   - Cria sessão com `platform_user_id`, `platform_user_email`, `platform_user_role`

2. `loginStoreUser(string $email, string $password): bool`
   - Autentica usuário da loja
   - Busca em `store_users` por email E `tenant_id` (resolvido via `TenantContext`)
   - Verifica senha com `password_verify()`
   - Cria sessão com `store_user_id`, `store_user_email`, `store_user_role`, `store_user_tenant_id`

3. `logout(): void`
   - Destrói a sessão completamente (`session_destroy()`)
   - Usado tanto para platform quanto store users

4. `isPlatformAuthenticated(): bool`
   - Verifica se há `platform_user_id` na sessão

5. `isStoreAuthenticated(): bool`
   - Verifica se há `store_user_id` na sessão

6. `getPlatformUserId(): ?int`
   - Retorna o ID do usuário platform logado

7. `getStoreUserId(): ?int`
   - Retorna o ID do usuário store logado

**Observação:** Não há método específico para autenticação de clientes. O `CustomerAuthController` faz a autenticação diretamente via PDO.

### Exibição no Painel

**Admin da Loja (`/admin`):**
- O nome da loja exibido na sidebar vem de:
  1. `ThemeConfig::get('admin_store_name')` (se configurado em Tema da Loja)
  2. `$tenant->name` (fallback)
  3. `'Loja'` (fallback final)
- O título da aba do navegador vem de:
  1. `$pageTitle` (se passado pelo controller)
  2. `ThemeConfig::get('admin_title_base')` (se configurado)
  3. `'Store Admin'` (fallback)
- Não há exibição do nome do usuário logado no layout atual

**Platform Admin (`/admin/platform`):**
- Layout simples, sem sidebar complexa
- Não há exibição do nome do usuário logado no layout atual

**Cliente (`/minha-conta`):**
- Nome do cliente vem de `$_SESSION['customer_name']`
- Exibido no header da área do cliente

---

## Considerações para Futuras Implementações

### Criar um Novo Tipo de Usuário

**Passos necessários:**

1. **Criar migration para nova tabela:**
   ```php
   // database/migrations/XXX_create_novo_tipo_users_table.php
   CREATE TABLE novo_tipo_users (
       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       tenant_id BIGINT UNSIGNED NULL,  // Se necessário
       name VARCHAR(255) NOT NULL,
       email VARCHAR(255) NOT NULL,
       password_hash VARCHAR(255) NOT NULL,
       -- outros campos específicos
       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
       updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   )
   ```

2. **Criar controller de autenticação:**
   - Exemplo: `src/Http/Controllers/NovoTipoAuthController.php`
   - Implementar métodos: `showLogin()`, `login()`, `logout()`

3. **Adicionar métodos no AuthService:**
   - `loginNovoTipoUser()`
   - `isNovoTipoAuthenticated()`
   - `getNovoTipoUserId()`

4. **Criar middleware (se necessário):**
   - Exemplo: `src/Http/Middleware/NovoTipoAuthMiddleware.php`
   - Ou estender `AuthMiddleware` para suportar novo tipo

5. **Registrar rotas em `public/index.php`:**
   ```php
   $router->get('/novo-tipo/login', NovoTipoAuthController::class . '@showLogin');
   $router->post('/novo-tipo/login', NovoTipoAuthController::class . '@login');
   $router->get('/novo-tipo/logout', NovoTipoAuthController::class . '@logout', [
       NovoTipoAuthMiddleware::class
   ]);
   ```

6. **Criar views de login:**
   - `themes/default/admin/novo-tipo/login.php`

### Adicionar Novas Permissões

**Atualmente:**
- O campo `role` existe em `store_users` e `platform_users`, mas não há verificação de permissões baseada em role
- Todos os admins da loja têm acesso total, independente do role

**Para implementar verificação de permissões:**

1. **Criar sistema de permissões:**
   - Opção A: Tabela `permissions` e `user_permissions` (mais flexível)
   - Opção B: Verificação manual baseada em `role` (mais simples)

2. **Criar Policy ou Service de permissões:**
   ```php
   // src/Services/PermissionService.php
   class PermissionService {
       public function can(string $action, ?int $userId = null): bool {
           // Lógica de verificação
       }
   }
   ```

3. **Adicionar verificação em controllers:**
   ```php
   public function destroy(): void {
       $permission = new PermissionService();
       if (!$permission->can('delete_product', $_SESSION['store_user_id'])) {
           http_response_code(403);
           exit('Acesso negado');
       }
       // ...
   }
   ```

4. **Ou criar middleware de permissões:**
   ```php
   // src/Http/Middleware/PermissionMiddleware.php
   class PermissionMiddleware extends Middleware {
       private string $permission;
       
       public function __construct(string $permission) {
           $this->permission = $permission;
       }
       
       public function handle(): bool {
           // Verificar permissão
       }
   }
   ```

### Unificar ou Separar Perfis

**Situação atual:**
- Três sistemas de autenticação completamente separados
- Sessões independentes
- Sem compartilhamento de código entre autenticações

**Para unificar (exemplo: usar um único guard Laravel):**

1. **Criar configuração de auth:**
   ```php
   // config/auth.php
   'guards' => [
       'customer' => [...],
       'store' => [...],
       'platform' => [...],
   ]
   ```

2. **Criar models Eloquent:**
   - `App\Models\Customer`
   - `App\Models\StoreUser`
   - `App\Models\PlatformUser`

3. **Refatorar AuthService para usar guards Laravel**

**Para separar ainda mais:**
- Criar namespaces separados para cada tipo de usuário
- Separar completamente as rotas em arquivos diferentes
- Criar middlewares específicos para cada tipo

### Lugar Correto para Plugar Novas Regras

**Configuração de autenticação:**
- `src/Services/AuthService.php` - Lógica central de autenticação
- `config/app.php` - Configurações gerais (session_name, etc.)

**Middleware:**
- `src/Http/Middleware/` - Criar novos middlewares aqui
- `public/index.php` - Registrar middlewares nas rotas

**Validação de permissões:**
- Criar `src/Services/PermissionService.php` (recomendado)
- Ou adicionar métodos em `AuthService`
- Ou criar Policies em `src/Policies/` (se seguir padrão Laravel)

**Controllers:**
- `src/Http/Controllers/` - Organizar por namespace (Admin, Storefront, Platform)

**Rotas:**
- `public/index.php` - Arquivo central de rotas
- Considerar separar em arquivos menores se crescer muito

---

## Resumo Executivo

### Tipos de Usuários

| Tipo | Tabela | Autenticação | Área de Acesso | Multi-tenant |
|------|--------|--------------|----------------|-------------|
| Cliente | `customers` | Sessão (`customer_id`) | `/minha-conta/*` | Sim (tenant_id) |
| Admin Loja | `store_users` | Sessão (`store_user_id`) | `/admin/*` | Sim (tenant_id) |
| Admin Plataforma | `platform_users` | Sessão (`platform_user_id`) | `/admin/platform/*` | Não (global) |

### Credenciais Padrão (Seed)

**Platform Admin:**
- Email: `admin@platform.local`
- Senha: `admin123`

**Store Admin (Tenant ID 1):**
- Email: `contato@pixel12digital.com.br`
- Senha: `admin123`

**Cliente:**
- Não há cliente padrão criado no seed

### Pontos de Atenção

1. **Não há verificação de roles/permissões:** Todos os admins da loja têm acesso total, independente do campo `role`
2. **Autenticação manual:** Não usa guards Laravel, tudo é feito via sessões PHP nativas
3. **Sem recuperação de senha:** Nenhum tipo de usuário possui fluxo de recuperação de senha
4. **Sem confirmação de email:** Clientes não precisam confirmar email ao se cadastrar
5. **Sessões compartilhadas:** Todas as autenticações usam a mesma sessão PHP (diferentes chaves, mas mesma sessão)

---

**Documento gerado em:** 2024  
**Versão do sistema:** 1.0  
**Última atualização:** Após implementação da seção "Informações da Loja" no Tema

