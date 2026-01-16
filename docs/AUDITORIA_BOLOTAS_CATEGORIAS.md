# Auditoria de Bolotas de Categorias - Relatório Completo

**Data:** 2025-01-27  
**Objetivo:** Identificar problemas nas bolotas (carrossel de categorias) onde categorias pai apontam para páginas vazias porque produtos estão nas subcategorias.

---

## 📍 PARTE A - MAPEAMENTO DA IMPLEMENTAÇÃO ATUAL

### 1. Onde estão as Bolotas (Carrossel de Categorias)

#### Frontend - Renderização
**Arquivo:** `themes/default/storefront/partials/category-strip.php`

- **Linha 22:** Link gerado: `href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($pill['categoria_slug']) ?>"`
- Cada bolota usa o `categoria_slug` da categoria associada
- Os dados vêm da variável `$categoryPills` passada pelo controller

#### Backend - Geração dos Dados
**Arquivo:** `src/Http/Controllers/Storefront/HomeController.php`

- **Linhas 110-121:** Query que busca as bolotas:
  ```php
  SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
  FROM home_category_pills hcp
  LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
  WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
  ORDER BY hcp.ordem ASC, hcp.id ASC
  ```
- **Fonte de dados:** Tabela `home_category_pills` que referencia `categorias.id`
- **Identificador usado:** `categoria_slug` (passado via query string `?categoria=slug`)

### 2. Filtro de Produtos por Categoria

#### Rota/Endpoint
**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`

- **Método `index()`:** Lista todos os produtos (sem filtro de categoria)
- **Método `category(string $slugCategoria)`:** Lista produtos de uma categoria específica via slug na URL
- **Método privado `renderProductList()`:** Lógica comum de filtragem

#### Query de Filtro Atual
**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`  
**Linhas 74-89:**

```php
// Filtro por categoria
if ($categoriaId !== null) {
    // Caso 1: Categoria passada via rota /produtos/categoria/slug
    $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
    $joins[] = "INNER JOIN categorias c ON c.id = pc.categoria_id AND c.tenant_id = :tenant_id_c AND c.id = :categoria_id";
    // ...
} elseif (!empty($_GET['categoria'])) {
    // Caso 2: Categoria passada via query string ?categoria=slug (usado pelas bolotas)
    $categoriaSlug = $_GET['categoria'];
    $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
    $joins[] = "INNER JOIN categorias c ON c.id = pc.categoria_id AND c.tenant_id = :tenant_id_c AND c.slug = :categoria_slug";
    // ...
}
```

**🔴 PROBLEMA IDENTIFICADO:**

A query atual faz `JOIN` apenas com produtos que estão **diretamente** na categoria informada. **Não inclui produtos das subcategorias (filhos)**.

**Estrutura de dados:**
- Tabela `categorias` tem campo `categoria_pai_id` (NULL = categoria pai)
- Tabela `produto_categorias` é pivot (N:N) entre produtos e categorias
- Um produto pode estar em múltiplas categorias, mas não há herança automática

### 3. Como o Backend Trata Categoria Pai vs Subcategoria

**Resposta:** Atualmente **NÃO há tratamento especial**. Quando o slug é de uma categoria pai:
- ✅ Busca a categoria pelo slug
- ❌ Busca produtos apenas nessa categoria
- ❌ **NÃO inclui produtos das subcategorias**

**Exemplo do problema:**
```
Categoria: "Calças" (id: 3, slug: "calcas", categoria_pai_id: NULL)
  ├── Subcategoria: "Calças Femininas" (id: 10, slug: "calcas-femininas", categoria_pai_id: 3)
  └── Subcategoria: "Calças Masculinas" (id: 11, slug: "calcas-masculinas", categoria_pai_id: 3)

Produtos:
- Produto A → categoria_id: 10 (Calças Femininas)
- Produto B → categoria_id: 11 (Calças Masculinas)
- Produto C → categoria_id: 3 (Calças - diretamente no pai)

Resultado ao clicar na bolota "Calças":
- Query atual: Retorna apenas Produto C
- Esperado: Retornar Produto A + B + C (incluir filhos)
```

---

## 🔍 PARTE B - AUDITORIA AUTOMÁTICA

### Script de Auditoria Criado

**Arquivo:** `public/auditoria_bolotas_categorias.php`

#### Funcionalidades

Para cada bolota ativa, o script verifica:
1. ✅ Se a categoria existe no banco
2. ✅ Quantidade de produtos diretamente na categoria
3. ✅ Quantidade de subcategorias (filhos)
4. ✅ Quantidade de produtos nas subcategorias
5. ✅ Calcula total (direto + filhos)
6. ✅ Classifica o status:
   - `OK_DIRETO`: Categoria tem produtos próprios
   - `OK_FILHOS`: Categoria pai sem produtos próprios, mas filhos têm (⚠️ **PROBLEMA**)
   - `VAZIO`: Categoria e filhos não têm produtos
   - `INCONSISTENTE`: Bolota aponta para categoria inexistente

#### Como Executar

**Via Web:**
```
http://seu-dominio.com/auditoria_bolotas_categorias.php?tenant_id=1&format=html
```

**Via CLI:**
```bash
php public/auditoria_bolotas_categorias.php --tenant-id=1 --format=console
```

**Formato JSON:**
```
http://seu-dominio.com/auditoria_bolotas_categorias.php?tenant_id=1&format=json
```

#### Saída Esperada

O script gera um relatório detalhado com:
- Resumo por status (quantas bolotas em cada categoria)
- Tabela completa com todas as bolotas
- Para cada bolota:
  - ID, label, ordem
  - Informações da categoria (nome, slug, se é pai/filho)
  - Contadores de produtos (direto, filhos, total)
  - Status e motivo
  - URL que será gerada no frontend

---

## 🔧 PARTE C - PROPOSTAS DE CORREÇÃO

### Correção 1: Ajustar Query para Incluir Subcategorias (Backend)

**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`  
**Método:** `renderProductList()`

#### Opção A: Sempre Incluir Descendentes (Recomendada)

**Vantagem:** Comportamento consistente - categoria pai sempre mostra produtos do pai + filhos.

**Implementação:**

Modificar a lógica do filtro por categoria (linhas 74-89) para:

1. Buscar a categoria pelo slug/ID
2. Verificar se tem subcategorias
3. Se tiver subcategorias, incluir produtos do pai + filhos
4. Se não tiver subcategorias, comportamento atual (só pai)

```php
// Filtro por categoria
if ($categoriaId !== null || !empty($_GET['categoria'])) {
    // Buscar categoria para verificar se tem filhos
    if ($categoriaId !== null) {
        $stmt = $db->prepare("SELECT id, categoria_pai_id FROM categorias WHERE tenant_id = :tenant_id AND id = :categoria_id LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'categoria_id' => $categoriaId]);
        $categoriaInfo = $stmt->fetch();
    } else {
        $categoriaSlug = $_GET['categoria'];
        $stmt = $db->prepare("SELECT id, categoria_pai_id FROM categorias WHERE tenant_id = :tenant_id AND slug = :slug LIMIT 1");
        $stmt->execute(['tenant_id' => $tenantId, 'slug' => $categoriaSlug]);
        $categoriaInfo = $stmt->fetch();
        if ($categoriaInfo) {
            $categoriaId = $categoriaInfo['id'];
        }
    }
    
    if ($categoriaInfo) {
        // Verificar se tem subcategorias
        $stmt = $db->prepare("SELECT id FROM categorias WHERE tenant_id = :tenant_id AND categoria_pai_id = :categoria_pai_id");
        $stmt->execute(['tenant_id' => $tenantId, 'categoria_pai_id' => $categoriaInfo['id']]);
        $subcategorias = $stmt->fetchAll();
        $subcategoriaIds = array_column($subcategorias, 'id');
        
        // Se tem subcategorias, incluir pai + filhos
        if (!empty($subcategoriaIds)) {
            $categoriaIds = array_merge([$categoriaInfo['id']], $subcategoriaIds);
            $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
            
            $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
            $params['tenant_id_pc'] = $tenantId;
            
            // Usar IN para incluir pai + filhos
            $where[] = "pc.categoria_id IN ({$placeholders})";
            foreach ($categoriaIds as $catId) {
                $params['categoria_id_' . $catId] = $catId;
            }
            // Nota: PDO não suporta bindValue com array dinâmico, precisaremos usar bindValue individual
        } else {
            // Sem subcategorias: comportamento atual (só pai)
            $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
            $joins[] = "INNER JOIN categorias c ON c.id = pc.categoria_id AND c.tenant_id = :tenant_id_c AND c.id = :categoria_id";
            $params['tenant_id_pc'] = $tenantId;
            $params['tenant_id_c'] = $tenantId;
            $params['categoria_id'] = $categoriaInfo['id'];
        }
    }
}
```

**⚠️ Nota:** A implementação acima tem um problema: PDO não permite bindValue com placeholders dinâmicos facilmente. Melhor abordagem:

```php
// Melhor abordagem: usar array de IDs e bindValue individual
if (!empty($categoriaIds)) {
    $joins[] = "INNER JOIN produto_categorias pc ON pc.produto_id = p.id AND pc.tenant_id = :tenant_id_pc";
    $params['tenant_id_pc'] = $tenantId;
    
    $placeholders = [];
    foreach ($categoriaIds as $idx => $catId) {
        $key = "categoria_id_{$idx}";
        $placeholders[] = ":{$key}";
        $params[$key] = $catId;
    }
    $where[] = "pc.categoria_id IN (" . implode(',', $placeholders) . ")";
}
```

#### Opção B: Incluir Filhos Apenas se Pai Estiver Vazio

**Vantagem:** Comportamento mais granular - se pai tem produtos, mostra só pai.

**Desvantagem:** Menos intuitivo para o usuário.

**Implementação:** Similar à Opção A, mas só busca filhos se `productsCountDirect == 0`.

**Recomendação:** Usar **Opção A** (sempre incluir descendentes) para consistência e melhor UX.

---

### Correção 2: Exibir Subcategorias no Frontend (Opcional, mas Recomendado)

Para melhorar a experiência do usuário, adicionar um filtro secundário ou visualização de subcategorias quando uma categoria pai for selecionada.

#### Opção A: Filtro de Subcategoria no Sidebar (Mínima)

**Arquivo:** `themes/default/storefront/products/index.php`

Adicionar, após o filtro de categoria principal, um segundo select/dropdown com subcategorias (somente quando a categoria atual for pai e tiver filhos).

**Implementação:**
```php
<?php
// Se categoria atual é pai e tem filhos, exibir filtro de subcategoria
$subcategoriasParaFiltro = [];
if ($categoriaAtual && empty($categoriaAtual['categoria_pai_id'])) {
    // Buscar subcategorias
    $stmt = $db->prepare("
        SELECT id, nome, slug 
        FROM categorias 
        WHERE tenant_id = :tenant_id AND categoria_pai_id = :categoria_pai_id 
        ORDER BY nome ASC
    ");
    $stmt->execute([
        'tenant_id' => $tenantId,
        'categoria_pai_id' => $categoriaAtual['id']
    ]);
    $subcategoriasParaFiltro = $stmt->fetchAll();
}

if (!empty($subcategoriasParaFiltro)):
?>
    <div class="filtro-grupo">
        <label>Subcategoria:</label>
        <select name="subcategoria" onchange="window.location.href=this.value">
            <option value="<?= $basePath ?>/produtos?categoria=<?= urlencode($categoriaAtual['slug']) ?>">
                Todas
            </option>
            <?php foreach ($subcategoriasParaFiltro as $sub): ?>
                <option value="<?= $basePath ?>/produtos?categoria=<?= urlencode($sub['slug']) ?>"
                        <?= (isset($_GET['categoria']) && $_GET['categoria'] === $sub['slug']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sub['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
<?php endif; ?>
```

#### Opção B: Chips/Botões de Subcategorias (Mais Visual)

Exibir chips/botões clicáveis logo acima dos produtos, similar a um filtro horizontal.

**Implementação:** Similar à Opção A, mas renderizar como botões/chips ao invés de dropdown.

**Recomendação:** Começar com **Opção A** (dropdown simples) e evoluir para chips se necessário.

---

## 📋 RESUMO EXECUTIVO

### Problemas Identificados

1. **🔴 CRÍTICO:** Categorias pai nas bolotas mostram "nenhum produto" quando produtos estão apenas nas subcategorias
2. **⚠️ MENOR:** Não há forma visual de acessar/navegar subcategorias quando uma categoria pai é selecionada

### Ponto de Código Problemático

**Arquivo:** `src/Http/Controllers/Storefront/ProductController.php`  
**Linhas:** 74-89 (método `renderProductList()`)  
**Problema:** Query filtra apenas produtos diretamente na categoria, não inclui subcategorias

### Correção Recomendada

**Prioridade ALTA:**
1. ✅ Modificar query do backend para incluir produtos das subcategorias quando categoria pai for selecionada (Opção A - sempre incluir descendentes)

**Prioridade MÉDIA:**
2. ✅ Adicionar filtro de subcategorias no frontend (Opção A - dropdown simples)

### Próximos Passos

1. Executar `auditoria_bolotas_categorias.php` para identificar todas as bolotas problemáticas
2. Aplicar correção no backend (`ProductController.php`)
3. Testar com categorias que têm produtos apenas nos filhos
4. Implementar filtro de subcategorias no frontend (opcional)
5. Validar que todas as bolotas agora mostram produtos corretamente

---

## 📄 Anexos

### Arquivos Relacionados

- `themes/default/storefront/partials/category-strip.php` - Renderização das bolotas
- `src/Http/Controllers/Storefront/HomeController.php` - Geração dos dados das bolotas
- `src/Http/Controllers/Storefront/ProductController.php` - Filtro de produtos por categoria
- `database/migrations/027_create_home_category_pills_table.php` - Estrutura da tabela de bolotas
- `database/migrations/022_create_categorias_table_detailed.php` - Estrutura da tabela de categorias

### Scripts Criados

- `public/auditoria_bolotas_categorias.php` - Script de auditoria completo

---

**Fim do Relatório**
