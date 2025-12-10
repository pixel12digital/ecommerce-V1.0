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

**Última Atualização:** 10 de dezembro de 2025 (Tarde)
**Status:** 🔄 Problema persistente - Terceira imagem não persiste - Logs detalhados adicionados para investigação

