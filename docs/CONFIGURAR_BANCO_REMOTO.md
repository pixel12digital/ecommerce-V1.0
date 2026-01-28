# 🔌 Configurar Banco de Dados Remoto

## 📋 Pré-requisitos

Para conectar a um banco de dados remoto, você precisa:

1. **Credenciais do banco remoto:**
   - Host (ex: `srv1075.hstgr.io` ou `mysql.exemplo.com`)
   - Porta (geralmente `3306`)
   - Nome do banco
   - Usuário
   - Senha

2. **Acesso autorizado:**
   - O IP do seu servidor deve estar autorizado no banco remoto
   - A porta 3306 deve estar aberta no firewall

## 🔧 Configuração

### 1. Criar arquivo `.env`

Na raiz do projeto (`C:\xampp\htdocs\ecommerce-v1.0\.env`), crie ou edite o arquivo `.env`:

```env
# Ambiente
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

# Modo de operação
APP_MODE=single
DEFAULT_TENANT_ID=1

# ============================================
# BANCO DE DADOS REMOTO
# ============================================
DB_HOST=seu_host_remoto_aqui
DB_PORT=3306
DB_NAME=nome_do_banco
DB_USER=usuario_banco
DB_PASS=senha_banco

# Sessão
SESSION_NAME=ECOMMERCE_SESSION
```

### 2. Exemplo Real (Hostinger)

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
DB_PASS=SUA_SENHA_AQUI

SESSION_NAME=ECOMMERCE_SESSION
```

## ✅ Verificar Configuração

### Opção 1: Via Navegador

Acesse:
```
http://localhost/ecommerce-v1.0/public/verificar_config_correios.php
```

A página mostrará:
- ✅ Se a conexão foi estabelecida
- ❌ Se houve erro (com detalhes)
- 📊 Todas as configurações Correios salvas no banco

### Opção 2: Via Script PHP

Execute:
```bash
C:\xampp\php\php.exe verificar_config_correios.php
```

## 🔍 Troubleshooting

### Erro: "Nenhuma conexão pôde ser feita porque a máquina de destino as recusou ativamente"

**Possíveis causas:**
1. **Host incorreto** - Verifique se o `DB_HOST` está correto
2. **Porta bloqueada** - Verifique se a porta 3306 está aberta
3. **IP não autorizado** - O banco remoto precisa autorizar seu IP
4. **Firewall** - O firewall pode estar bloqueando a conexão

**Soluções:**
- Verifique as credenciais no painel do seu hosting
- Autorize o IP do seu servidor no banco remoto
- Verifique se o MySQL está rodando no servidor remoto

### Erro: "Access denied for user"

**Causa:** Credenciais incorretas

**Solução:**
- Verifique `DB_USER` e `DB_PASS` no arquivo `.env`
- Confirme as credenciais no painel do hosting

### Erro: "Unknown database"

**Causa:** Nome do banco incorreto

**Solução:**
- Verifique `DB_NAME` no arquivo `.env`
- Confirme o nome do banco no painel do hosting

## 📝 Notas Importantes

1. **Segurança:**
   - ⚠️ **NUNCA** commite o arquivo `.env` no Git (já está no `.gitignore`)
   - ⚠️ Mantenha as credenciais seguras
   - ⚠️ Use senhas fortes

2. **Backup:**
   - Mantenha um backup do arquivo `.env` em local seguro
   - Não compartilhe as credenciais

3. **Produção:**
   - Em produção, use `APP_DEBUG=false`
   - Use `APP_ENV=production`
   - Configure `APP_URL` com o domínio real

## 🔗 Arquivos Relacionados

- `config/database.php` - Configuração do banco
- `src/Core/Database.php` - Classe de conexão
- `public/verificar_config_correios.php` - Script de verificação
