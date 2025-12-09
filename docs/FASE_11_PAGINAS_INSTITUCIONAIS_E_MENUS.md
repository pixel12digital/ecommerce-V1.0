# Fase 11: Páginas Institucionais + Menus (Header/Footer) + Contato

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Diagnóstico e Mapeamento](#fase-1---diagnóstico-e-mapeamento)
- [Estrutura Atual do Sistema](#estrutura-atual-do-sistema)
- [Rotas Existentes](#rotas-existentes)
- [Configurações de Tema](#configurações-de-tema)
- [Dados da Loja](#dados-da-loja)
- [Categorias de Destaque](#categorias-de-destaque)
- [Próximas Fases](#próximas-fases)

---

## Visão Geral

Esta fase implementa:
- ✅ Páginas institucionais padrão de e-commerce (Sobre, Contato, Frete e Prazos, Trocas/Devoluções, Formas de Pagamento, FAQ, Política de Privacidade, Termos de Uso, Política de Cookies, Seja Parceiro/Atacado)
- ✅ Gerenciamento centralizado de menus (header e footer)
- ✅ Página de Contato que utiliza dados já cadastrados da loja
- ✅ Sistema de edição de conteúdo das páginas institucionais via admin

**Status:** Fases 1-7 - ✅ Concluídas

---

## Fase 1 - Diagnóstico e Mapeamento

### Arquivos Analisados

#### Documentação
- ✅ `docs/FASE_1_TEMA_LAYOUT_HOME.md` - Sistema de tema e configurações
- ✅ `docs/FASE_2_HOME_DINAMICA.md` - Bolotas de categorias
- ✅ `docs/FASE_3_LOJA_LISTAGEM_PDP.md` - Loja e produtos
- ✅ `docs/FASE_6_AREA_DO_CLIENTE.md` - Área do cliente
- ✅ `docs/STATUS_PROJETO_COMPLETO.md` - Status geral do projeto

#### Controllers
- ✅ `src/Http/Controllers/Admin/ThemeController.php` - Gerenciamento de tema
- ✅ `src/Http/Controllers/Storefront/HomeController.php` - Home pública
- ✅ `src/Http/Controllers/Admin/HomeCategoriesController.php` - Bolotas de categorias

#### Services
- ✅ `src/Services/ThemeConfig.php` - Service de configurações de tema

#### Views
- ✅ `themes/default/storefront/home.php` - Home com header e footer
- ✅ `themes/default/admin/theme/edit-content.php` - Formulário de edição de tema

---

## Estrutura Atual do Sistema

### Menu do Header

**Localização:** `src/Services/ThemeConfig.php` + `src/Http/Controllers/Admin/ThemeController.php`

**Chave em tenant_settings:** `theme_menu_main`

**Formato:** JSON array de objetos
```json
[
  {
    "label": "Home",
    "url": "/",
    "enabled": true
  },
  {
    "label": "Sobre",
    "url": "/sobre",
    "enabled": true
  },
  {
    "label": "Loja",
    "url": "/produtos",
    "enabled": true
  },
  {
    "label": "Minha conta",
    "url": "/minha-conta",
    "enabled": true
  },
  {
    "label": "Carrinho",
    "url": "/carrinho",
    "enabled": true
  },
  {
    "label": "Frete/Prazos",
    "url": "/frete-prazos",
    "enabled": false
  }
]
```

**Como é salvo:**
- Em `ThemeController@update()`: lê `$_POST['menu_label']`, `$_POST['menu_url']`, `$_POST['menu_enabled']`
- Converte para array PHP e salva via `ThemeConfig::set('theme_menu_main', $menuItems)`
- `ThemeConfig::set()` converte array para JSON automaticamente

**Como é usado:**
- `ThemeConfig::getMainMenu()` retorna apenas itens com `enabled: true`
- Usado em `HomeController@index()` e passado para views como `$theme['menu_main']`
- Renderizado em `themes/default/storefront/home.php` (linhas 806-811 para desktop, 857-861 para mobile)

**Observações:**
- ✅ Desktop e mobile usam a mesma fonte de dados (`$theme['menu_main']`)
- ✅ Menu mobile usa JavaScript (`toggleMobileMenu()`) para mostrar/ocultar
- ✅ Estrutura já permite ativar/desativar itens via admin

---

## Rotas Existentes

### Loja Pública

| Rota | Controller | Método | Status |
|------|------------|--------|--------|
| `/` | `HomeController` | `index()` | ✅ Existe |
| `/produtos` | `ProductController` | `index()` | ✅ Existe |
| `/produto/{slug}` | `ProductController` | `show()` | ✅ Existe |
| `/categoria/{slug}` | `ProductController` | `category()` | ✅ Existe |

### Área do Cliente

| Rota | Controller | Método | Status |
|------|------------|--------|--------|
| `/minha-conta` | `CustomerController` | `dashboard()` | ✅ Existe |
| `/minha-conta/login` | `CustomerAuthController` | `showLoginForm()` | ✅ Existe |
| `/minha-conta/pedidos` | `CustomerController` | `orders()` | ✅ Existe |
| `/minha-conta/perfil` | `CustomerController` | `profile()` | ✅ Existe |
| `/minha-conta/enderecos` | `CustomerController` | `addresses()` | ✅ Existe |

### Carrinho e Checkout

| Rota | Controller | Método | Status |
|------|------------|--------|--------|
| `/carrinho` | `CartController` | `index()` | ✅ Existe |
| `/checkout` | `CheckoutController` | `index()` | ✅ Existe |

### Páginas Institucionais

| Rota | Status | Observação |
|------|-------|------------|
| `/sobre` | ❌ Não existe | Menu já referencia, mas rota não implementada |
| `/contato` | ❌ Não existe | Footer referencia, mas rota não implementada |
| `/frete-prazos` | ❌ Não existe | Menu referencia (desabilitado), mas rota não implementada |
| `/trocas-e-devolucoes` | ❌ Não existe | Footer referencia `/trocas`, mas rota não implementada |
| `/formas-de-pagamento` | ❌ Não existe | Não referenciada ainda |
| `/faq` | ❌ Não existe | Footer referencia `/duvidas`, mas rota não implementada |
| `/politica-de-privacidade` | ❌ Não existe | Footer referencia, mas rota não implementada |
| `/termos-de-uso` | ❌ Não existe | Não referenciada ainda |
| `/politica-de-cookies` | ❌ Não existe | Não referenciada ainda |
| `/seja-parceiro` | ❌ Não existe | Não referenciada ainda |

**Observação:** O footer atual (`themes/default/storefront/home.php` linhas 1045-1112) já referencia algumas dessas rotas, mas elas não estão implementadas. Isso será corrigido nesta fase.

---

## Configurações de Tema

### Estrutura Atual

Todas as configurações são salvas em `tenant_settings` usando a classe `ThemeConfig`.

**Chaves existentes:**

#### Cores (8 configurações)
- `theme_color_primary`
- `theme_color_secondary`
- `theme_color_topbar_bg`
- `theme_color_topbar_text`
- `theme_color_header_bg`
- `theme_color_header_text`
- `theme_color_footer_bg`
- `theme_color_footer_text`

#### Textos (3 configurações)
- `topbar_text`
- `newsletter_title`
- `newsletter_subtitle`

#### Contato e Endereço (4 configurações)
- `footer_phone`
- `footer_whatsapp`
- `footer_email`
- `footer_address`

#### Redes Sociais (3 configurações)
- `footer_social_instagram`
- `footer_social_facebook`
- `footer_social_youtube`

#### Menu Principal (1 configuração JSON)
- `theme_menu_main` - Array JSON de objetos `{label, url, enabled}`

#### Outras Configurações
- `catalogo_ocultar_estoque_zero` - '0' ou '1'
- `logo_url` - Caminho relativo do logo

### Como Funciona

**Leitura:**
```php
ThemeConfig::get($key, $default)
ThemeConfig::getColor($key, $default) // Garante formato hex
ThemeConfig::getJson($key, $default) // Decodifica JSON
ThemeConfig::getMainMenu() // Menu filtrado (apenas enabled)
```

**Escrita:**
```php
ThemeConfig::set($key, $value) // Converte array para JSON automaticamente
ThemeConfig::clearCache() // Limpa cache após atualizações
```

**Cache:**
- Cache em memória (`static array $cache`)
- Chave: `"{$tenantId}:{$key}"`
- Limpo automaticamente após `set()`

---

## Dados da Loja

### Fonte de Dados

**Tabela:** `tenants` (dados básicos) + `tenant_settings` (configurações)

**Dados básicos do tenant:**
- `tenants.name` - Nome da loja
- `tenants.slug` - Slug da loja
- `tenants.status` - Status (active/inactive)
- `tenants.plan` - Plano

**Dados de contato (tenant_settings):**
- `footer_phone` - Telefone
- `footer_whatsapp` - WhatsApp
- `footer_email` - E-mail
- `footer_address` - Endereço completo

**Como são obtidos:**
```php
// Dados básicos
$tenant = TenantContext::tenant();
$lojaNome = $tenant->name;

// Dados de contato
$phone = ThemeConfig::get('footer_phone', '');
$whatsapp = ThemeConfig::get('footer_whatsapp', '');
$email = ThemeConfig::get('footer_email', '');
$address = ThemeConfig::get('footer_address', '');
```

**Onde são usados:**
- Footer da home (`themes/default/storefront/home.php` linhas 1082-1107)
- Checkout (provavelmente)
- E-mails (provavelmente)

**Observação:** Não há CNPJ configurado ainda. Se necessário, pode ser adicionado como nova chave em `tenant_settings`.

---

## Categorias de Destaque

### Tabela: `home_category_pills`

**Estrutura:**
- `id` - Chave primária
- `tenant_id` - ID do tenant
- `categoria_id` - FK para `categorias.id`
- `label` - Label customizado (opcional, usa nome da categoria se vazio)
- `icone_path` - Caminho do ícone (opcional)
- `ordem` - Ordem de exibição
- `ativo` - 1 ou 0
- `created_at`, `updated_at`

**Como são obtidas:**
```php
// Em HomeController@index()
$stmt = $db->prepare("
    SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
    FROM home_category_pills hcp
    LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
    WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
    ORDER BY hcp.ordem ASC, hcp.id ASC
");
```

**Onde são usadas:**
- Bolotas na home (`themes/default/storefront/home.php` linhas 867-890)
- Footer - coluna "Categorias" (linhas 1072-1080) - **LIMIT 4**

**Admin:**
- Controller: `src/Http/Controllers/Admin/HomeCategoriesController.php`
- Rotas: `/admin/home/categorias-pills`
- Views: `themes/default/admin/home/categories-pills-content.php`

**Para reutilizar no footer:**
- Criar função helper ou método em `HomeController` que retorne categorias destacadas
- Usar mesma query, mas com `LIMIT` configurável (vindo do tema)

---

## Footer Atual

### Estrutura HTML

**Localização:** `themes/default/storefront/home.php` (linhas 1045-1112)

**Colunas atuais (hardcoded):**
1. **Ajuda** - Links: Frete e Prazos, Trocas e Devoluções, Dúvidas Frequentes
2. **Minha Conta** - Links: Entrar, Meus Pedidos, Favoritos
3. **Institucional** - Links: Sobre Nós, Contato, Política de Privacidade
4. **Categorias** - Primeiras 4 categorias de destaque (bolotas)
5. **Contato** - Dados da loja (telefone, WhatsApp, e-mail, endereço, redes sociais)

**Problemas identificados:**
- ❌ Links hardcoded (não configuráveis via admin)
- ❌ URLs podem não existir (`/trocas`, `/duvidas`, `/contato`, etc.)
- ❌ Não há controle de ativar/desativar seções ou links
- ❌ Categorias limitadas a 4 (hardcoded)

**Solução proposta:**
- Criar estrutura `footer.sections` em `tenant_settings`
- Permitir edição via `/admin/tema`
- Reutilizar categorias de destaque com limite configurável

---

## Implementação Concluída

### Fase 2 - Modelo de Dados para Páginas Institucionais ✅
- ✅ Criada estrutura `theme_pages` em `tenant_settings`
- ✅ Estendido `ThemeConfig` com métodos `getPages()`, `getPage()`, `setPages()`
- ✅ Definidos defaults para todas as 10 páginas institucionais

### Fase 3 - Rotas + Controller ✅
- ✅ Criado `StaticPageController` com 10 métodos públicos
- ✅ Implementados métodos para todas as páginas
- ✅ Adicionadas 10 rotas GET em `public/index.php`

### Fase 4 - Views das Páginas ✅
- ✅ Criada view base (`base.php`) reutilizável com header/footer
- ✅ Criadas 9 views específicas (todas usam base.php)
- ✅ Implementada página de Contato com dados da loja e layout especial

### Fase 5 - Configuração de Menus ✅
- ✅ Adicionado "Contato" ao menu padrão do header
- ✅ Criada estrutura `theme_footer` em `tenant_settings`
- ✅ Implementada edição completa de footer via `/admin/tema`
- ✅ Atualizado footer da home para usar configuração dinâmica

### Fase 6 - Admin: Edição de Conteúdo ✅
- ✅ Adicionada seção "Conteúdo das Páginas Institucionais" em `/admin/tema`
- ✅ Implementado salvamento de conteúdo via `ThemeController@update()`
- ✅ Adicionada seção "Footer / Páginas Institucionais" para configurar footer

### Fase 7 - Testes e Checklist ✅
- ✅ Rotas criadas e funcionais
- ✅ Página de Contato integrada com dados da loja
- ✅ Menus header/footer configuráveis via admin
- ✅ Sistema multi-tenant preservado
- ✅ Documentação atualizada

---

## Resumo Técnico

### Arquivos que serão criados
- `src/Http/Controllers/Storefront/StaticPageController.php`
- `themes/default/storefront/pages/base.php`
- `themes/default/storefront/pages/sobre.php`
- `themes/default/storefront/pages/contato.php`
- `themes/default/storefront/pages/trocas-devolucoes.php`
- `themes/default/storefront/pages/frete-prazos.php`
- `themes/default/storefront/pages/formas-pagamento.php`
- `themes/default/storefront/pages/faq.php`
- `themes/default/storefront/pages/politica-privacidade.php`
- `themes/default/storefront/pages/termos-uso.php`
- `themes/default/storefront/pages/politica-cookies.php`
- `themes/default/storefront/pages/seja-parceiro.php`

### Arquivos que serão modificados
- `src/Services/ThemeConfig.php` - Adicionar métodos para páginas
- `src/Http/Controllers/Admin/ThemeController.php` - Adicionar salvamento de páginas e footer
- `themes/default/admin/theme/edit-content.php` - Adicionar seções de edição
- `themes/default/storefront/home.php` - Atualizar footer para usar configuração
- `public/index.php` - Adicionar rotas das páginas institucionais

### Estrutura de Dados Proposta

**Nova chave em tenant_settings:** `theme_pages`
```json
{
  "sobre": {
    "title": "Sobre o Ponto do Golfe",
    "content": "<p>...</p>"
  },
  "contato": {
    "title": "Fale conosco",
    "intro": "<p>...</p>"
  },
  ...
}
```

**Nova chave em tenant_settings:** `theme_footer`
```json
{
  "sections": {
    "ajuda": {
      "title": "Ajuda",
      "enabled": true,
      "links": {
        "contato": {"label": "Fale conosco", "enabled": true},
        "trocas_devolucoes": {"label": "Trocas e devoluções", "enabled": true},
        ...
      }
    },
    "minha_conta": {...},
    "institucional": {...},
    "categorias": {
      "title": "Categorias",
      "enabled": true,
      "limit": 6
    }
  }
}
```

---

## Arquivos Criados

### Controllers
- `src/Http/Controllers/Storefront/StaticPageController.php`

### Views
- `themes/default/storefront/pages/base.php`
- `themes/default/storefront/pages/sobre.php`
- `themes/default/storefront/pages/contato.php`
- `themes/default/storefront/pages/trocas-devolucoes.php`
- `themes/default/storefront/pages/frete-prazos.php`
- `themes/default/storefront/pages/formas-pagamento.php`
- `themes/default/storefront/pages/faq.php`
- `themes/default/storefront/pages/politica-privacidade.php`
- `themes/default/storefront/pages/termos-uso.php`
- `themes/default/storefront/pages/politica-cookies.php`
- `themes/default/storefront/pages/seja-parceiro.php`

## Arquivos Modificados

### Services
- `src/Services/ThemeConfig.php` - Adicionados métodos `getPages()`, `getPage()`, `setPages()`, `getFooterConfig()`, `setFooterConfig()`

### Controllers
- `src/Http/Controllers/Admin/ThemeController.php` - Adicionado suporte para salvar pages e footer
- `public/index.php` - Adicionadas 10 rotas para páginas institucionais

### Views
- `themes/default/admin/theme/edit-content.php` - Adicionadas seções de edição de páginas e footer
- `themes/default/storefront/home.php` - Footer atualizado para usar configuração dinâmica

## Estruturas de Dados Finais

### theme_pages (tenant_settings)
```json
{
  "sobre": {
    "title": "Sobre o Ponto do Golfe",
    "content": "<p>...</p>"
  },
  "contato": {
    "title": "Fale conosco",
    "intro": "<p>...</p>"
  },
  ...
}
```

### theme_footer (tenant_settings)
```json
{
  "sections": {
    "ajuda": {
      "title": "Ajuda",
      "enabled": true,
      "links": {
        "contato": {
          "label": "Fale conosco",
          "enabled": true,
          "route": "/contato"
        },
        ...
      }
    },
    "minha_conta": {...},
    "institucional": {...},
    "categorias": {
      "title": "Categorias",
      "enabled": true,
      "limit": 6
    }
  }
}
```

## Guia para o Lojista

### Como Editar Conteúdo das Páginas Institucionais

1. Acesse `/admin/tema` no painel administrativo
2. Role até a seção **"Conteúdo das Páginas Institucionais"**
3. Para cada página:
   - Edite o **Título** da página
   - Edite o **Conteúdo** (HTML permitido)
   - Para Contato, edite o **Texto Introdutório**
4. Clique em **"Salvar Tema"**

### Como Configurar o Footer

1. Acesse `/admin/tema` no painel administrativo
2. Role até a seção **"Footer / Páginas Institucionais"**
3. Para cada seção (Ajuda, Minha Conta, Institucional, Categorias):
   - Edite o **Título** da seção
   - Marque/desmarque **"Exibir seção no footer"**
   - Para seções com links, edite os **Labels** e marque/desmarque **"Ativo"**
   - Para Categorias, defina a **Quantidade máxima** de categorias a exibir
4. Clique em **"Salvar Tema"**

### Como Ativar Item "Contato" no Menu do Header

1. Acesse `/admin/tema` no painel administrativo
2. Role até a seção **"Menu Principal"**
3. Localize o item "Contato" (ou adicione se não existir)
4. Marque o checkbox **"Ativo"** na linha correspondente
5. Clique em **"Salvar Tema"**

---

---

## Fase 11B - Formulário de Contato

### Status: ✅ Concluída

### Implementação

**Objetivo:** Criar formulário de contato funcional na página `/contato` com envio de mensagens e e-mail para o lojista.

#### Fase 1 - Modelo de Dados ✅
- ✅ Criada migration `038_create_contact_messages_table.php`
- ✅ Criado repositório `ContactMessageRepository`
- ✅ Tabela `contact_messages` com campos: id, tenant_id, nome, email, telefone, tipo_assunto, numero_pedido, mensagem, status, origin_url, created_at, updated_at

#### Fase 2 - Rotas e Controller ✅
- ✅ Adicionado método `enviarContato()` em `StaticPageController`
- ✅ Adicionada rota POST `/contato` em `public/index.php`
- ✅ Validação completa de campos obrigatórios
- ✅ Validação condicional de número do pedido (obrigatório para pedido_andamento, trocas_devolucoes, pagamento)
- ✅ Salvamento em `contact_messages` via `ContactMessageRepository`
- ✅ Envio de e-mail via `EmailService`

#### Fase 3 - Formulário na View ✅
- ✅ Formulário completo adicionado em `themes/default/storefront/pages/contato.php`
- ✅ Campos: nome, email, telefone, tipo_assunto, numero_pedido (condicional), mensagem
- ✅ Mensagens de erro e sucesso via flash messages
- ✅ Pré-preenchimento para clientes logados
- ✅ JavaScript para mostrar/ocultar campo de número do pedido
- ✅ Preservação de valores após erro de validação

#### Fase 4 - Admin (Opcional)
- ⏳ Pendente - Pode ser implementado futuramente

#### Fase 5 - Testes e Documentação ✅
- ✅ Documentação atualizada

### Estrutura da Tabela contact_messages

```sql
CREATE TABLE contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50) NULL,
    tipo_assunto ENUM(
        'duvidas_produtos',
        'pedido_andamento',
        'trocas_devolucoes',
        'pagamento',
        'problema_site',
        'outros'
    ) NOT NULL,
    numero_pedido VARCHAR(50) NULL,
    mensagem TEXT NOT NULL,
    status ENUM('novo', 'lido') NOT NULL DEFAULT 'novo',
    origin_url VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_contact_messages_tenant (tenant_id),
    INDEX idx_contact_messages_status (tenant_id, status),
    INDEX idx_contact_messages_created (created_at)
)
```

### Fluxo do Formulário de Contato

1. **Usuário acessa `/contato`**
   - Visualiza dados da loja e formulário

2. **Preenche e envia formulário**
   - Validação client-side (HTML5) e server-side (PHP)

3. **Validação no backend**
   - Campos obrigatórios verificados
   - Número do pedido obrigatório para tipos específicos
   - Mensagem mínima de 10 caracteres

4. **Se houver erros**
   - Valores preservados em `$_SESSION['contact_old_data']`
   - Erros em `$_SESSION['contact_errors']`
   - Redirecionamento para `/contato` com mensagens

5. **Se válido**
   - Mensagem salva em `contact_messages` (status = 'novo')
   - E-mail enviado para lojista via `EmailService`
   - Mensagem de sucesso exibida

### Configuração de E-mail

**E-mail de destino:**
- Prioridade 1: `contact_email` (configurável em `/admin/tema`)
- Prioridade 2: `footer_email` (fallback)

**Como alterar:**
1. Acesse `/admin/tema`
2. Na seção "Contato e Endereço"
3. Preencha "E-mail para contato" (opcional)
4. Se não preenchido, será usado o "E-mail" padrão

### Arquivos Criados/Modificados

**Criados:**
- `database/migrations/038_create_contact_messages_table.php`
- `src/Repositories/ContactMessageRepository.php`
- `src/Services/EmailService.php`

**Modificados:**
- `src/Http/Controllers/Storefront/StaticPageController.php` - Métodos `contato()` e `enviarContato()`
- `themes/default/storefront/pages/contato.php` - Formulário completo
- `public/index.php` - Rota POST `/contato`
- `src/Http/Controllers/Admin/ThemeController.php` - Suporte a `contact_email`
- `themes/default/admin/theme/edit-content.php` - Campo `contact_email`

### Como Consultar Mensagens no Banco

Até existir uma UI no admin, as mensagens podem ser consultadas diretamente no banco:

```sql
-- Ver todas as mensagens de um tenant
SELECT * FROM contact_messages 
WHERE tenant_id = 1 
ORDER BY created_at DESC;

-- Ver apenas mensagens não lidas
SELECT * FROM contact_messages 
WHERE tenant_id = 1 AND status = 'novo'
ORDER BY created_at DESC;

-- Marcar mensagem como lida
UPDATE contact_messages 
SET status = 'lido', updated_at = NOW() 
WHERE id = 1 AND tenant_id = 1;
```

---

---

## Fase 11.3 - FAQ Dinâmico (Perguntas e Respostas com Accordion)

### Status: ✅ Concluída

### Objetivo

Transformar a página FAQ de um conteúdo HTML estático para um sistema dinâmico baseado em pares Pergunta/Resposta, com interface de accordion no frontend.

### Implementação

#### 1. Modelo de Dados

- **Estrutura do FAQ em `theme_pages['faq']`:**
  ```php
  'faq' => [
      'title' => 'Perguntas frequentes (FAQ)',
      'intro' => '<p>Texto introdutório opcional</p>',
      'items' => [
          [
              'question' => 'Pergunta 1',
              'answer' => '<p>Resposta formatada em HTML</p>',
          ],
          // ...
      ],
  ]
  ```

- **Arquivo:** `src/Services/ThemeConfig.php`
  - Atualizado `getDefaultPages()` para usar estrutura com `intro` + `items[]`
  - Ajustado `getPage()` para garantir que FAQ sempre tenha `items` como array

#### 2. Backend - Salvamento

- **Arquivo:** `src/Http/Controllers/Admin/ThemeController.php`
- **Processamento:**
  - Tratamento especial para `pages['faq']` no método `update()`
  - Normalização de items: remove itens vazios, garante índices sequenciais
  - Sanitização de HTML com whitelist de tags permitidas
  - Merge com páginas existentes para não perder dados de outras páginas

#### 3. Interface Admin - Repeater

- **Arquivo:** `themes/default/admin/theme/edit-content.php`
- **Funcionalidades:**
  - Campo de título da página
  - Campo de texto introdutório (com editor visual CKEditor 5)
  - Repeater de perguntas/respostas:
    - Botão "Adicionar pergunta" para criar novos itens
    - Botão "Remover pergunta" em cada item
    - Campo "Pergunta" (texto simples)
    - Campo "Resposta" (editor visual CKEditor 5)
  - JavaScript para gerenciar adição/remoção de itens
  - Inicialização automática do CKEditor 5 em novos campos adicionados

#### 4. Frontend - Accordion

- **Arquivo:** `themes/default/storefront/pages/faq.php`
- **Controller:** `src/Http/Controllers/Storefront/StaticPageController.php`
  - Método `faq()` atualizado para usar view específica
  - Passa `$page` e `$faqItems` para a view

- **Funcionalidades do Accordion:**
  - Lista de perguntas com botões expansíveis
  - Por padrão, todas as respostas estão recolhidas
  - Ao clicar em uma pergunta, abre a resposta correspondente
  - Ao abrir uma pergunta, fecha automaticamente as outras (comportamento de accordion)
  - Ícone "+" que rotaciona quando a pergunta está aberta
  - Transições suaves de abertura/fechamento
  - Responsivo para mobile

- **CSS:**
  - Estilos para `.pg-faq-item`, `.pg-faq-question`, `.pg-faq-answer`
  - Transições CSS para animação suave
  - Layout responsivo

- **JavaScript:**
  - Event listener no accordion
  - Gerencia estado `aria-expanded` para acessibilidade
  - Fecha todas as outras respostas ao abrir uma nova

### Estrutura de Dados

```php
// Exemplo de dados salvos em tenant_settings['theme_pages']
{
    "faq": {
        "title": "Perguntas frequentes (FAQ)",
        "intro": "<p>Veja abaixo as respostas para as dúvidas mais comuns.</p>",
        "items": [
            {
                "question": "Como faço meu pedido?",
                "answer": "<p>Navegue pelo site, adicione os produtos desejados ao carrinho e finalize a compra.</p>"
            },
            {
                "question": "Como acompanho meu pedido?",
                "answer": "<p>Após a confirmação do pagamento, você receberá um e-mail com o código de rastreamento.</p>"
            }
        ]
    }
}
```

### Passo a Passo para o Lojista

1. **Acessar `/admin/tema`**
   - Navegar até a seção "Conteúdo das Páginas Institucionais"
   - Localizar a seção "FAQ"

2. **Configurar FAQ:**
   - Preencher "Título da Página"
   - (Opcional) Adicionar texto introdutório usando o editor visual
   - Clicar em "Adicionar pergunta" para criar novos itens
   - Preencher "Pergunta" (texto simples)
   - Preencher "Resposta" usando o editor visual (pode incluir formatação, listas, links)
   - Repetir para cada pergunta/resposta
   - Usar "Remover pergunta" para excluir itens indesejados

3. **Salvar:**
   - Clicar em "Salvar Tema"
   - As perguntas serão salvas na ordem em que foram adicionadas

4. **Visualizar no site:**
   - Acessar `/faq` no site público
   - Ver lista de perguntas em formato accordion
   - Clicar em qualquer pergunta para expandir a resposta

### Benefícios

- ✅ Interface intuitiva para gerenciar perguntas/respostas
- ✅ Editor visual para formatação de respostas
- ✅ Accordion interativo no frontend
- ✅ Responsivo e acessível
- ✅ Compatível com sistema multi-tenant existente
- ✅ Não quebra páginas institucionais existentes

### Checklist de Testes

- [x] Admin: Adicionar perguntas via repeater
- [x] Admin: Remover perguntas
- [x] Admin: Editor visual funciona em campos de resposta
- [x] Admin: Salvar e recarregar mantém dados
- [x] Frontend: Lista de perguntas aparece corretamente
- [x] Frontend: Accordion abre/fecha corretamente
- [x] Frontend: Apenas uma resposta aberta por vez
- [x] Frontend: Formatação HTML das respostas renderiza corretamente
- [x] Mobile: Layout responsivo funciona
- [x] Multi-tenant: Isolamento de dados entre lojas

---

---

## Fase 11.4 - Ajustes Visuais do Footer + Crédito Pixel12Digital

### Status: ✅ Concluída

### Objetivo

Melhorar o visual do footer com melhor hierarquia, espaçamentos, responsividade e adicionar crédito "Desenvolvido por Pixel12Digital".

### Implementação

#### 1. Estrutura HTML

- **Classes semânticas adotadas:**
  - `.pg-footer` - Container principal do footer
  - `.pg-footer-main` - Bloco principal com colunas
  - `.pg-container` - Container padrão (max-width: 1200px)
  - `.pg-footer-grid` - Grid responsivo para colunas
  - `.pg-footer-col` - Cada coluna do footer
  - `.pg-footer-title` - Títulos das colunas (h4)
  - `.pg-footer-links` - Lista de links
  - `.pg-footer-contact` - Coluna de contato
  - `.pg-footer-contact-item` - Item de contato (telefone, email, etc.)
  - `.pg-footer-social` - Ícones de redes sociais
  - `.pg-footer-bottom` - Faixa inferior (copyright + crédito)
  - `.pg-footer-bottom-inner` - Container interno da faixa inferior
  - `.pg-footer-copy` - Texto de copyright
  - `.pg-footer-dev` - Crédito "Desenvolvido por Pixel12Digital"

#### 2. Estilos CSS

- **Cores:**
  - Fundo principal: `#111111`
  - Fundo inferior: `#0c0c0c`
  - Títulos: `#ffffff`
  - Links: `#e0e0e0` com hover `#F7931E`
  - Borda superior da faixa inferior: `#222222`

- **Espaçamentos:**
  - Padding principal: `40px 0 32px 0` (desktop)
  - Gap entre colunas: `32px` (desktop), `24px` (tablet), `20px` (mobile)
  - Espaçamento entre links: `6px`

- **Responsividade:**
  - Desktop: Grid adaptativo (`repeat(auto-fit, minmax(200px, 1fr))`) - 4-5 colunas conforme espaço
  - Tablet (≤992px): Grid adaptativo (`repeat(auto-fit, minmax(180px, 1fr))`) - 3-4 colunas
  - Tablet pequeno (≤768px): 2 colunas fixas (`repeat(2, minmax(0, 1fr))`)
  - Mobile (≤576px): 1 coluna (`1fr`)

- **Efeitos:**
  - Links com hover: cor laranja (`#F7931E`) + translateX(2px)
  - Ícones sociais com hover: background laranja + translateY(-2px)
  - Transições suaves (0.2s ease)

#### 3. Crédito Pixel12Digital

- **Localização:** Faixa inferior do footer (`.pg-footer-bottom`)
- **Texto:** "Desenvolvido por Pixel12Digital"
- **Link:** `https://pixel12digital.com.br`
- **Comportamento:** Abre em nova aba (`target="_blank"`) com `rel="noopener"`
- **Estilo:** Cor laranja (`#F7931E`), negrito, underline no hover

#### 4. Integração com ThemeConfig

- ✅ Mantida integração completa com `ThemeConfig::getFooterConfig()`
- ✅ Links dinâmicos de Ajuda, Minha Conta, Institucional
- ✅ Categorias dinâmicas do footer
- ✅ Dados de contato (telefone, whatsapp, email, endereço)
- ✅ Redes sociais (Instagram, Facebook, YouTube)
- ✅ Nome da loja dinâmico no copyright

#### 5. Arquivos Atualizados

- `themes/default/storefront/home.php` - Footer da home
- `themes/default/storefront/pages/base.php` - Footer das páginas institucionais
- `themes/default/storefront/pages/faq.php` - Footer da página FAQ
- `themes/default/storefront/pages/contato.php` - Footer da página de contato

### Benefícios

- ✅ Visual mais profissional e organizado
- ✅ Melhor hierarquia visual (títulos destacados, links claros)
- ✅ Responsividade aprimorada (grid adaptativo com auto-fit)
- ✅ Crédito Pixel12Digital visível e acessível
- ✅ Compatibilidade mantida com sistema existente
- ✅ Hover effects melhoram a experiência do usuário
- ✅ Layout fluido que evita colunas isoladas

### Checklist de Testes

- [x] Desktop: 4 colunas alinhadas e espaçadas
- [x] Tablet: 2 colunas funcionando corretamente
- [x] Mobile: 1 coluna com bom espaçamento
- [x] Links com hover laranja funcionando
- [x] Ícones sociais com hover funcionando
- [x] Crédito Pixel12Digital visível e clicável
- [x] Copyright com nome da loja correto
- [x] Multi-tenant: footer respeita dados de cada loja

#### 6. Ajuste de Grid Responsivo (Pós-Fase 11.4)

**Problema identificado:** Em resoluções intermediárias, 5 colunas resultavam em layout 4+1 (4 colunas na primeira linha, 1 coluna isolada na segunda).

**Solução implementada:**
- **CSS Grid com `auto-fit`:** `grid-template-columns: repeat(auto-fit, minmax(200px, 1fr))`
- **Comportamento:**
  - Desktop largo: 4-5 colunas por linha (distribuição automática)
  - Tablet (992px): `minmax(180px, 1fr)` para colunas mais estreitas
  - Tablet pequeno (768px): 2 colunas fixas por linha
  - Mobile (576px): 1 coluna por linha
- **Gap ajustado:** `gap: 32px 40px` (vertical / horizontal) para melhor espaçamento

**Arquivos atualizados:**
- `themes/default/storefront/home.php`
- `themes/default/storefront/pages/base.php`
- `themes/default/storefront/pages/faq.php`
- `themes/default/storefront/pages/contato.php`

**Resultado:** Layout fluido que se reorganiza automaticamente, evitando colunas isoladas e mantendo distribuição harmônica em todas as resoluções.

#### 7. Ajuste Extra de Grid (Evitar Layout 4+1) – 2025-12-08

**Problema identificado:** Em resoluções intermediárias (~1100px), o grid com `auto-fit` ainda resultava em layout 4+1 (4 colunas na primeira linha, 1 coluna isolada na segunda).

**Solução implementada:**
- **Breakpoints fixos explícitos:**
  - ≥ 1200px: 5 colunas (`repeat(5, minmax(0, 1fr))`) - todas numa linha
  - 992px–1199px: 3 colunas (`repeat(3, minmax(0, 1fr))`) - layout 3+2, nunca 4+1
  - 768px–991px: 2 colunas (`repeat(2, minmax(0, 1fr))`) - 2 colunas por linha
  - < 768px: 1 coluna (`1fr`) - 1 coluna por linha
- **Remoção de `auto-fit`:** Substituído por breakpoints fixos para controle preciso
- **Gaps mantidos:** `32px 40px` (desktop), `24px 32px` (tablet), `20px` (mobile)

**Arquivos atualizados:**
- `themes/default/storefront/home.php`
- `themes/default/storefront/pages/base.php`
- `themes/default/storefront/pages/faq.php`
- `themes/default/storefront/pages/contato.php`

**Resultado:** Controle preciso do número de colunas por breakpoint, garantindo que nunca haja coluna isolada. Layout 3+2 em resoluções intermediárias em vez de 4+1.

---

---

## Fase 11.6 - Padronização de Cores por ThemeConfig

### Status: ✅ Concluída (parcial - páginas principais)

### Objetivo

Padronizar todas as páginas do storefront para usar as cores do tema via `ThemeConfig`, eliminando cores hard-coded e garantindo que mudanças em `/admin/tema` sejam refletidas automaticamente em todo o site.

### Implementação

#### 1. Helper para Variáveis CSS

**Arquivo:** `src/Support/ThemeCssHelper.php`

**Método:** `generateCssVariables()`

Gera o bloco de variáveis CSS `:root` com todas as cores do tema:

```css
:root {
    --pg-color-primary: [cor primária do tema];
    --pg-color-secondary: [cor secundária do tema];
    --pg-color-topbar-bg: [cor de fundo da topbar];
    --pg-color-topbar-text: [cor do texto da topbar];
    --pg-color-header-bg: [cor de fundo do header];
    --pg-color-header-text: [cor do texto do header];
    --pg-color-footer-bg: [cor de fundo do footer];
    --pg-color-footer-text: [cor do texto do footer];
}
```

#### 2. Método Helper no ThemeConfig

**Arquivo:** `src/Services/ThemeConfig.php`

**Métodos adicionados:**
- `getAllThemeColors()`: Retorna array com todas as cores do tema
- `getFullThemeConfig()`: Retorna array completo (cores + textos + menu + logo + footer)

#### 3. Classe Base para Controllers do Storefront

**Arquivo:** `src/Http/Controllers/Storefront/BaseStorefrontController.php`

Classe abstrata que fornece métodos comuns:
- `getThemeConfig()`: Carrega todas as configurações do tema
- `getCartData()`: Carrega dados do carrinho
- `getStoreData()`: Carrega dados básicos da loja
- `getDefaultViewData()`: Retorna dados padrão para views

**Nota:** Esta classe foi criada mas ainda não está sendo usada por todos os controllers (opcional para migração futura).

#### 4. Substituição de Cores Hard-coded

**Arquivos atualizados:**

- ✅ `themes/default/storefront/home.php`
  - Variáveis CSS globais adicionadas
  - Cores do footer substituídas por variáveis

- ✅ `themes/default/storefront/cart/index.php`
  - Variáveis CSS globais adicionadas
  - Faixa azul do carrinho: `#023A8D` → `var(--pg-color-primary)`
  - Botões primários: `#F7931E` → `var(--pg-color-secondary)`
  - Cores do footer substituídas

- ✅ `themes/default/storefront/checkout/index.php`
  - Variáveis CSS globais adicionadas
  - Header: `#023A8D` → `var(--pg-color-primary)`
  - Botões e elementos de destaque substituídos
  - `CheckoutController` atualizado para passar `$theme`

- ✅ `themes/default/storefront/pages/base.php`
  - Variáveis CSS globais adicionadas
  - Cores do footer substituídas
  - Uso de `getFullThemeConfig()` para simplificar código

- ✅ `themes/default/storefront/pages/faq.php`
  - Variáveis CSS globais adicionadas
  - Cores do footer substituídas
  - Uso de `getFullThemeConfig()`

- ✅ `themes/default/storefront/pages/contato.php`
  - Variáveis CSS globais adicionadas
  - Cores do footer substituídas
  - Uso de `getFullThemeConfig()`

- ✅ `themes/default/storefront/products/index.php`
  - Variáveis CSS globais adicionadas

- ✅ `themes/default/storefront/products/show.php`
  - Variáveis CSS globais adicionadas

- ✅ `themes/default/storefront/customers/login.php`
  - Variáveis CSS globais adicionadas
  - Botões e links: `#2E7D32` → `var(--pg-color-primary)`
  - `CustomerAuthController` atualizado para passar `$theme`

- ⏳ `themes/default/storefront/customers/layout.php` (pendente)
  - Header: `#023A8D` → `var(--pg-color-primary)`
  - Links ativos e elementos de destaque

- ⏳ Outras páginas de cliente (pendente)
  - `dashboard.php`, `orders.php`, `order-show.php`, `addresses.php`, `profile.php`, `register.php`

### Padrão de Substituição

**Cores principais:**
- `#023A8D` (azul) → `var(--pg-color-primary)`
- `#2E7D32` (verde) → `var(--pg-color-primary)`
- `#F7931E` (laranja) → `var(--pg-color-secondary)`
- `#ff8400` (laranja alternativo) → `var(--pg-color-secondary)`

**Cores estruturais (mantidas como hard-coded quando apropriado):**
- `#ffffff` (branco) - mantido
- `#333333` (cinza escuro) - mantido
- `#1a1a1a` (preto) - mantido
- `#f5f5f5` (cinza claro) - mantido

### Recomendações Futuras

1. **NUNCA usar hex fixo** para elementos que representem identidade visual da loja (botões principais, headers, footers, CTAs)
2. **SEMPRE usar variáveis CSS** `var(--pg-color-primary)` e `var(--pg-color-secondary)` para cores de tema
3. **Usar `ThemeCssHelper::generateCssVariables()`** em todos os templates do storefront
4. **Migrar controllers** para usar `BaseStorefrontController` (opcional, mas recomendado)

### Checklist de Páginas

- [x] Home (`home.php`)
- [x] Carrinho (`cart/index.php`)
- [x] Checkout (`checkout/index.php`)
- [x] Páginas institucionais (`pages/base.php`, `faq.php`, `contato.php`)
- [x] Listagem de produtos (`products/index.php`)
- [x] Página de produto (`products/show.php`)
- [x] Login (`customers/login.php`)
- [ ] Layout de cliente (`customers/layout.php`)
- [ ] Dashboard (`customers/dashboard.php`)
- [ ] Pedidos (`customers/orders.php`, `order-show.php`)
- [ ] Endereços (`customers/addresses.php`)
- [ ] Perfil (`customers/profile.php`)
- [ ] Cadastro (`customers/register.php`)

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** Fases 1-7 + Fase 11B + Fase 11.2 + Fase 11.3 + Fase 11.4 + Fase 11.5 + Fase 11.6 (parcial) - ✅ Principais páginas concluídas

---

## Fase 11.2 - Editor Visual (WYSIWYG) para Conteúdo das Páginas Institucionais

### Status: ✅ Concluída

### Objetivo

Transformar os campos HTML em editores visuais (WYSIWYG) para melhorar a experiência do usuário administrador, eliminando a necessidade de digitar HTML manualmente.

### Implementação

#### 1. Integração do CKEditor 5 Classic

- **Localização:** `themes/default/admin/layouts/store.php`
- **CDN:** CKEditor 5 Classic build via CDN (cdn.ckeditor.com) - versão 41.4.2
- **Configuração:**
  - Toolbar simplificada: desfazer/refazer, negrito, itálico, sublinhado, títulos (heading), listas, alinhamento, links
  - Opções de heading: Parágrafo, Título Médio (H2), Subtítulo (H3)
  - Altura mínima: 220px
  - Inicialização automática em todos os `<textarea>` com classe `.pg-richtext`

#### 2. Marcação dos Campos

- **Classe CSS:** `pg-richtext`
- **Campos marcados:**
  - Todos os campos `content` das páginas institucionais (Sobre, Trocas/Devoluções, Frete/Prazos, Formas de Pagamento, FAQ, Política de Privacidade, Termos de Uso, Política de Cookies, Seja Parceiro)
  - Campo `intro` da página de Contato
- **Arquivo:** `themes/default/admin/theme/edit-content.php`

#### 3. Segurança no Backend

- **Sanitização:** Implementada whitelist de tags HTML permitidas usando `strip_tags()`
- **Tags permitidas:** `<p>`, `<h1>` até `<h6>`, `<strong>`, `<b>`, `<em>`, `<i>`, `<u>`, `<ul>`, `<ol>`, `<li>`, `<a>`, `<br>`, `<hr>`, `<div>`, `<span>`
- **Arquivo:** `src/Http/Controllers/Admin/ThemeController.php` (método `update()`)

#### 4. Compatibilidade

- O sistema continua salvando HTML no banco de dados (`tenant_settings`)
- Compatível com o sistema existente de renderização das páginas institucionais
- HTML gerado pelo editor é limpo e sem estilos inline exagerados

### Benefícios

- ✅ Usuário não precisa conhecer HTML
- ✅ Formatação visual intuitiva via toolbar
- ✅ Código isolado e reutilizável (qualquer `<textarea>` com classe `pg-richtext` recebe o editor)
- ✅ Segurança mantida com sanitização de tags
- ✅ Sem necessidade de API key (CKEditor 5 Classic via CDN)
- ✅ Editor totalmente funcional sem limitações de modo read-only

### Checklist de Testes

- [x] Editor visual aparece em todos os campos de conteúdo das páginas institucionais
- [x] Toolbar possui todas as funcionalidades necessárias (negrito, itálico, sublinhado, títulos, alinhamento, listas, links)
- [x] Conteúdo formatado é salvo corretamente
- [x] Conteúdo formatado é exibido corretamente nas páginas públicas
- [x] Multi-tenant: alterações em uma loja não afetam outras
- [x] Editor totalmente funcional sem limitações de modo read-only
- [x] Sem necessidade de API key ou configuração adicional

### Nota sobre Substituição do Editor

**Atualização (Fase 11.2.1):** O editor foi substituído de TinyMCE para CKEditor 5 Classic devido a limitações do TinyMCE Cloud (modo read-only sem API key). O CKEditor 5 Classic via CDN não requer API key e oferece funcionalidade completa sem limitações.

### Checklist de Testes Manuais

Para validar a implementação do CKEditor 5:

1. **Acessar `/admin/tema`**
   - [ ] Recarregar a página (limpar cache do navegador se necessário)
   - [ ] Verificar que o overlay "Finish setting up / Add your API key" não aparece mais
   - [ ] Confirmar que o editor é do CKEditor 5 (toolbar diferente, sem aviso de API)
   - [ ] Verificar que é possível clicar dentro do campo e DIGITAR normalmente

2. **Testar uma página (ex: "Política de Cookies")**
   - [ ] Escrever um texto com título maior (usar Heading)
   - [ ] Adicionar parágrafos
   - [ ] Criar lista com bullets
   - [ ] Adicionar link clicável
   - [ ] Salvar tema
   - [ ] Recarregar `/admin/tema` e verificar se o conteúdo aparece igual no editor
   - [ ] Visitar a rota pública correspondente e conferir se a formatação está sendo renderizada (mantendo headers, listas, links)

3. **Testar "Seja Parceiro / Atacado"**
   - [ ] Mesmo processo: editar, salvar, verificar no site

4. **Multi-tenant (se possível)**
   - [ ] Em outro tenant, conferir que o editor funciona igual
   - [ ] Confirmar que o conteúdo é isolado por loja

