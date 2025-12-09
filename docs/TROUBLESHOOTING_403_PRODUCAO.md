# 🔧 Troubleshooting - Erro 403 em Produção

## ❌ Problema

Ao acessar `https://pontodogolfeoutlet.com.br/`, você recebe:
```
403 Forbidden
Access to this resource on the server is denied!
```

## 🔍 Diagnóstico Passo a Passo

### 1. Teste de Acesso Básico

Acesse: `https://pontodogolfeoutlet.com.br/test_access.php`

Este arquivo mostra:
- Informações do servidor
- Verificação de arquivos
- Permissões
- Status do mod_rewrite
- Conexão com banco

**Se conseguir acessar `test_access.php`:**
- O problema é no `.htaccess` ou roteamento
- Continue com os passos abaixo

**Se NÃO conseguir acessar `test_access.php`:**
- Problema de permissões ou configuração do Apache
- Verifique permissões via SSH: `chmod 644 public/test_access.php`

### 2. Verificar Estrutura de Arquivos

Via SSH ou File Manager, verifique:

```
public_html/
├── .htaccess          ← DEVE existir na raiz
├── .env              ← DEVE existir na raiz
├── public/
│   ├── .htaccess     ← DEVE existir
│   ├── index.php     ← DEVE existir
│   └── test_access.php ← Criar para teste
├── vendor/           ← Criado após composer install
└── ...
```

### 3. Verificar Permissões

Execute via SSH:

```bash
cd public_html
chmod 644 .htaccess
chmod 644 .env
chmod 755 public/
chmod 644 public/.htaccess
chmod 644 public/index.php
chmod 644 public/test_access.php
```

### 4. Verificar Conteúdo do .htaccess na Raiz

O arquivo `public_html/.htaccess` deve conter:

```apache
RewriteEngine On
Options -Indexes

RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.*)$ - [L]

RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ - [L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

### 5. Teste Alternativo: Acessar Diretamente

Tente acessar diretamente:
- `https://pontodogolfeoutlet.com.br/public/index.php`
- `https://pontodogolfeoutlet.com.br/public/index.php/admin/login`

**Se funcionar:**
- O problema é no `.htaccess` da raiz
- O código PHP está funcionando

**Se não funcionar:**
- Problema mais profundo (permissões, PHP, etc.)

### 6. Verificar Logs de Erro

Via SSH, execute:

```bash
tail -f /var/log/apache2/error.log
# ou
tail -f /home/usuario/logs/error.log
```

Tente acessar o site e veja os erros no log.

### 7. Solução Alternativa: Mover Conteúdo de public/ para Raiz

Se nada funcionar, você pode mover o conteúdo de `public/` para `public_html/`:

**⚠️ ATENÇÃO:** Isso requer ajustes no código!

**Passos:**
1. Mover conteúdo de `public/` para `public_html/`
2. Ajustar caminhos no `index.php` (trocar `__DIR__ . '/../'` por `__DIR__ . '/'`)
3. Ajustar `.htaccess` para não redirecionar

**NÃO RECOMENDADO** - Melhor resolver o problema do `.htaccess`

## ✅ Soluções Comuns

### Solução 1: .htaccess com Caminho Absoluto

Se o caminho relativo não funcionar, tente caminho absoluto:

```apache
RewriteEngine On
Options -Indexes

RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.*)$ - [L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /public/index.php [QSA,L]
```

### Solução 2: Verificar AllowOverride

O Apache precisa permitir `.htaccess`. Na Hostinger, geralmente já está habilitado, mas verifique.

### Solução 3: Desabilitar Temporariamente .htaccess

Para testar, renomeie temporariamente:
```bash
mv .htaccess .htaccess.bak
```

Se funcionar sem `.htaccess`, o problema é nas regras de rewrite.

### Solução 4: Criar index.php na Raiz (Temporário)

Crie um `index.php` na raiz de `public_html/`:

```php
<?php
require __DIR__ . '/public/index.php';
```

Isso força o redirecionamento via PHP ao invés de `.htaccess`.

## 📞 Próximos Passos

1. Acesse `test_access.php` e veja os resultados
2. Verifique os logs de erro do Apache
3. Teste acessar diretamente `public/index.php`
4. Se necessário, entre em contato com o suporte da Hostinger

