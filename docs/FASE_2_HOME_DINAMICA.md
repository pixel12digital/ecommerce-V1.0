# Fase 2: Home Dinâmica (Categorias + Banners + Newsletter)

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Tabelas do Banco de Dados](#tabelas-do-banco-de-dados)
- [Funcionalidades](#funcionalidades)
- [Como Usar](#como-usar)
- [Rotas](#rotas)
- [Exemplos de Uso](#exemplos-de-uso)
- [Critérios de Aceite](#critérios-de-aceite)

---

## Visão Geral

A Fase 2 transforma a home em uma página 100% configurável via painel administrativo, permitindo que cada loja configure dinamicamente:

- **Bolotas de Categorias**: Categorias exibidas na faixa horizontal abaixo do header
- **Seções de Produtos**: 4 seções configuráveis com produtos por categoria
- **Banners**: Hero banners (slider) e banners retrato configuráveis
- **Newsletter**: Sistema funcional de inscrições com listagem no admin

### Funcionalidades Implementadas

✅ **Faixa de Categorias Dinâmica**
- Configuração de quais categorias aparecem nas bolotas
- Ícones personalizados por categoria
- Labels customizados
- Ordem configurável

✅ **Seções de Produtos por Categoria**
- 4 seções configuráveis (linha_1, linha_2, linha_3, linha_4)
- Título e subtítulo por seção
- Seleção de categoria
- Quantidade de produtos configurável
- Link "Ver tudo" automático

✅ **Gestão de Banners**
- Banners hero (slider principal)
- Banners retrato (laterais)
- Título, subtítulo, CTA configuráveis
- Imagens desktop e mobile
- Ordem e ativação

✅ **Sistema de Newsletter**
- Formulário funcional na home
- Salvamento no banco de dados
- Listagem no admin com busca
- Validação de e-mail
- Prevenção de duplicatas

---

## Arquitetura

### Fluxo de Dados

```
Store Admin (/admin/home/*)
    ↓
Controllers Admin (HomeCategoriesController, HomeSectionsController, HomeBannersController)
    ↓
Banco de Dados (home_category_pills, home_category_sections, banners)
    ↓
HomeController@index()
    ↓
View storefront/home.php (renderiza dados dinâmicos)
```

### Componentes Principais

1. **HomeCategoriesController** (`src/Http/Controllers/Admin/HomeCategoriesController.php`)
   - Gerencia bolotas de categorias
   - CRUD completo (create, read, update, delete)

2. **HomeSectionsController** (`src/Http/Controllers/Admin/HomeSectionsController.php`)
   - Gerencia seções de produtos por categoria
   - Cria seções padrão automaticamente

3. **HomeBannersController** (`src/Http/Controllers/Admin/HomeBannersController.php`)
   - Gerencia banners (hero + retrato)
   - CRUD completo com filtros por tipo

4. **NewsletterController (Admin)** (`src/Http/Controllers/Admin/NewsletterController.php`)
   - Lista inscrições de newsletter
   - Busca por nome/e-mail

5. **NewsletterController (Storefront)** (`src/Http/Controllers/Storefront/NewsletterController.php`)
   - Processa inscrições do formulário
   - Validação e prevenção de duplicatas

6. **HomeController (Atualizado)** (`src/Http/Controllers/Storefront/HomeController.php`)
   - Carrega todos os dados dinâmicos
   - Busca produtos por categoria para cada seção
   - Busca banners ativos

---

## Estrutura de Arquivos

```
ecommerce-v1.0/
├── database/
│   └── migrations/
│       ├── 027_create_home_category_pills_table.php
│       ├── 028_create_home_category_sections_table.php
│       ├── 029_create_banners_table.php
│       └── 030_create_newsletter_inscricoes_table.php
│
├── src/
│   └── Http/
│       └── Controllers/
│           ├── Admin/
│           │   ├── HomeCategoriesController.php
│           │   ├── HomeSectionsController.php
│           │   ├── HomeBannersController.php
│           │   └── NewsletterController.php
│           └── Storefront/
│               ├── HomeController.php (atualizado)
│               └── NewsletterController.php
│
├── themes/
│   └── default/
│       ├── admin/
│       │   ├── home/
│       │   │   ├── categories-pills.php
│       │   │   ├── categories-pills-edit.php
│       │   │   ├── sections-categories.php
│       │   │   ├── banners.php
│       │   │   └── banners-form.php
│       │   └── newsletter/
│       │       └── index.php
│       └── storefront/
│           └── home.php (atualizado)
│
└── public/
    └── index.php (rotas atualizadas)
```

---

## Tabelas do Banco de Dados

### 1. `home_category_pills`

Armazena as bolotas de categorias exibidas na faixa horizontal.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `tenant_id` | BIGINT UNSIGNED | ID do tenant |
| `categoria_id` | BIGINT UNSIGNED | ID da categoria (FK lógica) |
| `label` | VARCHAR(100) | Label customizado (opcional) |
| `icone_path` | VARCHAR(255) | Caminho do ícone (opcional) |
| `ordem` | INT UNSIGNED | Ordem de exibição |
| `ativo` | TINYINT(1) | Se está ativo (1) ou inativo (0) |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Data de atualização |

**Índices:**
- INDEX (`tenant_id`)
- INDEX (`tenant_id`, `ordem`)

### 2. `home_category_sections`

Armazena as seções de produtos por categoria.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `tenant_id` | BIGINT UNSIGNED | ID do tenant |
| `slug_secao` | VARCHAR(50) | Slug da seção (linha_1, linha_2, etc.) |
| `titulo` | VARCHAR(150) | Título da seção |
| `subtitulo` | VARCHAR(255) | Subtítulo (opcional) |
| `categoria_id` | BIGINT UNSIGNED | ID da categoria |
| `quantidade_produtos` | INT UNSIGNED | Quantidade de produtos a exibir |
| `ordem` | INT UNSIGNED | Ordem de exibição |
| `ativo` | TINYINT(1) | Se está ativo (1) ou inativo (0) |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Data de atualização |

**Índices:**
- INDEX (`tenant_id`)
- INDEX (`tenant_id`, `slug_secao`)

### 3. `banners`

Armazena banners hero e retrato.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `tenant_id` | BIGINT UNSIGNED | ID do tenant |
| `tipo` | ENUM('hero', 'portrait') | Tipo do banner |
| `titulo` | VARCHAR(150) | Título do banner |
| `subtitulo` | VARCHAR(255) | Subtítulo (opcional) |
| `cta_label` | VARCHAR(50) | Label do botão CTA |
| `cta_url` | VARCHAR(255) | URL do botão CTA |
| `imagem_desktop` | VARCHAR(255) | Caminho da imagem desktop |
| `imagem_mobile` | VARCHAR(255) | Caminho da imagem mobile (opcional) |
| `ordem` | INT UNSIGNED | Ordem de exibição |
| `ativo` | TINYINT(1) | Se está ativo (1) ou inativo (0) |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Data de atualização |

**Índices:**
- INDEX (`tenant_id`, `tipo`, `ativo`)

### 4. `newsletter_inscricoes`

Armazena inscrições de newsletter.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `tenant_id` | BIGINT UNSIGNED | ID do tenant |
| `nome` | VARCHAR(150) | Nome do inscrito (opcional) |
| `email` | VARCHAR(150) | E-mail do inscrito |
| `origem` | VARCHAR(50) | Origem da inscrição (ex: 'home') |
| `created_at` | DATETIME | Data de inscrição |

**Índices:**
- INDEX (`tenant_id`, `email`)

---

## Funcionalidades

### 1. Faixa de Categorias (Bolotas)

**Admin: `/admin/home/categorias-pills`**

- **Listar**: Visualiza todas as bolotas configuradas
- **Adicionar**: 
  - Seleciona categoria
  - Define label customizado (opcional)
  - Define caminho do ícone (opcional)
  - Define ordem
  - Ativa/desativa
- **Editar**: Modifica bolota existente
- **Excluir**: Remove bolota

**Frontend:**
- Exibe apenas bolotas ativas
- Ordena por `ordem` ASC
- Cada bolota linka para `/produtos?categoria={slug}`
- Se tiver ícone, exibe junto com o label

### 2. Seções de Produtos por Categoria

**Admin: `/admin/home/secoes-categorias`**

- **Configurar 4 seções** (linha_1, linha_2, linha_3, linha_4):
  - Título (obrigatório)
  - Subtítulo (opcional)
  - Categoria (dropdown)
  - Quantidade de produtos (1-20)
  - Ativo/Inativo

**Frontend:**
- Para cada seção ativa:
  - Busca produtos da categoria selecionada
  - Limita pela quantidade configurada
  - Exibe cards de produtos
  - Link "Ver tudo" aponta para `/produtos?categoria={slug}`

### 3. Banners

**Admin: `/admin/home/banners`**

- **Listar**: Visualiza todos os banners (com filtro por tipo)
- **Criar/Editar**:
  - Tipo (hero ou portrait)
  - Título e subtítulo
  - CTA (label + URL)
  - Imagem desktop (obrigatória)
  - Imagem mobile (opcional)
  - Ordem
  - Ativo/Inativo
- **Excluir**: Remove banner

**Frontend:**
- **Hero banners**: Exibidos no slider principal
- **Portrait banners**: Exibidos na seção de banners retrato
- Ordenados por `ordem` ASC
- Apenas banners ativos são exibidos

### 4. Newsletter

**Frontend:**
- Formulário na seção newsletter da home
- Campos: nome (opcional) e e-mail (obrigatório)
- Validação de e-mail
- Prevenção de duplicatas
- Mensagens de feedback (sucesso, erro, já cadastrado)

**Admin: `/admin/newsletter`**
- Lista todas as inscrições do tenant
- Busca por nome ou e-mail
- Exibe data de inscrição e origem

---

## Como Usar

### 1. Configurar Bolotas de Categorias

1. Acesse: `/admin/home/categorias-pills`
2. Clique em "Adicionar Nova Bolota"
3. Preencha:
   - Selecione a categoria
   - (Opcional) Defina um label customizado
   - (Opcional) Defina caminho do ícone
   - Defina a ordem
   - Marque "Ativo"
4. Salve
5. A bolota aparecerá na faixa de categorias da home

### 2. Configurar Seções de Produtos

1. Acesse: `/admin/home/secoes-categorias`
2. Para cada seção (linha_1 a linha_4):
   - Defina o título
   - (Opcional) Defina subtítulo
   - Selecione a categoria
   - Defina quantidade de produtos (padrão: 8)
   - Marque "Ativo" se quiser exibir
3. Clique em "Salvar Todas as Seções"
4. As seções aparecerão na home com produtos da categoria selecionada

### 3. Criar Banners

#### Hero Banner:
1. Acesse: `/admin/home/banners`
2. Clique em "+ Novo Banner"
3. Selecione tipo "Hero"
4. Preencha:
   - Título e subtítulo
   - CTA (label e URL)
   - Caminho da imagem desktop
   - (Opcional) Caminho da imagem mobile
   - Ordem
   - Marque "Ativo"
5. Salve
6. O banner aparecerá no slider hero da home

#### Banner Retrato:
1. Mesmo processo, mas selecione tipo "Portrait"
2. O banner aparecerá na seção de banners retrato

### 4. Gerenciar Newsletter

**Ver inscrições:**
1. Acesse: `/admin/newsletter`
2. Visualize todas as inscrições
3. Use a busca para filtrar por nome ou e-mail

**Testar formulário:**
1. Acesse a home: `/`
2. Role até a seção newsletter
3. Preencha nome e e-mail
4. Clique em "Cadastrar"
5. Verifique a mensagem de sucesso
6. Confira no admin se a inscrição foi salva

---

## Rotas

### Admin

#### Bolotas de Categorias
| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/admin/home/categorias-pills` | `HomeCategoriesController@index` | Lista bolotas |
| POST | `/admin/home/categorias-pills` | `HomeCategoriesController@store` | Cria bolota |
| GET | `/admin/home/categorias-pills/{id}/editar` | `HomeCategoriesController@edit` | Formulário edição |
| POST | `/admin/home/categorias-pills/{id}` | `HomeCategoriesController@update` | Atualiza bolota |
| POST | `/admin/home/categorias-pills/{id}/excluir` | `HomeCategoriesController@destroy` | Exclui bolota |

#### Seções de Categorias
| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/admin/home/secoes-categorias` | `HomeSectionsController@index` | Lista/Configura seções |
| POST | `/admin/home/secoes-categorias` | `HomeSectionsController@update` | Salva configurações |

#### Banners
| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/admin/home/banners` | `HomeBannersController@index` | Lista banners |
| GET | `/admin/home/banners/novo` | `HomeBannersController@create` | Formulário novo |
| POST | `/admin/home/banners/novo` | `HomeBannersController@store` | Cria banner |
| GET | `/admin/home/banners/{id}/editar` | `HomeBannersController@edit` | Formulário edição |
| POST | `/admin/home/banners/{id}` | `HomeBannersController@update` | Atualiza banner |
| POST | `/admin/home/banners/{id}/excluir` | `HomeBannersController@destroy` | Exclui banner |

#### Newsletter
| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/admin/newsletter` | `NewsletterController@index` | Lista inscrições |

### Públicas

| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| POST | `/newsletter/inscrever` | `NewsletterController@store` | Processa inscrição |

---

## Exemplos de Uso

### Exemplo 1: Adicionar Bolota de Categoria

```php
// No admin, via formulário:
// - Categoria: "Bonés" (ID: 5)
// - Label: "Bonés e Chapéus"
// - Ícone: "/images/icons/bone.png"
// - Ordem: 1
// - Ativo: Sim

// Resultado na home:
// Bolota aparece na faixa com ícone e label customizado
// Link: /produtos?categoria=bones
```

### Exemplo 2: Configurar Seção de Produtos

```php
// No admin, configurar linha_1:
// - Título: "Bonés, Viseiras e Chapéus"
// - Subtítulo: "Os melhores acessórios para sua cabeça"
// - Categoria: "Bonés" (ID: 5)
// - Quantidade: 6
// - Ativo: Sim

// Resultado na home:
// Seção exibe 6 produtos da categoria "Bonés"
// Link "Ver tudo" aponta para /produtos?categoria=bones
```

### Exemplo 3: Criar Banner Hero

```php
// No admin, criar banner:
// - Tipo: Hero
// - Título: "Promoção de Verão"
// - Subtítulo: "Até 50% OFF em produtos selecionados"
// - CTA Label: "Ver Ofertas"
// - CTA URL: "/produtos?promocao=verao"
// - Imagem Desktop: "/images/banners/hero-verao.jpg"
// - Ordem: 1
// - Ativo: Sim

// Resultado na home:
// Banner aparece no slider hero com título, subtítulo e botão
```

### Exemplo 4: Inscrição Newsletter

```php
// Usuário preenche formulário na home:
// - Nome: "João Silva"
// - E-mail: "joao@example.com"

// Sistema:
// 1. Valida e-mail
// 2. Verifica se já existe
// 3. Insere na tabela newsletter_inscricoes
// 4. Redireciona com mensagem de sucesso

// Admin pode ver em /admin/newsletter
```

---

## Critérios de Aceite

### ✅ Faixa de Categorias

- [x] Consigo configurar quais categorias aparecem nas bolotas
- [x] Posso definir label customizado
- [x] Posso definir ícone por bolota
- [x] Posso ordenar as bolotas
- [x] Posso ativar/desativar bolotas
- [x] A home exibe apenas bolotas ativas
- [x] Cada bolota linka para a categoria correta

### ✅ Seções de Produtos

- [x] Consigo configurar 4 seções (linha_1 a linha_4)
- [x] Posso definir título e subtítulo por seção
- [x] Posso selecionar categoria por seção
- [x] Posso definir quantidade de produtos
- [x] Posso ativar/desativar seções
- [x] A home exibe produtos reais das categorias
- [x] Link "Ver tudo" funciona corretamente

### ✅ Banners

- [x] Consigo criar banners hero
- [x] Consigo criar banners retrato
- [x] Posso definir título, subtítulo e CTA
- [x] Posso definir imagens desktop e mobile
- [x] Posso ordenar banners
- [x] Posso ativar/desativar banners
- [x] A home exibe banners hero no slider
- [x] A home exibe banners retrato na seção correta

### ✅ Newsletter

- [x] Formulário funciona na home
- [x] Validação de e-mail funciona
- [x] Prevenção de duplicatas funciona
- [x] Inscrições são salvas no banco
- [x] Admin pode ver lista de inscrições
- [x] Busca por nome/e-mail funciona
- [x] Mensagens de feedback aparecem corretamente

---

## Troubleshooting

### Problema: Bolotas não aparecem na home

**Solução:**
1. Verifique se há bolotas configuradas em `/admin/home/categorias-pills`
2. Verifique se estão marcadas como "Ativo"
3. Verifique se as categorias existem e têm slug válido
4. Limpe cache do navegador

### Problema: Seções não exibem produtos

**Solução:**
1. Verifique se a seção está ativa
2. Verifique se a categoria foi selecionada
3. Verifique se há produtos publicados nessa categoria
4. Verifique se os produtos têm `status = 'publish'`
5. Verifique se a relação `produto_categorias` está correta

### Problema: Banners não aparecem

**Solução:**
1. Verifique se os banners estão marcados como "Ativo"
2. Verifique se o caminho da imagem está correto
3. Verifique se o tipo está correto (hero ou portrait)
4. Verifique a ordem (banners são ordenados por `ordem` ASC)

### Problema: Newsletter não salva

**Solução:**
1. Verifique se o e-mail é válido
2. Verifique se não é duplicata (sistema previne)
3. Verifique logs de erro do PHP
4. Verifique permissões de escrita no banco
5. Verifique se a tabela `newsletter_inscricoes` existe

### Problema: Erro ao acessar telas admin

**Solução:**
1. Verifique se está autenticado no Store Admin
2. Verifique se as rotas estão corretas em `public/index.php`
3. Verifique se os controllers existem
4. Verifique logs de erro do PHP

---

## Notas Técnicas

### Performance

- Queries otimizadas com índices adequados
- Produtos são buscados apenas para seções ativas
- Banners são filtrados por tipo e status
- Cache pode ser implementado futuramente

### Segurança

- Todas as queries filtram por `tenant_id`
- Validação de dados nos controllers
- Prevenção de SQL injection via prepared statements
- Validação de e-mail no formulário de newsletter
- Prevenção de duplicatas de e-mail

### Multi-tenant

- Todas as tabelas têm `tenant_id`
- Todas as queries filtram por tenant atual
- Isolamento completo de dados entre tenants

---

## Próximas Melhorias (Futuro)

### Não Implementado (Fase 2)

- ❌ Upload real de imagens (atualmente apenas caminho de arquivo)
- ❌ Preview de banners antes de salvar
- ❌ Drag-and-drop para reordenar bolotas/seções
- ❌ Export CSV de inscrições newsletter
- ❌ Envio de e-mails de confirmação de newsletter
- ❌ Estatísticas de newsletter (taxa de conversão, etc.)
- ❌ Slider automático para hero banners (atualmente exibe apenas o primeiro)
- ❌ Responsividade avançada para banners mobile

Essas funcionalidades podem ser implementadas em fases futuras.

---

## Changelog

### Fase 2.0 (2025-01-XX)

- ✅ Implementação de bolotas de categorias dinâmicas
- ✅ Implementação de seções de produtos por categoria
- ✅ Implementação de gestão de banners (hero + retrato)
- ✅ Implementação de sistema de newsletter
- ✅ Atualização do HomeController para carregar dados dinâmicos
- ✅ Atualização da view home.php para usar dados dinâmicos
- ✅ Criação de 4 novas tabelas no banco
- ✅ Criação de 5 controllers (4 admin + 1 storefront)
- ✅ Criação de 6 views admin
- ✅ Documentação completa

---

**Documento criado em:** 2025-01-XX  
**Última atualização:** 2025-01-XX  
**Versão:** 2.0
