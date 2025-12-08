# 🔐 Acessos e URLs do Sistema

Este documento lista todos os acessos, URLs e credenciais do sistema e-commerce multi-tenant.

## 📋 Índice

- [Painéis Administrativos](#-painéis-administrativos)
- [Front-end (Loja Pública)](#-front-end-loja-pública)
- [Credenciais Padrão](#-credenciais-padrão)
- [URLs Base](#-urls-base)

---

## 🎛️ Painéis Administrativos

### Platform Admin (Super Admin)

**Acesso:** Gerenciamento da plataforma (modo multi-tenant)

**URLs:**
- **Login:** `http://localhost/ecommerce-v1.0/public/admin/platform/login`
- **Dashboard:** `http://localhost/ecommerce-v1.0/public/admin/platform`
- **Editar Tenant:** `http://localhost/ecommerce-v1.0/public/admin/platform/tenants/{id}/edit`
- **Logout:** `http://localhost/ecommerce-v1.0/public/admin/platform/logout`

**Credenciais padrão (após seed):**
- **Email:** `admin@platform.local`
- **Senha:** `admin123`

**Funcionalidades:**
- Listar todos os tenants (lojas)
- Editar informações dos tenants
- Gerenciar domínios (futuro)
- Monitorar uso e planos (futuro)

**Quando usar:**
- Apenas no modo **multi-tenant** (`APP_MODE=multi`)
- Para gerenciar múltiplas lojas na plataforma

---

### Store Admin (Admin da Loja)

**Acesso:** Gerenciamento de uma loja específica

**URLs:**
- **Login:** `http://localhost/ecommerce-v1.0/public/admin/login`
- **Dashboard:** `http://localhost/ecommerce-v1.0/public/admin`
- **Atualizações do Sistema:** `http://localhost/ecommerce-v1.0/public/admin/system/updates`
- **Logout:** `http://localhost/ecommerce-v1.0/public/admin/logout`

**Credenciais padrão (após seed):**
- **Email:** `contato@pixel12digital.com.br`
- **Senha:** `admin123`

**Funcionalidades:**
- Dashboard da loja
- Ver informações do tenant atual
- Acessar atualizações do sistema
- Gerenciar produtos (futuro)
- Gerenciar pedidos (futuro)
- Gerenciar clientes (futuro)
- Configurações da loja (futuro)

**Quando usar:**
- Modo **single-tenant** (`APP_MODE=single`) - admin da loja única
- Modo **multi-tenant** (`APP_MODE=multi`) - admin de uma loja específica

---

## 🛒 Front-end (Loja Pública)

**Status:** ⚠️ **Em desenvolvimento** (Fase 1)

**URL Base:**
- **Home:** `http://localhost/ecommerce-v1.0/public/`

**Rotas planejadas (futuro):**
- **Home:** `http://localhost/ecommerce-v1.0/public/`
- **Categorias:** `http://localhost/ecommerce-v1.0/public/categoria/{slug}`
- **Produto:** `http://localhost/ecommerce-v1.0/public/produto/{slug}`
- **Carrinho:** `http://localhost/ecommerce-v1.0/public/carrinho`
- **Checkout:** `http://localhost/ecommerce-v1.0/public/checkout`
- **Área do Cliente:** `http://localhost/ecommerce-v1.0/public/minha-conta`

**Status atual:**
- A rota `/` mostra apenas uma mensagem informativa
- As funcionalidades de catálogo, carrinho e checkout serão implementadas nas próximas fases

---

## 🔑 Credenciais Padrão

Após executar `php database/run_seed.php`, as seguintes credenciais são criadas:

### Platform Admin
```
Email: admin@platform.local
Senha: admin123
```

### Store Admin
```
Email: contato@pixel12digital.com.br
Senha: admin123
```

**⚠️ IMPORTANTE:** 
- Essas são credenciais de **desenvolvimento/teste**
- **Altere as senhas** antes de colocar em produção
- Use senhas fortes em ambiente de produção

---

## 🌐 URLs Base

### Desenvolvimento Local

**Base URL:** `http://localhost`

**Estrutura completa:**
```
http://localhost/ecommerce-v1.0/public/                          # Front-end (em desenvolvimento)
http://localhost/ecommerce-v1.0/public/admin/login               # Store Admin Login
http://localhost/ecommerce-v1.0/public/admin                    # Store Admin Dashboard
http://localhost/ecommerce-v1.0/public/admin/platform/login     # Platform Admin Login
http://localhost/ecommerce-v1.0/public/admin/platform           # Platform Admin Dashboard
http://localhost/ecommerce-v1.0/public/test.php                 # Script de teste/diagnóstico
```

### Produção (Hostinger)

**Base URL:** `https://seudominio.com.br`

**Estrutura completa:**
```
https://seudominio.com.br/                # Front-end
https://seudominio.com.br/admin/login     # Store Admin Login
https://seudominio.com.br/admin           # Store Admin Dashboard
https://seudominio.com.br/admin/platform/login  # Platform Admin Login
https://seudominio.com.br/admin/platform  # Platform Admin Dashboard
```

### Modo Multi-tenant

No modo multi-tenant, cada loja pode ter seu próprio domínio:

**Exemplo:**
```
https://loja1.plataforma.com.br/          # Loja 1
https://loja2.plataforma.com.br/          # Loja 2
https://minhaloja.com.br/                 # Loja com domínio customizado
```

Cada domínio resolve automaticamente para o tenant correto através da tabela `tenant_domains`.

---

## 🔒 Segurança

### Middleware de Autenticação

- **Rotas `/admin/platform/*`:** Requerem autenticação de Platform Admin
- **Rotas `/admin/*`:** Requerem autenticação de Store Admin
- **Rotas públicas:** Não requerem autenticação (futuro: home, catálogo, etc.)

### Sessões

- As sessões são gerenciadas pelo PHP
- Nome da sessão configurável via `.env`: `SESSION_NAME`
- Padrão: `ECOMMERCE_SESSION`

---

## 📝 Notas Importantes

1. **Tenant Resolution:**
   - No modo **single**, sempre usa `DEFAULT_TENANT_ID`
   - No modo **multi**, resolve pelo domínio (`HTTP_HOST`)

2. **Acesso ao Front-end:**
   - O front-end ainda não está implementado
   - A rota `/` mostra apenas uma mensagem informativa
   - Será implementado na Fase 1 (catálogo, home, PDP, carrinho)

3. **Acesso aos Painéis:**
   - Ambos os painéis estão funcionais
   - Requerem autenticação
   - Dashboards básicos implementados

4. **Atualizações do Sistema:**
   - Acessível via Store Admin: `/admin/system/updates`
   - Permite rodar migrations pendentes via interface web

---

## 🚀 Próximos Passos

- **Fase 1:** Implementar front-end (home, catálogo, PDP, carrinho)
- **Fase 2:** Implementar checkout e pagamentos
- **Fase 3:** Área do cliente
- **Fase 4:** Painel admin completo

---

**Última atualização:** Fase 0 concluída ✅

