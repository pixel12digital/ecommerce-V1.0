# Relatório: Correção do Carregamento do media-picker.js no Admin

**Data:** 2025-12-09  
**Última atualização:** 2025-12-09  
**Problema:** Botão "Escolher da biblioteca" não funcionava e modal não listava imagens existentes  
**Status:** ✅ Corrigido

---

## 🔍 Problema Identificado

### Sintomas
- ✅ Biblioteca de Mídia (`/admin/midias`) funcionava normalmente
- ✅ Front da loja funcionava normalmente (banners, ícones, produtos)
- ❌ **Botão "Escolher da biblioteca" não funcionava** nas seguintes telas:
  - `/admin/home/categorias-pills`
  - `/admin/home/categorias-pills/novo`
  - `/admin/home/categorias-pills/{id}/editar`
  - `/admin/home/banners`
  - `/admin/home/banners/novo?tipo=hero`
  - `/admin/home/banners/{id}/editar`
- ❌ Console do navegador mostrava: `Failed to load media-picker.js:1 – resource: the server responded with a status of 404`
- ❌ **Modal abria mas não listava imagens existentes**, mostrando apenas "Nenhuma imagem encontrada ainda."

### Causa Raiz

O problema tinha duas partes:

1. **Detecção do caminho do `media-picker.js`** no layout admin:
   - **Código anterior:** Usava `$basePath` que era sempre definido como `/ecommerce-v1.0/public` (mesmo em produção)
   - **Resultado em produção:** Tentava carregar `/ecommerce-v1.0/public/admin/js/media-picker.js` (caminho inexistente)
   - **Caminho correto em produção:** Deveria ser `/public/admin/js/media-picker.js` (DocumentRoot = `public_html/`)

2. **Listagem de imagens no modal:**
   - Construção de URL do endpoint incorreta quando `basePath` era vazio
   - Falta de tratamento robusto de erros no endpoint `listar()`
   - Validação insuficiente no JavaScript para verificar se `data.files` era um array

---

## ✅ Correções Implementadas

### 1. Função Helper `admin_asset_path()`

**Arquivo:** `themes/default/admin/layouts/store.php` (linha ~786)

**Funcionalidade:**
- Detecta automaticamente o ambiente (dev vs produção)
- Gera caminho correto para assets do admin baseado no ambiente
- Remove dependência de `$basePath` que estava incorreto

**Implementação:**
```php
function admin_asset_path($relativePath) {
    // Remover barra inicial se existir
    $relativePath = ltrim($relativePath, '/');
    
    // Detectar se estamos em desenvolvimento local
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Se REQUEST_URI ou SCRIPT_NAME contém /ecommerce-v1.0/public, estamos em dev
    if (strpos($requestUri, '/ecommerce-v1.0/public') !== false || 
        strpos($scriptName, '/ecommerce-v1.0/public') !== false) {
        return '/ecommerce-v1.0/public/admin/' . $relativePath;
    }
    
    // Em produção na Hostinger:
    // - DocumentRoot aponta para public_html/ (raiz do projeto)
    // - Arquivos físicos estão em public_html/public/admin/js/...
    // - Para acessar via URL, precisamos usar /public/admin/...
    return '/public/admin/' . $relativePath;
}
```

**Comportamento:**
- **Dev:** `/ecommerce-v1.0/public/admin/js/media-picker.js`
- **Produção:** `/public/admin/js/media-picker.js` (DocumentRoot = `public_html/`, arquivos em `public/admin/js/`)

### 2. Uso da Função Helper

**Antes:**
```php
if (empty($basePath)) {
    $mediaPickerPath = '/admin/js/media-picker.js';
} else {
    $mediaPickerPath = $basePath . '/admin/js/media-picker.js';
}
```

**Depois:**
```php
$mediaPickerPath = admin_asset_path('js/media-picker.js');
```

---

## 🔧 Correção Adicional: Modal Não Listava Imagens Existentes

### Causa Raiz

1. **Construção de URL incorreta:** A URL do endpoint estava sendo construída com `basePath + '/admin/midias/listar'`, mas quando `basePath` era vazio em produção, a concatenação podia gerar URLs incorretas.
2. **Falta de tratamento de erros no endpoint:** O método `listar()` do `MediaLibraryController` não tinha tratamento robusto de erros e output buffering, podendo retornar HTML de erro em vez de JSON.
3. **Validação insuficiente no JavaScript:** O código JavaScript não validava adequadamente se `data.files` era um array antes de tentar iterar.

### Correções Implementadas

#### 1. Construção Correta da URL do Endpoint

**Arquivo:** `public/admin/js/media-picker.js` (linha ~310)

**Antes:**
```javascript
var url = basePath + '/admin/midias/listar';
```

**Depois:**
```javascript
// Construir URL corretamente: garantir que não tenha barras duplicadas
var url = '/admin/midias/listar';
if (basePath && basePath !== '') {
    // Remover barra final do basePath se existir
    var cleanBasePath = basePath.replace(/\/$/, '');
    url = cleanBasePath + url;
}
```

**Benefício:** Garante que a URL seja construída corretamente tanto em dev (`/ecommerce-v1.0/public/admin/midias/listar`) quanto em produção (`/admin/midias/listar`).

#### 2. Tratamento Robusto de Erros no Endpoint

**Arquivo:** `src/Http/Controllers/Admin/MediaLibraryController.php` (método `listar()`)

**Mudanças:**
- Adicionado `ob_start()` e `ob_clean()` para garantir resposta JSON limpa
- Adicionado `try-catch` para capturar exceções
- Garantido que `$imagens` seja sempre um array
- Adicionado campo `count` na resposta JSON para facilitar debug
- Tratamento de erros retorna JSON estruturado em vez de HTML

**Código:**
```php
public function listar(): void
{
    // Limpar qualquer saída anterior
    if (ob_get_level() > 0) {
        ob_clean();
    }
    ob_start();
    
    // Desabilitar exibição de erros para retornar JSON limpo
    $oldErrorReporting = error_reporting(0);
    $oldDisplayErrors = ini_set('display_errors', 0);
    
    try {
        $tenantId = TenantContext::id();
        $folder = $_GET['folder'] ?? null;
        $query = $_GET['q'] ?? '';
        
        if (!empty($query)) {
            $imagens = MediaLibraryService::buscarImagens($tenantId, $query);
        } else {
            $imagens = MediaLibraryService::listarImagensDoTenant($tenantId, $folder);
        }
        
        // Garantir que $imagens é sempre um array
        if (!is_array($imagens)) {
            $imagens = [];
        }
        
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'files' => $imagens,
            'count' => count($imagens),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\Throwable $e) {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao listar imagens: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
            'files' => [],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    } finally {
        // Restaurar configurações de erro
        if (isset($oldErrorReporting)) {
            error_reporting($oldErrorReporting);
        }
        if (isset($oldDisplayErrors)) {
            ini_set('display_errors', $oldDisplayErrors);
        }
    }
}
```

#### 3. Validação Melhorada no JavaScript

**Arquivo:** `public/admin/js/media-picker.js` (função `loadImages()`)

**Mudanças:**
- Validação se `data` é um objeto válido
- Validação se `data.files` é um array antes de iterar
- Logs detalhados para debug (tipo de dados, quantidade de arquivos, etc.)
- Mensagens de erro mais específicas

**Código:**
```javascript
.then(function(data) {
    console.log('[Media Picker] Dados recebidos:', data);
    console.log('[Media Picker] Tipo de dados:', typeof data);
    console.log('[Media Picker] data.success:', data.success);
    console.log('[Media Picker] data.files:', data.files);
    console.log('[Media Picker] data.count:', data.count);
    console.log('[Media Picker] Quantidade de arquivos:', data.files ? data.files.length : 0);
    
    loading.style.display = 'none';

    if (!data || typeof data !== 'object') {
        console.error('[Media Picker] Resposta inválida:', data);
        erro.textContent = 'Resposta inválida do servidor.';
        erro.style.display = 'block';
        return;
    }

    if (!data.success) {
        erro.textContent = data.message || 'Não foi possível carregar as imagens.';
        erro.style.display = 'block';
        return;
    }

    grid.innerHTML = '';
    if (!data.files || !Array.isArray(data.files) || data.files.length === 0) {
        console.log('[Media Picker] Nenhuma imagem encontrada (array vazio ou não é array)');
        console.log('[Media Picker] data.files é array?', Array.isArray(data.files));
        console.log('[Media Picker] data.files.length:', data.files ? data.files.length : 'undefined');
        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #666;">Nenhuma imagem encontrada ainda. Use o campo acima para fazer upload.</div>';
        grid.style.display = 'grid';
        return;
    }
    
    // ... renderização das imagens ...
})
```

---

## 📋 Arquivos Modificados

1. **`themes/default/admin/layouts/store.php`**
   - Adicionada função `admin_asset_path()`
   - Corrigida inclusão do `media-picker.js` para usar a nova função
   - Corrigida detecção de `basePath` em produção (agora retorna vazio quando DocumentRoot aponta para raiz)

2. **`public/admin/js/media-picker.js`**
   - Correção na construção da URL do endpoint
   - Validação melhorada da resposta JSON
   - Logs detalhados para debug
   - Melhor tratamento de erros

3. **`src/Http/Controllers/Admin/MediaLibraryController.php`**
   - Tratamento robusto de erros no método `listar()`
   - Garantia de resposta JSON limpa
   - Campo `count` adicionado à resposta
   - Output buffering para evitar HTML misturado com JSON

---

## 🧪 Como Testar

### Checklist de Testes Locais

#### 1. Categorias em Destaque

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/categorias-pills`

- [ ] Abrir DevTools → Aba "Network" e "Console"
- [ ] Recarregar a página
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar no console:
  - `[Media Picker] Carregando imagens de: ...`
  - `[Media Picker] Dados recebidos: ...`
  - `[Media Picker] Quantidade de arquivos: X`
- [ ] Verificar na aba Network:
  - Requisição para `/admin/midias/listar` retorna HTTP 200
  - Resposta JSON contém `{success: true, files: [...], count: X}`
- [ ] Verificar que o modal abre normalmente
- [ ] Verificar que as imagens aparecem no grid do modal
- [ ] Clicar em uma imagem e verificar que ela fica selecionada
- [ ] Clicar em "Usar imagem selecionada" e verificar que o campo é preenchido

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/categorias-pills/novo`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre e lista as imagens

#### 2. Banners da Home

**URL:** `http://localhost/ecommerce-v1.0/public/admin/home/banners/novo?tipo=hero`

- [ ] Abrir DevTools → Aba "Network"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar que o modal abre e lista as imagens

### Checklist de Testes em Produção

**URL:** `https://pontodogolfeoutlet.com.br/admin/home/categorias-pills/novo`

- [ ] Abrir DevTools → Aba "Network" e "Console"
- [ ] Verificar que `media-picker.js` carrega com HTTP 200 (deve ser `/public/admin/js/media-picker.js`)
- [ ] Clicar em "Escolher da biblioteca"
- [ ] Verificar no console:
  - `[Media Picker] basePath final: '' (tipo: string)`
  - `[Media Picker] Carregando imagens de: /admin/midias/listar`
  - `[Media Picker] Dados recebidos: {success: true, files: [...], count: X}`
- [ ] Verificar na aba Network:
  - Requisição para `/admin/midias/listar` retorna HTTP 200
  - Resposta JSON contém todas as imagens existentes
- [ ] Verificar que o modal abre e exibe todas as imagens (Logos, Produtos, Banners, Category Pills)
- [ ] Verificar que as imagens podem ser selecionadas e usadas

---

## 📍 Rotas/URLs Envolvidos

### Endpoint de Listagem

- **URL em dev:** `http://localhost/ecommerce-v1.0/public/admin/midias/listar`
- **URL em produção:** `https://pontodogolfeoutlet.com.br/admin/midias/listar`
- **Método:** `GET`
- **Parâmetros opcionais:**
  - `folder` - Filtrar por pasta (ex: `banners`, `category-pills`, `produtos`, `logo`)
  - `q` - Buscar por nome de arquivo
- **Resposta JSON:**
  ```json
  {
    "success": true,
    "files": [
      {
        "url": "/uploads/tenants/1/banners/golfe04.webp",
        "filename": "golfe04.webp",
        "folder": "banners",
        "folderLabel": "Banners",
        "size": 123456
      }
    ],
    "count": 1
  }
  ```

### Como o Script é Incluído

O script é incluído no layout base do admin (`themes/default/admin/layouts/store.php`), que é usado por todas as páginas do admin. Isso garante que o `media-picker.js` esteja disponível em todas as telas que precisam do botão "Escolher da biblioteca".

**Código de inclusão:**
```php
<?php
$mediaPickerPath = admin_asset_path('js/media-picker.js');
?>
<script src="<?= htmlspecialchars($mediaPickerPath) ?>"></script>
<script>
    // Definir basePath globalmente para o Media Picker
    window.basePath = '<?= htmlspecialchars($basePath) ?>';
</script>
```

### Compatibilidade

A função `admin_asset_path()` detecta automaticamente o ambiente baseado em:
- `$_SERVER['REQUEST_URI']` - URI da requisição
- `$_SERVER['SCRIPT_NAME']` - Caminho do script PHP

**Lógica de detecção:**
- Se `REQUEST_URI` ou `SCRIPT_NAME` contém `/ecommerce-v1.0/public` → **Dev** → `/ecommerce-v1.0/public/admin/...`
- Caso contrário → **Produção** → `/public/admin/...` (porque DocumentRoot = `public_html/` e arquivos estão em `public/`)

---

## 🔗 Relacionado

- **`docs/RELATORIO_MIDIA_PONTODOGOLFE.md`:** Correções de URLs de mídia no storefront e admin
- **`public/admin/js/media-picker.js`:** Script do componente de seleção de mídia
- **`themes/default/admin/layouts/store.php`:** Layout base do admin onde o script é incluído
- **`src/Http/Controllers/Admin/MediaLibraryController.php`:** Controller que retorna a lista de imagens via JSON

---

## ⚠️ Observações Importantes

1. **Não alterar a estrutura de diretórios:** O arquivo `media-picker.js` deve permanecer em `public/admin/js/`
2. **Não usar caminhos hardcoded:** Sempre usar a função `admin_asset_path()` para assets do admin
3. **Compatibilidade multi-tenant:** A correção funciona tanto em modo single quanto multi-tenant
4. **Sem dependência de .htaccess:** A correção não depende de configurações do `.htaccess`
5. **Endpoint sempre retorna JSON:** O método `listar()` sempre retorna JSON válido, mesmo em caso de erro

---

## ✅ Resultado Esperado

Após as correções:

- ✅ Modal abre corretamente ao clicar em "Escolher da biblioteca"
- ✅ Endpoint `/admin/midias/listar` retorna JSON válido com todas as imagens
- ✅ Grid do modal exibe todas as imagens existentes (Logos, Produtos, Banners, Category Pills)
- ✅ Imagens podem ser selecionadas e usadas
- ✅ Upload de novas imagens funciona normalmente
- ✅ Experiência igual ao WordPress: abrir modal → ver todas as mídias → selecionar → usar

---

**Status:** ✅ Implementação Concluída  
**Última atualização:** 2025-12-09
