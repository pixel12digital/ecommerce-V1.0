# 📦 Guia Completo de Importação de Produtos - Para Desenvolvedor

**Versão:** 1.0  
**Data:** Dezembro 2024  
**Projeto:** E-commerce Multi-tenant v1.0

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura de Dados](#estrutura-de-dados)
3. [Pré-requisitos](#pré-requisitos)
4. [Processo de Importação](#processo-de-importação)
5. [Estrutura de Imagens](#estrutura-de-imagens)
6. [Scripts Disponíveis](#scripts-disponíveis)
7. [Estrutura do Banco de Dados](#estrutura-do-banco-de-dados)
8. [Troubleshooting](#troubleshooting)
9. [Exemplos e Casos de Uso](#exemplos-e-casos-de-uso)

---

## 🎯 Visão Geral

Este documento descreve o processo completo de importação de produtos do WooCommerce para o sistema e-commerce multi-tenant. O sistema suporta:

- ✅ Importação de **928 produtos** completos
- ✅ **148 imagens** (47 principais + 101 de galeria)
- ✅ Categorias, tags e metadados
- ✅ Prevenção de duplicatas (idempotência)
- ✅ Suporte multi-tenant

### Arquivos de Importação

A pasta de exportação contém:

```
exportacao-produtos-2025-12-05_11-36-53/
├── produtos-completo.json      # ⭐ Arquivo principal (928 produtos)
├── estatisticas.json           # Estatísticas da exportação
├── produtos-resumo.csv         # Resumo em CSV
├── images/                     # ⭐ Pasta com 147 imagens
│   ├── main_13873_*.jpg       # Imagens principais
│   └── gallery_10119_*.webp   # Imagens de galeria
└── GUIA-COMPLETO-DESENVOLVEDOR.md  # Documentação original
```

---

## 📊 Estrutura de Dados

### Formato do JSON

O arquivo `produtos-completo.json` é um **array JSON** contendo objetos de produtos. Cada produto segue esta estrutura:

```json
{
    "id": 15328,
    "name": "BLUSA OLD NAVY AZUL MARINHO TM XL",
    "slug": "blusa-old-navy-azul-marinho-tm-xl-3",
    "sku": "236",
    "type": "simple",
    "status": "publish",
    "featured": false,
    
    "price": "190",
    "regular_price": "190",
    "sale_price": "",
    "date_on_sale_from": null,
    "date_on_sale_to": null,
    
    "manage_stock": true,
    "stock_quantity": 1,
    "stock_status": "instock",
    "backorders": "no",
    
    "weight": "",
    "length": "",
    "width": "",
    "height": "",
    
    "description": "",
    "short_description": "",
    
    "images": {
        "main": {
            "id": "13873",
            "url_original": "http://...",
            "local_path": "images/main_13873_91gwKUrxIQL._AC_SL1500_.jpg",
            "alt": "",
            "title": "...",
            "mime_type": "image/jpeg"
        },
        "gallery": [
            {
                "id": 13873,
                "url_original": "http://...",
                "local_path": "images/gallery_13873_91gwKUrxIQL._AC_SL1500_.jpg",
                "alt": "",
                "title": "...",
                "mime_type": "image/jpeg"
            }
        ]
    },
    
    "categories": [
        {
            "id": 56,
            "name": "Array",
            "slug": "array",
            "description": "",
            "parent": 0
        }
    ],
    
    "tags": [],
    "custom_meta": {}
}
```

### ⚠️ Importante: Estrutura de Imagens

As imagens estão organizadas em:
- `images.main` (objeto) - Imagem principal
- `images.gallery` (array) - Imagens de galeria

**Campo crítico:** `local_path` - caminho relativo à pasta `images/`

---

## ✅ Pré-requisitos

### 1. Banco de Dados

Certifique-se de que as migrations foram executadas:

```bash
php database/run_migrations.php
```

**Tabelas necessárias:**
- `produtos`
- `produto_imagens`
- `categorias`
- `produto_categorias`
- `tags`
- `produto_tags`
- `produto_meta`

### 2. Configuração

Verifique o arquivo `.env`:

```env
APP_MODE=single
DEFAULT_TENANT_ID=1

DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecommerce_db
DB_USER=root
DB_PASS=
```

### 3. Pasta de Exportação

A pasta `exportacao-produtos-2025-12-05_11-36-53/` deve estar na **raiz do projeto**.

### 4. Configuração de Caminhos

O arquivo `config/paths.php` deve conter:

```php
return [
    'exportacao_produtos_path' => ROOT_PATH . '/exportacao-produtos-2025-12-05_11-36-53',
    'uploads_produtos_base_path' => ROOT_PATH . '/public/uploads/tenants',
];
```

---

## 🚀 Processo de Importação

### Passo 1: Verificar Migrations

```bash
php database/run_migrations.php
```

**Saída esperada:**
```
✓ Migrations aplicadas: X
✓ Nenhuma migration pendente
```

### Passo 2: Importar Produtos

```bash
php database/import_products.php
```

**O que o script faz:**
1. Resolve o tenant (via `APP_MODE` e `DEFAULT_TENANT_ID`)
2. Verifica se já existem produtos (aviso se houver)
3. Lê o arquivo JSON
4. Importa na ordem:
   - Categorias
   - Tags
   - Produtos
   - Imagens (copia arquivos físicos)
   - Relacionamentos (produto-categoria, produto-tag)
   - Metadados

**Saída esperada:**
```
Importando para tenant: Loja Demo (ID: 1)

Lendo arquivo JSON...
Total de produtos encontrados no JSON: 928

Coletando categorias e tags...
Categorias únicas encontradas: 7
Tags únicas encontradas: 0

Importando categorias...
✓ Categorias processadas: 7 (inseridas: 7, já existiam: 0)

Importando tags...
✓ Tags processadas: 0 (inseridas: 0, já existiam: 0)

Importando produtos...
Processando produto 928/928 - ID WP: 24709

============================================================
IMPORTAÇÃO CONCLUÍDA!
============================================================

Resumo:
  Produtos processados: 928
    ✓ Inseridos: 928
    ⊘ Pulados (já existiam): 0
    ✗ Erros: 0

  Categorias: 7 (inseridas: 7, já existiam: 0)
  Tags: 0 (inseridas: 0, já existiam: 0)

  Total de produtos no tenant após importação: 928
============================================================
```

### Passo 3: Importar Imagens (se necessário)

Se os produtos já existem mas as imagens não foram importadas:

```bash
php database/import_images_only.php
```

**Saída esperada:**
```
Importando imagens para tenant: Loja Demo (ID: 1)

Lendo arquivo JSON...
Total de produtos encontrados no JSON: 928

Produtos encontrados no banco: 928

Importando imagens...
Processando produto 928/928 - ID WP: 24709

============================================================
IMPORTAÇÃO DE IMAGENS CONCLUÍDA!
============================================================

Resumo:
  Produtos processados: 928
  Produtos com imagens: 47
  Imagens copiadas: 148
  Imagens registradas: 148
  Erros: 0

Total de imagens no banco após importação: 148
Total de produtos com imagem_principal: 47
============================================================
```

---

## 🖼️ Estrutura de Imagens

### Organização

As imagens são copiadas de:
```
exportacao-produtos-2025-12-05_11-36-53/images/
```

Para:
```
public/uploads/tenants/{tenant_id}/produtos/
```

### Nomenclatura

- **Imagens principais:** `main_{id_wp}_{filename}`
  - Exemplo: `main_13873_91gwKUrxIQL._AC_SL1500_.jpg`

- **Imagens de galeria:** `gallery_{id_wp}_{filename}`
  - Exemplo: `gallery_10119_s-l960.webp`

### Registro no Banco

**Tabela `produto_imagens`:**
- `tipo`: `'main'` ou `'gallery'`
- `caminho_arquivo`: `/uploads/tenants/{tenant_id}/produtos/{filename}`
- `ordem`: ordem de exibição

**Tabela `produtos`:**
- `imagem_principal`: preenchido automaticamente com o caminho da imagem principal

### Estatísticas

- **Total de imagens:** 148
  - 47 imagens principais (`main`)
  - 101 imagens de galeria (`gallery`)
- **Produtos com imagens:** 47 produtos
- **Arquivos físicos:** 147 arquivos

---

## 📜 Scripts Disponíveis

### 1. `database/import_products.php`

Script principal de importação.

**Funcionalidades:**
- Importa produtos, categorias, tags, imagens e relacionamentos
- Previne duplicatas (verifica por `id_original_wp`)
- Copia imagens físicas
- Atualiza `imagem_principal` dos produtos

**Uso:**
```bash
php database/import_products.php
```

**Idempotência:**
- Pode ser executado múltiplas vezes
- Produtos existentes são pulados (não duplicados)

### 2. `database/import_images_only.php`

Script para importar apenas imagens de produtos já existentes.

**Uso:**
```bash
php database/import_images_only.php
```

**Quando usar:**
- Produtos já foram importados
- Imagens não foram importadas ou foram perdidas
- Precisa atualizar apenas as imagens

### 3. `public/check_products.php`

Script de verificação (acessível via browser).

**URL:**
```
http://localhost/ecommerce-v1.0/public/check_products.php
```

**Mostra:**
- Total de produtos por status
- Total de imagens
- Produtos com imagens
- Estatísticas gerais

---

## 🗄️ Estrutura do Banco de Dados

### Tabela `produtos`

**Campos principais:**
- `id` (PK)
- `tenant_id` (FK → tenants)
- `id_original_wp` (ID do WooCommerce)
- `nome`, `slug`, `sku`
- `preco`, `preco_regular`, `preco_promocional`
- `quantidade_estoque`, `status_estoque`
- `imagem_principal` (caminho relativo)
- `descricao`, `descricao_curta`
- `status` (publish, draft, private)
- `destaque` (0 ou 1)

**Índices:**
- `idx_produtos_tenant` (tenant_id)
- `idx_produtos_tenant_slug` (tenant_id, slug)
- `idx_produtos_tenant_sku` (tenant_id, sku)

### Tabela `produto_imagens`

**Campos:**
- `id` (PK)
- `tenant_id`, `produto_id` (FK → produtos)
- `tipo` (ENUM: 'main', 'gallery')
- `ordem` (INT)
- `caminho_arquivo` (VARCHAR)
- `url_original`, `alt_text`, `titulo`
- `mime_type`, `tamanho_arquivo`

**Índices:**
- `idx_produto_imagens_tenant_produto` (tenant_id, produto_id)

### Tabela `categorias`

**Campos:**
- `id` (PK)
- `tenant_id`
- `id_original_wp`
- `nome`, `slug`, `descricao`
- `categoria_pai_id` (FK → categorias, nullable)

### Tabela `produto_categorias`

**Tabela de relação N:N:**
- `tenant_id`, `produto_id`, `categoria_id`
- PRIMARY KEY (tenant_id, produto_id, categoria_id)

### Tabela `tags` e `produto_tags`

Similar às categorias, com tabela de relação `produto_tags`.

### Tabela `produto_meta`

**Campos:**
- `id` (PK)
- `tenant_id`, `produto_id`
- `chave` (VARCHAR)
- `valor` (TEXT)

---

## 🔧 Troubleshooting

### Problema 1: "Arquivo JSON não encontrado"

**Erro:**
```
ERRO: Arquivo não encontrado: exportacao-produtos-2025-12-05_11-36-53/produtos-completo.json
```

**Solução:**
1. Verifique se a pasta existe na raiz do projeto
2. Verifique o caminho em `config/paths.php`
3. Confirme o nome da pasta (pode variar)

### Problema 2: "Imagens não foram importadas"

**Sintomas:**
- Produtos importados, mas sem imagens
- Tabela `produto_imagens` vazia

**Solução:**
```bash
php database/import_images_only.php
```

### Problema 3: "Produtos duplicados"

**Sintomas:**
- Produtos aparecem múltiplas vezes

**Solução:**
O script já previne duplicatas verificando `id_original_wp`. Se houver duplicatas:
1. Verifique se `id_original_wp` está sendo preenchido corretamente
2. Limpe produtos duplicados manualmente no banco
3. Re-execute o import (ele pulará os existentes)

### Problema 4: "Imagens não aparecem na interface"

**Sintomas:**
- Imagens no banco, mas não aparecem no admin/loja

**Solução:**
1. Verifique se os arquivos físicos existem em `public/uploads/tenants/{tenant_id}/produtos/`
2. Verifique o caminho em `produto_imagens.caminho_arquivo`
3. Verifique permissões da pasta (deve ser 755)
4. Verifique se `$basePath` está correto nas views

### Problema 5: "Erro SQL: Invalid parameter number"

**Sintomas:**
- Erro ao filtrar produtos no admin

**Solução:**
Já corrigido no código. Se persistir:
1. Verifique a versão do PHP (deve ser 8.x)
2. Verifique se o PDO está configurado corretamente
3. Limpe cache do navegador

---

## 📝 Exemplos e Casos de Uso

### Exemplo 1: Produto com Múltiplas Imagens

**Produto ID:** 439  
**Nome:** TÊNIS NIKE PRETO C/ VERDE TM 9.5  
**Slug:** `tenis-nike-preto-c-verde-tm-9-5-9`

**Estrutura de Imagens:**
- 1 imagem principal (`main`)
- 7 imagens de galeria (`gallery`)
- Total: 8 imagens

**Acessar:**
- Admin: `http://localhost/ecommerce-v1.0/public/admin/produtos/439`
- Loja: `http://localhost/ecommerce-v1.0/public/produto/tenis-nike-preto-c-verde-tm-9-5-9`

### Exemplo 2: Verificar Produtos Importados

**SQL:**
```sql
-- Total de produtos
SELECT COUNT(*) FROM produtos WHERE tenant_id = 1;

-- Produtos com imagens
SELECT COUNT(DISTINCT produto_id) 
FROM produto_imagens 
WHERE tenant_id = 1;

-- Imagens por tipo
SELECT tipo, COUNT(*) 
FROM produto_imagens 
WHERE tenant_id = 1 
GROUP BY tipo;

-- Produto específico com imagens
SELECT p.id, p.nome, COUNT(pi.id) as total_imagens
FROM produtos p
LEFT JOIN produto_imagens pi ON pi.produto_id = p.id AND pi.tenant_id = p.tenant_id
WHERE p.tenant_id = 1 AND p.id = 439
GROUP BY p.id;
```

### Exemplo 3: Buscar Produto por ID Original WP

```sql
SELECT * FROM produtos 
WHERE tenant_id = 1 
AND id_original_wp = 9902;
```

### Exemplo 4: Listar Produtos com Imagens

```sql
SELECT p.id, p.nome, p.imagem_principal, COUNT(pi.id) as total_imagens
FROM produtos p
INNER JOIN produto_imagens pi ON pi.produto_id = p.id AND pi.tenant_id = p.tenant_id
WHERE p.tenant_id = 1
GROUP BY p.id
HAVING total_imagens > 0
ORDER BY total_imagens DESC
LIMIT 10;
```

---

## ✅ Checklist de Importação

Antes de enviar para produção, verifique:

- [ ] Migrations executadas com sucesso
- [ ] Arquivo JSON existe e está acessível
- [ ] Pasta `images/` contém os arquivos
- [ ] Script de importação executado sem erros
- [ ] Total de produtos no banco = 928
- [ ] Total de imagens no banco = 148
- [ ] Produtos com `imagem_principal` = 47
- [ ] Arquivos físicos copiados para `public/uploads/tenants/1/produtos/`
- [ ] Imagens aparecem no admin (`/admin/produtos`)
- [ ] Imagens aparecem na loja (`/produto/{slug}`)
- [ ] Descrições renderizam HTML corretamente
- [ ] Filtros funcionam no admin

---

## 📞 Suporte

Em caso de dúvidas ou problemas:

1. Verifique os logs em `storage/logs/`
2. Execute `public/check_products.php` para diagnóstico
3. Consulte `docs/IMPORTACAO_PRODUTOS_PONTO_DO_GOLFE.md` para detalhes técnicos
4. Consulte `docs/EXEMPLO_PRODUTO_COM_IMAGENS.md` para exemplos

---

## 📚 Documentação Relacionada

- `docs/IMPORTACAO_PRODUTOS_PONTO_DO_GOLFE.md` - Documentação técnica completa
- `docs/IMPORTACAO_IMAGENS_CONCLUIDA.md` - Detalhes da importação de imagens
- `docs/EXEMPLO_PRODUTO_COM_IMAGENS.md` - Exemplos de produtos com imagens
- `docs/ACESSOS_E_URLS.md` - URLs e acessos do sistema

---

**Última atualização:** Dezembro 2024  
**Versão do sistema:** 1.0  
**Status:** ✅ Produção



