# IMPLEMENTAÇÃO — Variações de Produto (Fase 1 e 2)

**Data:** 2025-01-27  
**Status:** ✅ Implementado (DB + Carrinho/Pedido)

---

## Resumo

Implementação das Fases 1 e 2 do sistema de variações de produto:
- **Fase 1:** Migrations para estrutura de atributos e variações
- **Fase 2:** Modificações no carrinho e checkout para suportar variações com validação e decremento de estoque

---

## Fase 1 — Migrations

### Tabelas Criadas

#### 1. `atributos` (047)
Atributos globais do tenant (ex: Tamanho, Cor, Material).

**Colunas principais:**
- `id`, `tenant_id`, `nome`, `slug`
- `tipo` ENUM('select','color','image')
- `ordem` INT

**Índices:**
- `idx_tenant_id`
- `idx_tenant_slug`
- `UNIQUE KEY unique_tenant_slug (tenant_id, slug)`

#### 2. `atributo_termos` (048)
Termos/valores dos atributos (ex: P, M, G para Tamanho; Vermelho, Azul para Cor).

**Colunas principais:**
- `id`, `tenant_id`, `atributo_id`, `nome`, `slug`
- `valor_cor` VARCHAR(7) - Código hexadecimal da cor
- `imagem` VARCHAR(255) - Caminho da imagem do termo
- `ordem` INT

**Índices:**
- `idx_tenant_id`, `idx_atributo_id`
- `idx_tenant_atributo_slug`
- `UNIQUE KEY unique_tenant_atributo_slug (tenant_id, atributo_id, slug)`

#### 3. `produto_atributos` (049)
Relação N:N entre produtos e atributos (quais atributos um produto usa).

**Colunas principais:**
- `id`, `tenant_id`, `produto_id`, `atributo_id`
- `ordem` INT

**Índices:**
- `idx_tenant_id`, `idx_produto_id`, `idx_atributo_id`
- `UNIQUE KEY unique_produto_atributo (produto_id, atributo_id)`

#### 4. `produto_atributo_termos` (050)
Relação N:N entre produtos e termos de atributos (quais valores estão disponíveis para o produto).

**Colunas principais:**
- `id`, `tenant_id`, `produto_id`, `atributo_id`, `atributo_termo_id`

**Índices:**
- `idx_tenant_id`, `idx_produto_id`, `idx_atributo_id`, `idx_atributo_termo_id`
- `UNIQUE KEY unique_produto_atributo_termo (produto_id, atributo_id, atributo_termo_id)`

#### 5. `produto_variacoes` (051)
Variações do produto (combinações de atributos).

**Colunas principais:**
- `id`, `tenant_id`, `produto_id`
- `sku` VARCHAR(100) - SKU único da variação
- `preco`, `preco_regular`, `preco_promocional` - Preços específicos (herdam do produto se NULL)
- `gerencia_estoque` TINYINT(1) - Se gerencia estoque próprio (1) ou herda do produto (0)
- `quantidade_estoque` INT
- `status_estoque` ENUM('instock','outofstock','onbackorder')
- `permite_pedidos_falta` ENUM('no','notify','yes')
- `imagem` VARCHAR(255) - Imagem específica da variação
- `peso`, `comprimento`, `largura`, `altura` - Dimensões específicas (herdam se NULL)
- `status` ENUM('publish','draft','private')

**Índices:**
- `idx_tenant_id`, `idx_produto_id`
- `idx_tenant_sku`
- `idx_status`, `idx_status_estoque`
- `UNIQUE KEY unique_tenant_sku (tenant_id, sku)`

#### 6. `produto_variacao_atributos` (052)
Relação entre variações e atributos/termos (define quais atributos cada variação tem).

**Colunas principais:**
- `id`, `tenant_id`, `variacao_id`, `atributo_id`, `atributo_termo_id`

**Índices:**
- `idx_tenant_id`, `idx_variacao_id`, `idx_atributo_id`, `idx_atributo_termo_id`
- `UNIQUE KEY unique_variacao_atributo (variacao_id, atributo_id)`

#### 7. Modificação em `pedido_itens` (053)
Adiciona suporte a variações nos itens de pedido.

**Colunas adicionadas:**
- `variacao_id` BIGINT UNSIGNED NULL - FK para produto_variacoes
- `atributos_json` TEXT NULL - Snapshot JSON dos atributos no momento do pedido

**Índices:**
- `idx_variacao_id`
- `FOREIGN KEY fk_pedido_itens_variacao` → `produto_variacoes(id) ON DELETE SET NULL`

**Justificativa do ON DELETE SET NULL:**
- Se uma variação for deletada, o pedido histórico deve manter a referência (com variacao_id = NULL)
- O snapshot em `atributos_json` preserva as informações da variação
- Compatível com pedidos antigos (variacao_id = NULL)

---

## Fase 2 — Carrinho/Pedido

### Mudanças no Carrinho

#### CartService (`src/Services/CartService.php`)

**Mudanças:**
1. **Nova função `getItemKey()`:**
   - Gera chave de identificação: `"v:{variacao_id}"` se tiver variação, senão `"p:{produto_id}"`
   - Permite diferenciar itens do mesmo produto com variações diferentes

2. **`addItem()` modificado:**
   - Aceita `variacao_id` opcional em `$itemData`
   - Usa `getItemKey()` para identificar item único
   - Mantém compatibilidade: se não tiver `variacao_id`, usa chave `"p:{produto_id}"`

3. **`updateItem()` e `removeItem()` modificados:**
   - Agora recebem `$itemKey` (string) em vez de `$produtoId` (int)
   - Permite atualizar/remover itens por chave completa

**Estrutura do item no carrinho:**
```php
[
    'produto_id' => int,
    'variacao_id' => int|null,
    'nome' => string,  // Ex: "Camiseta (Tamanho: P, Cor: Vermelho)"
    'slug' => string,
    'preco_unitario' => float,
    'quantidade' => int,
    'imagem' => string|null,
    'atributos' => string,  // Ex: "Tamanho: P, Cor: Vermelho"
    'sku' => string|null
]
```

#### CartController (`src/Http/Controllers/Storefront/CartController.php`)

**Mudanças em `add()`:**

1. **Recebe `variacao_id` opcional do POST:**
   ```php
   $variacaoId = !empty($_POST['variacao_id']) ? (int)$_POST['variacao_id'] : null;
   ```

2. **Se tem variação:**
   - Valida que variação pertence ao produto e ao tenant
   - Valida estoque da variação (se `gerencia_estoque=1` e `permite_pedidos_falta='no'`, bloqueia)
   - Determina preço: prioriza preço da variação, senão herda do produto
   - Busca SKU da variação (ou do produto se não tiver)
   - Busca imagem da variação (ou do produto se não tiver)
   - Busca atributos da variação e monta string (ex: "Tamanho: P, Cor: Vermelho")

3. **Se não tem variação (produto simples):**
   - Valida estoque do produto (se `gerencia_estoque=1` e `permite_pedidos_falta='no'`, bloqueia)
   - Comportamento similar ao anterior, mas com validação de estoque

4. **Monta nome do produto:**
   - Se tiver atributos, adiciona ao nome: `"{nome_produto} ({atributos})"`

5. **Adiciona ao carrinho:**
   - Inclui `variacao_id`, `atributos`, `sku` no item

**Mudanças em `update()` e `remove()`:**
- Agora recebem `item_key` do POST em vez de `produto_id`
- Permite atualizar/remover itens específicos (incluindo variações)

### Mudanças no Checkout

#### CheckoutController (`src/Http/Controllers/Storefront/CheckoutController.php`)

**Mudanças em `store()`:**

1. **Inserção de itens modificada:**
   - Adiciona `variacao_id` e `atributos_json` em `pedido_itens`
   - Busca SKU: prioriza variação, senão produto
   - Monta `atributos_json`: snapshot completo dos atributos da variação (se houver)

2. **Decremento de estoque implementado:**
   - **Para variação:**
     ```sql
     UPDATE produto_variacoes 
     SET quantidade_estoque = quantidade_estoque - :quantidade,
         status_estoque = CASE 
             WHEN gerencia_estoque = 1 AND (quantidade_estoque - :quantidade) <= 0 THEN 'outofstock'
             WHEN gerencia_estoque = 1 AND (quantidade_estoque - :quantidade) > 0 THEN 'instock'
             ELSE status_estoque
         END
     WHERE id = :variacao_id
     AND gerencia_estoque = 1
     AND quantidade_estoque >= :quantidade
     ```
   
   - **Para produto simples:**
     ```sql
     UPDATE produtos 
     SET quantidade_estoque = quantidade_estoque - :quantidade,
         status_estoque = CASE 
             WHEN gerencia_estoque = 1 AND (quantidade_estoque - :quantidade) <= 0 THEN 'outofstock'
             WHEN gerencia_estoque = 1 AND (quantidade_estoque - :quantidade) > 0 THEN 'instock'
             ELSE status_estoque
         END
     WHERE id = :produto_id
     AND gerencia_estoque = 1
     AND quantidade_estoque >= :quantidade
     ```

3. **Validação de estoque:**
   - Usa `WHERE quantidade_estoque >= :quantidade` para evitar race condition
   - Se `rowCount() === 0`, lança exceção e aborta transação
   - Mensagem amigável: "Estoque insuficiente para [produto/variação]. Por favor, verifique o carrinho e tente novamente."

4. **Compatibilidade:**
   - Pedidos antigos continuam funcionando (`variacao_id = NULL`)
   - Snapshot de atributos preserva informações mesmo se variação for deletada

---

## Como Testar Manualmente

### Pré-requisitos

1. Executar migrations:

   **No PowerShell (Windows/XAMPP):**
   ```powershell
   # Opção 1: Usar caminho completo do PHP (recomendado)
   Set-Location "C:\xampp\htdocs\ecommerce-v1.0"
   & "C:\xampp\php\php.exe" "database\run_migrations.php"
   
   # Opção 2: Adicionar PHP ao PATH temporariamente
   $env:Path = "C:\xampp\php;" + $env:Path
   php database\run_migrations.php
   ```

   **No Linux/Mac:**
   ```bash
   php database/run_migrations.php
   ```

   Ou executar individualmente:
   - 047_create_atributos_table.php
   - 048_create_atributo_termos_table.php
   - 049_create_produto_atributos_table.php
   - 050_create_produto_atributo_termos_table.php
   - 051_create_produto_variacoes_table.php
   - 052_create_produto_variacao_atributos_table.php
   - 053_add_variacao_id_and_atributos_json_to_pedido_itens.php

### Teste 1: Produto Simples (Compatibilidade)

1. **Criar produto simples** (via admin):
   - Nome: "Produto Teste"
   - Preço: R$ 100,00
   - Estoque: 10 unidades
   - `gerencia_estoque = 1`

2. **Adicionar ao carrinho** (via storefront):
   - POST `/carrinho/adicionar`
   - `produto_id = {id}`
   - `quantidade = 2`
   - **Não enviar `variacao_id`**

3. **Verificar carrinho:**
   - Item deve ter chave `"p:{produto_id}"`
   - Nome: "Produto Teste"
   - Preço: R$ 100,00
   - Quantidade: 2

4. **Finalizar pedido:**
   - Verificar que estoque foi decrementado (deve ficar 8)
   - Verificar `pedido_itens`: `variacao_id = NULL`, `atributos_json = NULL`

### Teste 2: Produto com Variação

1. **Criar atributos** (via SQL ou admin futuro):
   ```sql
   INSERT INTO atributos (tenant_id, nome, slug, tipo) VALUES
   (1, 'Tamanho', 'tamanho', 'select'),
   (1, 'Cor', 'cor', 'color');
   
   INSERT INTO atributo_termos (tenant_id, atributo_id, nome, slug) VALUES
   (1, 1, 'P', 'p'),
   (1, 1, 'M', 'm'),
   (1, 1, 'G', 'g'),
   (1, 2, 'Vermelho', 'vermelho'),
   (1, 2, 'Azul', 'azul');
   ```

2. **Criar produto variável** (via SQL ou admin futuro):
   ```sql
   UPDATE produtos SET tipo = 'variable' WHERE id = {produto_id};
   
   INSERT INTO produto_atributos (tenant_id, produto_id, atributo_id) VALUES
   (1, {produto_id}, 1),  -- Tamanho
   (1, {produto_id}, 2);  -- Cor
   ```

3. **Criar variações** (via SQL ou admin futuro):
   ```sql
   INSERT INTO produto_variacoes 
   (tenant_id, produto_id, sku, preco_regular, gerencia_estoque, quantidade_estoque, status_estoque) 
   VALUES
   (1, {produto_id}, 'CAM-P-VERM', 100.00, 1, 5, 'instock'),
   (1, {produto_id}, 'CAM-M-AZUL', 110.00, 1, 3, 'instock');
   
   INSERT INTO produto_variacao_atributos 
   (tenant_id, variacao_id, atributo_id, atributo_termo_id) 
   VALUES
   (1, {variacao_1_id}, 1, 1),  -- Tamanho: P
   (1, {variacao_1_id}, 2, 3),  -- Cor: Vermelho
   (1, {variacao_2_id}, 1, 2),  -- Tamanho: M
   (1, {variacao_2_id}, 2, 4);  -- Cor: Azul
   ```

4. **Adicionar variação ao carrinho** (via storefront):
   - POST `/carrinho/adicionar`
   - `produto_id = {id}`
   - `variacao_id = {variacao_1_id}`
   - `quantidade = 2`

5. **Verificar carrinho:**
   - Item deve ter chave `"v:{variacao_1_id}"`
   - Nome: "Produto Teste (Tamanho: P, Cor: Vermelho)"
   - Preço: R$ 100,00 (da variação)
   - Quantidade: 2

6. **Finalizar pedido:**
   - Verificar que estoque da variação foi decrementado (deve ficar 3)
   - Verificar `pedido_itens`: 
     - `variacao_id = {variacao_1_id}`
     - `atributos_json` contém JSON com atributos
     - `sku = 'CAM-P-VERM'`

### Teste 3: Validação de Estoque

1. **Criar variação com estoque baixo:**
   ```sql
   INSERT INTO produto_variacoes 
   (tenant_id, produto_id, sku, preco_regular, gerencia_estoque, quantidade_estoque, status_estoque, permite_pedidos_falta) 
   VALUES
   (1, {produto_id}, 'CAM-G-VERM', 120.00, 1, 1, 'instock', 'no');
   ```

2. **Tentar adicionar quantidade maior que estoque:**
   - POST `/carrinho/adicionar`
   - `variacao_id = {variacao_id}`
   - `quantidade = 5`
   - **Deve redirecionar com erro:** `?error=estoque_insuficiente`

3. **Tentar finalizar pedido com estoque insuficiente:**
   - Adicionar item com quantidade válida ao carrinho
   - Em outra sessão/aba, decrementar estoque manualmente
   - Tentar finalizar pedido
   - **Deve lançar exceção e abortar transação**

### Teste 4: Compatibilidade com Pedidos Antigos

1. **Verificar pedido antigo:**
   - Pedidos criados antes da implementação devem ter:
     - `variacao_id = NULL`
     - `atributos_json = NULL`
   - **Não deve quebrar ao exibir pedido**

2. **Verificar carrinho antigo:**
   - Carrinhos salvos na sessão com formato antigo (`produto_id` como chave numérica)
   - **Deve funcionar** (compatibilidade retroativa)

---

## Arquivos Modificados

### Migrations (Novos)
- `database/migrations/047_create_atributos_table.php`
- `database/migrations/048_create_atributo_termos_table.php`
- `database/migrations/049_create_produto_atributos_table.php`
- `database/migrations/050_create_produto_atributo_termos_table.php`
- `database/migrations/051_create_produto_variacoes_table.php`
- `database/migrations/052_create_produto_variacao_atributos_table.php`
- `database/migrations/053_add_variacao_id_and_atributos_json_to_pedido_itens.php`

### Código (Modificados)
- `src/Services/CartService.php`
- `src/Http/Controllers/Storefront/CartController.php`
- `src/Http/Controllers/Storefront/CheckoutController.php`

---

## Checklist de Testes Manuais (Mínimo para Validar 95% do Risco)

### 3.1 Produto Simples (Sem Variação)

**Setup:**
1. Criar/editar um produto simples via admin:
   - `gerencia_estoque = 1`
   - `quantidade_estoque = 5`

**Teste 1: Validação de Estoque no Carrinho**
- Tentar adicionar quantidade **6** ao carrinho
- **Resultado esperado:** Deve bloquear com erro `estoque_insuficiente`

**Teste 2: Adição Válida**
- Adicionar quantidade **2** ao carrinho
- **Resultado esperado:** Deve permitir e adicionar ao carrinho

**Teste 3: Decremento de Estoque no Checkout**
- Finalizar checkout
- **Resultado esperado:**
  - Estoque deve cair de **5 → 3**
  - `pedido_itens.variacao_id` deve ficar **NULL**
  - `pedido_itens.atributos_json` deve ficar **NULL**

### 3.2 Produto com Variações (Teste Técnico)

**Setup (via SQL, pois Fase 3 ainda não existe):**

1. **Criar atributos e termos:**
   ```sql
   INSERT INTO atributos (tenant_id, nome, slug, tipo) VALUES
   (1, 'Tamanho', 'tamanho', 'select'),
   (1, 'Cor', 'cor', 'color');
   
   INSERT INTO atributo_termos (tenant_id, atributo_id, nome, slug) VALUES
   (1, 1, 'P', 'p'),
   (1, 1, 'M', 'm'),
   (1, 2, 'Vermelho', 'vermelho');
   ```

2. **Criar produto variável:**
   ```sql
   UPDATE produtos SET tipo = 'variable' WHERE id = {produto_id};
   
   INSERT INTO produto_atributos (tenant_id, produto_id, atributo_id) VALUES
   (1, {produto_id}, 1),  -- Tamanho
   (1, {produto_id}, 2);  -- Cor
   ```

3. **Criar variação:**
   ```sql
   INSERT INTO produto_variacoes 
   (tenant_id, produto_id, sku, preco_regular, gerencia_estoque, quantidade_estoque, status_estoque) 
   VALUES
   (1, {produto_id}, 'CAM-P-VERM', 100.00, 1, 3, 'instock');
   
   INSERT INTO produto_variacao_atributos 
   (tenant_id, variacao_id, atributo_id, atributo_termo_id) 
   VALUES
   (1, {variacao_id}, 1, 1),  -- Tamanho: P
   (1, {variacao_id}, 2, 3);  -- Cor: Vermelho
   ```

**Teste 1: Validação de Estoque da Variação**
- Tentar adicionar ao carrinho com `variacao_id` e quantidade **4** (acima do estoque de 3)
- **Resultado esperado:** Deve bloquear com erro `estoque_insuficiente`

**Teste 2: Adição Válida de Variação**
- Adicionar ao carrinho com `variacao_id` e quantidade **2** (dentro do estoque)
- **Resultado esperado:** Deve permitir e adicionar ao carrinho

**Teste 3: Checkout com Variação**
- Finalizar checkout
- **Resultado esperado:**
  - `pedido_itens.variacao_id` deve estar **preenchido** com o ID da variação
  - `pedido_itens.atributos_json` deve conter JSON com os atributos
  - Estoque da **variação** deve ser decrementado (não do produto pai)
  - Estoque do produto pai **não deve** ser alterado

### 3.3 Compatibilidade / Histórico

**Teste:**
- Abrir um pedido antigo (criado antes das migrations)
- **Resultado esperado:**
  - Pedido deve carregar normalmente
  - `variacao_id = NULL` (compatível)
  - `atributos_json = NULL` (compatível)
  - Nenhum erro ao exibir pedido

---

## Próximos Passos (Fase 3 - Admin UI)

Hoje o sistema já suporta variação no carrinho/checkout, mas ainda falta **onde o usuário escolhe e onde o admin cria**.

### Fase 3 - Entregas Planejadas

#### 3.1 Admin: Gerenciamento de Atributos e Termos
- Interface para criar/editar atributos (Tamanho, Cor, Material, etc.)
- Interface para criar/editar termos dos atributos (P, M, G, Vermelho, Azul, etc.)
- Suporte a cores (hexadecimal) e imagens por termo

#### 3.2 Admin: Produto Variável
- Selecionar atributos do produto (quais atributos o produto usa)
- Selecionar termos disponíveis para cada atributo
- Gerar variações automaticamente (combinações de atributos)
- Grade de variações com:
  - SKU por variação
  - Estoque por variação
  - Preço por variação (opcional, herda do produto se não preenchido)
  - Imagem por variação (opcional)
  - Status (publish/draft)

#### 3.3 Storefront: Seletores de Variação
- Exibir seletores de atributos na página do produto (se `tipo = 'variable'`)
- Atualizar preço/imagem quando variação for selecionada
- Enviar `variacao_id` no formulário de adicionar ao carrinho
- Validação visual de estoque (desabilitar variações sem estoque)

---

## Notas Importantes

1. **Compatibilidade:** Pedidos antigos continuam funcionando (`variacao_id = NULL`)
2. **Estoque:** Decremento acontece na criação do pedido (dentro da transação)
3. **Validação:** Estoque é validado tanto no carrinho quanto no checkout
4. **Chave do carrinho:** Mudou de `produto_id` (int) para `"p:{id}"` ou `"v:{id}"` (string)
5. **Snapshot:** `atributos_json` preserva informações mesmo se variação for deletada

---

**Fim do Documento**
