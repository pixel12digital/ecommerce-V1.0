# Documentação: Fallbacks de Peso e Dimensões para Cálculo de Frete

## Visão Geral

O sistema de cálculo de frete utiliza dados de peso e dimensões dos produtos para calcular o custo e prazo de entrega. Quando esses dados não estão disponíveis, o sistema aplica valores padrão (fallbacks) configuráveis.

## Precedência de Medidas

### 1. Dados do Produto (Prioridade Máxima)

**Se o produto possui peso/dimensões cadastrados, esses valores são sempre utilizados.**

- **Peso**: Campo `produtos.peso` (em kg, DECIMAL 8,2)
- **Dimensões**: 
  - `produtos.comprimento` (em cm, DECIMAL 8,2)
  - `produtos.largura` (em cm, DECIMAL 8,2)
  - `produtos.altura` (em cm, DECIMAL 8,2)

**Exemplo:**
- Produto A: peso = 0.5kg, dimensões = 15x10x5 cm → **Usa esses valores**
- Produto B: peso = null, dimensões = null → **Usa fallback**

### 2. Fallback (Aplicado apenas quando dados do produto estão ausentes)

**Fallback é aplicado SOMENTE para itens que não possuem dados cadastrados.**

#### Valores Padrão Configuráveis

Os fallbacks podem ser configurados no gateway Correios via `config_json`:

```json
{
  "correios": {
    "fallback_peso_kg": 0.3,
    "fallback_dimensoes": {
      "comprimento": 20,
      "largura": 20,
      "altura": 10
    }
  }
}
```

**Valores padrão (se não configurados):**
- Peso: **0.3 kg**
- Dimensões: **20 x 20 x 10 cm** (comprimento x largura x altura)

#### Como Funciona

1. **Cálculo de Peso Total:**
   - Para cada item do carrinho:
     - Se `item.peso > 0`: soma `peso × quantidade`
     - Se `item.peso = 0` ou `null`: aplica `fallback_peso_kg × quantidade`
   - Peso mínimo garantido: **0.1 kg**

2. **Cálculo de Dimensões:**
   - Para cada item do carrinho:
     - Se item possui todas as dimensões (`comprimento > 0`, `largura > 0`, `altura > 0`):
       - Usa a **maior dimensão** de cada eixo
       - Soma alturas proporcionalmente ao peso
     - Se item não possui dimensões:
       - Usa `fallback_dimensoes` para esse item
   - Dimensões mínimas garantidas:
     - Comprimento: **16 cm**
     - Largura: **11 cm**
     - Altura: **2 cm**

3. **Validação de Unidades:**
   - O sistema detecta automaticamente se dimensões estão em **mm** (valores > 200cm)
   - Se detectado, divide por 10 para converter para cm

## Exemplos Práticos

### Exemplo 1: Carrinho com produtos completos

**Carrinho:**
- Produto A (qtd: 2): peso = 0.5kg, dimensões = 15x10x5 cm
- Produto B (qtd: 1): peso = 0.3kg, dimensões = 20x15x8 cm

**Cálculo:**
- Peso total: (0.5 × 2) + (0.3 × 1) = **1.3 kg**
- Dimensões: maior de cada eixo = **20 x 15 x 8 cm** (ajustado para mínimos: 20 x 15 x 8 cm)

### Exemplo 2: Carrinho com produtos sem dados

**Carrinho:**
- Produto A (qtd: 2): peso = null, dimensões = null
- Produto B (qtd: 1): peso = null, dimensões = null

**Cálculo (usando fallback padrão):**
- Peso total: (0.3 × 2) + (0.3 × 1) = **0.9 kg** (mínimo: 0.1 kg)
- Dimensões: **20 x 20 x 10 cm** (fallback padrão)

### Exemplo 3: Carrinho misto

**Carrinho:**
- Produto A (qtd: 1): peso = 0.5kg, dimensões = 15x10x5 cm
- Produto B (qtd: 2): peso = null, dimensões = null

**Cálculo:**
- Peso total: (0.5 × 1) + (0.3 × 2) = **1.1 kg**
- Dimensões: 
  - Comprimento: max(15, 20) = **20 cm**
  - Largura: max(10, 20) = **20 cm**
  - Altura: média ponderada entre 5cm (produto A) e 10cm (fallback para B) = **~8.3 cm**

## Configuração no Gateway

### Via Interface Admin

1. Acesse: **Admin → Configurações → Gateways → Frete → Correios**
2. Os fallbacks podem ser configurados via **JSON Personalizado** (avançado):

```json
{
  "correios": {
    "fallback_peso_kg": 0.3,
    "fallback_dimensoes": {
      "comprimento": 20,
      "largura": 20,
      "altura": 10
    }
  }
}
```

### Valores Recomendados

- **Peso padrão**: 0.3 kg (adequado para produtos pequenos/médios)
- **Dimensões padrão**: 20x20x10 cm (caixa pequena padrão)

**Ajuste conforme o perfil da loja:**
- Loja de roupas: peso 0.2kg, dimensões 15x15x5 cm
- Loja de eletrônicos: peso 0.5kg, dimensões 25x20x15 cm
- Loja de livros: peso 0.3kg, dimensões 20x15x3 cm

## Validações e Limites

### Valores Mínimos (API Correios)

- **Peso mínimo**: 0.1 kg
- **Dimensões mínimas**:
  - Comprimento: 16 cm
  - Largura: 11 cm
  - Altura: 2 cm

### Valores Máximos (PAC/SEDEX padrão)

- **Peso máximo**: 30 kg
- Dimensões máximas variam conforme serviço (consultar API Correios)

### Tratamento de Erros

Se após aplicar fallbacks os dados ainda estiverem inválidos:
- Sistema retorna array vazio de opções de frete
- Exibe mensagem amigável no checkout: *"Não foi possível calcular o frete no momento. Verifique o CEP e tente novamente."*
- Loga motivo técnico (sem credenciais) para debug

## Implementação Técnica

### Arquivos Envolvidos

- `src/Services/Shipping/Providers/CorreiosProvider.php`
  - Métodos: `calcularPesoTotal()`, `calcularDimensoes()`
- `src/Services/Shipping/ShippingService.php`
  - Método: `enriquecerItensComDimensoes()`

### Fluxo de Cálculo

1. `ShippingService::calcularFrete()` enriquece itens com dados do banco
2. `CorreiosProvider::calcularOpcoesFrete()` recebe itens enriquecidos
3. Para cada item:
   - Se possui dados → usa dados do produto
   - Se não possui → aplica fallback
4. Calcula totais (peso e dimensões agregadas)
5. Aplica validações (mínimos/máximos)
6. Chama API Correios com dados calculados

## Boas Práticas

1. **Sempre cadastre peso e dimensões dos produtos** quando possível
2. **Configure fallbacks adequados** ao perfil da loja
3. **Monitore logs** quando frete retornar vazio
4. **Valide unidades** ao cadastrar produtos (kg para peso, cm para dimensões)

## Troubleshooting

### Problema: Frete sempre retorna vazio

**Possíveis causas:**
1. CEP de origem não configurado
2. Credenciais Correios inválidas
3. Dados após fallback ainda inválidos (verificar logs)
4. API Correios temporariamente indisponível

**Solução:**
- Verificar logs: `error_log` com prefixo "CorreiosProvider:"
- Validar configuração do gateway
- Testar via botão "Testar Conexão Correios" no admin

### Problema: Frete muito alto/baixo

**Possíveis causas:**
1. Fallbacks inadequados para o tipo de produto
2. Unidades incorretas (mm vs cm, g vs kg)
3. Dados do produto incorretos

**Solução:**
- Revisar fallbacks no gateway
- Validar dados dos produtos
- Ajustar fallbacks conforme necessário
