# Fase 3: Loja (Listagem + PDP)

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Objetivos](#objetivos)
- [Funcionalidades Implementadas](#funcionalidades-implementadas)
- [Arquitetura](#arquitetura)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Rotas](#rotas)
- [Controllers](#controllers)
- [Views](#views)
- [Como Usar](#como-usar)
- [Exemplos](#exemplos)
- [Critérios de Aceite](#critérios-de-aceite)
- [Troubleshooting](#troubleshooting)

---

## Visão Geral

A **Fase 3** transforma a loja pública em uma experiência completa de navegação e compra, implementando:

- ✅ **Listagem de produtos** com filtros avançados (busca, categoria, preço, ordenação)
- ✅ **Paginação** completa e funcional
- ✅ **Rota amigável** para categorias (`/categoria/{slug}`)
- ✅ **Página de produto (PDP)** completa com galeria, preços, estoque, descrição e produtos relacionados
- ✅ **Sistema de carrinho placeholder** preparado para a Fase 4

---

## Objetivos

### Objetivo Principal

Transformar `/produtos` em uma loja navegável e funcional, com:

1. **Listagem completa** com filtros e paginação
2. **Navegação por categoria** via URL amigável
3. **Página de produto finalizada** (PDP) com todas as informações
4. **Botões "Adicionar ao carrinho"** funcionais (placeholder)

### Objetivos Específicos

- ✅ Filtros funcionais (busca, categoria, faixa de preço, ordenação)
- ✅ Paginação mantendo filtros ativos
- ✅ URL amigável `/categoria/{slug}` reutilizando lógica de listagem
- ✅ PDP com galeria de imagens, preços formatados, estoque, descrição completa
- ✅ Produtos relacionados (mesma categoria)
- ✅ Carrinho placeholder que não quebra a experiência

---

## Funcionalidades Implementadas

### 1. Listagem de Produtos (`/produtos`)

#### Filtros Disponíveis

- **Busca**: Por nome ou SKU do produto
- **Categoria**: Dropdown com todas as categorias do tenant
- **Faixa de Preço**: Campos para preço mínimo e máximo
- **Ordenação**: 
  - Novidades (padrão)
  - Menor Preço
  - Maior Preço
  - Mais Vendidos (placeholder - ordena por data)

#### Paginação

- 12 produtos por página
- Navegação com números de página
- Mantém todos os filtros ao navegar entre páginas
- Mostra total de produtos encontrados

#### Layout

- **Desktop**: Sidebar de filtros fixa + grid de produtos
- **Mobile**: Botão para expandir filtros + grid responsivo

### 2. Página de Categoria (`/categoria/{slug}`)

- URL amigável para categorias
- Reutiliza a mesma lógica e view da listagem geral
- Filtra automaticamente produtos da categoria
- Permite filtros adicionais (busca, preço, ordenação)
- Breadcrumb completo

### 3. Página de Produto (PDP) (`/produto/{slug}`)

#### Galeria de Imagens

- Imagem principal grande
- Thumbnails clicáveis abaixo
- Troca de imagem principal ao clicar no thumbnail
- Placeholder quando não há imagens

#### Informações do Produto

- **Nome** e avaliação (placeholder)
- **Preços formatados**:
  - Se tem promoção: "de R$ X,XX por R$ Y,YY"
  - Se não tem: apenas preço regular
- **Status de estoque**:
  - Em estoque (com quantidade disponível)
  - Fora de estoque
- **Formulário "Adicionar ao carrinho"**:
  - Campo de quantidade
  - Botão desabilitado se fora de estoque

#### Abas de Informação

- **Descrição**: Texto completo do produto
- **Informações Adicionais**: SKU, peso, dimensões (se disponíveis)
- **Categorias**: Links para páginas de categoria

#### Produtos Relacionados

- Grid com até 6 produtos da mesma categoria
- Exclui o produto atual
- Cards com imagem, nome e preço
- Links para páginas de produto

### 4. Carrinho Placeholder (`/carrinho/adicionar`)

- Endpoint POST que valida produto
- Retorna mensagem informativa (não persiste dados)
- Suporta requisições AJAX e normais
- Preparado para implementação real na Fase 4

---

## Arquitetura

### Fluxo de Dados

```
Usuário → Router → ProductController → Database → View
```

### Componentes Principais

1. **ProductController**: Lógica de listagem, categoria e produto
2. **CartController**: Placeholder para adicionar ao carrinho
3. **Views**: Templates responsivos com filtros e paginação
4. **ThemeConfig**: Cores do tema para personalização

### Consultas SQL

#### Listagem com Filtros

```sql
SELECT DISTINCT p.*
FROM produtos p
[INNER JOIN produto_categorias pc ...]
[INNER JOIN categorias c ...]
WHERE p.tenant_id = :tenant_id
  AND p.status = 'publish'
  [AND (p.nome LIKE :q OR p.sku LIKE :q)]
  [AND c.slug = :categoria_slug]
  [AND COALESCE(p.preco_promocional, p.preco_regular) BETWEEN :min AND :max]
ORDER BY [ordenação]
LIMIT :limit OFFSET :offset
```

#### Produtos Relacionados

```sql
SELECT DISTINCT p.*
FROM produtos p
JOIN produto_categorias pc ON pc.produto_id = p.id
WHERE p.tenant_id = :tenant_id
  AND p.status = 'publish'
  AND pc.categoria_id = :categoria_id
  AND p.id <> :produto_id
ORDER BY p.data_criacao DESC
LIMIT 6
```

---

## Estrutura de Arquivos

### Arquivos Criados

```
src/Http/Controllers/Storefront/
├── CartController.php          # Controller placeholder do carrinho
```

### Arquivos Modificados

```
src/Http/Controllers/Storefront/
├── ProductController.php       # Melhorado com filtros, categoria e relacionados

themes/default/storefront/products/
├── index.php                   # Listagem completa com filtros
└── show.php                    # PDP completa com galeria e abas

public/
└── index.php                   # Rotas adicionadas
```

---

## Rotas

### Rotas Públicas - Loja

| Método | Rota | Controller | Método | Descrição |
|--------|------|------------|--------|-----------|
| GET | `/produtos` | `ProductController` | `index()` | Listagem geral de produtos |
| GET | `/categoria/{slug}` | `ProductController` | `category()` | Listagem por categoria |
| GET | `/produto/{slug}` | `ProductController` | `show()` | Página de produto (PDP) |
| POST | `/carrinho/adicionar` | `CartController` | `addPlaceholder()` | Adicionar ao carrinho (placeholder) |

### Parâmetros de Query String

#### `/produtos` e `/categoria/{slug}`

- `q`: Termo de busca (nome ou SKU)
- `categoria`: Slug da categoria (apenas em `/produtos`)
- `preco_min`: Preço mínimo
- `preco_max`: Preço máximo
- `ordenar`: `novidades`, `menor_preco`, `maior_preco`, `mais_vendidos`
- `page`: Número da página (padrão: 1)

**Exemplos:**

```
/produtos?q=camisa&ordenar=menor_preco&page=2
/categoria/bones?preco_min=50&preco_max=200
/produtos?categoria=acessorios&q=boné
```

---

## Controllers

### ProductController

#### `index(): void`

Listagem geral de produtos com filtros e paginação.

**Parâmetros recebidos (via GET):**
- `q`: Busca
- `categoria`: Slug da categoria
- `preco_min`, `preco_max`: Faixa de preço
- `ordenar`: Tipo de ordenação
- `page`: Página atual

**Dados passados para view:**
- `produtos`: Array de produtos com imagem principal
- `categoriasFiltro`: Lista de categorias para dropdown
- `categoriaAtual`: null (não é página de categoria)
- `filtrosAtuais`: Array com filtros aplicados
- `paginacao`: Dados de paginação
- `theme`: Cores do tema

#### `category(string $slugCategoria): void`

Listagem de produtos de uma categoria específica.

**Parâmetros:**
- `$slugCategoria`: Slug da categoria

**Comportamento:**
1. Busca categoria por slug
2. Retorna 404 se não encontrar
3. Chama `renderProductList()` com ID da categoria

**Dados passados para view:**
- Mesmos de `index()`, mas com `categoriaAtual` preenchido

#### `show(string $slug): void`

Página de detalhes do produto (PDP).

**Parâmetros:**
- `$slug`: Slug do produto

**Dados buscados:**
- Produto por slug
- Todas as imagens do produto
- Categorias associadas
- Produtos relacionados (mesma categoria, excluindo atual)

**Dados passados para view:**
- `produto`: Dados do produto
- `imagens`: Array de imagens
- `categorias`: Categorias do produto
- `produtosRelacionados`: Array de produtos relacionados
- `theme`: Cores do tema

#### `renderProductList(?int $categoriaId = null, ?array $categoriaAtual = null): void`

Método privado que centraliza a lógica de listagem.

**Parâmetros:**
- `$categoriaId`: ID da categoria (null para listagem geral)
- `$categoriaAtual`: Dados da categoria (null para listagem geral)

**Funcionalidades:**
- Monta query com filtros dinâmicos
- Aplica joins quando necessário (filtro por categoria)
- Calcula paginação
- Busca imagem principal de cada produto
- Carrega categorias para filtro

### CartController

#### `addPlaceholder(): void`

Endpoint placeholder para adicionar produto ao carrinho.

**Parâmetros recebidos (via POST):**
- `produto_id`: ID do produto
- `quantidade`: Quantidade (padrão: 1)

**Comportamento:**
1. Valida se produto existe e está publicado
2. Se AJAX: retorna JSON com mensagem
3. Se não-AJAX: redireciona com mensagem na query string

**Respostas:**

- **Sucesso (AJAX):**
  ```json
  {
    "status": "ok",
    "message": "Carrinho será implementado na próxima fase. Produto encontrado com sucesso."
  }
  ```

- **Sucesso (normal):**
  - Redireciona para página anterior com `?cart_message=...`

- **Erro:**
  - Retorna status 400/404 com mensagem de erro

---

## Views

### `themes/default/storefront/products/index.php`

View de listagem de produtos (usada tanto em `/produtos` quanto em `/categoria/{slug}`).

#### Estrutura

```
Header (simplificado)
  └── Logo + Busca

Breadcrumb
  └── Home > Loja > [Categoria]

Container
  ├── Sidebar de Filtros (desktop)
  │   ├── Busca
  │   ├── Categoria (dropdown)
  │   ├── Faixa de Preço
  │   ├── Ordenação
  │   └── Botões (Aplicar / Limpar)
  │
  └── Área de Produtos
      ├── Título + Contador
      ├── Ordenação rápida (select)
      ├── Grid de Produtos
      │   └── Card de Produto
      │       ├── Imagem
      │       ├── Nome
      │       ├── Preço
      │       └── Botões (Ver / Adicionar)
      └── Paginação
```

#### Variáveis Disponíveis

- `$produtos`: Array de produtos com `imagem_principal`
- `$categoriasFiltro`: Array de categorias
- `$categoriaAtual`: Array da categoria atual ou null
- `$filtrosAtuais`: Array com filtros aplicados
- `$paginacao`: Array com dados de paginação
- `$theme`: Array com cores do tema

#### Funcionalidades JavaScript

- `toggleFilters()`: Mostra/esconde filtros no mobile

### `themes/default/storefront/products/show.php`

View da página de produto (PDP).

#### Estrutura

```
Header (simplificado)
  └── Logo + Link Voltar

Breadcrumb
  └── Home > Loja > Categoria > Produto

Container
  ├── Detalhes do Produto
  │   ├── Galeria (esquerda)
  │   │   ├── Imagem Principal
  │   │   └── Thumbnails
  │   │
  │   └── Informações (direita)
  │       ├── Nome + Avaliação
  │       ├── Preço
  │       ├── Estoque
  │       └── Form Adicionar ao Carrinho
  │
  ├── Abas de Informação
  │   ├── Descrição
  │   ├── Informações Adicionais
  │   └── Categorias
  │
  └── Produtos Relacionados
      └── Grid de Produtos
```

#### Variáveis Disponíveis

- `$produto`: Array com dados do produto
- `$imagens`: Array de imagens do produto
- `$categorias`: Array de categorias do produto
- `$produtosRelacionados`: Array de produtos relacionados
- `$theme`: Array com cores do tema

#### Funcionalidades JavaScript

- `changeImage(imagePath, thumbnail)`: Troca imagem principal
- `showTab(tabName)`: Alterna entre abas

---

## Como Usar

### 1. Acessar Listagem de Produtos

```
GET /produtos
```

**Com filtros:**

```
GET /produtos?q=camisa&categoria=roupas&preco_min=50&preco_max=200&ordenar=menor_preco&page=1
```

### 2. Acessar Página de Categoria

```
GET /categoria/bones
```

**Com filtros adicionais:**

```
GET /categoria/bones?preco_min=30&ordenar=maior_preco
```

### 3. Acessar Página de Produto

```
GET /produto/camisa-polo-azul
```

### 4. Adicionar ao Carrinho (Placeholder)

**Via formulário:**

```html
<form method="POST" action="/carrinho/adicionar">
    <input type="hidden" name="produto_id" value="123">
    <input type="number" name="quantidade" value="1">
    <button type="submit">Adicionar ao Carrinho</button>
</form>
```

**Via AJAX:**

```javascript
fetch('/carrinho/adicionar', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: 'produto_id=123&quantidade=1'
})
.then(response => response.json())
.then(data => console.log(data));
```

---

## Exemplos

### Exemplo 1: Listagem com Busca

**URL:** `/produtos?q=camisa`

**Resultado:**
- Lista todos os produtos com "camisa" no nome ou SKU
- Mantém outros filtros disponíveis
- Paginação funcional

### Exemplo 2: Categoria com Filtro de Preço

**URL:** `/categoria/bones?preco_min=50&preco_max=150`

**Resultado:**
- Lista apenas produtos da categoria "bones"
- Filtra por preço entre R$ 50 e R$ 150
- Mostra breadcrumb: Home > Loja > Bones

### Exemplo 3: Produto com Produtos Relacionados

**URL:** `/produto/bone-nike-vermelho`

**Resultado:**
- Mostra detalhes completos do produto
- Galeria de imagens funcional
- Preços formatados (se houver promoção)
- Seção de produtos relacionados com outros produtos da mesma categoria

### Exemplo 4: Adicionar ao Carrinho

**Ação:** Clique em "Adicionar ao Carrinho" na PDP

**Resultado:**
- Valida produto
- Retorna mensagem: "Carrinho ainda será implementado na próxima fase. Produto X encontrado com sucesso."
- Redireciona de volta para a página do produto

---

## Critérios de Aceite

### ✅ Listagem de Produtos (`/produtos`)

- [x] Lista produtos do tenant atual com `status = 'publish'`
- [x] Filtro de busca funciona (nome ou SKU)
- [x] Filtro de categoria funciona (dropdown)
- [x] Filtro de faixa de preço funciona
- [x] Ordenação funciona (4 opções)
- [x] Paginação funciona (12 por página)
- [x] Mantém filtros ao navegar entre páginas
- [x] Cards mostram imagem, nome, preço
- [x] Links para detalhes funcionam
- [x] Botão "Adicionar" funciona (placeholder)

### ✅ Página de Categoria (`/categoria/{slug}`)

- [x] Mostra apenas produtos da categoria
- [x] Retorna 404 se categoria não existir
- [x] Usa mesma view de listagem
- [x] Breadcrumb mostra categoria atual
- [x] Permite filtros adicionais (busca, preço, ordenação)
- [x] Paginação funciona

### ✅ Página de Produto (`/produto/{slug}`)

- [x] Mostra detalhes completos do produto
- [x] Galeria de imagens funciona (thumbnails clicáveis)
- [x] Preços formatados corretamente (de/por se promoção)
- [x] Status de estoque visível
- [x] Formulário "Adicionar ao carrinho" funcional
- [x] Abas de informação funcionam
- [x] Descrição completa exibida
- [x] Informações adicionais (se disponíveis)
- [x] Categorias com links funcionais
- [x] Produtos relacionados exibidos (se houver)
- [x] Breadcrumb completo

### ✅ Carrinho Placeholder (`/carrinho/adicionar`)

- [x] Valida produto existe e está publicado
- [x] Retorna mensagem informativa
- [x] Suporta requisições AJAX
- [x] Suporta requisições normais
- [x] Não quebra a experiência do usuário

### ✅ Filtros e Tenant

- [x] Todos os filtros respeitam `tenant_id`
- [x] Apenas produtos com `status = 'publish'` são exibidos
- [x] Categorias filtradas por tenant
- [x] Produtos relacionados filtrados por tenant

---

## Troubleshooting

### Problema: Listagem vazia

**Causa:** Nenhum produto com `status = 'publish'` no tenant.

**Solução:**
1. Verificar produtos no admin: `/admin/produtos`
2. Garantir que produtos têm `status = 'publish'`
3. Verificar `tenant_id` dos produtos

### Problema: Filtros não funcionam

**Causa:** Query string não está sendo passada corretamente.

**Solução:**
1. Verificar se formulário tem `method="GET"`
2. Verificar se inputs têm `name` correto
3. Verificar se `action` do formulário está correto

### Problema: Imagens não aparecem

**Causa:** Caminho das imagens incorreto ou arquivo não existe.

**Solução:**
1. Verificar `caminho_arquivo` na tabela `produto_imagens`
2. Verificar se arquivo existe no servidor
3. Verificar `$basePath` na view

### Problema: Produtos relacionados vazios

**Causa:** Produto não tem categorias associadas ou não há outros produtos na mesma categoria.

**Solução:**
1. Verificar se produto tem categorias: `/admin/produtos/{id}`
2. Verificar se há outros produtos na mesma categoria
3. Verificar se outros produtos têm `status = 'publish'`

### Problema: Carrinho placeholder retorna erro

**Causa:** Produto não existe ou não está publicado.

**Solução:**
1. Verificar se `produto_id` está correto
2. Verificar se produto tem `status = 'publish'`
3. Verificar se produto pertence ao tenant correto

### Problema: Paginação não mantém filtros

**Causa:** Links de paginação não incluem query string.

**Solução:**
1. Verificar função `buildQuery()` na view
2. Garantir que todos os filtros são passados nos links
3. Verificar se `$filtrosAtuais` está sendo usado corretamente

---

## Próximos Passos (Fase 4)

A Fase 3 prepara o terreno para a **Fase 4: Carrinho + Checkout + Pedidos**, que implementará:

- ✅ Carrinho de compras real (persistência)
- ✅ Gerenciamento de itens (adicionar, remover, atualizar quantidade)
- ✅ Checkout completo
- ✅ Sistema de pedidos
- ✅ Painel admin de pedidos

---

## Resumo Técnico

### Tabelas Utilizadas

- `produtos`: Dados dos produtos
- `categorias`: Categorias
- `produto_categorias`: Relação produto-categoria
- `produto_imagens`: Imagens dos produtos
- `tenant_settings`: Configurações de tema (via ThemeConfig)

### Novos Arquivos

- `src/Http/Controllers/Storefront/CartController.php`

### Arquivos Modificados

- `src/Http/Controllers/Storefront/ProductController.php`
- `themes/default/storefront/products/index.php`
- `themes/default/storefront/products/show.php`
- `public/index.php`

### Rotas Adicionadas

- `GET /categoria/{slug}`
- `POST /carrinho/adicionar`

### Migrations Necessárias

**Nenhuma.** A Fase 3 utiliza apenas tabelas existentes.

---

**Versão:** 1.0  
**Data:** 2025-01-XX  
**Status:** ✅ Concluída


