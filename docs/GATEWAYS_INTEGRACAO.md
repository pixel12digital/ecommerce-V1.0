# Integração de Gateways (Pagamento e Frete)

## 📋 Resumo

Este documento explica como integrar novos gateways de pagamento e frete no sistema, seguindo a arquitetura neutra implementada na Fase 7.

**Status:** ✅ Implementado  
**Data:** 2025-01-XX  
**Versão:** 1.0

---

## 🎯 Objetivo

O sistema foi projetado para ser **neutro em relação a gateways**, permitindo que qualquer provedor de pagamento ou frete seja integrado sem modificar o código principal. A arquitetura baseia-se em **interfaces** e **providers** configuráveis por tenant.

---

## 🏗️ Arquitetura

### Estrutura de Diretórios

```
src/Services/
├── Payment/
│   ├── PaymentProviderInterface.php      # Interface para providers de pagamento
│   ├── PaymentResult.php                 # DTO de resultado do pagamento
│   ├── PaymentService.php                # Service que resolve qual provider usar
│   └── Providers/
│       ├── ManualPaymentProvider.php     # Provider padrão (manual/PIX)
│       └── [SeuProvider].php             # Seu novo provider aqui
│
└── Shipping/
    ├── ShippingProviderInterface.php     # Interface para providers de frete
    ├── ShippingService.php                # Service que resolve qual provider usar
    └── Providers/
        ├── SimpleShippingProvider.php     # Provider padrão (frete simples)
        └── [SeuProvider].php             # Seu novo provider aqui
```

### Tabela de Configuração

**Tabela: `tenant_gateways`**

Armazena a configuração de gateways por tenant:

```sql
CREATE TABLE tenant_gateways (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('payment', 'shipping') NOT NULL,
    codigo VARCHAR(50) NOT NULL,              -- Ex: 'manual', 'mercadopago', 'melhorenvio'
    config_json JSON NULL,                     -- Credenciais e configurações em JSON
    ativo TINYINT(1) DEFAULT 1,
    created_at, updated_at,
    UNIQUE KEY (tenant_id, tipo)              -- Um provider ativo por tipo por tenant
);
```

**Exemplo de registros:**

```sql
-- Gateway de pagamento
INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json) VALUES
(1, 'payment', 'mercadopago', '{"api_key": "APP_USR-...", "access_token": "..."}');

-- Gateway de frete
INSERT INTO tenant_gateways (tenant_id, tipo, codigo, config_json) VALUES
(1, 'shipping', 'melhorenvio', '{"token": "abc123", "email": "loja@exemplo.com"}');
```

---

## 🔌 Como Integrar um Novo Gateway de Pagamento

### Passo 1: Criar a Classe do Provider

Crie um arquivo em `src/Services/Payment/Providers/`:

**Exemplo: `MercadoPagoProvider.php`**

```php
<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\PaymentResult;

class MercadoPagoProvider implements PaymentProviderInterface
{
    public function createPayment(array $pedido, array $cliente, string $metodoEscolhido, array $config = []): PaymentResult
    {
        // 1. Ler credenciais do config
        $apiKey = $config['api_key'] ?? null;
        $accessToken = $config['access_token'] ?? null;
        
        if (empty($apiKey) || empty($accessToken)) {
            throw new \RuntimeException('Credenciais do Mercado Pago não configuradas');
        }

        // 2. Chamar API do Mercado Pago
        // (exemplo simplificado)
        $response = $this->chamarApiMercadoPago($pedido, $cliente, $metodoEscolhido, $apiKey, $accessToken);

        // 3. Retornar PaymentResult
        return new PaymentResult(
            codigoTransacao: $response['id'],           // ID da transação no gateway
            statusInicial: $this->mapearStatus($response['status']),  // 'pending', 'paid', etc.
            dadosExibicao: [
                'tipo' => 'mercadopago',
                'link_pagamento' => $response['init_point'] ?? null,
                'qr_code' => $response['qr_code'] ?? null,
                'instrucoes' => 'Escaneie o QR Code ou acesse o link para pagar',
            ]
        );
    }

    private function chamarApiMercadoPago(array $pedido, array $cliente, string $metodo, string $apiKey, string $accessToken): array
    {
        // Implementar chamada HTTP para API do Mercado Pago
        // Retornar resposta da API
        // ...
    }

    private function mapearStatus(string $statusMercadoPago): string
    {
        // Mapear status do gateway para status interno
        $map = [
            'pending' => 'pending',
            'approved' => 'paid',
            'rejected' => 'canceled',
        ];
        return $map[$statusMercadoPago] ?? 'pending';
    }
}
```

### Passo 2: Registrar o Provider no PaymentService

Edite `src/Services/Payment/PaymentService.php`:

```php
private static function getProvider(int $tenantId): PaymentProviderInterface
{
    $gateway = self::getGatewayConfig($tenantId, 'payment');
    $codigo = $gateway['codigo'] ?? 'manual';

    // Mapear código para classe do provider
    $providers = [
        'manual' => ManualPaymentProvider::class,
        'mercadopago' => MercadoPagoProvider::class,  // ← Adicionar aqui
        // Futuro: 'asaas' => AsaasProvider::class,
        // Futuro: 'pagarme' => PagarmeProvider::class,
    ];

    $providerClass = $providers[$codigo] ?? ManualPaymentProvider::class;
    // ...
}
```

### Passo 3: Configurar no Admin

1. Acesse `/admin/configuracoes/gateways`
2. Selecione o novo gateway no dropdown (você precisará adicionar a opção na view)
3. Cole as credenciais em formato JSON:

```json
{
    "api_key": "APP_USR-1234567890",
    "access_token": "APP_USR-0987654321",
    "public_key": "APP_USR-abcdefghij"
}
```

4. Salve

### Passo 4: Adicionar Opção no Dropdown (Opcional)

Edite `themes/default/admin/gateways/index-content.php`:

```php
<select name="payment_gateway_code">
    <option value="manual">Manual / PIX</option>
    <option value="mercadopago">Mercado Pago</option>  <!-- ← Adicionar -->
</select>
```

---

## 🚚 Como Integrar um Novo Gateway de Frete

### Passo 1: Criar a Classe do Provider

Crie um arquivo em `src/Services/Shipping/Providers/`:

**Exemplo: `MelhorEnvioProvider.php`**

```php
<?php

namespace App\Services\Shipping\Providers;

use App\Services\Shipping\ShippingProviderInterface;

class MelhorEnvioProvider implements ShippingProviderInterface
{
    public function calcularOpcoesFrete(array $pedido, array $endereco, array $config = []): array
    {
        // 1. Ler credenciais
        $token = $config['token'] ?? null;
        $email = $config['email'] ?? null;
        
        if (empty($token)) {
            throw new \RuntimeException('Token do Melhor Envio não configurado');
        }

        // 2. Preparar dados para API
        $dados = [
            'from' => [
                'postal_code' => $config['cep_origem'] ?? '01310-100',  // CEP da loja
            ],
            'to' => [
                'postal_code' => preg_replace('/\D/', '', $endereco['cep']),
            ],
            'products' => $this->prepararProdutos($pedido['itens']),
        ];

        // 3. Chamar API do Melhor Envio
        $opcoes = $this->chamarApiMelhorEnvio($dados, $token);

        // 4. Formatar resposta no padrão esperado
        $resultado = [];
        foreach ($opcoes as $opcao) {
            $resultado[] = [
                'codigo' => 'melhorenvio_' . $opcao['id'],
                'titulo' => $opcao['name'],
                'valor' => (float)$opcao['price'],
                'prazo' => $opcao['delivery_time'] . ' dias úteis',
            ];
        }

        return $resultado;
    }

    private function chamarApiMelhorEnvio(array $dados, string $token): array
    {
        // Implementar chamada HTTP para API do Melhor Envio
        // Retornar lista de opções de frete
        // ...
    }

    private function prepararProdutos(array $itens): array
    {
        // Converter itens do pedido para formato esperado pela API
        // ...
    }
}
```

### Passo 2: Registrar no ShippingService

Edite `src/Services/Shipping/ShippingService.php`:

```php
private static function getProvider(int $tenantId): ShippingProviderInterface
{
    // ...
    $providers = [
        'simples' => SimpleShippingProvider::class,
        'melhorenvio' => MelhorEnvioProvider::class,  // ← Adicionar
    ];
    // ...
}
```

### Passo 3: Configurar no Admin

1. Acesse `/admin/configuracoes/gateways`
2. Selecione o novo gateway de frete
3. Cole as credenciais em JSON:

```json
{
    "token": "abc123xyz",
    "email": "loja@exemplo.com",
    "cep_origem": "01310-100"
}
```

---

## 📝 Exemplo Completo: Provider Fictício

### FakePayProvider (Exemplo Didático)

```php
<?php

namespace App\Services\Payment\Providers;

use App\Services\Payment\PaymentProviderInterface;
use App\Services\Payment\PaymentResult;

class FakePayProvider implements PaymentProviderInterface
{
    public function createPayment(array $pedido, array $cliente, string $metodoEscolhido, array $config = []): PaymentResult
    {
        // Validar credenciais
        $apiKey = $config['api_key'] ?? null;
        if (empty($apiKey)) {
            throw new \RuntimeException('API Key do FakePay não configurada');
        }

        // Simular chamada à API (em produção, seria uma chamada HTTP real)
        $transacaoId = 'FAKE-' . time() . '-' . $pedido['numero_pedido'];
        $status = 'pending';  // Gateway retornaria o status real

        // Dados para exibição na tela de confirmação
        $dadosExibicao = [
            'tipo' => 'fakepay',
            'link_pagamento' => "https://fakepay.com/pay/{$transacaoId}",
            'instrucoes' => 'Acesse o link acima para finalizar o pagamento',
        ];

        return new PaymentResult(
            codigoTransacao: $transacaoId,
            statusInicial: $status,
            dadosExibicao: $dadosExibicao
        );
    }
}
```

**Registrar no PaymentService:**

```php
$providers = [
    'manual' => ManualPaymentProvider::class,
    'fakepay' => FakePayProvider::class,  // ← Adicionar
];
```

**Configurar no Admin:**

```json
{
    "api_key": "fake_api_key_123"
}
```

---

## 🔍 Como Funciona a Resolução de Providers

### Fluxo de Pagamento

1. **CheckoutController** chama `PaymentService::processarPagamento()`
2. **PaymentService** busca configuração em `tenant_gateways` (tipo='payment')
3. **PaymentService** instancia o provider correspondente ao `codigo`
4. **Provider** executa `createPayment()` e retorna `PaymentResult`
5. **CheckoutController** salva `codigo_transacao` e `status` no pedido

### Fluxo de Frete

1. **CheckoutController** chama `ShippingService::calcularFrete()`
2. **ShippingService** busca configuração em `tenant_gateways` (tipo='shipping')
3. **ShippingService** instancia o provider correspondente
4. **Provider** executa `calcularOpcoesFrete()` e retorna lista de opções
5. **CheckoutController** exibe opções para o cliente escolher

---

## 🧪 Como Testar um Novo Provider

### 1. Teste Unitário (Opcional)

Crie um teste simples para validar a lógica:

```php
// tests/Payment/MercadoPagoProviderTest.php
$provider = new MercadoPagoProvider();
$result = $provider->createPayment(
    ['numero_pedido' => 'TEST-001', 'total_geral' => 100.00],
    ['nome' => 'Teste', 'email' => 'teste@exemplo.com'],
    'pix',
    ['api_key' => 'test_key', 'access_token' => 'test_token']
);

assert($result instanceof PaymentResult);
assert(!empty($result->codigoTransacao));
```

### 2. Teste Manual

1. Configure o gateway no admin (`/admin/configuracoes/gateways`)
2. Adicione produtos ao carrinho
3. Acesse o checkout
4. Finalize um pedido de teste
5. Verifique:
   - Código de transação foi salvo no pedido
   - Status inicial está correto
   - Dados de exibição aparecem na tela de confirmação

### 3. Verificar Logs

```php
// No provider, adicione logs para debug:
error_log("MercadoPago: Criando pagamento para pedido {$pedido['numero_pedido']}");
error_log("MercadoPago: Resposta da API: " . json_encode($response));
```

---

## 📊 Estrutura de Dados

### PaymentResult

```php
class PaymentResult
{
    public ?string $codigoTransacao;  // ID da transação no gateway (ou null)
    public string $statusInicial;      // 'pending', 'paid', 'canceled', etc.
    public array $dadosExibicao;      // Dados para exibir na tela de confirmação
}
```

**Exemplo de `dadosExibicao`:**

```php
[
    'tipo' => 'mercadopago',
    'link_pagamento' => 'https://...',
    'qr_code' => 'data:image/png;base64,...',
    'instrucoes' => 'Escaneie o QR Code...',
]
```

### Opções de Frete

```php
[
    [
        'codigo' => 'melhorenvio_pac',
        'titulo' => 'PAC',
        'valor' => 15.90,
        'prazo' => '5 a 8 dias úteis'
    ],
    [
        'codigo' => 'melhorenvio_sedex',
        'titulo' => 'SEDEX',
        'valor' => 29.90,
        'prazo' => '2 a 3 dias úteis'
    ],
]
```

---

## 🔒 Segurança e Boas Práticas

### 1. Validação de Credenciais

Sempre valide se as credenciais estão presentes antes de chamar APIs:

```php
if (empty($config['api_key'])) {
    throw new \RuntimeException('API Key não configurada');
}
```

### 2. Tratamento de Erros

Envolva chamadas de API em try-catch:

```php
try {
    $response = $this->chamarApi($dados);
} catch (\Exception $e) {
    error_log("Erro ao chamar API: " . $e->getMessage());
    throw new \RuntimeException('Erro ao processar pagamento. Tente novamente.');
}
```

### 3. Sanitização de Dados

Nunca exponha credenciais em logs ou mensagens de erro:

```php
// ❌ ERRADO
error_log("API Key: " . $apiKey);

// ✅ CORRETO
error_log("Erro ao processar pagamento (API Key configurada: " . (!empty($apiKey) ? 'sim' : 'não') . ")");
```

### 4. Multi-tenant

Sempre use `TenantContext::id()` para garantir isolamento:

```php
$tenantId = TenantContext::id();
// Buscar configuração apenas do tenant atual
```

---

## 📚 Referências

### Interfaces

- **PaymentProviderInterface:** `src/Services/Payment/PaymentProviderInterface.php`
- **ShippingProviderInterface:** `src/Services/Shipping/ShippingProviderInterface.php`

### Providers de Exemplo

- **ManualPaymentProvider:** `src/Services/Payment/Providers/ManualPaymentProvider.php`
- **SimpleShippingProvider:** `src/Services/Shipping/Providers/SimpleShippingProvider.php`

### Services

- **PaymentService:** `src/Services/Payment/PaymentService.php`
- **ShippingService:** `src/Services/Shipping/ShippingService.php`

### Tabela

- **Migration:** `database/migrations/035_create_tenant_gateways_table.php`

---

## 🐛 Troubleshooting

### Problema: Provider não é encontrado

**Verificar:**
1. Classe do provider existe e está no namespace correto
2. Provider está registrado no array `$providers` do Service
3. Código do gateway no banco corresponde ao código no array

### Problema: Credenciais não funcionam

**Verificar:**
1. JSON de configuração está válido (use `json_decode` para validar)
2. Credenciais estão corretas (teste em ambiente de sandbox primeiro)
3. Provider está lendo `$config` corretamente

### Problema: Erro ao chamar API externa

**Verificar:**
1. URL da API está correta
2. Headers de autenticação estão corretos
3. Formato dos dados enviados está de acordo com a documentação da API
4. Tratamento de erros HTTP (401, 403, 500, etc.)

### Problema: Opções de frete não aparecem

**Verificar:**
1. Provider está retornando array no formato correto
2. CEP está sendo enviado corretamente
3. Dados do pedido (peso, dimensões) estão completos (se necessário)

---

## 🚀 Próximos Passos

### Gateways Sugeridos para Implementação Futura

**Pagamento:**
- Mercado Pago
- Asaas
- Pagarme
- Stripe (se internacional)

**Frete:**
- Melhor Envio
- API dos Correios
- Jadlog
- Transportadoras próprias

### Melhorias Futuras

- Webhooks para atualização automática de status
- Cache de cotações de frete
- Retry automático em caso de falha de API
- Logs estruturados de transações
- Dashboard de transações por gateway

---

**Documentação criada em:** 2025-01-XX  
**Última atualização:** 2025-01-XX
