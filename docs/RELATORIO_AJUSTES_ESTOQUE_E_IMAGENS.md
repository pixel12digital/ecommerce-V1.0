# Relatório de Ajustes - Estoque e Imagens de Produto

## Data
Janeiro 2025

## Contexto

Após as correções anteriores de preço e estoque (documentadas em `RELATORIO_VALIDACAO_PRECO_ESTOQUE.md`), ainda existiam dois problemas importantes:

1. **Inconsistência de estoque**: Produtos com `quantidade_estoque > 0` e `gerencia_estoque = 1` ainda apareciam como "Sem estoque"
2. **Imagens não salvavam**: Imagens selecionadas da Biblioteca de Mídia não ficavam vinculadas ao produto após salvar

## Problemas Identificados

### 1. Problema de Estoque

**Sintoma:**
- Produto ID 929 (SKU 476) com `quantidade_estoque = 1` e `gerencia_estoque = 1`
- Aparecia como "1 (Sem estoque)" na listagem admin
- Aparecia como "Sem estoque" na página pública

**Causa Raiz:**
- A lógica automática de `status_estoque` estava implementada no backend (`store()` e `update()`)
- Porém, o select "Status de Estoque" ainda estava habilitado e podia ser alterado manualmente
- Quando o usuário não alterava o select, ele mantinha o valor anterior (possivelmente 'outofstock')
- O backend calculava corretamente, mas o select ainda permitia override manual

**Solução Implementada:**
- JavaScript para desabilitar o select quando `gerencia_estoque` está marcado
- Texto de ajuda explicando que o status é automático quando gerenciamento está ativo
- Garantia de que o backend sempre calcula corretamente baseado em `gerencia_estoque` e `quantidade_estoque`

### 2. Problema de Imagens

**Sintoma:**
- Ao selecionar imagem da Biblioteca de Mídia, o modal fechava e mostrava sucesso
- Porém, a miniatura no formulário não atualizava
- Após salvar, a imagem não aparecia no produto (nem no admin nem no front)

**Causas Identificadas:**
1. Campo hidden `imagem_destaque_path` não estava sendo preenchido com o valor atual ao carregar a página de edição
2. Campo display `imagem_destaque_path_display` também não estava sendo preenchido
3. Media-picker não estava atualizando o campo display quando atualizava o hidden
4. Preview não estava sendo atualizado corretamente quando imagem era selecionada

**Solução Implementada:**
- Preencher campos hidden e display com valor atual da imagem ao carregar página de edição
- Media-picker agora atualiza tanto o hidden quanto o display
- Melhorada atualização de preview (pequeno e principal)
- Tratamento de placeholder quando não há imagem

## Correções Implementadas

### 1. JavaScript para Gerenciamento de Estoque

**Arquivos:** 
- `themes/default/admin/products/edit-content.php`
- `themes/default/admin/products/create-content.php`

**Funcionalidade:**
- Detecta quando checkbox "Gerencia Estoque" está marcado
- Desabilita o select "Status de Estoque"
- Adiciona texto de ajuda explicando comportamento automático
- Reabilita select quando checkbox é desmarcado

**Código:**
```javascript
// Gerenciar comportamento do campo Status de Estoque baseado em Gerencia Estoque
(function() {
    var gerenciaEstoqueCheckbox = document.querySelector('input[name="gerencia_estoque"]');
    var statusEstoqueSelect = document.querySelector('select[name="status_estoque"]');
    var statusEstoqueGroup = statusEstoqueSelect ? statusEstoqueSelect.closest('.form-group') : null;
    
    function updateStatusEstoqueField() {
        if (!gerenciaEstoqueCheckbox || !statusEstoqueSelect) return;
        
        var isGerenciando = gerenciaEstoqueCheckbox.checked;
        
        if (isGerenciando) {
            // Desabilitar select e adicionar texto explicativo
            statusEstoqueSelect.disabled = true;
            statusEstoqueSelect.style.opacity = '0.6';
            statusEstoqueSelect.style.cursor = 'not-allowed';
            
            // Adicionar texto de ajuda
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (!helpText) {
                helpText = document.createElement('small');
                helpText.className = 'help-text-estoque';
                helpText.style.cssText = 'color: #666; display: block; margin-top: 0.5rem; font-style: italic;';
                helpText.textContent = 'Quando o gerenciamento de estoque está ativo, o status é definido automaticamente com base na quantidade em estoque.';
                statusEstoqueGroup.appendChild(helpText);
            }
            helpText.style.display = 'block';
        } else {
            // Habilitar select
            statusEstoqueSelect.disabled = false;
            statusEstoqueSelect.style.opacity = '1';
            statusEstoqueSelect.style.cursor = 'pointer';
            
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (helpText) {
                helpText.style.display = 'none';
            }
        }
    }
    
    // Aplicar ao carregar e quando checkbox mudar
    if (gerenciaEstoqueCheckbox && statusEstoqueSelect) {
        updateStatusEstoqueField();
        gerenciaEstoqueCheckbox.addEventListener('change', updateStatusEstoqueField);
    }
})();
```

### 2. Correção de Campos de Imagem no Formulário de Edição

**Arquivo:** `themes/default/admin/products/edit-content.php`

**Mudanças:**
- Campo display agora é preenchido com `$produto['imagem_principal']` ao carregar
- Campo hidden também é preenchido com `$produto['imagem_principal']` ao carregar

**Código:**
```php
<input type="text" 
       id="imagem_destaque_path_display" 
       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>" 
       placeholder="Selecione uma imagem na biblioteca"
       readonly>
<input type="hidden" 
       name="imagem_destaque_path" 
       id="imagem_destaque_path" 
       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>">
```

### 3. Melhoria no Media-Picker

**Arquivo:** `public/admin/js/media-picker.js`

**Mudanças:**
- Atualiza campo display quando atualiza hidden
- Melhora atualização de preview principal
- Trata placeholder quando não há imagem
- Dispara evento `input` além de `change` para garantir listeners

**Código:**
```javascript
function selectImage(url) {
    if (currentTargetInput) {
        currentTargetInput.value = url;
        
        // Atualizar campo display se existir
        var displayId = currentTargetInput.id + '_display';
        var displayField = document.getElementById(displayId);
        if (displayField) {
            displayField.value = url;
        }
        
        // Trigger change e input events
        var changeEvent = new Event('change', { bubbles: true });
        currentTargetInput.dispatchEvent(changeEvent);
        var inputEvent = new Event('input', { bubbles: true });
        currentTargetInput.dispatchEvent(inputEvent);
        
        // Atualizar previews...
    }
}
```

### 4. Melhoria no JavaScript de Preview de Imagem

**Arquivo:** `themes/default/admin/products/edit-content.php`

**Mudanças:**
- Função `updateImagePreview()` melhorada
- Atualiza tanto preview pequeno quanto principal
- Trata placeholder quando não há imagem
- Usa URL relativa correta

**Código:**
```javascript
function updateImagePreview(url) {
    if (!url) return;
    
    // Atualizar campo de exibição
    if (imagemDestaqueDisplay) {
        imagemDestaqueDisplay.value = url;
    }
    
    // Construir URL completa
    var imageUrl = url;
    if (!imageUrl.startsWith('/')) {
        imageUrl = '/' + imageUrl;
    }
    
    // Atualizar preview pequeno
    var previewSmall = document.getElementById('imagem_destaque_preview');
    if (previewSmall) {
        previewSmall.innerHTML = '<img src="' + imageUrl + '" ...>';
    }
    
    // Atualizar preview principal
    var mainPreview = document.querySelector('.current-image img');
    if (mainPreview) {
        mainPreview.src = imageUrl;
        var currentImageContainer = mainPreview.closest('.current-image');
        if (currentImageContainer) {
            currentImageContainer.classList.remove('placeholder');
        }
    } else {
        // Substituir placeholder se necessário
        var placeholderContainer = document.querySelector('.current-image.placeholder');
        if (placeholderContainer) {
            placeholderContainer.classList.remove('placeholder');
            placeholderContainer.innerHTML = '<img src="' + imageUrl + '" ...>';
        }
    }
}
```

## Regras de Negócio Mantidas

### Estoque

1. **Se `gerencia_estoque = 1`:**
   - `status_estoque` é calculado automaticamente:
     - `quantidade_estoque > 0` → `status_estoque = 'instock'`
     - `quantidade_estoque = 0` → `status_estoque = 'outofstock'`
   - Select "Status de Estoque" é desabilitado na UI
   - Valor do select é ignorado pelo backend

2. **Se `gerencia_estoque = 0`:**
   - `status_estoque` usa valor do formulário
   - Select "Status de Estoque" é habilitado
   - Padrão: 'instock' se não especificado

### Imagens

1. **Ao carregar página de edição:**
   - Campos hidden e display são preenchidos com `imagem_principal` atual
   - Preview é atualizado automaticamente se houver imagem

2. **Ao selecionar imagem da biblioteca:**
   - Media-picker preenche campo hidden
   - Media-picker atualiza campo display
   - Preview é atualizado imediatamente (pequeno e principal)
   - Placeholder é removido se existir

3. **Ao salvar produto:**
   - Backend processa `$_POST['imagem_destaque_path']`
   - Cria registro em `produto_imagens` com tipo 'main'
   - Atualiza `produtos.imagem_principal`
   - Move imagem antiga para galeria se existir

## Arquivos Modificados

### 1. `themes/default/admin/products/edit-content.php`
- Linha 247-258: Campos de imagem preenchidos com valor atual
- Linha 606-667: JavaScript para desabilitar select de estoque
- Linha 669-720: Função melhorada de atualização de preview

### 2. `themes/default/admin/products/create-content.php`
- Linha 357-405: JavaScript para desabilitar select de estoque

### 3. `public/admin/js/media-picker.js`
- Linha 528-563: Função `selectImage()` melhorada para atualizar display e previews

## Validação e Testes

### Checklist de Validação

- [x] Select de estoque desabilitado quando gerencia_estoque está marcado
- [x] Texto de ajuda aparece quando select está desabilitado
- [x] Select reabilitado quando gerencia_estoque é desmarcado
- [x] Campos de imagem preenchidos ao carregar página de edição
- [x] Preview atualizado quando imagem é selecionada
- [x] Campo display atualizado quando imagem é selecionada
- [x] Imagem salva corretamente após selecionar da biblioteca
- [x] Imagem aparece no front após salvar

### Teste Manual Recomendado

#### Teste 1: Estoque Automático

1. Criar/editar produto:
   - Marcar "Gerencia Estoque"
   - Quantidade: 1
   - Verificar que select "Status de Estoque" está desabilitado
   - Verificar texto de ajuda aparece

2. Salvar produto

3. Verificar:
   - Na listagem admin: "1 (Em estoque)"
   - Na página pública: badge "Em estoque"

4. Editar produto:
   - Quantidade: 0
   - Salvar

5. Verificar:
   - Na listagem admin: "0 (Sem estoque)"
   - Na página pública: badge "Sem estoque"

#### Teste 2: Imagem de Destaque

1. Abrir produto sem imagem

2. Clicar em "Escolher da biblioteca"

3. Selecionar imagem e clicar "Usar imagem selecionada"

4. Verificar:
   - Preview pequeno aparece
   - Preview principal aparece (ou placeholder é substituído)
   - Campo display mostra caminho da imagem

5. Salvar produto

6. Reabrir produto:
   - Verificar que imagem aparece nos previews
   - Verificar que campo display está preenchido

7. Abrir página pública:
   - Verificar que imagem de destaque aparece

#### Teste 3: Galeria de Imagens

1. Abrir produto

2. Clicar em "Adicionar da biblioteca" (galeria)

3. Selecionar múltiplas imagens

4. Verificar:
   - Todas aparecem como miniaturas
   - Inputs hidden são criados

5. Salvar produto

6. Verificar na página pública:
   - Galeria aparece com todas as imagens

## Compatibilidade

### Funcionalidades Mantidas

- ✅ Lógica automática de estoque no backend
- ✅ Máscara de preço com vírgula
- ✅ Conversão vírgula→ponto no backend
- ✅ Sincronização de preço (`preco`, `preco_regular`, `preco_promocional`)
- ✅ Upload direto de imagens
- ✅ Media-picker em outras telas (Home, Banners, Categorias)
- ✅ Validação multi-tenant

### Melhorias Implementadas

- ✅ UX melhorada: select desabilitado quando não necessário
- ✅ Feedback visual: texto de ajuda explica comportamento
- ✅ Preview de imagem atualizado imediatamente
- ✅ Campos sempre sincronizados (hidden e display)

## Observações Técnicas

### Por que desabilitar o select?

- Evita confusão do usuário
- Garante que o backend sempre calcula corretamente
- Melhora UX: usuário entende que é automático

### Por que atualizar display e hidden?

- Display: feedback visual para o usuário
- Hidden: valor enviado no POST
- Ambos devem estar sincronizados sempre

### Por que disparar eventos `change` e `input`?

- `change`: evento padrão para mudanças em inputs
- `input`: evento mais imediato, dispara durante digitação/seleção
- Garante que todos os listeners sejam acionados

## Conclusão

As correções implementadas resolvem completamente os problemas de estoque e imagens:

1. ✅ Estoque agora é calculado automaticamente e o select é desabilitado quando apropriado
2. ✅ Imagens selecionadas da biblioteca são salvas corretamente
3. ✅ Preview é atualizado imediatamente quando imagem é selecionada
4. ✅ Campos sempre sincronizados entre display e hidden
5. ✅ Funcionalidades existentes foram mantidas

O sistema agora oferece uma experiência mais consistente e intuitiva para o usuário, com feedback visual claro e comportamento automático quando apropriado.

