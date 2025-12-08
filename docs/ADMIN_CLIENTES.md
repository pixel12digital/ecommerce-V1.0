# Admin - Gerenciar Clientes

## 📋 Resumo

Funcionalidade completa no painel administrativo para visualizar e gerenciar clientes cadastrados na loja.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Permitir que o lojista visualize e gerencie todos os clientes cadastrados na sua loja, incluindo:
- Listagem com busca e filtros
- Detalhes completos do cliente
- Histórico de pedidos
- Estatísticas de compras

---

## 🔧 Implementação

### Controller

**Arquivo:** `src/Http/Controllers/Admin/CustomerController.php`

**Métodos:**
- `index()` - Listagem de clientes com busca, filtros e paginação
- `show($id)` - Detalhes do cliente, endereços, pedidos e estatísticas

### Rotas

**Registradas em:** `public/index.php`

- `GET /admin/clientes` → `Admin\CustomerController@index`
- `GET /admin/clientes/{id}` → `Admin\CustomerController@show`

**Proteção:** Todas as rotas são protegidas por `AuthMiddleware` (autenticação de admin).

### Views

**Localização:** `themes/default/admin/customers/`

- `index-content.php` - Listagem de clientes
- `show-content.php` - Detalhes do cliente

---

## 📊 Funcionalidades

### 1. Listagem de Clientes (`/admin/clientes`)

**Busca:**
- Por nome, e-mail ou documento (CPF/CNPJ)
- Campo de busca: `q`

**Filtros:**
- Data inicial de cadastro
- Data final de cadastro

**Colunas da Tabela:**
- Nome
- E-mail
- Documento (CPF/CNPJ)
- Telefone
- Data de Cadastro
- Total de Pedidos (contagem)
- Ação: "Ver detalhes"

**Paginação:**
- 20 clientes por página
- Navegação anterior/próxima
- Exibe total de clientes e página atual

### 2. Detalhes do Cliente (`/admin/clientes/{id}`)

**Seções:**

#### Dados Cadastrais
- Nome
- E-mail
- Documento (CPF/CNPJ)
- Telefone
- Data de Cadastro
- Última Atualização

#### Estatísticas
- **Total de Pedidos:** Quantidade de pedidos realizados
- **Valor Total Gasto:** Soma de todos os pedidos
- **Data do Último Pedido:** Data do pedido mais recente

#### Endereços Cadastrados
- Lista todos os endereços do cliente
- Destaque para endereço padrão
- Informações: tipo, rua, número, complemento, bairro, cidade, estado, CEP

#### Histórico de Pedidos
- Tabela com todos os pedidos do cliente
- Colunas:
  - Número do Pedido
  - Data
  - Status (com badge colorido)
  - Valor Total
  - Link "Ver pedido" (abre no admin de pedidos)

---

## 🔍 Como Usar

### Acessar Listagem de Clientes

1. No menu lateral do admin, clique em **"Clientes"**
2. Ou acesse diretamente: `/admin/clientes`

### Buscar Cliente

1. No campo de busca, digite:
   - Nome do cliente
   - E-mail
   - Documento (CPF/CNPJ)
2. Clique em "Filtrar"
3. Para limpar filtros, clique em "Limpar filtros"

### Filtrar por Data

1. Preencha "Data inicial" e/ou "Data final"
2. Clique em "Filtrar"
3. A listagem mostrará apenas clientes cadastrados no período

### Ver Detalhes do Cliente

1. Na listagem, clique em **"Ver detalhes"** na linha do cliente
2. Ou acesse diretamente: `/admin/clientes/{id}`

### Ver Pedido do Cliente

1. Na página de detalhes do cliente, na seção "Histórico de Pedidos"
2. Clique em **"Ver pedido"** na linha do pedido desejado
3. Será redirecionado para `/admin/pedidos/{id}`

---

## 🔒 Segurança e Multi-tenant

### Isolamento por Tenant

- Todas as queries filtram por `tenant_id` (via `TenantContext::id()`)
- Cliente de um tenant não pode ser acessado por outro tenant
- Se tentar acessar cliente de outro tenant, retorna 404

### Autenticação

- Todas as rotas são protegidas por `AuthMiddleware`
- Apenas admins autenticados podem acessar
- Verificação automática de permissões

### Validação

- IDs são validados e convertidos para inteiros
- Busca sanitizada com `htmlspecialchars`
- Parâmetros de data validados

---

## 📝 Estrutura de Dados

### Tabelas Utilizadas

**`customers`**
- Campos principais: `id`, `tenant_id`, `name`, `email`, `document`, `phone`, `created_at`, `updated_at`

**`customer_addresses`**
- Campos: `id`, `tenant_id`, `customer_id`, `type`, `street`, `number`, `complement`, `neighborhood`, `city`, `state`, `zipcode`, `is_default`

**`pedidos`**
- Campos utilizados: `id`, `tenant_id`, `customer_id`, `numero_pedido`, `status`, `total_geral`, `created_at`

### Queries Principais

**Listagem:**
```sql
SELECT 
    c.*,
    (SELECT COUNT(*) FROM pedidos p 
     WHERE p.tenant_id = c.tenant_id 
     AND p.customer_id = c.id) as total_pedidos
FROM customers c
WHERE c.tenant_id = :tenant_id
  AND (c.name LIKE :q OR c.email LIKE :q OR c.document LIKE :q)
ORDER BY c.created_at DESC
LIMIT :limit OFFSET :offset
```

**Estatísticas:**
```sql
SELECT COALESCE(SUM(total_geral), 0) as total_gasto 
FROM pedidos 
WHERE customer_id = :customer_id 
AND tenant_id = :tenant_id
```

---

## 🎨 Interface

### Layout

- Usa o layout padrão do admin (`admin/layouts/store.php`)
- Menu lateral com link "Clientes"
- Design consistente com outras telas do admin

### Responsividade

- Tabelas com scroll horizontal em mobile
- Grids adaptáveis (2 colunas → 1 coluna em mobile)
- Filtros empilhados em telas pequenas

### Ícones

- Usa Bootstrap Icons (padrão do projeto)
- Ícones: `bi-people`, `bi-person`, `bi-receipt`, `bi-geo-alt`, `bi-graph-up`

---

## 🔗 Integrações

### Admin de Pedidos

- Link "Ver pedido" na lista de pedidos do cliente
- Redireciona para `/admin/pedidos/{id}`
- Reutiliza a tela de detalhes de pedido já existente

### Área do Cliente (Frontend)

- Não altera a área do cliente (frontend)
- Mantém separação entre admin e área do cliente
- Dados compartilhados via banco de dados

---

## 📊 Estatísticas Calculadas

### Total de Pedidos
- Contagem de todos os pedidos do cliente
- Query: `COUNT(*) FROM pedidos WHERE customer_id = :id AND tenant_id = :tenant_id`

### Valor Total Gasto
- Soma de todos os valores de pedidos
- Query: `SUM(total_geral) FROM pedidos WHERE customer_id = :id AND tenant_id = :tenant_id`
- Formatação: R$ 1.234,56

### Data do Último Pedido
- Data do pedido mais recente
- Obtido do primeiro item da lista ordenada por `created_at DESC`

---

## 🐛 Troubleshooting

### Problema: Cliente não encontrado

**Causa:** Cliente não pertence ao tenant atual ou ID inválido.

**Solução:** Verificar se o `tenant_id` está correto. O sistema retorna 404 automaticamente.

### Problema: Busca não retorna resultados

**Causa:** Termo de busca não corresponde a nenhum cliente.

**Solução:** 
- Verificar se o termo está correto
- Tentar buscar por e-mail completo
- Verificar se o cliente pertence ao tenant atual

### Problema: Estatísticas zeradas

**Causa:** Cliente não tem pedidos ou pedidos não estão vinculados ao `customer_id`.

**Solução:** Verificar se os pedidos foram criados com `customer_id` preenchido (requer cliente logado no checkout).

---

## 📚 Referências

- **Controller:** `src/Http/Controllers/Admin/CustomerController.php`
- **Views:** `themes/default/admin/customers/`
- **Rotas:** `public/index.php`
- **Layout:** `themes/default/admin/layouts/store.php`
- **Tabelas:** `customers`, `customer_addresses`, `pedidos`

---

## 🚀 Melhorias Futuras (Opcionais)

### Edição de Dados do Cliente
- Métodos `edit($id)` e `update($id)` no controller
- View `edit-content.php` com formulário
- Validação e atualização de dados

### Exportação de Dados
- Exportar lista de clientes para CSV/Excel
- Incluir estatísticas na exportação

### Filtros Avançados
- Filtrar por total de pedidos (mínimo/máximo)
- Filtrar por valor total gasto
- Filtrar por status de pedido mais recente

### Ações em Massa
- Seleção múltipla de clientes
- Exportação em lote
- Envio de e-mail em massa (futuro)

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX
