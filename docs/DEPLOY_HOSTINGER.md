# 🚀 Guia de Deploy na Hostinger

## ✅ Checklist de Deploy

### 1. Estrutura de Diretórios Esperada

Após o deploy via Git, a estrutura deve ser:

```
public_html/
├── .env                    ← Criar manualmente
├── .htaccess              ← Deve estar na raiz
├── composer.json
├── composer.lock
├── config/
├── database/
├── public/
│   ├── .htaccess          ← IMPORTANTE: deve existir
│   ├── index.php          ← Front Controller
│   └── admin/
├── src/
├── storage/
├── themes/
└── vendor/                 ← Criado após composer install
```

### 2. Configuração do DocumentRoot

**IMPORTANTE:** O DocumentRoot do Apache deve apontar para `public_html/public/`, não para `public_html/`.

**Como verificar/configurar:**
1. Acesse o painel da Hostinger
2. Vá em "Sites" → "Configuração de PHP"
3. Verifique o DocumentRoot ou configure um VirtualHost apontando para `public_html/public/`

**Alternativa:** Se não conseguir alterar o DocumentRoot, você pode:
- Criar um `.htaccess` na raiz (`public_html/.htaccess`) que redireciona tudo para `public/`
- Ou mover o conteúdo de `public/` para `public_html/` diretamente

### 3. Arquivo .env

O arquivo `.env` deve estar na raiz do projeto (`public_html/.env`), não dentro de `public/`.

**Conteúdo mínimo:**
```env
APP_MODE=multi
DEFAULT_TENANT_ID=1

DB_HOST=srv1075.hstgr.io
DB_PORT=3306
DB_NAME=u426126796_pontodogolpe
DB_USER=u426126796_pontodogolfe
DB_PASS=Los@ngo#081081

APP_URL=https://pontodogolfeoutlet.com.br
APP_ENV=production
APP_DEBUG=false

SESSION_NAME=ECOMMERCE_SESSION
```

### 4. Composer Install

**OBRIGATÓRIO:** Execute via SSH ou terminal da Hostinger:

```bash
cd public_html
composer install --no-dev --optimize-autoloader
```

Isso criará a pasta `vendor/` com todas as dependências.

### 5. Permissões de Arquivos

Execute via SSH:

```bash
cd public_html
chmod 755 public/
chmod 644 public/.htaccess
chmod 644 .env
chmod -R 755 public/uploads/
chmod -R 755 storage/
```

### 6. Migrations

Execute as migrations para criar as tabelas:

```bash
cd public_html
php database/run_migrations.php
```

### 7. Seed (Opcional)

Se necessário, execute o seed:

```bash
php database/run_seed.php
```

## 🔧 Troubleshooting - Erro 403 Forbidden

### Causa 1: DocumentRoot Incorreto

**Sintoma:** Erro 403 ao acessar qualquer URL

**Solução:**
- Verifique se o DocumentRoot aponta para `public_html/` (raiz) ou `public_html/public/`
- Se apontar para raiz, certifique-se de que `public_html/.htaccess` existe e contém:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

**Nota:** O `index.php` na raiz então inclui `public/index.php`, completando o fluxo.

### Causa 2: .htaccess Não Funcionando

**Sintoma:** Erro 403 ou listagem de diretórios

**Solução:**
- Verifique se `mod_rewrite` está habilitado no Apache
- Verifique se `AllowOverride All` está configurado
- Confirme que o arquivo `public/.htaccess` existe

### Causa 3: Permissões Incorretas

**Sintoma:** Erro 403 em arquivos específicos

**Solução:**
```bash
chmod 755 public/
chmod 644 public/.htaccess
chmod 644 public/index.php
```

### Causa 4: Composer Não Executado

**Sintoma:** Erro 500 ou "Class not found"

**Solução:**
```bash
composer install --no-dev
```

### Causa 5: .env Não Criado ou Incorreto

**Sintoma:** Erro de conexão com banco ou variáveis não definidas

**Solução:**
- Verifique se `.env` existe em `public_html/.env`
- Verifique se as credenciais do banco estão corretas
- Verifique se `APP_URL` está correto

## 📝 Passo a Passo Completo

1. **Deploy via Git na Hostinger:**
   - Repositório: `https://github.com/pixel12digital/ecommerce-V1.0.git`
   - Branch: `main`
   - Diretório: vazio (deploy em `public_html`)

2. **Acessar via SSH ou File Manager:**
   - Navegar até `public_html/`

3. **Criar arquivo `.env`:**
   - Copiar conteúdo acima
   - Salvar em `public_html/.env`

4. **Executar Composer:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

5. **Configurar Permissões:**
   ```bash
   chmod 755 public/
   chmod 644 public/.htaccess
   chmod 644 .env
   chmod -R 755 public/uploads/
   ```

6. **Executar Migrations:**
   ```bash
   php database/run_migrations.php
   ```

7. **Verificar DocumentRoot:**
   - Deve apontar para `public_html/public/`
   - Ou criar `.htaccess` na raiz redirecionando para `public/`

8. **Testar acesso:**
   - `https://pontodogolfeoutlet.com.br/`
   - `https://pontodogolfeoutlet.com.br/admin/login`

## ⚠️ Notas Importantes

- O arquivo `.env` NÃO deve ser commitado no Git (já está no `.gitignore`)
- O arquivo `.env` deve estar na RAIZ do projeto, não em `public/`
- O DocumentRoot pode apontar para `public/` OU para a raiz (há `index.php` de fallback)
- Sempre execute `composer install` após o deploy
- Verifique as permissões de arquivos e diretórios
- **IMPORTANTE - Caminhos de Mídia:**
  - Em **desenvolvimento** (DocumentRoot = `public/`): arquivos em `public/uploads/tenants/...`
  - Em **produção Hostinger** (DocumentRoot = `public_html/`): arquivos em `public_html/uploads/tenants/...` (NÃO em `public_html/public/uploads/...`)
  - O código sempre gera URLs como `/uploads/tenants/...` (sem `/public`)
  - Se as imagens não aparecerem, verifique se estão no lugar correto conforme o DocumentRoot

## 🔄 Solução para Hostings com Restrições (403/404 Forbidden)

### Problema: Rotas Amigáveis Retornam 404 da Hostinger

**Sintoma:** 
- `/` funciona (loja abre)
- `/admin/login` retorna 404 da Hostinger (não passa pelo sistema)

**Causa:** `.htaccess` não está reescrevendo rotas para `index.php`

**Solução:**

O projeto possui um **`index.php` de fallback na raiz** que funciona em conjunto com `.htaccess`:

**Fluxo correto:**
1. `.htaccess` na raiz reescreve `/admin/login` → `index.php` (raiz)
2. `index.php` (raiz) inclui `public/index.php`
3. `public/index.php` processa a rota e renderiza a view

**Configuração necessária:**

O `.htaccess` na raiz (`public_html/.htaccess`) deve conter:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

**Importante:**
- Sem este `.htaccess`, apenas `/` funciona (porque Apache encontra `index.php` diretamente)
- Rotas amigáveis como `/admin/login` retornam 404 da Hostinger
- O `index.php` na raiz garante que, quando o rewrite funcionar, tudo seja processado corretamente

**Para mais detalhes:** Veja [Deploy Hostinger - Instalação Independente](DEPLOY_HOSTINGER_PONTODOGOLFE.md)

## 🔍 Verificação Rápida

Execute estes comandos via SSH para verificar:

```bash
# Verificar estrutura
ls -la public_html/
ls -la public_html/public/

# Verificar .env
cat public_html/.env

# Verificar .htaccess
cat public_html/public/.htaccess

# Verificar vendor (deve existir após composer install)
ls -la public_html/vendor/
```

