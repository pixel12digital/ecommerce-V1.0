# Análise: Campos Necessários para Cálculo Automático de Frete

## 📋 Resumo Executivo

Esta análise identifica quais campos de produtos são necessários para cálculo automático de frete e compara com o que já está implementado no sistema, além de verificar a compatibilidade com integrações via API (Correios, Melhor Envio, etc.).

---

## ✅ O QUE JÁ EXISTE NO SISTEMA

### 1. Estrutura de Banco de Dados

A tabela `produtos` já possui os seguintes campos relacionados a dimensões e frete:

```sql
-- Campos existentes na tabela produtos
peso DECIMAL(8,2) NULL,           -- ✅ Peso do produto
comprimento DECIMAL(8,2) NULL,    -- ✅ Comprimento (cm)
largura DECIMAL(8,2) NULL,        -- ✅ Largura (cm)
altura DECIMAL(8,2) NULL,         -- ✅ Altura (cm)
```

**Status:** ✅ **IMPLEMENTADO** - Campos existem no banco de dados

### 2. Exibição dos Dados

Os campos de dimensões são exibidos na visualização do produto (admin):

- **Arquivo:** `themes/default/admin/products/show.php` (linhas 295-325)
- Os campos são mostrados quando preenchidos
- Formato: peso em kg, dimensões em cm

**Status:** ✅ **IMPLEMENTADO** - Dados são exibidos quando cadastrados

### 3. Importação de Dados

O script de importação já processa esses campos do WooCommerce:

- **Arquivo:** `database/import_products.php` (linhas 331-334)
- Campos mapeados: `weight`, `length`, `width`, `height`

**Status:** ✅ **IMPLEMENTADO** - Importação funciona

---

## ❌ O QUE FALTA IMPLEMENTAR

### 1. Formulário de Cadastro/Edição de Produtos

**PROBLEMA CRÍTICO:** Os campos de dimensões e peso **NÃO estão presentes** nos formulários de criação e edição de produtos.

**Arquivos afetados:**
- `themes/default/admin/products/create-content.php` - **FALTA seção de dimensões**
- `themes/default/admin/products/edit-content.php` - **FALTA seção de dimensões**

**Impacto:** 
- Usuários não conseguem cadastrar peso e dimensões via interface administrativa
- Dados só podem ser inseridos diretamente no banco ou via importação

**Status:** ❌ **NÃO IMPLEMENTADO** - Bloqueia uso prático do sistema

---

### 2. Processamento no Controller

**PROBLEMA:** O `ProductController` não processa os campos de dimensões ao salvar produtos.

**Arquivo:** `src/Http/Controllers/Admin/ProductController.php`

**Análise:**
- Método `update()` (linha ~600): Não processa `peso`, `comprimento`, `largura`, `altura`
- Método `store()` (criação): Provavelmente também não processa

**Status:** ❌ **NÃO IMPLEMENTADO** - Dados não são salvos mesmo se formulário existisse

---

### 3. Uso no Cálculo de Frete

**PROBLEMA:** O serviço de frete atual (`SimpleShippingProvider`) **não utiliza** as dimensões dos produtos.

**Arquivo:** `src/Services/Shipping/SimpleShippingProvider.php`

**Análise:**
- O cálculo atual é baseado apenas em:
  - Subtotal do pedido
  - CEP de destino (para determinar região)
- **Não considera:** peso, dimensões, volume dos produtos

**Status:** ⚠️ **PARCIAL** - Infraestrutura existe, mas não é utilizada

---

### 4. Campos Adicionais Necessários para APIs Reais

Para integração com APIs de frete (Correios, Melhor Envio, etc.), podem ser necessários campos adicionais:

#### 4.1. Campos de Embalagem (Opcional, mas Recomendado)

Alguns e-commerces separam dimensões do produto das dimensões da embalagem:

```sql
-- Campos que PODERIAM ser adicionados (opcional)
peso_embalagem DECIMAL(8,2) NULL,
comprimento_embalagem DECIMAL(8,2) NULL,
largura_embalagem DECIMAL(8,2) NULL,
altura_embalagem DECIMAL(8,2) NULL,
```

**Status:** ❌ **NÃO IMPLEMENTADO** - Não é crítico, mas melhora precisão

#### 4.2. CEP de Origem (Configuração da Loja)

O CEP de origem deve estar configurado no tenant/loja, não no produto.

**Status:** ⚠️ **VERIFICAR** - Pode estar em `tenant_gateways.config_json` ou precisa ser adicionado

#### 4.3. Valor Declarado

Algumas transportadoras calculam seguro baseado no valor do produto.

**Status:** ✅ **JÁ EXISTE** - Campo `preco` já existe na tabela

#### 4.4. Informações de Fragilidade

Produtos frágeis podem ter custos adicionais.

**Status:** ❌ **NÃO IMPLEMENTADO** - Campo opcional

---

## 🔍 COMPARAÇÃO COM E-COMMERCES DE REFERÊNCIA

### WooCommerce (WordPress)

**Campos padrão:**
- ✅ Peso (`weight`)
- ✅ Comprimento (`length`)
- ✅ Largura (`width`)
- ✅ Altura (`height`)
- ✅ Classe de frete (`shipping_class`) - agrupa produtos com regras similares

**Observação:** O sistema atual já importa esses campos do WooCommerce.

### Magento

**Campos padrão:**
- ✅ Peso
- ✅ Dimensões (comprimento, largura, altura)
- ✅ Volume (calculado automaticamente)
- ✅ Classe de frete
- ✅ Código de produto para frete

### Shopify

**Campos padrão:**
- ✅ Peso
- ✅ Dimensões
- ✅ Requer embalagem especial (checkbox)
- ✅ Código HS (Harmonized System) para internacional

---

## 📊 REQUISITOS PARA APIs DE FRETE

### API dos Correios

**Campos obrigatórios:**
- ✅ CEP de origem
- ✅ CEP de destino
- ✅ Peso (em kg)
- ✅ Dimensões (comprimento, largura, altura em cm)
- ✅ Valor declarado (opcional, mas recomendado)
- ✅ Formato (caixa/pacote/envelope)

**Status no sistema:** ⚠️ **PARCIAL** - Campos existem, mas não são usados no cálculo

### Melhor Envio

**Campos obrigatórios:**
- ✅ CEP de origem
- ✅ CEP de destino
- ✅ Peso (em kg)
- ✅ Dimensões (comprimento, largura, altura em cm)
- ✅ Valor do produto (para seguro)

**Status no sistema:** ⚠️ **PARCIAL** - Campos existem, mas não são usados no cálculo

### Jadlog / Outras Transportadoras

**Campos obrigatórios:**
- ✅ Peso
- ✅ Dimensões
- ✅ CEP origem/destino
- ✅ Valor declarado

**Status no sistema:** ⚠️ **PARCIAL** - Campos existem, mas não são usados no cálculo

---

## 🎯 PRIORIDADES DE IMPLEMENTAÇÃO

### 🔴 CRÍTICO (Bloqueia uso)

1. **Adicionar campos de dimensões no formulário de cadastro/edição**
   - Criar seção "Dimensões e Frete" nos formulários
   - Campos: Peso (kg), Comprimento (cm), Largura (cm), Altura (cm)
   - Validação: valores numéricos, opcionais mas recomendados

2. **Processar campos no Controller**
   - Atualizar método `store()` para salvar dimensões
   - Atualizar método `update()` para salvar dimensões
   - Validar valores antes de salvar

### 🟡 ALTA (Necessário para APIs reais)

3. **Utilizar dimensões no cálculo de frete**
   - Modificar `ShippingService` para buscar dimensões dos produtos
   - Calcular peso total e dimensões totais do pedido
   - Passar dados para providers de frete

4. **Implementar provider para API real (Melhor Envio/Correios)**
   - Criar `MelhorEnvioProvider` ou `CorreiosProvider`
   - Usar dimensões dos produtos no cálculo
   - Implementar cache de resultados

### 🟢 MÉDIA (Melhorias)

5. **Campos adicionais (opcional)**
   - Peso/dimensões de embalagem (se diferente do produto)
   - Flag de fragilidade
   - Classe de frete (agrupar produtos)

6. **Validações e UX**
   - Validação de dimensões máximas (limites das transportadoras)
   - Cálculo automático de volume (comprimento × largura × altura)
   - Avisos quando campos estão vazios

---

## 📝 CHECKLIST DE IMPLEMENTAÇÃO

### Fase 1: Formulários e Controller (CRÍTICO)

- [ ] Adicionar seção "Dimensões e Frete" em `create-content.php`
- [ ] Adicionar seção "Dimensões e Frete" em `edit-content.php`
- [ ] Criar campos: peso, comprimento, largura, altura
- [ ] Adicionar validação JavaScript (valores numéricos)
- [ ] Processar campos no método `store()` do `ProductController`
- [ ] Processar campos no método `update()` do `ProductController`
- [ ] Adicionar validação PHP (valores numéricos, opcionais)

### Fase 2: Integração com Frete (ALTA)

- [ ] Modificar `ShippingService::calcularFrete()` para buscar dimensões dos produtos
- [ ] Criar método para calcular peso total do pedido
- [ ] Criar método para calcular dimensões totais (soma ou maior dimensão)
- [ ] Passar dimensões para `ShippingProviderInterface`
- [ ] Atualizar `SimpleShippingProvider` para usar dimensões (opcional)
- [ ] Criar `MelhorEnvioProvider` ou `CorreiosProvider`
- [ ] Implementar chamada à API com dimensões

### Fase 3: Melhorias (MÉDIA)

- [ ] Adicionar campo "CEP de origem" nas configurações do tenant
- [ ] Adicionar validação de limites de dimensões (ex: Correios tem limites)
- [ ] Calcular e exibir volume do produto automaticamente
- [ ] Adicionar avisos quando dimensões não estão cadastradas
- [ ] Criar relatório de produtos sem dimensões

---

## 🔗 ESTRUTURA DE DADOS NECESSÁRIA

### Dados do Produto (já existe)

```php
[
    'peso' => 0.5,           // kg
    'comprimento' => 20.0,   // cm
    'largura' => 15.0,        // cm
    'altura' => 10.0,         // cm
]
```

### Dados para API de Frete

```php
[
    'from' => [
        'postal_code' => '01310-100',  // CEP origem (config tenant)
    ],
    'to' => [
        'postal_code' => '20000-000',  // CEP destino (do cliente)
    ],
    'products' => [
        [
            'id' => 123,
            'weight' => 0.5,           // kg
            'width' => 15.0,            // cm
            'height' => 10.0,           // cm
            'length' => 20.0,           // cm
            'quantity' => 2,
            'price' => 99.90,
        ],
        // ... mais produtos
    ],
]
```

---

## 📚 REFERÊNCIAS TÉCNICAS

### Documentação de APIs

- **Melhor Envio:** https://melhorenvio.com.br/documentacao
- **Correios:** https://www.correios.com.br/enviar/precisa-de-ajuda/calculador-remoto-de-precos-e-prazos
- **Jadlog:** https://www.jadlog.com.br/siteInstitucional/calculadora

### Padrões de E-commerce

- **WooCommerce:** Shipping dimensions
- **Magento:** Product weight and dimensions
- **Shopify:** Product shipping information

---

## ✅ CONCLUSÃO

### O que está pronto:
1. ✅ Estrutura de banco de dados com campos de dimensões
2. ✅ Importação de dados do WooCommerce
3. ✅ Exibição dos dados na visualização do produto
4. ✅ Infraestrutura de providers de frete (interface e serviço)

### O que falta (bloqueia uso):
1. ❌ **Formulários de cadastro/edição** - Usuários não conseguem cadastrar dimensões
2. ❌ **Processamento no Controller** - Dados não são salvos
3. ❌ **Uso no cálculo de frete** - Dimensões não são utilizadas

### Próximos passos recomendados:
1. Implementar formulários e controller (Fase 1) - **CRÍTICO**
2. Integrar dimensões no cálculo de frete (Fase 2) - **ALTA PRIORIDADE**
3. Criar provider para API real (Melhor Envio/Correios) - **ALTA PRIORIDADE**

---

**Data da Análise:** Janeiro 2025  
**Versão do Sistema:** ecommerce-v1.0

