# FASE 19 • Banners da Home (Bug + UX)

## Objetivo

Corrigir bugs de persistência do tipo de banner "Retrato" e melhorar a experiência do usuário na gestão de banners da home, tornando a interface mais intuitiva e autoexplicativa.

## Problemas Identificados

1. **Bug de persistência**: Banners criados como "Retrato" apareciam na aba "Hero" e desapareciam da aba "Retrato" após salvar.
2. **UX confusa**: Termos técnicos "Hero" e "Retrato" não eram claros para lojistas.
3. **Falta de contexto**: Não ficava claro onde cadastrar banners para desktop vs mobile.

## Correções Implementadas

### TAREFA 1 – Corrigir persistência do tipo "Retrato"

**Arquivos modificados:**
- `src/Http/Controllers/Admin/HomeBannersController.php`

**Mudanças:**
- Método `create()` agora recebe `tipo` via query string (`?tipo=hero` ou `?tipo=portrait`) e passa `tipoInicial` para a view.
- Métodos `store()` e `update()` agora redirecionam mantendo o filtro de tipo na URL após salvar.

**Resultado:**
- Banners criados via aba "Retrato" são salvos corretamente com `tipo = 'portrait'`.
- Banners criados via aba "Hero" são salvos corretamente com `tipo = 'hero'`.
- Após salvar, o usuário permanece na aba correta.

### TAREFA 2 – Melhorar textos e UX das abas e formulário

**Arquivos modificados:**
- `themes/default/admin/home/banners-content.php`
- `themes/default/admin/home/banners-form-content.php`

**Mudanças nas abas:**
- "Hero" → "Carrossel principal (topo)"
- "Retrato" → "Banners de apoio (retratos)"
- Badge nos cards: "Hero" → "Carrossel", "Retrato" → "Apoio"
- Título da página: "Banners Configurados" → "Banners da Home"
- Botões de criação: Separados em "+ Carrossel principal" e "+ Banner de apoio"

**Mudanças no formulário:**
- Campo "Tipo" substituído por "Posição do banner" com radio buttons visuais.
- Radio buttons com descrições claras:
  - **Carrossel principal (topo)**: "Banner grande no topo da página, visível em desktop e celular"
  - **Banners de apoio (retratos)**: "Banners menores em formato retrato para áreas laterais ou de apoio"
- Textos de ajuda nos campos de imagem:
  - **Imagem Desktop**: "Versão do banner para telas de computador (carrossel principal). Se você não enviar imagem mobile, esta será usada também no celular."
  - **Imagem Mobile**: "Versão do banner otimizada para celular. Recomendada para o carrossel em dispositivos móveis."

**CSS adicionado:**
- Estilos para radio buttons visuais com hover e estados ativos.
- Layout responsivo e acessível.

### TAREFA 3 – Home exibindo carrossel corretamente

**Arquivos verificados:**
- `src/Http/Controllers/Storefront/HomeController.php`
- `themes/default/storefront/home.php`

**Status:**
- ✅ Home já busca apenas banners `tipo = 'hero'` e `ativo = 1`.
- ✅ Carrossel funciona corretamente com rotação automática a cada 5 segundos.
- ✅ Suporta banners com imagem desktop, mobile ou apenas texto/CTA.
- ✅ Usa `<picture>` para responsividade de imagens.

### TAREFA 4 – UX da Biblioteca de Mídia

**Arquivos verificados:**
- `public/admin/js/media-picker.js`
- `src/Http/Controllers/Admin/MediaLibraryController.php`

**Status:**
- ✅ Biblioteca de Mídia já está funcionando corretamente para banners.
- ✅ Botões "Escolher da biblioteca" têm `data-folder="banners"`.
- ✅ Modal filtra corretamente imagens da pasta "banners".
- ✅ Upload múltiplo funcionando.
- ✅ Seleção visual e botão "Usar imagem selecionada" implementados.

## Estrutura de Dados

### Tabela `banners`

```sql
CREATE TABLE banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('hero', 'portrait') NOT NULL,
    titulo VARCHAR(150) NULL,
    subtitulo VARCHAR(255) NULL,
    cta_label VARCHAR(50) NULL,
    cta_url VARCHAR(255) NULL,
    imagem_desktop VARCHAR(255) NULL,
    imagem_mobile VARCHAR(255) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_tipo_ativo (tenant_id, tipo, ativo)
);
```

**Valores de `tipo`:**
- `'hero'`: Carrossel principal (topo da página)
- `'portrait'`: Banners de apoio (retratos)

## Fluxo de Uso

### Criar Banner do Carrossel Principal

1. Acessar `/admin/home/banners`
2. Clicar em "+ Carrossel principal" (ou na aba "Carrossel principal (topo)" → "+ Novo Banner")
3. Formulário abre com "Carrossel principal (topo)" pré-selecionado
4. Preencher título, subtítulo, CTA (opcional)
5. Escolher imagem desktop e/ou mobile via Biblioteca de Mídia
6. Definir ordem e status ativo
7. Salvar → redireciona para aba "Carrossel principal (topo)"

### Criar Banner de Apoio

1. Acessar `/admin/home/banners`
2. Clicar em "+ Banner de apoio" (ou na aba "Banners de apoio (retratos)" → "+ Novo Banner")
3. Formulário abre com "Banners de apoio (retratos)" pré-selecionado
4. Preencher título, subtítulo, CTA (opcional)
5. **Obrigatório**: Escolher imagem desktop via Biblioteca de Mídia
6. Definir ordem e status ativo
7. Salvar → redireciona para aba "Banners de apoio (retratos)"

### Editar Banner

1. Clicar em "Editar" em qualquer card de banner
2. Formulário abre com o tipo atual do banner pré-selecionado
3. Alterações são salvas mantendo ou alterando o tipo conforme seleção
4. Após salvar, redireciona para a aba correspondente ao tipo salvo

## Testes Realizados

### Teste 1: Persistência do tipo "Retrato"
- ✅ Criar banner via aba "Banners de apoio (retratos)"
- ✅ Verificar que aparece apenas na aba "Banners de apoio (retratos)"
- ✅ Editar e salvar → continua aparecendo na aba correta

### Teste 2: Persistência do tipo "Hero"
- ✅ Criar banner via aba "Carrossel principal (topo)"
- ✅ Verificar que aparece apenas na aba "Carrossel principal (topo)"
- ✅ Editar e salvar → continua aparecendo na aba correta

### Teste 3: Carrossel na Home
- ✅ Criar 2+ banners hero ativos
- ✅ Verificar rotação automática a cada 5 segundos
- ✅ Verificar responsividade (desktop vs mobile)

### Teste 4: Biblioteca de Mídia
- ✅ Upload de imagens na pasta "banners"
- ✅ Seleção de imagem preenche campo corretamente
- ✅ Modal fecha após seleção

## Arquivos Modificados

1. `src/Http/Controllers/Admin/HomeBannersController.php`
   - Método `create()`: Recebe `tipo` via query string
   - Métodos `store()` e `update()`: Redirecionam mantendo filtro de tipo

2. `themes/default/admin/home/banners-content.php`
   - Abas renomeadas para termos mais amigáveis
   - Botões de criação separados por tipo
   - Badge nos cards atualizado

3. `themes/default/admin/home/banners-form-content.php`
   - Campo "Tipo" substituído por radio buttons visuais
   - Textos de ajuda melhorados
   - CSS para radio buttons visuais

## Notas Técnicas

- Os valores internos no banco continuam sendo `'hero'` e `'portrait'` (sem quebrar compatibilidade).
- Apenas os textos visuais foram alterados para melhorar a UX.
- O carrossel na home já estava funcionando corretamente, apenas foi documentado.
- A Biblioteca de Mídia já estava funcionando, apenas foi confirmada a integração.

## Próximos Passos (Opcional)

- [ ] Adicionar preview do banner no formulário
- [ ] Implementar drag-and-drop para reordenar banners
- [ ] Adicionar validação de dimensões de imagem recomendadas
- [ ] Implementar área de exibição dos banners de apoio (portrait) na home

