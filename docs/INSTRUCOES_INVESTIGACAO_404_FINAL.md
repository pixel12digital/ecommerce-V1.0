# Instruções Finais: Investigação 404 em /admin/categorias

## ✅ Ferramentas Criadas e Prontas

### 1. Script de Hash do index.php
**Arquivo:** `public/debug_index_hash.php`  
**Acesso:** `https://pontodogolfeoutlet.com.br/debug_index_hash.php`

**O que faz:**
- Mostra hash MD5 do `index.php` em produção
- Verifica se contém rotas de categorias
- Mostra informações do servidor

**Como usar:**
1. Acesse a URL acima
2. Anote o hash MD5 mostrado
3. Compare com hash local: `md5sum public/index.php` (Linux) ou `Get-FileHash public/index.php -Algorithm MD5` (Windows PowerShell)

---

### 2. Script de Diagnóstico Completo
**Arquivo:** `public/debug_rota_categorias.php`  
**Acesso:** `https://pontodogolfeoutlet.com.br/debug_rota_categorias.php`

**O que faz:**
- Verifica se `index.php` contém rotas
- Verifica se controller existe
- Verifica se view existe
- Testa autoload
- Mostra logs de erro do PHP

---

### 3. Logs Temporários Adicionados

#### Em `public/index.php`:

**Logs de requisição (linhas 125-127, 100-101):**
```php
error_log('[DEBUG INDEX] REQUEST_URI = ' . ($_SERVER['REQUEST_URI'] ?? ''));
error_log('[DEBUG INDEX] URI após processamento: ' . $uri);
```

**Log ao registrar rota (linha 196):**
```php
error_log('[DEBUG INDEX] Registrando rota /admin/categorias');
```

**Log após registrar todas (linha 220):**
```php
error_log('[DEBUG INDEX] Todas as rotas de categorias registradas');
```

**Logs antes/depois do dispatch (linhas 500-504):**
```php
error_log('[DEBUG INDEX] Antes de dispatch - Method: ' . $method . ', URI: ' . $uri);
error_log('[DEBUG INDEX] Dispatch concluído com sucesso');
```

#### Em `src/Core/Router.php`:

**Log ao registrar rota (método addRoute):**
```php
if (strpos($path, '/admin/categorias') !== false) {
    error_log('[DEBUG ROUTER] Rota registrada: ' . $method . ' ' . $path);
}
```

**Logs quando retorna 404 (método dispatch):**
```php
error_log('[DEBUG ROUTER] 404 para URI: ' . $uri);
error_log('[DEBUG ROUTER] Rotas GET registradas: ' . implode(', ', $rotasDebug));
```

---

## 📋 Passo a Passo de Investigação

### PASSO 1: Verificar Hash do index.php ✅ CONCLUÍDO

**Status:** ✅ **CONFIRMADO** - Hash do `index.php` em produção é idêntico ao local

**Resultado da verificação:**
- **Hash produção:** `58bbcb654ebf6e217c39eff386e4423d`
- **Hash local:** `58BBCB654EBF6E217C39EFF386E4423D` (idêntico)
- **Conclusão:** ✅ Arquivo `index.php` está atualizado em produção

**Rotas confirmadas no `index.php` de produção:**
- ✅ Import do `CategoriaController` encontrado
- ✅ Todas as 6 rotas de categorias presentes

**⚠️ IMPORTANTE:** A causa raiz anterior (arquivo desatualizado) foi descartada. O problema 404 persiste mesmo com o arquivo correto.

---

### PASSO 2: Executar Script de Diagnóstico Completo

**Ação:**
1. Acessar: `https://pontodogolfeoutlet.com.br/public/debug_rota_categorias.php`
2. Verificar todas as seções do relatório gerado

**O que o script verifica:**
- ✅ Hash MD5 do `index.php` (já confirmado como atualizado)
- ✅ Import do `CategoriaController` no código
- ✅ Presença das rotas no `index.php`
- ✅ Existência do Controller e View
- ✅ Teste de autoload do Controller
- ✅ Simulação de Router e matching de rotas
- ✅ Processamento de URI (simulação do que acontece no `index.php`)
- ✅ Logs de erro do PHP (últimas entradas)

**Seção mais importante:** Seção 6.3 - Teste de Matching de Rota
- Verifica se o Router consegue fazer match da URI `/admin/categorias`
- Mostra o pattern regex gerado
- Indica se há problema no matching

---

### PASSO 3: Acessar Rotas e Verificar Logs

**Ação:**
1. Acessar `/admin/produtos` (funciona)
2. Acessar `/admin/categorias` (retorna 404)
3. Verificar logs do PHP

**Como verificar logs:**
- Painel Hostinger → "Avançado" → "Logs de erro"
- Ou via SSH: `tail -f error_log` ou `tail -f /path/to/error_log`

**Logs esperados para `/admin/produtos`:**
```
[DEBUG INDEX] REQUEST_URI = /admin/produtos
[DEBUG INDEX] URI após processamento: /admin/produtos
[DEBUG INDEX] Antes de dispatch - Method: GET, URI: /admin/produtos
[DEBUG INDEX] Dispatch concluído com sucesso
```

**Logs esperados para `/admin/categorias`:**
```
[DEBUG INDEX] REQUEST_URI = /admin/categorias
[DEBUG INDEX] URI após processamento: /admin/categorias
[DEBUG INDEX] Registrando rota /admin/categorias
[DEBUG ROUTER] Rota registrada: GET /admin/categorias
[DEBUG INDEX] Todas as rotas de categorias registradas
[DEBUG INDEX] Antes de dispatch - Method: GET, URI: /admin/categorias
[DEBUG ROUTER] 404 para URI: /admin/categorias  ← Se aparecer
[DEBUG ROUTER] Rotas GET registradas: /admin, /admin/pedidos, /admin/produtos, /admin/categorias, ...
```

---

### PASSO 4: Análise dos Logs

**Cenário A: Nenhum log aparece para `/admin/categorias`**
- **Causa:** Requisição não está passando pelo `index.php`
- **Solução:** Verificar `.htaccess` e configuração do servidor

**Cenário B: Logs aparecem, mas rota não está na lista**
- **Causa:** Rota não foi registrada
- **Solução:** Verificar se código de registro está sendo executado

**Cenário C: Rota está na lista, mas retorna 404**
- **Causa:** Problema no matching do Router (formato da URI, regex, etc.)
- **Solução:** Verificar formato da URI e padrão da rota

**Cenário D: URI processada diferente da original**
- **Causa:** Processamento de prefixos está modificando incorretamente
- **Solução:** Ajustar lógica de processamento de URI

---

## 📊 Informações a Coletar

Após executar os passos acima, coletar:

1. ✅ **Hash MD5 do index.php em produção** - `58bbcb654ebf6e217c39eff386e4423d` (CONFIRMADO)
2. ✅ **Hash MD5 do index.php local** - `58BBCB654EBF6E217C39EFF386E4423D` (CONFIRMADO)
3. **Saída completa do `debug_rota_categorias.php`** (especialmente seções 6.3 e 8)
4. **Logs do PHP** para `/admin/produtos` e `/admin/categorias`
5. **Lista de rotas GET registradas** (do log `[DEBUG ROUTER] Rotas GET registradas`)

### O que copiar do `debug_rota_categorias.php`:

**Seção 6.3 - Teste de Matching de Rota:**
- URI original
- URI após parseUri
- Pattern regex gerado
- Resultado do match (✅ ou ❌)

**Seção 8 - Verificar Processamento de URI:**
- URI Original
- SCRIPT_NAME
- scriptDir calculado
- URI após processamento
- Se a URI foi processada corretamente

**Seção 7 - Logs de Erro:**
- Últimas entradas de log relacionadas (se houver)

---

## 🔍 Como Interpretar a Saída do debug_rota_categorias.php

### Cenário A: Rota encontrada, mas matching falha

**Se o script mostrar:**
- ✅ "Rota '/admin/categorias' encontrada no index.php"
- ✅ "Router consegue registrar a rota manualmente"
- ❌ "Pattern NÃO faz match com a URI processada"

**Causa provável:** Problema na lógica de matching do Router (regex, trailing slash, prefixo, etc.)

**Solução:** Verificar o método `pathToRegex()` do Router e comparar com rotas que funcionam (ex: `/admin/produtos`)

---

### Cenário B: URI processada incorretamente

**Se o script mostrar:**
- ✅ "Rota encontrada no index.php"
- ❌ "URI processada incorretamente! Esperado: `/admin/categorias`, Obtido: `[outro valor]`"

**Causa provável:** O processamento de prefixos no `index.php` está removendo/modificando a URI incorretamente

**Solução:** Ajustar a lógica de processamento de URI no `index.php` (linhas 81-100)

---

### Cenário C: Rota não encontrada no index.php

**Se o script mostrar:**
- ❌ "Rota '/admin/categorias' NÃO encontrada no index.php"
- ❌ "Import do CategoriaController NÃO encontrado"

**Causa provável:** Arquivo `index.php` em produção está desatualizado (mas isso já foi descartado pelo hash)

**Solução:** Verificar se há cache do PHP (OPcache) ou se o arquivo foi modificado após o deploy

---

### Cenário D: Erro ao carregar Router ou Controller

**Se o script mostrar:**
- ❌ "Erro ao testar Router: [mensagem de erro]"
- ❌ "Classe CategoriaController NÃO pode ser carregada via autoload"

**Causa provável:** Problema no autoload do Composer ou arquivos faltando

**Solução:** Verificar se `vendor/autoload.php` está presente e se o controller existe no caminho correto

---

### Cenário E: Tudo OK no script, mas 404 persiste

**Se o script mostrar:**
- ✅ Todas as verificações passam
- ✅ Matching funciona
- ✅ URI processada corretamente

**Mas ainda assim `/admin/categorias` retorna 404:**

**Causa provável:** 
- Cache do PHP (OPcache) servindo código antigo
- Requisição não está passando pelo `index.php` (problema no `.htaccess`)
- Alguma rota anterior está capturando a requisição antes de chegar em `/admin/categorias`

**Solução:** 
- Limpar OPcache
- Verificar logs do PHP ao acessar `/admin/categorias` (ver se logs `[DEBUG INDEX]` aparecem)
- Verificar ordem de registro das rotas no `index.php`

---

## 🎯 Resultado Esperado

Com essas informações, será possível identificar exatamente:

- ✅ Se o arquivo `index.php` foi atualizado (hash)
- ✅ Se as rotas estão no arquivo (conteúdo)
- ✅ Se a requisição passa pelo `index.php` (logs)
- ✅ Se a rota está registrada (logs do Router)
- ✅ Por que o Router retorna 404 (lista de rotas vs URI)

---

## 📝 Próximos Passos

1. **Fazer deploy dos arquivos com logs**
2. **Executar scripts de diagnóstico**
3. **Acessar rotas e coletar logs**
4. **Enviar informações coletadas para análise**
5. **Aplicar correção baseada na causa identificada**

---

## ⚠️ Importante

**Após identificar e corrigir o problema:**
- Remover todos os `error_log()` de debug
- Remover método `getRoutes()` do Router (ou deixar apenas se necessário)
- Remover scripts de debug ou movê-los para pasta de desenvolvimento

---

## 🔗 Arquivos Modificados

1. `public/index.php` - Logs de requisição e registro
2. `src/Core/Router.php` - Logs de 404 e método getRoutes()
3. `public/debug_index_hash.php` - Novo script
4. `public/debug_rota_categorias.php` - Melhorado
5. `docs/PLANO_INVESTIGACAO_404_CATEGORIAS.md` - Documentação completa

---

## 📌 Commits Relacionados

- `ff16a34` - Adicionar logs de debug e scripts
- `6dc0600` - Adicionar log de URI processada

