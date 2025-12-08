# Importação de Produtos - Ponto do Golfe

Este documento descreve o processo de importação dos produtos WooCommerce para o sistema e-commerce multi-tenant.

## 📋 Pré-requisitos

> ⚡ **Primeira vez configurando?** Consulte o guia [Configuração Inicial Rápida](CONFIGURACAO_INICIAL_RAPIDA.md) para criar o banco e o arquivo `.env`.

1. **Estrutura de pastas:**
   - A pasta `exportacao-produtos/` deve estar na raiz do projeto
   - Dentro dela deve conter:
     - `produtos-completo.json` - Arquivo JSON com todos os produtos
     - `images/` - Pasta com todas as imagens dos produtos

2. **Banco de dados:**
   - Banco `ecommerce_db` criado (veja [Configuração Inicial Rápida](CONFIGURACAO_INICIAL_RAPIDA.md))
   - Arquivo `.env` configurado (já criado automaticamente)
   - Migrations executadas (incluindo as novas migrations de catálogo)

3. **Configuração:**
   - Arquivo `.env` configurado com:
     - `APP_MODE` (multi ou single)
     - `DEFAULT_TENANT_ID` (ID do tenant que receberá os produtos)
     - Configurações do banco de dados (`DB_HOST`, `DB_NAME`, etc.)

## 🚀 Resumo Rápido

**Primeira vez executando?**
1. Execute migrations: `php database/run_migrations.php`
2. Execute importação: `php database/import_products.php`

**Já executou antes?**
- Migrations: Não precisa rodar novamente (são idempotentes)
- Importação: Não precisa rodar novamente (evita duplicatas automaticamente)

**Quer rodar mesmo assim?**
- Migrations: Pode rodar, só aplicará o que falta
- Importação: Pode rodar, pulará produtos já existentes

---

## 🚀 Passo a Passo Detalhado

### 1. Preparar a Estrutura de Pastas

Certifique-se de que a pasta `exportacao-produtos/` está na raiz do projeto:

```
ecommerce-v1.0/
├── exportacao-produtos/
│   ├── produtos-completo.json
│   └── images/
│       ├── imagem1.jpg
│       ├── imagem2.jpg
│       └── ...
```

**Nota:** Se você tem a pasta com nome diferente (ex.: `exportacao-produtos-2025-12-05_11-36-53`), renomeie para `exportacao-produtos` ou ajuste o caminho em `config/paths.php`.

### 2. Verificar Configuração do .env

Abra o arquivo `.env` e verifique:

```env
APP_MODE=single
DEFAULT_TENANT_ID=1

DB_HOST=localhost
DB_PORT=3306
DB_NAME=ecommerce_db
DB_USER=root
DB_PASS=
```

- **APP_MODE=single:** Modo single-tenant (uma loja isolada)
- **APP_MODE=multi:** Modo multi-tenant (várias lojas)
- **DEFAULT_TENANT_ID:** ID do tenant que receberá os produtos

### 3. Executar Migrations

**⚠️ IMPORTANTE:** 

- **Se você já executou `php database/run_migrations.php` anteriormente** e as tabelas já existem no banco, **não é necessário rodar novamente**. As migrations são idempotentes e só aplicam o que ainda não foi aplicado.

- **Se você ainda não executou**, rode:

```bash
php database/run_migrations.php
```

Isso criará as seguintes tabelas (se ainda não existirem):
- `produtos` - Tabela principal de produtos
- `produto_imagens` - Imagens dos produtos
- `categorias` - Categorias de produtos
- `produto_categorias` - Relação N:N produtos-categorias
- `tags` - Tags de produtos
- `produto_tags` - Relação N:N produtos-tags
- `produto_meta` - Metadados customizados dos produtos

**Nota:** O script mostrará "Nenhuma migration pendente" se todas já foram aplicadas. Isso é normal e esperado.

### 4. Executar Importação

**⚠️ IMPORTANTE:**

- **Se os produtos já foram importados anteriormente** (por exemplo, você rodou este comando antes), **não é obrigatório rodar novamente**. O script faz checagens automáticas para evitar duplicatas, verificando por `id_original_wp`. Se você rodar de novo, ele irá pular produtos já existentes.

- **Se os produtos ainda não foram importados**, execute:

```bash
php database/import_products.php
```

**Para modo multi-tenant com tenant específico:**

```bash
php database/import_products.php --tenant=2
```

O script irá:
1. Verificar se já existem produtos no tenant (mostra aviso se existirem)
2. Carregar o arquivo JSON
3. Resolver o tenant alvo
4. Importar categorias (pulando as que já existem)
5. Importar tags (pulando as que já existem)
6. Importar produtos (pulando os que já existem)
7. Copiar imagens para `public/uploads/tenants/{tenant_id}/produtos/`
8. Criar relacionamentos (produto-categoria, produto-tag)
9. Importar metadados

**Nota:** O script mostrará claramente quantos produtos foram inseridos vs quantos foram pulados por já existirem.

### 5. Acompanhar o Progresso

Durante a importação, você verá mensagens como:

```
Importando para tenant: Loja Demo (ID: 1)

Lendo arquivo JSON...
Total de produtos encontrados: 928

Coletando categorias e tags...
Categorias únicas encontradas: 45
Tags únicas encontradas: 12

Importando categorias...
✓ Categorias importadas: 45

Importando tags...
✓ Tags importadas: 12

Importando produtos...
Importando produto 928/928 - ID WP: 12345

========================================
Importação concluída!
========================================
Produtos importados: 928
Erros: 0
Categorias: 45
Tags: 12
```

### 6. Verificar Resultados

Após a importação, verifique no banco de dados:

```sql
-- Contar produtos importados
SELECT COUNT(*) FROM produtos WHERE tenant_id = 1;

-- Ver algumas categorias
SELECT * FROM categorias WHERE tenant_id = 1 LIMIT 10;

-- Ver algumas tags
SELECT * FROM tags WHERE tenant_id = 1 LIMIT 10;

-- Verificar imagens
SELECT COUNT(*) FROM produto_imagens WHERE tenant_id = 1;

-- Verificar relacionamentos
SELECT COUNT(*) FROM produto_categorias WHERE tenant_id = 1;
SELECT COUNT(*) FROM produto_tags WHERE tenant_id = 1;
```

Verifique também se as imagens foram copiadas:

```
public/uploads/tenants/1/produtos/
├── imagem1.jpg
├── imagem2.jpg
└── ...
```

## 📊 Estrutura dos Dados Importados

### Produtos

Cada produto importado contém:
- Informações básicas (nome, slug, SKU, tipo, status)
- Preços (preço, preço regular, preço promocional)
- Estoque (gerenciamento, quantidade, status)
- Dimensões (peso, comprimento, largura, altura)
- Descrições (completa e curta)
- Flags (destaque, visibilidade, status de imposto)
- Datas (criação, modificação)
- Referência ao WooCommerce original (`id_original_wp`)

### Categorias

- Nome, slug, descrição
- Relacionamento hierárquico (categoria pai)
- Referência ao WooCommerce original

### Tags

- Nome e slug
- Referência ao WooCommerce original

### Imagens

- Tipo (main ou gallery)
- Ordem
- Caminho relativo no sistema
- URL original do WooCommerce
- Metadados (alt text, título, legenda, mime type, tamanho)

### Relacionamentos

- **produto_categorias:** Relação N:N entre produtos e categorias
- **produto_tags:** Relação N:N entre produtos e tags

### Metadados

- **produto_meta:** Metadados customizados do WooCommerce (chave/valor)

## ⚠️ Executando Mais de Uma Vez

### Migrations são Idempotentes

As migrations são **idempotentes**, ou seja:
- Você pode executar `php database/run_migrations.php` quantas vezes quiser
- O sistema só aplica migrations que ainda não foram aplicadas
- Se todas já foram aplicadas, o script mostrará "Nenhuma migration pendente"
- **Não há risco de duplicar tabelas ou quebrar o banco**

### Importação Evita Duplicatas

O script de importação **evita duplicatas automaticamente**:
- **Produtos:** Verifica por `id_original_wp` antes de inserir
- **Categorias:** Verifica por `id_original_wp` e `slug` antes de inserir
- **Tags:** Verifica por `id_original_wp` e `slug` antes de inserir
- Se um item já existe, ele é **pulado** (não duplica)

**Recomendação:**
- **Ideal:** Importar apenas uma vez por tenant
- **Se rodar novamente:** O script funcionará normalmente, mas pulará itens já existentes
- **Logs:** O script mostra claramente quantos itens foram inseridos vs pulados

**Exemplo de saída ao rodar novamente:**
```
⚠️  ATENÇÃO: Já existem 928 produtos no tenant 'Loja Demo' (ID: 1).
   Se você já importou antes, não é necessário rodar novamente.
   O script irá pular produtos já existentes (verificando por id_original_wp).
   Continuando mesmo assim...

[...]

Resumo:
  Produtos processados: 928
    ✓ Inseridos: 0
    ⊘ Pulados (já existiam): 928
    ✗ Erros: 0
```

## ⚠️ Observações Importantes

### Modo Single-tenant

No modo `APP_MODE=single`:
- Todos os produtos são importados para o tenant definido em `DEFAULT_TENANT_ID`
- Não é necessário especificar `--tenant` na linha de comando

### Modo Multi-tenant

No modo `APP_MODE=multi`:
- Por padrão, usa `DEFAULT_TENANT_ID`
- Para importar para outro tenant, use: `php database/import_products.php --tenant=2`
- Certifique-se de que o tenant existe antes de importar

### Imagens Não Encontradas

Se uma imagem não for encontrada na pasta `exportacao-produtos/images/`:
- O script registra um aviso mas continua
- O registro da imagem é criado no banco mesmo sem o arquivo físico
- Você pode copiar as imagens manualmente depois

### Performance

- O script usa transações por produto para garantir integridade
- Para grandes volumes (928 produtos), a importação pode levar alguns minutos
- O progresso é exibido em tempo real

## 🔧 Troubleshooting

### Erro: "Arquivo não encontrado"

**Solução:** Verifique se a pasta `exportacao-produtos/` existe na raiz do projeto e contém `produtos-completo.json`.

### Erro: "Tenant não encontrado"

**Solução:** Verifique se o tenant existe no banco e se `DEFAULT_TENANT_ID` está correto no `.env`.

### Erro: "Tabela não existe"

**Solução:** Execute as migrations primeiro: `php database/run_migrations.php`

### Imagens não estão sendo copiadas

**Solução:** 
- Verifique se a pasta `exportacao-produtos/images/` existe e contém as imagens
- Verifique permissões de escrita na pasta `public/uploads/tenants/`
- Verifique os logs do script para ver quais imagens não foram encontradas

### Produtos duplicados

**Solução:** O script já trata duplicatas. Se ainda assim houver duplicatas, verifique se há produtos com `id_original_wp` duplicado no JSON.

## 📝 Estrutura do JSON Esperado

O arquivo `produtos-completo.json` deve ser um array de objetos, cada objeto representando um produto:

```json
[
    {
        "id": 15328,
        "name": "Nome do Produto",
        "slug": "nome-do-produto",
        "sku": "123",
        "type": "simple",
        "status": "publish",
        "price": "190",
        "regular_price": "190",
        "sale_price": "",
        "categories": [
            {
                "id": 56,
                "name": "Categoria",
                "slug": "categoria",
                "parent": 0
            }
        ],
        "tags": [],
        "images": [
            {
                "src": "https://...",
                "alt": "Alt text",
                "name": "Nome da imagem"
            }
        ],
        "custom_meta": {},
        ...
    }
]
```

## ✅ Critérios de Sucesso

A importação é considerada bem-sucedida quando:

1. ✅ O script executa sem erros fatais
2. ✅ ~928 produtos estão na tabela `produtos` para o tenant alvo
3. ✅ Categorias e tags estão populadas
4. ✅ Relacionamentos produto-categoria e produto-tag estão criados
5. ✅ Imagens foram copiadas para `public/uploads/tenants/{tenant_id}/produtos/`
6. ✅ Registros em `produto_imagens` referenciam as imagens corretamente
7. ✅ Campo `imagem_principal` em `produtos` está preenchido
8. ✅ Metadados customizados foram importados (se existirem)

## 📚 Referências

- [Arquitetura E-commerce Multi-tenant](ARQUITETURA_ECOMMERCE_MULTITENANT.md)
- [Sistema de Migrations](ATUALIZACOES_E_VERSOES.md)

