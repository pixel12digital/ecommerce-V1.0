# Diagnóstico: Erro 400 "Nenhum atributo marcado para variação"

## Resumo

**Causa raiz:** O endpoint `POST /admin/produtos/{id}/variacoes/gerar` **lê exclusivamente do banco de dados**. Não recebe payload. Se o usuário marcou atributos/termos na tela mas **não clicou em "Salvar Atributos"** antes, o banco está vazio → 400.

---

## 1. Fluxo esperado (confirmado)

| Endpoint | Payload | Fonte dos dados |
|----------|---------|-----------------|
| `.../variacoes/gerar` | **Nenhum** (POST vazio) | Banco: `produto_atributos`, `produto_atributo_termos` |
| `.../atributos/salvar-e-gerar-variacoes` | FormData (atributos, termos, flags) | Formulário → salva no banco → gera |

**Conclusão:** O botão "Gerar Variações" que chama `variacoes/gerar` **exige** que o usuário tenha clicado em "Salvar Atributos" antes (ou use "Salvar e Gerar Variações").

---

## 2. Persistência (tabelas)

- **produto_atributos**: `produto_id`, `atributo_id`, `usado_para_variacao` (0 ou 1), `tenant_id`
- **produto_atributo_termos**: `produto_id`, `atributo_id`, `atributo_termo_id`, `tenant_id`

Após "Salvar Atributos", verificar:
```sql
SELECT * FROM produto_atributos WHERE produto_id = 954 AND tenant_id = ?;
SELECT * FROM produto_atributo_termos WHERE produto_id = 954 AND tenant_id = ?;
```

---

## 3. Request do POST variacoes/gerar

- **Método:** POST
- **Body:** vazio (ou `{}` se Content-Type: application/json)
- **Headers:** `X-Requested-With: XMLHttpRequest`
- **Response 400:** `{"success":false,"message":"Nenhum atributo marcado para variação..."}`

O erro vem de `ProductController::generateVariations()` linha ~2568: query em `produto_atributos` com `usado_para_variacao = 1` retorna vazio.

---

## 4. Frontend – coleta

- **Inputs:** `atributos[]`, `atributos_para_variacao[ID]`, `atributo_ID_termos[]`
- **Botão "Gerar Variações":** chama `fetch(.../variacoes/gerar)` **sem enviar** esses dados
- **Botão "Salvar e Gerar Variações":** chama `fetch(.../atributos/salvar-e-gerar-variacoes)` com `collectAttributesData()` (FormData)

---

## 5. Correção aplicada

**UX:** Ao clicar em "Gerar Variações", se houver termos selecionados no formulário (alterações não salvas), o botão passa a chamar `salvar-e-gerar-variacoes` em vez de `variacoes/gerar`, salvando e gerando em um único passo.
