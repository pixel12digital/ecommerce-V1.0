<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho de Compras</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        /* Fase 10 - Ajustes layout storefront */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1.25rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header a { 
            color: white; 
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
        }
        .header a:hover {
            opacity: 0.8;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: #333;
        }
        .cart-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f8f8;
            font-weight: 600;
            color: #555;
        }
        .product-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        .product-image-placeholder {
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            background: #f0f0f0;
            border-radius: 6px;
            font-size: 0.75rem;
            flex-shrink: 0;
        }
        .product-image-placeholder .icon {
            font-size: 1.5rem;
            color: #ccc;
            margin-bottom: 0.25rem;
        }
        .product-info {
            display: flex;
            flex-direction: column;
        }
        .product-name {
            font-weight: 600;
            color: #333;
            text-decoration: none;
        }
        .product-name:hover {
            text-decoration: underline;
        }
        .quantity-input {
            width: 80px;
            padding: 0.625rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-align: center;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .quantity-input:focus {
            outline: none;
            border-color: #023A8D;
        }
        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        .btn-remove:hover {
            background: #c82333;
            transform: translateY(-1px);
        }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #023A8D;
            border-bottom: none;
            margin-top: 0.5rem;
        }
        .cart-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .btn-primary {
            background: #F7931E;
            color: white;
            flex: 1;
            text-align: center;
            justify-content: center;
        }
        .btn-primary:hover {
            background: #e6851a;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }
        .empty-cart {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 8px;
        }
        .message {
            padding: 1rem 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message .icon {
            font-size: 1.25rem;
        }
        @media (max-width: 768px) {
            .cart-table {
                overflow-x: auto;
            }
            table {
                min-width: 600px;
            }
            .cart-actions {
                flex-direction: column;
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
        <h2>Carrinho de Compras</h2>
        <a href="<?= $basePath ?>/"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['added'])): ?>
            <div class="message success">
                <i class="bi bi-check-circle icon"></i>
                <span>Produto adicionado ao carrinho!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="message success">
                <i class="bi bi-check-circle icon"></i>
                <span>Carrinho atualizado!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['removed'])): ?>
            <div class="message success">
                <i class="bi bi-check-circle icon"></i>
                <span>Produto removido do carrinho!</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="message error">
                <i class="bi bi-exclamation-triangle icon"></i>
                <span>
                    <?php
                    $errors = [
                        'carrinho_vazio' => 'Seu carrinho está vazio.',
                        'produto_invalido' => 'Produto inválido.',
                    ];
                    echo $errors[$_GET['error']] ?? 'Erro desconhecido.';
                    ?>
                </span>
            </div>
        <?php endif; ?>
        
        <h1 class="page-title">Carrinho de Compras</h1>
        
        <?php if (empty($cart['items'])): ?>
            <div class="empty-cart">
                <i class="bi bi-cart-x icon" style="font-size: 4rem; color: #ccc; display: block; margin-bottom: 1rem;"></i>
                <p style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: #333;">Seu carrinho está vazio</p>
                <p style="color: #666; margin-bottom: 1.5rem;">Adicione produtos ao carrinho para continuar.</p>
                <a href="<?= $basePath ?>/produtos" class="btn btn-primary">
                    <i class="bi bi-arrow-left icon"></i>
                    Continuar Comprando
                </a>
            </div>
        <?php else: ?>
            <div class="cart-table">
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unitário</th>
                            <th>Quantidade</th>
                            <th>Total</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart['items'] as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <?php if ($item['imagem']): ?>
                                            <img src="<?= $basePath ?>/<?= htmlspecialchars($item['imagem']) ?>" 
                                                 alt="<?= htmlspecialchars($item['nome']) ?>"
                                                 class="product-image">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="bi bi-image icon"></i>
                                                <span>Sem imagem</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($item['slug']) ?>" class="product-name">
                                                <?= htmlspecialchars($item['nome']) ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                <td>
                                    <form method="POST" action="<?= $basePath ?>/carrinho/atualizar" style="display: inline;">
                                        <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                        <input type="number" name="quantidade" value="<?= $item['quantidade'] ?>" 
                                               min="1" class="quantity-input" 
                                               onchange="this.form.submit()">
                                    </form>
                                </td>
                                <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                                <td>
                                    <form method="POST" action="<?= $basePath ?>/carrinho/remover" style="display: inline;">
                                        <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                        <button type="submit" class="btn btn-remove">Remover</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                <div class="summary-row" style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
                    <span style="font-weight: 600;">Frete:</span>
                    <span style="color: #666;">Será calculado no checkout</span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                </div>
                
                <div class="cart-actions">
                    <a href="<?= $basePath ?>/produtos" class="btn btn-secondary">
                        <i class="bi bi-arrow-left icon"></i>
                        Continuar Comprando
                    </a>
                    <a href="<?= $basePath ?>/checkout" class="btn btn-primary">
                        Finalizar Compra
                        <i class="bi bi-arrow-right icon"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


