# AJUSTE DE UX — Variações Realmente Funcionais (Admin)

**Data:** 2026-01-20  
**Status:** ✅ Implementado  
**Versão:** 1.0

---

## 📋 Sumário Executivo

Este documento descreve os ajustes de UX implementados para tornar o fluxo de variações de produto completamente funcional no admin, resolvendo problemas de fluxo e clareza na interface.

---

## 🎯 Problemas Identificados

### 1. Gerenciamento de Termos
- **Problema:** Não havia interface clara para cadastrar TERMOS (valores) dos atributos (ex: cores, tamanhos)
- **Impacto:** Admin não conseguia criar valores para os atributos de forma intuitiva

### 2. Fluxo do Novo Produto
- **Problema:** Em `/admin/produtos/novo`, mesmo escolhendo "Produto Variável", não apareciam as seções Atributos/Variações
- **Impacto:** Admin precisava salvar primeiro e depois editar, sem orientação clara

### 3. Estoque do Produto Pai
- **Problema:** Não estava claro que estoque do produto variável é controlado por variação
- **Impacto:** Confusão sobre onde configurar estoque

---

## 🔧 Soluções Implementadas

### A) Gerenciamento de Termos do Atributo

#### Interface
- **Localização:** `/admin/atributos/{id}/editar`
- **Seção:** "Termos do Atributo" (aba ou bloco dedicado)

#### Funcionalidades
1. **Listar Termos:**
   - Tabela com: Nome, Slug, Ordem, Ações
   - Para tipo "color": exibe hex e preview
   - Para tipo "image": exibe miniatura

2. **Criar Termo:**
   - Formulário inline ou modal
   - Campos obrigatórios: Nome
   - Campos opcionais: Slug (auto-gerado), Ordem
   - Campos por tipo:
     - **dropdown:** Nome, Slug, Ordem
     - **color:** Nome, Slug, Hex (color picker), Swatch (upload), Ordem
     - **image:** Nome, Slug, Swatch (upload obrigatório), Ordem

3. **Editar Termo:**
   - Formulário inline ou modal
   - Mesmos campos do criar

4. **Remover Termo:**
   - Botão de exclusão
   - Validação: verificar se termo está em uso
   - Aviso se estiver em uso (não permite excluir ou permite com aviso)

#### Fluxo Recomendado
1. Criar atributo "Cor" (tipo: color)
2. Editar atributo → Seção "Termos"
3. Adicionar termos: Vermelho (#FF0000), Azul (#0000FF), Verde (#00FF00)
4. Para cada cor, opcionalmente:
   - Upload de swatch (miniatura)
   - Upload de imagem do produto (para trocar na loja)

---

### B) Fluxo do Novo Produto Variável

#### Interface no "Novo Produto"
- **Quando Tipo = "Produto Variável":**
  - Exibe aviso informativo:
    > "Produtos variáveis: depois de salvar, você poderá escolher Cor/Tamanho e gerar variações."
  - Exibe botão adicional:
    > "Salvar e configurar variações"
  - Botão padrão "Salvar" continua disponível

#### Comportamento
1. **Se clicar "Salvar e configurar variações":**
   - Produto é criado normalmente
   - Redireciona para `/admin/produtos/{id}/editar#atributos`
   - Scroll automático para seção de atributos

2. **Se clicar "Salvar" (padrão):**
   - Produto é criado normalmente
   - Redireciona para lista de produtos

#### Implementação Técnica
- Flag `go_variations` no POST
- `ProductController::store()` verifica flag
- Redirecionamento com âncora `#atributos`

---

### C) Experiência no Produto Variável (Edição)

#### Seção "Atributos do Produto"
1. **Dropdown "Adicionar atributo":**
   - Lista todos os atributos globais disponíveis
   - Ao selecionar, adiciona à lista de atributos do produto

2. **Para cada atributo adicionado:**
   - Checkbox: "Usado para variação"
   - Lista de termos do atributo (checkboxes)
   - Busca/filtro de termos (se muitos)
   - Preview de swatch/imagem (se cor/imagem)
   - Link para cadastrar termos (se não houver termos)

3. **Avisos:**
   - Se atributo não tem termos:
     > "Nenhum termo cadastrado. [Cadastrar termos deste atributo](link)"
   - Link abre `/admin/atributos/{id}/editar` em nova aba

4. **Botões:**
   - "Salvar atributos" (salva configuração sem gerar variações)
   - "Gerar variações" (gera combinações cartesianas)

#### Seção "Estoque"
- **Para produto variável:**
  - Campo de estoque desabilitado ou oculto
  - Mensagem: "Estoque é controlado por variação. Configure o estoque de cada variação abaixo."
  - Ou: campo visível mas com aviso claro

**Observação Técnica - Estoque do Produto Pai:**
- Em produtos variáveis, o estoque do produto pai é sempre `0` e `gerencia_estoque = 0`
- O sistema força esses valores no backend ao criar/editar produto variável
- A UI desabilita os campos de estoque quando `tipo = variable` para evitar confusão
- O estoque real é gerenciado exclusivamente na grade de variações
- Isso evita ambiguidade: o admin não precisa decidir se preenche estoque no pai ou nas variações

#### Seção "Variações"
- Grade de variações (já existente)
- Colunas: Combinação | SKU | Preço | Estoque | Backorder | Imagem | Status
- Edição inline
- Upload de imagem por variação

---

### D) Storefront — Troca de Imagem ao Selecionar Cor

#### Comportamento
1. **Ao selecionar apenas COR (sem tamanho):**
   - Troca imagem principal para `imagem_produto` do termo (se existir)
   - Fallback: imagem do produto pai

2. **Ao selecionar COR + TAMANHO (variação completa):**
   - Prioridade: imagem da variação > imagem por cor > imagem do produto
   - Troca imagem principal

3. **Botão "Adicionar ao Carrinho":**
   - Só habilita quando `variacao_id` válido e comprável
   - Validação: estoque > 0 OU backorder = 'yes'

---

## 📊 Fluxo Completo Recomendado

### Passo 1: Criar Atributos
1. Ir em `/admin/atributos/novo`
2. Criar atributo "Cor" (tipo: color)
3. Criar atributo "Tamanho" (tipo: select)

### Passo 2: Cadastrar Termos
1. Editar atributo "Cor"
2. Na seção "Termos", adicionar:
   - Vermelho (#FF0000)
   - Azul (#0000FF)
   - Verde (#00FF00)
3. Para cada cor, opcionalmente:
   - Upload de swatch
   - Upload de imagem do produto
4. Editar atributo "Tamanho"
5. Na seção "Termos", adicionar: P, M, G, GG

### Passo 3: Criar Produto Variável
1. Ir em `/admin/produtos/novo`
2. Preencher dados básicos
3. Selecionar Tipo = "Produto Variável"
4. Clicar em "Salvar e configurar variações"
5. Sistema redireciona para edição com foco em atributos

### Passo 4: Configurar Atributos do Produto
1. Na seção "Atributos do Produto":
   - Selecionar atributo "Cor" do dropdown
   - Marcar "Usado para variação"
   - Selecionar termos: Vermelho, Azul
   - Selecionar atributo "Tamanho" do dropdown
   - Marcar "Usado para variação"
   - Selecionar termos: P, M
2. Clicar em "Salvar atributos"

### Passo 5: Gerar Variações
1. Clicar em "Gerar Variações"
2. Sistema cria 4 variações: Vermelho-P, Vermelho-M, Azul-P, Azul-M

### Passo 6: Configurar Variações
1. Na grade de variações:
   - Preencher SKU de cada variação
   - Configurar preço (se diferente do produto)
   - Configurar estoque
   - Configurar backorder
   - Upload de imagem (se necessário)
2. Clicar em "Salvar variações"

### Passo 7: Publicar
1. Verificar status de cada variação
2. Salvar produto

---

## 🔍 Diferença: Atributo vs Termo

### Atributo
- **Definição:** Característica do produto (ex: Cor, Tamanho, Material)
- **Escopo:** Global (usado por múltiplos produtos)
- **Campos:** Nome, Slug, Tipo Visual (select/color/image), Ordem
- **Exemplo:** "Cor" (atributo)

### Termo
- **Definição:** Valor específico do atributo (ex: Vermelho, Azul, P, M)
- **Escopo:** Pertence a um atributo específico
- **Campos:** Nome, Slug, Ordem + campos específicos por tipo
- **Exemplo:** "Vermelho" (termo do atributo "Cor")

### Relação
```
Atributo "Cor"
  ├─ Termo "Vermelho" (#FF0000)
  ├─ Termo "Azul" (#0000FF)
  └─ Termo "Verde" (#00FF00)

Atributo "Tamanho"
  ├─ Termo "P"
  ├─ Termo "M"
  ├─ Termo "G"
  └─ Termo "GG"
```

---

## ✅ Checklist de Testes Manuais

### Preparação
- [ ] Criar atributo "Cor" (tipo: color)
- [ ] Criar atributo "Tamanho" (tipo: select)

### Cadastro de Termos
- [ ] Editar atributo "Cor"
- [ ] Na seção "Termos", adicionar termo "Vermelho"
- [ ] Configurar hex #FF0000 usando color picker
- [ ] Verificar: campo de texto HEX sincroniza com color picker
- [ ] Upload de swatch para "Vermelho"
- [ ] Verificar: preview do swatch aparece
- [ ] Upload de imagem do produto para "Vermelho"
- [ ] Verificar: preview da imagem aparece
- [ ] Adicionar termo "Azul" (#0000FF)
- [ ] Adicionar termo "Verde" (#00FF00)
- [ ] Editar atributo "Tamanho"
- [ ] Adicionar termos: P, M, G, GG

### Novo Produto Variável
- [ ] Ir em `/admin/produtos/novo`
- [ ] Preencher nome, preço, etc.
- [ ] Selecionar Tipo = "Produto Variável"
- [ ] Verificar: aviso informativo aparece
- [ ] Verificar: botão "Salvar e configurar variações" aparece
- [ ] Clicar em "Salvar e configurar variações"
- [ ] Verificar: redireciona para edição com âncora #atributos
- [ ] Verificar: scroll automático para seção de atributos

### Configuração de Atributos no Produto
- [ ] Na seção "Atributos do Produto":
  - [ ] Selecionar "Cor" do dropdown "Adicionar atributo"
  - [ ] Verificar: atributo aparece na lista
  - [ ] Marcar "Usado para variação"
  - [ ] Selecionar termos: Vermelho, Azul
  - [ ] Verificar: preview de swatch aparece para cada cor
  - [ ] Selecionar "Tamanho" do dropdown
  - [ ] Marcar "Usado para variação"
  - [ ] Selecionar termos: P, M
- [ ] Clicar em "Salvar atributos"
- [ ] Verificar: atributos salvos corretamente

### Geração de Variações
- [ ] Clicar em "Gerar Variações"
- [ ] Verificar: 4 variações criadas (Vermelho-P, Vermelho-M, Azul-P, Azul-M)
- [ ] Verificar: mensagem de sucesso exibida

### Configuração de Variações
- [ ] Na grade de variações:
  - [ ] Preencher SKU para cada variação
  - [ ] Configurar preço (se diferente)
  - [ ] Configurar estoque: 10 para Vermelho-P, 5 para Vermelho-M, etc.
  - [ ] Configurar backorder: "Não" para todas
  - [ ] Upload de imagem para variação "Vermelho-P"
  - [ ] Verificar: preview da imagem aparece
- [ ] Clicar em "Salvar variações"
- [ ] Verificar: variações salvas corretamente

### Estoque do Produto Pai
- [ ] Verificar: campo de estoque do produto pai está desabilitado ou com aviso
- [ ] Verificar: mensagem "Estoque é controlado por variação" aparece

### Storefront
- [ ] Acessar página do produto variável
- [ ] Selecionar apenas Cor = "Vermelho" (sem tamanho)
- [ ] Verificar: imagem principal troca para imagem por cor (se configurada)
- [ ] Verificar: botão "Adicionar" ainda desabilitado
- [ ] Selecionar Tamanho = "P"
- [ ] Verificar: imagem principal troca para imagem da variação (se configurada)
- [ ] Verificar: estoque exibido ("Em estoque (10 unidades)")
- [ ] Verificar: botão "Adicionar" habilitado
- [ ] Clicar em "Adicionar ao Carrinho"
- [ ] Verificar: produto adicionado com variacao_id correto
- [ ] Testar variação sem estoque:
  - [ ] Selecionar variação com estoque = 0, backorder = "no"
  - [ ] Verificar: exibe "Indisponível"
  - [ ] Verificar: botão "Adicionar" desabilitado

---

## 🚀 Próximos Passos (Opcional)

1. **Bulk Actions na Grade de Variações:**
   - Selecionar múltiplas variações
   - Aplicar preço/estoque/backorder em lote

2. **Filtros na Grade de Variações:**
   - Filtrar por Cor
   - Filtrar por Tamanho
   - Filtrar por status de estoque

3. **Import/Export de Variações:**
   - Exportar variações para CSV
   - Importar variações de CSV

4. **Validação de Termos em Uso:**
   - Ao excluir termo, verificar se está em uso
   - Mostrar lista de produtos que usam o termo
   - Opção de substituir por outro termo

---

**Fim do Documento**
