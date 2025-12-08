# Fase 5: Admin Produtos - Edição + Mídia

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Modelagem de Dados](#modelagem-de-dados)
- [Funcionalidades](#funcionalidades)
- [Rotas](#rotas)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Como Usar](#como-usar)
- [Critérios de Aceite](#critérios-de-aceite)

---

## Visão Geral

A Fase 5 transforma a tela de visualização de produtos (`/admin/produtos/{id}`) em uma tela completa de edição, permitindo:

- **Edição de dados básicos** do produto (nome, preço, estoque, descrições, etc.)
- **Gestão de imagem de destaque** (upload ou seleção da galeria)
- **Gestão de galeria de imagens** (adicionar, remover, reordenar)
- **Gestão de vídeos** (adicionar links de YouTube, Vimeo ou MP4)

### Funcionalidades Implementadas

✅ **Edição de Produtos**
- Campos básicos editáveis (nome, slug, SKU, status, preços, estoque, descrições)
- Validação e salvamento com multi-tenant

✅ **Imagem de Destaque**
- Upload de nova imagem
- Seleção de imagem da galeria como destaque
- Sincronização automática com `produtos.imagem_principal`

✅ **Galeria de Imagens**
- Listagem de imagens existentes
- Upload múltiplo de novas imagens
- Remoção de imagens
- Reordenação (preparado para futura implementação)

✅ **Vídeos do Produto**
- Adicionar vídeos via URL (YouTube, Vimeo, MP4)
- Título opcional para cada vídeo
- Ativação/desativação
- Remoção de vídeos

---

## Modelagem de Dados

### Tabelas Existentes (Reaproveitadas)

#### `produtos`
- Campo `imagem_principal` - sempre sincronizado com a imagem `main` de `produto_imagens`

#### `produto_imagens`
- `tipo` ENUM('main', 'gallery')
- `ordem` INT
- **Padrão adotado:**
  - Imagem de destaque: `tipo = 'main'` e `ordem = 0`
  - Galeria: `tipo = 'gallery'` e `ordem >= 1`

### Nova Tabela

#### `produto_videos`
```sql
CREATE TABLE produto_videos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    produto_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NULL,
    url VARCHAR(255) NOT NULL,
    ordem INT UNSIGNED DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_produto_videos_tenant (tenant_id),
    INDEX idx_produto_videos_tenant_produto (tenant_id, produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Funcionalidades

### 1. Edição de Dados Básicos

**Campos editáveis:**
- Nome
- Slug (gerado automaticamente a partir do nome, se vazio)
- SKU
- Status (Ativo/Rascunho → mapeia para publish/draft)
- Preço Regular
- Preço Promocional
- Data Início Promoção
- Data Fim Promoção
- Quantidade em Estoque
- Status de Estoque (Em estoque/Sem estoque/Sob encomenda)
- Gerencia Estoque (Sim/Não)
- Permite Pedidos em Falta
- Descrição Curta
- Descrição Completa

### 2. Imagem de Destaque

**Funcionalidades:**
- Exibir thumbnail da imagem atual (ou placeholder)
- Upload de nova imagem via `<input type="file">`
- Opção de selecionar uma imagem da galeria como destaque

**Regras de negócio:**
- Se enviar arquivo novo:
  1. Salvar em `public/uploads/tenants/{tenant_id}/produtos/`
  2. Criar/atualizar registro em `produto_imagens` com `tipo = 'main'`, `ordem = 0`
  3. Atualizar `produtos.imagem_principal` com o caminho

- Se marcar imagem da galeria como destaque:
  1. Imagem escolhida vira `tipo = 'main'`, `ordem = 0`
  2. Antiga main vira `tipo = 'gallery'` com ordem no fim
  3. Atualizar `produtos.imagem_principal`

### 3. Galeria de Imagens

**Funcionalidades:**
- Listagem em grid (miniaturas) das imagens `tipo = 'gallery'`
- Para cada imagem:
  - Thumbnail
  - Ícone "remover" (marca para exclusão)
  - Campo oculto `ordem[]` (para reordenação futura)
- Campo `file` com `multiple` para adicionar novas imagens

**Processamento no POST:**
- Tratar `remove_imagens[]` (IDs a remover)
- Tratar arquivos enviados, criar linhas em `produto_imagens` com `tipo = 'gallery'` e ordem sequencial
- Atualizar colunas `ordem` conforme arrays recebidos

### 4. Vídeos do Produto

**Funcionalidades:**
- Lista de vídeos já cadastrados
- Para cada vídeo:
  - Campo "Título" (opcional)
  - Campo "URL do vídeo" (obrigatório)
  - Checkbox "Ativo"
  - Botão "Remover"
- Repeater para adicionar novos vídeos:
  - `novo_videos[n][titulo]`
  - `novo_videos[n][url]`

**Processamento no POST:**
- Atualizar registros existentes (por id)
- Criar novos vídeos quando URL não estiver vazio
- Remover os marcados

---

## Rotas

### Admin

```
GET  /admin/produtos/{id}        → Admin\ProductController@edit
POST /admin/produtos/{id}        → Admin\ProductController@update
```

**Nota:** A rota `GET /admin/produtos/{id}` anteriormente chamava `show()`, agora chama `edit()`. Se necessário manter a visualização, pode-se criar uma rota separada ou usar query parameter.

---

## Estrutura de Arquivos

### Novos Arquivos

```
database/migrations/033_create_produto_videos_table.php
themes/default/admin/products/edit-content.php
```

### Arquivos Modificados

```
public/index.php
src/Http/Controllers/Admin/ProductController.php
```

---

## Como Usar

### 1. Acessar Tela de Edição

Navegue para `/admin/produtos/{id}` onde `{id}` é o ID do produto.

### 2. Editar Dados Básicos

Preencha os campos na seção "Dados Gerais" e clique em "Salvar alterações".

### 3. Gerenciar Imagem de Destaque

**Opção A - Upload:**
1. Clique em "Escolher arquivo" no campo "Nova imagem de destaque"
2. Selecione a imagem
3. Clique em "Salvar alterações"

**Opção B - Selecionar da Galeria:**
1. Na seção "Galeria de Imagens", clique no ícone de estrela (⭐) na imagem desejada
2. Clique em "Salvar alterações"

### 4. Gerenciar Galeria

**Adicionar imagens:**
1. Na seção "Galeria de Imagens", clique em "Escolher arquivos"
2. Selecione múltiplas imagens
3. Clique em "Salvar alterações"

**Remover imagens:**
1. Clique no ícone de lixeira (🗑️) na imagem desejada
2. Clique em "Salvar alterações"

### 5. Gerenciar Vídeos

**Adicionar vídeo:**
1. Na seção "Vídeos do Produto", preencha "Título" (opcional) e "URL"
2. Clique em "Salvar alterações"

**Remover vídeo:**
1. Clique em "Remover" no vídeo desejado
2. Clique em "Salvar alterações"

---

## Critérios de Aceite

✅ **Edição de Produtos**
- [ ] Todos os campos básicos são editáveis e salvam corretamente
- [ ] Validação funciona (campos obrigatórios, tipos de dados)
- [ ] Multi-tenant respeitado (não é possível editar produto de outro tenant)

✅ **Imagem de Destaque**
- [ ] Upload de nova imagem funciona e salva corretamente
- [ ] Seleção de imagem da galeria como destaque funciona
- [ ] `produtos.imagem_principal` sempre sincronizado com imagem `main`
- [ ] Apenas uma imagem `main` por produto

✅ **Galeria de Imagens**
- [ ] Upload múltiplo funciona
- [ ] Remoção de imagens funciona
- [ ] Imagens são salvas em `public/uploads/tenants/{tenant_id}/produtos/`
- [ ] Registros em `produto_imagens` criados corretamente

✅ **Vídeos**
- [ ] Adicionar vídeos funciona
- [ ] Editar vídeos existentes funciona
- [ ] Remover vídeos funciona
- [ ] Validação de URL funciona

✅ **Interface**
- [ ] Layout consistente com o resto do admin
- [ ] Mensagens de sucesso/erro exibidas corretamente
- [ ] Formulário responsivo

---

## Notas Técnicas

### Upload de Arquivos

- Caminho base: `public/uploads/tenants/{tenant_id}/produtos/`
- Nome do arquivo: mantém nome original ou gera nome único se houver conflito
- Validação: apenas imagens (jpg, jpeg, png, gif, webp)

### Sincronização de Imagem Principal

Sempre que uma imagem é definida como `main`:
1. Atualizar `produto_imagens` (tipo e ordem)
2. Atualizar `produtos.imagem_principal` com o caminho completo

### Validação de URLs de Vídeo

Aceita:
- YouTube: `https://www.youtube.com/watch?v=...` ou `https://youtu.be/...`
- Vimeo: `https://vimeo.com/...`
- MP4 direto: `https://...mp4`

---

## Próximas Etapas (Futuro)

- Integração de vídeos na PDP (página de produto na loja)
- Reordenação drag-and-drop da galeria
- Preview de vídeos na galeria da loja
- Upload de vídeos próprios (além de links)


