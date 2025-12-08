# 📘 Guia Completo para Desenvolvedor - Integração de Produtos

**Versão:** 2.0  
**Data de Exportação:** 2025-12-05 11:39:50  
**Status:** ✅ Completo e Validado

---

## 📋 Índice

1. [Visão Geral](#visão-geral)
2. [Estrutura da Pasta](#estrutura-da-pasta)
3. [Formato dos Dados](#formato-dos-dados)
4. [Estrutura de Banco de Dados](#estrutura-de-banco-de-dados)
5. [Processo de Importação](#processo-de-importação)
6. [Tratamento de Imagens](#tratamento-de-imagens)
7. [Exemplos de Código](#exemplos-de-código)
8. [Mapeamento de Campos](#mapeamento-de-campos)
9. [Considerações Importantes](#considerações-importantes)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

Este pacote contém uma exportação completa de **928 produtos** do WordPress/WooCommerce, incluindo:

- ✅ Todos os dados dos produtos (nome, descrição, preço, estoque, etc.)
- ✅ **148 imagens** baixadas e organizadas (47 principais + 101 de galeria)
- ✅ **147 arquivos físicos** de imagens na pasta `images/`
- ✅ Categorias, tags e metadados customizados
- ✅ Estrutura completa para importação em sistema não-WooCommerce

**Objetivo:** Facilitar a migração completa dos dados de produtos para um novo sistema desenvolvido em código puro (sem WordPress/WooCommerce).

---

## 📁 Estrutura da Pasta

```
exportacao-produtos-2025-12-05_11-36-53/
│
├── 📄 produtos-completo.json      # ⭐ ARQUIVO PRINCIPAL - Todos os dados
├── 📄 produtos-resumo.csv         # Resumo em CSV para referência rápida
├── 📄 estatisticas.json           # Estatísticas da exportação
│
├── 📁 images/                     # ⭐ PASTA DE IMAGENS
│   ├── main_13873_*.jpg          # Imagens principais (47 arquivos)
│   ├── gallery_5449_*.png        # Imagens de galeria (101 arquivos)
│   └── ... (147 arquivos no total)
│
├── 📄 INDEX.md                    # Índice rápido dos arquivos
├── 📄 INSTRUCOES-ENTREGA.md       # Instruções de entrega
├── 📄 README-IMPORTACAO.md        # Guia de importação (legado)
├── 📄 validar-dados.php           # Script de validação PHP
│
└── 📁 backup-produtos-*.json      # Backups parciais (opcional)
```

### 📊 Estatísticas

- **Total de produtos:** 928
- **Produtos com imagens:** 47
- **Total de imagens:** 148 (47 principais + 101 galeria)
- **Arquivos físicos:** 147 na pasta `images/`
- **Taxa de sucesso:** 100%
- **Erros:** 0

---

## 📊 Formato dos Dados

### Arquivo Principal: `produtos-completo.json`

O arquivo é um **array JSON** contendo objetos de produtos. Cada produto segue esta estrutura:

```json
{
    "id": 15328,
    "name": "BLUSA OLD NAVY AZUL MARINHO TM XL",
    "slug": "blusa-old-navy-azul-marinho-tm-xl-3",
    "sku": "236",
    "type": "simple",
    "status": "publish",
    
    // Preços
    "price": "190",
    "regular_price": "190",
    "sale_price": "",
    "date_on_sale_from": null,
    "date_on_sale_to": null,
    
    // Estoque
    "manage_stock": true,
    "stock_quantity": 1,
    "stock_status": "instock",
    "backorders": "no",
    
    // Dimensões
    "weight": "",
    "length": "",
    "width": "",
    "height": "",
    
    // Descrições
    "description": "",
    "short_description": "",
    
    // Imagens ⭐ IMPORTANTE
    "images": {
        "main": {
            "id": "13873",
            "url_original": "http://localhost/...",
            "local_path": "images/main_13873_91gwKUrxIQL._AC_SL1500_.jpg",  // ⭐ CAMINHO RELATIVO
            "alt": "",
            "title": "91gwKUrxIQL._AC_SL1500_",
            "mime_type": "image/jpeg",
            "file_size": null,
            "sizes": { /* tamanhos gerados pelo WordPress */ }
        },
        "gallery": [
            {
                "id": 13873,
                "url_original": "http://localhost/...",
                "local_path": "images/gallery_13873_91gwKUrxIQL._AC_SL1500_.jpg",  // ⭐ CAMINHO RELATIVO
                "alt": "",
                "title": "...",
                "mime_type": "image/jpeg"
            }
        ]
    },
    
    // Categorias
    "categories": [
        {
            "id": 56,
            "name": "Array",
            "slug": "array",
            "description": "",
            "parent": 0
        }
    ],
    
    // Tags
    "tags": [],
    
    // Atributos (cor, tamanho, etc.)
    "attributes": [],
    
    // Variações (se produto variável)
    "variations": [],
    
    // Metadados customizados
    "custom_meta": [],
    
    // Outros
    "featured": false,
    "catalog_visibility": "visible",
    "tax_status": "taxable",
    "date_created": "2025-12-04 15:06:17",
    "date_modified": "2025-12-04 15:06:17"
}
```

### ⚠️ Observações Importantes sobre o JSON

1. **Campo `local_path`:** 
   - Sempre relativo à pasta `images/` dentro da exportação
   - Exemplo: `"images/main_13873_91gwKUrxIQL._AC_SL1500_.jpg"`
   - O arquivo físico existe na pasta `images/` da exportação

2. **Arrays vazios:**
   - Se `images: []`, o produto não tem imagens
   - Se `categories: []`, o produto não tem categorias
   - Se `tags: []`, o produto não tem tags

3. **Valores nulos:**
   - `null` significa que o campo não foi preenchido
   - Strings vazias `""` também indicam ausência de valor

4. **IDs originais:**
   - Os IDs do WordPress foram preservados
   - Você pode manter ou gerar novos IDs no seu sistema

---

## 🗄️ Estrutura de Banco de Dados

### Schema SQL Sugerido

```sql
-- Tabela principal de produtos
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_original_wp INT UNIQUE,              -- ID original do WordPress
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    sku VARCHAR(100) UNIQUE,
    tipo ENUM('simple', 'variable', 'grouped', 'external') DEFAULT 'simple',
    status ENUM('publish', 'draft', 'private') DEFAULT 'publish',
    
    -- Preços
    preco DECIMAL(10,2) DEFAULT 0.00,
    preco_regular DECIMAL(10,2) DEFAULT 0.00,
    preco_promocional DECIMAL(10,2) NULL,
    data_promocao_inicio DATETIME NULL,
    data_promocao_fim DATETIME NULL,
    
    -- Estoque
    gerencia_estoque BOOLEAN DEFAULT FALSE,
    quantidade_estoque INT DEFAULT 0,
    status_estoque ENUM('instock', 'outofstock', 'onbackorder') DEFAULT 'instock',
    permite_pedidos_falta ENUM('no', 'notify', 'yes') DEFAULT 'no',
    
    -- Dimensões
    peso DECIMAL(8,2) NULL,
    comprimento DECIMAL(8,2) NULL,
    largura DECIMAL(8,2) NULL,
    altura DECIMAL(8,2) NULL,
    
    -- Descrições
    descricao TEXT,
    descricao_curta TEXT,
    
    -- Imagem principal
    imagem_principal VARCHAR(255) NULL,      -- Caminho relativo ou URL
    
    -- Flags
    destaque BOOLEAN DEFAULT FALSE,
    visibilidade_catalogo ENUM('visible', 'catalog', 'search', 'hidden') DEFAULT 'visible',
    status_imposto ENUM('taxable', 'shipping', 'none') DEFAULT 'taxable',
    
    -- Datas
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_modificacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_sku (sku),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_status_estoque (status_estoque)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens dos produtos
CREATE TABLE produto_imagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('main', 'gallery') NOT NULL,
    ordem INT DEFAULT 0,
    caminho_arquivo VARCHAR(255) NOT NULL,   -- Caminho relativo ou URL
    url_original VARCHAR(500) NULL,          -- URL original do WordPress
    alt_text VARCHAR(255) NULL,
    titulo VARCHAR(255) NULL,
    legenda TEXT NULL,
    mime_type VARCHAR(100) NULL,
    tamanho_arquivo BIGINT NULL,
    
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_produto (produto_id),
    INDEX idx_tipo (tipo),
    INDEX idx_ordem (ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_original_wp INT UNIQUE,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    descricao TEXT,
    categoria_pai_id INT NULL,
    
    FOREIGN KEY (categoria_pai_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_pai (categoria_pai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento produto-categoria
CREATE TABLE produto_categorias (
    produto_id INT NOT NULL,
    categoria_id INT NOT NULL,
    
    PRIMARY KEY (produto_id, categoria_id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tags
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_original_wp INT UNIQUE,
    nome VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de relacionamento produto-tag
CREATE TABLE produto_tags (
    produto_id INT NOT NULL,
    tag_id INT NOT NULL,
    
    PRIMARY KEY (produto_id, tag_id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de metadados customizados
CREATE TABLE produto_meta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    chave VARCHAR(255) NOT NULL,
    valor TEXT,
    
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_produto (produto_id),
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🚀 Processo de Importação

### Passo 1: Validar os Dados

Antes de importar, sempre valide a integridade:

```bash
php validar-dados.php
```

O script verifica:
- ✅ Formato JSON válido
- ✅ Campos obrigatórios presentes
- ✅ Existência dos arquivos de imagem referenciados

### Passo 2: Preparar o Ambiente

1. **Copiar a pasta de exportação** para seu projeto
2. **Configurar conexão com banco de dados**
3. **Criar as tabelas** usando o schema SQL acima
4. **Definir pasta de destino** para as imagens no seu sistema

### Passo 3: Processo de Importação

A importação deve seguir esta ordem:

1. **Categorias** (criar primeiro para referências)
2. **Tags** (criar antes de associar)
3. **Produtos** (dados principais)
4. **Imagens** (associar aos produtos)
5. **Relacionamentos** (produto-categoria, produto-tag)
6. **Metadados customizados**

---

## 🖼️ Tratamento de Imagens

### Estrutura das Imagens

As imagens estão organizadas na pasta `images/` com nomenclatura padronizada:

- **Imagens principais:** `main_{id_wp}_{filename}`
  - Exemplo: `main_13873_91gwKUrxIQL._AC_SL1500_.jpg`

- **Imagens de galeria:** `gallery_{id_wp}_{filename}`
  - Exemplo: `gallery_5449_IMG-20251008-WA0405.png`

### Processo de Migração de Imagens

#### Opção 1: Copiar Diretamente

```php
// Caminho origem (pasta de exportação)
$origem = __DIR__ . '/exportacao-produtos-2025-12-05_11-36-53/images/';

// Caminho destino (pasta de uploads do seu sistema)
$destino = __DIR__ . '/public/uploads/produtos/';

// Copiar arquivo
copy($origem . $nomeArquivo, $destino . $nomeArquivo);
```

#### Opção 2: Processar e Otimizar

```php
// Ler imagem da exportação
$imagemOrigem = imagecreatefromjpeg($caminhoOrigem);

// Processar/redimensionar se necessário
$imagemProcessada = imagescale($imagemOrigem, 800);

// Salvar no destino
imagejpeg($imagemProcessada, $caminhoDestino, 85);
```

### Atualizar Caminhos no Banco

Após copiar as imagens, atualize os caminhos no banco de dados:

```php
// Caminho antigo (relativo à exportação)
$caminhoAntigo = "images/main_13873_91gwKUrxIQL._AC_SL1500_.jpg";

// Caminho novo (relativo ao seu sistema)
$caminhoNovo = "/uploads/produtos/main_13873_91gwKUrxIQL._AC_SL1500_.jpg";

// Atualizar no banco
$stmt = $pdo->prepare("UPDATE produto_imagens SET caminho_arquivo = ? WHERE caminho_arquivo = ?");
$stmt->execute([$caminhoNovo, $caminhoAntigo]);
```

---

## 💻 Exemplos de Código

### PHP - Importação Completa

```php
<?php
require_once 'vendor/autoload.php'; // Se usar Composer

// Configurações
$jsonFile = __DIR__ . '/exportacao-produtos-2025-12-05_11-36-53/produtos-completo.json';
$imagesSource = __DIR__ . '/exportacao-produtos-2025-12-05_11-36-53/images/';
$imagesDest = __DIR__ . '/public/uploads/produtos/';

// Conexão com banco
$pdo = new PDO(
    'mysql:host=localhost;dbname=seu_banco;charset=utf8mb4',
    'usuario',
    'senha',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Ler JSON
$json = file_get_contents($jsonFile);
$produtos = json_decode($json, true);

if (!$produtos) {
    die("Erro ao ler JSON");
}

// Iniciar transação
$pdo->beginTransaction();

try {
    // Mapear categorias primeiro
    $categoriasMap = [];
    foreach ($produtos as $produto) {
        foreach ($produto['categories'] as $cat) {
            if (!isset($categoriasMap[$cat['id']])) {
                // Verificar se já existe
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id_original_wp = ?");
                $stmt->execute([$cat['id']]);
                $catId = $stmt->fetchColumn();
                
                if (!$catId) {
                    // Criar categoria
                    $stmt = $pdo->prepare("
                        INSERT INTO categorias (id_original_wp, nome, slug, descricao, categoria_pai_id)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $cat['id'],
                        $cat['name'],
                        $cat['slug'],
                        $cat['description'] ?? '',
                        $cat['parent'] > 0 ? $categoriasMap[$cat['parent']] ?? null : null
                    ]);
                    $catId = $pdo->lastInsertId();
                }
                
                $categoriasMap[$cat['id']] = $catId;
            }
        }
    }
    
    // Importar produtos
    foreach ($produtos as $produto) {
        // Inserir produto
        $stmt = $pdo->prepare("
            INSERT INTO produtos (
                id_original_wp, nome, slug, sku, tipo, status,
                preco, preco_regular, preco_promocional,
                gerencia_estoque, quantidade_estoque, status_estoque,
                descricao, descricao_curta,
                imagem_principal, destaque, data_criacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $imagemPrincipal = null;
        if (!empty($produto['images']['main']['local_path'])) {
            $imagemPrincipal = $produto['images']['main']['local_path'];
        }
        
        $stmt->execute([
            $produto['id'],
            $produto['name'],
            $produto['slug'],
            $produto['sku'] ?? null,
            $produto['type'],
            $produto['status'],
            $produto['price'] ?? 0,
            $produto['regular_price'] ?? 0,
            !empty($produto['sale_price']) ? $produto['sale_price'] : null,
            $produto['manage_stock'] ?? false,
            $produto['stock_quantity'] ?? 0,
            $produto['stock_status'] ?? 'instock',
            $produto['description'] ?? '',
            $produto['short_description'] ?? '',
            $imagemPrincipal,
            $produto['featured'] ?? false,
            $produto['date_created'] ?? date('Y-m-d H:i:s')
        ]);
        
        $produtoId = $pdo->lastInsertId();
        
        // Processar imagem principal
        if (!empty($produto['images']['main']['local_path'])) {
            $img = $produto['images']['main'];
            $caminhoOrigem = $imagesSource . basename($img['local_path']);
            $caminhoDestino = $imagesDest . basename($img['local_path']);
            
            // Copiar arquivo
            if (file_exists($caminhoOrigem)) {
                copy($caminhoOrigem, $caminhoDestino);
            }
            
            // Inserir no banco
            $stmt = $pdo->prepare("
                INSERT INTO produto_imagens (
                    produto_id, tipo, ordem, caminho_arquivo, url_original,
                    alt_text, titulo, mime_type
                ) VALUES (?, 'main', 0, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $produtoId,
                '/uploads/produtos/' . basename($img['local_path']),
                $img['url_original'] ?? null,
                $img['alt'] ?? null,
                $img['title'] ?? null,
                $img['mime_type'] ?? null
            ]);
        }
        
        // Processar galeria
        if (!empty($produto['images']['gallery']) && is_array($produto['images']['gallery'])) {
            $ordem = 1;
            foreach ($produto['images']['gallery'] as $img) {
                if (!empty($img['local_path'])) {
                    $caminhoOrigem = $imagesSource . basename($img['local_path']);
                    $caminhoDestino = $imagesDest . basename($img['local_path']);
                    
                    // Copiar arquivo
                    if (file_exists($caminhoOrigem)) {
                        copy($caminhoOrigem, $caminhoDestino);
                    }
                    
                    // Inserir no banco
                    $stmt = $pdo->prepare("
                        INSERT INTO produto_imagens (
                            produto_id, tipo, ordem, caminho_arquivo, url_original,
                            alt_text, titulo, mime_type
                        ) VALUES (?, 'gallery', ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $produtoId,
                        $ordem++,
                        '/uploads/produtos/' . basename($img['local_path']),
                        $img['url_original'] ?? null,
                        $img['alt'] ?? null,
                        $img['title'] ?? null,
                        $img['mime_type'] ?? null
                    ]);
                }
            }
        }
        
        // Associar categorias
        foreach ($produto['categories'] as $cat) {
            if (isset($categoriasMap[$cat['id']])) {
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO produto_categorias (produto_id, categoria_id)
                    VALUES (?, ?)
                ");
                $stmt->execute([$produtoId, $categoriasMap[$cat['id']]]);
            }
        }
        
        // Associar tags (se houver)
        foreach ($produto['tags'] as $tag) {
            // Criar tag se não existir
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE id_original_wp = ?");
            $stmt->execute([$tag['id']]);
            $tagId = $stmt->fetchColumn();
            
            if (!$tagId) {
                $stmt = $pdo->prepare("
                    INSERT INTO tags (id_original_wp, nome, slug)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$tag['id'], $tag['name'], $tag['slug']]);
                $tagId = $pdo->lastInsertId();
            }
            
            // Associar
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO produto_tags (produto_id, tag_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$produtoId, $tagId]);
        }
        
        // Metadados customizados
        if (!empty($produto['custom_meta']) && is_array($produto['custom_meta'])) {
            foreach ($produto['custom_meta'] as $chave => $valor) {
                $stmt = $pdo->prepare("
                    INSERT INTO produto_meta (produto_id, chave, valor)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $produtoId,
                    $chave,
                    is_array($valor) ? json_encode($valor) : $valor
                ]);
            }
        }
    }
    
    // Commit
    $pdo->commit();
    echo "✅ Importação concluída com sucesso!\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Erro: " . $e->getMessage() . "\n";
    throw $e;
}
```

### Node.js - Exemplo Básico

```javascript
const fs = require('fs');
const path = require('path');
const mysql = require('mysql2/promise');

async function importarProdutos() {
    // Ler JSON
    const jsonData = fs.readFileSync('./exportacao-produtos-2025-12-05_11-36-53/produtos-completo.json', 'utf8');
    const produtos = JSON.parse(jsonData);
    
    // Conexão com banco
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'usuario',
        password: 'senha',
        database: 'seu_banco'
    });
    
    try {
        await connection.beginTransaction();
        
        for (const produto of produtos) {
            // Inserir produto
            const [result] = await connection.execute(
                `INSERT INTO produtos (id_original_wp, nome, slug, sku, preco, preco_regular)
                 VALUES (?, ?, ?, ?, ?, ?)`,
                [
                    produto.id,
                    produto.name,
                    produto.slug,
                    produto.sku,
                    produto.price || 0,
                    produto.regular_price || 0
                ]
            );
            
            const produtoId = result.insertId;
            
            // Processar imagens
            if (produto.images && produto.images.main && produto.images.main.local_path) {
                const img = produto.images.main;
                await connection.execute(
                    `INSERT INTO produto_imagens (produto_id, tipo, caminho_arquivo)
                     VALUES (?, 'main', ?)`,
                    [produtoId, img.local_path]
                );
            }
        }
        
        await connection.commit();
        console.log('✅ Importação concluída!');
        
    } catch (error) {
        await connection.rollback();
        throw error;
    } finally {
        await connection.end();
    }
}

importarProdutos();
```

### Python - Exemplo Básico

```python
import json
import mysql.connector
from mysql.connector import Error

def importar_produtos():
    # Ler JSON
    with open('exportacao-produtos-2025-12-05_11-36-53/produtos-completo.json', 'r', encoding='utf-8') as f:
        produtos = json.load(f)
    
    # Conexão com banco
    try:
        connection = mysql.connector.connect(
            host='localhost',
            database='seu_banco',
            user='usuario',
            password='senha'
        )
        
        cursor = connection.cursor()
        connection.start_transaction()
        
        for produto in produtos:
            # Inserir produto
            cursor.execute("""
                INSERT INTO produtos (id_original_wp, nome, slug, sku, preco, preco_regular)
                VALUES (%s, %s, %s, %s, %s, %s)
            """, (
                produto['id'],
                produto['name'],
                produto['slug'],
                produto.get('sku'),
                produto.get('price', 0),
                produto.get('regular_price', 0)
            ))
            
            produto_id = cursor.lastrowid
            
            # Processar imagem principal
            if produto.get('images', {}).get('main', {}).get('local_path'):
                img = produto['images']['main']
                cursor.execute("""
                    INSERT INTO produto_imagens (produto_id, tipo, caminho_arquivo)
                    VALUES (%s, 'main', %s)
                """, (produto_id, img['local_path']))
        
        connection.commit()
        print("✅ Importação concluída!")
        
    except Error as e:
        connection.rollback()
        print(f"❌ Erro: {e}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == '__main__':
    importar_produtos()
```

---

## 🔄 Mapeamento de Campos

### Tabela de Mapeamento WooCommerce → Novo Sistema

| WooCommerce (JSON) | Novo Sistema (BD) | Tipo | Observações |
|-------------------|-------------------|------|-------------|
| `id` | `id_original_wp` | INT | Preservar para referência |
| `name` | `nome` | VARCHAR(255) | - |
| `slug` | `slug` | VARCHAR(255) | Único, URL-friendly |
| `sku` | `sku` | VARCHAR(100) | Único, código do produto |
| `type` | `tipo` | ENUM | simple, variable, etc. |
| `status` | `status` | ENUM | publish, draft, etc. |
| `price` | `preco` | DECIMAL(10,2) | Preço atual |
| `regular_price` | `preco_regular` | DECIMAL(10,2) | Preço sem desconto |
| `sale_price` | `preco_promocional` | DECIMAL(10,2) | Preço com desconto |
| `manage_stock` | `gerencia_estoque` | BOOLEAN | - |
| `stock_quantity` | `quantidade_estoque` | INT | - |
| `stock_status` | `status_estoque` | ENUM | instock, outofstock |
| `description` | `descricao` | TEXT | HTML permitido |
| `short_description` | `descricao_curta` | TEXT | - |
| `images.main.local_path` | `imagem_principal` | VARCHAR(255) | Caminho relativo |
| `images.gallery[].local_path` | `produto_imagens` | VARCHAR(255) | Tabela separada |
| `categories[]` | `categorias` + `produto_categorias` | - | Relacionamento N:N |
| `tags[]` | `tags` + `produto_tags` | - | Relacionamento N:N |
| `custom_meta` | `produto_meta` | - | Tabela separada |

---

## ⚠️ Considerações Importantes

### 1. Caminhos das Imagens

- **Na exportação:** `images/main_13873_*.jpg` (relativo à pasta `images/`)
- **No seu sistema:** Defina a estrutura de pastas e atualize os caminhos
- **Recomendação:** Use caminhos relativos ou URLs absolutas consistentes

### 2. IDs Originais

- Os IDs do WordPress foram preservados no campo `id_original_wp`
- Você pode:
  - **Manter** os IDs originais (se não houver conflito)
  - **Gerar novos** IDs e usar `id_original_wp` apenas para referência

### 3. Formato de Dados

- **Encoding:** UTF-8
- **Caracteres especiais:** Preservados corretamente
- **HTML nas descrições:** Mantido como está (sanitize se necessário)

### 4. Validação

- **Sempre valide** antes de importar:
  - Formato JSON válido
  - Campos obrigatórios presentes
  - Integridade referencial
  - Existência de arquivos de imagem

### 5. Performance

- **Importação em lote:** Use transações
- **Processamento de imagens:** Considere fazer em background
- **Índices:** Crie índices nas colunas de busca frequente

### 6. Segurança

- **Sanitize inputs:** Especialmente descrições HTML
- **Valide SKUs:** Garanta unicidade
- **Proteja uploads:** Valide tipos MIME de imagens

---

## 🔧 Troubleshooting

### Problema: JSON inválido

**Solução:**
```php
$json = file_get_contents($jsonFile);
$produtos = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "Erro JSON: " . json_last_error_msg();
}
```

### Problema: Imagem não encontrada

**Solução:**
```php
$caminhoOrigem = $imagesSource . basename($img['local_path']);

if (!file_exists($caminhoOrigem)) {
    echo "⚠️ Imagem não encontrada: {$caminhoOrigem}\n";
    continue; // Pular esta imagem
}
```

### Problema: Slug duplicado

**Solução:**
```php
// Verificar se slug já existe
$stmt = $pdo->prepare("SELECT id FROM produtos WHERE slug = ?");
$stmt->execute([$slug]);
if ($stmt->fetch()) {
    // Adicionar sufixo
    $slug = $slug . '-' . time();
}
```

### Problema: Memória insuficiente

**Solução:**
```php
// Processar em lotes
$lote = 100;
$total = count($produtos);

for ($i = 0; $i < $total; $i += $lote) {
    $produtosLote = array_slice($produtos, $i, $lote);
    processarLote($produtosLote);
    unset($produtosLote); // Liberar memória
}
```

---

## 📞 Suporte

### Arquivos de Referência

- `INDEX.md` - Índice rápido
- `INSTRUCOES-ENTREGA.md` - Instruções de entrega
- `README-IMPORTACAO.md` - Guia legado
- `validar-dados.php` - Script de validação
- `estatisticas.json` - Estatísticas da exportação

### Checklist de Importação

- [ ] Validar JSON (`validar-dados.php`)
- [ ] Criar estrutura de banco de dados
- [ ] Configurar conexão com banco
- [ ] Definir pasta de destino para imagens
- [ ] Importar categorias primeiro
- [ ] Importar produtos
- [ ] Copiar imagens para destino
- [ ] Atualizar caminhos das imagens
- [ ] Associar categorias e tags
- [ ] Importar metadados customizados
- [ ] Validar dados importados
- [ ] Testar busca e exibição

---

**Última Atualização:** 2025-12-05  
**Versão do Guia:** 1.0  
**Status:** ✅ Completo e Pronto para Uso
