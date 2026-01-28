# AUDITORIA — Sugestão de Variações para Produtos Existentes

## ⚠️ IMPORTANTE

**Somente SELECT. Nenhuma alteração foi feita no banco de dados.**

Este relatório identifica grupos de produtos que parecem representar o mesmo item com variações (ex.: "Blusa X" em cores/tamanhos diferentes cadastradas como produtos separados).

---

## Como Executar a Auditoria

```bash
php database/auditar_produtos_variacoes_cli.php
```

O script:
1. Analisa todos os produtos simples (`tipo = 'simple'`)
2. Normaliza nomes removendo variações comuns
3. Agrupa produtos similares
4. Extrai variações detectadas (cor, tamanho, numeração)
5. Calcula pontuação de confiança
6. Gera relatório JSON em `storage/reports/auditoria_variacoes_<data>.json`

---

## Metodologia

### 1. Normalização de Nome

- **Lowercase e trim**
- **Remoção de acentos** (á → a, é → e, etc.)
- **Remoção de pontuação** (-, /, ., ,)
- **Remoção de tokens de variação** no final:
  - **Cores:** vermelho, azul, preto, branco, amarelo, verde, rosa, cinza, marrom, bege, etc.
  - **Tamanhos:** PP, P, M, G, GG, XG, XGG, Plus, Infantil, Adulto, etc.
  - **Numeração:** 34-46, números isolados (30-50)

O restante vira o `base_name`.

### 2. Agrupamento

- Agrupa por `tenant_id + base_name`
- Considera grupo "candidato" apenas se tiver **2+ produtos**

### 3. Extração de Variações

Detecta automaticamente:
- **COR** no nome (lista de cores conhecidas)
- **TAMANHO** no nome (PP/P/M/G/GG/etc.)
- **NUMERAÇÃO** no nome (34-46, números isolados)
- **SKU padronizado** (ex.: termina com -P, -M, -Vermelho)

### 4. Pontuação de Confiança (0 a 100)

- **+30** se `base_name` igual (já agrupados)
- **+20** se descrições muito parecidas (>80% similaridade)
- **+20** se imagens iguais (mesma `imagem_principal`)
- **+10** se preços iguais
- **+5** se preços muito próximos (diferença ≤ R$ 5,00)
- **-20** se categorias diferentes (se aplicável)

**Marcar como "precisa revisão" se confiança < 60**

### 5. Nomenclatura

- **Produto pai sugerido:** `base_name` capitalizado (ex: "Blusa Básica Lisa")
- **Termos:** Extraídos dos nomes/SKUs detectados
- **Não inventa nomenclatura:** Usa apenas o que foi detectado

---

## Estrutura do Relatório JSON

```json
{
  "generated_at": "2025-01-XX XX:XX:XX",
  "total_produtos_analisados": 150,
  "total_grupos_encontrados": 12,
  "total_produtos_em_grupos": 35,
  "total_produtos_orfas": 115,
  "groups": [
    {
      "tenant_id": 1,
      "base_name": "blusa basica lisa",
      "confidence": 82,
      "suggested_parent_name": "Blusa Básica Lisa",
      "detected_attributes": {
        "Cor": ["Amarelo", "Vermelho", "Preto"],
        "Tamanho": ["P", "M", "G", "GG"]
      },
      "items": [
        {
          "id": 10,
          "nome": "Blusa Básica Lisa Amarelo P",
          "sku": "BLUSA-AM-P",
          "preco": 99.90,
          "preco_regular": 99.90,
          "preco_promocional": null,
          "estoque": 5,
          "status": "publish"
        },
        ...
      ],
      "notes": []
    }
  ],
  "orphans": [
    {
      "id": 999,
      "nome": "Produto Único",
      "sku": "UNICO-001",
      "preco": 149.90
    }
  ]
}
```

---

## Interpretação dos Resultados

### Grupos com Alta Confiança (≥ 80)

- **Ação recomendada:** Converter para produto variável
- **Processo:**
  1. Criar produto variável com nome sugerido
  2. Criar atributos detectados (Cor, Tamanho, etc.)
  3. Criar termos para cada atributo
  4. Gerar variações automaticamente
  5. Migrar dados (preço, estoque, imagens) dos produtos antigos
  6. Desativar/excluir produtos antigos

### Grupos com Confiança Média (60-79)

- **Ação recomendada:** Revisão manual antes de converter
- **Verificar:**
  - Se realmente são o mesmo produto
  - Se as variações detectadas estão corretas
  - Se há divergências de preço/descrição que precisam ser resolvidas

### Grupos com Baixa Confiança (< 60)

- **Ação recomendada:** Análise detalhada
- **Possíveis causas:**
  - Produtos diferentes que apenas têm nomes similares
  - Variações não detectadas corretamente
  - Divergências significativas (preço, descrição, imagens)

### Produtos Órfãos

- **Ação recomendada:** Manter como produtos simples
- São produtos únicos que não têm variações

---

## TOP 20 Grupos (por Confiança)

*Será preenchido após execução do script*

---

## TOP 20 Precisa Revisão (confiança < 60)

*Será preenchido após execução do script*

---

## Próximos Passos

1. **Revisar o relatório JSON** gerado em `storage/reports/`
2. **Validar grupos** de alta confiança manualmente
3. **Decidir nomenclatura padrão** para cada grupo
4. **Criar plano de migração** (quais grupos converter primeiro)
5. **Executar migração** (fora do escopo desta auditoria)

---

## Notas Técnicas

- **Tabelas consultadas:** `produtos` (apenas SELECT)
- **Filtros aplicados:** `tipo = 'simple' AND status = 'publish'`
- **Campos utilizados:** nome, sku, preco, preco_regular, preco_promocional, quantidade_estoque, descricao, imagem_principal
- **Performance:** Script otimizado para grandes volumes (usa índices existentes)

---

**Gerado em:** *Data da execução do script*  
**Versão:** 1.0
