<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            text-align: center;
        }
        .header a { color: white; text-decoration: none; }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .success-box {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .success-box h1 {
            color: #155724;
            margin-bottom: 0.5rem;
        }
        .success-box p {
            color: #155724;
            font-size: 1.1rem;
        }
        .order-info {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: #333;
            border-bottom: 2px solid #023A8D;
            padding-bottom: 0.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.25rem;
        }
        .info-value {
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .items-table th,
        .items-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .items-table th {
            background: #f8f8f8;
            font-weight: 600;
        }
        .payment-instructions {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #023A8D;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 1rem;
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    
    <div class="header">
        <h2>Pedido Confirmado</h2>
        <a href="<?= $basePath ?>/"><i class="bi bi-arrow-left icon"></i> Voltar à Home</a>
    </div>
    
    <div class="container">
        <div class="success-box">
            <h1><i class="bi bi-check-circle-fill icon" style="color: #28a745; font-size: 2rem;"></i> Pedido Recebido!</h1>
            <p>Obrigado pela sua compra. Seu pedido foi registrado com sucesso.</p>
        </div>
        
        <div class="order-info">
            <h3 class="info-title">Informações do Pedido</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Número do Pedido</span>
                    <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: #023A8D;">
                        <?= htmlspecialchars($pedido['numero_pedido']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <?php
                        echo \App\Support\LangHelper::orderStatusLabel($pedido['status']);
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Data</span>
                    <span class="info-value">
                        <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total</span>
                    <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: #023A8D;">
                        R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="order-info">
            <h3 class="info-title">Itens do Pedido</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['total_linha'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 1rem; text-align: right;">
                <div style="display: inline-block; text-align: left;">
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Subtotal:</strong> R$ <?= number_format($pedido['total_produtos'], 2, ',', '.') ?>
                    </div>
                    <div style="margin-bottom: 0.5rem;">
                        <strong>Frete:</strong> R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?>
                    </div>
                    <div style="font-size: 1.2rem; font-weight: 700; color: #023A8D;">
                        <strong>Total:</strong> R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="order-info">
            <h3 class="info-title">Endereço de Entrega</h3>
            <p>
                <?= htmlspecialchars($pedido['entrega_logradouro']) ?>, 
                <?= htmlspecialchars($pedido['entrega_numero']) ?>
                <?php if ($pedido['entrega_complemento']): ?>
                    - <?= htmlspecialchars($pedido['entrega_complemento']) ?>
                <?php endif; ?><br>
                <?= htmlspecialchars($pedido['entrega_bairro']) ?> - 
                <?= htmlspecialchars($pedido['entrega_cidade']) ?>/<?= htmlspecialchars($pedido['entrega_estado']) ?><br>
                CEP: <?= htmlspecialchars($pedido['entrega_cep']) ?>
            </p>
        </div>
        
        <?php if ($pedido['metodo_pagamento'] === 'manual_pix'): ?>
            <div class="order-info">
                <h3 class="info-title">Instruções de Pagamento</h3>
                <div class="payment-instructions">
                    <p><strong>Método:</strong> PIX / Transferência</p>
                    <p><?= htmlspecialchars($instrucoesPagamento) ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div style="text-align: center;">
            <a href="<?= $basePath ?>/produtos" class="btn">Continuar Comprando</a>
        </div>
    </div>
</body>
</html>


