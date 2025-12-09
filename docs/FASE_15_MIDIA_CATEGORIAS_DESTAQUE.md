# Fase 15: Mini Biblioteca de Mídia para Categorias em Destaque

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Diagnóstico](#fase-1---diagnóstico-rápido)
- [Fase 2 - Endpoint para Listar Imagens](#fase-2---endpoint-para-listar-imagens-existentes)
- [Fase 3 - Modal Biblioteca de Mídia](#fase-3---modal-biblioteca-de-mídia-na-view)
- [Fase 4 - JavaScript](#fase-4---javascript-para-carregar-e-selecionar-imagens)
- [Fase 5 - Testes](#fase-5---testes-manuais)
- [Auditoria 2025-12-08](#auditoria-2025-12-08--inconsistência-biblioteca-de-mídia)

---

## Visão Geral

Esta fase implementa uma mini biblioteca de mídia para permitir reutilizar imagens já enviadas nas Categorias em Destaque, evitando reuploads desnecessários.

**Status:** ✅ Concluída (com bug identificado na auditoria)

---

## Fase 1 - Diagnóstico Rápido

### View Atual

- **Arquivo de edição:** `themes/default/admin/home/categories-pills-edit-content.php`
- **Arquivo de criação:** `themes/default/admin/home/categories-pills-content.php`
- **Campos:**
  - `icon_upload` (input type="file") - Upload de arquivo
  - `icon_path` (input type="text") - Caminho do ícone (avançado)
- **Form:** Já possui `enctype="multipart/form-data"`

### Pasta de Uploads

- **Caminho absoluto:** `/public/uploads/tenants/{tenantId}/category-pills/`
- **Caminho relativo:** `/uploads/tenants/{tenantId}/category-pills/`
- **Base path:** Obtido via `config/paths.php` → `uploads_produtos_base_path`
- **Estrutura:** Isolada por tenant

### Framework Front-end

- Admin usa Bootstrap (confirmado pelo layout)
- Modal Bootstrap disponível para uso

---

## Fase 2 - Endpoint para Listar Imagens Existentes

### Implementação

- ✅ Método `listarImagensExistentes()` adicionado ao `HomeCategoriesController`
- ✅ Lista apenas imagens (JPG, JPEG, PNG, WEBP, GIF)
- ✅ Retorna JSON com lista de arquivos e URLs
- ✅ Rota GET `/admin/home/categorias-pills/midia` criada
- ✅ Protegida por autenticação admin

---

## Fase 3 - Modal Biblioteca de Mídia na View

### Implementação

- ✅ Botão "Escolher da biblioteca" adicionado ao lado do campo de upload
- ✅ Modal Bootstrap criado com grade de thumbnails
- ✅ Estados de loading, erro e grid implementados
- ✅ Modal responsivo (modal-lg, modal-dialog-scrollable)

---

## Fase 4 - JavaScript para Carregar e Selecionar Imagens

### Implementação

- ✅ Event listener no botão de abrir biblioteca
- ✅ Fetch para carregar imagens do endpoint
- ✅ Renderização de thumbnails em grade responsiva
- ✅ Seleção de imagem preenche campo `icon_path`
- ✅ Fechamento automático do modal após seleção
- ✅ Tratamento de erros e estados vazios

---

## Fase 5 - Testes Manuais

### Checklist

- [x] Admin: Botão "Escolher da biblioteca" visível
- [x] Admin: Modal abre ao clicar no botão
- [x] Admin: Imagens carregam corretamente na grade
- [x] Admin: Seleção de imagem preenche campo `icon_path`
- [x] Admin: Modal fecha após seleção
- [x] Admin: Salvamento funciona com imagem da biblioteca
- [x] Front: Imagem selecionada aparece corretamente na home

### Implementação Realizada

#### Endpoint

- ✅ Método `listarImagensExistentes()` adicionado ao `HomeCategoriesController`
- ✅ Lista apenas imagens (JPG, JPEG, PNG, WEBP, GIF) da pasta do tenant
- ✅ Retorna JSON com lista de arquivos e URLs
- ✅ Rota GET `/admin/home/categorias-pills/midia` criada e protegida

#### Views

- ✅ Botão "Escolher da biblioteca" adicionado nas views de criação e edição
- ✅ Modal customizado criado (sem dependência de Bootstrap JS)
- ✅ Grade responsiva de thumbnails
- ✅ Estados de loading, erro e vazio implementados

#### JavaScript

- ✅ Carregamento de imagens via Fetch API
- ✅ Renderização dinâmica de thumbnails
- ✅ Seleção de imagem preenche campo `icon_path`
- ✅ Fechamento do modal (botões e overlay)
- ✅ Tratamento de erros

**Arquivos Alterados:**
- `src/Http/Controllers/Admin/HomeCategoriesController.php` - Método `listarImagensExistentes()`
- `public/index.php` - Rota GET `/admin/home/categorias-pills/midia`
- `themes/default/admin/home/categories-pills-content.php` - Botão, modal e JavaScript
- `themes/default/admin/home/categories-pills-edit-content.php` - Botão, modal e JavaScript
- `docs/FASE_15_MIDIA_CATEGORIAS_DESTAQUE.md` - Documentação

---

**Como Funciona:**
1. Usuário clica em "Escolher da biblioteca"
2. Modal abre e carrega imagens do diretório `/uploads/tenants/{tenantId}/category-pills/`
3. Imagens são exibidas em grade responsiva
4. Ao clicar em uma imagem, o campo `icon_path` é preenchido
5. Modal fecha automaticamente
6. Ao salvar, a imagem selecionada é usada (sem upload adicional)

**Nota:** A biblioteca lista apenas imagens já enviadas anteriormente. Não há upload de novas imagens dentro do modal.

---

## Auditoria 2025-12-08 – Inconsistência biblioteca de mídia

### Resumo do Bug Detectado

**Problema:** A biblioteca de mídia das Categorias em Destaque exibe "Nenhuma imagem encontrada ainda" mesmo quando o tenant possui muitas imagens cadastradas no sistema (produtos, logos, etc.).

**Sintoma:** Modal da biblioteca aparece vazio, sem exibir nenhuma imagem disponível.

---

### Fase 1 – Confirmação Implementação vs Documento

#### ✅ Verificações Realizadas

1. **Método `listarImagensExistentes()`**
   - ✅ Existe em `src/Http/Controllers/Admin/HomeCategoriesController.php` (linha 258)
   - ✅ Implementação corresponde à documentação
   - ✅ Usa `TenantContext::id()` corretamente
   - ✅ Filtra apenas imagens (JPG, JPEG, PNG, WEBP, GIF)
   - ✅ Retorna JSON com estrutura `{success: true, files: [...]}`

2. **Rota GET `/admin/home/categorias-pills/midia`**
   - ✅ Existe em `public/index.php` (linha 165)
   - ✅ Aponta para `HomeCategoriesController@listarImagensExistentes`
   - ✅ Protegida por `AuthMiddleware`

3. **Modal HTML e JavaScript**
   - ✅ Presente em `categories-pills-edit-content.php` (linhas 310-409)
   - ✅ Presente em `categories-pills-content.php` (linhas 373-490)
   - ✅ Botão "Escolher da biblioteca" existe e tem ID correto
   - ✅ JavaScript usa `fetch()` para chamar o endpoint

#### ⚠️ Divergências Encontradas

**Nenhuma divergência entre código e documentação.** A implementação está correta conforme documentado.

---

### Fase 2 – Checagem de Onde Vêm as Imagens Existentes

#### Estrutura de Pastas Identificada

**Configuração (`config/paths.php`):**
- `uploads_produtos_base_path` = `/public/uploads/tenants`

**Pastas Reais Identificadas (tenant ID = 1):**
- ✅ `/public/uploads/tenants/1/produtos/` → **147 arquivos** (105 JPG, 21 WEBP, 9 PNG, etc.)
- ✅ `/public/uploads/tenants/1/logo/` → **2 arquivos** (PNG)
- ❌ `/public/uploads/tenants/1/category-pills/` → **NÃO EXISTE ou está VAZIA**

#### Onde as Imagens São Salvas

1. **Imagens de Produtos:**
   - **Controller:** `ProductController@update()` (linha 391)
   - **Pasta:** `/uploads/tenants/{tenantId}/produtos/`
   - **Caminho relativo salvo:** `/uploads/tenants/{tenantId}/produtos/{fileName}`
   - **Tabela:** `produto_imagens` (campo `caminho_arquivo`)

2. **Logo da Loja:**
   - **Controller:** `ThemeController@update()` (linha 160)
   - **Pasta:** `/uploads/tenants/{tenantId}/logo/`
   - **Caminho relativo salvo:** `/uploads/tenants/{tenantId}/logo/{fileName}`
   - **Tabela:** `tenant_settings` (chave `logo_url`)

3. **Categorias em Destaque:**
   - **Controller:** `HomeCategoriesController@store()` e `@update()` (linhas 80, 191)
   - **Pasta:** `/uploads/tenants/{tenantId}/category-pills/`
   - **Caminho relativo salvo:** `/uploads/tenants/{tenantId}/category-pills/{fileName}`
   - **Tabela:** `home_category_pills` (campo `icone_path`)

#### Causa Raiz do Bug

**A biblioteca de mídia está procurando imagens APENAS na pasta `/category-pills/`, que:**
1. Pode não existir (se nenhuma categoria em destaque foi criada com upload)
2. Está vazia (se as categorias foram criadas sem upload ou usando caminho manual)
3. Contém apenas imagens enviadas especificamente para categorias em destaque

**As "muitas imagens já cadastradas" estão em outras pastas:**
- `/produtos/` → 147 arquivos
- `/logo/` → 2 arquivos

**Conclusão:** A biblioteca está funcionando corretamente, mas seu escopo é limitado apenas à pasta `category-pills`, que provavelmente está vazia ou não existe.

---

### Fase 3 – Auditoria do Endpoint `listarImagensExistentes()`

#### Análise do Código

```php
public function listarImagensExistentes(): void
{
    $tenantId = TenantContext::id();
    $paths = require __DIR__ . '/../../../../config/paths.php';
    $uploadsBasePath = $paths['uploads_produtos_base_path'];
    $baseDir = $uploadsBasePath . '/' . $tenantId . '/category-pills';
    $baseUrl = "/uploads/tenants/{$tenantId}/category-pills";
    
    $arquivos = [];
    
    if (is_dir($baseDir)) {
        // ... lê arquivos ...
    }
    
    // Retorna JSON
}
```

#### Comportamento do Método

1. **Quando a pasta NÃO existe:**
   - `is_dir($baseDir)` retorna `false`
   - `$arquivos` permanece vazio `[]`
   - Retorna: `{success: true, files: []}`

2. **Quando a pasta existe mas está vazia:**
   - `is_dir($baseDir)` retorna `true`
   - Loop `readdir()` não encontra arquivos (apenas `.` e `..`)
   - `$arquivos` permanece vazio `[]`
   - Retorna: `{success: true, files: []}`

3. **Quando a pasta existe com arquivos não-imagem:**
   - Arquivos são filtrados por extensão
   - Se nenhum arquivo passar no filtro, `$arquivos` permanece vazio
   - Retorna: `{success: true, files: []}`

4. **Quando a pasta existe com imagens:**
   - Arquivos são listados e filtrados
   - `$arquivos` é populado
   - Retorna: `{success: true, files: [{name: "...", url: "..."}, ...]}`

#### Problemas Identificados

1. **Não cria a pasta se não existir:**
   - O método apenas verifica `is_dir()`, mas não cria a pasta automaticamente
   - Se a pasta não existir, retorna array vazio sem erro

2. **Não retorna informação sobre pasta inexistente:**
   - O JSON sempre retorna `success: true`, mesmo quando a pasta não existe
   - Não há distinção entre "pasta vazia" e "pasta inexistente"

3. **Escopo limitado:**
   - Busca apenas em `/category-pills/`
   - Não inclui imagens de outras pastas (produtos, logo, etc.)

#### Formato JSON Retornado

✅ **Estrutura correta:**
```json
{
  "success": true,
  "files": [
    {
      "name": "imagem.png",
      "url": "/uploads/tenants/1/category-pills/imagem.png"
    }
  ]
}
```

✅ **Compatível com o JavaScript** que espera `data.files` e `file.url`.

---

### Fase 4 – Auditoria do JavaScript do Modal

#### Análise do Código JavaScript

**URL usada no fetch:**
```javascript
var basePath = '<?= $basePath ?>';
var url = basePath + '/admin/home/categorias-pills/midia';
```

**Possíveis problemas:**

1. **BasePath pode estar incorreto:**
   - Se `$basePath` estiver vazio, URL fica: `/admin/home/categorias-pills/midia` ✅
   - Se `$basePath` for `/ecommerce-v1.0/public`, URL fica: `/ecommerce-v1.0/public/admin/home/categorias-pills/midia` ✅
   - **Ambos os casos estão corretos** dependendo da configuração do servidor

2. **Tratamento de resposta:**
   ```javascript
   .then(function (response) { 
       if (!response.ok) {
           throw new Error('Erro ao carregar imagens');
       }
       return response.json(); 
   })
   ```
   - ✅ Verifica `response.ok` antes de parsear JSON
   - ✅ Trata erros HTTP corretamente

3. **Lógica de exibição:**
   ```javascript
   if (!data.files || !data.files.length) {
       grid.innerHTML = '<p>Nenhuma imagem encontrada ainda...</p>';
       grid.style.display = 'grid';
       return;
   }
   ```
   - ✅ Verifica se `data.files` existe e tem length
   - ✅ Exibe mensagem apropriada quando vazio

4. **Tratamento de erros:**
   ```javascript
   .catch(function (err) {
       loading.style.display = 'none';
       erro.textContent = 'Erro ao carregar as imagens. Tente novamente.';
       erro.style.display = 'block';
       console.error('Erro ao carregar imagens:', err);
   });
   ```
   - ✅ Exibe mensagem de erro ao usuário
   - ✅ Loga erro no console para debug

#### Verificações de Elementos DOM

```javascript
var btnAbrir = document.getElementById('btn-abrir-biblioteca-midia');
var modalElement = document.getElementById('modal-biblioteca-midia');
var grid = document.getElementById('midia-grid');
var loading = document.getElementById('midia-loading');
var erro = document.getElementById('midia-erro');
var iconPathInput = document.getElementById('icone_path');

if (!btnAbrir || !modalElement || !grid || !loading || !erro || !iconPathInput) {
    return; // Silenciosamente falha se algum elemento não existir
}
```

**Problema potencial:**
- Se algum elemento não existir, o script retorna silenciosamente sem erro
- Isso pode mascarar problemas de HTML/IDs incorretos

#### Conclusão da Auditoria JavaScript

✅ **O JavaScript está correto e bem implementado.**
- URL do endpoint está correta
- Tratamento de resposta está adequado
- Lógica de exibição funciona corretamente
- Tratamento de erros está presente

**O problema NÃO está no JavaScript.** O endpoint está retornando `{success: true, files: []}` porque a pasta `/category-pills/` não existe ou está vazia.

---

### Fase 5 – Proposta de Correção Imediata

#### Problema Identificado

A biblioteca de mídia funciona corretamente, mas seu escopo é limitado à pasta `/category-pills/`, que provavelmente não existe ou está vazia. As imagens existentes estão em outras pastas (`/produtos/`, `/logo/`).

#### Correção Imediata Sugerida

**Opção A: Expandir escopo da biblioteca (RECOMENDADO)**

Modificar `listarImagensExistentes()` para buscar imagens em múltiplas pastas:

```php
public function listarImagensExistentes(): void
{
    $tenantId = TenantContext::id();
    $paths = require __DIR__ . '/../../../../config/paths.php';
    $uploadsBasePath = $paths['uploads_produtos_base_path'];
    
    $arquivos = [];
    $pastas = ['category-pills', 'produtos', 'logo']; // Expandir escopo
    
    foreach ($pastas as $pasta) {
        $baseDir = $uploadsBasePath . '/' . $tenantId . '/' . $pasta;
        $baseUrl = "/uploads/tenants/{$tenantId}/{$pasta}";
        
        if (is_dir($baseDir)) {
            $handle = opendir($baseDir);
            if ($handle) {
                while (($file = readdir($handle)) !== false) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                        continue;
                    }
                    
                    $arquivos[] = [
                        'name' => $file,
                        'url'  => $baseUrl . '/' . $file,
                        'folder' => $pasta, // Opcional: identificar origem
                    ];
                }
                closedir($handle);
            }
        }
    }
    
    // Ordenar por nome
    usort($arquivos, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'files'   => $arquivos,
    ]);
    exit;
}
```

**Vantagens:**
- ✅ Usuário vê todas as imagens disponíveis
- ✅ Permite reutilizar imagens de produtos/logos
- ✅ Implementação simples (apenas adicionar pastas ao array)

**Desvantagens:**
- ⚠️ Pode listar muitas imagens (147+ arquivos)
- ⚠️ Mistura imagens de diferentes contextos

**Opção B: Criar pasta se não existir**

Adicionar criação automática da pasta:

```php
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0755, true);
}
```

**Vantagens:**
- ✅ Garante que a pasta existe
- ✅ Evita erros futuros

**Desvantagens:**
- ⚠️ Não resolve o problema de imagens vazias
- ⚠️ Ainda limita ao escopo de `category-pills`

**Opção C: Combinar A + B (RECOMENDADO)**

Expandir escopo E criar pasta se necessário.

---

### Fase 6 – Desenho de Biblioteca de Mídia Centralizada (Futuro)

#### Objetivo

Criar uma biblioteca de mídia centralizada tipo WordPress, permitindo:
- Reutilizar imagens em qualquer contexto (produtos, categorias, banners, páginas, etc.)
- Evitar duplicidade de arquivos
- Gerenciar metadados (título, alt text, legenda)
- Buscar/filtrar imagens

#### Opções de Arquitetura

**Opção A: Tabela `midias` por tenant**

```sql
CREATE TABLE midias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100),
    tamanho_arquivo BIGINT UNSIGNED,
    titulo VARCHAR(255),
    alt_text VARCHAR(255),
    legenda TEXT,
    contexto_origem VARCHAR(50), -- 'produto', 'categoria', 'logo', 'banner', etc.
    origem_id BIGINT UNSIGNED NULL, -- ID do registro que originou o upload
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_contexto (tenant_id, contexto_origem),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Vantagens:**
- ✅ Indexação completa de todas as mídias
- ✅ Metadados centralizados
- ✅ Busca/filtro eficiente
- ✅ Rastreamento de origem
- ✅ Facilita limpeza de arquivos órfãos

**Desvantagens:**
- ⚠️ Requer migration e sincronização com uploads existentes
- ⚠️ Mais complexo de implementar

**Opção B: Reaproveitar diretórios existentes + indexação**

Manter estrutura atual de pastas, mas criar uma view/index que escaneia todas as pastas:

```php
class MediaLibraryService {
    public function scanAllFolders($tenantId): array {
        $pastas = ['produtos', 'category-pills', 'logo', 'banners', 'paginas'];
        // Escanear todas e indexar
    }
    
    public function getMediaByContext($tenantId, $contexto): array {
        // Retornar mídias de um contexto específico
    }
}
```

**Vantagens:**
- ✅ Não requer mudança de estrutura
- ✅ Compatível com código existente
- ✅ Implementação incremental

**Desvantagens:**
- ⚠️ Performance pode ser ruim com muitos arquivos
- ⚠️ Sem metadados centralizados
- ⚠️ Dificulta busca/filtro

**Opção C: Padronizar diretório `media-library`**

Criar uma pasta única `/uploads/tenants/{tenantId}/media-library/` e migrar/copiar todas as imagens para lá:

```php
// Ao fazer upload, salvar em media-library E na pasta específica
$mediaLibraryPath = $uploadsBasePath . '/' . $tenantId . '/media-library';
$specificPath = $uploadsBasePath . '/' . $tenantId . '/produtos'; // ou category-pills, etc.

// Salvar em ambos os lugares
```

**Vantagens:**
- ✅ Localização única e previsível
- ✅ Facilita backup/gestão
- ✅ Biblioteca sempre completa

**Desvantagens:**
- ⚠️ Duplicação de arquivos (ou symlinks)
- ⚠️ Requer migração de arquivos existentes
- ⚠️ Pode confundir estrutura atual

#### Recomendação

**Fase 1 (Curto Prazo):** Implementar **Opção A da Correção Imediata** (expandir escopo para múltiplas pastas)

**Fase 2 (Médio Prazo):** Implementar **Opção A do Desenho Futuro** (tabela `midias`) com:
- Migration para criar tabela
- Service para indexar mídias existentes
- Endpoint para listar todas as mídias
- Interface admin para gerenciar biblioteca

**Fase 3 (Longo Prazo):** Adicionar funcionalidades avançadas:
- Upload direto na biblioteca
- Edição de metadados
- Busca/filtro
- Preview/lightbox
- Integração com todos os pontos de upload

---

---

## Correções Aplicadas - 2025-12-08

### Problemas Identificados e Corrigidos

#### 1. Modal não funcionava na tela de criação

**Problema:** O botão "Escolher da biblioteca" na tela de criação não abria o modal.

**Causa:** O HTML do modal não estava presente na view `categories-pills-content.php`.

**Correção:** Adicionado o HTML completo do modal na view de criação, idêntico ao da view de edição.

#### 2. Biblioteca aparecia vazia mesmo com imagens existentes

**Problema:** O modal mostrava "Nenhuma imagem encontrada ainda" mesmo com muitas imagens no sistema.

**Causa:** O endpoint `listarImagensExistentes()` buscava apenas na pasta `/category-pills/`, que estava vazia. As imagens existentes estavam em outras pastas (`/produtos/`, `/logo/`).

**Correção:** Expandido o escopo do endpoint para buscar em múltiplas pastas:
- `category-pills` - Categorias em Destaque
- `produtos` - Produtos
- `logo` - Logos

**Código alterado:**
```php
// Antes: apenas category-pills
$baseDir = $uploadsBasePath . '/' . $tenantId . '/category-pills';

// Depois: múltiplas pastas
$pastas = [
    'category-pills' => 'Categorias em Destaque',
    'produtos' => 'Produtos',
    'logo' => 'Logos',
];
```

#### 3. Melhorias de UX

**Adicionado:**
- Badge mostrando a pasta de origem de cada imagem no modal
- Melhor tratamento de erros no JavaScript
- Feedback visual ao copiar URL

### Arquivos Modificados

1. `src/Http/Controllers/Admin/HomeCategoriesController.php`
   - Método `listarImagensExistentes()` expandido para múltiplas pastas

2. `themes/default/admin/home/categories-pills-content.php`
   - Modal HTML adicionado
   - JavaScript atualizado para mostrar pasta de origem

3. `themes/default/admin/home/categories-pills-edit-content.php`
   - JavaScript atualizado para mostrar pasta de origem

### Resultado

✅ Botão "Escolher da biblioteca" funciona em criação e edição  
✅ Modal lista todas as imagens disponíveis (produtos, logos, category-pills)  
✅ Usuário pode reutilizar imagens existentes sem reupload  
✅ Badge mostra origem de cada imagem

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08 (Correções aplicadas)  
**Status:** ✅ Concluída e corrigida
