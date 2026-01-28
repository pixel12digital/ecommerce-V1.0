# UX PROFISSIONAL — Produto Variável (Admin + Storefront)

**Data:** 2026-01-20  
**Status:** ✅ Implementado  
**Versão:** 1.0

---

## 📋 Sumário Executivo

Este documento descreve a experiência de usuário (UX) completa para produtos variáveis no sistema, tanto para o **admin** (cadastro/edição) quanto para o **storefront** (comprador). A implementação segue padrões de mercado (WooCommerce, Shopify, Magento) e oferece uma experiência profissional e intuitiva.

---

## 🎯 Objetivos

### Admin (Cadastro/Edição)
1. **Gerenciamento de Atributos:** Selecionar atributos globais e configurar quais termos serão usados
2. **Swatches Visuais:** Para atributos do tipo "cor" e "imagem", permitir configuração visual (hex picker, upload de swatch)
3. **Geração de Variações:** Gerar automaticamente todas as combinações possíveis
4. **Grade de Variações:** Editar variações em lote com filtros e ações em massa
5. **Imagens por Variação:** Associar imagem específica a cada variação
6. **Imagem por Cor (Opcional):** Facilitar configuração associando imagem do produto a uma cor específica

### Storefront (Comprador)
1. **Seletores Visuais:** Exibir swatches (cor/imagem) e pills (tamanho) em vez de dropdowns
2. **Troca de Imagem:** Atualizar imagem principal ao selecionar variação
3. **Bloqueio de Combinações:** Desabilitar opções inválidas dinamicamente
4. **Feedback Visual:** Mostrar "Indisponível" quando estoque = 0 e backorder = no
5. **Atualização Dinâmica:** Preço, estoque e botão "Adicionar" atualizam conforme seleção

---

## 🏗️ Arquitetura de Dados

### Tabelas Utilizadas

#### `atributos` (Global)
- `id`, `nome`, `slug`, `tipo` (select/color/image), `ordem`

#### `atributo_termos` (Global)
- `id`, `atributo_id`, `nome`, `slug`, `valor_cor` (hex), `imagem` (swatch), `ordem`

#### `produto_atributos` (Relação Produto ↔ Atributo)
- `id`, `produto_id`, `atributo_id`, `usado_para_variacao` (0/1), `ordem`

#### `produto_atributo_termos` (Termos Selecionados por Produto)
- `id`, `produto_id`, `atributo_id`, `atributo_termo_id`
- **Novo:** `imagem_produto` (VARCHAR 255) - Imagem do produto para esta cor (opcional)

#### `produto_variacoes` (Variações)
- `id`, `produto_id`, `signature`, `sku`, `preco_regular`, `preco_promocional`
- `gerencia_estoque`, `quantidade_estoque`, `status_estoque`, `backorder`
- **`imagem`** (VARCHAR 255) - Imagem específica da variação

#### `produto_variacao_atributos` (Atributos da Variação)
- `variacao_id`, `atributo_id`, `atributo_termo_id`

### Prioridade de Imagem (Storefront)

Ao exibir a imagem principal do produto variável:

1. **Imagem da Variação** (`produto_variacoes.imagem`) - se existir
2. **Imagem por Cor** (`produto_atributo_termos.imagem_produto`) - se variação tiver cor e imagem estiver configurada
3. **Imagem do Produto Pai** (`produto_imagens` tipo 'main')

---

## 👨‍💼 UX ADMIN — Cadastro de Produto Variável

### 1. Seção "Atributos do Produto"

**Localização:** Abaixo de "Dados Gerais", antes de "Variações"

**Interface:**

```
┌─────────────────────────────────────────────────────────────┐
│ Atributos do Produto                                         │
│                                                              │
│ Selecione os atributos que este produto usa e marque quais  │
│ serão usados para gerar variações.                          │
│                                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ☑ Cor (color)                                           │ │
│ │   ☑ Usar para gerar variações                           │ │
│ │                                                          │ │
│ │   Termos disponíveis:                                   │ │
│ │   ☑ Vermelho    [🟥]                                    │ │
│ │   ☑ Azul        [🟦]                                    │ │
│ │   ☑ Verde       [🟩]                                    │ │
│ │                                                          │ │
│ │   Para cada termo selecionado:                          │ │
│ │   • Vermelho:                                           │ │
│ │     Cor HEX: [#FF0000] [Color Picker]                  │ │
│ │     Swatch (imagem): [Upload] [Preview]                 │ │
│ │     Imagem do produto para esta cor: [Upload] [Preview] │ │
│ │                                                          │ │
│ │   • Azul:                                               │ │
│ │     Cor HEX: [#0000FF] [Color Picker]                  │ │
│ │     Swatch (imagem): [Upload] [Preview]                 │ │
│ │     Imagem do produto para esta cor: [Upload] [Preview] │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ ☑ Tamanho (select)                                      │ │
│ │   ☑ Usar para gerar variações                           │ │
│ │                                                          │ │
│ │   Termos disponíveis:                                   │ │
│ │   ☑ P                                                    │ │
│ │   ☑ M                                                    │ │
│ │   ☑ G                                                    │ │
│ │   ☑ GG                                                   │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ [Salvar Atributos]                                           │
└─────────────────────────────────────────────────────────────┘
```

**Comportamento:**

1. **Checkbox "Atributo":** Marca/desmarca o atributo para o produto
2. **Checkbox "Usar para gerar variações":** Só aparece se atributo estiver marcado
3. **Termos:** Checkboxes para selecionar quais termos do atributo serão usados
4. **Para atributo tipo "color":**
   - Campo HEX (com color picker)
   - Upload de swatch (imagem miniatura)
   - Upload de "Imagem do produto para esta cor" (opcional)
5. **Para atributo tipo "image":**
   - Upload de swatch (imagem miniatura)
   - Upload de "Imagem do produto para este termo" (opcional)
6. **Botão "Salvar Atributos":** Salva configuração sem gerar variações ainda

### 2. Seção "Variações"

**Localização:** Abaixo de "Atributos do Produto"

**Interface:**

```
┌─────────────────────────────────────────────────────────────┐
│ Variações                                                     │
│                                                              │
│ [Gerar Variações]                                            │
│ Gera automaticamente todas as combinações possíveis dos     │
│ atributos marcados para variação.                           │
│                                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Filtros:                                                 │ │
│ │ Cor: [Todas ▼]  Tamanho: [Todas ▼]                      │ │
│ │                                                          │ │
│ │ Ações em Lote:                                          │ │
│ │ [Selecionar Todas] [Desselecionar Todas]               │ │
│ │ Preço Regular: [____] [Aplicar]                        │ │
│ │ Preço Promo: [____] [Aplicar]                          │ │
│ │ Estoque: [____] [Aplicar]                              │ │
│ │ Backorder: [Sim/Não ▼] [Aplicar]                       │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ [☐] Cor | Tamanho | SKU | Preço | Promo | Estoque |    │ │
│ │     Backorder | Imagem | Status                         │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ [☐] Vermelho, P | [SKU-001] | [R$ 100,00] | [R$ 90,00] │ │
│ │     | [10] | [Sim ☑] | [Upload] [Preview] | [Publicado]│ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ [☐] Vermelho, M | [SKU-002] | [R$ 100,00] | [R$ 90,00] │ │
│ │     | [15] | [Sim ☑] | [Upload] [Preview] | [Publicado]│ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ [Salvar Variações]                                           │
└─────────────────────────────────────────────────────────────┘
```

**Comportamento:**

1. **Botão "Gerar Variações":**
   - Gera combinações cartesianas dos termos selecionados
   - Não duplica variações existentes (usa signature)
   - Mostra mensagem: "X variações criadas, Y ignoradas (já existiam)"

2. **Filtros:**
   - Dropdown por Cor e por Tamanho
   - Filtra a tabela em tempo real

3. **Ações em Lote:**
   - Selecionar/Desselecionar todas
   - Aplicar preço/estoque/backorder para variações selecionadas

4. **Grade de Variações:**
   - Colunas: Checkbox | Combinação | SKU | Preço Regular | Preço Promo | Estoque | Backorder | Imagem | Status
   - Edição inline
   - Upload de imagem por variação (com preview)

5. **Botão "Salvar Variações":**
   - Salva todas as alterações em lote
   - Validação: SKU único por tenant

---

## 🛒 UX STOREFRONT — Página do Produto Variável

### Interface Visual

```
┌─────────────────────────────────────────────────────────────┐
│ [Imagem Principal]                                           │
│                                                              │
│ Nome do Produto                                              │
│ R$ 100,00                                                    │
│                                                              │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Cor:                                                      │ │
│ │ [🟥 Vermelho] [🟦 Azul] [🟩 Verde]                       │ │
│ │                                                          │ │
│ │ Tamanho:                                                  │ │
│ │ [P] [M] [G] [GG]                                         │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                                              │
│ ✓ Em estoque (10 unidades disponíveis)                      │
│                                                              │
│ Quantidade: [1]                                              │
│ [Adicionar ao Carrinho]                                      │
└─────────────────────────────────────────────────────────────┘
```

### Comportamento Detalhado

#### 1. Renderização de Swatches

**Para atributo tipo "color":**
- Se `valor_cor` existe: Exibe bolinha colorida (hex)
- Se `imagem` (swatch) existe: Exibe miniatura (30x30px)
- Se ambos existem: Prioriza imagem, fallback para cor

**Para atributo tipo "image":**
- Exibe miniatura (30x30px) do termo

**Para atributo tipo "select":**
- Exibe pills/botões com texto

#### 2. Seleção e Bloqueio de Combinações

**Lógica:**
1. Usuário seleciona Cor = "Vermelho"
2. Sistema verifica quais variações têm Cor = "Vermelho"
3. Para cada variação encontrada, extrai os Tamanhos disponíveis
4. Desabilita Tamanhos que não aparecem em nenhuma variação com Cor = "Vermelho"
5. Se nenhuma variação com Cor = "Vermelho" tiver estoque > 0 e backorder = no, mostra "Indisponível"

**Exemplo:**
- Variações: Vermelho-P (estoque 10), Vermelho-M (estoque 0, backorder=no), Azul-G (estoque 5)
- Usuário seleciona "Vermelho"
- Sistema desabilita "G" e "GG" (não existem variações Vermelho-G ou Vermelho-GG)
- Sistema marca "M" como "Indisponível" (estoque 0, backorder=no)
- Sistema habilita "P" (estoque 10)

#### 3. Atualização de Imagem

**Ordem de prioridade:**
1. `produto_variacoes.imagem` (imagem da variação)
2. `produto_atributo_termos.imagem_produto` (imagem por cor, se variação tiver cor)
3. `produto_imagens` tipo 'main' (imagem do produto pai)

**Transição:** Fade out/in (300ms)

#### 4. Atualização de Preço

- Exibe preço da variação (promocional ou regular)
- Se variação não tem preço, herda do produto
- Atualiza em tempo real

#### 5. Atualização de Estoque

- Exibe status: "Em estoque (X unidades)" ou "Indisponível"
- Se `backorder = 'yes'`: Permite adicionar mesmo com estoque 0
- Se `backorder = 'no'` e estoque = 0: Bloqueia adicionar ao carrinho

#### 6. Botão "Adicionar ao Carrinho"

**Estados:**
- **Desabilitado:** Nenhuma variação selecionada ou variação indisponível
- **Habilitado:** Variação válida e comprável selecionada

**Validação:**
- Backend valida `variacao_id` obrigatório para produto variável
- Retorna erro amigável se não enviado

---

## 🔧 Decisões Técnicas

### 1. Estrutura de Dados

**Imagem por Cor:**
- Campo `imagem_produto` em `produto_atributo_termos`
- Permite associar imagem do produto a um termo específico (ex: Cor "Vermelho")
- Facilita configuração quando várias variações da mesma cor compartilham imagem

**Signature:**
- Formato: `atributo_id:termo_id|atributo_id:termo_id`
- Ordenado por `atributo_id` (garantia de consistência)
- Índice único: `(tenant_id, produto_id, signature)`

### 2. JavaScript (Storefront)

**Função `buildCurrentSignature()`:**
- Ordena por `atributo_id` (numérico, não string)
- Garante compatibilidade 100% com backend

**Função `updateUI()`:**
- Localiza variação por signature
- Atualiza: preço, estoque, max qty, botão, imagem
- Bloqueia combinações inválidas

**Função `getAvailableOptions()`:**
- Retorna termos disponíveis para cada atributo baseado na seleção atual
- Usado para desabilitar opções inválidas

### 3. Upload de Imagens

**Admin:**
- Swatch: 30x30px (thumbnail)
- Imagem por Cor: Tamanho original (redimensionado pelo sistema)
- Imagem por Variação: Tamanho original (redimensionado pelo sistema)

**Storefront:**
- Swatch: 30x30px
- Imagem Principal: Tamanho responsivo (max-width: 100%)

### 4. Validações

**Backend:**
- SKU único por tenant
- Signature única por produto
- `variacao_id` obrigatório para produto variável no add-to-cart

**Frontend:**
- Bloqueio de combinações inválidas
- Validação de estoque antes de habilitar botão
- Feedback visual imediato

---

## 📸 Telas (Descrição Textual)

### Admin — Edição de Produto Variável

**Tela 1: Seção Atributos**
- Lista de atributos disponíveis (Cor, Tamanho, etc.)
- Checkboxes para selecionar atributos
- Para cada atributo selecionado:
  - Checkbox "Usar para variação"
  - Lista de termos com checkboxes
  - Se tipo "color": Campo HEX + color picker + upload swatch + upload imagem produto
  - Se tipo "image": Upload swatch + upload imagem produto

**Tela 2: Seção Variações**
- Botão "Gerar Variações"
- Filtros (Cor, Tamanho)
- Ações em lote (preço, estoque, backorder)
- Tabela com colunas: Checkbox | Combinação | SKU | Preços | Estoque | Backorder | Imagem | Status
- Upload de imagem por linha (com preview)

### Storefront — Página do Produto

**Tela 1: Produto Variável (Estado Inicial)**
- Imagem principal do produto
- Nome e preço
- Swatches de Cor (bolinhas coloridas ou miniaturas)
- Pills de Tamanho (botões)
- Botão "Adicionar" desabilitado

**Tela 2: Produto Variável (Cor Selecionada)**
- Imagem principal (pode ter mudado se houver imagem por cor)
- Swatches: Cor selecionada destacada
- Pills: Tamanhos inválidos desabilitados, disponíveis habilitados
- Preço atualizado (se variação tiver preço diferente)
- Botão "Adicionar" habilitado (se variação válida)

**Tela 3: Produto Variável (Combinação Completa)**
- Imagem principal (prioridade: variação > cor > produto)
- Preço final exibido
- Estoque: "Em estoque (X unidades)" ou "Indisponível"
- Botão "Adicionar" habilitado/desabilitado conforme disponibilidade

---

## ✅ Checklist de Testes Manuais

### Admin

#### Preparação
- [ ] Criar atributo "Cor" (tipo: color) com termos: Vermelho, Azul, Verde
- [ ] Criar atributo "Tamanho" (tipo: select) com termos: P, M, G, GG
- [ ] Criar produto variável

#### Configuração de Atributos
- [ ] Selecionar atributos "Cor" e "Tamanho" no produto
- [ ] Marcar ambos como "Usado para variação"
- [ ] Selecionar termos: Vermelho, Azul (Cor) e P, M (Tamanho)
- [ ] Configurar hex para cada cor (ex: #FF0000, #0000FF) usando color picker
- [ ] Verificar: Campo de texto HEX sincroniza com color picker
- [ ] Upload de swatch para cada cor (opcional)
- [ ] Verificar: Preview do swatch aparece após upload
- [ ] Upload de "imagem do produto" para Cor "Vermelho" (opcional)
- [ ] Verificar: Preview da imagem do produto aparece após upload
- [ ] Salvar atributos
- [ ] Verificar: Dados salvos corretamente

#### Geração de Variações
- [ ] Clicar em "Gerar Variações"
- [ ] Verificar: 4 variações criadas (Vermelho-P, Vermelho-M, Azul-P, Azul-M)
- [ ] Verificar: Mensagem de sucesso exibida

#### Edição de Variações
- [ ] Editar variações em lote:
  - [ ] Filtrar por Cor = "Vermelho" (se implementado)
  - [ ] Selecionar todas
  - [ ] Aplicar Preço Regular = R$ 100,00
  - [ ] Aplicar Estoque = 10
  - [ ] Aplicar Backorder = "Sim"
- [ ] Upload de imagem para variação "Vermelho-P"
- [ ] Verificar: Preview da imagem aparece após upload
- [ ] Salvar variações
- [ ] Verificar: Variações salvas corretamente
- [ ] Verificar: Imagens das variações aparecem corretamente

### Storefront

#### Estado Inicial
- [ ] Acessar página do produto variável
- [ ] Verificar: Swatches de Cor exibidos (bolinhas coloridas ou miniaturas)
- [ ] Verificar: Pills de Tamanho exibidos (botões)
- [ ] Verificar: Botão "Adicionar" desabilitado
- [ ] Verificar: Mensagem "Selecione todas as opções" exibida

#### Seleção de Atributos
- [ ] Selecionar Cor = "Vermelho"
- [ ] Verificar: Swatch selecionado destacado (borda verde, checkmark)
- [ ] Verificar: Imagem principal muda (se houver imagem por cor)
- [ ] Verificar: Tamanhos "G" e "GG" desabilitados (não existem variações)
- [ ] Verificar: Tamanhos "P" e "M" habilitados
- [ ] Verificar: Preço atualizado (se variação tiver preço diferente)
- [ ] Selecionar Tamanho = "P"
- [ ] Verificar: Pill selecionado destacado (fundo verde, texto branco)
- [ ] Verificar: Imagem principal muda para imagem da variação (se houver)
- [ ] Verificar: Estoque exibido ("Em estoque (10 unidades)")
- [ ] Verificar: Botão "Adicionar" habilitado

#### Adicionar ao Carrinho
- [ ] Clicar em "Adicionar ao Carrinho"
- [ ] Verificar: Produto adicionado com `variacao_id` correto
- [ ] Verificar: Carrinho exibe informações da variação corretamente

#### Combinações Inválidas
- [ ] Selecionar Cor = "Vermelho"
- [ ] Tentar selecionar Tamanho = "G" (deve estar desabilitado)
- [ ] Verificar: Opção desabilitada não responde ao clique
- [ ] Verificar: Tooltip "Indisponível" aparece ao passar mouse

#### Variação Sem Estoque
- [ ] Selecionar Cor = "Vermelho", Tamanho = "M" (se estoque = 0, backorder=no)
- [ ] Verificar: Exibe "Indisponível"
- [ ] Verificar: Botão "Adicionar" desabilitado
- [ ] Verificar: Input de quantidade com max = 0

#### Variação com Backorder
- [ ] Selecionar variação com estoque = 0, backorder = "yes"
- [ ] Verificar: Exibe "Sob encomenda"
- [ ] Verificar: Permite adicionar ao carrinho
- [ ] Verificar: Input de quantidade sem max (permite qualquer quantidade)

#### Troca de Imagem
- [ ] Selecionar variação com imagem própria
- [ ] Verificar: Imagem principal muda para imagem da variação
- [ ] Verificar: Transição suave (fade)
- [ ] Selecionar variação sem imagem própria mas com imagem por cor
- [ ] Verificar: Imagem principal muda para imagem por cor
- [ ] Selecionar variação sem imagem própria nem por cor
- [ ] Verificar: Imagem principal volta para imagem do produto

#### Prioridade de Imagem
- [ ] Criar variação com imagem própria
- [ ] Configurar imagem por cor para o termo
- [ ] Verificar: Ao selecionar variação, exibe imagem da variação (prioridade 1)
- [ ] Remover imagem da variação
- [ ] Verificar: Ao selecionar variação, exibe imagem por cor (prioridade 2)
- [ ] Remover imagem por cor
- [ ] Verificar: Ao selecionar variação, exibe imagem do produto (prioridade 3)

---

## 🚀 Próximos Passos (Opcional)

1. **Galeria por Variação:** Permitir múltiplas imagens por variação
2. **Preview de Combinação:** Mostrar preview da variação antes de selecionar
3. **Comparação de Variações:** Tabela comparativa de variações
4. **Filtros Avançados:** Filtros por preço, estoque na grade admin
5. **Import/Export:** CSV de variações

---

**Fim do Documento**
