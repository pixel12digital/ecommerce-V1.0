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

### PASSO 1: Verificar Hash do index.php

**Ação:**
1. Acessar: `https://pontodogolfeoutlet.com.br/debug_index_hash.php`
2. Anotar o hash MD5 mostrado
3. No local, executar:
   ```bash
   # Linux/Mac
   md5sum public/index.php
   
   # Windows PowerShell
   Get-FileHash public/index.php -Algorithm MD5
   ```
4. Comparar os hashes

**Resultado:**
- ✅ **Hashes iguais:** Arquivo está atualizado, problema é outro
- ❌ **Hashes diferentes:** Arquivo NÃO foi atualizado → Fazer upload manual

---

### PASSO 2: Verificar Conteúdo do index.php

**Ação:**
1. Acessar: `https://pontodogolfeoutlet.com.br/debug_rota_categorias.php`
2. Verificar se mostra:
   - ✅ Import do `CategoriaController` encontrado
   - ✅ Rota `/admin/categorias` encontrada
   - ✅ Trecho das rotas exibido

**Se não encontrar:**
- Arquivo está desatualizado → Fazer upload manual

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

1. **Hash MD5 do index.php em produção** (do `debug_index_hash.php`)
2. **Hash MD5 do index.php local** (comando terminal)
3. **Saída completa do `debug_rota_categorias.php`**
4. **Logs do PHP** para `/admin/produtos` e `/admin/categorias`
5. **Lista de rotas GET registradas** (do log `[DEBUG ROUTER] Rotas GET registradas`)

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

