# ⚡ Configuração Inicial Rápida

Este guia resolve os dois problemas mais comuns após a instalação:
- ✗ Arquivo .env não encontrado
- ✗ Banco ecommerce_db não existe

## 🎯 Passo a Passo

### 1️⃣ Criar o Banco de Dados

#### Opção A: Via phpMyAdmin (Recomendado)

1. Abra no navegador:
   ```
   http://localhost/phpmyadmin
   ```

2. Clique na aba **"Databases"** ou **"Bancos de dados"**

3. Em **"Database name"**, digite: `ecommerce_db`

4. Em **"Collation"**, escolha: `utf8mb4_unicode_ci`

5. Clique em **"Create"** ou **"Criar"**

✅ Pronto! O banco foi criado.

#### Opção B: Via Linha de Comando

Abra o MySQL no terminal e execute:

```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2️⃣ Criar o Arquivo .env

O arquivo `.env` já foi criado automaticamente na raiz do projeto com as configurações padrão.

**Localização:** `C:\xampp\htdocs\ecommerce-v1.0\.env`

**Conteúdo padrão:**
```env
APP_ENV=local
APP_DEBUG=true

APP_MODE=single
DEFAULT_TENANT_ID=1

APP_URL=http://localhost/ecommerce-v1.0/public

DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecommerce_db
DB_USER=root
DB_PASS=

SESSION_NAME=ECOMMERCE_SESSION
```

**Se precisar ajustar:**
- `DB_PASS` - Senha do MySQL (se tiver configurado)
- `DB_USER` - Usuário do MySQL (padrão: root)
- `APP_URL` - URL base do projeto

### 3️⃣ Rodar Migrations e Seed

Agora que o banco existe e o `.env` está configurado, vamos criar as tabelas e dados básicos.

#### No PowerShell ou CMD:

```bash
# Ir para a pasta do projeto
cd C:\xampp\htdocs\ecommerce-v1.0

# Rodar migrations (cria todas as tabelas)
C:\xampp\php\php.exe database\run_migrations.php

# Rodar seed (cria tenant demo e usuários)
C:\xampp\php\php.exe database\run_seed.php
```

**O que cada comando faz:**

- `run_migrations.php` - Cria todas as tabelas do banco de dados
- `run_seed.php` - Cria:
  - Tenant demo (ID: 1, slug: loja-demo)
  - Domínio localhost
  - Platform admin: `admin@platform.local` / `admin123`
  - Store admin: `contato@pixel12digital.com.br` / `admin123`

### 4️⃣ (Opcional) Importar Produtos

Se você tem a pasta `exportacao-produtos/` com os produtos:

```bash
C:\xampp\php\php.exe database\import_products.php
```

**Nota:** Se já tiver importado antes, o script detecta e pula produtos existentes (sem duplicar).

### 5️⃣ Testar

Agora teste no navegador:

#### 1. Script de Teste:
```
http://localhost/ecommerce-v1.0/public/test.php
```

**Deve mostrar tudo ✓:**
- ✓ Autoloader carregado
- ✓ Arquivo .env existe
- ✓ Conexão com banco estabelecida
- ✓ Tenant resolvido
- ✓ Rotas configuradas
- ✓ Views existem

#### 2. Rotas de Login:

**Platform Admin:**
```
http://localhost/ecommerce-v1.0/public/admin/platform/login
```
- Email: `admin@platform.local`
- Senha: `admin123`

**Store Admin:**
```
http://localhost/ecommerce-v1.0/public/admin/login
```
- Email: `contato@pixel12digital.com.br`
- Senha: `admin123`

## ✅ Checklist Final

- [ ] Banco `ecommerce_db` criado
- [ ] Arquivo `.env` criado na raiz do projeto
- [ ] Migrations executadas (`run_migrations.php`)
- [ ] Seed executado (`run_seed.php`)
- [ ] Test.php mostra tudo ✓
- [ ] Login Platform Admin funciona
- [ ] Login Store Admin funciona

## 🐛 Se Ainda Der Erro

### Erro: "Unknown database 'ecommerce_db'"
- ✅ Verifique se o banco foi criado (passo 1)
- ✅ Verifique se o nome no `.env` está correto: `DB_NAME=ecommerce_db`

### Erro: "Arquivo .env não encontrado"
- ✅ Verifique se o arquivo `.env` existe na raiz: `C:\xampp\htdocs\ecommerce-v1.0\.env`
- ✅ Verifique se não está com nome errado (`.env.txt` ou `env.txt`)

### Erro: "Access denied for user"
- ✅ Verifique `DB_USER` e `DB_PASS` no `.env`
- ✅ Teste a conexão no phpMyAdmin

### Erro: "Tenant não encontrado"
- ✅ Execute o seed: `C:\xampp\php\php.exe database\run_seed.php`
- ✅ Verifique se `DEFAULT_TENANT_ID=1` no `.env`

## 📝 Resumo dos Comandos

```bash
# 1. Criar banco (via phpMyAdmin ou SQL)
# 2. Arquivo .env já foi criado automaticamente

# 3. Rodar migrations
C:\xampp\php\php.exe database\run_migrations.php

# 4. Rodar seed
C:\xampp\php\php.exe database\run_seed.php

# 5. (Opcional) Importar produtos
C:\xampp\php\php.exe database\import_products.php
```

---

**Após seguir esses passos, o sistema deve estar 100% funcional!** ✅

