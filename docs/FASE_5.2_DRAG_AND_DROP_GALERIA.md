# Fase 5.2: Drag-and-Drop na Galeria de Imagens

## 📋 Resumo

Implementação de reordenação por drag-and-drop das imagens da galeria no admin de produtos, permitindo que o usuário arraste as miniaturas para reorganizar a ordem de exibição.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Permitir que administradores reordenem visualmente as imagens da galeria de produtos através de drag-and-drop, com a ordem sendo persistida no banco de dados na coluna `ordem` da tabela `produto_imagens`.

---

## 📦 Estrutura de Dados

### Tabela: `produto_imagens`

A tabela já possui a coluna `ordem` (criada na migration `021_create_produto_imagens_table.php`):

```sql
ordem INT DEFAULT 0
```

**Convenções:**
- **Imagem principal:** `tipo = 'main'`, `ordem = 0`
- **Galeria:** `tipo = 'gallery'`, `ordem = 1, 2, 3...` (sequencial)

---

## 🔧 Implementação

### 1. HTML da Galeria

**Arquivo:** `themes/default/admin/products/edit-content.php`

**Alterações:**
- Container `#product-gallery` com classes `gallery-grid product-gallery`
- Cada item da galeria possui:
  - `data-imagem-id`: ID da imagem
  - `draggable="true"`: Habilita arraste
  - Input hidden `galeria_ordem[ID]`: Armazena ordem atual
- Mensagem informativa: "Arraste as imagens para reordená-las"

**Exemplo de estrutura:**
```html
<div class="gallery-grid product-gallery" id="product-gallery">
    <div class="gallery-item product-gallery__item" 
         data-imagem-id="123"
         draggable="true">
        <div class="product-gallery__thumb">
            <img src="..." alt="Imagem da galeria">
        </div>
        <div class="gallery-item-actions">
            <!-- Botões de ação -->
        </div>
        <input type="hidden"
               name="galeria_ordem[123]"
               value="1"
               class="product-gallery__ordem-input">
    </div>
</div>
```

### 2. JavaScript - Drag-and-Drop

**Arquivo:** `themes/default/admin/products/edit-content.php` (bloco `<script>`)

**Funcionalidades:**
- Eventos HTML5 Drag-and-Drop nativos (sem dependências)
- Reordenação visual em tempo real durante o arraste
- Atualização automática dos inputs hidden após cada drop
- Feedback visual (opacidade, borda destacada)

**Eventos implementados:**
- `dragstart`: Marca item como arrastado, adiciona classe `is-dragging`
- `dragover`: Permite drop, calcula posição de inserção, reordena DOM
- `dragend`: Remove classes, chama `updateOrder()`
- `dragenter`/`dragleave`: Feedback visual com classe `drag-over`
- `drop`: Previne comportamento padrão

**Funções principais:**
```javascript
function getDragAfterElement(container, y) {
    // Calcula a posição onde o item deve ser inserido
    // baseado na coordenada Y do mouse
}

function updateOrder() {
    // Recalcula a ordem de todos os itens (1, 2, 3...)
    // e atualiza os inputs hidden
}
```

### 3. CSS - Feedback Visual

**Arquivo:** `themes/default/admin/products/edit-content.php` (bloco `<style>`)

**Classes adicionadas:**
- `.product-gallery__item`: Cursor `grab`, transições suaves
- `.product-gallery__item:hover`: Elevação sutil, sombra
- `.product-gallery__item.is-dragging`: Opacidade reduzida (0.5), escala reduzida
- `.product-gallery__item.drag-over`: Borda destacada com cor primária

### 4. Controller - Salvar Ordem

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php`

**Método:** `processGallery($db, $tenantId, $produtoId)`

**Nova seção adicionada (após remoção e upload):**
```php
// Atualizar ordem das imagens da galeria (após remoção e upload)
if (!empty($_POST['galeria_ordem']) && is_array($_POST['galeria_ordem'])) {
    foreach ($_POST['galeria_ordem'] as $imagemId => $novaOrdem) {
        $imagemId = (int)$imagemId;
        $novaOrdem = (int)$novaOrdem;
        
        // Verificar se a imagem existe e pertence ao produto/tenant
        $stmt = $db->prepare("
            SELECT id FROM produto_imagens 
            WHERE id = :id 
            AND tenant_id = :tenant_id 
            AND produto_id = :produto_id
            AND tipo = 'gallery'
        ");
        $stmt->execute([...]);
        
        if ($stmt->fetch()) {
            // Atualizar ordem
            $stmt = $db->prepare("
                UPDATE produto_imagens 
                SET ordem = :ordem 
                WHERE id = :id 
                AND tenant_id = :tenant_id 
                AND produto_id = :produto_id
                AND tipo = 'gallery'
            ");
            $stmt->execute([...]);
        }
    }
}
```

**Características:**
- Executado após remoção e upload de imagens
- Validação de segurança (tenant_id, produto_id, tipo)
- Atualiza apenas imagens `gallery` (não afeta imagem principal)
- Não atualiza se `galeria_ordem` estiver vazio

---

## 🔒 Segurança e Multi-tenant

### Validações Implementadas

1. **Filtro por Tenant:**
   - Todas as queries incluem `tenant_id = :tenant_id`
   - Previne acesso a imagens de outros tenants

2. **Filtro por Produto:**
   - Todas as queries incluem `produto_id = :produto_id`
   - Previne alteração de imagens de outros produtos

3. **Filtro por Tipo:**
   - Apenas imagens `tipo = 'gallery'` são reordenadas
   - Imagem principal (`tipo = 'main'`) não é afetada

4. **Sanitização:**
   - IDs convertidos para `int` com `(int)`
   - Validação de existência antes de atualizar

---

## 📝 Fluxo de Uso

1. **Acessar edição de produto:** `/admin/produtos/{id}`
2. **Visualizar galeria:** Seção "Galeria de Imagens" com mensagem informativa
3. **Arrastar imagem:** Clicar e arrastar uma miniatura para nova posição
4. **Feedback visual:** Item fica semi-transparente, bordas destacadas
5. **Soltar:** Ordem é recalculada automaticamente
6. **Salvar:** Clicar em "Salvar alterações"
7. **Persistência:** Ordem salva no banco, mantida ao recarregar página

---

## 🎨 Interface do Usuário

### Mensagem Informativa
```
ℹ️ Arraste as imagens para reordená-las
```

### Estados Visuais

**Normal:**
- Cursor: `grab`
- Borda: Cinza (#ddd)
- Hover: Elevação sutil, sombra

**Arrastando:**
- Cursor: `grabbing`
- Opacidade: 50%
- Escala: 95%

**Sobre outro item:**
- Borda: Cor primária (verde)
- Largura: 3px

---

## ✅ Checklist de Aceite

- [x] É possível arrastar as miniaturas para reordená-las
- [x] A ordem visual muda imediatamente durante o arraste
- [x] Após clicar em "Salvar alterações", a ordem é persistida
- [x] Ao reabrir a tela de produto, as miniaturas aparecem na ordem escolhida
- [x] Na loja (PDP), a galeria respeita a nova ordem (ORDER BY ordem)
- [x] Upload de novas imagens continua funcionando
- [x] Remoção de imagens continua funcionando
- [x] Imagem principal não é afetada pelo drag-and-drop
- [x] Multi-tenant: cada loja vê apenas suas próprias imagens
- [x] Segurança: validações de tenant_id e produto_id

---

## 🔄 Compatibilidade

### Funcionalidades Mantidas

- ✅ Upload de novas imagens (múltiplas)
- ✅ Remoção de imagens (checkbox)
- ✅ Definir imagem principal a partir da galeria
- ✅ Ordenação existente no banco (ORDER BY ordem ASC)

### Não Afetado

- ❌ Imagem principal (tipo = 'main', ordem = 0)
- ❌ Vídeos do produto
- ❌ Dados gerais do produto

---

## 📊 Estrutura de Arquivos Modificados

```
themes/default/admin/products/
└── edit-content.php
    ├── HTML: Container e itens da galeria
    ├── CSS: Estilos de drag-and-drop
    └── JavaScript: Lógica de reordenação

src/Http/Controllers/Admin/
└── ProductController.php
    └── processGallery(): Nova seção para salvar ordem
```

---

## 🚀 Próximos Passos (Futuro)

### Fase 5.3: Preview de Vídeos na Galeria da Loja
- Integrar thumbnails de vídeos na galeria da PDP
- Player de vídeo ao clicar

### Fase 5.4: Upload de Vídeos Próprios
- Upload de arquivos de vídeo (além de links)
- Processamento e armazenamento

---

## 📚 Referências

- **Migration:** `021_create_produto_imagens_table.php` (coluna `ordem`)
- **Controller:** `Admin\ProductController@processGallery()`
- **View:** `themes/default/admin/products/edit-content.php`
- **PDP:** `themes/default/storefront/products/show.php` (usa ORDER BY ordem)

---

## 🐛 Troubleshooting

### Problema: Ordem não está sendo salva

**Verificar:**
1. Inputs hidden `galeria_ordem[ID]` estão presentes no HTML
2. JavaScript `updateOrder()` está sendo chamado após drop
3. Controller está lendo `$_POST['galeria_ordem']`
4. Query UPDATE está sendo executada (verificar logs)

### Problema: Drag-and-drop não funciona

**Verificar:**
1. Atributo `draggable="true"` está presente
2. JavaScript está carregado (sem erros no console)
3. Container `#product-gallery` existe
4. Event listeners estão sendo anexados

### Problema: Ordem não persiste após salvar

**Verificar:**
1. Query UPDATE está sendo executada
2. Filtros `tenant_id` e `produto_id` estão corretos
3. Tipo da imagem é `'gallery'` (não `'main'`)
4. Não há erros de transação no banco

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX


