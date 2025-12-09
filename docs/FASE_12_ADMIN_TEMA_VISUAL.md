# Fase 12: Admin com Paleta do Ponto do Golfe + Logo da Loja

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Fase 1 - Diagnóstico](#fase-1---diagnóstico-rápido)
- [Fase 2 - Sistema de Cores](#fase-2---criar-sistema-mínimo-de-cores-do-admin)
- [Fase 3 - Logo na Sidebar](#fase-3---inserir-logo-da-loja-na-sidebar-do-admin)
- [Fase 4 - Refinamentos](#fase-4---refinar-detalhes-visuais-do-admin)
- [Fase 5 - Testes](#fase-5---testes-visuais)
- [Fase 6 - Documentação](#fase-6---documentação)

---

## Visão Geral

Esta fase aplica a paleta de cores do front (Ponto do Golfe) no painel admin e exibe o logo da loja na sidebar.

**Status:** Em implementação

---

## Fase 1 - Diagnóstico Rápido

### Layout do Admin

- **Arquivo principal:** `themes/default/admin/layouts/store.php`
- **Estrutura:**
  - Sidebar: `.admin-sidebar` com `.sidebar-header` contendo "Store Admin" e nome da loja
  - Topbar: `.admin-topbar` com título da página e link "Sair"
  - Conteúdo: `.admin-content` para área principal

### CSS do Admin

- **Localização:** CSS inline no próprio `store.php` (dentro de `<style>`)
- **Cores atuais:**
  - Sidebar: fundo branco (`background: white`)
  - Topbar: fundo branco
  - Botão primário: laranja `#F7931E` (já está correto!)
  - Links de paginação: azul `#023A8D` (precisa mudar para verde)

### Paleta do Front

- **Verde principal:** Vem de `ThemeConfig::getColor('theme_color_primary')` (padrão: `#2E7D32`)
- **Laranja secundário:** `#F7931E` (já usado no admin)
- **Cores do header do front:** Verde primário do tema

### Logo da Loja

- **Chave:** `logo_url` em `ThemeConfig::get('logo_url')`
- **Uso atual:** Exibido em `/admin/tema` como "Logo Atual"
- **Caminho:** Relativo a `/public` (ex: `/uploads/tenants/{id}/logo/logo.png`)

---

## Fase 2 - Criar Sistema Mínimo de Cores do Admin

### CSS Variables Criadas

```css
:root {
    /* Cores base do painel admin, alinhadas com o front Ponto do Golfe */
    --pg-admin-sidebar-bg:   #2E7D32;  /* verde principal do front */
    --pg-admin-sidebar-hover:#3A9A42;  /* variação para hover/ativo */
    --pg-admin-sidebar-text: #F5F5F5;  /* textos na sidebar */
    --pg-admin-sidebar-muted:#C0C0C0;  /* textos menos importantes/labels */
    --pg-admin-topbar-bg:    #FFFFFF;
    --pg-admin-topbar-text:  #222222;
    --pg-admin-primary:      #F7931E;  /* laranja de destaque da marca */
    --pg-admin-primary-hover:#d67f1a;
    --pg-admin-border-subtle:#E4E4E4;
    --pg-admin-bg-main:      #F5F5F7;
    --pg-admin-card-bg:      #FFFFFF;
}
```

### Substituições Realizadas

- Sidebar: fundo branco → verde (`--pg-admin-sidebar-bg`)
- Links do menu: hover/ativo com verde escuro e borda laranja
- Botões primários: já estavam em laranja (mantido)
- Paginação: azul → verde/laranja
- Cards e formulários: mantidos brancos com bordas sutis

---

## Fase 3 - Inserir Logo da Loja na Sidebar

### Estrutura HTML

- **Bloco:** `.pg-admin-brand` no topo da sidebar
- **Componentes:**
  - Logo: `.pg-admin-brand-logo` (imagem ou placeholder)
  - Texto: `.pg-admin-brand-text` (nome da loja + "Store Admin")

### Obtenção do Logo

- Via `ThemeConfig::get('logo_url')`
- Fallback: placeholder com iniciais da loja se não houver logo

---

## Fase 4 - Refinamentos Visuais

### Menu Lateral

- Links com borda esquerda transparente
- Hover: fundo verde escuro
- Ativo: fundo verde escuro + borda esquerda laranja

### Cards e Títulos

- Bordas sutis
- Sombras leves
- Contraste adequado

---

## Fase 5 - Testes Visuais

### Checklist

- [x] Sidebar verde (cor alinhada com header/front da loja)
- [x] Logo da loja aparecendo ao lado do texto "Store Admin"
- [x] Links da sidebar com hover/active coerentes (verde e laranja)
- [x] Botões principais em laranja
- [x] Outras telas do admin mantêm as mesmas cores
- [x] Responsividade mantida
- [x] Multi-tenant: logo e nome mudam conforme tenant

---

## Fase 6 - Documentação

**Arquivos Alterados:**
- `themes/default/admin/layouts/store.php` - CSS variables, cores da sidebar, logo na sidebar

**CSS Variables Criadas:**
- `--pg-admin-sidebar-bg` - Verde principal
- `--pg-admin-sidebar-hover` - Verde para hover/ativo
- `--pg-admin-sidebar-text` - Texto branco na sidebar
- `--pg-admin-sidebar-muted` - Texto cinza na sidebar
- `--pg-admin-primary` - Laranja de destaque
- `--pg-admin-primary-hover` - Laranja hover
- Outras variáveis para topbar, fundos, bordas

**Logo na Sidebar:**
- Obtido via `ThemeConfig::get('logo_url')`
- Exibido em `.pg-admin-brand-logo`
- Fallback: placeholder com iniciais se não houver logo

---

### Implementação Realizada

#### CSS Variables Criadas

```css
:root {
    --pg-admin-sidebar-bg:   #2E7D32;  /* verde principal do front */
    --pg-admin-sidebar-hover:#3A9A42;  /* variação para hover/ativo */
    --pg-admin-sidebar-text: #F5F5F5;  /* textos na sidebar */
    --pg-admin-sidebar-muted:#C0C0C0;  /* textos menos importantes/labels */
    --pg-admin-topbar-bg:    #FFFFFF;
    --pg-admin-topbar-text:  #222222;
    --pg-admin-primary:      #F7931E;  /* laranja de destaque da marca */
    --pg-admin-primary-hover:#d67f1a;
    --pg-admin-border-subtle:#E4E4E4;
    --pg-admin-bg-main:      #F5F5F7;
    --pg-admin-card-bg:      #FFFFFF;
}
```

#### Substituições de Cores

- ✅ Sidebar: `#023A8D` (azul) → `var(--pg-admin-sidebar-bg)` (verde `#2E7D32`)
- ✅ Links do menu: hover com `var(--pg-admin-sidebar-hover)`, ativo com borda laranja
- ✅ Botões primários: já estavam em laranja (mantido com variável)
- ✅ Paginação: azul → verde (`var(--pg-admin-sidebar-bg)`)
- ✅ Cards e formulários: fundo branco com bordas sutis
- ✅ Body: fundo `var(--pg-admin-bg-main)`

#### Logo na Sidebar

- ✅ Bloco `.pg-admin-brand` no topo da sidebar
- ✅ Logo obtido via `ThemeConfig::get('logo_url')`
- ✅ Fallback: placeholder com iniciais da loja (ex: "LO" para "Loja Demo")
- ✅ Layout: logo à esquerda, nome da loja + "Store Admin" à direita
- ✅ Responsivo: texto com ellipsis se muito longo

#### Refinamentos Visuais

- ✅ Cards com sombras mais sutis (`0 4px 10px rgba(0, 0, 0, 0.03)`)
- ✅ Bordas sutis em cards e formulários
- ✅ Links do menu com borda esquerda transparente → laranja quando ativo
- ✅ Link "Ver Site" em laranja e negrito
- ✅ Títulos de seções com borda inferior

---

**Documento criado em:** 2025-12-08  
**Última atualização:** 2025-12-08  
**Status:** ✅ Concluída

