# Fase 1: Tema + Layout Base da Home

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Arquitetura](#arquitetura)
- [Estrutura de Arquivos](#estrutura-de-arquivos)
- [Configurações de Tema](#configurações-de-tema)
- [Como Usar](#como-usar)
- [API do ThemeConfig](#api-do-themeconfig)
- [Exemplos de Uso](#exemplos-de-uso)
- [Critérios de Aceite](#critérios-de-aceite)

---

## Visão Geral

A Fase 1 implementa um sistema completo de personalização de tema por tenant (loja), permitindo que cada loja configure suas cores, textos, menu e informações de contato através do painel administrativo. A home pública foi completamente reescrita para usar essas configurações dinamicamente.

### Funcionalidades Implementadas

✅ **Infraestrutura de Tema por Tenant**
- Sistema de configurações usando a tabela `tenant_settings`
- Service `ThemeConfig` para gerenciar configurações
- Cache de configurações para performance

✅ **Painel Admin - Tema da Loja**
- Interface completa para editar todas as configurações
- Formulário organizado em seções (Cores, Textos, Contato, Redes Sociais, Menu)
- Validação e salvamento automático

✅ **Home Pública com Layout Completo**
- Top bar configurável
- Header com logo, busca e menu
- Faixa de categorias com scroll horizontal
- Hero slider
- Seção de benefícios
- Seções de produtos por categoria
- Banners retrato
- Newsletter configurável
- Footer completo com todas as informações

✅ **Responsividade**
- Menu hambúrguer no mobile
- Layout adaptativo
- Scroll horizontal para categorias

---

## Arquitetura

### Fluxo de Dados

```
Store Admin (/admin/tema)
    ↓
ThemeController@update()
    ↓
ThemeConfig::set()
    ↓
tenant_settings (banco de dados)
    ↓
HomeController@index()
    ↓
ThemeConfig::get()
    ↓
View storefront/home.php
```

### Componentes Principais

1. **ThemeConfig Service** (`src/Services/ThemeConfig.php`)
   - Gerencia leitura/escrita de configurações
   - Cache em memória
   - Métodos auxiliares para cores e JSON

2. **ThemeController** (`src/Http/Controllers/Admin/ThemeController.php`)
   - `edit()` - Exibe formulário de edição
   - `update()` - Processa e salva configurações

3. **HomeController** (`src/Http/Controllers/Storefront/HomeController.php`)
   - Carrega todas as configurações do tema
   - Busca produtos para exibição
   - Passa dados para a view

4. **View Admin** (`themes/default/admin/theme/edit.php`)
   - Formulário completo de edição
   - Organizado em seções
   - Validação client-side

5. **View Home** (`themes/default/storefront/home.php`)
   - Layout completo responsivo
   - Usa todas as configurações do tema
   - CSS inline com variáveis dinâmicas

---

## Estrutura de Arquivos

```
ecommerce-v1.0/
├── src/
│   ├── Services/
│   │   └── ThemeConfig.php          # Service de configurações
│   └── Http/
│       └── Controllers/
│           ├── Admin/
│           │   └── ThemeController.php  # Controller admin tema
│           └── Storefront/
│               └── HomeController.php   # Controller home (atualizado)
│
├── themes/
│   └── default/
│       ├── admin/
│       │   └── theme/
│       │       └── edit.php         # View edição tema
│       └── storefront/
│           └── home.php             # View home (reescrita)
│
├── database/
│   └── seeds/
│       └── 001_initial_seed.php    # Seed com configs padrão
│
└── public/
    └── index.php                    # Rotas atualizadas
```

---

## Configurações de Tema

### Cores (8 configurações)

| Chave | Descrição | Padrão |
|-------|-----------|--------|
| `theme_color_primary` | Cor primária (botões, links principais) | `#2E7D32` |
| `theme_color_secondary` | Cor secundária (destaques) | `#F7931E` |
| `theme_color_topbar_bg` | Fundo da top bar | `#1a1a1a` |
| `theme_color_topbar_text` | Texto da top bar | `#ffffff` |
| `theme_color_header_bg` | Fundo do header | `#ffffff` |
| `theme_color_header_text` | Texto do header | `#333333` |
| `theme_color_footer_bg` | Fundo do footer | `#1a1a1a` |
| `theme_color_footer_text` | Texto do footer | `#ffffff` |

### Textos e Identidade (3 configurações)

| Chave | Descrição | Padrão |
|-------|-----------|--------|
| `topbar_text` | Texto exibido na top bar | `Frete grátis acima de R$ 299 \| Troca garantida em até 7 dias \| Outlet de golfe` |
| `newsletter_title` | Título da seção newsletter | `Receba nossas ofertas` |
| `newsletter_subtitle` | Subtítulo da seção newsletter | `Cadastre-se e receba promoções exclusivas` |

### Contato e Endereço (4 configurações)

| Chave | Descrição | Padrão |
|-------|-----------|--------|
| `footer_phone` | Telefone de contato | (vazio) |
| `footer_whatsapp` | WhatsApp de contato | (vazio) |
| `footer_email` | E-mail de contato | (vazio) |
| `footer_address` | Endereço completo | (vazio) |

### Redes Sociais (3 configurações)

| Chave | Descrição | Padrão |
|-------|-----------|--------|
| `footer_social_instagram` | URL do Instagram | (vazio) |
| `footer_social_facebook` | URL do Facebook | (vazio) |
| `footer_social_youtube` | URL do YouTube | (vazio) |

### Menu Principal (1 configuração JSON)

| Chave | Descrição | Formato |
|-------|-----------|---------|
| `theme_menu_main` | Itens do menu principal | Array JSON de objetos `{label, url, enabled}` |

**Estrutura do JSON:**
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
  }
]
```

---

## Como Usar

### 1. Acessar o Painel de Tema

1. Faça login no Store Admin: `http://localhost/ecommerce-v1.0/public/admin/login`
2. Acesse "Tema da Loja" no dashboard ou diretamente: `http://localhost/ecommerce-v1.0/public/admin/tema`

### 2. Editar Configurações

#### Cores
- Use os seletores de cor ou digite o código hex diretamente
- As cores são aplicadas imediatamente após salvar

#### Textos
- Preencha os campos de texto conforme necessário
- Campos vazios usam valores padrão

#### Contato
- Preencha telefone, WhatsApp, e-mail e endereço
- Esses dados aparecem no footer da loja

#### Redes Sociais
- Cole as URLs completas (ex: `https://instagram.com/minhaloja`)
- Apenas links preenchidos aparecem no footer

#### Menu Principal
- Edite label e URL de cada item
- Marque/desmarque "Ativo" para mostrar/ocultar no menu
- Itens desativados não aparecem na home

### 3. Salvar

- Clique em "Salvar Tema"
- Você será redirecionado com mensagem de sucesso
- As alterações são aplicadas imediatamente na home

### 4. Verificar Resultado

- Acesse a home: `http://localhost/ecommerce-v1.0/public/`
- Todas as configurações devem estar refletidas

---

## API do ThemeConfig

### Métodos Públicos

#### `ThemeConfig::get(string $key, $default = null)`

Obtém uma configuração do tema.

```php
$topbarText = ThemeConfig::get('topbar_text', 'Texto padrão');
```

#### `ThemeConfig::getColor(string $key, string $default = '#000000')`

Obtém uma cor garantindo formato hex válido.

```php
$primaryColor = ThemeConfig::getColor('theme_color_primary', '#2E7D32');
// Retorna sempre no formato #RRGGBB
```

#### `ThemeConfig::getJson(string $key, array $default = [])`

Obtém e decodifica um JSON.

```php
$menu = ThemeConfig::getJson('theme_menu_main', []);
// Retorna array PHP
```

#### `ThemeConfig::getMainMenu()`

Obtém o menu principal apenas com itens habilitados.

```php
$menuItems = ThemeConfig::getMainMenu();
// Retorna array filtrado com enabled = true
```

#### `ThemeConfig::set(string $key, $value)`

Define uma configuração do tema.

```php
ThemeConfig::set('theme_color_primary', '#FF0000');
ThemeConfig::set('topbar_text', 'Novo texto');
ThemeConfig::set('theme_menu_main', [
    ['label' => 'Home', 'url' => '/', 'enabled' => true]
]);
```

#### `ThemeConfig::clearCache()`

Limpa o cache de configurações (útil após atualizações).

```php
ThemeConfig::clearCache();
```

---

## Exemplos de Uso

### Exemplo 1: Obter Cor Primária

```php
use App\Services\ThemeConfig;

$primaryColor = ThemeConfig::getColor('theme_color_primary');
// Retorna: #2E7D32 (ou o valor configurado)
```

### Exemplo 2: Obter Menu Principal

```php
use App\Services\ThemeConfig;

$menuItems = ThemeConfig::getMainMenu();

foreach ($menuItems as $item) {
    echo "<a href='{$item['url']}'>{$item['label']}</a>";
}
```

### Exemplo 3: Definir Nova Cor

```php
use App\Services\ThemeConfig;

ThemeConfig::set('theme_color_primary', '#FF5733');
ThemeConfig::clearCache(); // Limpar cache para refletir mudança
```

### Exemplo 4: Usar em View

```php
// No controller
$theme = [
    'color_primary' => ThemeConfig::getColor('theme_color_primary'),
    'topbar_text' => ThemeConfig::get('topbar_text'),
];

$this->view('minha_view', ['theme' => $theme]);
```

```php
<!-- Na view -->
<div style="background: <?= htmlspecialchars($theme['color_primary']) ?>">
    <?= htmlspecialchars($theme['topbar_text']) ?>
</div>
```

---

## Rotas

### Admin

| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/admin/tema` | `ThemeController@edit` | Exibe formulário de edição |
| POST | `/admin/tema` | `ThemeController@update` | Salva configurações |

### Pública

| Método | Rota | Controller | Descrição |
|--------|------|------------|-----------|
| GET | `/` | `HomeController@index` | Home com layout completo |

---

## Seed Inicial

O seed `001_initial_seed.php` já inclui todas as configurações padrão para `tenant_id = 1`.

### Executar Seed

```bash
php database/run_seed.php
```

### Configurações Inseridas

- ✅ 8 cores padrão
- ✅ 3 textos padrão
- ✅ 4 campos de contato (vazios)
- ✅ 3 redes sociais (vazias)
- ✅ Menu principal com 6 itens

---

## Estrutura do Banco de Dados

### Tabela: `tenant_settings`

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | BIGINT UNSIGNED | Chave primária |
| `tenant_id` | BIGINT UNSIGNED | ID do tenant |
| `key` | VARCHAR(255) | Chave da configuração |
| `value` | TEXT | Valor da configuração |
| `created_at` | DATETIME | Data de criação |
| `updated_at` | DATETIME | Data de atualização |

**Índices:**
- PRIMARY KEY (`id`)
- UNIQUE KEY (`tenant_id`, `key`)
- INDEX (`tenant_id`)

---

## Critérios de Aceite

### ✅ Painel Store Admin

- [x] Existe menu "Tema da Loja"
- [x] Consigo alterar cores e textos
- [x] Ao salvar, a home reflete mudanças sem editar código
- [x] Consigo ativar/desativar itens do menu principal

### ✅ Loja Pública

- [x] Home exibe top bar com texto configurado
- [x] Header com logo, busca, menu
- [x] Faixa de categorias em círculos (com dados dummy)
- [x] Hero com ao menos 1 slide
- [x] Seção de benefícios com 4 cards
- [x] Bloco de newsletter com títulos configuráveis
- [x] Footer com dados de contato e redes sociais configuráveis
- [x] No mobile, menu principal vira hambúrguer
- [x] Faixa de categorias é rolável horizontalmente

---

## Próximas Fases

### Não Implementado (Fase 1)

- ❌ Conteúdo dinâmico das seções de categoria
- ❌ Gestão de banners no admin
- ❌ Salvamento de newsletter
- ❌ Upload de logo
- ❌ Categorias reais na faixa (atualmente dummy)

Essas funcionalidades serão implementadas em fases seguintes.

---

## Troubleshooting

### Problema: Configurações não aparecem na home

**Solução:**
1. Verifique se o seed foi executado: `php database/run_seed.php`
2. Limpe o cache: `ThemeConfig::clearCache()` (ou recarregue a página)
3. Verifique se o `tenant_id` está correto no `TenantContext`

### Problema: Cores não são aplicadas

**Solução:**
1. Verifique se o formato está correto (hex: `#RRGGBB`)
2. Use `ThemeConfig::getColor()` que garante formato válido
3. Verifique se o CSS está usando as variáveis corretas

### Problema: Menu não aparece

**Solução:**
1. Verifique se há itens com `enabled: true` no JSON
2. Use `ThemeConfig::getMainMenu()` que filtra automaticamente
3. Verifique se o JSON está válido no banco

### Problema: Formulário não salva

**Solução:**
1. Verifique se está autenticado no Store Admin
2. Verifique permissões de escrita no banco
3. Verifique logs de erro do PHP

---

## Notas Técnicas

### Cache

O `ThemeConfig` usa cache em memória para evitar múltiplas consultas ao banco. O cache é limpo automaticamente após `set()`, mas pode ser limpo manualmente com `clearCache()`.

### Segurança

- Todas as saídas usam `htmlspecialchars()` para prevenir XSS
- Validação de cores garante formato hex válido
- JSON é validado antes de salvar

### Performance

- Cache reduz consultas ao banco
- Configurações são carregadas uma vez por requisição
- CSS inline evita requisições adicionais

---

## Changelog

### Fase 1.0 (2025-01-XX)

- ✅ Implementação inicial do sistema de tema
- ✅ Painel admin para edição de tema
- ✅ Home pública com layout completo
- ✅ Seed com configurações padrão
- ✅ Documentação completa

---

**Documento criado em:** 2025-01-XX  
**Última atualização:** 2025-01-XX  
**Versão:** 1.0
