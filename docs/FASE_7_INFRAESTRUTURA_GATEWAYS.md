# Fase 7: Infraestrutura Neutra de Gateways (Pagamento + Frete)

## 📋 Resumo

Implementação de uma arquitetura genérica e extensível para integração com gateways de pagamento e frete, sem acoplar o projeto a nenhum provedor específico.

**Status:** ✅ Concluída  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

Criar uma infraestrutura que permita:
- Integrar qualquer gateway de pagamento sem modificar código principal
- Integrar qualquer provedor de frete sem modificar código principal
- Configurar gateways por tenant via painel admin
- Armazenar credenciais de forma genérica (JSON)
- Manter compatibilidade com implementações atuais (manual/simples)

---

## 📦 Estrutura de Dados

### Tabela: `tenant_gateways`

**Migration:** `035_create_tenant_gateways_table.php`

**Campos:**
- `id` - PK
- `tenant_id` - FK para tenants
- `tipo` - ENUM('payment', 'shipping')
- `codigo` - VARCHAR(50) - Código do provider (ex: 'manual', 'mercadopago', 'simples', 'melhorenvio')
- `config_json` - JSON - Credenciais e configurações específicas do provider
- `ativo` - TINYINT(1) - Se o gateway está ativo
- `created_at`, `updated_at`

**Índices:**
- UNIQUE (tenant_id, tipo) - Um provider ativo por tipo por tenant
- INDEX (tenant_id, tipo, codigo)

**Dados iniciais:**
- Para cada tenant existente, cria:
  - `tipo='payment'`, `codigo='manual'`
  - `tipo='shipping'`, `codigo='simples'`

---

## 🔧 Implementação

### 1. Interfaces

#### PaymentProviderInterface

**Arquivo:** `src/Services/Payment/PaymentProviderInterface.php`

**Método:**
```php
public function createPayment(
    array $pedido, 
    array $cliente, 
    string $metodoEscolhido, 
    array $config = []
): PaymentResult;
```

#### ShippingProviderInterface

**Arquivo:** `src/Services/Shipping/ShippingProviderInterface.php`

**Método:**
```php
public function calcularOpcoesFrete(
    array $pedido, 
    array $endereco, 
    array $config = []
): array;
```

### 2. DTOs

#### PaymentResult

**Arquivo:** `src/Services/Payment/PaymentResult.php`

**Propriedades:**
- `codigoTransacao` (string|null) - ID da transação no gateway
- `statusInicial` (string) - Status inicial ('pending', 'paid', etc.)
- `dadosExibicao` (array) - Dados para exibir na tela de confirmação

### 3. Providers Padrão

#### ManualPaymentProvider

**Arquivo:** `src/Services/Payment/Providers/ManualPaymentProvider.php`

**Funcionalidade:**
- Não chama API externa
- Gera código de transação simples: `manual-{numero_pedido}`
- Status inicial: `pending`
- Dados de exibição: mensagem de instruções (pode vir do config_json)

#### SimpleShippingProvider

**Arquivo:** `src/Services/Shipping/Providers/SimpleShippingProvider.php`

**Funcionalidade:**
- Implementa regra de frete simples (já existente na Fase 4)
- Frete grátis acima de valor configurável (padrão: R$ 299)
- Valores diferentes por região (Sudeste vs outras)
- Configurável via config_json

### 4. Services Refatorados

#### PaymentService

**Arquivo:** `src/Services/Payment/PaymentService.php`

**Mudanças:**
- Movido de `App\Services` para `App\Services\Payment`
- Método `processarPagamento()` agora retorna `PaymentResult` (não array)
- Busca provider configurado em `tenant_gateways`
- Instancia provider dinamicamente baseado no `codigo`
- Passa `config_json` decodificado para o provider

**Métodos:**
- `listarMetodosDisponiveis($tenantId)` - Mantido para compatibilidade
- `processarPagamento($metodoEscolhido, $pedido, $cliente)` - Novo formato
- `getInstrucoes($metodo)` - Mantido para compatibilidade
- `getProvider($tenantId)` - Privado, resolve qual provider usar
- `getGatewayConfig($tenantId, $tipo)` - Privado, busca do banco
- `getProviderConfig($tenantId, $tipo)` - Privado, decodifica JSON

#### ShippingService

**Arquivo:** `src/Services/Shipping/ShippingService.php`

**Mudanças:**
- Movido de `App\Services` para `App\Services\Shipping`
- Método `calcularFrete()` agora usa provider configurado
- Busca provider em `tenant_gateways`
- Instancia provider dinamicamente
- Passa `config_json` decodificado para o provider

**Métodos:**
- `calcularFrete($tenantId, $cep, $subtotal, $itens)` - Mantido, agora usa provider
- `getValorFrete($codigoFrete, ...)` - Mantido
- `getProvider($tenantId)` - Privado, resolve qual provider usar
- `getGatewayConfig($tenantId, $tipo)` - Privado
- `getProviderConfig($tenantId, $tipo)` - Privado

### 5. Controller Admin

#### GatewayConfigController

**Arquivo:** `src/Http/Controllers/Admin/GatewayConfigController.php`

**Métodos:**
- `index()` - Exibe formulário de configuração
- `store()` - Salva configurações (INSERT/UPDATE em tenant_gateways)

**Rotas:**
- `GET /admin/configuracoes/gateways` - Exibir configurações
- `POST /admin/configuracoes/gateways` - Salvar configurações

### 6. View Admin

**Arquivo:** `themes/default/admin/gateways/index-content.php`

**Funcionalidades:**
- Formulário com dois blocos: Pagamento e Frete
- Dropdown para selecionar provider
- Textarea para config_json (formato JSON)
- Validação de JSON antes de salvar
- Mensagens de sucesso/erro

### 7. Ajustes no Checkout

**Arquivo:** `src/Http/Controllers/Storefront/CheckoutController.php`

**Mudanças:**
- Imports atualizados para novos namespaces
- `processarPagamento()` agora recebe `PaymentResult`
- Atualiza pedido com `codigo_transacao` e `status` do `PaymentResult`
- Mantém compatibilidade com fluxo existente

### 8. Menu Admin

**Arquivo:** `themes/default/admin/layouts/store.php`

**Mudanças:**
- Adicionado link "Integrações / Gateways" no menu lateral
- Ícone: `bi-plug`

---

## 📝 Documentação

### GATEWAYS_INTEGRACAO.md

**Arquivo:** `docs/GATEWAYS_INTEGRACAO.md`

**Conteúdo:**
- Explicação da arquitetura
- Como integrar novo gateway de pagamento (passo a passo)
- Como integrar novo gateway de frete (passo a passo)
- Exemplo completo (FakePayProvider)
- Estrutura de dados
- Segurança e boas práticas
- Troubleshooting

---

## ✅ Checklist de Aceite

- [x] Migration `035_create_tenant_gateways_table.php` criada
- [x] Interfaces `PaymentProviderInterface` e `ShippingProviderInterface` criadas
- [x] DTO `PaymentResult` criado
- [x] Providers padrão implementados (ManualPaymentProvider, SimpleShippingProvider)
- [x] PaymentService refatorado para usar providers
- [x] ShippingService refatorado para usar providers
- [x] GatewayConfigController criado
- [x] View admin de configuração criada
- [x] Rotas registradas
- [x] Link no menu lateral adicionado
- [x] CheckoutController atualizado para usar novos services
- [x] Compatibilidade mantida (não quebrou fluxo existente)
- [x] Documentação completa criada

---

## 🔄 Compatibilidade

### Funcionalidades Mantidas

- ✅ Checkout continua funcionando normalmente
- ✅ Pagamento manual/PIX funciona como antes
- ✅ Frete simples funciona como antes
- ✅ Pedidos são criados normalmente
- ✅ Tela de confirmação funciona normalmente

### Mudanças Transparentes

- Services movidos para subnamespaces (compatibilidade mantida via imports)
- Lógica de frete migrada para SimpleShippingProvider
- Lógica de pagamento migrada para ManualPaymentProvider

---

## 📊 Estrutura de Arquivos Criados/Modificados

```
database/migrations/
└── 035_create_tenant_gateways_table.php (NOVO)

src/Services/Payment/
├── PaymentProviderInterface.php (NOVO)
├── PaymentResult.php (NOVO)
├── PaymentService.php (NOVO - refatorado)
└── Providers/
    └── ManualPaymentProvider.php (NOVO)

src/Services/Shipping/
├── ShippingProviderInterface.php (NOVO)
├── ShippingService.php (NOVO - refatorado)
└── Providers/
    └── SimpleShippingProvider.php (NOVO)

src/Http/Controllers/Admin/
└── GatewayConfigController.php (NOVO)

themes/default/admin/gateways/
└── index-content.php (NOVO)

src/Http/Controllers/Storefront/
├── CheckoutController.php (MODIFICADO - imports e uso de PaymentResult)
└── OrderController.php (MODIFICADO - import)

themes/default/admin/layouts/
└── store.php (MODIFICADO - link no menu)

public/index.php (MODIFICADO - rotas)

docs/
└── GATEWAYS_INTEGRACAO.md (NOVO)
```

**Arquivos Removidos:**
- `src/Services/PaymentService.php` (substituído por `src/Services/Payment/PaymentService.php`)
- `src/Services/ShippingService.php` (substituído por `src/Services/Shipping/ShippingService.php`)

---

## 🚀 Como Usar

### Configurar Gateway no Admin

1. Acesse `/admin/configuracoes/gateways`
2. Selecione o provider desejado (por enquanto apenas "Manual" e "Simples")
3. Opcionalmente, configure JSON personalizado:

**Para Pagamento Manual:**
```json
{
    "mensagem_instrucoes": "Sua mensagem personalizada aqui",
    "instrucoes": "Texto adicional de instruções"
}
```

**Para Frete Simples:**
```json
{
    "limite_frete_gratis": 299.00,
    "frete_sudeste": 19.90,
    "frete_outras_regioes": 29.90,
    "prazo_sudeste": "5 a 8 dias úteis",
    "prazo_outras": "7 a 10 dias úteis"
}
```

4. Clique em "Salvar Configurações"

### Integrar Novo Gateway

Siga o guia completo em `docs/GATEWAYS_INTEGRACAO.md`.

**Resumo rápido:**
1. Criar classe do provider implementando a interface
2. Registrar no array `$providers` do Service
3. Adicionar opção no dropdown da view (opcional)
4. Configurar credenciais no admin

---

## 🔒 Segurança

### Validações Implementadas

1. **JSON Validation:**
   - Validação de JSON antes de salvar
   - Erro amigável se JSON inválido

2. **Multi-tenant:**
   - Todas as queries filtram por `tenant_id`
   - Cada tenant tem suas próprias configurações

3. **Isolamento de Credenciais:**
   - Credenciais armazenadas em JSON por tenant
   - Não expostas em logs ou mensagens de erro

---

## 🐛 Troubleshooting

### Problema: Provider não encontrado

**Solução:**
1. Verificar se classe existe e namespace está correto
2. Verificar se está registrado no array `$providers`
3. Verificar se código no banco corresponde ao código no array

### Problema: JSON inválido

**Solução:**
1. Validar JSON em validador online antes de colar
2. Verificar vírgulas e aspas
3. Usar `json_decode` para validar antes de salvar

### Problema: Frete não calcula corretamente

**Solução:**
1. Verificar se `config_json` está sendo lido corretamente
2. Verificar se provider está usando `$config` no método
3. Verificar logs de erro

---

## 📚 Referências

- **Documentação de Integração:** `docs/GATEWAYS_INTEGRACAO.md`
- **Interfaces:** `src/Services/Payment/PaymentProviderInterface.php`, `src/Services/Shipping/ShippingProviderInterface.php`
- **Providers de Exemplo:** `src/Services/Payment/Providers/ManualPaymentProvider.php`, `src/Services/Shipping/Providers/SimpleShippingProvider.php`
- **Migration:** `database/migrations/035_create_tenant_gateways_table.php`

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX


