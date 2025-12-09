# FASE 19 - Banners da Home - Refatoração Completa de UX

## PARTE 1 - Auditoria da Implementação Atual

### Arquivos Encontrados

#### Controller Admin
- **`src/Http/Controllers/Admin/HomeBannersController.php`**
  - `index()` - Lista banners com filtro por tipo (`hero` ou `portrait`)
  - `create()` - Formulário de novo banner (recebe `tipo` via query string)
  - `store()` - Salva novo banner
  - `edit()` - Formulário de edição
  - `update()` - Atualiza banner existente
  - `destroy()` - Exclui banner
  - `reordenar()` - Reordena banners via AJAX (NOVO)

#### Views Admin
- **`themes/default/admin/home/banners-content.php`** - Listagem de banners com abas de filtro + drag-and-drop
- **`themes/default/admin/home/banners-form-content.php`** - Formulário de criação/edição com tipo fixo

#### Controller Storefront
- **`src/Http/Controllers/Storefront/HomeController.php`**
  - Busca banners `tipo = 'hero'` e `ativo = 1` → `$heroBanners`
  - Busca banners `tipo = 'portrait'` e `ativo = 1` → `$portraitBanners`
  - Passa ambos para a view `storefront/home`

#### View Storefront
- **`themes/default/storefront/home.php`**
  - Renderiza carrossel hero com `$heroBanners` (linha ~1364)
  - Renderiza seção de banners portrait com `$portraitBanners` (linha ~1491)
  - Implementa fallback desktop/mobile

#### Database
- **Tabela:** `banners`
  - `tipo` ENUM('hero', 'portrait') NOT NULL
  - `imagem_desktop` VARCHAR(255) NULL (migration 039 tornou nullable)
  - `imagem_mobile` VARCHAR(255) NULL
  - `ordem` INT UNSIGNED NOT NULL DEFAULT 0
  - `ativo` TINYINT(1) NOT NULL DEFAULT 1
  - Índice: `idx_tenant_tipo_ativo (tenant_id, tipo, ativo)`

#### Rotas
- `GET /admin/home/banners` → `HomeBannersController@index`
- `GET /admin/home/banners/novo` → `HomeBannersController@create`
- `POST /admin/home/banners/novo` → `HomeBannersController@store`
- `GET /admin/home/banners/{id}/editar` → `HomeBannersController@edit`
- `POST /admin/home/banners/{id}` → `HomeBannersController@update`
- `POST /admin/home/banners/{id}/excluir` → `HomeBannersController@destroy`
- `POST /admin/home/banners/reordenar` → `HomeBannersController@reordenar` (NOVO)

### Como o Tipo é Armazenado

- **Banco de dados:** Campo `tipo` ENUM('hero', 'portrait')
- **Valores internos:** `'hero'` = Carrossel principal, `'portrait'` = Banners de apoio
- **UI:** Usa termos claros "Carrossel principal (topo)" e "Banners de apoio (entre seções)"

### Como Estão Sendo Filtrados

- **Listagem:** Filtro via `?tipo=hero` ou `?tipo=portrait` na query string
- **Query:** `WHERE tenant_id = :tenant_id AND tipo = :tipo` quando filtro existe
- **Ordenação:** `ORDER BY tipo ASC, ordem ASC, id ASC`

### Como a Home Busca os Banners

- **Hero:** `SELECT * FROM banners WHERE tenant_id = :tenant_id AND tipo = 'hero' AND ativo = 1 ORDER BY ordem ASC, id ASC`
- **Portrait:** `SELECT * FROM banners WHERE tenant_id = :tenant_id AND tipo = 'portrait' AND ativo = 1 ORDER BY ordem ASC, id ASC`

---

## Resumo das Implementações Realizadas

### ✅ PARTE 2 - Terminologia Ajustada
- Aba "Retrato" → "Banners de apoio (entre seções)"
- Badge nos cards: "Carrossel principal" ou "Banner de apoio"
- Textos de ajuda atualizados
- Botões: "+ Carrossel principal" e "+ Banner de apoio"

### ✅ PARTE 3 - Formulário com Tipo Fixo
- Tipo não pode mais ser alterado no formulário
- Campo tipo é hidden, apenas informação visual exibida
- Formulário específico para cada tipo de banner
- Ao clicar "+ Carrossel principal" → tipo fixo `hero`
- Ao clicar "+ Banner de apoio" → tipo fixo `portrait`

### ✅ PARTE 4 - Biblioteca de Mídia
- Já estava implementada e funcionando
- Filtro por pasta "banners" funcionando
- Seleção visual e botão "Usar imagem selecionada" funcionando
- Upload múltiplo funcionando

### ✅ PARTE 5 - Drag-and-Drop Implementado
- SortableJS integrado via CDN
- Handle de arrastar em cada card (aparece no hover)
- Endpoint `POST /admin/home/banners/reordenar` criado
- Método `reordenar()` implementado no controller
- Atualização via AJAX sem recarregar página
- Funciona apenas nas abas específicas (não em "Todos")

### ✅ PARTE 6 - Bugs Corrigidos
- Fallback desktop/mobile implementado na home
- Redirecionamento após salvar mantém filtro correto
- Tipo passado corretamente para view de edição
- Banners portrait aparecem corretamente após salvar

---

## Guia Rápido para o Usuário Final

### Como Acessar
1. Acesse **Home da Loja** → **Banners da Home**

### Como Criar Banner do Carrossel Principal
1. Clique em **"+ Carrossel principal"**
2. Preencha título, subtítulo, CTA (opcional)
3. Escolha imagem Desktop (opcional) e/ou Mobile (opcional)
4. Se só desktop estiver preenchido, será usada também no mobile
5. Defina ordem e marque como Ativo
6. Clique em **"Criar Banner"**

### Como Criar Banner de Apoio
1. Clique em **"+ Banner de apoio"**
2. Preencha título, subtítulo, CTA (opcional)
3. **Obrigatório:** Escolha imagem Desktop
4. Opcionalmente escolha imagem Mobile
5. Defina ordem e marque como Ativo
6. Clique em **"Criar Banner"**

### Como Usar Biblioteca de Mídia
1. Clique em **"Escolher da biblioteca"** ao lado do campo de imagem
2. Modal abre já filtrado na pasta "Banners"
3. Você pode fazer upload de novas imagens ou escolher existentes
4. Clique em uma imagem para selecioná-la (borda laranja)
5. Clique em **"Usar imagem selecionada"** para confirmar
6. Campo é preenchido automaticamente

### Como Reordenar Banners
1. Acesse a aba **"Carrossel principal (topo)"** ou **"Banners de apoio"**
2. Passe o mouse sobre um card → aparece ícone de arrastar (grip vertical)
3. Clique e arraste o card para a posição desejada
4. Solte → ordem é salva automaticamente via AJAX
5. Não funciona na aba "Todos" (apenas nas abas específicas)

### Regras de Fallback Desktop/Mobile
- **Se só Desktop preenchido:** Usada em desktop e mobile
- **Se ambas preenchidas:** Desktop usa imagem desktop, Mobile usa imagem mobile
- **Se só Mobile preenchido:** Mobile usa imagem mobile, Desktop também usa imagem mobile

---

## Arquivos Modificados

1. `src/Http/Controllers/Admin/HomeBannersController.php`
   - Adicionado método `reordenar()`
   - Ajustado `edit()` para passar `tipoInicial`

2. `themes/default/admin/home/banners-content.php`
   - Terminologia atualizada
   - Adicionado drag-and-drop com SortableJS
   - Adicionado handle de arrastar nos cards
   - Badge atualizado

3. `themes/default/admin/home/banners-form-content.php`
   - Tipo fixo (hidden) - não pode ser alterado
   - Informação visual sobre o tipo
   - Textos de ajuda atualizados

4. `themes/default/storefront/home.php`
   - Fallback desktop/mobile implementado no carrossel hero
   - Fallback desktop/mobile implementado nos banners portrait

5. `public/index.php`
   - Adicionada rota `POST /admin/home/banners/reordenar`

6. `docs/FASE_19_BANNERS_HOME_UX.md`
   - Documentação completa criada

---

**Status:** ✅ Implementação Concluída  
**Última atualização:** 2025-12-09
