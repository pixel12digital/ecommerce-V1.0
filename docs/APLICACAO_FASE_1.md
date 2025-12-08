# E-commerce Multi-tenant v1.0 - Documentação da Aplicação

## 📋 Índice

- [Visão Geral do Projeto](#visão-geral-do-projeto)
- [Arquitetura](#arquitetura)
- [Fases de Desenvolvimento](#fases-de-desenvolvimento)
- [Fase 1: Tema + Layout Base da Home](#fase-1-tema--layout-base-da-home)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Configuração e Instalação](#configuração-e-instalação)
- [Acessos e URLs](#acessos-e-urls)
- [Tecnologias Utilizadas](#tecnologias-utilizadas)

---

## Visão Geral do Projeto

Este é um sistema de e-commerce profissional desenvolvido em **PHP 8.x** com arquitetura **MVC simples**, projetado para funcionar tanto em modo **multi-tenant** (SaaS) quanto em modo **single-tenant** (instalação isolada), usando um único código-base.

### Características Principais

- ✅ **Multi-tenant**: Suporta múltiplas lojas em uma única instalação
- ✅ **Single-tenant**: Pode funcionar como instalação isolada
- ✅ **MVC Simples**: Arquitetura limpa e fácil de manter
- ✅ **PHP 8.x**: Utiliza recursos modernos do PHP
- ✅ **Sem Framework**: Código próprio, sem dependências pesadas
- ✅ **Tema Customizável**: Cada loja pode personalizar cores, textos e layout

---

## Arquitetura

### Modos de Operação

#### Modo Multi-tenant (`APP_MODE=multi`)

- Múltiplas lojas em uma única instalação
- Cada loja identificada por domínio/subdomínio
- Dados isolados por `tenant_id`
- Exemplo: `loja1.plataforma.com`, `loja2.plataforma.com`

#### Modo Single-tenant (`APP_MODE=single`)

- Uma única loja por instalação
- Tenant fixo definido em `DEFAULT_TENANT_ID`
- Mesma estrutura de dados (facilita migração futura)

### Componentes Principais

1. **TenantContext**: Gerencia o tenant atual
2. **ThemeConfig**: Gerencia configurações de tema por tenant
3. **Controllers**: Lógica de negócio
4. **Views**: Templates PHP
5. **Services**: Serviços auxiliares (Auth, Migration, etc.)

---

## Fases de Desenvolvimento

### ✅ Fase 0: Base Multi-tenant
- Estrutura multi-tenant
- Sistema de autenticação (Platform Admin + Store Admin)
- Tabelas de produtos/categorias
- Painel Store Admin básico

### ✅ Fase 1: Tema + Layout Base da Home
- Sistema de configurações de tema por tenant
- Painel admin para editar tema
- Home pública com layout completo
- **Status: CONCLUÍDA** (ver [FASE_1_TEMA_LAYOUT_HOME.md](./FASE_1_TEMA_LAYOUT_HOME.md))

### ✅ Fase 2: Home Dinâmica (Categorias + Banners + Newsletter)
- Bolotas de categorias configuráveis
- Seções de produtos por categoria (4 seções)
- Gestão de banners (hero + retrato)
- Sistema de newsletter funcional
- **Status: CONCLUÍDA** (ver [FASE_2_HOME_DINAMICA.md](./FASE_2_HOME_DINAMICA.md))

### ✅ Fase 3: Loja (Listagem + PDP)
- Listagem completa com filtros e paginação
- Navegação por categoria (URL amigável)
- Página de produto (PDP) completa
- Carrinho placeholder preparado para Fase 4
- **Status: CONCLUÍDA** (ver [FASE_3_LOJA_LISTAGEM_PDP.md](./FASE_3_LOJA_LISTAGEM_PDP.md))

### 🔄 Fase 4: (Próxima)
- Carrinho de compras
- Checkout
- Sistema de pedidos

---

## Fase 1: Tema + Layout Base da Home

### O que foi implementado

A Fase 1 adiciona um sistema completo de personalização de tema, permitindo que cada loja configure:

- **Cores**: 8 cores personalizáveis (primária, secundária, topbar, header, footer)
- **Textos**: Topbar, newsletter (título e subtítulo)
- **Contato**: Telefone, WhatsApp, e-mail, endereço
- **Redes Sociais**: Instagram, Facebook, YouTube
- **Menu Principal**: Itens editáveis com ativação/desativação

### Funcionalidades

#### Painel Admin
- **Rota**: `/admin/tema`
- **Acesso**: Store Admin autenticado
- **Funcionalidades**:
  - Edição de todas as configurações de tema
  - Preview visual das cores
  - Gerenciamento de menu principal
  - Salvamento com feedback de sucesso

#### Home Pública
- **Rota**: `/`
- **Componentes**:
  - Top bar configurável
  - Header com logo, busca e menu
  - Faixa de categorias (scroll horizontal)
  - Hero slider
  - Seção de benefícios (4 cards)
  - Seções de produtos por categoria
  - Banners retrato
  - Newsletter configurável
  - Footer completo

#### Responsividade
- Menu hambúrguer no mobile
- Layout adaptativo
- Scroll horizontal para categorias

### Documentação Detalhada

- **Fase 1:** [FASE_1_TEMA_LAYOUT_HOME.md](./FASE_1_TEMA_LAYOUT_HOME.md)
- **Fase 2:** [FASE_2_HOME_DINAMICA.md](./FASE_2_HOME_DINAMICA.md)
- **Fase 3:** [FASE_3_LOJA_LISTAGEM_PDP.md](./FASE_3_LOJA_LISTAGEM_PDP.md) ⭐ NOVO

---

## Estrutura do Projeto

```
ecommerce-v1.0/
├── config/                  # Configurações
│   ├── app.php             # Configurações da aplicação
│   ├── database.php        # Configurações do banco
│   └── paths.php           # Caminhos do sistema
│
├── database/              # Scripts de banco de dados
│   ├── migrations/        # Migrations do banco
│   ├── seeds/             # Seeds (dados iniciais)
│   ├── run_migrations.php # Executar migrations
│   └── run_seed.php       # Executar seeds
│
├── docs/                  # Documentação
│   ├── README.md          # Documentação geral
│   ├── FASE_1_TEMA_LAYOUT_HOME.md  # Doc Fase 1
│   └── ...                # Outros documentos
│
├── public/                # Ponto de entrada público
│   ├── index.php          # Front controller
│   └── .htaccess          # Configurações Apache
│
├── src/                   # Código fonte
│   ├── Core/              # Classes core
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   └── Router.php
│   ├── Http/
│   │   ├── Controllers/   # Controllers
│   │   │   ├── Admin/
│   │   │   │   ├── ThemeController.php
│   │   │   │   └── ProductController.php
│   │   │   └── Storefront/
│   │   │       └── HomeController.php
│   │   └── Middleware/    # Middlewares
│   ├── Services/          # Serviços
│   │   ├── ThemeConfig.php
│   │   ├── AuthService.php
│   │   └── MigrationRunner.php
│   └── Tenant/            # Classes de tenant
│       ├── Tenant.php
│       ├── TenantContext.php
│       └── TenantRepository.php
│
├── storage/               # Armazenamento
│   ├── cache/            # Cache
│   └── logs/             # Logs
│
├── themes/               # Templates
│   └── default/
│       ├── admin/        # Views admin
│       │   ├── store/
│       │   │   ├── dashboard.php
│       │   │   └── login.php
│       │   └── theme/
│       │       └── edit.php
│       └── storefront/   # Views loja
│           ├── home.php
│           └── products/
│
├── vendor/               # Dependências Composer
├── .env                  # Variáveis de ambiente
├── composer.json         # Dependências
└── README.md             # README principal
```

---

## Configuração e Instalação

### Requisitos

- PHP 8.0 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Apache com mod_rewrite (ou Nginx)
- Composer

### Instalação

1. **Clonar/Baixar o projeto**
   ```bash
   cd c:\xampp\htdocs\ecommerce-v1.0
   ```

2. **Instalar dependências**
   ```bash
   composer install
   ```

3. **Configurar .env**
   - Copiar `env.example.txt` para `.env`
   - Configurar banco de dados
   - Definir `APP_MODE` (multi ou single)
   - Definir `DEFAULT_TENANT_ID` se single

4. **Executar migrations**
   ```bash
   php database/run_migrations.php
   ```

5. **Executar seed**
   ```bash
   php database/run_seed.php
   ```

### Configuração do Apache

Certifique-se de que o `.htaccess` está funcionando e o `mod_rewrite` está habilitado.

---

## Acessos e URLs

### Platform Admin (Super Admin)

**URLs:**
- Login: `/admin/platform/login`
- Dashboard: `/admin/platform`

**Credenciais padrão:**
- Email: `admin@platform.local`
- Senha: `admin123`

**Quando usar:**
- Apenas no modo multi-tenant
- Para gerenciar múltiplas lojas

### Store Admin (Admin da Loja)

**URLs:**
- Login: `/admin/login`
- Dashboard: `/admin`
- Tema da Loja: `/admin/tema`
- Produtos: `/admin/produtos`

**Credenciais padrão:**
- Email: `contato@pixel12digital.com.br`
- Senha: `admin123`

**Quando usar:**
- Modo single-tenant: admin da loja única
- Modo multi-tenant: admin de uma loja específica

### Loja Pública

**URLs:**
- Home: `/`
- Produtos: `/produtos`
- Produto: `/produto/{slug}`

---

## Tecnologias Utilizadas

### Backend
- **PHP 8.x**: Linguagem principal
- **PDO**: Acesso ao banco de dados
- **Composer**: Gerenciamento de dependências

### Frontend
- **HTML5**: Estrutura
- **CSS3**: Estilização (inline na Fase 1)
- **JavaScript**: Interatividade básica

### Banco de Dados
- **MySQL/MariaDB**: Banco de dados relacional
- **InnoDB**: Engine de tabelas

### Ferramentas
- **Composer**: Gerenciamento de dependências
- **Git**: Controle de versão

---

## Próximos Passos

### Fase 4 (Planejada)
- Carrinho de compras
- Checkout completo
- Sistema de pedidos
- Painel de pedidos no admin

---

## Documentação Adicional

- [Fase 1 - Tema + Layout Home](./FASE_1_TEMA_LAYOUT_HOME.md) - Documentação completa da Fase 1
- [Fase 2 - Home Dinâmica](./FASE_2_HOME_DINAMICA.md) - Documentação completa da Fase 2
- [Fase 3 - Loja (Listagem + PDP)](./FASE_3_LOJA_LISTAGEM_PDP.md) - Documentação completa da Fase 3 ⭐ NOVO
- [Arquitetura Multi-tenant](./ARQUITETURA_ECOMMERCE_MULTITENANT.md) - Detalhes da arquitetura
- [Acessos e URLs](./ACESSOS_E_URLS.md) - Lista completa de URLs
- [Troubleshooting](./TROUBLESHOOTING_404.md) - Solução de problemas comuns

---

## Suporte

Para dúvidas ou problemas:
1. Consulte a documentação específica da fase
2. Verifique os logs em `storage/logs/`
3. Execute o script de teste: `http://localhost/ecommerce-v1.0/public/test.php`

---

**Versão:** 3.0  
**Última atualização:** 2025-01-XX  
**Fase atual:** Fase 3 - Concluída ✅
