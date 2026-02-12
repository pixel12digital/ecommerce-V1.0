<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="order-detail-page">
    <?php if (isset($_GET['success'])): ?>
        <div class="message success">Status atualizado com sucesso!</div>
    <?php endif; ?>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="message error">
            <?php
            $errors = [
                'status_invalido' => 'Status inválido.',
                'pedido_nao_encontrado' => 'Pedido não encontrado.',
                'rastreio_vazio' => 'Código de rastreamento não pode estar vazio.',
                'rastreio_invalido' => 'Código de rastreamento inválido.',
                'erro_salvar_rastreio' => 'Erro ao salvar código de rastreamento.',
                'erro_marcar_enviado' => 'Erro ao marcar pedido como enviado.',
                'metodo_frete_invalido' => 'Método de frete não suportado para geração de etiqueta.',
                'selecione_servico_envio' => 'Selecione o serviço de envio (PAC ou SEDEX) para gerar a etiqueta.',
                'pedido_cancelado' => 'Não é possível gerar etiqueta para pedido cancelado.',
                'cep_invalido' => 'CEP de entrega inválido.',
                'endereco_incompleto' => 'Endereço de entrega incompleto.',
                'pedido_sem_itens' => 'Pedido sem itens.',
                'gateway_nao_configurado' => 'Gateway de frete não configurado. Configure em Admin → Integrações.',
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
    
    <!-- Envio -->
    <div class="card">
        <h3 class="card-title"><i class="bi bi-truck icon"></i> Envio</h3>
        
        <?php $trackingCode = $pedido['tracking_code'] ?? ''; ?>

        <?php if (($pedido['metodo_frete'] ?? '') === 'frete_gratis'): ?>
            <div style="padding: 0.75rem 1rem; background: #e8f5e9; border-radius: 4px; border-left: 4px solid #2e7d32; margin-bottom: 1.25rem;">
                <p style="margin: 0; color: #2e7d32; font-weight: 600; font-size: 0.9rem;">
                    <i class="bi bi-gift"></i> Frete Grátis para o cliente
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Código de Rastreamento -->
        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #023A8D;">
            <h4 style="margin: 0 0 1rem 0; font-size: 1rem; color: #333;">Código de Rastreamento</h4>
            <form method="POST" action="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/rastreio" class="tracking-form" style="display: flex; gap: 0.5rem; align-items: end;">
                <div class="form-group" style="flex: 1;">
                    <input type="text" 
                           name="tracking_code" 
                           id="tracking_code"
                           value="<?= htmlspecialchars($trackingCode) ?>"
                           placeholder="Ex: BR123456789BR"
                           maxlength="100"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                </div>
                <button type="submit" class="btn" style="padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; white-space: nowrap;">
                    <i class="bi bi-check-circle" style="margin-right: 0.5rem;"></i>
                    Salvar
                </button>
            </form>
            
            <?php if (!empty($trackingCode)): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                    <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #155724;">
                        <i class="bi bi-check-circle"></i> Rastreio: 
                        <strong><?= htmlspecialchars($trackingCode) ?></strong>
                    </p>
                    <a href="https://rastreamento.correios.com.br/app/index.php" 
                       target="_blank"
                       style="color: #023A8D; text-decoration: none; font-size: 0.875rem; font-weight: 600;">
                        <i class="bi bi-box-arrow-up-right" style="margin-right: 0.25rem;"></i>
                        Rastrear nos Correios
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Marcar como Enviado -->
        <?php if ($pedido['status'] !== 'shipped' && $pedido['status'] !== 'completed'): ?>
            <form method="POST" action="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/marcar-enviado" style="display: inline;">
                <button type="submit" 
                        class="btn" 
                        style="padding: 0.75rem 1.5rem; background: #F7931E; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;"
                        onclick="return confirm('Marcar este pedido como enviado?');">
                    <i class="bi bi-truck" style="margin-right: 0.5rem;"></i>
                    Marcar como Enviado
                </button>
            </form>
        <?php else: ?>
            <div style="padding: 0.75rem 1rem; background: #d4edda; border-radius: 4px;">
                <p style="margin: 0; color: #155724; font-weight: 600;">
                    <i class="bi bi-check-circle"></i> Pedido enviado
                </p>
            </div>
        <?php endif; ?>
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

<style>
.order-detail-page {
    max-width: 1200px;
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
.message.info {
    background: #d1ecf1;
    color: #0c5460;
}
.btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #023A8D;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    font-size: 1rem;
}
.btn:hover {
    background: #022a6d;
}
</style>



