# Integração Cielo - Gateway de Pagamento

## Resumo

Implementação do gateway de pagamento Cielo no Ponto do Golfe, com interface amigável (sem JSON) e teste de conexão.

## O que foi implementado

### 1. Interface Admin (Integrações / Gateways)

- **Manual / PIX**: Campos de texto para mensagem e instruções adicionais
- **Cielo**: Campos para MerchantId, MerchantKey e Ambiente (Sandbox/Produção)
- Botão **"Testar Conexão Cielo"** para validar credenciais antes de salvar
- Exibição condicional: só mostra os campos do provedor selecionado

### 2. Backend

- `GatewayConfigController::processarConfigPayment()` – Monta o JSON a partir dos campos do formulário
- `GatewayConfigController::testCielo()` – Endpoint POST `/admin/gateways/cielo/test` para testar credenciais
- `CieloPaymentProvider` – Provider que cria pagamentos PIX via API Cielo
- `PaymentService` – Registro do provider Cielo e método `cielo_pix`

### 3. Checkout e Confirmação

- Método **PIX (Cielo)** disponível no checkout quando Cielo está configurado
- Criação de pagamento PIX na Cielo e exibição do QR Code na página de confirmação
- Coluna `payment_details` (JSON) em `pedidos` para armazenar QR Code e dados PIX

### 4. Migração

- `057_add_payment_details_to_pedidos.php` – Adiciona coluna `payment_details` em `pedidos`

## Como usar

1. Acesse **Admin → Integrações / Gateways**
2. Selecione **Cielo** no provedor de pagamento
3. Preencha **MerchantId** e **MerchantKey** (obtidos em [minhaconta2.cielo.com.br](https://minhaconta2.cielo.com.br) → E-commerce → API e-commerce Cielo → Credenciais)
4. Escolha **Testes (Sandbox)** ou **Produção**
5. Clique em **Testar Conexão Cielo** para validar
6. Salve as configurações

## Credenciais

- **MerchantId**: Identificador da loja na API Cielo
- **MerchantKey**: Chave de acesso
- **Número EC (Estabelecimento Comercial)**: 2763676299 – usado pela Cielo para confirmações; não é enviado na API

## Endpoints Cielo

| Ambiente | Transações | Consultas |
|----------|------------|-----------|
| Sandbox  | apisandbox.cieloecommerce.cielo.com.br | apiquerysandbox.cieloecommerce.cielo.com.br |
| Produção | api.cieloecommerce.cielo.com.br | apiquery.cieloecommerce.cielo.com.br |

## Documentação oficial

- [docs.cielo.com.br](https://docs.cielo.com.br)
- [API E-commerce Cielo](https://docs.cielo.com.br/ecommerce-cielo)
