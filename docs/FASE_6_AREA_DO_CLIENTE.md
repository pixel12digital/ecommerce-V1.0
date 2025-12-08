# Fase 6: Área do Cliente (Storefront)

## 📋 Resumo

Implementação completa da Área do Cliente no storefront, permitindo que clientes se cadastrem, façam login, visualizem seus pedidos, gerenciem endereços e atualizem dados pessoais.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Implementar uma Área do Cliente completa no storefront, com:
- Cadastro e login de cliente (separado do admin)
- Dashboard "Minha Conta"
- Histórico de pedidos e detalhes de cada pedido
- Endereços salvos (faturamento/entrega)
- Edição básica de dados pessoais
- Integração com o fluxo de checkout

---

## 📦 Estrutura de Dados

### Tabelas Utilizadas

**Tabela: `customers`** (já existia)
- `id`, `tenant_id`, `name`, `email`, `password_hash`
- `document`, `phone`
- `created_at`, `updated_at`

**Tabela: `customer_addresses`** (já existia)
- `id`, `tenant_id`, `customer_id`, `type` (billing/shipping)
- `street`, `number`, `complement`, `neighborhood`
- `city`, `state`, `zipcode`, `is_default`
- `created_at`, `updated_at`

**Tabela: `pedidos`** (modificada)
- Adicionada coluna `customer_id` (nullable) via migration `034_add_customer_id_to_pedidos.php`
- Mantém compatibilidade com pedidos antigos (guest)

**Tabela: `pedido_itens`** (já existia)
- Utilizada para exibir itens dos pedidos

---

## 🔧 Implementação

### 1. Migration

**Arquivo:** `database/migrations/034_add_customer_id_to_pedidos.php`

**Alteração:**
- Adiciona coluna `customer_id` na tabela `pedidos` (nullable)
- Adiciona índice e foreign key
- Mantém compatibilidade com pedidos antigos (guest)

### 2. Middleware de Autenticação

**Arquivo:** `src/Http/Middleware/CustomerAuthMiddleware.php`

**Funcionalidade:**
- Verifica se cliente está logado (`$_SESSION['customer_id']`)
- Redireciona para login se não estiver autenticado
- Armazena URL de redirecionamento para retornar após login

### 3. Controllers

#### 3.1. CustomerAuthController

**Arquivo:** `src/Http/Controllers/Storefront/CustomerAuthController.php`

**Métodos:**
- `showLoginForm()`: Exibe formulário de login
- `login()`: Processa login (valida email/senha, verifica tenant_id)
- `showRegisterForm()`: Exibe formulário de cadastro
- `register()`: Processa cadastro (valida dados, verifica duplicidade de email, cria cliente, login automático)
- `logout()`: Encerra sessão do cliente

**Características:**
- Sessão separada do admin (`customer_id`, `customer_name`, `customer_email`)
- Validação de email e senha (mínimo 6 caracteres)
- Verificação de duplicidade de email por tenant
- Hash de senha com `password_hash()`

#### 3.2. CustomerController

**Arquivo:** `src/Http/Controllers/Storefront/CustomerController.php`

**Métodos:**
- `dashboard()`: Resumo geral (dados do cliente, últimos pedidos, total de pedidos)
- `orders()`: Listagem completa de pedidos
- `orderShow($codigo)`: Detalhes de um pedido específico (com itens)
- `addresses()`: Listagem e edição de endereços
- `saveAddress()`: Salvar/atualizar endereço
- `deleteAddress($id)`: Excluir endereço
- `profile()`: Dados pessoais do cliente
- `updateProfile()`: Atualizar dados pessoais (incluindo senha opcional)

**Características:**
- Todas as queries filtram por `tenant_id` e `customer_id`
- Validação de segurança (cliente só vê seus próprios dados)
- Mensagens de feedback via sessão

### 4. Views

**Localização:** `themes/default/storefront/customers/`

**Arquivos criados:**
- `layout.php`: Layout base com sidebar e menu de navegação
- `login.php`: Formulário de login
- `register.php`: Formulário de cadastro
- `dashboard.php`: Dashboard com resumo e últimos pedidos
- `orders.php`: Listagem de todos os pedidos
- `order-show.php`: Detalhes de um pedido específico
- `addresses.php`: Gerenciamento de endereços (listar, criar, editar, excluir)
- `profile.php`: Formulário de edição de dados pessoais

**Layout:**
- Menu lateral com links: Dashboard, Pedidos, Endereços, Dados da Conta, Sair
- Conteúdo principal à direita
- Responsivo (mobile-friendly)

### 5. Integração com Checkout

**Arquivo:** `src/Http/Controllers/Storefront/CheckoutController.php`

**Alterações:**
- Método `index()`: Busca dados do cliente logado e endereços salvos
- Método `process()`: Salva `customer_id` no pedido quando cliente está logado
- View `checkout/index.php`: Adiciona link "Já tem cadastro? Faça login" e preenche dados automaticamente

### 6. Navegação (Header)

**Arquivos modificados:**
- `themes/default/storefront/home.php`
- `themes/default/storefront/products/index.php`
- `themes/default/storefront/products/show.php`

**Alterações:**
- Link "Entrar" quando cliente não está logado
- Link "Minha Conta" / nome do cliente quando logado
- Ícone Bootstrap Icons (`bi-person` / `bi-person-circle`)

### 7. Rotas

**Arquivo:** `public/index.php`

**Rotas públicas (autenticação):**
- `GET /minha-conta/login` → `CustomerAuthController@showLoginForm`
- `POST /minha-conta/login` → `CustomerAuthController@login`
- `GET /minha-conta/registrar` → `CustomerAuthController@showRegisterForm`
- `POST /minha-conta/registrar` → `CustomerAuthController@register`
- `GET /minha-conta/logout` → `CustomerAuthController@logout`

**Rotas protegidas (área do cliente):**
- `GET /minha-conta` → `CustomerController@dashboard` (com `CustomerAuthMiddleware`)
- `GET /minha-conta/pedidos` → `CustomerController@orders` (com `CustomerAuthMiddleware`)
- `GET /minha-conta/pedidos/{codigo}` → `CustomerController@orderShow` (com `CustomerAuthMiddleware`)
- `GET /minha-conta/enderecos` → `CustomerController@addresses` (com `CustomerAuthMiddleware`)
- `POST /minha-conta/enderecos` → `CustomerController@saveAddress` (com `CustomerAuthMiddleware`)
- `GET /minha-conta/enderecos/excluir/{id}` → `CustomerController@deleteAddress` (com `CustomerAuthMiddleware`)
- `GET /minha-conta/perfil` → `CustomerController@profile` (com `CustomerAuthMiddleware`)
- `POST /minha-conta/perfil` → `CustomerController@updateProfile` (com `CustomerAuthMiddleware`)

---

## 🔒 Segurança e Multi-tenant

### Validações Implementadas

1. **Filtro por Tenant:**
   - Todas as queries incluem `tenant_id = :tenant_id`
   - Cliente só pode ver seus próprios dados
   - Email único por tenant (não global)

2. **Filtro por Cliente:**
   - Todas as queries de pedidos incluem `customer_id = :customer_id`
   - Cliente só vê seus próprios pedidos
   - Validação de propriedade antes de exibir detalhes

3. **Sessão:**
   - Sessão separada do admin (`customer_id` vs `admin_auth`)
   - Verificação de sessão em todas as rotas protegidas
   - Logout limpa sessão completamente

4. **Senhas:**
   - Hash com `password_hash()` (PASSWORD_DEFAULT)
   - Verificação com `password_verify()`
   - Senha mínima de 6 caracteres

---

## 📝 Fluxo de Uso

### Cadastro de Cliente

1. Cliente acessa `/minha-conta/registrar`
2. Preenche: Nome, E-mail, Telefone (opcional), CPF/CNPJ (opcional), Senha
3. Sistema valida dados e verifica duplicidade de email
4. Cliente é criado no banco
5. Login automático após cadastro
6. Redirecionamento para `/minha-conta?registered=1`

### Login de Cliente

1. Cliente acessa `/minha-conta/login`
2. Informa email e senha
3. Sistema valida credenciais (tenant_id + email + senha)
4. Sessão é criada com `customer_id`, `customer_name`, `customer_email`
5. Redirecionamento para URL original ou `/minha-conta`

### Checkout com Cliente Logado

1. Cliente adiciona produtos ao carrinho
2. Acessa `/checkout`
3. Se logado: dados são preenchidos automaticamente
4. Se não logado: link "Já tem cadastro? Faça login" disponível
5. Ao finalizar pedido: `customer_id` é salvo no pedido

### Visualização de Pedidos

1. Cliente acessa `/minha-conta/pedidos`
2. Lista todos os pedidos do cliente (ordenados por data DESC)
3. Clique em "Ver detalhes" → `/minha-conta/pedidos/{codigo}`
4. Exibe: dados do pedido, endereço, itens, totais

### Gerenciamento de Endereços

1. Cliente acessa `/minha-conta/enderecos`
2. Visualiza endereços cadastrados
3. Cria novo endereço ou edita existente
4. Marca endereço como padrão
5. Exclui endereços (com confirmação)

### Edição de Perfil

1. Cliente acessa `/minha-conta/perfil`
2. Edita: Nome, Telefone, CPF/CNPJ
3. Opcionalmente altera senha
4. Salva alterações

---

## ✅ Checklist de Aceite

- [x] Cliente consegue se cadastrar
- [x] Cliente consegue fazer login
- [x] Cliente consegue fazer logout
- [x] Dashboard exibe resumo e últimos pedidos
- [x] Listagem de pedidos funciona
- [x] Detalhes do pedido exibem todos os dados
- [x] Cliente consegue gerenciar endereços (criar, editar, excluir)
- [x] Cliente consegue editar dados pessoais
- [x] Cliente consegue alterar senha
- [x] Checkout salva `customer_id` quando cliente está logado
- [x] Checkout preenche dados automaticamente quando cliente está logado
- [x] Link "Minha Conta" / "Entrar" aparece no header
- [x] Rotas protegidas redirecionam para login se não autenticado
- [x] Multi-tenant: cliente de um tenant não vê dados de outro
- [x] Segurança: cliente só vê seus próprios pedidos

---

## 🔄 Compatibilidade

### Funcionalidades Mantidas

- ✅ Checkout para clientes não logados (guest) continua funcionando
- ✅ Pedidos antigos sem `customer_id` continuam acessíveis
- ✅ Admin de pedidos continua funcionando normalmente

### Não Afetado

- ❌ Autenticação de admin (separada)
- ❌ Outras funcionalidades da loja
- ❌ Fluxo de pagamento e frete

---

## 📊 Estrutura de Arquivos Criados/Modificados

```
database/migrations/
└── 034_add_customer_id_to_pedidos.php (NOVO)

src/Http/Middleware/
└── CustomerAuthMiddleware.php (NOVO)

src/Http/Controllers/Storefront/
├── CustomerAuthController.php (NOVO)
├── CustomerController.php (NOVO)
└── CheckoutController.php (MODIFICADO)

themes/default/storefront/customers/
├── layout.php (NOVO)
├── login.php (NOVO)
├── register.php (NOVO)
├── dashboard.php (NOVO)
├── orders.php (NOVO)
├── order-show.php (NOVO)
├── addresses.php (NOVO)
└── profile.php (NOVO)

themes/default/storefront/
├── home.php (MODIFICADO - header)
├── products/index.php (MODIFICADO - header)
├── products/show.php (MODIFICADO - header)
└── checkout/index.php (MODIFICADO - link login + preenchimento)

public/index.php (MODIFICADO - rotas)
```

---

## 🚀 Próximos Passos (Futuro)

### Melhorias Futuras
- Área do cliente: Rastreio de pedidos (quando API de frete real estiver integrada)
- Área do cliente: Avaliações de produtos
- Área do cliente: Wishlist/Favoritos
- Checkout: Conversão de pedido guest em conta de cliente
- Checkout: Preenchimento automático de endereço a partir de endereços salvos
- Autenticação: Recuperação de senha por e-mail
- Autenticação: Login social (Google, Facebook)

---

## 📚 Referências

- **Tabelas:** `customers`, `customer_addresses`, `pedidos`, `pedido_itens`
- **Migration:** `034_add_customer_id_to_pedidos.php`
- **Middleware:** `CustomerAuthMiddleware`
- **Controllers:** `CustomerAuthController`, `CustomerController`
- **Views:** `themes/default/storefront/customers/*`

---

## 🐛 Troubleshooting

### Problema: Cliente não consegue fazer login

**Verificar:**
1. Email e senha estão corretos
2. Cliente existe no tenant correto (`tenant_id`)
3. Senha está com hash correto no banco
4. Sessão está sendo iniciada (`session_start()`)

### Problema: Pedidos não aparecem na área do cliente

**Verificar:**
1. Pedido tem `customer_id` preenchido
2. `customer_id` corresponde ao cliente logado
3. `tenant_id` está correto
4. Query está filtrando corretamente

### Problema: Cliente vê pedidos de outro tenant

**Verificar:**
1. Todas as queries incluem `tenant_id = :tenant_id`
2. `TenantContext::id()` está retornando o tenant correto
3. Sessão não está compartilhada entre tenants

### Problema: Link "Minha Conta" não aparece

**Verificar:**
1. `session_start()` está sendo chamado antes de verificar `$_SESSION['customer_id']`
2. Sessão está persistindo entre requisições
3. Header está sendo renderizado corretamente

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX

---

## ⚠️ Migration Pendente

**IMPORTANTE:** A migration `034_add_customer_id_to_pedidos.php` precisa ser executada antes de usar a área do cliente.

### Como Executar

**Via Interface Web (Recomendado):**
1. Acesse `/admin/system/updates` (como admin da loja)
2. Clique em "Rodar Migrations"
3. Verifique se `034_add_customer_id_to_pedidos` aparece como aplicada

**Via CLI:**
```bash
php database/run_migrations.php
```

**Verificação Manual:**
```sql
-- Verificar se a coluna existe
SHOW COLUMNS FROM pedidos LIKE 'customer_id';

-- Verificar se a migration foi registrada
SELECT * FROM migrations WHERE migration = '034_add_customer_id_to_pedidos';
```

A migration é idempotente (verifica se a coluna já existe antes de adicionar), então pode ser executada com segurança mesmo se já tiver sido aplicada manualmente.
