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
    
    <!-- Envio / Correios -->
    <div class="card">
        <h3 class="card-title"><i class="bi bi-truck icon"></i> Envio / Correios</h3>
        
        <?php
        $hasEtiqueta = !empty($pedido['tracking_code']) || !empty($pedido['label_url']) || !empty($pedido['label_pdf_path']);
        $labelFormat = $pedido['label_format'] ?? 'A4';
        $labelGeneratedAt = $pedido['label_generated_at'] ?? null;
        ?>
        
        <!-- Status da Etiqueta -->
        <?php if ($hasEtiqueta): ?>
            <div style="padding: 1rem; background: #d4edda; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 1.5rem;">
                <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #155724;">
                    <i class="bi bi-check-circle"></i> Etiqueta gerada
                    <?php if ($labelGeneratedAt): ?>
                        <small style="font-weight: normal; color: #666; margin-left: 0.5rem;">
                            em <?= date('d/m/Y H:i', strtotime($labelGeneratedAt)) ?>
                        </small>
                    <?php endif; ?>
                </p>
                <?php if (!empty($pedido['tracking_code'])): ?>
                    <p style="margin: 0.5rem 0; color: #155724;">
                        <strong>Código de rastreamento:</strong> <?= htmlspecialchars($pedido['tracking_code']) ?>
                    </p>
                <?php endif; ?>
                <p style="margin: 1rem 0 0 0;">
                    <a href="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/frete/imprimir-etiqueta" 
                       target="_blank"
                       class="btn" 
                       style="display: inline-block; padding: 0.75rem 1.5rem; background: #28a745; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
                        <i class="bi bi-printer" style="margin-right: 0.5rem;"></i>
                        Imprimir Etiqueta
                    </a>
                </p>
            </div>
        <?php else: ?>
            <div style="padding: 1rem; background: #fff3cd; border-radius: 4px; border-left: 4px solid #856404; margin-bottom: 1.5rem;">
                <p style="margin: 0; color: #856404;">
                    <i class="bi bi-info-circle"></i> Etiqueta ainda não foi gerada.
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Formulário para Gerar Etiqueta -->
        <form method="POST" action="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/frete/gerar-etiqueta" class="label-form" style="margin-top: 1rem;">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="label_format" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                    Formato da Etiqueta
                </label>
                <select id="label_format" name="label_format" 
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                    <option value="A4" <?= $labelFormat === 'A4' ? 'selected' : '' ?>>
                        A4 (Folha comum - 2 etiquetas por folha)
                    </option>
                    <option value="10x15" <?= $labelFormat === '10x15' ? 'selected' : '' ?>>
                        10x15 (Térmica - 100x150mm)
                    </option>
                </select>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                    <?php if (!$hasEtiqueta): ?>
                        O formato será usado ao gerar a etiqueta.
                    <?php else: ?>
                        <strong>Nota:</strong> Para alterar o formato, será necessário gerar a etiqueta novamente (quando a API estiver disponível).
                    <?php endif; ?>
                    <?php if ($labelFormat === '10x15'): ?>
                        <br><strong>Observação:</strong> Formato 10x15 depende do suporte da API dos Correios.
                    <?php endif; ?>
                </small>
            </div>
            
            <button type="submit" class="btn" style="padding: 0.75rem 1.5rem; background: #023A8D; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 1rem;">
                <i class="bi bi-file-earmark-plus" style="margin-right: 0.5rem;"></i>
                <?= $hasEtiqueta ? 'Regenerar Etiqueta' : 'Gerar Etiqueta' ?>
            </button>
        </form>
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
    
    <!-- Documento do Envio -->
    <div class="card">
        <h3 class="card-title">Documento do Envio</h3>
        
        <?php
        $documentoEnvio = $pedido['documento_envio'] ?? 'declaracao_conteudo';
        $nfReference = $pedido['nf_reference'] ?? $pedido['nf_chave'] ?? '';
        ?>
        
        <form method="POST" action="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/documento-envio" class="document-form" style="margin-top: 1rem;">
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="documento_envio" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Tipo de Documento *</label>
                <select id="documento_envio" name="documento_envio" required 
                        onchange="toggleDocumentFields()"
                        style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                    <option value="declaracao_conteudo" <?= $documentoEnvio === 'declaracao_conteudo' ? 'selected' : '' ?>>
                        Declaração de Conteúdo
                    </option>
                    <option value="nota_fiscal" <?= $documentoEnvio === 'nota_fiscal' ? 'selected' : '' ?>>
                        Nota Fiscal
                    </option>
                </select>
            </div>
            
            <!-- Campo para Declaração de Conteúdo -->
            <div id="declaracao_section" class="document-section" style="display: <?= $documentoEnvio === 'declaracao_conteudo' ? 'block' : 'none' ?>;">
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #023A8D;">
                    <p style="margin: 0 0 1rem 0; color: #666;">
                        A Declaração de Conteúdo será gerada automaticamente com base nos dados do pedido e do remetente configurado no gateway Correios.
                    </p>
                    <a href="<?= $basePath ?>/admin/pedidos/<?= $pedido['id'] ?>/envio/declaracao-conteudo" 
                       target="_blank"
                       class="btn" 
                       style="display: inline-block; padding: 0.75rem 1.5rem; background: #023A8D; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
                        <i class="bi bi-file-earmark-pdf" style="margin-right: 0.5rem;"></i>
                        Visualizar Declaração (PDF)
                    </a>
                </div>
            </div>
            
            <!-- Campo para Nota Fiscal -->
            <div id="nota_fiscal_section" class="document-section" style="display: <?= $documentoEnvio === 'nota_fiscal' ? 'block' : 'none' ?>;">
                <div style="padding: 1rem; background: #fff3cd; border-radius: 4px; border-left: 4px solid #856404; margin-bottom: 1rem;">
                    <p style="margin: 0; color: #856404; font-size: 0.875rem;">
                        <strong>Nota:</strong> A Nota Fiscal é emitida fora do sistema. Aqui é apenas para referência e registro.
                    </p>
                </div>
                <div class="form-group">
                    <label for="nf_reference" style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Referência da NF (opcional)</label>
                    <input type="text" 
                           id="nf_reference" 
                           name="nf_reference" 
                           value="<?= htmlspecialchars($nfReference) ?>"
                           placeholder="Chave da NF (44 dígitos), número da NF, ou observação"
                           maxlength="255"
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                        Informe a chave da NF-e, número da NF, ou qualquer referência relacionada.
                    </small>
                </div>
            </div>
            
            <button type="submit" class="btn" style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: #28a745; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                Salvar Documento
            </button>
        </form>
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

<script>
function toggleDocumentFields() {
    const select = document.getElementById('documento_envio');
    const selectedValue = select.value;
    
    document.getElementById('declaracao_section').style.display = selectedValue === 'declaracao_conteudo' ? 'block' : 'none';
    document.getElementById('nota_fiscal_section').style.display = selectedValue === 'nota_fiscal' ? 'block' : 'none';
}

// Executar ao carregar
document.addEventListener('DOMContentLoaded', function() {
    toggleDocumentFields();
});
</script>


