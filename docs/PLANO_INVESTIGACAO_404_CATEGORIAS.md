# Plano de Investigação: 404 em /admin/categorias após Deploy

## 📋 Contexto

- ✅ Deploy concluído com sucesso (Hostinger)
- ✅ Layout `store.php` atualizado (marcador de debug confirmado)
- ✅ Menu "Categorias" aparece no menu lateral
- ❌ Rota `/admin/categorias` retorna 404

---

## 🔧 Ferramentas de Debug Criadas

### 1. Script de Hash do index.php

**Arquivo:** `public/debug_index_hash.php`

**Funcionalidade:**
- Mostra hash MD5 do `index.php` em produção
- Verifica se contém rotas de categorias
- Mostra informações do servidor

**Como usar:**
```
https://pontodogolfeoutlet.com.br/debug_index_hash.php
```

**O que verificar:**
- Hash MD5 do arquivo em produção
- Comparar com hash local: `md5sum public/index.php` (Linux) ou `Get-FileHash public/index.php -Algorithm MD5` (Windows)
- Se hashes forem diferentes = arquivo não foi atualizado

---

### 2. Script de Diagnóstico de Rota (Melhorado)

**Arquivo:** `public/debug_rota_categorias.php`

**Melhorias adicionadas:**
- Mostra hash MD5 do `index.php`
- Mostra informações do servidor (REQUEST_URI, SCRIPT_NAME, DOCUMENT_ROOT)
- Tenta ler logs de erro do PHP
- Simula Router e mostra rotas registradas

**Como usar:**
```
https://pontodogolfeoutlet.com.br/debug_rota_categorias.php
```

---

### 3. Logs Temporários Adicionados

#### Em `public/index.php`:

**Linhas 125-127:** Log de requisição
```php
error_log('[DEBUG INDEX] REQUEST_URI = ' . ($_SERVER['REQUEST_URI'] ?? ''));
error_log('[DEBUG INDEX] SCRIPT_NAME = ' . ($_SERVER['SCRIPT_NAME'] ?? ''));
error_log('[DEBUG INDEX] PHP_SELF = ' . ($_SERVER['PHP_SELF'] ?? ''));
```

**Linha 196:** Log ao registrar rota
```php
error_log('[DEBUG INDEX] Registrando rota /admin/categorias');
```

**Linha 220:** Log após registrar todas as rotas
```php
error_log('[DEBUG INDEX] Todas as rotas de categorias registradas');
```

**Linhas 500-504:** Logs antes e depois do dispatch
```php
error_log('[DEBUG INDEX] Antes de dispatch - Method: ' . $method . ', URI: ' . $uri);
error_log('[DEBUG INDEX] Total de rotas antes do dispatch: ' . (method_exists($router, 'getRoutes') ? count($router->getRoutes()) : 'N/A'));
// ... dispatch ...
error_log('[DEBUG INDEX] Dispatch concluído com sucesso');
```

#### Em `src/Core/Router.php`:

**No método `addRoute()`:** Log ao registrar rota de categorias
```php
if (strpos($path, '/admin/categorias') !== false) {
    error_log('[DEBUG ROUTER] Rota registrada: ' . $method . ' ' . $path);
}
```

**No método `dispatch()` (quando retorna 404):**
```php
error_log('[DEBUG ROUTER] 404 para URI: ' . $uri);
error_log('[DEBUG ROUTER] Método: ' . $method);
error_log('[DEBUG ROUTER] Total de rotas registradas: ' . count($this->routes));
error_log('[DEBUG ROUTER] Rotas GET registradas: ' . implode(', ', $rotasDebug));
```

**Método público adicionado:**
```php
public function getRoutes(): array
{
    return $this->routes;
}
```

---

## 📝 Checklist de Investigação

### Passo 1: Verificar Hash do index.php

**Ação:**
1. Acessar `https://pontodogolfeoutlet.com.br/debug_index_hash.php`
2. Anotar o hash MD5 mostrado
3. No local, executar: `md5sum public/index.php` (Linux) ou `Get-FileHash public/index.php -Algorithm MD5` (Windows)
4. Comparar os hashes

**Resultado esperado:**
- ✅ Hashes iguais = arquivo atualizado
- ❌ Hashes diferentes = arquivo NÃO foi atualizado

**Se hashes diferentes:**
- Fazer upload manual do `public/index.php` atualizado
- Ou verificar configuração do Git no Hostinger

---

### Passo 2: Verificar Conteúdo do index.php em Produção

**Ação:**
1. Acessar `https://pontodogolfeoutlet.com.br/debug_rota_categorias.php`
2. Verificar se mostra:
   - ✅ Import do `CategoriaController` encontrado
   - ✅ Rota `/admin/categorias` encontrada
   - ✅ Trecho das rotas exibido

**Se não encontrar:**
- Arquivo `public/index.php` em produção está desatualizado
- Fazer upload manual ou forçar novo deploy

---

### Passo 3: Verificar Logs do PHP

**Ação:**
1. Acessar `/admin/produtos` em produção
2. Acessar `/admin/categorias` em produção
3. Verificar logs do PHP (error_log)

**Logs esperados para `/admin/produtos`:**
```
[DEBUG INDEX] REQUEST_URI = /admin/produtos
[DEBUG INDEX] Antes de dispatch - Method: GET, URI: /admin/produtos
[DEBUG INDEX] Dispatch concluído com sucesso
```

**Logs esperados para `/admin/categorias`:**
```
[DEBUG INDEX] REQUEST_URI = /admin/categorias
[DEBUG INDEX] Registrando rota /admin/categorias
[DEBUG ROUTER] Rota registrada: GET /admin/categorias
[DEBUG INDEX] Todas as rotas de categorias registradas
[DEBUG INDEX] Antes de dispatch - Method: GET, URI: /admin/categorias
[DEBUG ROUTER] 404 para URI: /admin/categorias  ← Se aparecer, problema no Router
[DEBUG ROUTER] Rotas GET registradas: ... (lista de rotas)
```

**Análise:**
- Se não aparecer `[DEBUG INDEX] REQUEST_URI = /admin/categorias` = requisição não está passando pelo `index.php`
- Se aparecer `[DEBUG ROUTER] 404` = rota não está sendo encontrada pelo Router
- Se aparecer `[DEBUG ROUTER] Rotas GET registradas` = verificar se `/admin/categorias` está na lista

---

### Passo 4: Verificar .htaccess

**Arquivos verificados:**
- ✅ `.htaccess` (raiz) - Rewrite para `index.php` da raiz
- ✅ `public/.htaccess` - Rewrite para `public/index.php`

**Possível problema:**
- Se DocumentRoot aponta para `public_html/` (raiz), o `.htaccess` da raiz deve redirecionar para `public/index.php`
- Se DocumentRoot aponta para `public_html/public/`, o `public/.htaccess` deve funcionar

**Como verificar:**
- Verificar qual é o DocumentRoot configurado
- Verificar qual `.htaccess` está sendo usado
- Testar se `/admin/produtos` e `/admin/categorias` passam pelo mesmo arquivo

---

### Passo 5: Comparar Comportamento de Rotas Funcionais

**Rota que funciona:** `/admin/produtos`

**Verificar:**
1. Acessar `/admin/produtos` e verificar logs
2. Acessar `/admin/categorias` e verificar logs
3. Comparar:
   - Mesmo `REQUEST_URI` formatado?
   - Mesmo `SCRIPT_NAME`?
   - Passam pelo mesmo `index.php`?

**Se `/admin/produtos` funciona mas `/admin/categorias` não:**
- Verificar se a rota está registrada ANTES de `/admin/produtos` no código
- Verificar se há alguma regra de `.htaccess` específica
- Verificar se há cache específico para uma rota

---

## 🎯 Resultado Esperado da Investigação

Após executar todos os passos, devemos ter:

1. **Hash do index.php em produção** - Para comparar com local
2. **Conteúdo do index.php em produção** - Para verificar se rotas estão presentes
3. **Logs de requisição** - Para ver se `/admin/categorias` passa pelo `index.php`
4. **Logs do Router** - Para ver se rota está registrada e por que retorna 404
5. **Lista de rotas registradas** - Para verificar se `/admin/categorias` está na lista

---

## 📊 Informações a Coletar

### Do Script debug_index_hash.php:
- Hash MD5 do `index.php` em produção
- Data de modificação do arquivo
- Tamanho do arquivo
- Se contém rotas de categorias

### Do Script debug_rota_categorias.php:
- Se `index.php` contém import do controller
- Se `index.php` contém rotas de categorias
- Se controller existe e pode ser carregado
- Se view existe
- Logs de erro do PHP (últimas entradas)

### Dos Logs do PHP:
- `[DEBUG INDEX] REQUEST_URI` para `/admin/produtos` e `/admin/categorias`
- `[DEBUG INDEX] Registrando rota /admin/categorias`
- `[DEBUG ROUTER] Rota registrada: GET /admin/categorias`
- `[DEBUG ROUTER] 404 para URI: /admin/categorias` (se aparecer)
- `[DEBUG ROUTER] Rotas GET registradas: ...` (lista completa)

---

## 🔍 Análise de Possíveis Causas

### Causa 1: Arquivo index.php não atualizado
**Sintoma:** Hash diferente entre local e produção

**Solução:** Fazer upload manual ou forçar novo deploy

---

### Causa 2: Cache do PHP (OPcache)
**Sintoma:** Hash igual, mas logs não aparecem

**Solução:** Limpar OPcache no painel Hostinger

---

### Causa 3: Rota não está sendo registrada
**Sintoma:** Log `[DEBUG INDEX] Registrando rota /admin/categorias` não aparece

**Solução:** Verificar se código está sendo executado (pode ser cache)

---

### Causa 4: Router não encontra a rota
**Sintoma:** Log `[DEBUG ROUTER] 404` aparece, mas rota está na lista

**Solução:** Verificar formato da URI (barra final, case-sensitive, etc.)

---

### Causa 5: Requisição não passa pelo index.php
**Sintoma:** Nenhum log `[DEBUG INDEX]` aparece para `/admin/categorias`

**Solução:** Verificar `.htaccess` e configuração do servidor

---

## 📌 Próximos Passos

1. **Fazer deploy dos arquivos com logs:**
   - `public/index.php` (com logs)
   - `src/Core/Router.php` (com logs e método getRoutes)
   - `public/debug_index_hash.php` (novo)
   - `public/debug_rota_categorias.php` (melhorado)

2. **Executar scripts de diagnóstico em produção:**
   - `debug_index_hash.php`
   - `debug_rota_categorias.php`

3. **Acessar rotas e verificar logs:**
   - `/admin/produtos` (funciona)
   - `/admin/categorias` (retorna 404)

4. **Coletar informações:**
   - Hash do `index.php`
   - Logs do PHP
   - Saída dos scripts de diagnóstico

5. **Analisar e corrigir:**
   - Comparar com local
   - Identificar divergência
   - Aplicar correção

---

## 🚨 Importante

**Após identificar e corrigir o problema, REMOVER todos os logs de debug:**
- Remover `error_log()` de `public/index.php`
- Remover `error_log()` de `src/Core/Router.php`
- Remover método `getRoutes()` do Router (ou deixar apenas se necessário)
- Remover scripts de debug ou movê-los para pasta de desenvolvimento

---

## 📝 Arquivos Modificados para Debug

1. `public/index.php` - Logs de requisição e registro de rotas
2. `src/Core/Router.php` - Logs de 404 e método getRoutes()
3. `public/debug_index_hash.php` - Novo script de verificação de hash
4. `public/debug_rota_categorias.php` - Melhorado com mais informações

