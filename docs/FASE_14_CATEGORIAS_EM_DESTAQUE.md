# Fase 14: Renomear "Bolotas" + Upload de Imagem nas Categorias em Destaque

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Diagnóstico](#fase-1---diagnóstico-rápido)
- [Fase 2 - Renomear na UI](#fase-2---renomear-bolotas-para-categorias-em-destaque-na-ui)
- [Fase 3 - Upload de Imagem](#fase-3---adicionar-upload-de-imagem-no-admin)
- [Fase 4 - Frontend](#fase-4---garantir-uso-da-imagem-no-front)
- [Fase 5 - Testes](#fase-5---testes-manuais)
- [Fase 6 - Documentação](#fase-6---documentação)

---

## Visão Geral

Esta fase renomeia "Bolotas de Categorias" para "Categorias em Destaque" na interface e adiciona upload de imagem direto no formulário.

**Status:** ✅ Concluída

---

## Fase 1 - Diagnóstico Rápido

### Tabela e Modelo

- **Tabela:** `home_category_pills`
- **Campos principais:**
  - `id` - ID do registro
  - `tenant_id` - ID do tenant
  - `categoria_id` - ID da categoria
  - `label` - Label customizado (opcional)
  - `icone_path` - Caminho do ícone/imagem
  - `ordem` - Ordem de exibição
  - `ativo` - Status ativo/inativo
  - `created_at`, `updated_at` - Timestamps

### Admin - Controller

- **Arquivo:** `src/Http/Controllers/Admin/HomeCategoriesController.php`
- **Métodos:**
  - `index()` - Lista categorias em destaque
  - `store()` - Cria nova categoria em destaque
  - `edit($id)` - Formulário de edição
  - `update($id)` - Atualiza categoria em destaque
  - `destroy($id)` - Remove categoria em destaque
- **Rota:** `/admin/home/categorias-pills`

### Admin - Views

- **Listagem:** `themes/default/admin/home/categories-pills-content.php`
- **Edição:** `themes/default/admin/home/categories-pills-edit-content.php`
- **Estrutura do formulário:**
  - Categoria (select obrigatório)
  - Label (opcional)
  - Caminho do Ícone (input text opcional)
  - Ordem (number)
  - Ativo (checkbox)

### Infra de Upload

- **Logo da Loja:** Implementado em `ThemeController@update()`
- **Padrão usado:**
  - Validação de tipo MIME
  - Caminho base via `config/paths.php`
  - Salva em `/uploads/tenants/{tenantId}/logo/`
  - Sanitização de nome de arquivo
  - Caminho relativo salvo em `ThemeConfig`

---

## Fase 2 - Renomear "Bolotas" para "Categorias em Destaque" na UI

### Alterações Realizadas

- ✅ Título da página: "Bolotas de Categorias" → "Categorias em Destaque"
- ✅ Título do formulário: "Adicionar Nova Bolota" → "Adicionar Categoria em Destaque"
- ✅ Título da lista: "Bolotas Configuradas" → "Categorias em Destaque Configuradas"
- ✅ Mensagens de feedback atualizadas
- ✅ Título de edição: "Editar Bolota" → "Editar Categoria em Destaque"
- ✅ Menu lateral: "Bolotas de Categorias" → "Categorias em Destaque"

---

## Fase 3 - Adicionar Upload de Imagem no Admin

### Formulário Atualizado

- ✅ Campo de upload de imagem adicionado antes do campo de caminho manual
- ✅ Campo de caminho manual mantido como alternativa avançada
- ✅ Form com `enctype="multipart/form-data"`

### Controller Atualizado

- ✅ Processamento de upload em `store()` e `update()`
- ✅ Validação de tipo de arquivo (JPG, PNG, WEBP, GIF, SVG)
- ✅ Salvamento em `/uploads/tenants/{tenantId}/category-pills/`
- ✅ Sanitização de nome de arquivo
- ✅ Preenchimento automático de `icone_path` quando há upload
- ✅ Mantém caminho manual se não houver upload

---

## Fase 4 - Garantir Uso da Imagem no Front

### Verificação

- ✅ Front já usa `icone_path` para exibir imagens
- ✅ Fallback existente quando não há imagem
- ✅ Nenhuma alteração necessária no frontend

---

## Fase 5 - Testes Manuais

### Checklist

- [x] Admin: título "Categorias em Destaque" visível
- [x] Admin: formulário com campo de upload funcionando
- [x] Admin: upload de imagem salva corretamente
- [x] Admin: caminho manual ainda funciona como alternativa
- [x] Front: imagens aparecem corretamente nos círculos
- [x] Multi-tenant: uploads isolados por tenant

### Implementação Realizada

#### Renomeação na UI

- ✅ Controller: `pageTitle` atualizado para "Categorias em Destaque"
- ✅ View listagem: "Adicionar Nova Bolota" → "Adicionar Categoria em Destaque"
- ✅ View listagem: "Bolotas Configuradas" → "Categorias em Destaque Configuradas"
- ✅ View edição: "Editar Bolota" → "Editar Categoria em Destaque"
- ✅ Página index home: "Faixa de Categorias (Bolotas)" → "Categorias em Destaque"
- ✅ Mensagens de feedback atualizadas
- ✅ Comentário em rotas atualizado

#### Upload de Imagem

- ✅ Campo de upload adicionado antes do campo manual
- ✅ Validação de tipo de arquivo (JPG, PNG, WEBP, GIF, SVG)
- ✅ Salvamento em `/uploads/tenants/{tenantId}/category-pills/`
- ✅ Sanitização de nome de arquivo
- ✅ Preenchimento automático de `icone_path` quando há upload
- ✅ Remoção de imagem antiga ao atualizar (se diferente)
- ✅ Campo manual mantido como alternativa avançada
- ✅ Preview da imagem atual na tela de edição

#### Frontend

- ✅ Front já usa `icone_path` corretamente
- ✅ Fallback existente quando não há imagem
- ✅ Nenhuma alteração necessária no frontend

---

## Fase 6 - Documentação

**Arquivos Alterados:**
- `src/Http/Controllers/Admin/HomeCategoriesController.php` - Renomeação e upload
- `themes/default/admin/home/categories-pills-content.php` - UI renomeada e campo de upload
- `themes/default/admin/home/categories-pills-edit-content.php` - UI renomeada e campo de upload
- `themes/default/admin/layouts/store.php` - Menu lateral atualizado

**Como Funciona:**
- Upload salva em `/uploads/tenants/{tenantId}/category-pills/`
- Caminho relativo salvo em `icone_path` (ex: `/uploads/tenants/1/category-pills/imagem.png`)
- Campo manual mantido como alternativa avançada
- Frontend usa `icone_path` diretamente

---

**Arquivos Alterados:**
- `src/Http/Controllers/Admin/HomeCategoriesController.php` - Renomeação, método `sanitizeFileName()`, upload em `store()` e `update()`
- `themes/default/admin/home/categories-pills-content.php` - Renomeação, campo de upload, `enctype="multipart/form-data"`
- `themes/default/admin/home/categories-pills-edit-content.php` - Renomeação, campo de upload, preview de imagem atual, `enctype="multipart/form-data"`
- `themes/default/admin/home/index-content.php` - Renomeação do card
- `themes/default/admin/home/index.php` - Renomeação do card
- `public/index.php` - Comentário atualizado

**Como Funciona:**
- Upload salva em `/uploads/tenants/{tenantId}/category-pills/`
- Caminho relativo salvo em `icone_path` (ex: `/uploads/tenants/1/category-pills/imagem.png`)
- Campo manual mantido como alternativa avançada
- Frontend usa `icone_path` diretamente para exibir imagens nos círculos

**Nota:** Nenhuma mudança de schema de banco. Apenas renomeação de labels na UI e adição de upload de imagem.

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** ✅ Concluída

