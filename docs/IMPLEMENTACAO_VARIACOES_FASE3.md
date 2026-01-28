# IMPLEMENTAÇÃO — Variações de Produto (Fase 3 - Admin UI + Storefront)

**Data:** 2025-01-27  
**Status:** ✅ Implementado

---

## Resumo

Implementação da Fase 3 do sistema de variações de produto:
- **Admin UI:** CRUD de atributos/termos, configuração de atributos por produto, geração e edição de variações
- **Storefront:** Seletores de variação na página do produto, envio de `variacao_id` no add-to-cart

---

## Rotas Adicionadas

### Admin - Atributos

- `GET /admin/atributos` → Listagem de atributos
- `GET /admin/atributos/novo` → Formulário de criação
- `POST /admin/atributos` → Criar atributo
- `GET /admin/atributos/{id}/editar` → Formulário de edição
- `POST /admin/atributos/{id}` → Atualizar atributo
- `POST /admin/atributos/{id}/excluir` → Excluir atributo
- `POST /admin/atributos/{id}/termos` → Adicionar termo
- `POST /admin/atributos/{id}/termos/{termId}` → Atualizar termo
- `POST /admin/atributos/{id}/termos/{termId}/excluir` → Excluir termo

### Admin - Variações de Produto

- `POST /admin/produtos/{id}/variacoes/gerar` → Gerar variações automaticamente
- `POST /admin/produtos/{id}/variacoes/salvar-lote` → Salvar variações em lote (via update do produto)

---

## Telas Admin

### 1. Listagem de Atributos (`/admin/atributos`)

**Arquivo:** `themes/default/admin/atributos/index-content.php`

**Funcionalidades:**
- Lista todos os atributos do tenant
- Exibe total de termos e produtos que usam cada atributo
- Filtro por nome/slug
- Botão para criar novo atributo

### 2. Criar/Editar Atributo

**Arquivos:**
- `themes/default/admin/atributos/create-content.php`
- `themes/default/admin/atributos/edit-content.php`

**Funcionalidades:**
- Campos: nome, slug, tipo (select/color/image), ordem
- Gerenciamento de termos do atributo
- Para tipo "color": campo valor_cor (hexadecimal)

### 3. Edição de Produto (com Variações)

**Arquivo:** `themes/default/admin/products/edit-content.php`

**Modificações:**
- Campo "Tipo" (simple/variable) na seção Dados Gerais
- Nova seção "Atributos do Produto" (apenas se tipo = variable):
  - Selecionar atributos globais
  - Marcar "usado_para_variacao" para cada atributo
  - Selecionar termos disponíveis por atributo
- Nova seção "Variações":
  - Botão "Gerar Variações"
  - Tabela editável com:
    - Combinação (ex: "Cor: Vermelho, Tamanho: P")
    - SKU
    - Preço regular/promocional (override)
    - Gerencia estoque, quantidade, status

**JavaScript:**
- Mostra/oculta seções baseado no tipo
- Mostra/oculta opções de atributo quando checkbox é marcado
- Botão "Gerar Variações" faz requisição AJAX
- Salva variações em lote ao submeter formulário

---

## Como Gerar Variações

### Processo Automático

1. **Configurar produto como variável:**
   - Na edição do produto, selecionar "Tipo: Produto Variável"

2. **Selecionar atributos:**
   - Na seção "Atributos do Produto", marcar os atributos que o produto usa
   - Marcar checkbox "Usar para gerar variações" nos atributos desejados
   - Selecionar termos disponíveis para cada atributo

3. **Gerar variações:**
   - Clicar em "Gerar Variações"
   - Sistema gera produto cartesiano de todas as combinações possíveis
   - **Idempotente:** se executar 2x, não cria duplicatas (compara assinaturas)

4. **Editar variações:**
   - Preencher SKU, preços, estoque por variação
   - Salvar alterações (salva em lote junto com o produto)

### Algoritmo de Geração (Idempotente)

**Método:** `ProductController::generateVariations()`

**Processo:**
1. Buscar atributos marcados como `usado_para_variacao = 1`
2. Para cada atributo, buscar termos selecionados em `produto_atributo_termos`
3. Gerar produto cartesiano das listas de termos
4. Para cada combinação:
   - Montar assinatura ordenada: `"atributo_id:termo_id|atributo_id:termo_id|..."`
   - Comparar com assinaturas existentes (derivadas do DB)
   - Se nova: criar `produto_variacoes` + `produto_variacao_atributos`
   - Se existente: ignorar (idempotência)

**Exemplo:**
- Atributo 1 (Cor): Vermelho, Azul
- Atributo 2 (Tamanho): P, M, G
- Combinações geradas: 2 x 3 = 6 variações
- Assinaturas: `"1:1|2:1"`, `"1:1|2:2"`, `"1:1|2:3"`, `"1:2|2:1"`, `"1:2|2:2"`, `"1:2|2:3"`

---

## Storefront - Seletores de Variação

### Modificações na Página do Produto

**Arquivo:** `themes/default/storefront/products/show.php`

**Se produto variável:**
- Renderiza selects para cada atributo usado_para_variacao
- Input hidden `variacao_id` (preenchido via JavaScript)
- Exibe status de estoque da variação selecionada
- Exibe preço da variação (override ou herdado)
- Botão "Adicionar" desabilitado até combinação válida

**Se produto simples:**
- Comportamento atual (sem seletores)

### JavaScript

**Localização:** Final de `themes/default/storefront/products/show.php`

**Funcionalidades:**
1. **Dados embutidos:** `window.productVariations` (JSON com todas as variações)
2. **Montar assinatura atual:** A partir dos valores dos selects
3. **Encontrar variação:** Buscar no array por assinatura correspondente
4. **Atualizar UI:**
   - Preencher `variacao_id`
   - Atualizar preço exibido
   - Atualizar status de estoque
   - Habilitar/desabilitar botão "Adicionar"
   - Atualizar `max` do input quantidade

**Trecho do código que envia variacao_id:**
```php
<form method="POST" action="<?= $basePath ?>/carrinho/adicionar" class="add-to-cart-form" id="add-to-cart-form">
    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
    <input type="hidden" name="variacao_id" id="variacao_id" value="">
    <!-- ... -->
</form>
```

**JavaScript preenche:**
```javascript
variacaoIdInput.value = variation.variacao_id;
```

---

## Como Testar

### 1. Criar Atributos e Termos

1. Acessar `/admin/atributos`
2. Criar atributo "Cor" (tipo: color)
3. Adicionar termos: Vermelho (#FF0000), Azul (#0000FF)
4. Criar atributo "Tamanho" (tipo: select)
5. Adicionar termos: P, M, G, GG

### 2. Criar Produto Variável

1. Criar/editar produto
2. Selecionar "Tipo: Produto Variável"
3. Na seção "Atributos do Produto":
   - Marcar "Cor" e "Tamanho"
   - Marcar "Usar para gerar variações" nos dois
   - Selecionar termos: Cor (Vermelho, Azul), Tamanho (P, M, G, GG)
4. Salvar produto

### 3. Gerar Variações

1. Na seção "Variações", clicar "Gerar Variações"
2. **Resultado esperado:** 8 variações criadas (2 cores x 4 tamanhos)
3. Clicar novamente "Gerar Variações"
4. **Resultado esperado:** Mensagem "0 criadas, 8 já existiam" (idempotência)

### 4. Editar Estoque de Variações

1. Na tabela de variações, preencher:
   - SKU por variação
   - Quantidade de estoque
   - Marcar "Gerencia Estoque" se necessário
2. Salvar alterações do produto
3. **Verificar:** Estoque salvo corretamente por variação

### 5. Testar Storefront

1. Acessar página do produto variável
2. **Verificar:** Seletores de Cor e Tamanho aparecem
3. Selecionar combinação válida (ex: Cor: Vermelho, Tamanho: P)
4. **Verificar:**
   - Preço atualiza (se variação tiver preço próprio)
   - Status de estoque atualiza
   - Botão "Adicionar" habilita
   - `variacao_id` é preenchido (inspecionar input hidden)
5. Adicionar ao carrinho
6. **Verificar:** Item no carrinho tem `variacao_id` correto

### 6. Testar Carrinho/Checkout

1. Finalizar pedido com variação
2. **Verificar no banco:**
   - `pedido_itens.variacao_id` preenchido
   - `pedido_itens.atributos_json` contém JSON dos atributos
   - Estoque da variação decrementado (não do produto pai)

---

## Arquivos Criados/Modificados

### Controllers
- ✅ `src/Http/Controllers/Admin/AttributeController.php` (novo)
- ✅ `src/Http/Controllers/Admin/ProductController.php` (modificado: generateVariations, saveVariationsBulk, edit, update)
- ✅ `src/Http/Controllers/Storefront/ProductController.php` (modificado: show)

### Views Admin
- ✅ `themes/default/admin/atributos/index-content.php` (novo)
- ✅ `themes/default/admin/atributos/create-content.php` (novo)
- ✅ `themes/default/admin/atributos/edit-content.php` (novo)
- ✅ `themes/default/admin/products/edit-content.php` (modificado: seções de atributos e variações)

### Views Storefront
- ✅ `themes/default/storefront/products/show.php` (modificado: seletores e JavaScript)

### Rotas
- ✅ `public/index.php` (adicionadas rotas de atributos e variações)

### Migrations
- ✅ `database/migrations/054_add_usado_para_variacao_to_produto_atributos.php` (novo)

---

## Trechos Principais

### Método de Geração (Core da Idempotência)

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php`  
**Método:** `generateVariations()`

```php
// Buscar assinaturas existentes
$stmt = $db->prepare("
    SELECT pva.variacao_id, 
           GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|') as signature
    FROM produto_variacao_atributos pva
    INNER JOIN produto_variacoes pv ON pv.id = pva.variacao_id
    WHERE pv.produto_id = :produto_id AND pv.tenant_id = :tenant_id
    GROUP BY pva.variacao_id
");
$stmt->execute(['produto_id' => $id, 'tenant_id' => $tenantId]);
$variacoesExistentes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$assinaturasExistentes = [];
foreach ($variacoesExistentes as $v) {
    $assinaturasExistentes[$v['signature']] = (int)$v['variacao_id'];
}

// Criar variações apenas para combinações novas
foreach ($combinacoes as $combinacao) {
    // Montar assinatura ordenada
    $assinatura = $this->buildVariationSignature($combinacao);

    // Verificar se já existe
    if (isset($assinaturasExistentes[$assinatura])) {
        $ignoradas++;
        continue; // IDEMPOTÊNCIA: pula se já existe
    }

    // Criar variação...
}
```

### Storefront - Envio de variacao_id

**Arquivo:** `themes/default/storefront/products/show.php`

```php
<form method="POST" action="<?= $basePath ?>/carrinho/adicionar" class="add-to-cart-form" id="add-to-cart-form">
    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
    <input type="hidden" name="variacao_id" id="variacao_id" value="">
    <!-- ... -->
</form>

<script>
// JavaScript preenche variacao_id quando variação é selecionada
function updateUI() {
    const signature = buildCurrentSignature();
    const variation = findVariation(signature);
    
    if (variation) {
        variacaoIdInput.value = variation.variacao_id; // ← AQUI
        // ... atualiza UI
    }
}
</script>
```

---

## Checklist de Testes Manuais

- [ ] Criar atributos: Cor e Tamanho + termos
- [ ] Criar produto tipo variable e selecionar termos
- [ ] Marcar usado_para_variacao nos dois e clicar "Gerar variações"
  - [ ] Deve criar 8 variações (2x4), sem duplicar se clicar 2x
- [ ] Editar estoque de variações
  - [ ] Ex.: Amarelo só P e M com quantidades diferentes
  - [ ] Vermelho P/M/G/GG com quantidades diferentes
- [ ] Storefront: selecionar Cor/Tamanho e validar
  - [ ] Combinação existente habilita add e envia variacao_id
  - [ ] Combinação sem variação fica indisponível
- [ ] Carrinho/Checkout:
  - [ ] Criar pedido com variação
  - [ ] Confirmar: pedido_itens.variacao_id preenchido
  - [ ] Confirmar: atributos_json preenchido
  - [ ] Confirmar: estoque da variação decrementado corretamente

---

**Fim do Documento**
