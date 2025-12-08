# 🔧 Solução para 404 em /admin/login

## ❌ Problema

Ao acessar `http://localhost/ecommerce-v1.0/public/admin/login`, você recebe:
```
404 - Página não encontrada
```

## 🔍 Causa

O Apache não está processando o `.htaccess` corretamente, então as rotas não estão sendo redirecionadas para `index.php`.

## ✅ Solução Rápida

### Opção 1: Acessar via index.php diretamente (Teste Rápido)

Para testar se o código está funcionando, acesse:
```
http://localhost/ecommerce-v1.0/public/index.php/admin/login
```

Se funcionar, o problema é apenas no `.htaccess` ou `mod_rewrite`.

### Opção 2: Configurar Apache corretamente (Solução Definitiva)

#### Passo 1: Verificar se mod_rewrite está habilitado

1. Abra: `C:\xampp\apache\conf\httpd.conf`
2. Procure por: `#LoadModule rewrite_module`
3. Se tiver `#` na frente, remova:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```

#### Passo 2: Configurar AllowOverride

No mesmo arquivo `httpd.conf`, procure por:
```apache
<Directory "C:/xampp/htdocs">
```

E certifique-se de que está assim:
```apache
<Directory "C:/xampp/htdocs">
    AllowOverride All
    Require all granted
    Options Indexes FollowSymLinks
</Directory>
```

#### Passo 3: Reiniciar Apache

1. Abra o **XAMPP Control Panel**
2. Clique em **Stop** no Apache
3. Aguarde alguns segundos
4. Clique em **Start** no Apache

#### Passo 4: Testar novamente

Acesse:
```
http://localhost/ecommerce-v1.0/public/admin/login
```

### Opção 3: Configurar VirtualHost (Recomendado para Produção)

Se quiser usar apenas `http://localhost/` sem o caminho completo:

1. Abra: `C:\xampp\apache\conf\httpd.conf`
2. Procure por: `#Include conf/extra/httpd-vhosts.conf`
3. Remova o `#`:
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

4. Abra: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
5. Adicione no final:
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

6. Reinicie o Apache

Agora você pode acessar:
```
http://localhost/admin/login
```

## 🧪 Verificar se está funcionando

1. Acesse: `http://localhost/ecommerce-v1.0/public/test.php`
   - Deve mostrar todos os testes ✓

2. Acesse: `http://localhost/ecommerce-v1.0/public/index.php/admin/login`
   - Se funcionar, o código está OK, só falta configurar o Apache

3. Acesse: `http://localhost/ecommerce-v1.0/public/admin/login`
   - Após configurar o Apache, deve funcionar

## 📝 Notas

- O arquivo `.htaccess` está em `public/.htaccess` e está correto
- O `index.php` está configurado corretamente
- O problema é apenas na configuração do Apache

## 🔗 Referências

- [Troubleshooting 404](TROUBLESHOOTING_404.md) - Guia completo
- [Configuração Inicial Rápida](CONFIGURACAO_INICIAL_RAPIDA.md) - Setup inicial



