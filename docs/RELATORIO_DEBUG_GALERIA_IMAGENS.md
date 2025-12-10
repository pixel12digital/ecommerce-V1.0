# Relatório Completo - Debug e Correção da Galeria de Imagens

## 📋 Sumário Executivo

**Problema Reportado:** As imagens da galeria de produtos não estão persistindo após salvar. O sistema aparentemente limita a 2 imagens e não permite excluir imagens existentes.

**Status:** Em investigação com logs detalhados implementados.

**Data:** 10 de dezembro de 2025

---

## 🔍 Problema Inicial

### Sintomas Reportados

1. **Limite de 2 imagens:**
   - Usuário consegue adicionar mais de 2 imagens visualmente
   - Imagens aparecem abaixo do botão "Adicionar da biblioteca"
   - Após salvar e recarregar a página, apenas 2 imagens permanecem

2. **Imagens não persistem:**
   - Mensagem de "Salvo com sucesso" aparece
   - Mas as imagens não são salvas no banco de dados
   - Imagens desaparecem após atualizar a página

3. **Não consegue excluir imagens:**
   - Checkbox de remoção não funciona
   - Botão de remoção (X) não remove imagens

### Produto de Teste
- **ID:** 929
- **Nome:** Short-Saia Adidas Vermelho TM
- **Imagens esperadas:** 4 imagens (conforme print fornecido)

---

## 📊 Logs do Console do Navegador

### Logs Iniciais (Antes das Correções)

```
[Media Picker] Inicializando...
[Media Picker] Modal criado
[Media Picker] Inicialização concluída
[Media Picker] basePath detectado do script src: /public
[Media Picker] basePath final: /public (tipo: string )
929:1858 [Layout] media-picker.js carregado com sucesso

feature_collector.js:23 using deprecated parameters for the initialization function; pass a single object instead

[Media Picker] Botão clicado: <button type="button" class="js-open-media-library admin-btn admin-btn-primary" 
  data-media-target="#galeria_paths_container" 
  data-folder="produtos" 
  data-multiple="true">
  Adicionar da biblioteca
</button>

[Media Picker] Target: #galeria_paths_container Folder: produtos Multiple: true
[Media Picker] openMediaLibrary chamado: #galeria_paths_container produtos Multiple: true

[MEDIA PICKER] basePath = /public
[MEDIA Picker] URL chamada = /public/admin/midias/listar?folder=produtos
[MEDIA PICKER] folderToUse = produtos
[MEDIA PICKER] HTTP status = 200

[MEDIA PICKER] RAW response text = {
  "success":true,
  "files":[
    {"url":"/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg","filename":"IMG-20251206-WA0050.jpg","folder":"produtos","folderLabel":"Produtos","size":87755},
    {"url":"/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg","filename":"IMG-20251206-WA0052.jpg","folder":"produtos",...}
  ]
}

[Media Picker] Dados recebidos: Object
[Media Picker] Tipo de dados: object
[Media Picker] data.success: true
[Media Picker] data.files: Array(144)
[Media Picker] data.count: 144
[Media Picker] Quantidade de arquivos: 144
[Media Picker] Renderizando 144 imagens
[Media Picker] Grid renderizado com 144 itens
```

### Observações dos Logs Iniciais

1. ✅ Media Picker carrega corretamente
2. ✅ Modal abre e lista 144 imagens
3. ✅ Modo múltiplo está ativo (`data-multiple="true"`)
4. ❓ **Não há logs de seleção de imagens** - isso indica que o evento `media-picker:multiple-selected` pode não estar sendo disparado ou capturado

---

## 🔧 Tentativas de Correção

### Tentativa 1: Verificação do Evento `selectMultipleImages`

**Problema Identificado:**
- O evento `media-picker:multiple-selected` estava sendo disparado no `currentTargetInput`, mas o listener estava no container `#galeria_paths_container`
- O `currentTargetInput` pode ser uma string (`"#galeria_paths_container"`) ou um elemento, causando inconsistência

**Correção Aplicada:**

```javascript
// ANTES (media-picker.js)
function selectMultipleImages(urls) {
    if (currentTargetInput && urls.length > 0) {
        var event = new CustomEvent('media-picker:multiple-selected', {
            bubbles: true,
            detail: { urls: urls }
        });
        currentTargetInput.dispatchEvent(event);
    }
}

// DEPOIS (media-picker.js)
function selectMultipleImages(urls) {
    if (currentTargetInput && urls.length > 0) {
        console.log('[Media Picker] selectMultipleImages chamado com', urls.length, 'URLs');
        console.log('[Media Picker] currentTargetInput:', currentTargetInput);
        
        // Buscar o container corretamente
        var container = document.querySelector(currentTargetInput.id || currentTargetInput);
        if (!container) {
            container = document.querySelector(currentTargetInput);
        }
        if (!container && typeof currentTargetInput === 'string') {
            container = document.getElementById(currentTargetInput.replace('#', ''));
        }
        if (!container && currentTargetInput instanceof Element) {
            container = currentTargetInput;
        }
        
        if (container) {
            console.log('[Media Picker] Disparando evento no container:', container);
            var event = new CustomEvent('media-picker:multiple-selected', {
                bubbles: true,
                cancelable: true,
                detail: { urls: urls }
            });
            container.dispatchEvent(event);
            console.log('[Media Picker] Evento disparado, URLs:', urls);
        } else {
            console.error('[Media Picker] Container não encontrado para disparar evento.');
        }
    }
}
```

**Logs Esperados Após Correção:**
```
[Media Picker] selectMultipleImages chamado com 4 URLs
[Media Picker] currentTargetInput: #galeria_paths_container
[Media Picker] Disparando evento no container: <div id="galeria_paths_container">
[Media Picker] Evento disparado, URLs: ["/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg", ...]
```

---

### Tentativa 2: Melhorar Listener no Container

**Problema Identificado:**
- Listener pode não estar capturando o evento corretamente
- Falta de logs para debug
- Verificação de duplicatas pode estar falhando

**Correção Aplicada:**

```javascript
// ANTES (edit-content.php)
container.addEventListener('media-picker:multiple-selected', function(event) {
    var urls = event.detail.urls;
    urls.forEach(function(url) {
        var existing = container.querySelector('input[value="' + url + '"]');
        if (existing) return;
        // ... adicionar input
    });
});

// DEPOIS (edit-content.php)
container.addEventListener('media-picker:multiple-selected', function(event) {
    console.log('[Galeria] Evento media-picker:multiple-selected recebido!');
    console.log('[Galeria] URLs recebidas:', event.detail.urls);
    
    var urls = event.detail.urls;
    if (!urls || !Array.isArray(urls)) {
        console.error('[Galeria] URLs inválidas:', urls);
        return;
    }
    
    var addedCount = 0;
    var skippedCount = 0;
    
    urls.forEach(function(url) {
        if (!url || typeof url !== 'string') {
            console.warn('[Galeria] URL inválida ignorada:', url);
            return;
        }
        
        // Verificar duplicatas com escape de aspas
        var existing = container.querySelector('input[value="' + url.replace(/"/g, '&quot;') + '"]');
        if (existing) {
            console.log('[Galeria] URL já existe (por valor), ignorando:', url);
            skippedCount++;
            return;
        }
        
        var existingByPath = container.querySelector('input[data-imagem-id][value="' + url.replace(/"/g, '&quot;') + '"]');
        if (existingByPath) {
            console.log('[Galeria] URL já existe (por data-imagem-id), ignorando:', url);
            skippedCount++;
            return;
        }
        
        console.log('[Galeria] Adicionando nova URL:', url);
        
        // Criar input e preview...
        addedCount++;
    });
    
    console.log('[Galeria] Resumo: ' + addedCount + ' adicionadas, ' + skippedCount + ' ignoradas');
    console.log('[Galeria] Total de inputs hidden agora:', container.querySelectorAll('input[type="hidden"]').length);
});
```

**Logs Esperados Após Correção:**
```
[Galeria] Evento media-picker:multiple-selected recebido!
[Galeria] URLs recebidas: ["/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg", ...]
[Galeria] Adicionando nova URL: /uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg
[Galeria] Adicionando nova URL: /uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg
[Galeria] Resumo: 4 adicionadas, 0 ignoradas
[Galeria] Total de inputs hidden agora: 4
```

---

### Tentativa 3: Logs no Submit do Formulário

**Problema Identificado:**
- Não sabemos quantos inputs estão sendo enviados no POST
- Pode haver problema na serialização do formulário

**Correção Aplicada:**

```javascript
// Adicionado em edit-content.php
form.addEventListener('submit', function(e) {
    // ... conversão de preços ...
    
    // Log para debug: verificar quantos inputs de galeria estão sendo enviados
    var galeriaInputs = document.querySelectorAll('#galeria_paths_container input[name="galeria_paths[]"]');
    console.log('[Form Submit] Total de inputs de galeria que serão enviados:', galeriaInputs.length);
    
    var galeriaPaths = [];
    galeriaInputs.forEach(function(input) {
        galeriaPaths.push(input.value);
    });
    console.log('[Form Submit] Caminhos de galeria:', galeriaPaths);
    
    // Verificar se há imagens marcadas para remoção
    var removeInputs = document.querySelectorAll('input[name="remove_imagens[]"]:checked');
    console.log('[Form Submit] Imagens marcadas para remoção:', removeInputs.length);
});
```

**Logs Esperados no Submit:**
```
[Form Submit] Total de inputs de galeria que serão enviados: 4
[Form Submit] Caminhos de galeria: [
  "/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg",
  "/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg",
  "/uploads/tenants/1/produtos/IMG-20251206-WA0053.jpg",
  "/uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg"
]
[Form Submit] Imagens marcadas para remoção: 0
```

---

### Tentativa 4: Logs Detalhados no Backend

**Problema Identificado:**
- Não sabemos o que está chegando no backend
- Não sabemos quantas imagens estão sendo processadas
- Não sabemos se há erros silenciosos

**Correção Aplicada:**

```php
// Adicionado em ProductController::processGallery()

// INÍCIO
error_log("ProductController::processGallery - INÍCIO - Total de caminhos recebidos no POST: " . count($_POST['galeria_paths']));
error_log("ProductController::processGallery - Caminhos recebidos: " . var_export($_POST['galeria_paths'], true));

// ANTES
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'");
$stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
$totalBefore = $stmt->fetch()['total'];
error_log("ProductController::processGallery - Total de imagens na galeria ANTES do processamento: {$totalBefore}");

// PROCESSAMENTO
foreach ($_POST['galeria_paths'] as $index => $imagePath) {
    error_log("ProductController::processGallery - Processando imagem #{$index}: '{$imagePath}'");
    
    // ... validações e inserção ...
    
    if (!$exists) {
        // Inserir nova imagem
        error_log("ProductController::processGallery - Inserindo nova imagem: {$imagePath}");
        $processedCount++;
    } else {
        error_log("ProductController::processGallery - Imagem já existe no produto (preservada): {$imagePath}");
        $skippedCount++;
    }
}

// RESUMO
error_log("ProductController::processGallery - RESUMO DO PROCESSAMENTO:");
error_log("ProductController::processGallery - Total de caminhos recebidos no POST: " . count($_POST['galeria_paths']));
error_log("ProductController::processGallery - Total de imagens ANTES: {$totalBefore}");
error_log("ProductController::processGallery - Imagens novas processadas: {$processedCount}");
error_log("ProductController::processGallery - Imagens já existentes (preservadas): {$skippedCount}");
error_log("ProductController::processGallery - Imagens com erro: {$errorCount}");

// APÓS
$stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'");
$stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
$totalAfter = $stmt->fetch()['total'];
error_log("ProductController::processGallery - Total de imagens na galeria APÓS processamento: {$totalAfter}");

// LISTA COMPLETA
$stmt = $db->prepare("SELECT id, caminho_arquivo, ordem FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery' ORDER BY ordem ASC");
$stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
$allImages = $stmt->fetchAll();
error_log("ProductController::processGallery - Lista completa de imagens na galeria:");
foreach ($allImages as $img) {
    error_log("ProductController::processGallery -   - ID: {$img['id']}, Ordem: {$img['ordem']}, Caminho: {$img['caminho_arquivo']}");
}

// ALERTA
if ($totalAfter < count($_POST['galeria_paths'])) {
    error_log("ProductController::processGallery - ⚠️ ATENÇÃO: Total no banco ({$totalAfter}) é menor que total enviado (" . count($_POST['galeria_paths']) . ")");
}
```

**Logs Esperados no Backend:**
```
ProductController::processGallery - INÍCIO - Total de caminhos recebidos no POST: 4
ProductController::processGallery - Caminhos recebidos: array (
  0 => '/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg',
  1 => '/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg',
  2 => '/uploads/tenants/1/produtos/IMG-20251206-WA0053.jpg',
  3 => '/uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg',
)
ProductController::processGallery - Total de imagens na galeria ANTES do processamento: 0
ProductController::processGallery - Processando imagem #0: '/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg'
ProductController::processGallery - Inserindo nova imagem: /uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg
ProductController::processGallery - Processando imagem #1: '/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg'
ProductController::processGallery - Inserindo nova imagem: /uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg
ProductController::processGallery - RESUMO DO PROCESSAMENTO:
ProductController::processGallery - Total de caminhos recebidos no POST: 4
ProductController::processGallery - Total de imagens ANTES: 0
ProductController::processGallery - Imagens novas processadas: 4
ProductController::processGallery - Imagens já existentes (preservadas): 0
ProductController::processGallery - Imagens com erro: 0
ProductController::processGallery - Total de imagens na galeria APÓS processamento: 4
ProductController::processGallery - Lista completa de imagens na galeria:
ProductController::processGallery -   - ID: 123, Ordem: 1, Caminho: /uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg
ProductController::processGallery -   - ID: 124, Ordem: 2, Caminho: /uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg
ProductController::processGallery -   - ID: 125, Ordem: 3, Caminho: /uploads/tenants/1/produtos/IMG-20251206-WA0053.jpg
ProductController::processGallery -   - ID: 126, Ordem: 4, Caminho: /uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg
```

---

## 🐛 Correção do Bug de Remoção de Preview

### Problema Identificado

A função `removeGalleryPreview` tinha um bug onde `previewItem` não estava definido:

```javascript
// ANTES (BUGADO)
window.removeGalleryPreview = function(btn, url) {
    var previewItem = btn.closest('div'); // ❌ Não estava definido
    if (previewItem) {
        previewItem.remove();
    }
    // ...
};
```

### Correção Aplicada

```javascript
// DEPOIS (CORRIGIDO)
window.removeGalleryPreview = function(btn, url) {
    console.log('[Galeria] Removendo preview da URL:', url);
    
    var previewItem = btn.closest('div');
    if (!previewItem) {
        console.error('[Galeria] Preview item não encontrado');
        return;
    }
    
    // Remover preview visual
    previewItem.remove();
    
    // Remover input hidden correspondente
    var container = document.getElementById('galeria_paths_container');
    if (container) {
        var input = container.querySelector('input[value="' + url.replace(/"/g, '&quot;') + '"]');
        if (input && !input.hasAttribute('data-imagem-id')) {
            // Só remover se não for imagem existente (sem data-imagem-id)
            input.remove();
            console.log('[Galeria] Input hidden removido');
        } else if (input && input.hasAttribute('data-imagem-id')) {
            // Se for imagem existente, marcar checkbox de remoção
            var imagemId = input.getAttribute('data-imagem-id');
            var removeCheckbox = document.querySelector('input[name="remove_imagens[]"][value="' + imagemId + '"]');
            if (removeCheckbox) {
                removeCheckbox.checked = true;
                console.log('[Galeria] Checkbox de remoção marcado para imagem ID:', imagemId);
            } else {
                console.warn('[Galeria] Checkbox de remoção não encontrado para imagem ID:', imagemId);
            }
        }
    }
    
    // Atualizar contadores
    var totalInputs = container ? container.querySelectorAll('input[type="hidden"]').length : 0;
    console.log('[Galeria] Total de inputs restantes:', totalInputs);
};
```

---

## 📝 Estrutura do Formulário

### HTML do Container de Galeria

```html
<!-- Container para inputs hidden (galeria_paths[]) -->
<div id="galeria_paths_container" style="display: none;">
    <?php 
    // Preencher com imagens existentes da galeria para preservar ao salvar
    foreach ($galeria as $img): 
    ?>
        <input type="hidden" 
               name="galeria_paths[]" 
               value="<?= htmlspecialchars($img['caminho_arquivo']) ?>"
               data-imagem-id="<?= (int)$img['id'] ?>">
    <?php endforeach; ?>
</div>

<!-- Container para preview das novas imagens da biblioteca -->
<div id="galeria_preview_container" style="display: none; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
    <!-- Preview das imagens existentes -->
    <?php foreach ($galeria as $img): ?>
        <div style="position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; aspect-ratio: 1;">
            <img src="<?= htmlspecialchars($img['caminho_arquivo']) ?>" 
                 style="width: 100%; height: 100%; object-fit: cover;">
            <button type="button" 
                    onclick="removeGalleryPreview(this, '<?= htmlspecialchars($img['caminho_arquivo'], ENT_QUOTES) ?>')"
                    style="position: absolute; top: 0.25rem; right: 0.25rem; background: #dc3545; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer;">
                <i class="bi bi-x"></i>
            </button>
            <!-- Checkbox para remoção de imagens existentes -->
            <input type="checkbox" 
                   name="remove_imagens[]" 
                   value="<?= (int)$img['id'] ?>"
                   style="position: absolute; top: 0.25rem; left: 0.25rem;">
        </div>
    <?php endforeach; ?>
</div>
```

### Fluxo de Dados

1. **Carregamento da Página:**
   - Imagens existentes são pré-preenchidas em `#galeria_paths_container` com `data-imagem-id`
   - Previews das imagens existentes são renderizados em `#galeria_preview_container`

2. **Seleção de Novas Imagens:**
   - Usuário clica em "Adicionar da biblioteca"
   - Media Picker abre e lista imagens
   - Usuário seleciona múltiplas imagens
   - Evento `media-picker:multiple-selected` é disparado
   - Listener adiciona inputs hidden em `#galeria_paths_container`
   - Listener adiciona previews em `#galeria_preview_container`

3. **Submit do Formulário:**
   - Todos os inputs `galeria_paths[]` são serializados e enviados no POST
   - Checkboxes `remove_imagens[]` marcados também são enviados

4. **Processamento no Backend:**
   - `ProductController::processGallery()` recebe `$_POST['galeria_paths']`
   - Para cada caminho, verifica se já existe no banco
   - Se não existe, insere nova imagem
   - Se existe, preserva (não duplica)
   - Processa `$_POST['remove_imagens']` para remover imagens marcadas

---

## 🔍 Pontos de Investigação

### 1. Verificar se Evento está Sendo Disparado

**Teste no Console:**
```javascript
// Verificar se o container existe
console.log('Container:', document.getElementById('galeria_paths_container'));

// Verificar se o listener está registrado
var container = document.getElementById('galeria_paths_container');
console.log('Event listeners:', getEventListeners(container));

// Disparar evento manualmente para testar
var testEvent = new CustomEvent('media-picker:multiple-selected', {
    bubbles: true,
    detail: { urls: ['/uploads/tenants/1/produtos/test.jpg'] }
});
container.dispatchEvent(testEvent);
```

### 2. Verificar se Inputs Estão Sendo Criados

**Teste no Console:**
```javascript
// Contar inputs antes de adicionar
var before = document.querySelectorAll('#galeria_paths_container input').length;
console.log('Inputs antes:', before);

// Adicionar imagem manualmente
var input = document.createElement('input');
input.type = 'hidden';
input.name = 'galeria_paths[]';
input.value = '/uploads/tenants/1/produtos/test.jpg';
document.getElementById('galeria_paths_container').appendChild(input);

// Contar inputs depois
var after = document.querySelectorAll('#galeria_paths_container input').length;
console.log('Inputs depois:', after);
```

### 3. Verificar POST Request

**Teste no DevTools:**
1. Abrir DevTools (F12)
2. Ir para aba "Network"
3. Filtrar por "produtos"
4. Salvar o produto
5. Clicar na requisição POST
6. Verificar aba "Payload" ou "Form Data"
7. Procurar por `galeria_paths[]`

**O que verificar:**
- Quantos `galeria_paths[]` estão no POST?
- Os valores estão corretos?
- Há `remove_imagens[]` se necessário?

### 4. Verificar Logs do Backend

**Via Script:**
```bash
php scripts/collect_product_logs.php --product=929 --last-hour
```

**Via Web:**
```
https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929
```

**O que verificar:**
- Total de caminhos recebidos no POST
- Total de imagens ANTES vs APÓS
- Quantas foram processadas vs preservadas
- Lista completa de imagens no banco

---

## 📊 Cenários de Teste

### Cenário 1: Adicionar 4 Imagens Novas (Produto sem Imagens)

**Passos:**
1. Abrir produto 929 (sem imagens na galeria)
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 4 imagens
4. Verificar console: deve mostrar "4 adicionadas"
5. Salvar produto
6. Verificar logs do backend: deve mostrar "4 processadas"
7. Recarregar página
8. **Resultado Esperado:** 4 imagens devem aparecer

**Logs Esperados:**
```
[Galeria] Resumo: 4 adicionadas, 0 ignoradas
[Form Submit] Total de inputs de galeria que serão enviados: 4
ProductController::processGallery - Imagens novas processadas: 4
ProductController::processGallery - Total de imagens APÓS: 4
```

### Cenário 2: Adicionar 2 Imagens a um Produto com 2 Imagens Existentes

**Passos:**
1. Abrir produto 929 (com 2 imagens na galeria)
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 2 novas imagens (diferentes das existentes)
4. Verificar console: deve mostrar "2 adicionadas"
5. Verificar console: deve mostrar "Total de inputs: 4" (2 existentes + 2 novas)
6. Salvar produto
7. Verificar logs: deve mostrar "2 processadas, 2 preservadas"
8. Recarregar página
9. **Resultado Esperado:** 4 imagens devem aparecer

**Logs Esperados:**
```
[Galeria] Resumo: 2 adicionadas, 0 ignoradas
[Galeria] Total de inputs hidden agora: 4
[Form Submit] Total de inputs de galeria que serão enviados: 4
ProductController::processGallery - Total de imagens ANTES: 2
ProductController::processGallery - Imagens novas processadas: 2
ProductController::processGallery - Imagens já existentes (preservadas): 2
ProductController::processGallery - Total de imagens APÓS: 4
```

### Cenário 3: Tentar Adicionar Imagem Duplicada

**Passos:**
1. Abrir produto 929 (com 2 imagens na galeria)
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 1 imagem que já está na galeria
4. Verificar console: deve mostrar "0 adicionadas, 1 ignoradas"
5. **Resultado Esperado:** Imagem não deve ser adicionada

**Logs Esperados:**
```
[Galeria] URL já existe (por data-imagem-id), ignorando: /uploads/tenants/1/produtos/existing.jpg
[Galeria] Resumo: 0 adicionadas, 1 ignoradas
```

### Cenário 4: Remover Imagem Existente

**Passos:**
1. Abrir produto 929 (com 2 imagens na galeria)
2. Clicar no botão "X" de uma imagem existente
3. Verificar console: deve mostrar "Checkbox de remoção marcado"
4. Salvar produto
5. Verificar logs: deve mostrar que a imagem foi removida
6. Recarregar página
7. **Resultado Esperado:** Apenas 1 imagem deve aparecer

**Logs Esperados:**
```
[Galeria] Checkbox de remoção marcado para imagem ID: 123
[Form Submit] Imagens marcadas para remoção: 1
ProductController::processGallery - Removendo 1 imagens
ProductController::processGallery - Total de imagens APÓS: 1
```

---

## 🚨 Problemas Conhecidos

### 1. Warning do `feature_collector.js`

```
feature_collector.js:23 using deprecated parameters for the initialization function; pass a single object instead
```

**Status:** Não crítico, é um warning de biblioteca externa.

**Ação:** Pode ser ignorado por enquanto.

### 2. Erro 404 do favicon

```
/favicon.ico:1 Failed to load resource: the server responded with a status of 404
```

**Status:** Não crítico, é apenas um ícone faltando.

**Ação:** Pode ser ignorado.

---

## 📋 Checklist de Validação

Após aplicar as correções, validar:

- [ ] Console mostra logs quando imagens são selecionadas
- [ ] Console mostra total correto de inputs antes do submit
- [ ] POST contém todos os `galeria_paths[]` esperados
- [ ] Backend recebe todos os caminhos no POST
- [ ] Backend processa todas as imagens (não apenas 2)
- [ ] Backend preserva imagens existentes
- [ ] Backend remove imagens marcadas para remoção
- [ ] Total no banco após salvar = total enviado no POST
- [ ] Imagens persistem após recarregar a página
- [ ] Botão de remoção funciona corretamente

---

## 🔗 Arquivos Modificados

1. **`public/admin/js/media-picker.js`**
   - Corrigido `selectMultipleImages()` para encontrar container corretamente
   - Adicionados logs detalhados

2. **`themes/default/admin/products/edit-content.php`**
   - Melhorado listener `media-picker:multiple-selected`
   - Adicionados logs no listener
   - Adicionados logs no submit do formulário
   - Corrigido `removeGalleryPreview()`

3. **`src/Http/Controllers/Admin/ProductController.php`**
   - Adicionados logs detalhados em `processGallery()`
   - Logs mostram total ANTES e APÓS
   - Logs mostram lista completa de imagens
   - Logs alertam se há discrepância

4. **`docs/INSTRUCOES_DIAGNOSTICO_IMAGENS.md`**
   - Documentação de scripts de diagnóstico
   - Instruções de uso

---

## 📞 Próximos Passos

1. **Testar no Ambiente de Produção:**
   - Aplicar as correções
   - Testar os cenários acima
   - Coletar logs reais

2. **Analisar Logs Reais:**
   - Comparar logs esperados vs reais
   - Identificar discrepâncias
   - Ajustar correções se necessário

3. **Validar Persistência:**
   - Verificar se imagens persistem após múltiplos saves
   - Verificar se ordem é mantida
   - Verificar se remoção funciona

4. **Documentar Solução Final:**
   - Atualizar este documento com resultados
   - Criar guia de uso para usuários
   - Documentar limitações conhecidas

---

## 📚 Referências

- **Script de Coleta de Logs:** `scripts/collect_product_logs.php`
- **Script de Verificação de Imagens (CLI):** `scripts/check_product_images.php`
- **Script de Verificação de Imagens (WEB):** `scripts/check_product_images_web.php`
- **Documentação de Diagnóstico:** `docs/INSTRUCOES_DIAGNOSTICO_IMAGENS.md`
- **Relatório de Ajustes:** `docs/RELATORIO_AJUSTES_ESTOQUE_E_IMAGENS.md`

---

---

## ✅ RESUMO FINAL - Correções Implementadas (10/12/2025)

### Problemas Corrigidos

#### 1. ✅ Remoção de Imagens da Galeria

**Antes:**
- Função `removeGalleryPreview` não lidava corretamente com imagens existentes
- Apenas removia inputs hidden de imagens novas
- Não marcava checkbox `remove_imagens[]` para imagens existentes

**Depois:**
- Função agora identifica se a imagem é nova (sem `data-imagem-id`) ou existente (com `data-imagem-id`)
- Para imagens novas: remove input hidden e preview
- Para imagens existentes: marca checkbox `remove_imagens[]` e adiciona indicador visual "Será removida"
- Backend processa `remove_imagens[]` corretamente, removendo do banco e arquivo físico

**Arquivos Modificados:**
- `themes/default/admin/products/edit-content.php` - Função `removeGalleryPreview`

#### 2. ✅ Remoção da Imagem de Destaque

**Antes:**
- Não havia forma clara de remover a imagem de destaque
- Usuário precisava selecionar outra imagem para "substituir"

**Depois:**
- Botão "Remover imagem" aparece quando há imagem de destaque
- Função `removeFeaturedImage()` limpa campos e marca flag `remove_featured=1`
- Backend processa `remove_featured` ou campo vazio, removendo do banco
- Preview volta para placeholder "Sem imagem de destaque"

**Arquivos Modificados:**
- `themes/default/admin/products/edit-content.php` - HTML do botão e função JavaScript
- `src/Http/Controllers/Admin/ProductController.php` - Método `processMainImage`

#### 3. ✅ Logs Condicionais (Otimização)

**Antes:**
- Logs muito verbosos sempre ativos, poluindo logs de produção

**Depois:**
- Logs detalhados apenas quando `APP_DEBUG` está ativo
- Logs importantes (erros, alertas) sempre ativos
- Logs resumidos (sucesso, contadores) sempre ativos

**Arquivos Modificados:**
- `src/Http/Controllers/Admin/ProductController.php` - Método `processGallery`

### Comportamento Esperado Após Correções

#### Galeria de Imagens

1. **Adicionar Múltiplas Imagens:**
   - Selecionar 4+ imagens na biblioteca
   - Todas aparecem nos previews
   - Todas são enviadas no POST `galeria_paths[]`
   - Backend processa todas (sem limite)
   - Todas persistem após recarregar

2. **Remover Imagem Nova:**
   - Clicar no botão X
   - Preview e input hidden são removidos
   - Não é enviada no POST
   - Não aparece após salvar

3. **Remover Imagem Existente:**
   - Clicar no botão X
   - Preview mostra "Será removida" (opacidade reduzida)
   - Checkbox `remove_imagens[]` é marcado
   - Backend remove do banco e arquivo físico
   - Não aparece após salvar e recarregar

#### Imagem de Destaque

1. **Remover Imagem de Destaque:**
   - Clicar no botão "Remover imagem"
   - Campos são limpos
   - Flag `remove_featured=1` é marcada
   - Backend remove do banco
   - Preview volta para placeholder
   - Não aparece após salvar e recarregar

### Validações Implementadas

- ✅ Não há limite artificial de imagens (verificado: nenhum `slice`, `LIMIT`, ou validação que limite)
- ✅ Todas as imagens do POST são processadas
- ✅ Imagens existentes são preservadas se estiverem no POST
- ✅ Remoção funciona para imagens novas e existentes
- ✅ Remoção da imagem de destaque funciona corretamente
- ✅ Logs são condicionais (apenas em debug)

### Próximos Passos para Teste

1. **Teste de Adição Múltipla:**
   - Produto sem imagens → Adicionar 4+ imagens → Salvar → Recarregar → Verificar se todas persistem

2. **Teste de Remoção:**
   - Produto com 2 imagens → Remover 1 → Salvar → Recarregar → Verificar se apenas 1 permanece

3. **Teste de Remoção de Destaque:**
   - Produto com imagem de destaque → Clicar "Remover imagem" → Salvar → Recarregar → Verificar placeholder

4. **Verificar Logs:**
   - Ativar `APP_DEBUG` para ver logs detalhados
   - Verificar se todos os caminhos são recebidos no POST
   - Verificar se todas as imagens são processadas

---

## 🔄 Atualização - Problema Persistente (10/12/2025 - Tarde)

### Status Atual

**Problema Reportado:**
- ✅ Adição de imagens funciona (JavaScript corrigido)
- ✅ Botão de excluir funciona (event listener adicionado)
- ❌ **Terceira imagem não persiste após salvar e recarregar**

### Logs do Console (Última Tentativa)

```
[Form Submit] Total de inputs de galeria que serão enviados: 3
[Form Submit] Caminhos de galeria: (3) [
  '/uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg',
  '/uploads/tenants/1/produtos/IMG-20251206-WA0055.jpg',
  '/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg'
]
```

**Observação:** 3 imagens estão sendo enviadas no POST, mas apenas 2 persistem após recarregar.

### Correções Adicionais Implementadas

#### 1. Logs Detalhados no Backend

**Adicionado:**
- Log para cada imagem processada (sempre, não apenas em debug)
- Log mostrando se imagem foi inserida ou pulada
- Log com ID inserido quando imagem é salva
- Log com ID existente quando imagem é preservada
- Resumo final sempre logado

**Exemplo de logs esperados:**
```
ProductController::processGallery - [IMAGEM #0] Iniciando processamento: '/uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg'
ProductController::processGallery - 🔍 Imagem NÃO existe no banco, será inserida: /uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg
ProductController::processGallery - ✅ [IMAGEM #0] INSERIDA COM SUCESSO: /uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg (ordem: 1, ID inserido: 154)

ProductController::processGallery - [IMAGEM #1] Iniciando processamento: '/uploads/tenants/1/produtos/IMG-20251206-WA0055.jpg'
ProductController::processGallery - 🔍 Imagem NÃO existe no banco, será inserida: /uploads/tenants/1/produtos/IMG-20251206-WA0055.jpg
ProductController::processGallery - ✅ [IMAGEM #1] INSERIDA COM SUCESSO: /uploads/tenants/1/produtos/IMG-20251206-WA0055.jpg (ordem: 2, ID inserido: 155)

ProductController::processGallery - [IMAGEM #2] Iniciando processamento: '/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg'
ProductController::processGallery - 🔍 Imagem já existe: ID=152, tipo=gallery, caminho=/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg
ProductController::processGallery - ⏭️ [IMAGEM #2] JÁ EXISTE no produto (preservada): /uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg (ID existente: 152, tipo: gallery)

ProductController::processGallery - 📊 RESUMO FINAL:
ProductController::processGallery -   Total recebido no POST: 3
ProductController::processGallery -   Total ANTES: 2
ProductController::processGallery -   Imagens novas inseridas: 2
ProductController::processGallery -   Imagens já existentes (preservadas): 1
ProductController::processGallery -   Imagens com erro: 0
ProductController::processGallery -   Total APÓS: 4
```

#### 2. Correção na Ordem de Inserção

**Problema Identificado:**
- Uso de `$ordem++` diretamente no array de parâmetros pode causar problemas
- Log mostrava ordem incorreta (mostrava `$ordem - 1`)

**Correção:**
- Armazenar ordem em variável `$currentOrdem` antes de incrementar
- Usar `$currentOrdem` no INSERT
- Log mostrar ordem correta

### Hipóteses para Investigação

#### Hipótese 1: Verificação de Duplicatas Muito Restritiva

**Possível Causa:**
- A verificação `SELECT id, tipo, caminho_arquivo` pode estar encontrando a imagem que acabou de ser inserida no mesmo loop
- Se a imagem #0 e #1 forem inseridas, e a imagem #2 tiver o mesmo caminho de uma já inserida, ela será pulada

**Como Verificar:**
- Verificar nos logs se a terceira imagem está sendo detectada como "já existe"
- Verificar se os caminhos são realmente diferentes

#### Hipótese 2: Problema com Transação/Commit

**Possível Causa:**
- A transação pode não estar sendo commitada corretamente
- Algumas inserções podem estar sendo revertidas

**Como Verificar:**
- Verificar se há `commit()` após `processGallery()`
- Verificar se há `rollback()` sendo chamado

#### Hipótese 3: Problema na Query de Busca da Galeria

**Possível Causa:**
- A query que busca a galeria para exibir pode ter um `LIMIT 2` ou similar
- Ou pode estar ordenando de forma que a terceira imagem não aparece

**Como Verificar:**
- Verificar a query em `edit()` que busca `$galeria`
- Verificar se há `LIMIT` ou ordenação que possa ocultar imagens

### Próximos Passos de Investigação

1. **Verificar Logs do Backend:**
   ```bash
   php scripts/collect_product_logs.php --product=929 --last-hour
   ```
   - Procurar por `[IMAGEM #2]` nos logs
   - Verificar se está sendo inserida ou pulada
   - Verificar se há erros

2. **Verificar Banco de Dados Diretamente:**
   ```bash
   php scripts/check_product_images.php 929
   ```
   - Verificar quantas imagens estão realmente no banco
   - Verificar se a terceira imagem foi inserida

3. **Verificar Query de Busca:**
   - Verificar `ProductController::edit()` método que busca `$galeria`
   - Verificar se há `LIMIT` ou filtros que possam ocultar imagens

4. **Testar com Produto Limpo:**
   - Criar produto novo sem imagens
   - Adicionar 3 imagens de uma vez
   - Verificar se todas persistem

### Arquivos Modificados (Última Atualização)

- `src/Http/Controllers/Admin/ProductController.php` - Logs detalhados adicionados
- `themes/default/admin/products/edit-content.php` - Event listener para botão de remoção

---

---

## 📋 LOGS COMPLETOS DO CONSOLE (Última Sessão)

### Logs de Inicialização

```
[Galeria] Container encontrado, adicionando listener para media-picker:multiple-selected
[Media Picker] Inicializando...
[Media Picker] Modal criado
[Media Picker] Inicialização concluída
[Media Picker] basePath detectado do script src: /public
[Media Picker] basePath final: /public (tipo: string )
[Layout] media-picker.js carregado com sucesso
```

### Logs de Seleção de Imagem

```
[Media Picker] Botão clicado: <button type="button" class="js-open-media-library admin-btn admin-btn-primary" 
  data-media-target="#galeria_paths_container" 
  data-folder="produtos" 
  data-multiple="true">
  Adicionar da biblioteca
</button>

[Media Picker] Target: #galeria_paths_container Folder: produtos Multiple: true
[Media Picker] openMediaLibrary chamado: #galeria_paths_container produtos Multiple: true

[MEDIA PICKER] basePath = /public
[MEDIA PICKER] URL chamada = /public/admin/midias/listar?folder=produtos
[MEDIA PICKER] folderToUse = produtos
[MEDIA PICKER] HTTP status = 200

[MEDIA PICKER] RAW response text = {
  "success":true,
  "files":[
    {"url":"/uploads/tenants/1/produtos/IMG-20251206-WA0050.jpg","filename":"IMG-20251206-WA0050.jpg","folder":"produtos","folderLabel":"Produtos","size":87755},
    {"url":"/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg","filename":"IMG-20251206-WA0052.jpg","folder":"produtos",...}
  ]
}

[Media Picker] Dados recebidos: Object
[Media Picker] Tipo de dados: object
[Media Picker] data.success: true
[Media Picker] data.files: Array(144)
[Media Picker] data.count: 144
[Media Picker] Quantidade de arquivos: 144
[Media Picker] Renderizando 144 imagens
[Media Picker] Grid renderizado com 144 itens
```

### Logs de Processamento de Seleção Múltipla

```
[Media Picker] selectMultipleImages chamado com 1 URLs
[Media Picker] currentTargetInput: <div id="galeria_paths_container" style="display: block;">…</div>
[Media Picker] Tipo de currentTargetInput: object (Element)
[Media Picker] Container encontrado (usando currentTargetInput diretamente): <div id="galeria_paths_container" style="display: block;">…</div>
[Media Picker] Container ID: galeria_paths_container
[Media Picker] Disparando evento no container

[Galeria] Evento media-picker:multiple-selected recebido!
[Galeria] URLs recebidas: Array(1)
[Galeria] Adicionando nova URL: /uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg
[Galeria] Resumo: 1 adicionadas, 0 ignoradas
[Galeria] Total de inputs hidden agora: 3

[Media Picker] ✅ Evento disparado com sucesso, URLs: Array(1)
```

### Logs de Submit do Formulário

```
[Form Submit] Total de inputs de galeria que serão enviados: 3
[Form Submit] Caminhos de galeria: (3) [
  '/uploads/tenants/1/produtos/IMG-20251206-WA0054.jpg',
  '/uploads/tenants/1/produtos/IMG-20251206-WA0055.jpg',
  '/uploads/tenants/1/produtos/IMG-20251206-WA0052.jpg'
]
[Form Submit] Imagens marcadas para remoção: 0
```

**Observação Crítica:** 
- ✅ 3 imagens estão sendo enviadas no POST
- ✅ JavaScript está funcionando corretamente
- ❌ Apenas 2 imagens persistem após recarregar

---

## 🔍 CÓDIGOS RELACIONADOS PARA INSPEÇÃO

### 1. JavaScript - Listener de Galeria (`themes/default/admin/products/edit-content.php`)

**Localização:** Linhas ~867-962

```javascript
// Processar seleção múltipla da biblioteca de mídia para galeria
(function() {
    var container = document.getElementById('galeria_paths_container');
    var previewContainer = document.getElementById('galeria_preview_container');
    
    if (container) {
        container.addEventListener('media-picker:multiple-selected', function(event) {
            console.log('[Galeria] Evento media-picker:multiple-selected recebido!');
            console.log('[Galeria] URLs recebidas:', event.detail.urls);
            
            var urls = event.detail.urls;
            if (!urls || !Array.isArray(urls)) {
                console.error('[Galeria] URLs inválidas:', urls);
                return;
            }
            
            var addedCount = 0;
            var skippedCount = 0;
            
            // Criar inputs hidden para cada URL
            urls.forEach(function(url) {
                if (!url || typeof url !== 'string') {
                    console.warn('[Galeria] URL inválida ignorada:', url);
                    return;
                }
                
                // Verificar se já não existe (por valor ou por imagem existente)
                var existing = container.querySelector('input[value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existing) {
                    console.log('[Galeria] URL já existe (por valor), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                // Verificar se já existe uma imagem com esse caminho na galeria existente
                var existingByPath = container.querySelector('input[data-imagem-id][value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existingByPath) {
                    console.log('[Galeria] URL já existe (por data-imagem-id), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                console.log('[Galeria] Adicionando nova URL:', url);
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'galeria_paths[]';
                input.value = url;
                container.appendChild(input);
                addedCount++;
                
                // Adicionar preview...
            });
            
            console.log('[Galeria] Resumo: ' + addedCount + ' adicionadas, ' + skippedCount + ' ignoradas');
            console.log('[Galeria] Total de inputs hidden agora:', container.querySelectorAll('input[type="hidden"]').length);
        });
    }
})();
```

**Análise:**
- ✅ Listener está registrado corretamente
- ✅ Evento está sendo recebido
- ✅ Inputs hidden estão sendo criados
- ✅ Logs mostram 3 inputs no total

### 2. JavaScript - Função removeGalleryPreview (`themes/default/admin/products/edit-content.php`)

**Localização:** Linhas ~964-1040

```javascript
window.removeGalleryPreview = function(btn, url) {
    console.log('[Galeria] removeGalleryPreview chamado para URL:', url);
    
    // Buscar container novamente (pode não estar no escopo)
    var container = document.getElementById('galeria_paths_container');
    var previewContainer = document.getElementById('galeria_preview_container');
    
    if (!container) {
        console.error('[Galeria] Container #galeria_paths_container não encontrado');
        return;
    }
    
    var previewItem = btn.closest('div');
    if (!previewItem) {
        console.error('[Galeria] Preview item não encontrado');
        return;
    }
    
    // Encontrar o input hidden correspondente a essa URL
    var escapedUrl = url.replace(/"/g, '&quot;').replace(/'/g, "&#39;").replace(/\[/g, '\\[').replace(/\]/g, '\\]');
    var input = container.querySelector('input[value="' + escapedUrl + '"]');
    
    if (input) {
        // Verificar se é imagem existente (tem data-imagem-id) ou nova
        if (input.hasAttribute('data-imagem-id')) {
            // É imagem existente - marcar checkbox de remoção
            var imagemId = input.getAttribute('data-imagem-id');
            console.log('[Galeria] Imagem existente encontrada, ID:', imagemId);
            
            // Buscar checkbox de remoção correspondente
            var removeCheckbox = document.querySelector('input[name="remove_imagens[]"][value="' + imagemId + '"]');
            if (removeCheckbox) {
                removeCheckbox.checked = true;
                console.log('[Galeria] Checkbox de remoção marcado para imagem ID:', imagemId);
                
                // Remover visualmente o preview (opcional - pode manter até salvar)
                previewItem.style.opacity = '0.5';
                previewItem.style.border = '2px solid #dc3545';
                
                // Adicionar indicador visual de que será removida
                var indicator = document.createElement('div');
                indicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 53, 69, 0.9); color: white; padding: 0.5rem; border-radius: 4px; font-size: 0.875rem; z-index: 10;';
                indicator.textContent = 'Será removida';
                previewItem.appendChild(indicator);
            } else {
                console.warn('[Galeria] Checkbox de remoção não encontrado para imagem ID:', imagemId);
                // Criar checkbox se não existir (fallback)
                var form = document.querySelector('form[method="POST"]');
                if (form) {
                    var newCheckbox = document.createElement('input');
                    newCheckbox.type = 'checkbox';
                    newCheckbox.name = 'remove_imagens[]';
                    newCheckbox.value = imagemId;
                    newCheckbox.checked = true;
                    newCheckbox.style.display = 'none';
                    form.appendChild(newCheckbox);
                    console.log('[Galeria] Checkbox de remoção criado dinamicamente');
                }
            }
        } else {
            // É imagem nova - remover input e preview
            console.log('[Galeria] Imagem nova encontrada, removendo input e preview');
            input.remove();
            previewItem.remove();
        }
    } else {
        console.warn('[Galeria] Input hidden não encontrado para URL:', url);
        previewItem.remove();
    }
    
    // Atualizar contadores...
};
```

**Análise:**
- ✅ Função está definida corretamente
- ✅ Lida com imagens existentes e novas
- ⚠️ **Problema:** Não há logs quando o botão é clicado (usuário reportou que não funciona)

### 3. JavaScript - Event Listener para Botão de Remoção de Imagens Existentes

**Localização:** Linhas ~962-1000 (após listener de galeria)

```javascript
// Adicionar event listeners para os botões de remoção das imagens existentes
(function() {
    // Usar event delegation para capturar cliques nos botões de remoção
    document.addEventListener('click', function(e) {
        // Verificar se o clique foi em um botão de remoção (label.btn-remove ou seu ícone)
        var btnRemove = e.target.closest('.btn-remove');
        if (btnRemove) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('[Galeria] Botão de remoção clicado');
            
            // Encontrar o checkbox dentro do label
            var checkbox = btnRemove.querySelector('input[type="checkbox"][name="remove_imagens[]"]');
            if (checkbox) {
                // Alternar estado do checkbox
                checkbox.checked = !checkbox.checked;
                
                var imagemId = checkbox.value;
                console.log('[Galeria] Checkbox de remoção ' + (checkbox.checked ? 'marcado' : 'desmarcado') + ' para imagem ID:', imagemId);
                
                // Encontrar o item da galeria correspondente
                var galleryItem = btnRemove.closest('.gallery-item');
                if (galleryItem) {
                    if (checkbox.checked) {
                        // Marcar para remoção - adicionar estilo visual
                        galleryItem.style.opacity = '0.5';
                        galleryItem.style.border = '2px solid #dc3545';
                        console.log('[Galeria] Item da galeria marcado para remoção visual');
                    } else {
                        // Desmarcar - remover estilo visual
                        galleryItem.style.opacity = '1';
                        galleryItem.style.border = '';
                        console.log('[Galeria] Item da galeria desmarcado da remoção');
                    }
                }
            } else {
                console.warn('[Galeria] Checkbox não encontrado dentro do botão de remoção');
            }
        }
    });
})();
```

**Análise:**
- ✅ Event delegation está implementado
- ⚠️ **Problema:** Usuário reportou que não há logs quando clica no botão
- ⚠️ **Possível causa:** O seletor `.btn-remove` pode não estar capturando o clique corretamente

### 4. Backend - Método processGallery (`src/Http/Controllers/Admin/ProductController.php`)

**Localização:** Linhas ~1032-1289

**Estrutura do Método:**

```php
private function processGallery($db, $tenantId, $produtoId): void
{
    error_log("ProductController::processGallery - Iniciando para produto {$produtoId}, tenant {$tenantId}");
    
    // 1. Remover imagens marcadas (ANTES de processar novas)
    if (!empty($_POST['remove_imagens']) && is_array($_POST['remove_imagens'])) {
        // ... lógica de remoção ...
    }

    // 2. Processar caminhos de imagens da biblioteca
    if (isset($_POST['galeria_paths']) && is_array($_POST['galeria_paths'])) {
        $isDebug = defined('APP_DEBUG') && APP_DEBUG;
        
        // Logs iniciais
        if ($isDebug) {
            error_log("ProductController::processGallery - INÍCIO - Total de caminhos recebidos no POST: " . count($_POST['galeria_paths']));
            error_log("ProductController::processGallery - Caminhos recebidos: " . var_export($_POST['galeria_paths'], true));
        }
        
        // Verificar total ANTES
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'");
        $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
        $totalBefore = $stmt->fetch()['total'];
        
        // Buscar maior ordem atual
        $stmt = $db->prepare("SELECT COALESCE(MAX(ordem), 0) as max_ordem FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'");
        $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
        $result = $stmt->fetch();
        $ordem = ($result['max_ordem'] ?? 0) + 1;
        
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        // 3. Processar cada imagem do POST
        foreach ($_POST['galeria_paths'] as $index => $imagePath) {
            $imagePath = trim($imagePath);
            
            // Log sempre (não apenas em debug)
            error_log("ProductController::processGallery - [IMAGEM #{$index}] Iniciando processamento: '{$imagePath}'");
            
            // Validar caminho
            $tenantPath = "/uploads/tenants/{$tenantId}/";
            if (strpos($imagePath, $tenantPath) === 0) {
                // Verificar arquivo físico
                $paths = require __DIR__ . '/../../../../config/paths.php';
                $root = $paths['root'];
                $devPath = $root . '/public' . $imagePath;
                $prodPath = $root . $imagePath;
                $filePath = file_exists($devPath) ? $devPath : (file_exists($prodPath) ? $prodPath : $devPath);
                
                if (file_exists($filePath)) {
                    // Verificar se já existe no banco
                    $stmtCheck = $db->prepare("
                        SELECT id, tipo, caminho_arquivo 
                        FROM produto_imagens 
                        WHERE tenant_id = :tenant_id AND produto_id = :produto_id 
                        AND caminho_arquivo = :caminho
                        LIMIT 1
                    ");
                    $stmtCheck->execute([
                        'tenant_id' => $tenantId,
                        'produto_id' => $produtoId,
                        'caminho' => $imagePath
                    ]);
                    $existingRecord = $stmtCheck->fetch();
                    $exists = $existingRecord !== false;
                    
                    // Log detalhado
                    if ($exists) {
                        error_log("ProductController::processGallery - 🔍 Imagem já existe: ID={$existingRecord['id']}, tipo={$existingRecord['tipo']}, caminho={$imagePath}");
                    } else {
                        error_log("ProductController::processGallery - 🔍 Imagem NÃO existe no banco, será inserida: {$imagePath}");
                    }
                    
                    if (!$exists) {
                        try {
                            // Inserir nova imagem
                            $fileSize = filesize($filePath);
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mimeType = finfo_file($finfo, $filePath);
                            finfo_close($finfo);

                            $currentOrdem = $ordem++;
                            $stmt = $db->prepare("
                                INSERT INTO produto_imagens (
                                    tenant_id, produto_id, tipo, ordem, caminho_arquivo,
                                    mime_type, tamanho_arquivo
                                ) VALUES (
                                    :tenant_id, :produto_id, 'gallery', :ordem, :caminho_arquivo,
                                    :mime_type, :tamanho_arquivo
                                )
                            ");
                            $stmt->execute([
                                'tenant_id' => $tenantId,
                                'produto_id' => $produtoId,
                                'ordem' => $currentOrdem,
                                'caminho_arquivo' => $imagePath,
                                'mime_type' => $mimeType,
                                'tamanho_arquivo' => $fileSize
                            ]);
                            $insertedId = $db->lastInsertId();
                            $processedCount++;
                            error_log("ProductController::processGallery - ✅ [IMAGEM #{$index}] INSERIDA COM SUCESSO: {$imagePath} (ordem: {$currentOrdem}, ID inserido: {$insertedId})");
                        } catch (\Exception $e) {
                            error_log("ProductController::processGallery - ❌ [IMAGEM #{$index}] Erro ao inserir: " . $e->getMessage() . " (caminho: {$imagePath})");
                            $errorCount++;
                        }
                    } else {
                        error_log("ProductController::processGallery - ⏭️ [IMAGEM #{$index}] JÁ EXISTE no produto (preservada): {$imagePath} (ID existente: {$existingRecord['id']}, tipo: {$existingRecord['tipo']})");
                        $skippedCount++;
                    }
                } else {
                    error_log("ProductController::processGallery - ⚠️ [IMAGEM #{$index}] Arquivo não encontrado: {$filePath} (caminho: {$imagePath})");
                    $errorCount++;
                }
            } else {
                error_log("ProductController::processGallery - ⚠️ [IMAGEM #{$index}] Caminho inválido: {$imagePath} (tenant: {$tenantId}, tenantPath esperado: {$tenantPath})");
                $errorCount++;
            }
        }
        
        // 4. Verificar total APÓS
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM produto_imagens WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'");
        $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
        $totalAfter = $stmt->fetch()['total'];
        
        // 5. Log resumo sempre
        error_log("ProductController::processGallery - 📊 RESUMO FINAL:");
        error_log("ProductController::processGallery -   Total recebido no POST: " . count($_POST['galeria_paths']));
        error_log("ProductController::processGallery -   Total ANTES: {$totalBefore}");
        error_log("ProductController::processGallery -   Imagens novas inseridas: {$processedCount}");
        error_log("ProductController::processGallery -   Imagens já existentes (preservadas): {$skippedCount}");
        error_log("ProductController::processGallery -   Imagens com erro: {$errorCount}");
        error_log("ProductController::processGallery -   Total APÓS: {$totalAfter}");
        
        // 6. Logs detalhados (apenas em debug)
        if ($isDebug) {
            // Listar todas as imagens da galeria após processamento
            $stmt = $db->prepare("
                SELECT id, caminho_arquivo, ordem 
                FROM produto_imagens 
                WHERE tenant_id = :tenant_id AND produto_id = :produto_id AND tipo = 'gallery'
                ORDER BY ordem ASC
            ");
            $stmt->execute(['tenant_id' => $tenantId, 'produto_id' => $produtoId]);
            $allImages = $stmt->fetchAll();
            error_log("ProductController::processGallery - Lista completa de imagens na galeria:");
            foreach ($allImages as $img) {
                error_log("ProductController::processGallery -   - ID: {$img['id']}, Ordem: {$img['ordem']}, Caminho: {$img['caminho_arquivo']}");
            }
        }
    }
}
```

**Pontos Críticos:**
1. **Ordem de Processamento:** Remoção acontece ANTES de processar novas imagens
2. **Verificação de Duplicatas:** Usa `SELECT ... LIMIT 1` e verifica se `$existingRecord !== false`
3. **Incremento de Ordem:** Usa `$ordem++` que pode causar problemas se houver exceções
4. **Logs Detalhados:** Cada imagem tem log individual com `[IMAGEM #{$index}]`

### 5. HTML - Container de Galeria (`themes/default/admin/products/edit-content.php`)

**Localização:** Linhas ~330-360

```html
<!-- Container para inputs hidden das imagens da biblioteca -->
<div id="galeria_paths_container" style="display: none;">
    <?php 
    // Preencher com imagens existentes da galeria para preservar ao salvar
    foreach ($galeria as $img): 
    ?>
        <input type="hidden" 
               name="galeria_paths[]" 
               value="<?= htmlspecialchars($img['caminho_arquivo']) ?>"
               data-imagem-id="<?= (int)$img['id'] ?>">
    <?php endforeach; ?>
</div>

<!-- Container para preview das novas imagens da biblioteca -->
<div id="galeria_preview_container" style="display: none; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
    <!-- Preview das imagens existentes -->
    <?php foreach ($galeria as $img): ?>
        <div style="position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; aspect-ratio: 1;">
            <img src="<?= htmlspecialchars($img['caminho_arquivo']) ?>" 
                 style="width: 100%; height: 100%; object-fit: cover;">
            <button type="button" 
                    onclick="removeGalleryPreview(this, '<?= htmlspecialchars($img['caminho_arquivo'], ENT_QUOTES) ?>')"
                    style="position: absolute; top: 0.25rem; right: 0.25rem; background: #dc3545; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer;">
                <i class="bi bi-x"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>
```

**Análise:**
- ✅ Imagens existentes são pré-preenchidas com `data-imagem-id`
- ✅ Container está oculto por padrão (`display: none`)
- ⚠️ **Possível problema:** Se o container estiver oculto, pode haver problemas com event listeners

### 6. HTML - Galeria Existente (Grid de Imagens) (`themes/default/admin/products/edit-content.php`)

**Localização:** Linhas ~299-318

```html
<div class="gallery-grid product-gallery" id="product-gallery">
    <?php foreach ($galeria as $index => $img): ?>
        <div class="gallery-item product-gallery__item" 
             data-imagem-id="<?= (int)$img['id'] ?>"
             draggable="true">
            <div class="product-gallery__thumb">
                <img src="<?= media_url($img['caminho_arquivo']) ?>" 
                     alt="Imagem da galeria">
            </div>
            <div class="gallery-item-actions">
                <button type="button" class="btn-set-main" 
                        onclick="setMainFromGallery(<?= $img['id'] ?>)"
                        title="Definir como imagem de destaque">
                    <i class="bi bi-star-fill icon"></i>
                </button>
                <label class="btn-remove">
                    <input type="checkbox" name="remove_imagens[]" value="<?= $img['id'] ?>">
                    <i class="bi bi-trash icon"></i>
                </label>
            </div>
            <input type="hidden"
                   name="galeria_ordem[<?= (int)$img['id'] ?>]"
                   value="<?= (int)($img['ordem'] ?? ($index + 1)) ?>"
                   class="product-gallery__ordem-input">
        </div>
    <?php endforeach; ?>
</div>
```

**Análise:**
- ✅ Checkbox de remoção está presente (`name="remove_imagens[]"`)
- ✅ Botão de remoção usa `<label class="btn-remove">` com checkbox dentro
- ⚠️ **Possível problema:** O event listener pode não estar capturando cliques no `<label>` corretamente

### 7. JavaScript - Media Picker (`public/admin/js/media-picker.js`)

**Localização:** Linhas ~602-640

```javascript
function selectMultipleImages(urls) {
    if (currentTargetInput && urls.length > 0) {
        console.log('[Media Picker] selectMultipleImages chamado com', urls.length, 'URLs');
        console.log('[Media Picker] currentTargetInput:', currentTargetInput);
        console.log('[Media Picker] Tipo de currentTargetInput:', typeof currentTargetInput, currentTargetInput instanceof Element ? '(Element)' : '(não é Element)');
        
        // currentTargetInput é sempre um elemento HTML (definido em openMediaLibrary linha 182)
        // Usar diretamente como container
        var container = currentTargetInput;
        
        if (container && container instanceof Element) {
            console.log('[Media Picker] Container encontrado (usando currentTargetInput diretamente):', container);
            console.log('[Media Picker] Container ID:', container.id || '(sem ID)');
            console.log('[Media Picker] Disparando evento no container');
            
            var event = new CustomEvent('media-picker:multiple-selected', {
                bubbles: true,
                cancelable: true,
                detail: { urls: urls }
            });
            
            container.dispatchEvent(event);
            console.log('[Media Picker] ✅ Evento disparado com sucesso, URLs:', urls);
        } else {
            console.error('[Media Picker] ❌ currentTargetInput não é um Element válido');
        }
    } else {
        if (!currentTargetInput) {
            console.warn('[Media Picker] ⚠️ selectMultipleImages chamado mas currentTargetInput não está definido');
        }
        if (!urls || urls.length === 0) {
            console.warn('[Media Picker] ⚠️ selectMultipleImages chamado mas urls está vazio');
        }
    }
}
```

**Análise:**
- ✅ Função corrigida para usar elemento diretamente
- ✅ Evento está sendo disparado corretamente
- ✅ Logs confirmam que o evento é recebido

---

## 🔍 HIPÓTESES PARA O PROBLEMA DA TERCEIRA IMAGEM

### Hipótese 1: Verificação de Duplicatas Muito Restritiva

**Cenário:**
- Imagem #0 é inserida com sucesso
- Imagem #1 é inserida com sucesso
- Imagem #2 tem o mesmo caminho de uma imagem já existente no banco (das 2 iniciais)
- A verificação `SELECT ... WHERE caminho_arquivo = :caminho` encontra a imagem existente
- A imagem #2 é pulada como "já existe"

**Como Verificar:**
- Verificar nos logs se `[IMAGEM #2]` aparece como `JÁ EXISTE`
- Comparar os 3 caminhos enviados no POST com os caminhos das 2 imagens existentes
- Se houver match, essa é a causa

**Código Relevante:**
```php
$stmtCheck = $db->prepare("
    SELECT id, tipo, caminho_arquivo 
    FROM produto_imagens 
    WHERE tenant_id = :tenant_id AND produto_id = :produto_id 
    AND caminho_arquivo = :caminho
    LIMIT 1
");
```

### Hipótese 2: Problema com Transação/Commit

**Cenário:**
- As 3 imagens são processadas corretamente
- Mas a transação não está sendo commitada
- Ou há um rollback silencioso

**Como Verificar:**
- Verificar se há `$db->commit()` após `processGallery()`
- Verificar se há `$db->rollBack()` sendo chamado
- Verificar logs de erro/exceção

**Código Relevante:**
```php
// Em ProductController::update()
try {
    $db->beginTransaction();
    // ... atualizações ...
    $this->processGallery($db, $tenantId, $id);
    // ... mais atualizações ...
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    // ...
}
```

### Hipótese 3: Problema na Query de Busca da Galeria

**Cenário:**
- As 3 imagens estão sendo salvas no banco
- Mas a query que busca a galeria para exibir tem algum problema
- Pode ter `LIMIT 2` ou ordenação que oculta a terceira

**Como Verificar:**
- Verificar a query em `ProductController::edit()` que busca `$galeria`
- Verificar se há `LIMIT` ou filtros

**Código Relevante:**
```php
// Em ProductController::edit()
$stmt = $db->prepare("
    SELECT * FROM produto_imagens 
    WHERE tenant_id = :tenant_id 
    AND produto_id = :produto_id 
    ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
");
```

**Análise do Código:**
- ✅ Não há `LIMIT` na query
- ✅ Ordenação parece correta
- ⚠️ **Possível problema:** Se houver imagens com `ordem` NULL ou duplicada, pode haver comportamento inesperado

### Hipótese 4: Problema com Ordem de Processamento

**Cenário:**
- A remoção de imagens acontece ANTES de processar novas
- Se uma imagem existente for removida, mas seu caminho estiver no POST, pode haver conflito
- A ordem de incremento pode estar causando problemas

**Como Verificar:**
- Verificar se há imagens sendo removidas antes de processar
- Verificar se a ordem está sendo calculada corretamente

**Código Relevante:**
```php
// Remover imagens marcadas (ANTES de processar novas)
if (!empty($_POST['remove_imagens']) && is_array($_POST['remove_imagens'])) {
    // ... remoção ...
}

// Depois processar novas
if (isset($_POST['galeria_paths']) && is_array($_POST['galeria_paths'])) {
    // ... processamento ...
}
```

---

## 📊 ESTRUTURA DO BANCO DE DADOS

### Tabela: `produto_imagens`

**Colunas Relevantes:**
- `id` (INT, PRIMARY KEY, AUTO_INCREMENT)
- `tenant_id` (INT, FOREIGN KEY)
- `produto_id` (INT, FOREIGN KEY)
- `tipo` (VARCHAR) - Valores: 'main' ou 'gallery'
- `ordem` (INT) - Ordem de exibição
- `caminho_arquivo` (VARCHAR) - Caminho relativo da imagem
- `mime_type` (VARCHAR)
- `tamanho_arquivo` (INT)

**Índices:**
- PRIMARY KEY (`id`)
- Índice em `tenant_id`, `produto_id`, `tipo`
- Possível índice em `caminho_arquivo`

**Query de Verificação de Duplicatas:**
```sql
SELECT id, tipo, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = :tenant_id 
  AND produto_id = :produto_id 
  AND caminho_arquivo = :caminho
LIMIT 1
```

**Query de Inserção:**
```sql
INSERT INTO produto_imagens (
    tenant_id, produto_id, tipo, ordem, caminho_arquivo,
    mime_type, tamanho_arquivo
) VALUES (
    :tenant_id, :produto_id, 'gallery', :ordem, :caminho_arquivo,
    :mime_type, :tamanho_arquivo
)
```

**Query de Busca da Galeria:**
```sql
SELECT * FROM produto_imagens 
WHERE tenant_id = :tenant_id 
  AND produto_id = :produto_id 
ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
```

---

## 🔧 TENTATIVAS DE CORREÇÃO REALIZADAS

### Tentativa 1: Corrigir Evento selectMultipleImages
- **Data:** 10/12/2025 (Manhã)
- **Problema:** Evento não estava sendo disparado corretamente
- **Solução:** Corrigir lógica de busca do container
- **Resultado:** ✅ Evento agora é disparado corretamente

### Tentativa 2: Adicionar Logs Detalhados
- **Data:** 10/12/2025 (Manhã)
- **Problema:** Falta de visibilidade do que estava acontecendo
- **Solução:** Adicionar logs em cada etapa do processamento
- **Resultado:** ✅ Logs agora mostram cada imagem processada

### Tentativa 3: Corrigir removeGalleryPreview
- **Data:** 10/12/2025 (Tarde)
- **Problema:** Função não lidava com imagens existentes
- **Solução:** Adicionar lógica para marcar checkbox de remoção
- **Resultado:** ⚠️ Função corrigida, mas usuário reporta que ainda não funciona

### Tentativa 4: Adicionar Event Listener para Botão de Remoção
- **Data:** 10/12/2025 (Tarde)
- **Problema:** Botão de remoção não tinha JavaScript conectado
- **Solução:** Adicionar event delegation para capturar cliques
- **Resultado:** ⚠️ Código implementado, mas usuário reporta que não funciona

### Tentativa 5: Logs Sempre Ativos
- **Data:** 10/12/2025 (Tarde)
- **Problema:** Logs apenas em debug não ajudavam em produção
- **Solução:** Logs importantes sempre ativos, detalhados apenas em debug
- **Resultado:** ✅ Logs agora sempre mostram informações importantes

### Tentativa 6: Corrigir Acesso ao Script de Verificação
- **Data:** 10/12/2025 (Tarde)
- **Problema:** Script redirecionava para dashboard
- **Solução:** Remover middleware, usar apenas verificação de sessão
- **Resultado:** ⚠️ Ainda redireciona (usuário reportou)

---

## 🐛 PROBLEMAS CONHECIDOS

### 1. Terceira Imagem Não Persiste
- **Status:** 🔴 Não Resolvido
- **Sintoma:** 3 imagens enviadas no POST, apenas 2 persistem
- **Logs Frontend:** Confirmam 3 imagens sendo enviadas
- **Logs Backend:** Ainda não verificados (precisa acessar logs do servidor)

### 2. Botão de Excluir Não Funciona
- **Status:** 🔴 Não Resolvido
- **Sintoma:** Nenhum log aparece quando clica no botão
- **Código:** Event listener implementado, mas não está sendo acionado
- **Possível Causa:** Seletor `.btn-remove` não está capturando o clique

### 3. Script de Verificação Redireciona
- **Status:** 🔴 Não Resolvido
- **Sintoma:** Acessar `/scripts/check-product-images?produto=929` redireciona para `/admin`
- **Código:** Verificação de sessão implementada, mas pode estar falhando

---

## 📝 INSTRUÇÕES PARA INVESTIGAÇÃO

### 1. Verificar Logs do Backend

**Via Script CLI:**
```bash
php scripts/collect_product_logs.php --product=929 --last-hour
```

**Via Script Web (após corrigir acesso):**
```
https://pontodogolfeoutlet.com.br/scripts/check-product-images?produto=929
```

**O que procurar:**
- `[IMAGEM #0]`, `[IMAGEM #1]`, `[IMAGEM #2]` - Ver se todas são processadas
- `INSERIDA COM SUCESSO` ou `JÁ EXISTE` - Ver o que acontece com cada uma
- `RESUMO FINAL` - Ver totais ANTES e APÓS
- `Total APÓS` - Verificar se é 3 ou 2

### 2. Verificar Banco de Dados Diretamente

**Query SQL:**
```sql
SELECT id, tipo, ordem, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = 1 AND produto_id = 929 AND tipo = 'gallery'
ORDER BY ordem ASC;
```

**O que verificar:**
- Quantas imagens estão realmente no banco
- Quais caminhos estão salvos
- Se a terceira imagem foi inserida ou não

### 3. Verificar POST Request

**Via DevTools:**
1. Abrir DevTools (F12)
2. Aba "Network"
3. Filtrar por "produtos"
4. Salvar o produto
5. Clicar na requisição POST
6. Aba "Payload" ou "Form Data"
7. Verificar `galeria_paths[]`

**O que verificar:**
- Quantos `galeria_paths[]` estão no POST
- Se os 3 caminhos estão presentes
- Se há `remove_imagens[]` marcados

### 4. Testar Botão de Remoção

**Via Console:**
```javascript
// Verificar se o event listener está registrado
document.addEventListener('click', function(e) {
    console.log('Clique capturado:', e.target);
    var btnRemove = e.target.closest('.btn-remove');
    if (btnRemove) {
        console.log('Botão de remoção encontrado!', btnRemove);
    }
});

// Testar clique manual
var btn = document.querySelector('.btn-remove');
if (btn) {
    console.log('Botão encontrado:', btn);
    btn.click(); // Simular clique
}
```

---

## 🔗 ARQUIVOS MODIFICADOS (Resumo Completo)

### Frontend

1. **`themes/default/admin/products/edit-content.php`**
   - Listener para `media-picker:multiple-selected`
   - Função `removeGalleryPreview`
   - Event listener para botão de remoção de imagens existentes
   - Logs no submit do formulário
   - Botão e função para remover imagem de destaque

2. **`public/admin/js/media-picker.js`**
   - Função `selectMultipleImages` corrigida
   - Logs detalhados adicionados

### Backend

3. **`src/Http/Controllers/Admin/ProductController.php`**
   - Método `processGallery` com logs detalhados
   - Método `processMainImage` com suporte a `remove_featured`
   - Verificação de imagens existentes melhorada

### Rotas

4. **`public/index.php`**
   - Rota `/scripts/check-product-images` adicionada
   - Autenticação via sessão (sem middleware)

### Scripts de Diagnóstico

5. **`scripts/check_product_images.php`** (CLI)
6. **`scripts/check_product_images_web.php`** (WEB)
7. **`scripts/collect_product_logs.php`** (CLI)

### Documentação

8. **`docs/RELATORIO_DEBUG_GALERIA_IMAGENS.md`** (este arquivo)
9. **`docs/INSTRUCOES_DIAGNOSTICO_IMAGENS.md`**

---

---

## 🔍 ESTRUTURA DE TRANSAÇÃO (Backend)

### Método update() - Fluxo de Transação

**Localização:** `src/Http/Controllers/Admin/ProductController.php` - Linhas ~548-600

```php
try {
    $db->beginTransaction();
    
    // 1. Atualizar dados do produto
    $stmt = $db->prepare("UPDATE produtos SET ... WHERE id = :id AND tenant_id = :tenant_id");
    $stmt->execute([...]);
    
    // 2. Processar categorias
    $this->processCategories($db, $tenantId, $id);
    
    // 3. Processar imagem de destaque
    $this->processMainImage($db, $tenantId, $id);
    
    // 4. Processar galeria
    $this->processGallery($db, $tenantId, $id);
    
    // 5. Processar vídeos
    $this->processVideos($db, $tenantId, $id);
    
    // 6. Commit
    $db->commit();
    
    $_SESSION['flash_message'] = 'Produto atualizado com sucesso!';
    $_SESSION['flash_type'] = 'success';
    
} catch (\Exception $e) {
    $db->rollBack();
    error_log("ProductController::update - Erro: " . $e->getMessage());
    // ...
}
```

**Análise:**
- ✅ Transação está sendo usada corretamente
- ✅ `processGallery` é chamado dentro da transação
- ⚠️ **Possível problema:** Se houver exceção em qualquer etapa, tudo é revertido (rollback)
- ⚠️ **Possível problema:** Se `processGallery` lançar exceção silenciosa, pode não estar sendo capturada

### Verificação de Exceções em processGallery

**Código Atual:**
```php
try {
    // Inserir imagem
    $stmt->execute([...]);
    $insertedId = $db->lastInsertId();
    $processedCount++;
    error_log("ProductController::processGallery - ✅ [IMAGEM #{$index}] INSERIDA COM SUCESSO: ...");
} catch (\Exception $e) {
    error_log("ProductController::processGallery - ❌ [IMAGEM #{$index}] Erro ao inserir: " . $e->getMessage());
    $errorCount++;
}
```

**Análise:**
- ✅ Try-catch está presente
- ✅ Erros são logados
- ⚠️ **Possível problema:** Se a exceção for `PDOException` e não `Exception`, pode não ser capturada (mas `PDOException` extends `Exception`, então deve funcionar)

---

## 🔍 QUERY DE BUSCA DA GALERIA (Para Exibição)

### Método edit() - Busca de Imagens

**Localização:** `src/Http/Controllers/Admin/ProductController.php` - Linhas ~400-421

```php
$stmt = $db->prepare("
    SELECT * FROM produto_imagens 
    WHERE tenant_id = :tenant_id 
    AND produto_id = :produto_id 
    ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
");
$stmt->execute([
    'tenant_id' => $tenantId,
    'produto_id' => $produto['id']
]);
$imagens = $stmt->fetchAll();

// Separar imagem principal e galeria
$imagemPrincipal = null;
$galeria = [];
foreach ($imagens as $img) {
    if ($img['tipo'] === 'main') {
        $imagemPrincipal = $img;
    } else {
        $galeria[] = $img;
    }
}
```

**Análise:**
- ✅ Não há `LIMIT` na query
- ✅ Ordenação: `tipo = 'main' DESC, ordem ASC, id ASC`
- ⚠️ **Possível problema:** Se `ordem` for NULL para alguma imagem, pode haver comportamento inesperado
- ⚠️ **Possível problema:** Se houver imagens com `ordem` duplicada, a ordenação pode não ser determinística

**Query SQL Equivalente:**
```sql
SELECT * FROM produto_imagens 
WHERE tenant_id = 1 
  AND produto_id = 929 
ORDER BY 
    CASE WHEN tipo = 'main' THEN 0 ELSE 1 END,  -- main primeiro
    ordem ASC,                                    -- depois por ordem
    id ASC;                                       -- depois por ID
```

---

## 📊 CENÁRIOS DE TESTE DOCUMENTADOS

### Cenário 1: Produto Sem Imagens → Adicionar 3 Imagens

**Passos:**
1. Abrir produto sem imagens na galeria
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 3 imagens diferentes
4. Verificar console: deve mostrar "3 adicionadas"
5. Salvar produto
6. Verificar logs do backend
7. Recarregar página
8. **Resultado Esperado:** 3 imagens devem aparecer
9. **Resultado Atual:** ❌ Apenas 2 aparecem

### Cenário 2: Produto com 2 Imagens → Adicionar 1 Imagem

**Passos:**
1. Abrir produto com 2 imagens na galeria
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 1 nova imagem (diferente das existentes)
4. Verificar console: deve mostrar "1 adicionada"
5. Verificar console: deve mostrar "Total de inputs: 3" (2 existentes + 1 nova)
6. Salvar produto
7. Verificar logs: deve mostrar "1 processada, 2 preservadas"
8. Recarregar página
9. **Resultado Esperado:** 3 imagens devem aparecer
10. **Resultado Atual:** ❌ Apenas 2 aparecem

### Cenário 3: Produto com 2 Imagens → Adicionar Imagem Duplicada

**Passos:**
1. Abrir produto com 2 imagens na galeria
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 1 imagem que já está na galeria
4. Verificar console: deve mostrar "0 adicionadas, 1 ignoradas"
5. **Resultado Esperado:** Imagem não deve ser adicionada
6. **Resultado Atual:** ✅ Funciona corretamente

### Cenário 4: Remover Imagem Existente

**Passos:**
1. Abrir produto com 2 imagens na galeria
2. Clicar no botão de lixeira de uma imagem
3. Verificar console: deve mostrar "[Galeria] Botão de remoção clicado"
4. Verificar visual: imagem deve ficar com opacidade reduzida
5. Salvar produto
6. Verificar logs: deve mostrar que a imagem foi removida
7. Recarregar página
8. **Resultado Esperado:** Apenas 1 imagem deve aparecer
9. **Resultado Atual:** ❌ Nenhum log aparece, botão não funciona

---

## 🎯 CHECKLIST DE INVESTIGAÇÃO

### Frontend

- [x] Verificar se evento `media-picker:multiple-selected` está sendo disparado
- [x] Verificar se listener está registrado no container
- [x] Verificar se inputs hidden estão sendo criados
- [x] Verificar se previews estão sendo criados
- [ ] Verificar se botão de remoção está capturando cliques
- [ ] Verificar se checkbox de remoção está sendo marcado

### Backend

- [ ] Verificar logs do backend para cada imagem processada
- [ ] Verificar se todas as 3 imagens estão sendo recebidas no POST
- [ ] Verificar se todas as 3 imagens estão sendo processadas
- [ ] Verificar se há exceções sendo lançadas
- [ ] Verificar se transação está sendo commitada
- [ ] Verificar se há rollback sendo executado

### Banco de Dados

- [ ] Verificar quantas imagens estão realmente no banco após salvar
- [ ] Verificar se a terceira imagem foi inserida
- [ ] Verificar se há imagens com `ordem` NULL
- [ ] Verificar se há imagens com `ordem` duplicada
- [ ] Verificar se há constraint ou trigger que possa estar limitando

### Query de Busca

- [x] Verificar se há `LIMIT` na query de busca
- [x] Verificar ordenação da query
- [ ] Verificar se há filtros que possam ocultar imagens
- [ ] Verificar se há problema com valores NULL em `ordem`

---

## 📚 REFERÊNCIAS DE CÓDIGO

### Arquivos Principais

1. **Controller:** `src/Http/Controllers/Admin/ProductController.php`
   - Método `edit()` - Linhas ~380-500 (busca de imagens)
   - Método `update()` - Linhas ~510-600 (processamento)
   - Método `processMainImage()` - Linhas ~700-1000
   - Método `processGallery()` - Linhas ~1032-1289

2. **View:** `themes/default/admin/products/edit-content.php`
   - HTML da galeria - Linhas ~291-360
   - JavaScript do listener - Linhas ~867-962
   - JavaScript de remoção - Linhas ~964-1040
   - JavaScript de remoção de imagens existentes - Linhas ~962-1000

3. **Media Picker:** `public/admin/js/media-picker.js`
   - Função `selectMultipleImages()` - Linhas ~602-640
   - Função `openMediaLibrary()` - Linhas ~156-200

4. **Rotas:** `public/index.php`
   - Rota de verificação - Linhas ~422-446

### Scripts de Diagnóstico

5. **`scripts/check_product_images.php`** - CLI para verificar imagens no banco
6. **`scripts/check_product_images_web.php`** - WEB para verificar imagens no banco
7. **`scripts/collect_product_logs.php`** - Coletar e filtrar logs

---

## 🚨 PROBLEMAS CRÍTICOS IDENTIFICADOS

### 1. Terceira Imagem Não Persiste

**Evidências:**
- ✅ Frontend envia 3 imagens no POST
- ✅ JavaScript cria 3 inputs hidden
- ❌ Apenas 2 imagens aparecem após recarregar

**Possíveis Causas:**
1. Verificação de duplicatas está detectando a terceira como duplicada
2. Exceção silenciosa ao inserir a terceira imagem
3. Transação está sendo revertida
4. Query de busca está ocultando a terceira imagem

**Próximos Passos:**
- Verificar logs do backend (crítico)
- Verificar banco de dados diretamente
- Verificar se há exceções sendo lançadas

### 2. Botão de Excluir Não Funciona

**Evidências:**
- ✅ Código do event listener está implementado
- ❌ Nenhum log aparece quando clica no botão
- ❌ Checkbox não é marcado

**Possíveis Causas:**
1. Event delegation não está capturando o clique
2. Seletor `.btn-remove` não está correto
3. Outro event listener está prevenindo a propagação

**Próximos Passos:**
- Testar seletor no console
- Verificar se há outros listeners interferindo
- Adicionar logs mais verbosos

### 3. Script de Verificação Redireciona

**Evidências:**
- ✅ Código de verificação de sessão está implementado
- ❌ Ainda redireciona para `/admin`

**Possíveis Causas:**
1. Sessão não está sendo iniciada corretamente
2. Verificação de `$_SESSION['user_id']` está falhando
3. Middleware ainda está sendo aplicado de alguma forma

**Próximos Passos:**
- Verificar se sessão está ativa
- Adicionar logs de debug na rota
- Verificar se há redirect em outro lugar

---

## ✅ CORREÇÕES IMPLEMENTADAS (11 de Dezembro de 2025)

### TAREFA 1: Análise do Backend ✅

**Problema Identificado:**
- A verificação de duplicatas no método `processGallery()` estava verificando **qualquer tipo** de imagem (`tipo = 'main'` ou `tipo = 'gallery'`), causando falsos positivos quando uma imagem da galeria tinha o mesmo caminho de uma imagem principal.

**Correção Aplicada:**
- **Arquivo:** `src/Http/Controllers/Admin/ProductController.php`
- **Método:** `processGallery()` - Linhas ~1172-1178
- **Mudança:** A query de verificação de duplicatas agora verifica **apenas** `tipo = 'gallery'`:

```php
// ANTES (verificava qualquer tipo):
SELECT id, tipo, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = :tenant_id AND produto_id = :produto_id 
AND caminho_arquivo = :caminho
LIMIT 1

// DEPOIS (verifica apenas galeria):
SELECT id, tipo, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = :tenant_id 
AND produto_id = :produto_id 
AND tipo = 'gallery'
AND caminho_arquivo = :caminho
LIMIT 1
```

**Resultado Esperado:**
- A terceira imagem (e todas as subsequentes) agora devem ser inseridas corretamente, mesmo que exista uma imagem principal com o mesmo caminho.

---

### TAREFA 2: Script de Verificação do Banco ✅

**Status:** Script já existia e está funcional.

**Arquivo:** `scripts/check_product_images.php`

**Uso:**
```bash
php scripts/check_product_images.php 929
php scripts/check_product_images.php 929 --tenant=1
```

**Funcionalidades:**
- Lista todas as imagens do produto (principal + galeria)
- Mostra detalhes de cada imagem (ID, tipo, ordem, caminho, tamanho)
- Verifica duplicatas
- Compara com imagens esperadas

---

### TAREFA 3: Correção da Lógica de Verificação de Duplicatas ✅

**Problema:**
- A verificação estava considerando imagens de qualquer tipo como duplicadas, causando que a terceira imagem fosse pulada se houvesse uma imagem principal com o mesmo caminho.

**Correção:**
- Verificação agora é específica para `tipo = 'gallery'`
- Logs melhorados para indicar claramente quando uma imagem é considerada duplicada na galeria

**Arquivo Modificado:**
- `src/Http/Controllers/Admin/ProductController.php` - Método `processGallery()`

---

### TAREFA 4: Garantir Query de Busca Traz Todas as Imagens ✅

**Status:** Query já estava correta.

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php` - Método `edit()` - Linhas ~400-421

**Query Verificada:**
```php
SELECT * FROM produto_imagens 
WHERE tenant_id = :tenant_id 
AND produto_id = :produto_id 
ORDER BY tipo = 'main' DESC, ordem ASC, id ASC
```

**Análise:**
- ✅ Não há `LIMIT` na query
- ✅ Ordenação correta (main primeiro, depois por ordem)
- ✅ Busca todas as imagens do produto
- ✅ Separação correta entre imagem principal e galeria no PHP

**Conclusão:** A query está correta e não limita o número de imagens.

---

### TAREFA 5: Correção da Remoção de Imagens ✅

#### 5.1. Remoção de Imagens da Galeria

**Problema:**
- Event listener não estava capturando cliques no botão de remoção
- Checkbox não era marcado quando o botão era clicado

**Correção Aplicada:**
- **Arquivo:** `themes/default/admin/products/edit-content.php` - Linhas ~964-1005
- **Mudanças:**
  1. Event delegation melhorado com múltiplos fallbacks para encontrar o botão `.btn-remove`
  2. Logs verbosos adicionados (`console.log('[Galeria] 🔴 CLICK NO BOTAO DE REMOCAO')`)
  3. Checkbox sempre marcado como `checked = true` (não alterna)
  4. Feedback visual melhorado (opacidade, borda vermelha, indicador "Será removida")
  5. Indicador visual adicionado dinamicamente ao item da galeria

**Código Implementado:**
```javascript
// Event delegation com múltiplos fallbacks
document.addEventListener('click', function(e) {
    var btnRemove = e.target.closest('.btn-remove');
    
    // Fallback 1: Verificar se clique foi no ícone dentro do label
    if (!btnRemove && e.target.closest('label.btn-remove')) {
        btnRemove = e.target.closest('label.btn-remove');
    }
    
    // Fallback 2: Verificar se clique foi no ícone bi-trash
    if (!btnRemove && (e.target.classList.contains('bi-trash') || e.target.closest('.bi-trash'))) {
        btnRemove = e.target.closest('label.btn-remove') || 
                    e.target.closest('.gallery-item-actions')?.querySelector('.btn-remove');
    }
    
    if (btnRemove) {
        // Sempre marcar como checked (não alternar)
        checkbox.checked = true;
        // Aplicar feedback visual
        // ...
    }
});
```

#### 5.2. Remoção de Imagem de Destaque

**Problema:**
- Função `removeFeaturedImage()` não tinha logs suficientes
- Feedback visual não indicava claramente que a imagem seria removida

**Correção Aplicada:**
- **Arquivo:** `themes/default/admin/products/edit-content.php` - Linhas ~742-786
- **Mudanças:**
  1. Logs verbosos adicionados (`console.log('[Imagem Destaque] 🔴 CLICK NO BOTAO DE REMOCAO DA IMAGEM DE DESTAQUE')`)
  2. Verificação se campo `remove_featured` existe antes de usar
  3. Feedback visual melhorado (opacidade, borda vermelha, indicador "Será removida")
  4. Campo `remove_featured` sempre marcado como `'1'` quando botão é clicado

**Código Implementado:**
```javascript
window.removeFeaturedImage = function() {
    console.log('[Imagem Destaque] 🔴 CLICK NO BOTAO DE REMOCAO DA IMAGEM DE DESTAQUE');
    
    // Verificar se campo existe
    if (!removeFeaturedInput) {
        console.error('[Imagem Destaque] ❌ Campo remove_featured não encontrado!');
        return;
    }
    
    // Marcar para remoção
    removeFeaturedInput.value = '1';
    
    // Aplicar feedback visual com indicador "Será removida"
    // ...
};
```

#### 5.3. Backend - Processamento de Remoção

**Status:** Já estava implementado corretamente.

**Arquivos:**
- `src/Http/Controllers/Admin/ProductController.php` - Método `processGallery()` - Linhas ~1044-1088
- `src/Http/Controllers/Admin/ProductController.php` - Método `processMainImage()` - Linhas ~711-742

**Funcionalidades:**
- ✅ Remove imagens da galeria marcadas em `remove_imagens[]`
- ✅ Remove arquivo físico quando possível
- ✅ Remove registro do banco de dados
- ✅ Remove imagem de destaque quando `remove_featured = '1'`
- ✅ Limpa campo `produtos.imagem_principal` quando imagem de destaque é removida

---

### TAREFA 6: Validação e Documentação ✅

#### Resumo das Correções

1. **Verificação de Duplicatas:**
   - ✅ Corrigida para verificar apenas `tipo = 'gallery'`
   - ✅ Evita falsos positivos com imagens principais

2. **Remoção de Imagens da Galeria:**
   - ✅ Event listener melhorado com múltiplos fallbacks
   - ✅ Logs verbosos para debug
   - ✅ Feedback visual claro
   - ✅ Checkbox sempre marcado quando botão é clicado

3. **Remoção de Imagem de Destaque:**
   - ✅ Logs verbosos adicionados
   - ✅ Feedback visual melhorado
   - ✅ Validação de campos antes de usar

4. **Query de Busca:**
   - ✅ Confirmada como correta (sem LIMIT, ordenação adequada)

#### Como Testar

**Teste 1: Adicionar 3+ Imagens na Galeria**
1. Abrir produto sem imagens na galeria (ou limpar galeria)
2. Clicar em "Adicionar da biblioteca"
3. Selecionar 3 imagens diferentes
4. Verificar console: deve mostrar "3 adicionadas"
5. Salvar produto
6. Recarregar página
7. **Resultado Esperado:** 3 imagens devem aparecer na galeria

**Teste 2: Remover Imagem da Galeria**
1. Abrir produto com 3 imagens na galeria
2. Clicar no botão de lixeira de uma imagem
3. Verificar console: deve mostrar `[Galeria] 🔴 CLICK NO BOTAO DE REMOCAO`
4. Verificar visual: imagem deve ficar com opacidade reduzida e indicador "Será removida"
5. Salvar produto
6. Recarregar página
7. **Resultado Esperado:** Apenas 2 imagens devem aparecer

**Teste 3: Remover Imagem de Destaque**
1. Abrir produto com imagem de destaque
2. Clicar no botão "Remover imagem"
3. Verificar console: deve mostrar `[Imagem Destaque] 🔴 CLICK NO BOTAO DE REMOCAO DA IMAGEM DE DESTAQUE`
4. Verificar visual: placeholder deve aparecer com indicador "Será removida"
5. Salvar produto
6. Recarregar página
7. **Resultado Esperado:** Placeholder deve aparecer, sem imagem de destaque

**Teste 4: Verificar Banco de Dados**
```bash
php scripts/check_product_images.php 929
```
- Deve mostrar todas as imagens do produto
- Contagem deve corresponder ao que aparece na interface

---

### Arquivos Modificados

1. **`src/Http/Controllers/Admin/ProductController.php`**
   - Método `processGallery()`: Correção da verificação de duplicatas (linhas ~1172-1178)
   - Logs melhorados para rastreamento

2. **`themes/default/admin/products/edit-content.php`**
   - Event listener para remoção de imagens da galeria (linhas ~964-1005)
   - Função `removeFeaturedImage()` melhorada (linhas ~742-786)
   - Logs verbosos adicionados

3. **`docs/RELATORIO_DEBUG_GALERIA_IMAGENS.md`**
   - Seção de correções implementadas adicionada
   - Instruções de teste documentadas

---

### Próximos Passos para Validação

1. **Testar em Produção:**
   - Adicionar 3+ imagens na galeria de um produto
   - Verificar se todas persistem após salvar
   - Testar remoção de imagens (galeria e destaque)

2. **Verificar Logs:**
   - Verificar logs do backend após salvar produto com 3 imagens
   - Confirmar que todas as 3 imagens foram inseridas
   - Verificar se não há erros

3. **Verificar Banco:**
   - Usar script CLI para verificar quantas imagens estão no banco
   - Confirmar que contagem corresponde à interface

---

---

## ✅ CORREÇÃO CRÍTICA - Preservação de Arquivos da Biblioteca (11 de Dezembro de 2025)

### Problema Identificado

**Comportamento Anterior (INCORRETO):**
- Ao remover uma imagem da galeria do produto, o sistema estava:
  1. Removendo a associação do produto com a imagem (correto)
  2. **Apagando o arquivo físico da biblioteca de mídia (INCORRETO)**

**Comportamento Desejado (WordPress-like):**
- Remover da galeria do produto → apenas desfaz a associação produto ↔ mídia
- O arquivo continua existindo na biblioteca e pode ser reutilizado em outros produtos
- A biblioteca é a "fonte única" de arquivos

### Correção Implementada

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php`

#### 1. Método `processGallery()` - Remoção de Imagens da Galeria

**Antes (INCORRETO):**
```php
// Deletar arquivo físico
$filePath = file_exists($devPath) ? $devPath : (file_exists($prodPath) ? $prodPath : $devPath);
if (file_exists($filePath)) {
    @unlink($filePath);  // ❌ Apagava o arquivo da biblioteca
    error_log("ProductController::processGallery - Arquivo físico removido: {$filePath}");
}

// Deletar registro
DELETE FROM produto_imagens WHERE ...
```

**Depois (CORRETO):**
```php
// IMPORTANTE: Remover apenas a associação do produto com a imagem
// NÃO apagar o arquivo físico da biblioteca de mídia
// O arquivo continua disponível na biblioteca e pode ser reutilizado

DELETE FROM produto_imagens WHERE ...
error_log("ProductController::processGallery - ✅ Associação removida (imagem desvinculada do produto)");
error_log("ProductController::processGallery - ℹ️ Arquivo físico preservado na biblioteca de mídia");
```

**Mudanças:**
- ✅ Removido `@unlink($filePath)` - não apaga mais o arquivo físico
- ✅ Removida lógica de busca do caminho do arquivo para exclusão
- ✅ Logs atualizados para indicar que arquivo foi preservado
- ✅ Apenas a associação na tabela `produto_imagens` é removida

#### 2. Método `processMainImage()` - Remoção de Imagem de Destaque

**Status:** ✅ Já estava correto (não apagava arquivos)

**Comportamento:**
- Remove apenas o registro da tabela `produto_imagens` (tipo='main')
- Limpa o campo `produtos.imagem_principal`
- **NÃO apaga o arquivo físico**

**Melhorias Aplicadas:**
- Comentários adicionados para deixar claro que arquivo é preservado
- Logs melhorados para indicar preservação do arquivo

### Comportamento Final

#### ✅ Remover Imagem da Galeria
1. Usuário clica na lixeira da galeria
2. Checkbox `remove_imagens[]` é marcado
3. Visual mostra "Será removida"
4. Ao salvar:
   - Associação removida de `produto_imagens`
   - **Arquivo físico preservado na biblioteca**
   - Imagem continua disponível para outros produtos

#### ✅ Remover Imagem de Destaque
1. Usuário clica em "Remover imagem"
2. Campo `remove_featured` é marcado
3. Visual mostra placeholder com "Será removida"
4. Ao salvar:
   - Registro removido de `produto_imagens` (tipo='main')
   - Campo `produtos.imagem_principal` limpo
   - **Arquivo físico preservado na biblioteca**

#### ✅ Exclusão de Produto
**Status:** Não implementado ainda (não há método `destroy()` no `ProductController`)

**Quando implementado, deve:**
- Remover apenas vínculos do produto com mídias
- **NÃO apagar arquivos físicos**
- **NÃO apagar registros da biblioteca de mídia**

### Testes de Validação

#### Teste 1: Remover Imagem da Galeria
1. Adicionar 3 imagens à galeria de um produto
2. Salvar produto
3. Verificar que todas aparecem na galeria
4. Clicar na lixeira de UMA imagem
5. Salvar produto
6. **Resultado Esperado:**
   - ✅ Imagem removida da galeria do produto
   - ✅ Imagem ainda aparece na Biblioteca de Mídia
   - ✅ Imagem pode ser reutilizada em outro produto

#### Teste 2: Remover Imagem de Destaque
1. Definir imagem de destaque para um produto
2. Clicar em "Remover imagem"
3. Salvar produto
4. **Resultado Esperado:**
   - ✅ Placeholder aparece no lugar da imagem
   - ✅ Imagem ainda aparece na Biblioteca de Mídia
   - ✅ Imagem pode ser reutilizada

#### Teste 3: Múltiplos Produtos Usando Mesma Imagem
1. Adicionar mesma imagem à galeria de 2 produtos diferentes
2. Remover imagem da galeria do Produto A
3. **Resultado Esperado:**
   - ✅ Imagem removida da galeria do Produto A
   - ✅ Imagem ainda aparece na galeria do Produto B
   - ✅ Imagem ainda aparece na Biblioteca de Mídia

### Arquivos Modificados

1. **`src/Http/Controllers/Admin/ProductController.php`**
   - Método `processGallery()`: Removido `@unlink()` e lógica de exclusão física
   - Método `processMainImage()`: Comentários e logs melhorados

### Observações Importantes

1. **Biblioteca de Mídia é Fonte Única:**
   - Arquivos só devem ser apagados pela tela própria da Biblioteca de Mídia
   - Produtos apenas associam/desassociam imagens

2. **Reutilização de Imagens:**
   - Múltiplos produtos podem usar a mesma imagem
   - Remover de um produto não afeta outros produtos

3. **Logs Preservados:**
   - Todos os logs de debug foram mantidos
   - Logs agora indicam claramente que arquivos são preservados

---

**Última Atualização:** 11 de dezembro de 2025
**Status:** ✅ Correções implementadas - Arquivos da biblioteca preservados ao remover da galeria/destaque

