# 🔍 AUDITORIA COMPLETA - Erro 403 em Produção

**Data:** 2025-12-09  
**Domínio:** pontodogolfeoutlet.com.br  
**Ambiente:** Hostinger (Produção)  
**Status:** ❌ Erro 403 Forbidden persiste

---

## 📋 SUMÁRIO EXECUTIVO

O sistema está retornando **403 Forbidden** ao acessar `https://pontodogolfeoutlet.com.br/` em produção. Esta auditoria mapeia todos os aspectos do problema sem fazer alterações, apenas documentando o estado atual e possíveis causas.

---

## 🏗️ 1. ESTRUTURA DE ARQUIVOS ESPERADA vs REAL

### Estrutura Esperada (após deploy Git)

```
public_html/                          ← DocumentRoot do Apache
├── .htaccess                        ← DEVE existir (redireciona para public/)
├── .env                             ← DEVE existir (configurações)
├── .gitignore
├── composer.json
├── composer.lock
├── config/
│   ├── app.php
│   ├── database.php
│   └── paths.php
├── database/
│   ├── migrations/
│   └── seeds/
├── public/                          ← Pasta pública
│   ├── .htaccess                   ← DEVE existir (roteamento)
│   ├── index.php                   ← Front Controller (DEVE existir)
│   ├── test_access.php             ← Script de diagnóstico
│   ├── fix_domain.php              ← Script de correção
│   └── admin/
│       └── js/
│           └── media-picker.js
├── src/
├── storage/
├── themes/
└── vendor/                          ← Criado após composer install
```

### ✅ Verificações Necessárias (via File Manager/SSH)

- [ ] `public_html/.htaccess` existe?
- [ ] `public_html/.env` existe?
- [ ] `public_html/public/.htaccess` existe?
- [ ] `public_html/public/index.php` existe?
- [ ] `public_html/vendor/` existe? (após composer install)
- [ ] Permissões corretas?

---

## 🔧 2. ANÁLISE DOS ARQUIVOS .htaccess

### 2.1. `.htaccess` na Raiz (`public_html/.htaccess`)

**Conteúdo Atual:**
```apache
# .htaccess na raiz do projeto
# Redireciona todas as requisições para public/index.php
# Funciona tanto localmente quanto em produção

# Habilitar RewriteEngine
RewriteEngine On

# Desabilitar listagem de diretórios
Options -Indexes

# Permitir acesso direto a arquivos existentes na raiz
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.*)$ - [L]

# Permitir acesso direto a diretórios existentes (mas não listar conteúdo)
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ - [L]

# Redirecionar tudo que não for arquivo ou diretório para public/index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

**Análise:**
- ✅ `RewriteEngine On` - Correto
- ✅ `Options -Indexes` - Previne listagem de diretórios
- ⚠️ **PROBLEMA POTENCIAL:** A regra `RewriteCond %{REQUEST_FILENAME} -d` pode estar bloqueando acesso a diretórios antes de chegar ao rewrite final
- ⚠️ **PROBLEMA POTENCIAL:** Se o DocumentRoot aponta para `public_html/`, a regra `RewriteRule ^(.*)$ public/index.php` usa caminho relativo, que pode não funcionar dependendo da configuração do Apache

**Possíveis Problemas:**
1. **Caminho relativo vs absoluto:** `public/index.php` pode não ser resolvido corretamente
2. **Conflito com diretórios:** A regra de diretórios pode estar interferindo
3. **Ordem das regras:** A ordem pode estar causando bloqueio prematuro

### 2.2. `.htaccess` em `public/` (`public_html/public/.htaccess`)

**Conteúdo Atual:**
```apache
RewriteEngine On

# Desabilitar listagem de diretórios
Options -Indexes

# Permitir acesso direto a arquivos estáticos (JS, CSS, imagens, etc)
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.*)$ - [L]

# Redirecionar tudo para index.php (incluindo diretórios sem index.html/php)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Análise:**
- ✅ Estrutura correta para roteamento interno
- ✅ Permite arquivos estáticos
- ✅ Redireciona tudo para `index.php`

**Observação:** Este arquivo só é processado se a requisição chegar até `public/`. Se o `.htaccess` da raiz estiver bloqueando, este nunca será executado.

---

## 🌐 3. FLUXO DE REQUISIÇÃO HTTP

### 3.1. Requisição: `GET https://pontodogolfeoutlet.com.br/`

**Fluxo Esperado:**
1. Apache recebe requisição para `/`
2. DocumentRoot aponta para `public_html/`
3. Apache verifica se existe `index.php` ou `index.html` em `public_html/`
4. Se não existir, processa `.htaccess` em `public_html/`
5. `.htaccess` redireciona para `public/index.php`
6. Apache processa `public/index.php`
7. `public/index.php` carrega autoloader, `.env`, resolve tenant, roteia

**Fluxo Real (com erro 403):**
1. Apache recebe requisição para `/`
2. DocumentRoot aponta para `public_html/`
3. ❌ **ERRO 403** - Acesso negado antes de processar `.htaccess` ou PHP

**Possíveis Causas do 403:**
- Apache não permite acesso ao diretório raiz
- `.htaccess` não está sendo processado (AllowOverride desabilitado)
- Permissões de arquivo/diretório incorretas
- DocumentRoot configurado incorretamente
- Conflito com configurações do servidor

---

## ⚙️ 4. CONFIGURAÇÃO DO APACHE/SERVIDOR

### 4.1. DocumentRoot

**Cenários Possíveis:**

**Cenário A: DocumentRoot = `public_html/`**
- Requisição `/` → Apache procura `public_html/index.php` ou `public_html/index.html`
- Se não existir → Processa `public_html/.htaccess`
- `.htaccess` deve redirecionar para `public/index.php`
- ✅ **Este é o cenário esperado com nosso `.htaccess`**

**Cenário B: DocumentRoot = `public_html/public/`**
- Requisição `/` → Apache procura `public_html/public/index.php`
- Processa `public_html/public/.htaccess`
- Não precisa do `.htaccess` na raiz
- ⚠️ **Neste caso, nosso `.htaccess` na raiz não seria necessário**

**Cenário C: DocumentRoot = `public_html/` mas bloqueado**
- Apache bloqueia acesso antes de processar `.htaccess`
- ❌ **Causa do 403**

### 4.2. AllowOverride

**Necessário:**
```apache
<Directory "public_html">
    AllowOverride All
    Require all granted
</Directory>
```

**Se `AllowOverride None`:**
- `.htaccess` não é processado
- Pode causar 403 ou comportamento inesperado

### 4.3. mod_rewrite

**Necessário:**
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

**Se não habilitado:**
- Regras `RewriteRule` não funcionam
- Pode causar 403 ou 404

---

## 🔐 5. PERMISSÕES DE ARQUIVOS

### Permissões Esperadas

```bash
public_html/                   755 (drwxr-xr-x)
public_html/.htaccess         644 (-rw-r--r--)
public_html/.env              644 (-rw-r--r--)
public_html/public/          755 (drwxr-xr-x)
public_html/public/.htaccess 644 (-rw-r--r--)
public_html/public/index.php 644 (-rw-r--r--)
```

### Problemas Comuns

- **Permissões muito restritivas (600, 700):** Apache não consegue ler
- **Permissões muito abertas (777):** Risco de segurança, mas não causa 403
- **Proprietário incorreto:** Apache precisa ter acesso de leitura

---

## 🔄 6. ANÁLISE DO CÓDIGO PHP

### 6.1. `public/index.php` - Linhas 1-24

```php
<?php
// Tratamento de erros para desenvolvimento
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar variáveis de ambiente
$envFile = __DIR__ . '/../.env';
```

**Análise:**
- ✅ Caminho relativo `__DIR__ . '/../'` funciona se `index.php` está em `public/`
- ⚠️ Se `vendor/autoload.php` não existir → Erro fatal (mas seria 500, não 403)
- ⚠️ Se `.env` não existir → Continua (usa valores padrão)

### 6.2. `public/index.php` - Linhas 58-99 (Processamento de URI)

```php
// Obter URI e método
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Remover query string da URI
$uri = parse_url($uri, PHP_URL_PATH);

// Detectar e remover caminho base automaticamente
if (strpos($uri, '/ecommerce-v1.0/public') === 0) {
    $uri = substr($uri, strlen('/ecommerce-v1.0/public'));
}
elseif (strpos($uri, '/public') === 0 && $uri !== '/public' && $uri !== '/public/') {
    $uri = substr($uri, strlen('/public'));
}
```

**Análise:**
- ✅ Detecta caminho base automaticamente
- ⚠️ Se a URI vier como `/public/index.php` (do `.htaccess`), remove `/public` → fica `/index.php` → pode causar loop ou erro

### 6.3. TenantResolverMiddleware

```php
if ($mode === 'single') {
    $defaultTenantId = $config['default_tenant_id'] ?? 1;
    TenantContext::setFixedTenant($defaultTenantId);
} else {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    TenantContext::resolveFromHost($host);
}
```

**Análise:**
- ✅ Se `APP_MODE=single` → Usa tenant fixo (não precisa de domínio)
- ⚠️ Se `APP_MODE=multi` → Precisa de domínio em `tenant_domains`
- ✅ Domínio já foi adicionado via script (`pontodogolfeoutlet.com.br`)

---

## 🐛 7. PROBLEMAS IDENTIFICADOS

### Problema 1: Conflito de Regras no .htaccess da Raiz

**Localização:** `.htaccess` linha 15-18

```apache
# Permitir acesso direto a diretórios existentes (mas não listar conteúdo)
RewriteCond %{REQUEST_FILENAME} -d
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ - [L]
```

**Problema:**
- Esta regra permite acesso a diretórios existentes, mas pode estar interferindo com o fluxo
- Se `public_html/` for considerado um diretório, pode estar bloqueando antes de chegar ao rewrite final

**Impacto:** ⚠️ MÉDIO

### Problema 2: Caminho Relativo no RewriteRule

**Localização:** `.htaccess` linha 23

```apache
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

**Problema:**
- Usa caminho relativo `public/index.php`
- Dependendo da configuração do Apache, pode não ser resolvido corretamente
- Deveria usar caminho absoluto `/public/index.php` ou variável `%{DOCUMENT_ROOT}`

**Impacto:** ⚠️ ALTO

### Problema 3: Ordem das Regras RewriteCond

**Localização:** `.htaccess` linhas 11-23

**Problema:**
- A regra de arquivos (`-f`) vem antes da regra de diretórios (`-d`)
- Mas a regra de diretórios tem `[L]` que pode parar o processamento antes do rewrite final
- A ordem pode estar causando bloqueio prematuro

**Impacto:** ⚠️ MÉDIO

### Problema 4: DocumentRoot Não Configurado Corretamente

**Problema:**
- Se o DocumentRoot não apontar para `public_html/`, o `.htaccess` não será processado
- Se apontar para `public_html/public/`, o `.htaccess` da raiz não será usado

**Impacto:** ⚠️ ALTO

### Problema 5: AllowOverride Desabilitado

**Problema:**
- Se `AllowOverride None` ou `AllowOverride FileInfo` (sem `All`), o `.htaccess` pode não funcionar completamente
- Regras `RewriteEngine` podem ser bloqueadas

**Impacto:** ⚠️ ALTO

---

## 📊 8. MATRIZ DE CAUSAS PROVÁVEIS

| Causa | Probabilidade | Impacto | Evidência |
|-------|--------------|---------|-----------|
| DocumentRoot incorreto | 🔴 ALTA | ALTO | 403 antes de processar PHP |
| AllowOverride desabilitado | 🟡 MÉDIA | ALTO | `.htaccess` não funciona |
| Caminho relativo no RewriteRule | 🟡 MÉDIA | MÉDIO | Rewrite não resolve corretamente |
| Conflito de regras .htaccess | 🟢 BAIXA | MÉDIO | Regras podem estar bloqueando |
| Permissões incorretas | 🟢 BAIXA | BAIXO | Seria erro diferente |
| mod_rewrite desabilitado | 🟢 BAIXA | ALTO | Seria erro diferente |

---

## 🧪 9. TESTES DE DIAGNÓSTICO

### Teste 1: Acessar arquivo PHP diretamente

**URL:** `https://pontodogolfeoutlet.com.br/public/index.php`

**Resultado Esperado:**
- ✅ Se funcionar: Problema é no `.htaccess` da raiz
- ❌ Se 403: Problema é mais profundo (permissões, PHP, etc.)

### Teste 2: Acessar script de teste

**URL:** `https://pontodogolfeoutlet.com.br/public/test_access.php`

**Resultado Esperado:**
- ✅ Se funcionar: PHP está OK, problema é no roteamento
- ❌ Se 403: Problema é no acesso a `public/`

### Teste 3: Acessar arquivo estático

**URL:** `https://pontodogolfeoutlet.com.br/public/admin/js/media-picker.js`

**Resultado Esperado:**
- ✅ Se funcionar: Apache consegue servir arquivos de `public/`
- ❌ Se 403: Problema é no acesso a `public/` ou permissões

### Teste 4: Verificar se .htaccess está sendo processado

**Criar arquivo:** `public_html/test_rewrite.php`
```php
<?php
echo "Rewrite funcionou!";
```

**Acessar:** `https://pontodogolfeoutlet.com.br/test_rewrite`

**Resultado Esperado:**
- ✅ Se mostrar "Rewrite funcionou!": `.htaccess` está funcionando
- ❌ Se 403 ou 404: `.htaccess` não está sendo processado

---

## 📝 10. CHECKLIST DE VERIFICAÇÃO

### Via File Manager/SSH

- [ ] `public_html/.htaccess` existe e tem conteúdo correto?
- [ ] `public_html/public/.htaccess` existe e tem conteúdo correto?
- [ ] `public_html/public/index.php` existe?
- [ ] `public_html/.env` existe e tem credenciais corretas?
- [ ] `public_html/vendor/` existe? (após composer install)
- [ ] Permissões de `public_html/` são 755?
- [ ] Permissões de `public_html/.htaccess` são 644?
- [ ] Permissões de `public_html/public/` são 755?
- [ ] Permissões de `public_html/public/index.php` são 644?

### Via Testes HTTP

- [ ] `https://pontodogolfeoutlet.com.br/public/index.php` funciona?
- [ ] `https://pontodogolfeoutlet.com.br/public/test_access.php` funciona?
- [ ] `https://pontodogolfeoutlet.com.br/public/admin/js/media-picker.js` funciona?
- [ ] `https://pontodogolfeoutlet.com.br/` retorna 403?

### Via Configuração do Servidor

- [ ] DocumentRoot aponta para `public_html/`?
- [ ] `AllowOverride All` está configurado?
- [ ] `mod_rewrite` está habilitado?
- [ ] Logs de erro do Apache mostram algo?

---

## 🎯 11. CONCLUSÕES E RECOMENDAÇÕES

### Causa Mais Provável

**DocumentRoot não está configurado corretamente OU AllowOverride está desabilitado**

### Próximos Passos Recomendados

1. **Verificar DocumentRoot:**
   - Acessar painel Hostinger
   - Verificar configuração do DocumentRoot
   - Se possível, alterar para `public_html/public/` OU garantir que `public_html/` permite `.htaccess`

2. **Testar acesso direto:**
   - Acessar `https://pontodogolfeoutlet.com.br/public/index.php`
   - Se funcionar, problema é no `.htaccess` da raiz
   - Se não funcionar, problema é mais profundo

3. **Verificar logs do Apache:**
   - Acessar logs de erro via SSH ou painel Hostinger
   - Procurar por mensagens relacionadas a 403, `.htaccess`, ou permissões

4. **Solução Alternativa (se nada funcionar):**
   - Criar `index.php` na raiz que inclui `public/index.php`
   - Isso bypassa o `.htaccess` completamente

---

## 📚 12. REFERÊNCIAS

- Documentação de deploy: `docs/DEPLOY_HOSTINGER.md`
- Troubleshooting 403: `docs/TROUBLESHOOTING_403_PRODUCAO.md`
- Troubleshooting 404: `docs/TROUBLESHOOTING_404.md`

---

**Última atualização:** 2025-12-09  
**Status:** 🔴 Aguardando verificação de DocumentRoot e AllowOverride

