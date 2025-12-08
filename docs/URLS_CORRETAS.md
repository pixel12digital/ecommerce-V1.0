# 🔗 URLs Corretas do Sistema

Este documento lista todas as URLs corretas para acessar o sistema em desenvolvimento local.

## 📋 URLs Principais

### Script de Teste/Diagnóstico
```
http://localhost/ecommerce-v1.0/public/test.php
```
- Verifica autoloader, .env, banco de dados, tenant, rotas e views
- Use para diagnosticar problemas de configuração

### Platform Admin (Super Admin)
```
Login:     http://localhost/ecommerce-v1.0/public/admin/platform/login
Dashboard: http://localhost/ecommerce-v1.0/public/admin/platform
Logout:    http://localhost/ecommerce-v1.0/public/admin/platform/logout
```
**Credenciais:**
- Email: `admin@platform.local`
- Senha: `admin123`

### Store Admin (Admin da Loja)
```
Login:              http://localhost/ecommerce-v1.0/public/admin/login
Dashboard:          http://localhost/ecommerce-v1.0/public/admin
Atualizações:       http://localhost/ecommerce-v1.0/public/admin/system/updates
Logout:             http://localhost/ecommerce-v1.0/public/admin/logout
```
**Credenciais:**
- Email: `contato@pixel12digital.com.br`
- Senha: `admin123`

### Front-end (Em Desenvolvimento)
```
Home: http://localhost/ecommerce-v1.0/public/
```
- Atualmente mostra apenas mensagem informativa
- Será implementado na Fase 1

## 📝 Nota Importante

**Por que `/ecommerce-v1.0/public/`?**

O Apache do XAMPP está configurado para servir arquivos de `C:\xampp\htdocs\`. Como o projeto está em `C:\xampp\htdocs\ecommerce-v1.0\`, e o DocumentRoot aponta para a pasta `public/`, a URL completa inclui o caminho completo do projeto.

**Alternativa:** Configure um VirtualHost no Apache para usar apenas `http://localhost/` (veja `docs/TROUBLESHOOTING_404.md`).

## ✅ Checklist de Acesso

- [ ] Composer instalado (`vendor/autoload.php` existe)
- [ ] Arquivo `.env` configurado
- [ ] Banco de dados criado
- [ ] Migrations executadas
- [ ] Seed executado
- [ ] Apache rodando
- [ ] Acessar `http://localhost/ecommerce-v1.0/public/test.php` para verificar

## 🔍 Referências

- [Acessos e URLs](ACESSOS_E_URLS.md) - Documentação completa de acessos
- [Troubleshooting 404](TROUBLESHOOTING_404.md) - Resolver problemas de acesso

