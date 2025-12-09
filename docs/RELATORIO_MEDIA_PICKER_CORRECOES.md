# Relatório Técnico – Media Picker (Modal)  

**Contexto:** o modal “Escolher da biblioteca” abre, mas não lista as imagens existentes. Esta auditoria documenta todo o código, lógica e implementações realizadas até agora para corrigir o carregamento do `media-picker.js` e a listagem de mídias no modal. Nenhuma nova alteração foi aplicada neste relatório; ele apenas registra o que já foi feito.

---

## 1) Arquitetura e Fluxo do Media Picker

- **Front (JS):** `public/admin/js/media-picker.js`  
  - Cria o modal, trata cliques em `.js-open-media-library`, detecta `basePath`, chama `loadImages(folder)`, renderiza grid, e preenche o input alvo ao selecionar.
- **Endpoint JSON:** `GET /admin/midias/listar`  
  - Rota definida em `public/index.php`  
  - Controller: `src/Http/Controllers/Admin/MediaLibraryController.php::listar()`  
  - Service: `src/Services/MediaLibraryService.php::listarImagensDoTenant()` (varre pastas permitidas e devolve array de arquivos).
- **Layout Admin:** `themes/default/admin/layouts/store.php`  
  - Inclui `media-picker.js` via helper `admin_asset_path()` e expõe `window.basePath`.

---

## 2) Código Relevante (Estado Atual)

### 2.1 JavaScript – `public/admin/js/media-picker.js`
- **Detecção de basePath:** tenta extrair do `src` do script (`.../admin/js/media-picker.js`); fallback em `window.basePath`; se vazio, usa `''` (produção).
- **URL do endpoint:**  
  ```javascript
  var url = '/admin/midias/listar';
  if (basePath && basePath !== '') {
      var cleanBasePath = basePath.replace(/\/$/, '');
      url = cleanBasePath + url;
  }
  if (folderToUse) {
      url += '?folder=' + encodeURIComponent(folderToUse);
  }
  ```
- **Fetch e validação:** logs detalhados (`data.success`, `data.files`, `data.count`); valida se `data` é objeto e `data.files` é array; mensagens de erro mais claras; mantém fallback “Nenhuma imagem encontrada...” se array vazio.
- **Renderização das imagens:** usa `file.url` (relativo, ex. `/uploads/tenants/1/...`); concatena `basePath` apenas em dev; `onerror` remove item quebrado; grava `data-url` com a URL relativa.
- **Seleção:** clique destaca a imagem; duplo clique seleciona e fecha; botão “Usar imagem selecionada” preenche o input de destino (`data-media-target`).

### 2.2 Controller – `src/Http/Controllers/Admin/MediaLibraryController.php::listar()`
- **Robustez de saída:** `ob_start()`/`ob_clean()`, desliga display_errors, garante JSON limpo.
- **Lógica:** lê `folder` e `q`; chama `MediaLibraryService::listarImagensDoTenant` ou `buscarImagens`.
- **Resposta:** `{ success: true, files: [...], count: N }` com `JSON_UNESCAPED_SLASHES|UNICODE`.
- **Erro:** HTTP 500 com `{ success: false, message, files: [] }`.

### 2.3 Service – `src/Services/MediaLibraryService.php::listarImagensDoTenant()`
- Escaneia pastas: `category-pills`, `produtos`, `logo`, `banners`.
- Cada item: `url`, `filename`, `folder`, `folderLabel`, `size`.
- Usa `config/paths.php` (`uploads_produtos_base_path`) para localizar arquivos físicos; URLs geradas como `/uploads/tenants/{tenant}/{pasta}/{arquivo}`.

### 2.4 Layout – `themes/default/admin/layouts/store.php`
- **Helper `admin_asset_path($relativePath)`:**
  - Dev: `/ecommerce-v1.0/public/admin/{relativePath}`
  - Produção (Hostinger, DocumentRoot=public_html/): `/public/admin/{relativePath}`
- **Inclusão do script:**
  ```php
  $mediaPickerPath = admin_asset_path('js/media-picker.js');
  <script src="<?= htmlspecialchars($mediaPickerPath) ?>"></script>
  <script>window.basePath = '<?= htmlspecialchars($basePath) ?>';</script>
  ```
- `basePath` no layout: em produção fica vazio; em dev `/ecommerce-v1.0/public`.

### 2.5 Rota – `public/index.php`
- `GET /admin/midias/listar` → `MediaLibraryController@listar` (com `AuthMiddleware`).
- Outras rotas do picker: `/admin/midias` (tela cheia), `/admin/midias/upload`.

---

## 3) Problema Observado (produção)
- Modal abre, upload aparece, mas grid retorna “Nenhuma imagem encontrada...”.
- Na página `/admin/midias` (tela cheia) tudo funciona (contadores e thumbs corretos).
- Suspeita:  
  - (a) Construção da URL ainda recebendo resposta vazia ou filtrada;  
  - (b) `basePath` em produção = `''` (correto), endpoint `/admin/midias/listar` deveria responder JSON;  
  - (c) Possível diferença de ambiente/autorização/cabeçalhos na chamada fetch em páginas de formulário.

---

## 4) Testes Recomendados (sem novas mudanças)

1) Console/Network no modal  
   - Confirmar fetch para `/admin/midias/listar` (produção: sem `public` na URL).  
   - Status HTTP 200 e payload `{success:true, files:[...], count:N}`.  
   - Ver se `data.files.length` > 0 nos logs do console.

2) Comparar resposta do endpoint  
   - Acessar diretamente `/admin/midias/listar` autenticado (via browser) e verificar JSON.  
   - Se vier vazio, checar `config/paths.php` (`uploads_produtos_base_path`) e permissões das pastas em `public_html/uploads/tenants/1/...`.

3) Autorização / sessão  
   - Garantir que a sessão de admin nas telas de formulário é válida; se cookie/sessão diferir, o endpoint pode retornar vazio ou redirecionar.

---

## 5) Commits Relacionados (já aplicados)
- `29f4bbf` – Correção de URL do endpoint, validação e logs no JS, tratamento robusto em `listar()`.
- `802fcc4` – Atualização da documentação detalhando todas as correções.

---

## 6) Resumo do Estado Atual
- **JS:** Usa URL absoluta `/admin/midias/listar`, com fallback de `basePath` vazio em produção; valida respostas e loga quantidades.  
- **Backend:** `listar()` sempre retorna JSON e trata erros; `MediaLibraryService` lista pastas padrão.  
- **Layout:** `media-picker.js` servido por `admin_asset_path()` compatível com dev/produção; `window.basePath` exposto.  
- **Persistente:** Problema de “grid vazio” persiste em produção; requer inspeção da resposta real do endpoint na página afetada (Network + console) e verificação de permissão/caminho físico.

---

## 7) Próximos Passos Sugeridos (investigação, sem aplicar código)
- Capturar em produção: resposta exata de `/admin/midias/listar` quando chamada de dentro do modal (Network → Preview/Response).
- Confirmar se `count` no JSON é > 0; se for 0, inspecionar `uploads_produtos_base_path` e permissões em `public_html/uploads/tenants/1`.
- Validar se o fetch está recebendo redirecionamento/login (código 302/401) em ambiente de formulário; se sim, checar cookies de sessão/admin nessas páginas.

---

Este documento é apenas descritivo e não aplica alterações novas. Todo o código citado já foi previamente commitado e enviado para o repositório.

