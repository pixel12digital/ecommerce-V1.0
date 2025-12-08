# Arquitetura E-commerce Multi-tenant

## Objetivo do Projeto

Este projeto é um e-commerce profissional desenvolvido em PHP 8.x com arquitetura MVC simples, projetado para funcionar tanto em modo **multi-tenant** (SaaS) quanto em modo **single-tenant** (instalação isolada), usando um único código-base.

## Modos de Operação

### APP_MODE=multi (Modo Multi-tenant)

No modo **multi**, o sistema suporta múltiplas lojas (tenants) em uma única instalação e banco de dados. Cada loja é identificada pelo domínio/subdomínio através da qual é acessada.

**Características:**
- 1 código, 1 banco de dados, várias lojas
- Cada tenant é resolvido automaticamente pelo domínio
- Exemplos de domínios:
  - `loja1.plataforma.com`
  - `loja2.plataforma.com`
  - `minhaloja.com.br` (domínio customizado)

**Como funciona:**
1. O sistema captura o `HTTP_HOST` da requisição
2. Busca na tabela `tenant_domains` o tenant associado ao domínio
3. Carrega o tenant no `TenantContext`
4. Todas as consultas subsequentes filtram automaticamente por `tenant_id`

### APP_MODE=single (Modo Single-tenant)

No modo **single**, o sistema funciona como uma instalação isolada para uma única loja. Mesmo assim, a estrutura do banco de dados mantém suporte multi-tenant (com coluna `tenant_id` em todas as tabelas), mas apenas um tenant é utilizado.

**Características:**
- 1 código, 1 banco de dados, 1 tenant fixo
- O tenant é definido pela configuração `DEFAULT_TENANT_ID` no `.env`
- Não há resolução por domínio - sempre usa o mesmo tenant

**Como funciona:**
1. O sistema lê `DEFAULT_TENANT_ID` do `.env`
2. Carrega o tenant fixo no `TenantContext`
3. Todas as consultas filtram por esse `tenant_id` fixo

**Por que manter estrutura multi-tenant mesmo em modo single?**
- Facilita atualizações futuras
- Permite migração de single para multi sem mudanças estruturais
- Reaproveitamento total do código entre os dois modos

## Estrutura de Dados

### Tabelas Globais (sem tenant_id ou apenas de controle)

#### `tenants`
Armazena informações sobre cada loja/tenant:
- `id`: Identificador único
- `name`: Nome da loja
- `slug`: Slug para URLs internas
- `status`: Status (active, suspended, trial, cancelled)
- `plan`: Plano (basic, pro, enterprise)

#### `tenant_domains`
Mapeia domínios para tenants (usado no modo multi):
- `tenant_id`: FK para tenants
- `domain`: Domínio completo (ex: loja1.plataforma.com)
- `is_primary`: Se é o domínio principal
- `is_custom_domain`: Se é um domínio customizado
- `ssl_status`: Status do SSL (pending, active, error)

#### `platform_users`
Usuários administradores da plataforma (superadmin):
- Usados apenas no modo multi para gerenciar tenants
- Não têm `tenant_id` (são globais)

#### `store_users`
Usuários administradores de cada loja:
- Têm `tenant_id` (pertencem a uma loja específica)
- Roles: owner, manager, staff

#### `system_versions`
Rastreia versões do sistema aplicadas:
- `version`: Versão (ex: 1.0.0)
- `applied_at`: Data de aplicação

#### `migrations`
Controle de migrations aplicadas:
- `migration`: Nome do arquivo de migration
- `applied_at`: Data de aplicação

#### `tenant_settings`
Configurações gerais por tenant:
- `tenant_id`: FK para tenants
- `key`: Chave da configuração
- `value`: Valor (pode ser JSON)

### Tabelas por Tenant (todas com tenant_id)

Todas as tabelas de domínio do negócio possuem `tenant_id` e índice:
- `customers`
- `customer_addresses`
- `categories`
- `brands`
- `products`
- `product_images`
- `carts`
- `cart_items`
- `orders`
- `order_items`
- `order_status_history`
- `coupons`
- `coupon_redemptions`

## TenantContext

A classe `App\Tenant\TenantContext` é o ponto central para gerenciar o tenant atual da requisição.

### Métodos principais:

```php
// Resolver tenant por domínio (modo multi)
TenantContext::resolveFromHost(string $host): void

// Definir tenant fixo (modo single)
TenantContext::setFixedTenant(int $tenantId): void

// Obter tenant atual
TenantContext::tenant(): Tenant

// Obter ID do tenant atual
TenantContext::id(): int
```

### Como é resolvido

1. **Modo Multi:**
   - Middleware `TenantResolverMiddleware` captura `HTTP_HOST`
   - Busca em `tenant_domains` via `TenantRepository`
   - Carrega no `TenantContext`

2. **Modo Single:**
   - Middleware lê `DEFAULT_TENANT_ID` do `.env`
   - Carrega tenant fixo no `TenantContext`

### Uso em consultas

Todas as consultas de dados da loja devem sempre incluir `tenant_id`:

```php
$tenantId = TenantContext::id();
$stmt = $db->prepare("SELECT * FROM products WHERE tenant_id = :tenant_id");
$stmt->execute(['tenant_id' => $tenantId]);
```

## Platform Users vs Store Users

### Platform Users
- **Uso:** Apenas no modo multi
- **Acesso:** `/admin/platform/*`
- **Função:** Gerenciar tenants, domínios, monitorar uso
- **Escopo:** Global (sem tenant_id)

### Store Users
- **Uso:** Ambos os modos (multi e single)
- **Acesso:** `/admin/*`
- **Função:** Gerenciar produtos, pedidos, clientes da loja
- **Escopo:** Por tenant (com tenant_id)

## Fluxo de Requisição

1. **Front Controller** (`public/index.php`)
   - Carrega autoloader
   - Carrega variáveis de ambiente do `.env`

2. **TenantResolverMiddleware**
   - Lê `APP_MODE`
   - Se `multi`: resolve por domínio
   - Se `single`: usa `DEFAULT_TENANT_ID`
   - Carrega no `TenantContext`

3. **Router**
   - Dispara middlewares da rota
   - Executa controller/handler

4. **Controller**
   - Usa `TenantContext::id()` para filtrar dados
   - Renderiza view ou retorna JSON

## Estrutura de Pastas

```
project-root/
  public/              # Ponto de entrada público
  src/
    Core/              # Kernel, Database, Router, Controller base
    Http/
      Controllers/     # Controllers
      Middleware/      # Middlewares
    Tenant/            # TenantContext, TenantRepository
    Services/          # MigrationRunner, AuthService
  config/              # Configurações (app, database, paths)
  database/
    migrations/       # Arquivos de migration
    seeds/            # Seeds iniciais
  storage/            # Logs, cache, uploads por tenant
  themes/
    default/          # Views/templates
  docs/               # Documentação
```

## Próximas Fases

Esta Fase 0 estabelece a base. As próximas fases implementarão:
- Fase 1: Catálogo, home, PDP, carrinho
- Fase 2: Checkout, pagamentos, frete
- Fase 3: Painel do cliente
- Fase 4: Painel admin completo



