# Checklist de Testes de Fluxo - Fase 10

Este documento contém o checklist executável para validação dos fluxos principais do sistema após os ajustes de layout da Fase 10.

**Data de criação:** 06/12/2025  
**Status:** Aguardando execução

---

## 📋 Cliente (Storefront)

### Fluxo de Compra – Cliente Novo

- [ ] **Home → Produto → Carrinho**
  - [ ] Navegar pela home
  - [ ] Clicar em um produto
  - [ ] Verificar layout da PDP (galeria, informações, avaliações)
  - [ ] Adicionar produto ao carrinho
  - [ ] Verificar mensagem de sucesso
  - [ ] Ir para o carrinho
  - [ ] Verificar layout do carrinho (tabela, resumo, botões)

- [ ] **Checkout – Criação de Conta**
  - [ ] Iniciar checkout
  - [ ] Verificar layout do formulário (dados, endereço, frete, pagamento)
  - [ ] Preencher dados do cliente
  - [ ] Preencher endereço de entrega
  - [ ] Selecionar frete
  - [ ] Selecionar forma de pagamento
  - [ ] Finalizar pedido
  - [ ] Verificar página de confirmação

- [ ] **Área do Cliente**
  - [ ] Fazer login (se necessário)
  - [ ] Verificar dashboard
  - [ ] Verificar pedido na lista de pedidos
  - [ ] Verificar detalhes do pedido
  - [ ] Verificar layout responsivo em mobile

### Fluxo de Compra – Cliente Existente

- [ ] **Login e Compra**
  - [ ] Fazer login na área do cliente
  - [ ] Navegar para produtos
  - [ ] Adicionar produto ao carrinho
  - [ ] Finalizar checkout (dados já preenchidos)
  - [ ] Verificar novo pedido na lista de pedidos

### Avaliações de Produtos

- [ ] **Cliente Avalia**
  - [ ] Fazer login como cliente que comprou o produto
  - [ ] Acessar PDP do produto comprado
  - [ ] Verificar seção de avaliações
  - [ ] Preencher formulário de avaliação (nota, título, comentário)
  - [ ] Enviar avaliação
  - [ ] Verificar mensagem de sucesso (avaliação pendente)

- [ ] **Admin Modera**
  - [ ] Fazer login no admin
  - [ ] Acessar "Avaliações"
  - [ ] Verificar avaliação pendente na listagem
  - [ ] Aprovar avaliação
  - [ ] Verificar que avaliação aparece na PDP
  - [ ] Rejeitar outra avaliação (se houver)
  - [ ] Verificar que avaliação rejeitada não aparece na PDP

### Newsletter

- [ ] **Cadastro na Home**
  - [ ] Acessar home
  - [ ] Preencher e-mail no formulário de newsletter
  - [ ] Enviar
  - [ ] Verificar mensagem de sucesso/erro

- [ ] **Verificação no Admin**
  - [ ] Fazer login no admin
  - [ ] Acessar "Newsletter"
  - [ ] Verificar e-mail cadastrado na listagem
  - [ ] Tentar cadastrar e-mail duplicado
  - [ ] Verificar comportamento (mensagem de erro ou ignorar)

### Área do Cliente – Funcionalidades

- [ ] **Dashboard**
  - [ ] Verificar layout do dashboard
  - [ ] Verificar cards de estatísticas
  - [ ] Verificar lista de últimos pedidos
  - [ ] Verificar links funcionais

- [ ] **Pedidos**
  - [ ] Verificar listagem de pedidos (tabela, status, valores)
  - [ ] Verificar detalhes do pedido (itens, endereço, pagamento)
  - [ ] Verificar layout responsivo

- [ ] **Endereços**
  - [ ] Verificar listagem de endereços
  - [ ] Adicionar novo endereço
  - [ ] Editar endereço existente
  - [ ] Excluir endereço
  - [ ] Verificar mensagens de feedback

- [ ] **Perfil**
  - [ ] Verificar formulário de dados pessoais
  - [ ] Atualizar nome/telefone
  - [ ] Alterar senha
  - [ ] Verificar mensagens de feedback

---

## 🔧 Admin

### Produtos

- [ ] **Listagem**
  - [ ] Acessar "Produtos" no admin
  - [ ] Verificar layout da tabela (cabeçalho, filtros, paginação)
  - [ ] Testar filtros (busca, status)
  - [ ] Verificar paginação
  - [ ] Verificar ícones e botões de ação

- [ ] **Edição de Produto**
  - [ ] Acessar detalhes de um produto
  - [ ] Verificar layout do formulário
  - [ ] Editar dados gerais (nome, preço, estoque)
  - [ ] Gerenciar imagens (adicionar, remover, ordenar)
  - [ ] Gerenciar vídeos (adicionar, remover)
  - [ ] Salvar alterações
  - [ ] Verificar mensagem de sucesso

### Pedidos

- [ ] **Listagem**
  - [ ] Acessar "Pedidos" no admin
  - [ ] Verificar layout da tabela
  - [ ] Testar filtros (busca, status)
  - [ ] Verificar paginação

- [ ] **Detalhes do Pedido**
  - [ ] Acessar detalhes de um pedido
  - [ ] Verificar informações do cliente
  - [ ] Verificar itens do pedido
  - [ ] Verificar endereço de entrega
  - [ ] Alterar status do pedido
  - [ ] Verificar atualização na listagem

### Clientes

- [ ] **Listagem**
  - [ ] Acessar "Clientes" no admin
  - [ ] Verificar layout da tabela
  - [ ] Testar filtros e busca
  - [ ] Verificar paginação

- [ ] **Detalhes do Cliente**
  - [ ] Acessar detalhes de um cliente
  - [ ] Verificar informações do cliente
  - [ ] Verificar histórico de pedidos
  - [ ] Verificar endereços cadastrados

### Avaliações

- [ ] **Listagem**
  - [ ] Acessar "Avaliações" no admin
  - [ ] Verificar layout da tabela
  - [ ] Verificar filtros (status: pendente, aprovado, rejeitado)
  - [ ] Verificar paginação

- [ ] **Moderação**
  - [ ] Acessar detalhes de uma avaliação pendente
  - [ ] Aprovar avaliação
  - [ ] Verificar que avaliação aparece na PDP
  - [ ] Rejeitar outra avaliação
  - [ ] Verificar que avaliação rejeitada não aparece na PDP

### Home da Loja

- [ ] **Categorias em Destaque (Pills)**
  - [ ] Acessar "Home da Loja" → "Categorias em Destaque"
  - [ ] Verificar layout do formulário
  - [ ] Adicionar/editar categorias
  - [ ] Salvar alterações
  - [ ] Verificar na home do storefront

- [ ] **Seções de Categorias**
  - [ ] Acessar "Seções de Categorias"
  - [ ] Verificar layout do formulário
  - [ ] Adicionar/editar seções
  - [ ] Salvar alterações
  - [ ] Verificar na home do storefront

- [ ] **Banners**
  - [ ] Acessar "Banners"
  - [ ] Verificar layout do formulário
  - [ ] Adicionar/editar banners
  - [ ] Salvar alterações
  - [ ] Verificar na home do storefront

### Tema da Loja

- [ ] **Configuração de Cores**
  - [ ] Acessar "Tema da Loja"
  - [ ] Verificar layout do formulário
  - [ ] Alterar cores (primária, secundária, header, footer)
  - [ ] Salvar alterações
  - [ ] Verificar aplicação no storefront

- [ ] **Layout e Textos**
  - [ ] Alterar textos (topbar, newsletter)
  - [ ] Alterar contato e endereço
  - [ ] Alterar redes sociais
  - [ ] Alterar menu principal
  - [ ] Salvar alterações
  - [ ] Verificar aplicação no storefront

### Gateways

- [ ] **Listagem**
  - [ ] Acessar "Gateways"
  - [ ] Verificar layout da tabela
  - [ ] Verificar informações de cada gateway

- [ ] **Configuração**
  - [ ] Acessar configuração de um gateway
  - [ ] Verificar layout do formulário
  - [ ] Alterar configurações (se aplicável)
  - [ ] Salvar alterações

### Newsletter

- [ ] **Listagem**
  - [ ] Acessar "Newsletter"
  - [ ] Verificar layout da tabela
  - [ ] Verificar e-mails cadastrados
  - [ ] Testar exportação (se houver)

---

## 📱 Responsividade

### Storefront

- [ ] **Home**
  - [ ] Verificar layout em desktop (1920px, 1366px)
  - [ ] Verificar layout em tablet (768px)
  - [ ] Verificar layout em mobile (375px, 414px)
  - [ ] Verificar scroll horizontal de categorias no mobile
  - [ ] Verificar hero slider responsivo

- [ ] **Listagem de Produtos**
  - [ ] Verificar grid responsivo (desktop/tablet/mobile)
  - [ ] Verificar filtros em mobile
  - [ ] Verificar cards de produtos

- [ ] **PDP (Página de Produto)**
  - [ ] Verificar galeria de imagens responsiva
  - [ ] Verificar miniaturas em mobile
  - [ ] Verificar formulário de avaliação em mobile

- [ ] **Carrinho**
  - [ ] Verificar tabela em mobile (scroll horizontal ou cards)
  - [ ] Verificar resumo do carrinho

- [ ] **Checkout**
  - [ ] Verificar formulários em mobile (1 coluna)
  - [ ] Verificar resumo do pedido

- [ ] **Área do Cliente**
  - [ ] Verificar menu lateral em mobile (tabs ou colapsável)
  - [ ] Verificar tabelas em mobile
  - [ ] Verificar formulários em mobile

### Admin

- [ ] **Layout Geral**
  - [ ] Verificar sidebar em mobile (tabs ou colapsável)
  - [ ] Verificar header responsivo

- [ ] **Tabelas**
  - [ ] Verificar scroll horizontal em mobile
  - [ ] Verificar filtros em mobile

- [ ] **Formulários**
  - [ ] Verificar grid de 2 colunas em mobile (1 coluna)
  - [ ] Verificar inputs e selects responsivos

---

## 🔒 Segurança Básica

- [ ] **Rotas Protegidas**
  - [ ] Tentar acessar `/admin` sem login (deve redirecionar)
  - [ ] Tentar acessar `/admin/produtos` sem login (deve redirecionar)
  - [ ] Tentar acessar `/minha-conta` sem login (deve redirecionar)
  - [ ] Verificar que rotas públicas (home, produtos, PDP) são acessíveis sem login

- [ ] **Multi-tenant**
  - [ ] Fazer login como admin de um tenant
  - [ ] Verificar que só vê produtos/pedidos do próprio tenant
  - [ ] Tentar acessar dados de outro tenant (deve ser bloqueado)

---

## ⚡ Performance Básica

- [ ] **Carregamento de Páginas**
  - [ ] Verificar tempo de carregamento da home (< 3s)
  - [ ] Verificar tempo de carregamento da listagem de produtos (< 2s)
  - [ ] Verificar tempo de carregamento da PDP (< 2s)
  - [ ] Verificar tempo de carregamento do admin (< 2s)

- [ ] **Imagens**
  - [ ] Verificar que imagens têm placeholder enquanto carregam
  - [ ] Verificar que imagens não quebram o layout

---

## ✅ Critérios de Aceitação

### Layout

- [ ] Todos os textos estão em PT-BR comercial
- [ ] Ícones são consistentes (Bootstrap Icons, mesma cor)
- [ ] Botões têm hover states e feedback visual
- [ ] Mensagens de sucesso/erro são claras e visíveis
- [ ] Formulários têm labels e placeholders em PT-BR
- [ ] Tabelas têm cabeçalho destacado e linhas alternadas (hover)

### Funcionalidade

- [ ] Todos os fluxos principais funcionam sem erros
- [ ] Validações de formulário funcionam corretamente
- [ ] Paginação funciona em todas as listagens
- [ ] Filtros funcionam corretamente
- [ ] Multi-tenant está funcionando corretamente

### Responsividade

- [ ] Layout funciona bem em desktop (1920px, 1366px)
- [ ] Layout funciona bem em tablet (768px)
- [ ] Layout funciona bem em mobile (375px, 414px)
- [ ] Não há scroll horizontal indesejado
- [ ] Textos são legíveis em todas as resoluções

---

## 📝 Observações

_Use este espaço para anotar problemas encontrados durante os testes:_

- 
- 
- 

---

## 🎯 Próximos Passos

Após completar este checklist:

1. Corrigir bugs críticos encontrados
2. Ajustar textos e labels conforme necessário
3. Melhorar performance se necessário
4. Atualizar documentação com observações
5. Marcar Fase 10 como concluída em `STATUS_PROJETO_COMPLETO.md`

---

**Última atualização:** 06/12/2025
