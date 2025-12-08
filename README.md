# E-commerce Multi-tenant v1.0

Sistema de e-commerce profissional com suporte a multi-tenant (SaaS) e single-tenant (instalação isolada), desenvolvido em PHP 8.x com arquitetura MVC simples.

## Requisitos

- PHP 8.0 ou superior
- MySQL 5.7+ ou MariaDB 10.3+
- Composer
- Extensões PHP: PDO, mbstring

## Instalação

### 1. Clonar/Baixar o projeto

```bash
cd /caminho/do/projeto
```

### 2. Instalar dependências

```bash
composer install
```

### 3. Configurar ambiente

Copie o arquivo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Edite o `.env` com suas configurações:

```env
APP_MODE=multi  # ou "single"
DEFAULT_TENANT_ID=1

DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecommerce_db
DB_USER=root
DB_PASS=

APP_URL=http://localhost
APP_ENV=development
APP_DEBUG=true
```

### 4. Criar banco de dados

```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Executar migrations

```bash
php database/run_migrations.php
```

### 6. Executar seed inicial

```bash
php database/run_seed.php
```

Isso criará:
- Tenant demo (ID: 1, slug: loja-demo)
- Domínio localhost
- Platform admin: `admin@platform.local` / `admin123`
- Store admin: `contato@pixel12digital.com.br` / `admin123`

### 7. Configurar servidor web

#### Apache

Certifique-se de que o DocumentRoot aponte para a pasta `public/`:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/ecommerce-v1.0/public"
    
    <Directory "C:/xampp/htdocs/ecommerce-v1.0/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name localhost;
    root /caminho/do/projeto/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Acessos

Após a instalação, você pode acessar:

- **Script de Teste:** http://localhost/ecommerce-v1.0/public/test.php
  - Verifica configuração do ambiente

- **Platform Admin:** http://localhost/ecommerce-v1.0/public/admin/platform/login
  - Email: `admin@platform.local`
  - Senha: `admin123`

- **Store Admin:** http://localhost/ecommerce-v1.0/public/admin/login
  - Email: `contato@pixel12digital.com.br`
  - Senha: `admin123`

- **Atualizações do Sistema:** http://localhost/ecommerce-v1.0/public/admin/system/updates
  - (Requer login como store admin)

## Estrutura do Projeto

```
ecommerce-v1.0/
├── public/              # Ponto de entrada público
│   ├── index.php       # Front Controller
│   └── .htaccess       # Rewrite rules
├── src/                # Código fonte
│   ├── Core/           # Kernel, Database, Router, Controller
│   ├── Http/           # Controllers, Middleware
│   ├── Tenant/         # TenantContext, TenantRepository
│   └── Services/       # MigrationRunner, AuthService
├── config/             # Configurações
├── database/
│   ├── migrations/     # Migrations do banco
│   └── seeds/          # Seeds iniciais
├── storage/            # Logs, cache, uploads
├── themes/             # Views/templates
└── docs/               # Documentação
```

## Modos de Operação

### Modo Multi-tenant (APP_MODE=multi)

- Múltiplas lojas em uma instalação
- Resolução de tenant por domínio
- Ideal para SaaS

### Modo Single-tenant (APP_MODE=single)

- Uma única loja por instalação
- Tenant fixo definido por DEFAULT_TENANT_ID
- Ideal para instalações isoladas

Veja mais detalhes em `docs/ARQUITETURA_ECOMMERCE_MULTITENANT.md`.

## Sistema de Migrations

O sistema possui migrations para gerenciar atualizações do banco de dados.

- **Executar migrations:** `php database/run_migrations.php`
- **Via web:** Acesse `/admin/system/updates`

Veja mais detalhes em `docs/ATUALIZACOES_E_VERSOES.md`.

## Documentação

- `docs/ARQUITETURA_ECOMMERCE_MULTITENANT.md` - Arquitetura e conceitos
- `docs/ATUALIZACOES_E_VERSOES.md` - Sistema de migrations e versões

## Fase 0 - Status

✅ Estrutura do projeto
✅ Multi-tenant vs single-tenant
✅ Migrations base
✅ Context de tenant
✅ Sistema de versões/migrations
✅ Logins mínimos (platform e store admin)
✅ Documentação inicial

## Status das Fases

- ✅ **Fase 0:** Base multi-tenant, autenticação, produtos
- ✅ **Fase 1:** Tema + Layout Base da Home
- ✅ **Fase 2:** Home Dinâmica (Categorias + Banners + Newsletter)
- ✅ **Fase 3:** Loja (Listagem + PDP)
- ✅ **Fase 4:** Carrinho + Checkout + Pedidos
- ✅ **Fase 5:** Admin Produtos (Edição + Mídia)
  - ✅ **Fase 5.1:** Integração de Vídeos na PDP
  - ✅ **Fase 5.2:** Drag-and-Drop na Galeria de Imagens
  - ✅ **Fase 5.3:** Preview de Vídeos na Galeria da Loja
- ✅ **Fase 6:** Área do Cliente (Storefront)
- ✅ **Fase 7:** Infraestrutura Neutra de Gateways (Pagamento + Frete)
- ✅ **Fase 8:** Admin Gerenciar Clientes

## Próximas Melhorias

Consulte **[docs/FASES_PENDENTES.md](docs/FASES_PENDENTES.md)** para ver o roadmap completo de funcionalidades pendentes, incluindo:
- Integração de vídeos na PDP
- Área do cliente
- Gateway de pagamento real
- API de frete real
- E muito mais...

## Licença

Proprietário - Todos os direitos reservados

