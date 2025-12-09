# Fase 16: Biblioteca de Mídia Global

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Implementação](#implementação)
- [Uso](#uso)
- [Evolução Futura](#evolução-futura)

---

## Visão Geral

Esta fase cria uma **Biblioteca de Mídia Global** no admin, permitindo visualizar, buscar e gerenciar todas as imagens do tenant em um único lugar.

**Status:** ✅ Concluída

---

## Arquitetura

### Service

**Arquivo:** `src/Services/MediaLibraryService.php`

**Métodos principais:**
- `listarImagensDoTenant(int $tenantId, ?string $folder = null): array` - Lista todas as imagens, opcionalmente filtradas por pasta
- `buscarImagens(int $tenantId, string $query): array` - Busca imagens por nome de arquivo
- `getEstatisticas(int $tenantId): array` - Retorna estatísticas por pasta (contagem, tamanho total)

**Pastas escaneadas:**
- `category-pills` - Categorias em Destaque
- `produtos` - Imagens de produtos
- `logo` - Logos da loja
- `banners` - Banners (se existir)

### Controller

**Arquivo:** `src/Http/Controllers/Admin/MediaLibraryController.php`

**Métodos:**
- `index()` - Renderiza a página principal da biblioteca
- `listar()` - Endpoint JSON para consumo assíncrono (opcional)
- `upload()` - Endpoint para upload de novas imagens via POST (multipart/form-data)

### Rotas

- `GET /admin/midias` → `MediaLibraryController@index`
- `GET /admin/midias/listar` → `MediaLibraryController@listar` (JSON)
- `POST /admin/midias/upload` → `MediaLibraryController@upload` (JSON) - Upload de nova imagem

### View

**Arquivo:** `themes/default/admin/media/index.php`

**Funcionalidades:**
- Grid de thumbnails com todas as imagens
- Busca por nome de arquivo
- Filtro por pasta
- Estatísticas por pasta
- Botão "Copiar URL" para cada imagem

---

## Implementação

### Estrutura de Dados

Cada imagem retornada contém:
```php
[
    'url' => '/uploads/tenants/1/produtos/imagem.jpg',
    'filename' => 'imagem.jpg',
    'folder' => 'produtos',
    'folderLabel' => 'Produtos',
    'size' => 123456, // bytes
]
```

### Menu Admin

Item adicionado no menu lateral:
- **Label:** "Biblioteca de Mídia"
- **Ícone:** `bi-images`
- **Link:** `/admin/midias`

---

## Uso

### Acessar Biblioteca

1. No menu admin, clique em "Biblioteca de Mídia"
2. Visualize todas as imagens em grid
3. Use a busca para encontrar imagens específicas
4. Filtre por pasta usando o dropdown
5. Clique em "Copiar URL" para copiar o caminho da imagem

### Integração com Outros Módulos

A biblioteca pode ser reutilizada em outros pontos do sistema:

**Exemplo - Modal genérico:**
```javascript
// Abrir modal da biblioteca
function abrirBibliotecaModal(callback) {
    // Carregar imagens via /admin/midias/listar
    // Exibir em modal
    // Ao selecionar, chamar callback(url)
}
```

**Futuro:** Outros módulos (banners, páginas institucionais) poderão usar a mesma biblioteca.

---

## Evolução Futura

### Fase 1: Indexação em Banco de Dados

Criar tabela `midias` para indexar todas as mídias:

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
    contexto_origem VARCHAR(50),
    origem_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_contexto (tenant_id, contexto_origem),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

**Vantagens:**
- Busca mais rápida
- Metadados centralizados
- Rastreamento de origem
- Facilita limpeza de arquivos órfãos

### Fase 2: Upload Direto na Biblioteca

Permitir upload de imagens diretamente na biblioteca, sem contexto específico.

### Fase 3: Edição de Metadados

Interface para editar título, alt text, legenda de cada imagem.

### Fase 4: Modal Reutilizável ✅

**Status:** ✅ Implementado

Componente modal genérico criado e disponível em qualquer tela do admin.

**Arquivo:** `public/admin/js/media-picker.js`

**Uso:**

1. **HTML - Botão para abrir biblioteca:**
```html
<button type="button" 
        class="js-open-media-library admin-btn admin-btn-primary" 
        data-media-target="#campo_imagem">
    <i class="bi bi-image icon"></i> Escolher da biblioteca
</button>
```

2. **HTML - Input que será preenchido:**
```html
<input type="text" 
       id="campo_imagem" 
       name="imagem" 
       placeholder="Selecione uma imagem na biblioteca"
       readonly>
```

3. **Funcionamento:**
   - O script `media-picker.js` é carregado automaticamente no layout do admin
   - Ao clicar no botão com classe `.js-open-media-library`, o modal abre
   - O atributo `data-media-target` indica qual input será preenchido
   - Dentro do modal, o usuário pode:
     - Ver todas as imagens da biblioteca
     - Fazer upload de nova imagem
     - Selecionar uma imagem existente
   - Ao selecionar, o input é preenchido com o caminho da imagem e o modal fecha

**Integração:**
- ✅ Banners Hero (`themes/default/admin/home/banners-form-content.php`)
- ✅ Categorias em Destaque (`themes/default/admin/home/categories-pills-edit-content.php`)
- ✅ Disponível para uso em qualquer formulário do admin

**Endpoint de Upload:**
- `POST /admin/midias/upload`
- Parâmetros: 
  - `imagens[]` (array de arquivos) - **Multi-upload suportado**
  - `file` (arquivo único) - Compatibilidade com código antigo
  - `folder` (opcional, padrão: 'banners')
- Retorna JSON: 
  ```json
  {
    "success": true,
    "message": "X imagem(ns) enviada(s) com sucesso.",
    "uploaded": [
      { "url": "/uploads/...", "filename": "...", "originalName": "..." }
    ],
    "errors": ["arquivo1.jpg: erro ao salvar", ...]
  }
  ```
- **Multi-upload:** O endpoint processa múltiplos arquivos de uma vez. Arquivos inválidos são reportados em `errors`, mas não impedem o upload dos válidos.

---

**Arquivos Criados:**
- `src/Services/MediaLibraryService.php`
- `src/Http/Controllers/Admin/MediaLibraryController.php`
- `themes/default/admin/media/index.php`

**Arquivos Modificados:**
- `public/index.php` - Rotas adicionadas (incluindo upload)
- `themes/default/admin/layouts/store.php` - Item de menu adicionado + script media-picker.js incluído
- `themes/default/admin/home/banners-form-content.php` - Integração com Media Picker
- `themes/default/admin/home/categories-pills-edit-content.php` - Refatorado para usar Media Picker genérico
- `src/Http/Controllers/Admin/MediaLibraryController.php` - Corrigido caminho de `paths.php` (usando `dirname(__DIR__, 4)`) + implementado multi-upload
- `public/admin/js/media-picker.js` - Adicionado suporte a multi-upload (input `multiple`, processamento de array de arquivos)

**Arquivos Criados:**
- `public/admin/js/media-picker.js` - Componente genérico de Media Picker

---

## Correções e Melhorias

### Correção do Caminho de `paths.php`

**Problema:** O método `upload()` estava usando `__DIR__ . '/../../../config/paths.php'`, que resultava em caminho incorreto no Windows.

**Solução:** Alterado para `dirname(__DIR__, 4) . '/config/paths.php'`, que sobe 4 níveis a partir de `src/Http/Controllers/Admin` até a raiz do projeto.

### Multi-Upload

**Implementação:**

1. **Frontend (`public/admin/js/media-picker.js`):**
   - Input file agora tem atributo `multiple`
   - Nome do campo: `imagens[]` (array)
   - Aceita: `image/jpeg,image/jpg,image/png,image/webp,image/gif`
   - Mensagem de status mostra quantidade de arquivos sendo enviados
   - Exibe contagem de sucessos e erros após upload

2. **Backend (`src/Http/Controllers/Admin/MediaLibraryController.php`):**
   - Suporta tanto `imagens[]` (múltiplos) quanto `file` (único, compatibilidade)
   - Processa cada arquivo individualmente em loop
   - Validação independente para cada arquivo (tipo, tamanho, permissões)
   - Arquivos inválidos são reportados em `errors`, mas não impedem o upload dos válidos
   - Retorna array `uploaded` com detalhes de cada arquivo enviado com sucesso
   - Retorna array `errors` com mensagens de erro para cada arquivo que falhou

**Comportamento:**
- Usuário pode selecionar múltiplas imagens (Ctrl+clique ou Shift+clique)
- Todas as imagens válidas são processadas e salvas
- Imagens inválidas são ignoradas e reportadas
- Lista de mídias é recarregada automaticamente após upload bem-sucedido
- Mensagens de status mostram quantas imagens foram enviadas e quantas falharam

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** ✅ Concluída

---

## Correções de Upload e Listagem (2025-12-08)

### Problemas Identificados e Corrigidos

1. **Upload salva mas não lista:**
   - **Causa:** `loadImages()` não passava parâmetro `folder`, então listava todas as pastas, enquanto upload salvava em pasta específica
   - **Solução:** `loadImages()` agora aceita parâmetro `folder` e passa para o endpoint `/admin/midias/listar?folder=...`
   - **Resultado:** Após upload, a grade recarrega mostrando apenas imagens da pasta correta

2. **Multi-upload não funcional:**
   - **Causa:** Input já tinha `multiple`, mas o problema real era a falta de sincronização entre pasta de upload e pasta de listagem
   - **Solução:** Implementado sistema de detecção de `folder`:
     - Botões podem ter `data-folder="banners"` ou `data-folder="category-pills"`
     - Se não especificado, infere do contexto (imagem_desktop/mobile → banners, icone_path → category-pills)
     - `currentFolder` é mantido globalmente e usado tanto no upload quanto na listagem

3. **Recarga da grade após upload:**
   - **Causa:** `loadImages()` era chamado sem parâmetro após upload
   - **Solução:** Após upload bem-sucedido, `loadImages(folderToUse)` é chamado com o mesmo `folder` usado no upload
   - **Resultado:** Grade recarrega imediatamente mostrando as novas imagens

### Mudanças Técnicas

**JavaScript (`public/admin/js/media-picker.js`):**
- Adicionada variável global `currentFolder` (padrão: 'banners')
- `loadImages(folder)` agora aceita parâmetro opcional `folder`
- `openMediaLibraryWithEvent()` detecta `folder` do botão (`data-folder`) ou infere do contexto
- `handleUpload()` usa `currentFolder` para enviar arquivos para a pasta correta
- Após upload, `loadImages(folderToUse)` recarrega a grade com o mesmo `folder`

**Views:**
- Botões em `banners-form-content.php` agora têm `data-folder="banners"`
- Botões em `categories-pills-edit-content.php` agora têm `data-folder="category-pills"`

### Comportamento Esperado

1. **Upload único:**
   - Seleciona 1 imagem → Envia → Mensagem de sucesso → Grade recarrega mostrando a nova imagem na pasta correta

2. **Multi-upload:**
   - Seleciona múltiplas imagens (Ctrl+clique) → Envia → Mensagem mostra quantidade → Grade recarrega com todas as novas imagens

3. **Integração:**
   - Clicar em imagem na grade → Campo de input é preenchido → Salvar formulário → Imagem aparece no frontend

---

## Testes Recomendados

### Teste 1: Upload Único
1. Abrir `/admin/home/banners/novo`
2. Clicar "Escolher da biblioteca"
3. Selecionar 1 imagem e enviar
4. Verificar que imagem aparece na grade

### Teste 2: Multi-Upload
1. No mesmo modal, selecionar 3-4 imagens de uma vez (Ctrl+clique)
2. Clicar "Enviar"
3. Verificar:
   - Mensagem mostra quantidade de imagens sendo enviadas
   - Todas as imagens válidas aparecem na grade após upload
   - Se houver erro em alguma imagem, mensagem mostra sucessos e erros

### Teste 3: Upload com Erros
1. Tentar enviar arquivo com extensão inválida (ex: .txt)
2. Verificar que erro é reportado mas não trava o sistema
3. Tentar enviar arquivo muito grande (>5MB)
4. Verificar mensagem de erro apropriada

### Teste 4: Seleção de Imagem
1. Após upload, clicar em uma imagem na grade
2. Verificar que campo de input é preenchido
3. Verificar que modal não fecha automaticamente (permite trocar seleção)

