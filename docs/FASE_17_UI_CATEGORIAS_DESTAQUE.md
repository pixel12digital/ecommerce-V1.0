# Fase 17: Ajustar UI das Categorias em Destaque (Círculos estilo Ponto do Golfe)

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Identificação](#fase-1---identificar-a-view-e-o-markup-atual)
- [Fase 2 - Ajuste de Markup](#fase-2---ajustar-markup-para-suportar-o-layout-em-círculos)
- [Fase 3 - Estilização Desktop](#fase-3---estilizar-faixa-e-círculos-desktop)
- [Fase 4 - Responsivo Mobile](#fase-4---responsivo-mobile)
- [Fase 5 - Limpeza](#fase-5---limpeza-de-estilos-antigos)
- [Fase 6 - Testes](#fase-6---testes-manuais)

---

## Visão Geral

Esta fase ajusta a UI da faixa de Categorias em Destaque na home para ficar visualmente igual ao site de referência do Ponto do Golfe, com círculos brancos sobre fundo verde escuro.

**Status:** ✅ Concluída

---

## Fase 1 - Identificar a View e o Markup Atual

### View Identificada

- **Arquivo:** `themes/default/storefront/home.php`
- **Seção:** Linhas 1085-1105 (aprox.)
- **Estrutura antiga:**
  - Container: `.categories-bar` (fundo cinza claro)
  - Botão: `.categories-toggle` (pill verde)
  - Scroll: `.categories-scroll`
  - Items: `.category-chip` (pills ovais brancas)

### Dados

- Variável: `$categoryPills` (array de categorias em destaque)
- Campos disponíveis:
  - `icone_path` - Caminho da imagem
  - `label` - Label customizado (ou `categoria_nome` como fallback)
  - `categoria_slug` - Slug para URL

---

## Fase 2 - Ajustar Markup para Suportar o Layout em Círculos

### Nova Estrutura HTML

```html
<section class="pg-category-strip">
    <div class="pg-category-strip-inner">
        <a href="/produtos" class="pg-category-main-button">
            <span class="pg-category-main-button-icon">
                <i class="bi bi-list icon"></i>
            </span>
            <span class="pg-category-main-button-label">Categorias</span>
        </a>
        <div class="pg-category-pills-scroll">
            <?php foreach ($categoryPills as $pill): ?>
                <a href="..." class="pg-category-pill">
                    <div class="pg-category-pill-circle">
                        <img src="..." alt="...">
                    </div>
                    <span class="pg-category-pill-label">Nome</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
```

### Alterações Realizadas

- ✅ Container principal: `.categories-bar` → `.pg-category-strip`
- ✅ Botão "Categorias": `.categories-toggle` → `.pg-category-main-button` (agora é `<a>`)
- ✅ Scroll: `.categories-scroll` → `.pg-category-pills-scroll`
- ✅ Items: `.category-chip` → `.pg-category-pill` com estrutura de círculo
- ✅ Cada pill agora tem: círculo branco + label abaixo
- ✅ Placeholder adicionado para imagens ausentes

---

## Fase 3 - Estilizar Faixa e Círculos (Desktop)

### CSS Implementado

#### Container da Faixa

```css
.pg-category-strip {
    background-color: var(--cor-primaria, #2E7D32);
    padding: 16px 0;
    width: 100%;
}
.pg-category-strip-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0 16px;
}
```

#### Botão "Categorias"

```css
.pg-category-main-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 32px;
    height: 64px;
    border-radius: 999px;
    background-color: #ffffff;
    color: var(--cor-primaria);
    font-weight: 600;
    font-size: 16px;
}
.pg-category-main-button-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid var(--cor-primaria);
}
```

#### Círculos de Categorias

```css
.pg-category-pill-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #ffffff;
    padding: 4px;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
}
.pg-category-pill-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}
.pg-category-pill-label {
    margin-top: 8px;
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    color: #ffffff;
}
```

### Características Visuais

- ✅ Fundo verde escuro (cor primária do tema)
- ✅ Botão "Categorias" branco à esquerda
- ✅ Círculos brancos de 80px com imagens recortadas
- ✅ Nomes das categorias em branco abaixo dos círculos
- ✅ Scroll horizontal suave
- ✅ Hover effects (elevação, sombra, underline)

---

## Fase 4 - Responsivo (Mobile)

### Breakpoint: 768px

```css
@media (max-width: 768px) {
    .pg-category-strip-inner {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        padding: 0 12px;
    }
    .pg-category-main-button {
        width: 100%;
        justify-content: center;
        height: 56px;
    }
    .pg-category-pill-circle {
        width: 64px;
        height: 64px;
    }
    .pg-category-pill-label {
        font-size: 12px;
        max-width: 72px;
    }
}
```

### Comportamento Mobile

- ✅ Botão "Categorias" ocupa largura total
- ✅ Círculos reduzidos para 64px
- ✅ Scroll horizontal mantido para categorias
- ✅ Labels com fonte menor

---

## Fase 5 - Limpeza de Estilos Antigos

### Estilos Removidos

- ❌ `.categories-bar` (fundo cinza)
- ❌ `.categories-container` (container antigo)
- ❌ `.categories-toggle` (botão antigo)
- ❌ `.categories-scroll` (scroll antigo)
- ❌ `.category-chip` (pills ovais antigas)

### Estilos Mantidos

- ✅ Apenas classes `.pg-category-*` (novo padrão)
- ✅ CSS responsivo atualizado
- ✅ Sem conflitos visuais

---

## Fase 6 - Testes Manuais

### Checklist Desktop

- [x] Faixa tem fundo verde escuro (cor primária)
- [x] Botão "Categorias" branco à esquerda com ícone
- [x] Círculos brancos de 80px com imagens
- [x] Nomes centralizados abaixo em branco
- [x] Scroll horizontal funciona
- [x] Hover effects funcionam
- [x] Links funcionam corretamente

### Checklist Mobile

- [x] Botão "Categorias" ocupa largura total
- [x] Círculos reduzidos para 64px
- [x] Scroll horizontal funciona com toque
- [x] Layout não quebra em telas pequenas

### Acessibilidade

- [x] Imagens têm `alt` com nome da categoria
- [x] Botão "Categorias" tem `aria-label`
- [x] Links têm `aria-label` descritivo
- [x] Foco via teclado funciona
- [x] Outline visível no foco

---

## Arquivos Modificados

- `themes/default/storefront/home.php`
  - HTML da seção de categorias atualizado
  - CSS completo reescrito
  - CSS responsivo atualizado

---

## Resultado Final

### Visual

- ✅ Fundo verde escuro contínuo
- ✅ Botão "Categorias" branco à esquerda
- ✅ Círculos brancos com imagens recortadas
- ✅ Nomes em branco abaixo dos círculos
- ✅ Layout alinhado ao site de referência

### Funcionalidade

- ✅ Scroll horizontal suave
- ✅ Links funcionais
- ✅ Responsivo em mobile
- ✅ Acessível (teclado, screen readers)

---

---

## Ajustes pós-teste (Centralização, Menu e Labels)

### Status: ✅ Concluída

### Implementação

**Objetivo:** Melhorar a UX da faixa de categorias com centralização inteligente, menu overlay e tratamento de labels longas.

#### Fase 1 - Centralização + Scroll Inteligente ✅

- **Viewport wrapper adicionado:**
  - Nova estrutura: `.pg-category-pills-viewport` (container com overflow)
  - `.pg-category-pills-scroll` agora é `inline-flex` com `margin: 0 auto` para centralização
- **Comportamento:**
  - Com poucas categorias: centralizadas automaticamente
  - Com muitas categorias: scroll horizontal aparece quando necessário
  - Visualmente mantém alinhamento centralizado

#### Fase 2 - Menu Overlay de Categorias ✅

- **HTML do overlay:**
  - Overlay com backdrop escuro
  - Painel centralizado com lista de categorias
  - Botão de fechar e header com título
- **JavaScript:**
  - Abre/fecha ao clicar no botão "Categorias"
  - Fecha ao clicar no backdrop ou botão X
  - Fecha com tecla ESC
  - Previne scroll do body quando aberto
  - Foco automático no primeiro link ao abrir
  - Acessibilidade: `aria-expanded`, `aria-controls`, `role="dialog"`
- **CSS:**
  - Animações suaves (opacity, transform)
  - Responsivo (mobile com ajustes de tamanho)
  - Estilos de hover/focus para links

#### Fase 3 - Labels Longas (Line-clamp) ✅

- **CSS atualizado:**
  - `.pg-category-pill-label` agora usa `-webkit-line-clamp: 2`
  - Limita a 2 linhas máximo
  - Texto cortado com ellipsis quando excede
  - `word-break: break-word` para palavras longas
  - Mantém centralização e legibilidade

### Arquivos Modificados

- `src/Http/Controllers/Storefront/HomeController.php`
  - Adicionada variável `$allCategories` para o menu
- `themes/default/storefront/home.php`
  - HTML: viewport wrapper, overlay do menu
  - CSS: centralização, menu overlay, line-clamp
  - JavaScript: controle do menu

### Checklist de Testes

#### Desktop
- [x] Poucas categorias (3-4) ficam centralizadas
- [x] Muitas categorias mostram scroll horizontal
- [x] Botão "Categorias" abre overlay
- [x] Overlay mostra lista de categorias
- [x] Clicar em categoria fecha overlay e navega
- [x] ESC fecha overlay sem navegar
- [x] Clicar fora (backdrop) fecha overlay

#### Mobile
- [x] Centralização funciona
- [x] Scroll horizontal funciona
- [x] Menu ocupa tela corretamente
- [x] Scroll vertical dentro do painel funciona

#### Labels Longas
- [x] Labels longas aparecem em até 2 linhas
- [x] Layout não quebra
- [x] Texto cortado com ellipsis quando necessário

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** ✅ Concluída (incluindo ajustes pós-teste)

