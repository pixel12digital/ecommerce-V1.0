# Respostas - Validação Final Obrigatória

## 1. Resultado da Query de Duplicatas

**Query executada:**
```sql
SELECT pv.produto_id, pv.tenant_id,
       COALESCE(
           pv.signature,
           (SELECT GROUP_CONCAT(CONCAT(pva.atributo_id, ':', pva.atributo_termo_id) ORDER BY pva.atributo_id SEPARATOR '|')
            FROM produto_variacao_atributos pva
            WHERE pva.variacao_id = pv.id)
       ) AS signature,
       COUNT(*) AS total
FROM produto_variacoes pv
GROUP BY pv.produto_id, pv.tenant_id, signature
HAVING total > 1
```

**Resultado:** ✅ **0 linhas** (nenhuma duplicata encontrada)

---

## 2. Exemplo do JSON `window.productVariations` (2 itens)

```json
[
  {
    "variacao_id": 1,
    "signature": "1:5|2:10",
    "price_regular": 99.90,
    "price_promo": 79.90,
    "price_final": 79.90,
    "manage_stock": 1,
    "qty": 5,
    "backorder": "no",
    "image": null,
    "status_estoque": "instock"
  },
  {
    "variacao_id": 2,
    "signature": "1:5|2:11",
    "price_regular": 99.90,
    "price_promo": null,
    "price_final": 99.90,
    "manage_stock": 1,
    "qty": 0,
    "backorder": "no",
    "image": "/uploads/variacao-2.jpg",
    "status_estoque": "outofstock"
  }
]
```

**Onde:**
- `signature`: `"1:5|2:10"` = atributo_id 1 com termo_id 5, atributo_id 2 com termo_id 10
- `price_final`: Usa promoção se houver, senão usa regular
- `qty`: Quantidade em estoque
- `backorder`: "yes" permite pedidos sem estoque, "no" bloqueia

---

## 3. Comportamento Escolhido para "Sem Estoque"

**Resposta:** ✅ **Mostra "Indisponível"** (não esconde a combinação)

### Detalhes do Comportamento:

1. **Combinação permanece visível** - Usuário pode selecionar
2. **Exibe mensagem "Indisponível"** - Em vermelho com ícone ❌
3. **Desabilita botão "Adicionar ao Carrinho"** - `disabled = true`
4. **Define max do input como 0** - Impede digitar quantidade
5. **Validação server-side adicional** - `CartController::add()` também valida

### Código (show.php, linhas 1532-1539):

```javascript
if (variation.qty > 0) {
    stockText = '<i class="bi bi-check-circle-fill icon" style="color: #28a745;"></i> Em estoque';
    // ...
} else {
    if (variation.backorder === 'yes') {
        stockText = '<i class="bi bi-clock icon" style="color: #ff9800;"></i> Sob encomenda';
    } else {
        stockText = '<i class="bi bi-x-circle-fill icon" style="color: #dc3545;"></i> Indisponível';
        stockClass = 'stock-out';
    }
}

// Desabilita botão se não pode adicionar
const canAdd = variation.manage_stock == 0 || variation.qty > 0 || variation.backorder === 'yes';
btnAddCart.disabled = !canAdd;
```

### Justificativa:

- **UX melhor**: Usuário vê que a combinação existe, mas está indisponível
- **Transparência**: Não "esconde" opções do usuário
- **Feedback claro**: Mensagem visual de "Indisponível" é mais informativa
- **Segurança dupla**: Frontend desabilita + backend valida

---

## ✅ Status: Pronto para "OK Final"

Todas as validações passaram:
- ✅ Duplicatas: 0
- ✅ Variações sem atributos: 0
- ✅ Signatures nulas: 0
- ✅ Consistência: 100%
- ✅ Hardenings: Implementados
- ✅ Comportamento: Definido e documentado
