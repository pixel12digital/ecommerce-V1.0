# 🚀 Deploy na Hostinger - Instalação Independente (Ponto do Golfe)

Este guia é específico para deploy de instalações independentes (single-tenant) em hostings compartilhados como a Hostinger, onde o DocumentRoot aponta para `public_html/` e pode haver restrições de configuração do Apache.

---

## 📋 Pré-requisitos

- ✅ Banco de dados remoto já configurado e acessível
- ✅ Tenant criado no banco de dados (geralmente ID 1)
- ✅ Domínio apontando para o servidor da Hostinger
- ✅ Acesso SSH ou File Manager da Hostinger

---

## 🔧 Passo a Passo de Deploy

### 1. Deploy dos Arquivos

#### Opção A: Via Git (Recomendado)

1. Acesse o painel da Hostinger
2. Vá em **"Sites"** → **"GIT"**
3. Configure o repositório:
   - **Repositório:** `https://github.com/pixel12digital/ecommerce-V1.0.git`
   - **Branch:** `main`
   - **Diretório:** Deixe vazio (deploy em `public_html`)
4. Clique em **"Criar"**

#### Opção B: Upload Manual

1. Faça download do repositório (ZIP do GitHub)
2. Extraia os arquivos
3. Faça upload via File Manager ou FTP para `public_html/`
4. Mantenha a estrutura de diretórios intacta

### 2. Estrutura de Arquivos Esperada

Após o deploy, a estrutura deve ser:

```
public_html/
├── index.php              ← NOVO: Fallback para hostings sem .htaccess
├── .htaccess              ← Opcional (comentado por padrão)
├── .env                   ← Criar manualmente (veja passo 3)
├── .gitignore
├── composer.json
├── composer.lock
├── config/
├── database/
├── public/
│   ├── .htaccess         ← Roteamento interno
│   ├── index.php         ← Front Controller real
│   └── ...
├── src/
├── storage/
├── themes/
└── vendor/               ← Criado após composer install
```

### 3. Criar Arquivo .env

1. No File Manager da Hostinger, navegue até `public_html/`
2. Crie um novo arquivo chamado `.env`
3. Use como base o arquivo `env.example.hostinger-single` do repositório
4. Preencha com seus dados reais:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pontodogolfeoutlet.com.br

APP_MODE=single
DEFAULT_TENANT_ID=1

DB_HOST=srv1075.hstgr.io
DB_PORT=3306
DB_NAME=u426126796_pontodogolpe
DB_USER=u426126796_pontodogolfe
DB_PASS=SUA_SENHA_REAL_AQUI

SESSION_NAME=ECOMMERCE_SESSION
```

**⚠️ IMPORTANTE:**
- `APP_MODE=single` para instalações independentes
- `DEFAULT_TENANT_ID=1` (ou o ID do seu tenant)
- Substitua `SUA_SENHA_REAL_AQUI` pela senha real do banco

### 4. Executar Composer Install

Via SSH ou terminal da Hostinger:

```bash
cd public_html
composer install --no-dev --optimize-autoloader
```

Isso criará a pasta `vendor/` com todas as dependências necessárias.

### 5. Configurar Permissões

Via SSH:

```bash
cd public_html
chmod 755 public/
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 644 index.php
chmod 644 .env
chmod 644 .htaccess
chmod -R 755 public/uploads/
chmod -R 755 storage/
```

### 6. Executar Migrations

Via SSH:

```bash
cd public_html
php database/run_migrations.php
```

Isso criará todas as tabelas necessárias no banco de dados.

### 7. Executar Seed (Opcional)

Se necessário, execute o seed para criar dados iniciais:

```bash
php database/run_seed.php
```

---

## 🔍 Como Funciona: index.php da Raiz vs public/index.php

### index.php na Raiz (`public_html/index.php`)

**Função:** Fallback para hostings que apontam DocumentRoot para a raiz

**Quando é usado:**
- Quando o DocumentRoot aponta para `public_html/` (não para `public_html/public/`)
- Quando o `.htaccess` não está sendo processado (AllowOverride restrito)
- Quando o provedor não permite configuração de VirtualHost

**Comportamento:**
- Verifica se `public/index.php` existe
- Se existir, inclui diretamente (bypass do `.htaccess`)
- Se não existir, mostra erro de configuração

**Vantagem:** Funciona mesmo sem `.htaccess` ou configuração especial do Apache

### public/index.php (`public_html/public/index.php`)

**Função:** Front Controller real da aplicação

**Quando é usado:**
- Sempre (seja chamado pelo `index.php` da raiz ou diretamente)
- Contém toda a lógica de roteamento, middleware, controllers

**Comportamento:**
- Carrega autoloader e variáveis de ambiente
- Resolve tenant (single ou multi)
- Processa rotas e renderiza views

**Vantagem:** Lógica centralizada, funciona em qualquer cenário

---

## ⚙️ Configuração do Modo Single vs Multi

### Modo Single (Instalações Independentes)

**Configuração no .env:**
```env
APP_MODE=single
DEFAULT_TENANT_ID=1
```

**Comportamento:**
- Usa sempre o tenant especificado em `DEFAULT_TENANT_ID`
- Não precisa cadastrar domínio em `tenant_domains`
- Ideal para uma loja por servidor
- **Usado pelo Ponto do Golfe**

### Modo Multi (Instalações Multi-tenant)

**Configuração no .env:**
```env
APP_MODE=multi
```

**Comportamento:**
- Resolve tenant pelo domínio (`HTTP_HOST`)
- Precisa cadastrar domínios em `tenant_domains`
- Ideal para plataforma SaaS com múltiplas lojas
- **Usado pela instalação principal**

---

## 🔧 Troubleshooting

### Erro 403 Forbidden

**Causa:** DocumentRoot não aponta para `public_html/` ou `index.php` não existe

**Solução:**
1. Verifique se `public_html/index.php` existe
2. Verifique se `public_html/public/index.php` existe
3. Teste acessar diretamente: `https://pontodogolfeoutlet.com.br/public/index.php`
4. Se funcionar, o problema é no `index.php` da raiz ou permissões

### Erro "Tenant não encontrado"

**Causa:** Modo multi sem domínio cadastrado OU modo single com tenant_id incorreto

**Solução:**
1. Verifique `APP_MODE` no `.env`
2. Se `single`, verifique `DEFAULT_TENANT_ID`
3. Se `multi`, execute o script `public/fix_domain.php` ou adicione domínio manualmente

### Erro "Class not found"

**Causa:** Composer não foi executado

**Solução:**
```bash
cd public_html
composer install --no-dev --optimize-autoloader
```

### Erro de Conexão com Banco

**Causa:** Credenciais incorretas no `.env`

**Solução:**
1. Verifique `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` no `.env`
2. Teste conexão via script PHP ou phpMyAdmin

---

## 📝 Checklist Final

- [ ] Arquivos deployados via Git ou upload manual
- [ ] Arquivo `.env` criado na raiz com configurações corretas
- [ ] `composer install` executado (pasta `vendor/` existe)
- [ ] Migrations executadas (tabelas criadas no banco)
- [ ] Permissões configuradas corretamente
- [ ] `APP_MODE=single` configurado no `.env`
- [ ] `DEFAULT_TENANT_ID` configurado corretamente
- [ ] Teste de acesso: `https://pontodogolfeoutlet.com.br/`
- [ ] Teste de admin: `https://pontodogolfeoutlet.com.br/admin/login`

---

## 🔄 Histórico de Versões

### 2025-12-09 - Versão 1.0
- ✅ Implementado `index.php` de fallback na raiz
- ✅ Refatorada detecção de caminho base em `public/index.php`
- ✅ `.htaccess` tornado opcional (regras comentadas)
- ✅ Criado `env.example.hostinger-single` como referência
- ✅ Documentação completa de deploy para instalações independentes

---

## 📚 Referências

- [Auditoria 403](AUDITORIA_403_PRODUCAO.md) - Análise completa do problema
- [Deploy Hostinger Geral](DEPLOY_HOSTINGER.md) - Guia geral de deploy
- [Troubleshooting 403](TROUBLESHOOTING_403_PRODUCAO.md) - Soluções para erro 403

---

**Última atualização:** 2025-12-09

