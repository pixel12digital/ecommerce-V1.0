# 🔧 Troubleshooting - Erro 404

Este guia ajuda a resolver o erro 404 ao acessar as rotas do sistema.

## ❌ Problema

Ao acessar `http://localhost/admin/platform/login` ou outras rotas, você recebe:
```
404 Not Found
The requested URL was not found on this server.
```

## ✅ Soluções

### 1. Verificar DocumentRoot do Apache

O Apache precisa apontar para a pasta `public/` do projeto, não para a raiz.

**Verificar configuração do Apache:**

1. Abra o arquivo `httpd.conf` do XAMPP (geralmente em `C:\xampp\apache\conf\httpd.conf`)

2. Procure por `DocumentRoot` e verifique se está assim:

```apache
DocumentRoot "C:/xampp/htdocs/ecommerce-v1.0/public"
<Directory "C:/xampp/htdocs/ecommerce-v1.0/public">
    AllowOverride All
    Require all granted
</Directory>
```

**OU** configure um VirtualHost:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/ecommerce-v1.0/public"
    
    <Directory "C:/xampp/htdocs/ecommerce-v1.0/public">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>
</VirtualHost>
```

3. Reinicie o Apache após alterar a configuração.

### 2. Verificar se mod_rewrite está habilitado

O módulo `mod_rewrite` é necessário para o `.htaccess` funcionar.

1. Abra `httpd.conf`
2. Procure por `#LoadModule rewrite_module` e remova o `#`:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

3. Reinicie o Apache.

### 3. Verificar AllowOverride

O Apache precisa permitir que o `.htaccess` seja processado.

No `httpd.conf`, certifique-se de que há:

```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
</Directory>
```

### 4. Verificar estrutura de pastas

Certifique-se de que os arquivos existem:

```
ecommerce-v1.0/
├── public/
│   ├── .htaccess      ← Deve existir
│   └── index.php      ← Deve existir
├── src/
├── config/
└── ...
```

### 5. Testar acesso direto ao index.php

Tente acessar diretamente:
```
http://localhost/index.php
```

Se funcionar, o problema é no `.htaccess` ou no `mod_rewrite`.

### 6. Verificar logs do Apache

Verifique os logs de erro do Apache em:
```
C:\xampp\apache\logs\error.log
```

Isso pode mostrar erros específicos.

## 🚀 Solução Rápida (XAMPP)

### Opção 1: Usar a pasta public como DocumentRoot

1. Abra o XAMPP Control Panel
2. Clique em "Config" ao lado do Apache
3. Selecione "httpd.conf"
4. Procure por `DocumentRoot` e altere para:

```apache
DocumentRoot "C:/xampp/htdocs/ecommerce-v1.0/public"
<Directory "C:/xampp/htdocs/ecommerce-v1.0/public">
    AllowOverride All
    Require all granted
</Directory>
```

5. Salve e reinicie o Apache

### Opção 2: Criar VirtualHost

1. Abra `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
2. Adicione:

```apache
<VirtualHost *:80>
    ServerName ecommerce.local
    DocumentRoot "C:/xampp/htdocs/ecommerce-v1.0/public"
    
    <Directory "C:/xampp/htdocs/ecommerce-v1.0/public">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>
</VirtualHost>
```

3. Edite `C:\Windows\System32\drivers\etc\hosts` e adicione:
```
127.0.0.1    ecommerce.local
```

4. Acesse: `http://ecommerce.local/admin/platform/login`

## ✅ Verificação Rápida

Execute estes comandos no PowerShell para verificar:

```powershell
# Verificar se .htaccess existe
Test-Path public\.htaccess

# Verificar se index.php existe
Test-Path public\index.php

# Verificar conteúdo do .htaccess
Get-Content public\.htaccess
```

## 📝 Checklist

- [ ] DocumentRoot aponta para `public/`
- [ ] `mod_rewrite` está habilitado
- [ ] `AllowOverride All` está configurado
- [ ] Arquivo `.htaccess` existe em `public/`
- [ ] Arquivo `index.php` existe em `public/`
- [ ] Apache foi reiniciado após alterações

## 🔍 Teste Final

Após aplicar as correções, teste:

1. **Acesso direto:** `http://localhost/ecommerce-v1.0/public/index.php` (deve mostrar mensagem)
2. **Script de teste:** `http://localhost/ecommerce-v1.0/public/test.php` (deve mostrar diagnóstico)
3. **Rota de login:** `http://localhost/ecommerce-v1.0/public/admin/platform/login` (deve mostrar formulário)
4. **Rota store:** `http://localhost/ecommerce-v1.0/public/admin/login` (deve mostrar formulário)

Se ainda não funcionar, verifique os logs do Apache para mais detalhes.

