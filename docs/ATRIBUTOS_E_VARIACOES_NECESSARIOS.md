# 📋 Atributos e Variações Necessários

**Baseado na auditoria de produtos existentes**

---

## 🎯 ATRIBUTOS A CRIAR NO SISTEMA

### 1. **Cor** (14 termos)
- Amarelo
- Azul
- Bege
- Branca
- Branco
- Cinza
- Dourado
- Laranja
- Preta
- Preto
- Rosa
- Roxa
- Verde
- Vermelho

**Tipo Visual:** `Cor` (Color Picker)

---

### 2. **Tamanho** (7 termos)
- G
- GG
- GRANDE
- INFANTIL
- M
- P
- UN

**Tipo Visual:** `Select` (Dropdown)

**Nota:** Alguns produtos usam "UN" (único/unidade) e "INFANTIL" como tamanho. Avaliar se devem ser tratados como atributo separado.

---

### 3. **Numeração** (5 termos)
- 32
- 34
- 36
- 38
- 42

**Tipo Visual:** `Select` (Dropdown)

**Nota:** Usado principalmente para calças e bermudas (numeração de cintura/perna).

---

## 📦 GRUPOS DE PRODUTOS (Variações Sugeridas)

### **Alta Confiança (≥ 60%) - Prioridade para Conversão**

#### 1. **Tênis de Golfe Adidas S2G Spikeless BOA** (Conf: 80%)
- **Produto Pai:** "Tênis de Golfe Adidas S2G Spikeless BOA"
- **2 itens** - Apenas diferença de tamanho (7 1/2 e 8 1/2)
- **Atributos necessários:** Tamanho (ou Numeração para calçados)
- **Produtos:** ID 926, 924

---

#### 2. **Calça Adidas TM** (Conf: 60%)
- **Produto Pai:** "Calça Adidas TM"
- **2 itens**
- **Atributos necessários:**
  - Cor: Bege
  - Numeração: 32, 34, 36
- **Produtos:** ID 496, 500

---

#### 3. **Camisa Polo Vinho Bordo Penguin** (Conf: 60%)
- **Produto Pai:** "Camisa Polo Vinho Bordo Penguin"
- **2 itens**
- **Atributos necessários:**
  - Tamanho: P, M
- **Produtos:** ID 200, 201

---

#### 4. **Nike Air Zoom Victory Tour** (Conf: 60%)
- **Produto Pai:** "Nike Air Zoom Victory Tour"
- **2 itens** - Tamanhos 7.5 e 8.5
- **Atributos necessários:** Tamanho (ou Numeração para calçados)
- **Produtos:** ID 190, 193

---

#### 5. **Short Saia Du´mo Claro TM** (Conf: 60%)
- **Produto Pai:** "Short Saia Du´mo Claro TM"
- **2 itens**
- **Atributos necessários:**
  - Cor: Azul
  - Tamanho: G, GG
- **Produtos:** ID 28, 29

---

#### 6. **Short Saia Du´mo Marinho TM** (Conf: 60%)
- **Produto Pai:** "Short Saia Du´mo Marinho TM"
- **2 itens**
- **Atributos necessários:**
  - Cor: Azul
  - Tamanho: GG, M
- **Produtos:** ID 37, 31

---

### **Média Confiança (40-59%) - Revisar Antes de Converter**

#### 7. **Tênis FJ Footjoy Pro/SLX** (Conf: 50%)
- **Produto Pai:** "Tênis FJ Footjoy Pro/SLX"
- **7 itens** - Maior grupo encontrado
- **Atributos necessários:**
  - Cor: Branco, Azul, Preto, Vermelho
- **Produtos:** ID 219, 236, 235, 224, 226, 227, 225
- **Observação:** Diferenças de tamanho também presentes (TM 10, 10 1/2, 11, 11 1/2, 12, 13)

---

#### 8. **Camisa Polo Penguin** (Conf: 40%)
- **Produto Pai:** "Camisa Polo Penguin"
- **5 itens**
- **Atributos necessários:**
  - Cor: Branca, Preta
  - Tamanho: G, M, P
- **Produtos:** ID 199, 194, 195, 197, 202
- **⚠️ Observação:** Preços divergentes (diferença de R$ 50,00)

---

#### 9. **Luva GOG Golf TM** (Conf: 40%)
- **Produto Pai:** "Luva GOG Golf TM"
- **5 itens**
- **Atributos necessários:**
  - Cor: Azul, Rosa
- **Produtos:** ID 564, 563, 559, 560, 561
- **Observação:** Diferenças de tamanho também (TM 18, 19, 20)

---

#### 10. **Camiseta Clube Masculina** (Conf: 40%)
- **Produto Pai:** "Camiseta Clube Masculina"
- **4 itens**
- **Atributos necessários:**
  - Cor: Cinza
  - Tamanho: G, GG, M, P
- **Produtos:** ID 878, 874, 882, 870

---

#### 11. **Viseira Under Armour** (Conf: 40%)
- **Produto Pai:** "Viseira Under Armour"
- **4 itens**
- **Atributos necessários:**
  - Cor: Branca, Branco, Preta, Preto
- **Produtos:** ID 456, 681, 455, 683
- **⚠️ Observação:** Imagens diferentes

---

#### 12. **Camiseta Clube Feminina** (Conf: 40%)
- **Produto Pai:** "Camiseta Clube Feminina"
- **3 itens**
- **Atributos necessários:**
  - Cor: Roxa
  - Tamanho: G, GG, M
- **Produtos:** ID 865, 862, 868

---

#### 13. **Camiseta Clube Sem Manga** (Conf: 40%)
- **Produto Pai:** "Camiseta Clube Sem Manga"
- **3 itens**
- **Atributos necessários:**
  - Cor: Rosa
  - Tamanho: G, M, P
- **Produtos:** ID 834, 879, 871

---

#### 14. **Corta Vento Alana TM** (Conf: 40%)
- **Produto Pai:** "Corta Vento Alana TM"
- **3 itens**
- **Atributos necessários:**
  - Cor: Amarelo
  - Tamanho: G, P
- **Produtos:** ID 747, 717, 749

---

#### 15. **Faixa Cabelo Protetor Ouvido** (Conf: 40%)
- **Produto Pai:** "Faixa Cabelo Protetor Ouvido"
- **3 itens**
- **Atributos necessários:**
  - Cor: Branco, Cinza, Preto
- **Produtos:** ID 787, 790, 793

---

#### 16. **Mangote** (Conf: 40%)
- **Produto Pai:** "Mangote"
- **3 itens**
- **Atributos necessários:**
  - Cor: Branco, Cinza, Preto
- **Produtos:** ID 746, 748, 716

---

## 📊 RESUMO ESTATÍSTICO

- **Total de atributos únicos:** 3 (Cor, Tamanho, Numeração)
- **Total de grupos encontrados:** 48
- **Total de produtos em grupos:** 119
- **Total de produtos órfãos:** 550 (permanecem simples)

---

## ✅ PLANO DE AÇÃO SUGERIDO

### Fase 1: Criar Atributos Globais
1. Criar atributo **"Cor"** com todos os 14 termos
2. Criar atributo **"Tamanho"** com todos os 7 termos
3. Criar atributo **"Numeração"** com todos os 5 termos

### Fase 2: Converter Grupos de Alta Confiança (≥ 60%)
Começar pelos 6 grupos com confiança ≥ 60%:
1. Tênis de Golfe Adidas S2G
2. Calça Adidas TM
3. Camisa Polo Vinho Bordo Penguin
4. Nike Air Zoom Victory Tour
5. Short Saia Du´mo Claro TM
6. Short Saia Du´mo Marinho TM

### Fase 3: Revisar e Converter Grupos de Média Confiança (40-59%)
Revisar manualmente os grupos com confiança 40-59% antes de converter.

### Fase 4: Análise Manual dos Grupos de Baixa Confiança (< 40%)
Analisar caso a caso se realmente devem ser convertidos.

---

## 📄 Arquivos de Referência

- **Relatório JSON completo:** `storage/reports/auditoria_variacoes_2026-01-20_144326.json`
- **Relatório texto detalhado:** `storage/reports/atributos_variacoes_necessarios.txt`
- **Documentação da auditoria:** `docs/AUDITORIA_SUGESTAO_VARIACOES_EXISTENTES.md`

---

**Gerado em:** 2026-01-20  
**Baseado em:** 669 produtos analisados
