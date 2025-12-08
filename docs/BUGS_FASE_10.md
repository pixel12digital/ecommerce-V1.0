# BUGS – Fase 10 (Testes)

## Como usar

- Severidade:
  - CRÍTICO: quebra fluxo de compra, login, admin, etc.
  - ALTO: não quebra, mas atrapalha bastante (layout forte, texto confuso).
  - MÉDIO: incômodo visual/UX, mas usável.
  - BAIXO: detalhe estético ou texto menor.

---

## 001 – Múltiplos session_start() sem verificação

- Severidade: MÉDIO
- Tela: Todas as páginas do storefront que usam sessão
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxos principais do cliente
- Passos para reproduzir:
  1. Acessar qualquer página do storefront que use sessão (checkout, carrinho, área do cliente)
  2. Verificar logs do PHP ou warnings
- Resultado atual:
  - Vários controllers chamam `session_start()` sem verificar se a sessão já foi iniciada
  - Pode gerar warnings "session_start(): Session has already been started"
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de chamar `session_start()`
- Status: RESOLVIDO
- Notas de correção:
  - Corrigido em todos os controllers do storefront:
    - `CheckoutController::index()` e `process()`
    - `ProductReviewController::store()`
    - `CustomerController::getCustomerId()` (método privado)
    - Removidos `session_start()` duplicados em `CustomerController` (orders, orderShow, addresses, saveAddress, deleteAddress, profile, updateProfile)
    - `CustomerAuthController` (todos os métodos)
    - `ProductController::show()`
  - Arquivos alterados:
    - `src/Http/Controllers/Storefront/CheckoutController.php`
    - `src/Http/Controllers/Storefront/ProductReviewController.php`
    - `src/Http/Controllers/Storefront/CustomerController.php`
    - `src/Http/Controllers/Storefront/CustomerAuthController.php`
    - `src/Http/Controllers/Storefront/ProductController.php`

---

## 002 – Checkout não permite criar conta durante o processo

- Severidade: ALTO
- Tela: Checkout – dados do cliente
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxo de compra – cliente novo → "Criar conta no próprio checkout"
- Passos para reproduzir:
  1. Adicionar produto ao carrinho
  2. Ir para checkout
  3. Verificar se há opção de criar conta
- Resultado atual:
  - Checkout apenas permite fazer login (link para `/minha-conta/login`)
  - Não há opção de criar conta durante o checkout
  - Cliente precisa sair do checkout, criar conta, voltar
- Resultado esperado:
  - Opção de criar conta durante o checkout (checkbox "Criar conta" com campo de senha)
  - Se marcado, criar conta automaticamente após finalizar pedido
- Status: RESOLVIDO
- Notas de correção:
  - Implementada opção de criação de conta durante o checkout
  - Quando cliente não está logado, aparece checkbox "Criar uma conta para acompanhar seus pedidos" com campo de senha
  - Se checkbox não for marcado, checkout exibe mensagem: "Para finalizar sua compra, faça login ou crie uma conta."
  - Se checkbox marcado, sistema cria conta automaticamente antes de finalizar pedido
  - Login automático após criação de conta
  - Se e-mail já existir, sistema tenta fazer login com a senha informada
  - Arquivos modificados:
    - `themes/default/storefront/checkout/index.php` (adicionados campos de criação de conta)
    - `src/Http/Controllers/Storefront/CheckoutController.php` (implementada lógica de criação de conta e validação)

---

## 003 – session_start() duplicado em CustomerController

- Severidade: MÉDIO
- Tela: Área do Cliente
- Ambiente: Desktop/Mobile
- Checklist relacionado: Área do Cliente – Funcionalidades
- Passos para reproduzir:
  1. Fazer login na área do cliente
  2. Navegar entre páginas (dashboard, pedidos, endereços, perfil)
  3. Verificar logs do PHP
- Resultado atual:
  - Método `getCustomerId()` chama `session_start()`
  - Métodos individuais também chamam `session_start()` antes de chamar `getCustomerId()`
  - Resulta em `session_start()` duplicado
- Resultado esperado:
  - Remover `session_start()` dos métodos individuais, deixar apenas em `getCustomerId()`
  - Ou verificar `session_status()` antes de iniciar
- Status: RESOLVIDO
- Notas de correção:
  - Removidos `session_start()` duplicados dos métodos: `orders()`, `orderShow()`, `addresses()`, `saveAddress()`, `deleteAddress()`, `profile()`, `updateProfile()`
  - `getCustomerId()` agora verifica `session_status() === PHP_SESSION_NONE` antes de iniciar
  - Arquivo: `src/Http/Controllers/Storefront/CustomerController.php`

---

## 004 – session_start() sem verificação em CheckoutController

- Severidade: MÉDIO
- Tela: Checkout
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxo de compra – cliente novo/existente
- Passos para reproduzir:
  1. Adicionar produto ao carrinho
  2. Ir para checkout
  3. Verificar logs do PHP
- Resultado atual:
  - `index()` e `process()` chamam `session_start()` sem verificação
  - Pode gerar warnings se sessão já foi iniciada
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de iniciar
- Status: RESOLVIDO
- Notas de correção:
  - Adicionada verificação de `session_status() === PHP_SESSION_NONE` em `CheckoutController::index()` e `process()`
  - Arquivo: `src/Http/Controllers/Storefront/CheckoutController.php`

---

## 005 – session_start() sem verificação em ProductReviewController

- Severidade: MÉDIO
- Tela: PDP – formulário de avaliação
- Ambiente: Desktop/Mobile
- Checklist relacionado: Avaliações de produtos
- Passos para reproduzir:
  1. Fazer login como cliente
  2. Acessar PDP de produto comprado
  3. Enviar avaliação
  4. Verificar logs do PHP
- Resultado atual:
  - `store()` chama `session_start()` sem verificação
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de iniciar
- Status: RESOLVIDO
- Notas de correção:
  - Adicionada verificação de `session_status() === PHP_SESSION_NONE` em `ProductReviewController::store()`
  - Arquivo: `src/Http/Controllers/Storefront/ProductReviewController.php`

---

## 006 – session_start() sem verificação em CustomerAuthController

- Severidade: MÉDIO
- Tela: Login/Registro de cliente
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxo de compra – cliente existente
- Passos para reproduzir:
  1. Acessar `/minha-conta/login` ou `/minha-conta/registrar`
  2. Fazer login ou registrar
  3. Verificar logs do PHP
- Resultado atual:
  - Múltiplos métodos chamam `session_start()` sem verificação
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de iniciar
- Status: RESOLVIDO
- Notas de correção:
  - Adicionada verificação de `session_status() === PHP_SESSION_NONE` em todos os métodos:
    - `showLoginForm()`, `login()`, `showRegisterForm()`, `register()`, `logout()`
  - Arquivo: `src/Http/Controllers/Storefront/CustomerAuthController.php`

---

## 007 – session_start() sem verificação em ProductController

- Severidade: MÉDIO
- Tela: PDP
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxo de compra – cliente novo
- Passos para reproduzir:
  1. Acessar PDP de um produto
  2. Verificar logs do PHP
- Resultado atual:
  - Método `show()` chama `session_start()` sem verificação
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de iniciar
- Status: RESOLVIDO
- Notas de correção:
  - Adicionada verificação de `session_status() === PHP_SESSION_NONE` em `ProductController::show()`
  - Arquivo: `src/Http/Controllers/Storefront/ProductController.php`

---

## 008 – Cliente não logado não vê pedido na área do cliente após checkout

- Severidade: ALTO
- Tela: Área do Cliente → Pedidos
- Ambiente: Desktop/Mobile
- Checklist relacionado: Fluxo de compra – cliente novo → "Ver pedido na Área do Cliente"
- Passos para reproduzir:
  1. Fazer checkout sem estar logado (cliente novo)
  2. Finalizar pedido
  3. Tentar ver pedido na área do cliente
  4. Não consegue porque pedido não tem `customer_id` vinculado
- Resultado atual:
  - Pedido é criado com `customer_id = null` se cliente não estiver logado
  - Cliente não consegue ver pedido na área do cliente mesmo após criar conta
- Resultado esperado:
  - Opção 1: Criar conta durante checkout (bug 002)
  - Opção 2: Permitir vincular pedido a conta existente por e-mail após checkout
  - Opção 3: Permitir visualizar pedido por número do pedido sem login (página pública)
- Status: RESOLVIDO
- Notas de correção:
  - Implementada solução via criação de conta durante checkout (bug 002)
  - Checkout agora exige que cliente esteja logado OU crie conta durante o processo
  - Pedido nunca mais é criado com `customer_id = null`
  - Se cliente não estiver logado e não marcar checkbox de criar conta, checkout não finaliza e exibe mensagem de erro
  - Se cliente criar conta durante checkout, pedido é vinculado automaticamente ao `customer_id` do novo cliente
  - Cliente pode ver pedido na área do cliente imediatamente após finalizar compra
  - Arquivos modificados:
    - `themes/default/storefront/checkout/index.php` (adicionados campos de criação de conta)
    - `src/Http/Controllers/Storefront/CheckoutController.php` (validação obrigatória de login/conta e garantia de customer_id sempre preenchido)

---

## 009 – CustomerAuthMiddleware acessa $_SESSION sem iniciar sessão

- Severidade: MÉDIO
- Tela: Área do Cliente (qualquer página protegida)
- Ambiente: Desktop/Mobile
- Checklist relacionado: Área do Cliente – Funcionalidades
- Passos para reproduzir:
  1. Tentar acessar `/minha-conta` sem estar logado
  2. Verificar logs do PHP
- Resultado atual:
  - `CustomerAuthMiddleware::handle()` acessa `$_SESSION` sem verificar se sessão foi iniciada
  - Pode gerar warnings se sessão não foi iniciada
- Resultado esperado:
  - Verificar `session_status() === PHP_SESSION_NONE` antes de acessar `$_SESSION`
- Status: RESOLVIDO
- Notas de correção:
  - Adicionada verificação e inicialização de sessão em `CustomerAuthMiddleware::handle()`
  - Arquivo: `src/Http/Middleware/CustomerAuthMiddleware.php`

---

## 010 – Link de Gateways no menu admin aponta para rota incorreta

- Severidade: BAIXO
- Tela: Admin – Menu lateral
- Ambiente: Desktop/Mobile
- Checklist relacionado: Gateways – Listagem
- Passos para reproduzir:
  1. Fazer login no admin
  2. Clicar em "Gateways" no menu lateral
  3. Verificar se página carrega
- Resultado atual:
  - Menu aponta para `/admin/gateways`
  - Rota real é `/admin/configuracoes/gateways`
  - Link quebra (404)
- Resultado esperado:
  - Link apontar para `/admin/configuracoes/gateways`
- Status: RESOLVIDO
- Notas de correção:
  - Corrigido link no menu lateral do admin
  - Arquivo: `themes/default/admin/layouts/store.php`
