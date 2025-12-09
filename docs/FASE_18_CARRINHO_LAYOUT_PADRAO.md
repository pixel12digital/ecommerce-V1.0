# Fase 18: Carrinho usando o mesmo header/footer da loja

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Identificação](#fase-1---identificar-templates-do-layout-e-do-carrinho)
- [Fase 2 - Layout Padrão](#fase-2---fazer-o-carrinho-usar-o-layout-padrão-headerfooter)
- [Fase 3 - Faixa Azul como Sub-header](#fase-3---integrar-a-faixa-azul-como-sub-header-da-página)
- [Fase 4 - Ocultar Categorias](#fase-4---ocultar-a-faixa-de-categorias-em-destaque-na-página-de-carrinho)
- [Fase 5 - Testes](#fase-5---testes-manuais)

---

## Visão Geral

Esta fase integra a página de carrinho ao layout padrão do storefront, garantindo consistência visual com as demais páginas da loja.

**Status:** ✅ Concluída

---

## Fase 1 - Identificar Templates do Layout e do Carrinho

### Templates Identificados

- **Layout padrão:** A home (`themes/default/storefront/home.php`) tem header e footer inline
- **Template do carrinho:** `themes/default/storefront/cart/index.php`
- **Controller:** `src/Http/Controllers/Storefront/CartController.php`

### Estrutura Atual (Antes)

- **Carrinho:** HTML isolado com apenas uma faixa azul simples e conteúdo do carrinho, sem header/footer padrão
- **Home:** HTML completo com topbar, header, footer e todas as seções

---

## Fase 2 - Fazer o Carrinho Usar o Layout Padrão (Header/Footer)

### Controller Atualizado

**Arquivo:** `src/Http/Controllers/Storefront/CartController.php`

**Alterações:**
- Adicionado `use App\Services\ThemeConfig;`
- Método `index()` agora carrega todas as configurações do tema (cores, textos, menu, logo, footer)
- Passa dados necessários para o template: `loja`, `theme`, `cartTotalItems`, `cartSubtotal`

**Dados passados:**
```php
[
    'loja' => ['nome' => $tenant->name, 'slug' => $tenant->slug],
    'theme' => [/* todas as configurações do tema */],
    'cart' => $cart,
    'subtotal' => $subtotal,
    'cartTotalItems' => $cartTotalItems,
    'cartSubtotal' => $subtotal,
]
```

### Template Refatorado

**Arquivo:** `themes/default/storefront/cart/index.php`

**Estrutura implementada:**
- HTML completo com `<!DOCTYPE html>`, `<head>`, `<body>`
- Topbar (igual à home)
- Header completo (logo, busca, menu, ícones de conta/carrinho)
- Faixa azul como sub-header (ver Fase 3)
- Conteúdo do carrinho
- Footer completo (igual à home)

**CSS:**
- Reutiliza estilos do header/footer da home
- Mantém estilos específicos do carrinho (tabela, mensagens, botões)
- Responsivo completo

---

## Fase 3 - Integrar Faixa Azul como Sub-header da Página

### Estrutura HTML

A faixa azul foi transformada em um sub-header logo abaixo do header padrão:

```html
<div class="pg-cart-banner">
    <div class="pg-container">
        <a href="/" class="pg-cart-back-link">
            <i class="bi bi-arrow-left icon"></i>
            Voltar
        </a>
        <h1 class="pg-cart-title">Carrinho de Compras</h1>
    </div>
</div>
```

### CSS

```css
.pg-cart-banner {
    background-color: #023A8D;
    color: #ffffff;
    padding: 16px 0;
}
.pg-cart-banner .pg-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.pg-cart-back-link {
    color: #ffffff;
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.pg-cart-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}
```

**Responsivo:**
- Mobile: flex-direction column, título menor (20px)

---

## Fase 4 - Ocultar Faixa de Categorias em Destaque

### Implementação

A faixa de "Categorias em Destaque" (bolotas) **não é incluída** no template do carrinho.

**Motivo:** Evitar distração na etapa de compra, mantendo foco na conversão.

**Como funciona:**
- A seção de categorias só aparece na home (`themes/default/storefront/home.php`)
- O carrinho não inclui essa seção no HTML
- Não há necessidade de variável `hideCategoryPills` pois a seção simplesmente não é renderizada

---

## Fase 5 - Testes Manuais

### Checklist Desktop

- [x] Topbar preta aparece normalmente
- [x] Header padrão com logo, busca, menu, ícones visíveis
- [x] Faixa azul "Carrinho de Compras / Voltar" aparece logo abaixo do header
- [x] Conteúdo do carrinho (vazio ou com produtos) renderiza corretamente
- [x] Footer completo aparece no final
- [x] Seção de categorias em destaque **não aparece** (como esperado)

### Checklist Mobile

- [x] Header se adapta bem (menu mobile funciona)
- [x] Faixa azul se adapta (flex-direction column)
- [x] Tabela do carrinho tem scroll horizontal quando necessário
- [x] Footer responsivo (1 coluna no mobile)
- [x] Sem overflow estranho

### Checklist Funcionalidade

- [x] Links do header funcionam (logo, menu, busca)
- [x] Link "Voltar" na faixa azul funciona
- [x] Ícone do carrinho no header mostra badge com quantidade
- [x] Tabela do carrinho funciona (atualizar quantidade, remover)
- [x] Botões "Continuar Comprando" e "Finalizar Compra" funcionam
- [x] Mensagens de sucesso/erro aparecem corretamente

---

## Arquivos Modificados

- `src/Http/Controllers/Storefront/CartController.php`
  - Adicionado carregamento de configurações do tema
  - Passa dados completos para o template
  
- `themes/default/storefront/cart/index.php`
  - Refatorado completamente para usar layout padrão
  - Adicionado header e footer
  - Faixa azul transformada em sub-header
  - CSS completo e responsivo

---

## Resultado Final

### Visual

- ✅ Layout consistente com o restante da loja
- ✅ Header padrão com logo, busca, menu, ícones
- ✅ Faixa azul como sub-header (não mais isolada)
- ✅ Footer completo com todas as seções
- ✅ Sem distrações (categorias em destaque ocultas)

### Funcionalidade

- ✅ Navegação completa disponível (menu, busca, conta)
- ✅ Carrinho acessível via ícone no header
- ✅ Responsivo em todas as resoluções
- ✅ Experiência focada na conversão

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** ✅ Concluída

