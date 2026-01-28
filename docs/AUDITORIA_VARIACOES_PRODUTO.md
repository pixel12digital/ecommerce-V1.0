# AUDITORIA — Variações de Produto (tamanho/cor) + estoque por variação

**Data:** 2025-01-27  
**Objetivo:** Entender exatamente como o cadastro, exibição, carrinho/pedido e controle de estoque funcionam HOJE no Ponto do Golf, identificando pontos de acoplamento e riscos para implementação de variações.

---

## 1. Contexto

O sistema atual funciona com produtos simples (1 produto = 1 preço = 1 estoque = 1 SKU). Não existe implementação de variações (tamanho, cor, etc.). O campo `tipo` na tabela `produtos` aceita valores `'simple','variable','grouped','external'`, mas apenas `'simple'` é utilizado.

**Status atual:** Sistema de produtos simples, sem variações.

---

## 2. Como funciona hoje (Resumo Executivo)

### 2.1 Cadastro de Produto (Admin)
- **Localização:** `src/Http/Controllers/Admin/ProductController.php`
- **Formulário:** `themes/default/admin/products/edit-content.php` e `create-content.php`
- **Campos disponíveis:**
  - Dados básicos: nome, slug, SKU, status, tipo (sempre 'simple')
  - Preços: preco_regular, preco_promocional, datas de promoção
  - Estoque: gerencia_estoque (checkbox), quantidade_estoque (número), status_estoque (select)
  - Dimensões: peso, comprimento, largura, altura
  - Descrições: descricao_curta, descricao
  - Mídia: imagem_principal, galeria de imagens, vídeos
  - Categorias: múltipla seleção via checkboxes
- **Validação:** Nome obrigatório, preço regular obrigatório
- **Persistência:** INSERT/UPDATE direto na tabela `produtos`

### 2.2 Exibição do Produto (Storefront)
- **Localização:** `src/Http/Controllers/Storefront/ProductController.php` (método `show()`)
- **View:** `themes/default/storefront/products/show.php`
- **Busca:** Por slug, filtra por `status = 'publish'`
- **Dados exibidos:**
  - Nome, descrição, preço (promocional ou regular)
  - Status de estoque e quantidade disponível
  - Imagens (principal + galeria)
  - Categorias
  - Formulário de adicionar ao carrinho (quantidade, botão)
- **Preço:** Usa `preco_promocional` se existir, senão `preco_regular`
- **Estoque:** Exibe status e quantidade se `gerencia_estoque = 1`

### 2.3 Carrinho
- **Localização:** `src/Services/CartService.php` (sessão PHP)
- **Armazenamento:** Sessão PHP (`$_SESSION['cart_{tenant_id}']`)
- **Estrutura do item:**
  ```php
  [
      'produto_id' => int,
      'nome' => string,
      'slug' => string,
      'preco_unitario' => float,  // Snapshot do preço
      'quantidade' => int,
      'imagem' => string|null
  ]
  ```
- **Adição:** `src/Http/Controllers/Storefront/CartController.php::add()`
  - Busca produto por ID
  - Calcula preço unitário (promocional ou regular)
  - Adiciona à sessão
- **Chave de identificação:** Apenas `produto_id` (sem variação)

### 2.4 Checkout e Pedido
- **Localização:** `src/Http/Controllers/Storefront/CheckoutController.php`
- **Tabela pedidos:** `pedidos` (migration `031_create_pedidos_table.php`)
- **Tabela itens:** `pedido_itens` (migration `032_create_pedido_itens_table.php`)
- **Estrutura do item no pedido:**
  ```sql
  pedido_itens:
  - produto_id (FK para produtos)
  - nome_produto (snapshot)
  - sku (buscado do produto no momento do pedido)
  - quantidade
  - preco_unitario (snapshot)
  - total_linha
  ```
- **Processo:**
  1. Valida carrinho
  2. Cria registro em `pedidos`
  3. Para cada item do carrinho, cria registro em `pedido_itens` (com snapshot de nome, SKU, preço)
  4. Processa pagamento
  5. Atualiza status do pedido
  6. Limpa carrinho
- **⚠️ IMPORTANTE:** Estoque NÃO é decrementado automaticamente ao criar o pedido

### 2.5 Controle de Estoque
- **Localização:** Tabela `produtos` (colunas `gerencia_estoque`, `quantidade_estoque`, `status_estoque`)
- **Lógica atual:**
  - Se `gerencia_estoque = 1`:
    - `quantidade_estoque > 0` → `status_estoque = 'instock'` (automático)
    - `quantidade_estoque = 0` → `status_estoque = 'outofstock'` (automático)
  - Se `gerencia_estoque = 0`:
    - `status_estoque` é manual (via formulário)
- **Atualização:** Apenas manual via formulário admin
- **Decremento:** **NÃO EXISTE** - nenhum código decrementa estoque ao criar pedido ou pagar
- **Reposição:** **NÃO EXISTE** - nenhum código repõe estoque em cancelamento/estorno

---

## 3. Banco de Dados Atual

### 3.1 Tabela `produtos`

**Migration:** `database/migrations/020_create_produtos_table_detailed.php`

```sql
CREATE TABLE produtos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    id_original_wp INT NULL,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,
    tipo ENUM('simple','variable','grouped','external') DEFAULT 'simple',
    status ENUM('publish','draft','private') DEFAULT 'publish',
    preco DECIMAL(10,2) DEFAULT 0.00,
    preco_regular DECIMAL(10,2) DEFAULT 0.00,
    preco_promocional DECIMAL(10,2) NULL,
    data_promocao_inicio DATETIME NULL,
    data_promocao_fim DATETIME NULL,
    gerencia_estoque TINYINT(1) DEFAULT 0,
    quantidade_estoque INT DEFAULT 0,
    status_estoque ENUM('instock','outofstock','onbackorder') DEFAULT 'instock',
    permite_pedidos_falta ENUM('no','notify','yes') DEFAULT 'no',
    peso DECIMAL(8,2) NULL,
    comprimento DECIMAL(8,2) NULL,
    largura DECIMAL(8,2) NULL,
    altura DECIMAL(8,2) NULL,
    descricao TEXT NULL,
    descricao_curta TEXT NULL,
    imagem_principal VARCHAR(255) NULL,
    destaque TINYINT(1) DEFAULT 0,
    visibilidade_catalogo ENUM('visible','catalog','search','hidden') DEFAULT 'visible',
    status_imposto ENUM('taxable','shipping','none') DEFAULT 'taxable',
    exibir_no_catalogo TINYINT(1) DEFAULT 1,
    data_criacao DATETIME NOT NULL,
    data_modificacao DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_produtos_tenant (tenant_id),
    INDEX idx_produtos_tenant_slug (tenant_id, slug),
    INDEX idx_produtos_tenant_sku (tenant_id, sku),
    INDEX idx_produtos_tenant_status (tenant_id, status),
    INDEX idx_produtos_id_original_wp (id_original_wp),
    UNIQUE KEY unique_produtos_tenant_slug (tenant_id, slug),
    UNIQUE KEY unique_produtos_tenant_wp_id (tenant_id, id_original_wp)
)
```

**Observações:**
- Campo `tipo` existe mas só `'simple'` é usado
- SKU é único por tenant (índice composto)
- Estoque é por produto único (não por variação)

### 3.2 Tabela `pedido_itens`

**Migration:** `database/migrations/032_create_pedido_itens_table.php`

```sql
CREATE TABLE pedido_itens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    pedido_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,
    nome_produto VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,
    quantidade INT UNSIGNED NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    total_linha DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_tenant_pedido (tenant_id, pedido_id),
    INDEX idx_produto_id (produto_id)
)
```

**Observações:**
- Armazena snapshot de `nome_produto`, `sku`, `preco_unitario`
- Referencia `produto_id` mas não tem campo para variação
- Compatível com pedidos antigos (não quebra se adicionar variação depois)

### 3.3 Tabelas Relacionadas

- **`produto_imagens`:** Imagens do produto (múltiplas)
- **`produto_categorias`:** Relação N:N produto ↔ categoria
- **`produto_tags`:** Tags do produto
- **`produto_meta`:** Metadados customizados
- **`produto_videos`:** Vídeos do produto
- **`produto_avaliacoes`:** Avaliações de clientes

**⚠️ NÃO EXISTEM:**
- `produto_atributos` (não existe)
- `produto_variacoes` (não existe)
- `produto_variacao_atributos` (não existe)

---

## 4. Fluxo de Cadastro (Admin)

### 4.1 Rota e Controller

**Rota:** `/admin/produtos` (GET) → `ProductController::index()`  
**Rota:** `/admin/produtos` (POST) → `ProductController::store()`  
**Rota:** `/admin/produtos/{id}` (GET) → `ProductController::edit()`  
**Rota:** `/admin/produtos/{id}` (POST) → `ProductController::update()`

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php`

### 4.2 Método `store()` (Criar)

**Linhas:** 261-410

**Processo:**
1. Valida dados do POST
2. Gera slug se vazio
3. Processa preços (converte vírgula para ponto)
4. Calcula `status_estoque` baseado em `gerencia_estoque` e `quantidade_estoque`
5. Calcula `preco` (promocional ou regular)
6. INSERT na tabela `produtos`
7. Processa categorias (INSERT em `produto_categorias`)
8. Processa imagens (INSERT em `produto_imagens`)
9. COMMIT transação

**Campos salvos:**
```php
nome, slug, sku, status, tipo (sempre 'simple'),
preco, preco_regular, preco_promocional,
data_promocao_inicio, data_promocao_fim,
gerencia_estoque, quantidade_estoque, status_estoque,
permite_pedidos_falta, peso, comprimento, largura, altura,
descricao, descricao_curta, imagem_principal,
destaque, visibilidade_catalogo, status_imposto, exibir_no_catalogo
```

### 4.3 Método `update()` (Editar)

**Linhas:** 614-746

**Processo:** Similar ao `store()`, mas faz UPDATE em vez de INSERT.

### 4.4 Formulário

**Arquivo:** `themes/default/admin/products/edit-content.php` (linhas 1-1883)

**Seções:**
- Dados Gerais (nome, slug, SKU, status)
- Preços (regular, promocional, datas)
- Estoque (gerencia, quantidade, status)
- Dimensões (peso, comprimento, largura, altura)
- Descrições
- Categorias (checkboxes)
- Mídia (imagem principal, galeria, vídeos)

**JavaScript:**
- Máscara de preço (vírgula → ponto)
- Desabilita `status_estoque` quando `gerencia_estoque` está marcado
- Drag-and-drop para galeria

---

## 5. Fluxo de Exibição (Storefront)

### 5.1 Rota e Controller

**Rota:** `/produto/{slug}` (GET) → `ProductController::show()`

**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`  
**Método:** `show(string $slug)` (linhas 284-345)

### 5.2 Busca do Produto

```php
SELECT * FROM produtos 
WHERE tenant_id = :tenant_id 
AND slug = :slug 
AND status = 'publish'
LIMIT 1
```

### 5.3 Dados Carregados

1. **Produto:** Tudo da tabela `produtos`
2. **Imagens:** `produto_imagens` (ordenadas por tipo='main' DESC, ordem ASC)
3. **Categorias:** JOIN `categorias` + `produto_categorias`
4. **Vídeos:** `produto_videos` (se existir)

### 5.4 View

**Arquivo:** `themes/default/storefront/products/show.php`

**Exibição:**
- Nome, descrição
- Preço (promocional ou regular)
- Status de estoque + quantidade
- Formulário de adicionar ao carrinho
- Galeria de imagens
- Abas (Descrição, Informações, Categorias)

**Formulário de carrinho:**
```html
<form method="POST" action="/carrinho/adicionar">
    <input type="hidden" name="produto_id" value="<?= $produto['id'] ?>">
    <input type="number" name="quantidade" value="1" min="1" 
           max="<?= $produto['quantidade_estoque'] ?? 999 ?>">
    <button type="submit">Adicionar ao Carrinho</button>
</form>
```

**Observação:** Apenas `produto_id` e `quantidade` são enviados (sem variação).

---

## 6. Carrinho / Pedido

### 6.1 Estrutura do Carrinho (Sessão)

**Arquivo:** `src/Services/CartService.php`

**Estrutura:**
```php
$_SESSION['cart_{tenant_id}'] = [
    'items' => [
        {produto_id} => [
            'produto_id' => int,
            'nome' => string,
            'slug' => string,
            'preco_unitario' => float,
            'quantidade' => int,
            'imagem' => string|null
        ]
    ]
]
```

**Chave de identificação:** `produto_id` (sem variação)

### 6.2 Adicionar ao Carrinho

**Arquivo:** `src/Http/Controllers/Storefront/CartController.php`  
**Método:** `add()` (linhas 66-129)

**Processo:**
1. Recebe `produto_id` e `quantidade` do POST
2. Busca produto no banco
3. Calcula `preco_unitario` (promocional ou regular)
4. Busca imagem principal
5. Chama `CartService::addItem($produtoId, $itemData)`
6. Redireciona

**⚠️ Problema:** Se o mesmo produto for adicionado duas vezes, apenas soma a quantidade (não diferencia variações).

### 6.3 Criar Pedido

**Arquivo:** `src/Http/Controllers/Storefront/CheckoutController.php`  
**Método:** `store()` (linhas 328-458)

**Processo:**
1. Valida dados do checkout
2. Gera `numero_pedido`
3. INSERT em `pedidos`
4. Para cada item do carrinho:
   - Busca SKU do produto
   - INSERT em `pedido_itens` (com snapshot de nome, SKU, preço)
5. Processa pagamento
6. Atualiza pedido com código de transação
7. Limpa carrinho

**Código relevante (linhas 388-406):**
```php
foreach ($cart['items'] as $item) {
    // Buscar SKU do produto
    $stmtSku = $db->prepare("SELECT sku FROM produtos WHERE id = :id AND tenant_id = :tenant_id");
    $stmtSku->execute(['id' => $item['produto_id'], 'tenant_id' => $tenantId]);
    $sku = $stmtSku->fetchColumn();

    $totalLinha = $item['preco_unitario'] * $item['quantidade'];

    $stmtItem->execute([
        'tenant_id' => $tenantId,
        'pedido_id' => $pedidoId,
        'produto_id' => $item['produto_id'],
        'nome_produto' => $item['nome'],
        'sku' => $sku ?: null,
        'quantidade' => $item['quantidade'],
        'preco_unitario' => $item['preco_unitario'],
        'total_linha' => $totalLinha,
    ]);
}
```

**⚠️ IMPORTANTE:**
- Estoque NÃO é decrementado
- Não há validação de estoque antes de criar pedido
- Não há campo para variação em `pedido_itens`

---

## 7. Estoque — Onde Atualiza e Onde Valida

### 7.1 Atualização de Estoque

**Localização:** Apenas via formulário admin (`ProductController::store()` e `update()`)

**Código:** `src/Http/Controllers/Admin/ProductController.php` (linhas 300-314, 644-653)

**Lógica:**
```php
$quantidadeEstoque = !empty($_POST['quantidade_estoque']) ? (int)$_POST['quantidade_estoque'] : 0;
$gerenciaEstoque = isset($_POST['gerencia_estoque']) ? 1 : 0;

if ($gerenciaEstoque == 1) {
    $statusEstoque = ($quantidadeEstoque > 0) ? 'instock' : 'outofstock';
} else {
    $statusEstoque = $_POST['status_estoque'] ?? 'instock';
}
```

**⚠️ NÃO EXISTE:**
- Decremento automático ao criar pedido
- Decremento automático ao pagar pedido
- Reposição ao cancelar pedido
- Reposição ao estornar pagamento

### 7.2 Validação de Estoque

**Localização:** Apenas na exibição (storefront)

**Código:**
- `src/Http/Controllers/Storefront/ProductController.php::show()` - não valida, apenas exibe
- `themes/default/storefront/products/show.php` (linha 225) - `max="<?= $produto['quantidade_estoque'] ?? 999 ?>"` (apenas HTML)
- `src/Http/Controllers/Storefront/CartController.php::add()` - **NÃO VALIDA ESTOQUE**

**⚠️ PROBLEMA:** É possível adicionar ao carrinho quantidade maior que o estoque disponível.

### 7.3 Filtro de Produtos (Ocultar Sem Estoque)

**Localização:** `src/Http/Controllers/Storefront/ProductController.php::renderProductList()` (linha 71)

**Código:**
```php
$ocultarEstoqueZero = ThemeConfig::get('catalogo_ocultar_estoque_zero', '0');
if ($ocultarEstoqueZero === '1') {
    $where[] = "(p.gerencia_estoque = 0 OR (p.gerencia_estoque = 1 AND p.quantidade_estoque > 0))";
}
```

**Observação:** Configurável via `tenant_settings` (chave `catalogo_ocultar_estoque_zero`).

---

## 8. Pontos de Acoplamento e Riscos

### 8.1 Arquivos que Seriam Impactados

#### 8.1.1 Admin (Cadastro/Edição)

1. **`src/Http/Controllers/Admin/ProductController.php`**
   - `store()`: Adicionar lógica para salvar atributos e variações
   - `update()`: Adicionar lógica para atualizar atributos e variações
   - `edit()`: Carregar atributos e variações do produto
   - **Linhas críticas:** 261-410 (store), 614-746 (update)

2. **`themes/default/admin/products/edit-content.php`**
   - Adicionar seção de atributos
   - Adicionar seção de variações (tabela/grid)
   - JavaScript para gerenciar variações dinamicamente
   - **Linhas críticas:** Toda a estrutura do formulário

3. **`themes/default/admin/products/create-content.php`**
   - Similar ao edit-content.php

4. **`themes/default/admin/products/index-content.php`**
   - Exibir indicador se produto tem variações
   - **Linha crítica:** Listagem de produtos

#### 8.1.2 Storefront (Exibição)

5. **`src/Http/Controllers/Storefront/ProductController.php`**
   - `show()`: Carregar variações do produto
   - Validar se produto tem variações e redirecionar lógica
   - **Linhas críticas:** 284-345

6. **`themes/default/storefront/products/show.php`**
   - Adicionar seletores de atributos (tamanho, cor, etc.)
   - Exibir preço da variação selecionada
   - Exibir estoque da variação selecionada
   - Atualizar imagem quando variação mudar (se tiver imagem por variação)
   - **Linhas críticas:** 222-233 (formulário de carrinho)

#### 8.1.3 Carrinho

7. **`src/Services/CartService.php`**
   - Modificar estrutura do item para incluir `variacao_id`
   - Modificar chave de identificação: `{produto_id}_{variacao_id}` ou apenas `variacao_id`
   - **Linhas críticas:** 63-76 (addItem), 33-38 (get)

8. **`src/Http/Controllers/Storefront/CartController.php`**
   - `add()`: Receber `variacao_id` do POST
   - Validar variação existe e está disponível
   - Validar estoque da variação
   - Buscar preço da variação (se diferente do produto)
   - **Linhas críticas:** 66-129

9. **`themes/default/storefront/cart/index.php`**
   - Exibir informações da variação (tamanho, cor) no item
   - **Linha crítica:** Renderização dos itens

#### 8.1.4 Checkout e Pedido

10. **`src/Http/Controllers/Storefront/CheckoutController.php`**
    - `store()`: Incluir `variacao_id` em `pedido_itens`
    - Validar estoque de cada variação antes de criar pedido
    - Decrementar estoque da variação (se implementar)
    - **Linhas críticas:** 388-406 (inserção de itens)

11. **`database/migrations/032_create_pedido_itens_table.php`**
    - Adicionar coluna `variacao_id` (nullable para compatibilidade)
    - **Ação:** Nova migration

#### 8.1.5 Estoque

12. **Todas as queries que verificam estoque:**
    - `src/Http/Controllers/Storefront/ProductController.php::renderProductList()` (linha 71)
    - `src/Http/Controllers/Storefront/HomeController.php` (múltiplas linhas)
    - `themes/default/storefront/products/index.php` (linha 56)
    - **Ação:** Modificar para verificar estoque da variação (se produto tiver variações)

### 8.2 Riscos de Duplicação

#### 8.2.1 Campo `tipo` na Tabela `produtos`

**Risco:** Campo `tipo ENUM('simple','variable','grouped','external')` existe mas não é usado.

**Status:** Campo existe, mas código sempre usa `'simple'`.

**Ação:** Pode usar `tipo = 'variable'` para produtos com variações, mas precisa:
- Atualizar código que assume `tipo = 'simple'`
- Adicionar validação: se `tipo = 'variable'`, obrigar ter variações

#### 8.2.2 Tabelas Antigas (Não Usadas)

**Tabelas encontradas mas não usadas:**
- `products` (migration `011_create_products_table.php`) - parece ser versão antiga
- `order_items` (migration `016_create_order_items_table.php`) - não usada (usa `pedido_itens`)

**Ação:** Verificar se essas tabelas existem no banco. Se sim, podem ser removidas ou ignoradas.

### 8.3 Suposições Fortes no Código

#### 8.3.1 "1 produto = 1 estoque = 1 preço = 1 SKU"

**Evidências:**
- Carrinho usa apenas `produto_id` como chave
- `pedido_itens` não tem campo de variação
- Formulário admin não tem campos de variação
- Storefront não tem seletores de atributos

**Impacto:** Alto - precisa refatorar múltiplos pontos.

#### 8.3.2 "Estoque é sempre do produto"

**Evidências:**
- Todas as queries de estoque usam `produtos.quantidade_estoque`
- Não há tabela de estoque por variação

**Impacto:** Alto - precisa criar estrutura de estoque por variação.

#### 8.3.3 "Preço é sempre do produto"

**Evidências:**
- Carrinho usa `preco_unitario` do produto
- Storefront exibe preço do produto

**Impacto:** Médio - variações podem ter preço próprio ou herdar do produto.

---

## 9. Oportunidades (Se Já Houver Algo Pronto)

### 9.1 Campo `tipo` na Tabela `produtos`

**Status:** Existe mas não é usado.

**Oportunidade:** Pode usar `tipo = 'variable'` para produtos com variações.

**Ação necessária:**
- Atualizar código que assume `tipo = 'simple'`
- Adicionar validação: se `tipo = 'variable'`, obrigar ter variações

### 9.2 Estrutura de Metadados

**Tabela:** `produto_meta` (migration `026_create_produto_meta_table.php`)

**Status:** Existe mas não foi verificado se é usada.

**Oportunidade:** Pode ser usada para armazenar atributos temporariamente, mas não é ideal para variações.

**Recomendação:** Criar tabelas dedicadas (`produto_atributos`, `produto_variacoes`).

### 9.3 Snapshot em `pedido_itens`

**Status:** Já existe snapshot de `nome_produto`, `sku`, `preco_unitario`.

**Oportunidade:** Compatível com variações - pode adicionar snapshot de atributos da variação (ex: `atributos_json` ou colunas `tamanho`, `cor`).

**Vantagem:** Não quebra pedidos antigos (campo nullable).

---

## 10. Respostas às Perguntas Críticas

### 10.1 Hoje o estoque é por produto único ou já existe estrutura para variações?

**Resposta:** Estoque é por produto único. Não existe estrutura para variações.

**Evidência:**
- Tabela `produtos` tem apenas `quantidade_estoque` (por produto)
- Não existe tabela `produto_variacoes` ou similar
- Código sempre usa `produtos.quantidade_estoque`

### 10.2 É possível introduzir "variação" sem quebrar pedidos antigos?

**Resposta:** Sim, com cuidado.

**Estratégia:**
1. Adicionar coluna `variacao_id` em `pedido_itens` (nullable)
2. Manter `produto_id` (obrigatório)
3. Pedidos antigos terão `variacao_id = NULL` (compatível)
4. Adicionar snapshot de atributos (ex: `atributos_json` ou colunas `tamanho`, `cor`) para histórico

**Risco:** Baixo se fizer migration correta.

### 10.3 Qual o melhor ponto de ancoragem?

**Resposta:** Manter `produtos` como "produto pai (matriz)" e criar `produto_variacoes`.

**Justificativa:**
- Campo `tipo` já existe e aceita `'variable'`
- Estrutura atual já trata `produtos` como entidade principal
- Variações são "filhas" do produto
- Compatível com modelo WooCommerce (referência)

**Estrutura proposta:**
```
produtos (produto pai)
  ├─ tipo = 'simple' → produto simples (comportamento atual)
  └─ tipo = 'variable' → produto com variações
      └─ produto_variacoes (variações filhas)
          ├─ produto_id (FK)
          ├─ sku (único por variação)
          ├─ preco (opcional, herda do pai se NULL)
          ├─ quantidade_estoque
          └─ status_estoque
```

### 10.4 Existe no código suposição forte do tipo "1 produto = 1 estoque = 1 preço = 1 SKU"?

**Resposta:** Sim, em múltiplos pontos.

**Evidências:**
1. **Carrinho:** Usa apenas `produto_id` como chave (linha 67 de `CartService.php`)
2. **Pedido:** `pedido_itens` não tem campo de variação
3. **Storefront:** Formulário de carrinho envia apenas `produto_id` (linha 223 de `show.php`)
4. **Admin:** Formulário não tem campos de variação
5. **Estoque:** Todas as queries usam `produtos.quantidade_estoque`

**Impacto:** Alto - precisa refatorar todos esses pontos.

---

## 11. Mapa do Fluxo

```
┌─────────────────────────────────────────────────────────────────┐
│                         ADMIN (Cadastro)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  ProductController::store()         │
        │  - Valida dados                     │
        │  - Calcula preço/estoque            │
        │  - INSERT produtos                   │
        │  - INSERT produto_categorias         │
        │  - INSERT produto_imagens            │
        └─────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Tabela:        │
                    │  produtos       │
                    │  - id           │
                    │  - sku          │
                    │  - preco        │
                    │  - estoque      │
                    └─────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    STOREFRONT (Exibição)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  ProductController::show()          │
        │  - Busca por slug                   │
        │  - Carrega imagens                  │
        │  - Carrega categorias                │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  Formulário: Adicionar ao Carrinho │
        │  - produto_id                       │
        │  - quantidade                       │
        └─────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         CARRINHO                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  CartController::add()              │
        │  - Busca produto                    │
        │  - Calcula preço                    │
        │  - Adiciona à sessão                │
        └─────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Sessão PHP:    │
                    │  cart_{tenant}  │
                    │  - produto_id  │
                    │  - quantidade   │
                    │  - preco       │
                    └─────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CHECKOUT / PEDIDO                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │  CheckoutController::store()       │
        │  - Valida dados                     │
        │  - INSERT pedidos                   │
        │  - INSERT pedido_itens              │
        │    (snapshot: nome, sku, preco)     │
        │  - Processa pagamento               │
        │  - Limpa carrinho                   │
        └─────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Tabela:        │
                    │  pedidos        │
                    │  pedido_itens   │
                    │  - produto_id   │
                    │  - nome (snap)  │
                    │  - sku (snap)   │
                    │  - preco (snap)  │
                    └─────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  ESTOQUE:      │
                    │  ⚠️ NÃO É       │
                    │  DECREMENTADO   │
                    └─────────────────┘
```

---

## 12. Lista de Arquivos Críticos

### 12.1 Admin

| Arquivo | Função/Método | Resumo | Linhas Críticas |
|---------|---------------|--------|-----------------|
| `src/Http/Controllers/Admin/ProductController.php` | `store()` | Criar produto | 261-410 |
| `src/Http/Controllers/Admin/ProductController.php` | `update()` | Atualizar produto | 614-746 |
| `src/Http/Controllers/Admin/ProductController.php` | `edit()` | Carregar dados para edição | 550-613 |
| `themes/default/admin/products/edit-content.php` | Formulário | HTML do formulário de edição | 1-1883 |
| `themes/default/admin/products/create-content.php` | Formulário | HTML do formulário de criação | Similar ao edit |

### 12.2 Storefront

| Arquivo | Função/Método | Resumo | Linhas Críticas |
|---------|---------------|--------|-----------------|
| `src/Http/Controllers/Storefront/ProductController.php` | `show()` | Exibir produto | 284-345 |
| `themes/default/storefront/products/show.php` | View | Template da página de produto | 222-233 (formulário) |
| `src/Http/Controllers/Storefront/CartController.php` | `add()` | Adicionar ao carrinho | 66-129 |
| `src/Services/CartService.php` | `addItem()` | Lógica de adicionar item | 63-76 |
| `src/Services/CartService.php` | `get()` | Obter carrinho | 33-38 |

### 12.3 Checkout/Pedido

| Arquivo | Função/Método | Resumo | Linhas Críticas |
|---------|---------------|--------|-----------------|
| `src/Http/Controllers/Storefront/CheckoutController.php` | `store()` | Criar pedido | 388-406 (itens) |
| `database/migrations/032_create_pedido_itens_table.php` | Migration | Estrutura de itens | Toda |

### 12.4 Estoque

| Arquivo | Função/Método | Resumo | Linhas Críticas |
|---------|---------------|--------|-----------------|
| `src/Http/Controllers/Admin/ProductController.php` | `store()`/`update()` | Atualizar estoque | 300-314, 644-653 |
| `src/Http/Controllers/Storefront/ProductController.php` | `renderProductList()` | Filtrar por estoque | 71 |
| `src/Http/Controllers/Storefront/HomeController.php` | Múltiplos métodos | Filtrar por estoque | 58, 159, 258, 345 |

---

## 13. Checklist do que Foi Verificado

### 13.1 Banco de Dados
- [x] Estrutura da tabela `produtos`
- [x] Estrutura da tabela `pedido_itens`
- [x] Verificação de tabelas de variações (não existem)
- [x] Verificação de campo `tipo` (existe mas não usado)
- [x] Verificação de índices e chaves estrangeiras

### 13.2 Cadastro Admin
- [x] Rota e controller de criação
- [x] Rota e controller de edição
- [x] Formulário HTML
- [x] Validação de dados
- [x] Persistência no banco

### 13.3 Exibição Storefront
- [x] Rota e controller de exibição
- [x] Template da página de produto
- [x] Formulário de adicionar ao carrinho
- [x] Cálculo de preço exibido

### 13.4 Carrinho
- [x] Estrutura de dados (sessão)
- [x] Adição de item
- [x] Atualização de item
- [x] Remoção de item
- [x] Cálculo de subtotal

### 13.5 Checkout/Pedido
- [x] Criação de pedido
- [x] Inserção de itens
- [x] Snapshot de dados
- [x] Processamento de pagamento

### 13.6 Estoque
- [x] Onde é atualizado (apenas admin)
- [x] Onde é validado (apenas exibição, não no carrinho)
- [x] Onde é decrementado (não existe)
- [x] Onde é reposto (não existe)
- [x] Filtros de produtos sem estoque

### 13.7 Código de Busca
- [x] Termos: variation, variant, atributo, option, tamanho, cor, SKU, estoque
- [x] Verificação de suposições no código
- [x] Identificação de pontos de acoplamento

---

## 14. Conclusões e Recomendações

### 14.1 Principais Achados

1. **Não existe estrutura de variações** - Tudo é produto simples
2. **Estoque não é decrementado automaticamente** - Apenas manual via admin
3. **Carrinho usa apenas `produto_id`** - Não diferencia variações
4. **Campo `tipo` existe mas não é usado** - Pode ser aproveitado
5. **Snapshot em `pedido_itens` é compatível** - Não quebra pedidos antigos

### 14.2 Arquivos Mais Críticos (Top 5)

1. **`src/Http/Controllers/Admin/ProductController.php`** - Lógica de cadastro/edição
2. **`src/Services/CartService.php`** - Estrutura do carrinho
3. **`src/Http/Controllers/Storefront/CartController.php`** - Adição ao carrinho
4. **`src/Http/Controllers/Storefront/CheckoutController.php`** - Criação de pedido
5. **`themes/default/storefront/products/show.php`** - Formulário de carrinho

### 14.3 Próximos Passos Sugeridos

1. **Criar migrations:**
   - `037_create_produto_atributos_table.php` (atributos globais: Tamanho, Cor, etc.)
   - `038_create_produto_variacoes_table.php` (variações do produto)
   - `039_add_variacao_id_to_pedido_itens.php` (compatibilidade com pedidos antigos)

2. **Refatorar código:**
   - Modificar `CartService` para usar `variacao_id`
   - Modificar `ProductController` (admin) para gerenciar variações
   - Modificar `ProductController` (storefront) para exibir seletores
   - Modificar `CheckoutController` para validar e decrementar estoque

3. **Implementar validações:**
   - Validar estoque antes de adicionar ao carrinho
   - Validar estoque antes de criar pedido
   - Decrementar estoque ao criar pedido (ou ao pagar)

4. **Testes:**
   - Testar compatibilidade com pedidos antigos
   - Testar produtos simples (sem variações)
   - Testar produtos com variações

---

**Fim do Relatório**
