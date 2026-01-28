# Micro Hardenings - Fase 3

## A) Consistência da Signature no DB

### Análise

Após análise do código, **os atributos de variação (`produto_variacao_atributos`) nunca são alterados após a criação da variação**.

O método `saveVariationsBulk()` apenas atualiza campos de `produto_variacoes`:
- `sku`
- `preco_regular`
- `preco_promocional`
- `gerencia_estoque`
- `quantidade_estoque`
- `status_estoque`
- `permite_pedidos_falta`
- `status`

**Não há nenhum fluxo que altere `produto_variacao_atributos` após a criação.**

### Conclusão

✅ **A signature não precisa ser recalculada no `saveVariationsBulk()`**, pois os atributos não mudam.

A única forma de alterar atributos seria:
1. Deletar a variação e criar uma nova (com nova signature)
2. Editar manualmente no banco (não recomendado)

### Recomendação

Se no futuro houver necessidade de editar atributos de variação via UI:
- Criar endpoint específico que recalcule e atualize a `signature`
- Ou implementar trigger no banco para manter consistência automática

---

## B) Mensagem de Erro Amigável - Produto Variável sem Variação

### Implementação

Adicionada validação no `CartController::add()`:

```php
// Validação: produto variável requer variacao_id
if ($produto['tipo'] === 'variable') {
    if (empty($variacaoId) || $variacaoId <= 0) {
        // Buscar nomes dos atributos para mensagem amigável
        $stmtAttrs = $db->prepare("
            SELECT a.nome
            FROM produto_atributos pa
            INNER JOIN atributos a ON a.id = pa.atributo_id
            WHERE pa.produto_id = :produto_id
            AND pa.tenant_id = :tenant_id
            AND pa.usado_para_variacao = 1
            ORDER BY pa.ordem ASC
        ");
        $stmtAttrs->execute(['produto_id' => $produtoId, 'tenant_id' => $tenantId]);
        $atributosNomes = $stmtAttrs->fetchAll(\PDO::FETCH_COLUMN);
        
        $atributosStr = !empty($atributosNomes) 
            ? implode(' e ', $atributosNomes) 
            : 'as opções';
        
        $this->redirect($_SERVER['HTTP_REFERER'] ?? '/produtos?error=selecione_' . urlencode($atributosStr));
        return;
    }
}
```

### Comportamento

- Se produto é variável e `variacao_id` está vazio/null
- Busca os nomes dos atributos marcados como `usado_para_variacao`
- Redireciona com mensagem: `error=selecione_Cor e Tamanho` (exemplo)
- Frontend pode capturar e exibir mensagem amigável

### Exemplo de Mensagem

Para produto com atributos "Cor" e "Tamanho":
```
"Selecione Cor e Tamanho para adicionar ao carrinho."
```

---

## Status

✅ **Ambos os hardenings implementados e documentados**
