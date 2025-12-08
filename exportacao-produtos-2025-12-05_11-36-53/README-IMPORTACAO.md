# 📦 Guia de Importação de Produtos

Este diretório contém uma exportação completa de todos os produtos do WooCommerce, incluindo imagens, descrições, categorias, tags, atributos e variações.

## 📁 Estrutura dos Arquivos

```
exportacao-produtos-YYYY-MM-DD_HH-MM-SS/
├── produtos-completo.json      # JSON completo com todos os dados dos produtos
├── produtos-resumo.csv         # CSV simplificado para referência rápida
├── estatisticas.json          # Estatísticas da exportação
├── images/                     # Todas as imagens baixadas dos produtos
│   ├── main_XXX_imagem.jpg    # Imagens principais
│   ├── gallery_XXX_imagem.jpg # Imagens da galeria
│   └── variation_XXX_imagem.jpg # Imagens de variações
└── README-IMPORTACAO.md        # Este arquivo
```

## 📊 Formato do JSON

O arquivo `produtos-completo.json` contém um array de objetos, onde cada objeto representa um produto completo com:

### Dados Básicos
- `id`: ID original do produto no WooCommerce
- `name`: Nome do produto
- `slug`: Slug/URL amigável
- `sku`: Código SKU
- `type`: Tipo (simple, variable, grouped, external)
- `status`: Status (publish, draft, etc)
- `description`: Descrição completa (HTML)
- `short_description`: Descrição curta

### Preços
- `price`: Preço atual
- `regular_price`: Preço regular
- `sale_price`: Preço promocional
- `date_on_sale_from`: Data início promoção
- `date_on_sale_to`: Data fim promoção

### Estoque
- `stock_quantity`: Quantidade em estoque
- `stock_status`: Status (instock, outofstock, etc)
- `manage_stock`: Se gerencia estoque
- `backorders`: Permite pedidos em falta

### Dimensões e Peso
- `weight`: Peso
- `length`: Comprimento
- `width`: Largura
- `height`: Altura

### Imagens
- `images.main`: Imagem principal (com `local_path` para arquivo baixado)
- `images.gallery[]`: Array de imagens da galeria
- Cada imagem contém:
  - `url_original`: URL original da imagem
  - `local_path`: Caminho relativo do arquivo baixado (em `images/`)
  - `alt`: Texto alternativo
  - `title`: Título da imagem
  - `caption`: Legenda
  - `mime_type`: Tipo MIME
  - `file_size`: Tamanho do arquivo em bytes

### Categorias e Tags
- `categories[]`: Array de categorias (id, name, slug, description, parent)
- `tags[]`: Array de tags (id, name, slug)

### Atributos e Variações
- `attributes[]`: Atributos do produto (cor, tamanho, etc)
- `variations[]`: Variações (se produto variável)

### Metadados Customizados
- `custom_meta`: Objeto com todos os metadados customizados que não são padrão do WooCommerce

## 🚀 Como Importar em um Projeto Não-WooCommerce

### Opção 1: Usar o Script de Importação PHP

Um script de exemplo está disponível em `importar-produtos-exemplo.php` na raiz do projeto WordPress.

**Passos:**

1. Copie o diretório de exportação para seu novo projeto
2. Configure as credenciais do banco de dados no script
3. Execute:
   ```bash
   php importar-produtos-exemplo.php
   ```

### Opção 2: Importação Manual via Código

#### 1. Estrutura de Banco de Dados

Crie tabelas conforme necessário. Exemplo mínimo:

```sql
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    sku VARCHAR(100) UNIQUE,
    descricao TEXT,
    descricao_curta TEXT,
    preco DECIMAL(10,2),
    preco_regular DECIMAL(10,2),
    preco_promocao DECIMAL(10,2),
    estoque INT,
    peso DECIMAL(10,2),
    imagem_principal VARCHAR(255),
    data_criacao DATETIME,
    meta_data JSON
);

CREATE TABLE produto_imagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('main', 'gallery', 'variation'),
    caminho VARCHAR(255),
    alt_text VARCHAR(255),
    ordem INT DEFAULT 0,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

CREATE TABLE produto_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    categoria_nome VARCHAR(255),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);
```

#### 2. Processar o JSON

```php
$json = file_get_contents('produtos-completo.json');
$produtos = json_decode($json, true);

foreach ($produtos as $produto) {
    // 1. Inserir produto principal
    $produtoId = inserirProduto($produto);
    
    // 2. Copiar imagens
    copiarImagens($produtoId, $produto['images'], 'exportacao-produtos-XXX/images/');
    
    // 3. Inserir categorias
    inserirCategorias($produtoId, $produto['categories']);
    
    // 4. Inserir tags
    inserirTags($produtoId, $produto['tags']);
    
    // 5. Inserir variações (se houver)
    inserirVariacoes($produtoId, $produto['variations']);
}
```

#### 3. Copiar Imagens

```php
function copiarImagens($produtoId, $images, $sourceDir) {
    // Imagem principal
    if (isset($images['main']['local_path'])) {
        $source = $sourceDir . '/' . $images['main']['local_path'];
        $dest = 'uploads/produtos/' . basename($source);
        copy($source, $dest);
        // Salvar caminho no banco
    }
    
    // Galeria
    if (isset($images['gallery'])) {
        foreach ($images['gallery'] as $img) {
            if (isset($img['local_path'])) {
                $source = $sourceDir . '/' . $img['local_path'];
                $dest = 'uploads/produtos/' . basename($source);
                copy($source, $dest);
            }
        }
    }
}
```

### Opção 3: Importação via API REST

Se seu novo projeto tiver uma API REST, você pode criar um endpoint que receba os dados do JSON e processe a importação.

## ⚠️ Observações Importantes

1. **Imagens**: Todas as imagens foram baixadas e estão na pasta `images/`. Certifique-se de copiá-las para o diretório de uploads do seu novo projeto.

2. **URLs**: As URLs originais estão preservadas no campo `url_original` de cada imagem, caso precise baixar novamente.

3. **Metadados**: Todos os metadados customizados estão em `custom_meta`. Revise e importe conforme necessário.

4. **Variações**: Produtos variáveis têm todas as variações em `variations[]`. Processe cada uma separadamente.

5. **Categorias**: As categorias mantêm a hierarquia (campo `parent`). Reconstrua a árvore de categorias no novo sistema.

6. **Slugs**: Os slugs estão preservados, mas você pode precisar ajustá-los se houver conflitos no novo sistema.

## 📝 Exemplo de Uso com Laravel

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$produtos = json_decode(file_get_contents('produtos-completo.json'), true);

foreach ($produtos as $produtoData) {
    DB::transaction(function () use ($produtoData) {
        // Criar produto
        $produto = Produto::create([
            'nome' => $produtoData['name'],
            'slug' => $produtoData['slug'],
            'sku' => $produtoData['sku'],
            'descricao' => $produtoData['description'],
            'preco' => $produtoData['price'],
            // ... outros campos
        ]);
        
        // Processar imagens
        if (isset($produtoData['images']['main']['local_path'])) {
            $imagemPath = 'exportacao/images/' . $produtoData['images']['main']['local_path'];
            $destino = Storage::putFile('produtos', new File($imagemPath));
            $produto->imagem_principal = $destino;
            $produto->save();
        }
        
        // Processar categorias
        foreach ($produtoData['categories'] as $categoria) {
            $produto->categorias()->attach(
                Categoria::firstOrCreate(['nome' => $categoria['name']])->id
            );
        }
    });
}
```

## 🔧 Troubleshooting

### Imagens não encontradas
- Verifique se a pasta `images/` está no mesmo diretório que o JSON
- Confirme que os caminhos em `local_path` estão corretos

### Erro de encoding
- O JSON está em UTF-8. Certifique-se de que seu banco de dados e código estão configurados para UTF-8

### Produtos duplicados
- Verifique se está usando `sku` ou `slug` como chave única
- Considere fazer um `UPDATE` ao invés de `INSERT` se o produto já existir

## 📞 Suporte

Para dúvidas sobre a estrutura dos dados ou problemas na importação, consulte:
- Arquivo `estatisticas.json` para informações sobre a exportação
- Arquivo `produtos-resumo.csv` para uma visão rápida dos produtos

---

**Data da Exportação:** 2025-12-05 11:39:50  
**Total de Produtos:** 928  
**Total de Imagens:** 0