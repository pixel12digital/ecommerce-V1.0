# Sistema de Avaliações de Produtos

## 📋 Resumo

Sistema completo de avaliações e ratings de produtos, permitindo que clientes avaliem produtos que compraram, com moderação pelo admin.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Permitir que clientes avaliem produtos após a compra, exibindo essas avaliações na página do produto (PDP) para ajudar outros clientes na decisão de compra. O sistema inclui moderação pelo admin para garantir qualidade das avaliações.

---

## 🔧 Implementação

### Migration

**Arquivo:** `database/migrations/036_create_produto_avaliacoes_table.php`

**Tabela:** `produto_avaliacoes`

**Campos:**
- `id` - BIGINT UNSIGNED PK
- `tenant_id` - BIGINT UNSIGNED NOT NULL
- `produto_id` - BIGINT UNSIGNED NOT NULL
- `customer_id` - BIGINT UNSIGNED NOT NULL
- `pedido_id` - BIGINT UNSIGNED NULL (pedido onde o cliente comprou)
- `nota` - TINYINT UNSIGNED NOT NULL (1-5)
- `titulo` - VARCHAR(150) NULL
- `comentario` - TEXT NULL
- `status` - ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente'
- `created_at`, `updated_at` - DATETIME

**Índices:**
- `idx_tenant_produto` (tenant_id, produto_id)
- `idx_tenant_customer` (tenant_id, customer_id)
- `idx_tenant_pedido` (tenant_id, pedido_id)
- `idx_tenant_status` (tenant_id, status)
- `idx_produto_status` (produto_id, status)

### Controllers

#### Storefront

**Arquivo:** `src/Http/Controllers/Storefront/ProductReviewController.php`

**Métodos:**
- `store($slug)` - Recebe e valida avaliação do cliente

**Validações:**
- Cliente deve estar logado
- Cliente deve ter comprado o produto (pedido com status paid/completed/shipped)
- Cliente não pode ter avaliação ativa (pendente ou aprovada) para o mesmo produto
- Nota obrigatória (1-5)
- Título opcional (máx. 150 caracteres)
- Comentário opcional (máx. 5000 caracteres)

#### Admin

**Arquivo:** `src/Http/Controllers/Admin/ProductReviewController.php`

**Métodos:**
- `index()` - Listagem de avaliações com filtros e paginação
- `show($id)` - Detalhes de uma avaliação
- `approve($id)` - Aprovar avaliação
- `reject($id)` - Rejeitar avaliação

### Views

#### Storefront

**Arquivo:** `themes/default/storefront/products/show.php`

**Seção de Avaliações:**
- Resumo com média de estrelas e total de avaliações
- Lista de avaliações aprovadas (últimas 10)
- Formulário de avaliação (se cliente pode avaliar)
- Mensagens de feedback (login necessário, já avaliou, etc.)

#### Admin

**Arquivos:**
- `themes/default/admin/product-reviews/index-content.php` - Listagem
- `themes/default/admin/product-reviews/show-content.php` - Detalhes

---

## 📊 Funcionalidades

### 1. Avaliação na PDP

**Quem pode avaliar:**
- Cliente logado
- Que já comprou o produto (pedido com status paid/completed/shipped)
- Que ainda não avaliou o produto (sem avaliação pendente ou aprovada)

**Formulário:**
- Nota (1-5 estrelas) - obrigatório
- Título (opcional, máx. 150 caracteres)
- Comentário (opcional, máx. 5000 caracteres)

**Fluxo:**
1. Cliente preenche formulário
2. Avaliação é salva com `status = 'pendente'`
3. Mensagem: "Avaliação enviada e aguarda aprovação"
4. Admin modera e aprova/rejeita

### 2. Exibição na PDP

**Resumo:**
- Média de estrelas (ex: 4.6 de 5)
- Visualização de estrelas (cheias/vazias/meia)
- Total de avaliações aprovadas

**Lista de Avaliações:**
- Últimas 10 avaliações aprovadas
- Nome do cliente (ou "Cliente" se não disponível)
- Nota em estrelas
- Título (se houver)
- Comentário (se houver)
- Data da avaliação

**Somente avaliações com `status = 'aprovado'` são exibidas na PDP.**

### 3. Moderação no Admin

**Listagem (`/admin/avaliacoes`):**
- Filtros: Status, Produto, Nota, Busca (produto/cliente)
- Colunas: Produto, Cliente, Nota, Título, Status, Data, Ações
- Ações rápidas: Ver, Aprovar, Rejeitar
- Paginação (20 por página)

**Detalhes (`/admin/avaliacoes/{id}`):**
- Informações completas da avaliação
- Dados do produto (com link para edição)
- Dados do cliente (com link para detalhes)
- Pedido relacionado (se disponível)
- Botões: Aprovar / Rejeitar

**Status:**
- `pendente` - Aguardando moderação (amarelo)
- `aprovado` - Publicada na PDP (verde)
- `rejeitado` - Não será publicada (vermelho)

---

## 🔍 Como Usar

### Cliente - Avaliar Produto

1. Fazer login na loja
2. Comprar um produto (pedido deve ser pago/concluído)
3. Acessar a página do produto (`/produto/{slug}`)
4. Rolar até a seção "Avaliações"
5. Preencher formulário:
   - Selecionar nota (1-5 estrelas)
   - Opcionalmente adicionar título
   - Opcionalmente adicionar comentário
6. Clicar em "Enviar Avaliação"
7. Aguardar aprovação do admin

### Admin - Moderar Avaliações

1. Acessar `/admin/avaliacoes`
2. Usar filtros para encontrar avaliações pendentes
3. Para cada avaliação:
   - Clicar em "Ver" para ver detalhes
   - Clicar em "Aprovar" para publicar na PDP
   - Clicar em "Rejeitar" para não publicar
4. Avaliações aprovadas aparecem automaticamente na PDP

---

## 🔒 Regras de Negócio

### Elegibilidade para Avaliar

1. **Cliente logado:** Sessão com `customer_id` válido
2. **Compra confirmada:** Deve existir registro em `pedido_itens` ligado a um `pedido` do cliente com status `paid`, `completed` ou `shipped`
3. **Sem avaliação ativa:** Não pode ter avaliação com `status IN ('pendente', 'aprovado')` para o mesmo produto

### Limite de Avaliações

- **Uma avaliação por produto por cliente**
- Se cliente já tem avaliação pendente ou aprovada, não pode criar nova
- Cliente pode ter múltiplas avaliações para produtos diferentes

### Moderação

- Todas as avaliações começam com `status = 'pendente'`
- Apenas avaliações `aprovado` aparecem na PDP
- Avaliações `rejeitado` não aparecem na PDP
- Admin pode mudar status: pendente ↔ aprovado ↔ rejeitado

### Cálculo de Média

- Média calculada apenas com avaliações `status = 'aprovado'`
- Fórmula: `AVG(nota) WHERE status = 'aprovado'`
- Arredondamento: 1 casa decimal (ex: 4.6)

---

## 🔗 Integrações

### ProductController (PDP)

O método `show($slug)` foi atualizado para:
- Buscar avaliações aprovadas do produto
- Calcular média e total
- Verificar se cliente logado pode avaliar
- Passar dados para a view

### Pedidos

Sistema verifica compra através de:
- Tabela `pedido_itens` (produto comprado)
- Tabela `pedidos` (status do pedido)
- Filtro: `customer_id` + `produto_id` + `status IN ('paid', 'completed', 'shipped')`

### Admin de Produtos

Link no menu lateral para `/admin/avaliacoes`

---

## 📝 Estrutura de Dados

### Tabela `produto_avaliacoes`

```sql
CREATE TABLE produto_avaliacoes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    pedido_id BIGINT UNSIGNED NULL,
    nota TINYINT UNSIGNED NOT NULL COMMENT '1-5',
    titulo VARCHAR(150) NULL,
    comentario TEXT NULL,
    status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_produto (tenant_id, produto_id),
    INDEX idx_tenant_customer (tenant_id, customer_id),
    INDEX idx_tenant_pedido (tenant_id, pedido_id),
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_produto_status (produto_id, status)
);
```

### Queries Principais

**Buscar avaliações aprovadas:**
```sql
SELECT 
    pa.*,
    c.name as nome_cliente
FROM produto_avaliacoes pa
LEFT JOIN customers c ON c.id = pa.customer_id AND c.tenant_id = pa.tenant_id
WHERE pa.tenant_id = :tenant_id
AND pa.produto_id = :produto_id
AND pa.status = 'aprovado'
ORDER BY pa.created_at DESC
LIMIT 10
```

**Calcular média:**
```sql
SELECT 
    AVG(nota) as media,
    COUNT(*) as total
FROM produto_avaliacoes
WHERE tenant_id = :tenant_id
AND produto_id = :produto_id
AND status = 'aprovado'
```

**Verificar se cliente comprou:**
```sql
SELECT pi.pedido_id
FROM pedido_itens pi
INNER JOIN pedidos p ON p.id = pi.pedido_id
WHERE p.tenant_id = :tenant_id
AND p.customer_id = :customer_id
AND pi.produto_id = :produto_id
AND p.status IN ('paid', 'completed', 'shipped')
LIMIT 1
```

---

## 🎨 Interface

### PDP - Seção de Avaliações

**Resumo:**
- Média grande (ex: "4.6 de 5")
- Estrelas visuais (cheias/vazias/meia)
- Total de avaliações

**Lista:**
- Cards com fundo branco
- Nome do cliente, nota em estrelas, data
- Título e comentário (se houver)

**Formulário:**
- Estrelas clicáveis (1-5)
- Campos de título e comentário
- Botão "Enviar Avaliação"

### Admin - Listagem

- Tabela responsiva
- Filtros no topo
- Badges de status coloridos
- Ações rápidas (Ver, Aprovar, Rejeitar)

### Admin - Detalhes

- Cards organizados por seção
- Links para produto, cliente e pedido
- Botões de ação destacados

---

## 🔒 Segurança e Multi-tenant

### Isolamento por Tenant

- Todas as queries filtram por `tenant_id`
- Cliente de um tenant não pode avaliar produto de outro
- Admin só vê avaliações do próprio tenant

### Validações

- Nota: 1-5 (validado no backend)
- Título: máx. 150 caracteres
- Comentário: máx. 5000 caracteres
- Verificação de compra antes de permitir avaliação
- Verificação de avaliação duplicada

### Sanitização

- Todos os outputs usam `htmlspecialchars()`
- Comentários usam `nl2br()` para quebras de linha
- Inputs validados e sanitizados antes de salvar

---

## 🐛 Troubleshooting

### Problema: Cliente não consegue avaliar

**Causas possíveis:**
1. Cliente não está logado → Redirecionar para login
2. Cliente não comprou o produto → Mensagem explicativa
3. Cliente já avaliou → Mensagem "Você já avaliou este produto"

**Solução:** Verificar logs e mensagens de erro na PDP

### Problema: Avaliação não aparece na PDP

**Causas possíveis:**
1. Status não é 'aprovado' → Admin precisa aprovar
2. Produto diferente → Verificar `produto_id`
3. Tenant diferente → Verificar `tenant_id`

**Solução:** Verificar status da avaliação no admin

### Problema: Média não calcula corretamente

**Causa:** Média considera apenas avaliações `aprovado`

**Solução:** Verificar se há avaliações aprovadas e se a query está correta

---

## 📚 Referências

- **Migration:** `database/migrations/036_create_produto_avaliacoes_table.php`
- **Controller Storefront:** `src/Http/Controllers/Storefront/ProductReviewController.php`
- **Controller Admin:** `src/Http/Controllers/Admin/ProductReviewController.php`
- **View PDP:** `themes/default/storefront/products/show.php`
- **Views Admin:** `themes/default/admin/product-reviews/`
- **Rotas:** `public/index.php`

---

## 🚀 Melhorias Futuras (Opcionais)

### Filtros na PDP
- Filtrar avaliações por nota (ex: só 5 estrelas)
- Ordenar por mais recente / mais útil
- Paginação de avaliações

### Respostas do Vendedor
- Admin pode responder avaliações
- Exibir resposta abaixo da avaliação

### Fotos nas Avaliações
- Permitir upload de fotos junto com avaliação
- Exibir fotos na lista de avaliações

### Útil/Não Útil
- Clientes podem marcar avaliações como úteis
- Ordenar por mais úteis

### Notificações
- E-mail ao admin quando nova avaliação pendente
- E-mail ao cliente quando avaliação for aprovada

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX


