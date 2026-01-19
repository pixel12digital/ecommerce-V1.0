# Diagnóstico: Integração com Correios

## 📋 Resumo Executivo

Este documento mapeia o estado atual do sistema de frete e define os próximos passos para integração com os Correios. O objetivo é entender o que já existe antes de implementar a integração.

**Data:** Janeiro 2025  
**Versão do Sistema:** ecommerce-v1.0  
**Status:** ✅ Diagnóstico Completo

---

## PARTE 1 — DIAGNÓSTICO DO QUE JÁ EXISTE

### 1. Métodos de Envio Atuais

#### Onde estão definidos?

**Arquivo:** `src/Services/Shipping/ShippingService.php`

O sistema utiliza uma arquitetura baseada em **providers** (provedores), seguindo o padrão Strategy:

- **Interface:** `ShippingProviderInterface` (`src/Services/Shipping/ShippingProviderInterface.php`)
- **Provider Atual:** `SimpleShippingProvider` (`src/Services/Shipping/Providers/SimpleShippingProvider.php`)
- **Configuração:** Armazenada em `tenant_gateways` (tipo='shipping')

#### Existe tabela/model para métodos de frete?

**✅ SIM** — Tabela `tenant_gateways`

**Estrutura:**
```sql
CREATE TABLE tenant_gateways (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('payment', 'shipping') NOT NULL,
    codigo VARCHAR(50) NOT NULL,           -- 'simples', 'correios', 'melhorenvio'
    config_json JSON NULL,                  -- Configurações específicas (CEP origem, etc.)
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    UNIQUE KEY unique_tenant_tipo (tenant_id, tipo)
);
```

**Status:**
- ✅ Tabela existe e está funcionando
- ✅ Suporta múltiplos providers (código configurável)
- ✅ Configurações por tenant (multi-tenant)
- ✅ Configurações em JSON (flexível para cada provider)

#### Existe tabela/model para transportadoras?

**❌ NÃO** — Não há tabela específica para transportadoras. A lógica está nos providers:

- Cada provider representa uma transportadora ou método
- Exemplos: `simples`, `correios`, `melhorenvio`
- Registrados no array de providers do `ShippingService`

#### Existem regras por tenant?

**✅ SIM** — Cada tenant configura seu próprio gateway de frete:

- Um gateway de frete por tenant (único por `tipo='shipping'`)
- Configurações específicas em `config_json`
- Exemplo de `config_json` para `SimpleShippingProvider`:
```json
{
    "limite_frete_gratis": 299.00,
    "frete_sudeste": 19.90,
    "frete_outras_regioes": 29.90,
    "prazo_sudeste": "5 a 8 dias úteis",
    "prazo_outras": "7 a 10 dias úteis"
}
```

### 2. Sistema Atual de Frete

#### Hoje o sistema usa:

**✅ Valor fixo por região** — Implementado em `SimpleShippingProvider`:
- Frete Sudeste: R$ 19,90 (padrão, configurável)
- Frete Outras Regiões: R$ 29,90 (padrão, configurável)
- Determinação da região baseada nos primeiros dígitos do CEP

**✅ Frete grátis** — Acima de valor configurável (padrão: R$ 299,00):
- Verificado automaticamente se `subtotal >= limite_frete_gratis`
- Opção exibida no checkout quando disponível

**❌ Retirada** — Não implementado

**❌ Cálculo por peso/dimensões** — Infraestrutura existe, mas não é usada:
- `SimpleShippingProvider` não utiliza peso/dimensões no cálculo
- O `ShippingService` já busca essas informações dos produtos
- Mas o provider atual ignora esses dados

### 3. Momento do Cálculo

#### Em que etapa o frete é calculado?

**✅ No checkout (página de finalização)** — `CheckoutController::index()`

**Fluxo:**
1. Cliente acessa `/checkout`
2. Sistema busca opções de frete via `ShippingService::calcularFrete()`
3. Opções são exibidas para o cliente escolher
4. Ao finalizar, valor do frete selecionado é salvo no pedido

**❌ Não há cálculo em tempo real via AJAX:**
- Cálculo ocorre apenas no carregamento da página
- Se CEP for alterado, só recalcula após submit (com erro de validação)
- Não existe endpoint REST/API para recalcular frete dinamicamente

**Arquivos envolvidos:**
- `src/Http/Controllers/Storefront/CheckoutController.php` (linhas 33-35, 144, 194, 434)
- `themes/default/storefront/checkout/index.php` (exibição das opções)

#### O cálculo hoje depende de:

**✅ CEP do cliente** — Passado para `calcularFrete($tenantId, $cep, ...)`

**✅ Subtotal do carrinho** — Usado para verificar frete grátis e para passar ao provider

**⚠️ Peso/dimensões do produto** — **PARCIAL:**
- `ShippingService` já busca peso, comprimento, largura, altura dos produtos
- Mas `SimpleShippingProvider` não utiliza esses dados no cálculo
- Dados são passados ao provider, mas não são processados

**Código relevante:**
```php
// ShippingService::calcularFrete() - linha 20-38
$itensComDimensoes = self::enriquecerItensComDimensoes($tenantId, $itens);
// Retorna itens com: produto_id, quantidade, preco_unitario, peso, comprimento, largura, altura

// SimpleShippingProvider::calcularOpcoesFrete() - linha 9-45
// Ignora os dados de peso/dimensões, usa apenas $subtotal e $cep
```

### 4. Dados Disponíveis

#### O que já existe no banco?

**✅ CEP de destino (cliente):**
- Capturado no formulário de checkout
- Salvo em `pedidos.entrega_cep`
- Passado para `ShippingService::calcularFrete()`

**✅ Peso do produto:**
- Campo `produtos.peso` (DECIMAL 8,2) - em kg
- Buscado pelo `ShippingService::enriquecerItensComDimensoes()`

**✅ Dimensões do produto:**
- `produtos.comprimento` (DECIMAL 8,2) - em cm
- `produtos.largura` (DECIMAL 8,2) - em cm
- `produtos.altura` (DECIMAL 8,2) - em cm
- Todos buscados pelo `ShippingService`

**✅ Quantidade por item:**
- Armazenada no carrinho (`CartService`)
- Passada junto com os itens para cálculo de frete

**❌ CEP de origem (loja/tenant):**
- **NÃO existe campo específico na tabela `tenants`**
- **NÃO está configurado em `tenant_settings`**
- Pode ser armazenado em `tenant_gateways.config_json` quando provider for 'correios'

**✅ Peso total do carrinho:**
- Calculável: soma de `produto.peso * quantidade` para cada item
- **Não é calculado ainda**, mas dados estão disponíveis

#### O que existe no código mas não é usado?

**⚠️ Dimensões e peso dos produtos:**
- Campos existem no banco ✅
- `ShippingService` busca esses dados ✅
- Passa para o provider ✅
- **MAS `SimpleShippingProvider` ignora** ❌

**⚠️ Estrutura pronta para novos providers:**
- Interface `ShippingProviderInterface` existe ✅
- Array de providers em `ShippingService::getProvider()` tem comentário: `// Futuro: 'correios' => CorreiosProvider::class` ✅
- Mas `CorreiosProvider` ainda não existe ❌

### 5. Dados Salvos no Pedido

**Tabela:** `pedidos` (`database/migrations/031_create_pedidos_table.php`)

**Campos relacionados a frete:**
- ✅ `total_frete` (DECIMAL 10,2) - Valor do frete escolhido
- ✅ `metodo_frete` (VARCHAR 50) - Código do método escolhido (ex: 'frete_simples', 'frete_gratis')
- ✅ `entrega_cep` (VARCHAR 20) - CEP de entrega
- ✅ `entrega_logradouro`, `entrega_numero`, `entrega_complemento`, `entrega_bairro`, `entrega_cidade`, `entrega_estado` - Endereço completo

**❌ Faltando campos úteis:**
- Prazo estimado de entrega (string) - Não é salvo, apenas exibido no checkout
- CEP de origem usado no cálculo - Não é salvo
- Peso total do pedido - Não é salvo
- Dimensões totais - Não são salvas

---

## PARTE 2 — INTEGRAÇÃO COM CORREIOS (DIREÇÃO TÉCNICA)

### 1. Modelo de Integração Sugerido

#### Opções de Integração com Correios

**Opção A: API Calculador Remoto (SIGEPWeb) — ⚠️ RECOMENDADO COM RESSALVAS**

**Características:**
- API oficial dos Correios
- Requer contrato empresarial com código de acesso (usuario + senha)
- Suporta PAC, SEDEX, SEDEX 10, SEDEX 12
- Retorna valor e prazo de entrega
- Documentação: https://www.correios.com.br/enviar/precisa-de-ajuda/calculador-remoto-de-precos-e-prazos

**Vantagens:**
- ✅ Oficial (dados diretos dos Correios)
- ✅ Gratuito após contrato
- ✅ Sem intermediários

**Desvantagens:**
- ❌ Requer contrato comercial com Correios
- ❌ Processo burocrático de cadastro
- ❌ Pode não ser viável para MVP

**Opção B: API Melhor Envio — ✅ RECOMENDADO PARA MVP**

**Características:**
- Serviço agregador (integra múltiplas transportadoras, incluindo Correios)
- API simples e bem documentada
- Suporta Correios (PAC, SEDEX), além de outras transportadoras
- Retorna valor e prazo
- Documentação: https://melhorenvio.com.br/documentacao

**Vantagens:**
- ✅ Implementação rápida
- ✅ Não requer contrato direto com Correios
- ✅ API moderna e bem documentada
- ✅ Suporta outras transportadoras (flexibilidade futura)

**Desvantagens:**
- ⚠️ Intermediário (custo adicional pode existir)
- ⚠️ Dependência de terceiro

**Opção C: Tabela Própria / Cálculo Manual — ❌ NÃO RECOMENDADO**

**Características:**
- Criar tabela com faixas de CEP e valores
- Calcular manualmente baseado em peso e distância
- Manter atualizado manualmente

**Vantagens:**
- ✅ Sem dependências externas
- ✅ Controle total

**Desvantagens:**
- ❌ Extremamente trabalhoso manter atualizado
- ❌ Impreciso (valores mudam frequentemente)
- ❌ Não considera todas as variáveis (prazo real, etc.)
- ❌ Alto risco de desatualização

#### Recomendação

**Para MVP: Opção B (Melhor Envio)**
- Implementação mais rápida
- API moderna e bem estruturada
- Permite evoluir para outros providers depois

**Para produção (futuro): Avaliar Opção A (SIGEPWeb)**
- Se o volume justificar o contrato com Correios
- Se a equipe tiver recursos para o processo burocrático
- Pode coexistir com Melhor Envio (cliente escolhe)

**Observação:** A arquitetura atual já suporta múltiplos providers, então é possível implementar ambos e deixar o tenant escolher qual usar.

### 2. Serviços Mínimos Necessários

#### Para MVP com Correios (via Melhor Envio ou SIGEPWeb):

**✅ PAC (Serviço Postal)**
- Tipo: `PAC`
- Prazo: 8-15 dias úteis (varia)
- Mais econômico

**✅ SEDEX (Expresso)**
- Tipo: `SEDEX`
- Prazo: 1-3 dias úteis
- Mais rápido e caro

**⏭️ SEDEX 10 / SEDEX 12 (Futuro)**
- Para entrega no mesmo dia ou no dia seguinte
- Disponível apenas em algumas localidades
- Pode ser adicionado depois do MVP

**Estrutura de retorno esperada:**
```php
[
    [
        'codigo' => 'correios_pac',
        'titulo' => 'PAC',
        'valor' => 25.50,
        'prazo' => '10 a 15 dias úteis',
        'descricao' => 'Entrega em domicílio'
    ],
    [
        'codigo' => 'correios_sedex',
        'titulo' => 'SEDEX',
        'valor' => 42.90,
        'prazo' => '1 a 3 dias úteis',
        'descricao' => 'Entrega expressa em domicílio'
    ]
]
```

### 3. Arquitetura Sugerida

#### Onde ficaria o serviço de cálculo?

**✅ JÁ EXISTE:** `src/Services/Shipping/ShippingService.php`

**Estrutura atual:**
```
src/Services/Shipping/
├── ShippingService.php              ← Serviço principal (JÁ EXISTE)
├── ShippingProviderInterface.php    ← Interface (JÁ EXISTE)
└── Providers/
    ├── SimpleShippingProvider.php   ← Provider atual (JÁ EXISTE)
    └── CorreiosProvider.php         ← A CRIAR
```

**Como funciona:**
1. `ShippingService::calcularFrete()` busca provider configurado em `tenant_gateways`
2. Instancia o provider (ex: `CorreiosProvider`)
3. Passa dados do pedido e endereço
4. Provider retorna array de opções

**Nenhuma mudança necessária no `ShippingService`** — apenas adicionar novo provider.

#### Como o frontend chamaria esse cálculo?

**✅ JÁ FUNCIONA (sem AJAX):**
- `CheckoutController::index()` já chama `ShippingService::calcularFrete()`
- Opções são exibidas no template

**⚠️ MELHORIA NECESSÁRIA (AJAX para recalcular):**
- Criar endpoint: `POST /api/checkout/calcular-frete` ou `GET /api/shipping/calculate`
- Frontend chama via JavaScript quando CEP mudar
- Retorna JSON com opções de frete
- Atualiza interface sem recarregar página

**Implementação sugerida:**
```javascript
// No checkout/index.php
document.getElementById('entrega_cep').addEventListener('blur', async function() {
    const cep = this.value;
    if (cep.length === 8 || cep.length === 9) {
        const response = await fetch(`/api/checkout/calcular-frete?cep=${cep}`);
        const opcoes = await response.json();
        // Atualizar interface com novas opções
    }
});
```

#### Como salvar a opção de frete escolhida no pedido?

**✅ JÁ FUNCIONA:**
- Campo `metodo_frete` salva o código (ex: 'correios_pac', 'correios_sedex')
- Campo `total_frete` salva o valor
- Processado em `CheckoutController::process()` (linhas 349, 335)

**⏭️ MELHORIA OPCIONAL (futuro):**
- Salvar prazo estimado (adicionar campo `prazo_entrega` na tabela `pedidos`)
- Salvar detalhes do frete em JSON (para rastreamento futuro)

---

## PARTE 3 — ESCOPO DA PRIMEIRA VERSÃO (MVP)

### Escopo Enxuto (Sem Complexidade Excessiva)

#### ✅ Funcionalidades do MVP

**1. Configuração:**
- Campo "CEP de origem" no `config_json` do gateway Correios
- Configurado via painel admin (`/admin/configuracoes/gateways`)

**2. Cálculo de frete via Correios:**
- Usando Melhor Envio ou SIGEPWeb (dependendo da viabilidade)
- CEP origem: do `config_json` do tenant
- CEP destino: do formulário de checkout
- Peso total: soma de `produto.peso * quantidade`
- Dimensões: calcular cubagem total ou maior dimensão (simplificado)

**3. Exibir opções no checkout:**
- Lista de serviços (PAC, SEDEX)
- Valor de cada serviço
- Prazo estimado de cada serviço

**4. Salvar no pedido:**
- Tipo de frete escolhido (`metodo_frete` = 'correios_pac' ou 'correios_sedex')
- Valor do frete (`total_frete`)
- CEP de destino já é salvo

#### ❌ Fora do Escopo do MVP

- Múltiplas transportadoras simultâneas (apenas Correios)
- Regras complexas (exceções por categoria, peso mínimo/máximo)
- Cálculo de múltiplos volumes (simplificar para um único pacote)
- Retirada em loja
- SEDEX 10/12 (apenas PAC e SEDEX)
- Cálculo em tempo real via AJAX (opcional, pode ser v2)

---

## PARTE 4 — ENTREGÁVEIS ESPERADOS

### Resumo do que já existe

#### ✅ O que está pronto

**1. Infraestrutura de Frete:**
- ✅ `ShippingService` implementado e funcionando
- ✅ Interface `ShippingProviderInterface` definida
- ✅ Provider `SimpleShippingProvider` funcional
- ✅ Integração com checkout funcionando
- ✅ Salvamento de frete no pedido funcionando

**2. Dados de Produto:**
- ✅ Campos peso, comprimento, largura, altura no banco
- ✅ `ShippingService` já busca essas informações
- ✅ Dados são passados aos providers (estrutura pronta)

**3. Dados de Endereço:**
- ✅ CEP de destino capturado no checkout
- ✅ Endereço completo salvo no pedido
- ✅ Endereços salvos para clientes logados

**4. Configuração Multi-tenant:**
- ✅ Tabela `tenant_gateways` criada
- ✅ Suporte a configurações por tenant
- ✅ Painel admin para configurar gateways

#### ⚠️ O que está parcialmente pronto

**1. Uso de Dimensões/Peso:**
- ⚠️ Dados existem e são buscados ✅
- ⚠️ Mas `SimpleShippingProvider` não usa ❌
- ⚠️ Estrutura está pronta para `CorreiosProvider` usar ✅

**2. CEP de Origem:**
- ⚠️ Não está configurado ainda ❌
- ⚠️ Mas pode ser adicionado em `config_json` do gateway ✅

**3. Cálculo em Tempo Real:**
- ⚠️ Calcula apenas no carregamento da página ✅
- ⚠️ Não recalcula via AJAX quando CEP muda ❌
- ⚠️ Mas estrutura do controller permite criar endpoint ✅

#### ❌ O que não existe

**1. Provider dos Correios:**
- ❌ Classe `CorreiosProvider` não existe
- ❌ Integração com API dos Correios não existe
- ❌ Cálculo de peso total e dimensões totais não é feito

**2. CEP de Origem Configurável:**
- ❌ Campo não está em lugar nenhum (nem `tenants`, nem `tenant_settings`)
- ❌ Não é capturado no admin de gateways
- ❌ Precisará ser adicionado ao `config_json`

**3. Cálculo de Peso/Dimensões Totais:**
- ❌ `ShippingService` busca dados por produto ✅
- ❌ Mas não calcula peso total do carrinho ❌
- ❌ Não calcula dimensões totais (cubagem) ❌

**4. Endpoint AJAX para Cálculo:**
- ❌ Não existe rota `/api/checkout/calcular-frete` ou similar
- ❌ Não existe controller/método dedicado para isso

### Lista clara do que precisa ser implementado

#### Backend

**1. Provider Correios:**
- [ ] Criar `src/Services/Shipping/Providers/CorreiosProvider.php`
- [ ] Implementar `ShippingProviderInterface`
- [ ] Integrar com API (Melhor Envio ou SIGEPWeb)
- [ ] Calcular peso total do carrinho
- [ ] Calcular dimensões totais (ou maior dimensão)
- [ ] Retornar opções PAC e SEDEX

**2. Registro do Provider:**
- [ ] Adicionar 'correios' no array de providers em `ShippingService::getProvider()`
- [ ] Remover comentário de "Futuro"

**3. Métodos auxiliares (se necessário):**
- [ ] Criar método `calcularPesoTotal()` em `ShippingService`
- [ ] Criar método `calcularDimensoesTotais()` em `ShippingService`
- [ ] Ou integrar no `CorreiosProvider`

**4. Endpoint AJAX (opcional, v1.1):**
- [ ] Criar rota `/api/checkout/calcular-frete` ou similar
- [ ] Criar método em `CheckoutController` ou controller separado
- [ ] Retornar JSON com opções de frete

#### Frontend

**1. Painel Admin - Configuração:**
- [ ] Adicionar campo "CEP de origem" no formulário de gateways
- [ ] Salvar CEP no `config_json` do gateway Correios
- [ ] Adicionar validação de CEP (8 dígitos)

**2. Checkout - Melhorias (opcional, v1.1):**
- [ ] JavaScript para recalcular frete quando CEP mudar (via AJAX)
- [ ] Loading indicator durante cálculo
- [ ] Tratamento de erros (CEP inválido, API indisponível)

**3. Exibição de Opções:**
- [ ] Já funciona ✅ (template já exibe `opcoesFrete`)
- [ ] Apenas garantir que códigos do Correios sejam reconhecidos

#### Banco de Dados

**1. Configuração:**
- [ ] Nenhuma migration necessária ✅
- [ ] CEP origem será armazenado em `tenant_gateways.config_json`

**2. Dados do Pedido (opcional):**
- [ ] Adicionar campo `prazo_entrega` em `pedidos` (migration futura)
- [ ] Adicionar campo `frete_detalhes` JSON (migration futura)

#### Configurações por Tenant

**1. Admin de Gateways:**
- [ ] Adicionar opção "Correios" no dropdown de providers
- [ ] Adicionar campo "CEP de origem" quando Correios for selecionado
- [ ] Adicionar campos de credenciais (se SIGEPWeb: usuario, senha)
- [ ] Validar JSON antes de salvar

**2. Configuração JSON esperada:**
```json
{
    "cep_origem": "01310-100",
    "usuario": "seu_usuario",
    "senha": "sua_senha",
    "codigo_servico": "40126,40096",
    "mao_propria": false,
    "valor_declarado": 0,
    "aviso_recebimento": false
}
```

### Proposta Técnica de Integração

#### Abordagem Escolhida

**Provider: Melhor Envio (recomendado para MVP)**

**Justificativa:**
- API moderna e bem documentada
- Implementação mais rápida
- Não requer contrato comercial com Correios
- Permite evoluir para outras transportadoras depois

**Alternativa (se contratar Correios):**
- SIGEPWeb (API oficial)
- Requer cadastro e contrato
- Pode ser implementado depois sem mudanças na arquitetura

#### Pontos de Integração no Código

**1. Criar Provider:**
```
src/Services/Shipping/Providers/CorreiosProvider.php
```

**2. Registrar Provider:**
```php
// src/Services/Shipping/ShippingService.php
private static function getProvider(int $tenantId): ShippingProviderInterface
{
    // ...
    $providers = [
        'simples' => SimpleShippingProvider::class,
        'correios' => CorreiosProvider::class,  // ← ADICIONAR
    ];
    // ...
}
```

**3. Configurar no Admin:**
```
themes/default/admin/gateways/index-content.php
```
- Adicionar "Correios" no dropdown
- Exibir campos específicos (CEP origem, credenciais)

**4. Salvar Configuração:**
- Já funciona via `GatewayConfigController` ✅
- Apenas garantir que `config_json` seja salvo corretamente

#### Risco ou Dependências Externas

**Riscos:**

1. **API Externa:**
   - Melhor Envio ou SIGEPWeb podem estar indisponíveis
   - **Mitigação:** Tratamento de erro, fallback para `SimpleShippingProvider`

2. **Credenciais:**
   - Requer cadastro em Melhor Envio ou contrato com Correios
   - **Mitigação:** Documentar processo de cadastro

3. **Latência:**
   - Chamadas HTTP podem ser lentas
   - **Mitigação:** Cache de resultados (futuro), timeout configurável

4. **Dados Incompletos:**
   - Produtos sem peso/dimensões
   - **Mitigação:** Valores padrão, validação no cadastro de produtos

**Dependências:**

1. **Biblioteca HTTP:**
   - Usar `curl` nativo do PHP (já disponível) ou Guzzle (se necessário)
   - Verificar se `allow_url_fopen` está habilitado

2. **JSON:**
   - Já disponível no PHP ✅

3. **Extensões:**
   - Nenhuma adicional necessária ✅

---

## CONCLUSÃO

### Status Atual

**✅ Bom:** A infraestrutura está pronta. O sistema já tem:
- Arquitetura de providers funcional
- Dados de produtos (peso, dimensões) disponíveis
- Integração com checkout funcionando
- Configuração multi-tenant pronta

**⚠️ Atenção:** Algumas funcionalidades precisam ser completadas:
- Provider dos Correios precisa ser criado
- CEP de origem precisa ser configurável
- Cálculo de peso/dimensões totais precisa ser implementado

**❌ Faltando:** Funcionalidades que não existem:
- Integração com API dos Correios
- Cálculo em tempo real via AJAX (opcional)

### Próximos Passos Recomendados

1. **Decisão de API:**
   - Avaliar viabilidade de Melhor Envio vs SIGEPWeb
   - Criar conta de teste (se Melhor Envio)

2. **Implementação do Provider:**
   - Criar `CorreiosProvider.php`
   - Implementar cálculo de peso/dimensões totais
   - Integrar com API escolhida

3. **Configuração no Admin:**
   - Adicionar campos no formulário de gateways
   - Configurar CEP de origem do tenant

4. **Testes:**
   - Testar com diferentes CEPs
   - Testar com produtos sem dimensões
   - Testar tratamento de erros

5. **Melhorias Futuras (v1.1):**
   - Endpoint AJAX para cálculo em tempo real
   - Cache de resultados de frete
   - Salvamento de prazo no pedido

---

**Documento criado em:** Janeiro 2025  
**Autor:** Diagnóstico Automatizado  
**Versão:** 1.0
