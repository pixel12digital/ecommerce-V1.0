# 📸 Exemplo de Produto com Imagens

Este documento mostra um exemplo real de produto que possui imagem principal (destaque) e imagens de galeria.

## 🎯 Produtos de Exemplo

### Exemplo 1: Produto com Mais Imagens

**ID:** 439  
**Nome:** TÊNIS NIKE PRETO C/ VERDE TM 9.5  
**Slug:** `tenis-nike-preto-c-verde-tm-9-5-9`  
**Imagem Principal:** `/uploads/tenants/1/produtos/main_10119_s-l960.webp`

**Estrutura de Imagens:**
- **Total:** 8 imagens
- **Imagem Principal (main):** 1 imagem
- **Imagens de Galeria (gallery):** 7 imagens

### Exemplo 2: Produto Simples

**ID:** 368  
**Nome:** BLUSA COLUMBIA SURF CINZA TM M  
**Slug:** `blusa-columbia-surf-cinza-tm-m`  
**Imagem Principal:** `/uploads/tenants/1/produtos/main_13873_91gwKUrxIQL._AC_SL1500_.jpg`

**Estrutura de Imagens:**
- **Total:** 3 imagens
- **Imagem Principal (main):** 1 imagem
- **Imagens de Galeria (gallery):** 2 imagens

### Detalhamento das Imagens

| Tipo | Ordem | Caminho do Arquivo | URL Original |
|------|-------|-------------------|--------------|
| `main` | 0 | `/uploads/tenants/1/produtos/main_13873_91gwKUrxIQL._AC_SL1500_.jpg` | (URL original do WooCommerce) |
| `gallery` | 1 | `/uploads/tenants/1/produtos/gallery_13873_91gwKUrxIQL._AC_SL1500_.jpg` | (URL original) |
| `gallery` | 2 | `/uploads/tenants/1/produtos/gallery_13874_91-aucK4JeL._AC_SL1500_.jpg` | (URL original) |

## 🔍 Como Acessar

### Via Loja Pública

**Produto com 8 imagens:**
```
http://localhost/ecommerce-v1.0/public/produto/tenis-nike-preto-c-verde-tm-9-5-9
```

**Produto com 3 imagens:**
```
http://localhost/ecommerce-v1.0/public/produto/blusa-columbia-surf-cinza-tm-m
```

### Via Admin

**Produto ID 439 (8 imagens):**
```
http://localhost/ecommerce-v1.0/public/admin/produtos/439
```

**Produto ID 368 (3 imagens):**
```
http://localhost/ecommerce-v1.0/public/admin/produtos/368
```

## 📝 Estrutura no Banco de Dados

### Tabela `produtos`

```sql
SELECT id, nome, slug, imagem_principal 
FROM produtos 
WHERE id = 368 AND tenant_id = 1;
```

**Resultado:**
- `imagem_principal`: `/uploads/tenants/1/produtos/main_13873_91gwKUrxIQL._AC_SL1500_.jpg`

### Tabela `produto_imagens`

**Produto ID 439 (8 imagens):**
```sql
SELECT tipo, ordem, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = 1 AND produto_id = 439 
ORDER BY tipo = 'main' DESC, ordem ASC;
```

**Produto ID 368 (3 imagens):**
```sql
SELECT tipo, ordem, caminho_arquivo 
FROM produto_imagens 
WHERE tenant_id = 1 AND produto_id = 368 
ORDER BY tipo = 'main' DESC, ordem ASC;
```

**Resultado (ID 368):**
- 1 registro com `tipo = 'main'` (ordem 0)
- 2 registros com `tipo = 'gallery'` (ordem 1 e 2)

## 🖼️ Como as Imagens São Exibidas

### Na Loja Pública (`/produto/{slug}`)

1. **Imagem Principal:** Exibida em destaque (grande)
2. **Miniaturas:** Imagens de galeria exibidas como miniaturas abaixo
3. **Interatividade:** Clique nas miniaturas para trocar a imagem principal

### No Admin (`/admin/produtos/{id}`)

1. **Galeria Completa:** Todas as imagens exibidas em grid
2. **Tipo Identificado:** Cada imagem mostra se é `main` ou `gallery`
3. **Informações Técnicas:** URL original, tamanho, mime_type, etc.

## 🔗 URLs de Acesso Direto às Imagens

### Produto ID 439 (Tênis Nike - 8 imagens)

**Imagem Principal:**
```
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/main_10119_s-l960.webp
```

**Imagens de Galeria:**
```
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/gallery_10119_xxx.webp
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/gallery_10120_xxx.webp
... (7 imagens de galeria)
```

### Produto ID 368 (Blusa Columbia - 3 imagens)

**Imagem Principal:**
```
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/main_13873_91gwKUrxIQL._AC_SL1500_.jpg
```

**Imagens de Galeria:**
```
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/gallery_13873_91gwKUrxIQL._AC_SL1500_.jpg
http://localhost/ecommerce-v1.0/public/uploads/tenants/1/produtos/gallery_13874_91-aucK4JeL._AC_SL1500_.jpg
```

## 📊 Estatísticas Gerais

- **Total de produtos:** 928
- **Produtos com imagens:** 47
- **Total de imagens:** 148
  - Imagens principais: 47
  - Imagens de galeria: 101
- **Produtos com múltiplas imagens:** Vários produtos têm mais de 1 imagem
  - Produto com mais imagens: ID 439 (8 imagens)
  - Outros exemplos: ID 602 (7 imagens), ID 373 (6 imagens)

## 🔍 Buscar Outros Produtos com Imagens

### Produtos com mais imagens

```sql
SELECT p.id, p.nome, COUNT(pi.id) as total_imagens
FROM produtos p
INNER JOIN produto_imagens pi ON pi.produto_id = p.id AND pi.tenant_id = p.tenant_id
WHERE p.tenant_id = 1
GROUP BY p.id
HAVING total_imagens > 1
ORDER BY total_imagens DESC
LIMIT 10;
```

### Produtos com imagem principal

```sql
SELECT id, nome, imagem_principal
FROM produtos
WHERE tenant_id = 1
AND imagem_principal IS NOT NULL
AND imagem_principal != ''
LIMIT 10;
```

---

**Última atualização:** 05/12/2024

