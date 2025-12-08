<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?= htmlspecialchars($pedido['numero_pedido']) ?> - Store Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a { color: white; text-decoration: none; }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .card-title {
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
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-canceled { background: #f8d7da; color: #721c24; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-completed { background: #d4edda; color: #155724; }
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
        .status-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            margin-top: 1rem;
        }
        .form-group {
            flex: 1;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            background: #F7931E;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .message {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
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
        <h2>Pedido #<?= htmlspecialchars($pedido['numero_pedido']) ?></h2>
        <a href="<?= $basePath ?>/admin/pedidos"><i class="bi bi-arrow-left icon"></i> Voltar</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="message success">Status atualizado com sucesso!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="message error">
                <?php
                $errors = [
                    'status_invalido' => 'Status inválido.',
                    'pedido_nao_encontrado' => 'Pedido não encontrado.',
                ];
                echo $errors[$_GET['error']] ?? 'Erro desconhecido.';
                ?>
            </div>
        <?php endif; ?>
        
        <!-- Informações Gerais -->
        <div class="card">
            <h3 class="card-title">Informações do Pedido</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Número do Pedido</span>
                    <span class="info-value" style="font-size: 1.2rem; font-weight: 700; color: #023A8D;">
                        <?= htmlspecialchars($pedido['numero_pedido']) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="status-badge status-<?= $pedido['status'] ?>">
                        <?php
                        echo \App\Support\LangHelper::orderStatusLabel($pedido['status']);
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Data do Pedido</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Última Atualização</span>
                    <span class="info-value"><?= date('d/m/Y H:i', strtotime($pedido['updated_at'])) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Método de Pagamento</span>
                    <span class="info-value">
                        <?php
                        $metodos = [
                            'manual_pix' => 'PIX / Transferência',
                        ];
                        echo $metodos[$pedido['metodo_pagamento']] ?? $pedido['metodo_pagamento'];
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Método de Frete</span>
                    <span class="info-value">
                        <?php
                        $fretes = [
                            'frete_fixo' => 'Frete Padrão',
                            'frete_gratis' => 'Frete Grátis',
                        ];
                        echo $fretes[$pedido['metodo_frete']] ?? $pedido['metodo_frete'];
                        ?>
                    </span>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 2px solid #eee;">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Subtotal Produtos</span>
                        <span class="info-value">R$ <?= number_format($pedido['total_produtos'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Frete</span>
                        <span class="info-value">R$ <?= number_format($pedido['total_frete'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Descontos</span>
                        <span class="info-value">R$ <?= number_format($pedido['total_descontos'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Total Geral</span>
                        <span class="info-value" style="font-size: 1.5rem; font-weight: 700; color: #023A8D;">
                            R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Formulário de atualização de status -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #eee;">
                <h4 style="margin-bottom: 1rem;">Alterar Status</h4>
                <form method="POST" action="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/status" class="status-form">
                    <div class="form-group">
                        <label>Novo Status</label>
                        <select name="status" required>
                            <?php foreach ($statusDisponiveis as $status): ?>
                                <option value="<?= $status ?>" <?= $pedido['status'] === $status ? 'selected' : '' ?>>
                                    <?php
                                    echo \App\Support\LangHelper::orderStatusLabel($status);
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Atualizar Status</button>
                </form>
            </div>
        </div>
        
        <!-- Dados do Cliente -->
        <div class="card">
            <h3 class="card-title">Dados do Cliente</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nome</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['cliente_nome']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">E-mail</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['cliente_email']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Telefone</span>
                    <span class="info-value"><?= htmlspecialchars($pedido['cliente_telefone'] ?: 'Não informado') ?></span>
                </div>
            </div>
        </div>
        
        <!-- Endereço de Entrega -->
        <div class="card">
            <h3 class="card-title">Endereço de Entrega</h3>
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
        
        <!-- Itens do Pedido -->
        <div class="card">
            <h3 class="card-title">Itens do Pedido</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>SKU</th>
                        <th>Quantidade</th>
                        <th>Preço Unitário</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nome_produto']) ?></td>
                            <td><?= htmlspecialchars($item['sku'] ?: '-') ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['total_linha'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pedido['observacoes']): ?>
            <div class="card">
                <h3 class="card-title">Observações</h3>
                <p><?= nl2br(htmlspecialchars($pedido['observacoes'])) ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>


