<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$cliente = $cliente ?? null;
$enderecos = $enderecos ?? [];
$pedidos = $pedidos ?? [];
$estatisticas = $estatisticas ?? ['total_pedidos' => 0, 'total_gasto' => 0, 'data_ultimo_pedido' => null];

if (!$cliente) {
    echo '<p>Cliente não encontrado.</p>';
    return;
}
?>
<div class="admin-content-header">
    <h1><i class="bi bi-person icon"></i> Cliente: <?= htmlspecialchars($cliente['name']) ?></h1>
    <a href="<?= $basePath ?>/admin/clientes" style="color: #023A8D; text-decoration: none; font-size: 0.875rem;">
        <i class="bi bi-arrow-left icon"></i> Voltar para lista
    </a>
</div>

<!-- Dados Cadastrais -->
<div class="card" style="background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
        <i class="bi bi-person-circle icon"></i> Dados Cadastrais
    </h2>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Nome</label>
            <p style="margin: 0; color: #333; font-size: 1rem;"><?= htmlspecialchars($cliente['name']) ?></p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">E-mail</label>
            <p style="margin: 0; color: #333; font-size: 1rem;"><?= htmlspecialchars($cliente['email']) ?></p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Documento (CPF/CNPJ)</label>
            <p style="margin: 0; color: #333; font-size: 1rem;"><?= htmlspecialchars($cliente['document'] ?? '-') ?></p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Telefone</label>
            <p style="margin: 0; color: #333; font-size: 1rem;"><?= htmlspecialchars($cliente['phone'] ?? '-') ?></p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Data de Cadastro</label>
            <p style="margin: 0; color: #333; font-size: 1rem;">
                <?= date('d/m/Y H:i', strtotime($cliente['created_at'])) ?>
            </p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Última Atualização</label>
            <p style="margin: 0; color: #333; font-size: 1rem;">
                <?= date('d/m/Y H:i', strtotime($cliente['updated_at'])) ?>
            </p>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="card" style="background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
        <i class="bi bi-graph-up icon"></i> Estatísticas
    </h2>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
        <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 2rem; font-weight: 700; color: #023A8D; margin-bottom: 0.5rem;">
                <?= $estatisticas['total_pedidos'] ?>
            </div>
            <div style="color: #666; font-size: 0.875rem; font-weight: 600;">Total de Pedidos</div>
        </div>
        <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 2rem; font-weight: 700; color: #28a745; margin-bottom: 0.5rem;">
                R$ <?= number_format($estatisticas['total_gasto'], 2, ',', '.') ?>
            </div>
            <div style="color: #666; font-size: 0.875rem; font-weight: 600;">Valor Total Gasto</div>
        </div>
        <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
            <div style="font-size: 1.25rem; font-weight: 700; color: #333; margin-bottom: 0.5rem;">
                <?php if ($estatisticas['data_ultimo_pedido']): ?>
                    <?= date('d/m/Y', strtotime($estatisticas['data_ultimo_pedido'])) ?>
                <?php else: ?>
                    -
                <?php endif; ?>
            </div>
            <div style="color: #666; font-size: 0.875rem; font-weight: 600;">Último Pedido</div>
        </div>
    </div>
</div>

<!-- Endereços -->
<?php if (!empty($enderecos)): ?>
    <div class="card" style="background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
            <i class="bi bi-geo-alt icon"></i> Endereços Cadastrados
        </h2>
        <div style="display: grid; gap: 1rem;">
            <?php foreach ($enderecos as $endereco): ?>
                <div style="padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid <?= $endereco['is_default'] ? '#28a745' : '#ccc' ?>;">
                    <?php if ($endereco['is_default']): ?>
                        <span style="background: #28a745; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.5rem; display: inline-block;">
                            Endereço Padrão
                        </span>
                    <?php endif; ?>
                    <p style="margin: 0.5rem 0; color: #333;">
                        <strong>Tipo:</strong> <?= htmlspecialchars($endereco['type'] ?? 'Entrega') ?><br>
                        <strong>Endereço:</strong> 
                        <?= htmlspecialchars($endereco['street']) ?>, 
                        <?= htmlspecialchars($endereco['number']) ?>
                        <?php if ($endereco['complement']): ?>
                            - <?= htmlspecialchars($endereco['complement']) ?>
                        <?php endif; ?><br>
                        <strong>Bairro:</strong> <?= htmlspecialchars($endereco['neighborhood']) ?><br>
                        <strong>Cidade/UF:</strong> <?= htmlspecialchars($endereco['city']) ?>/<?= htmlspecialchars($endereco['state']) ?><br>
                        <strong>CEP:</strong> <?= htmlspecialchars($endereco['zipcode']) ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Histórico de Pedidos -->
<div class="card" style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
        <i class="bi bi-receipt icon"></i> Histórico de Pedidos
    </h2>
    
    <?php if (!empty($pedidos)): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Número do Pedido</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Data</th>
                        <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Status</th>
                        <th style="padding: 1rem; text-align: right; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Valor Total</th>
                        <th style="padding: 1rem; text-align: center; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 1rem;">
                                <strong style="color: #023A8D;"><?= htmlspecialchars($pedido['numero_pedido']) ?></strong>
                            </td>
                            <td style="padding: 1rem; color: #666;">
                                <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php
                                $statusClass = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'canceled' => 'danger',
                                    'shipped' => 'info',
                                    'completed' => 'success',
                                ];
                                $statusLabel = [
                                    'pending' => 'Pendente',
                                    'paid' => 'Pago',
                                    'canceled' => 'Cancelado',
                                    'shipped' => 'Enviado',
                                    'completed' => 'Concluído',
                                ];
                                $status = $pedido['status'] ?? 'pending';
                                $class = $statusClass[$status] ?? 'secondary';
                                $label = $statusLabel[$status] ?? ucfirst($status);
                                ?>
                                <span style="background: <?= $class === 'success' ? '#d4edda' : ($class === 'warning' ? '#fff3cd' : ($class === 'danger' ? '#f8d7da' : '#d1ecf1')) ?>; 
                                            color: <?= $class === 'success' ? '#155724' : ($class === 'warning' ? '#856404' : ($class === 'danger' ? '#721c24' : '#0c5460')) ?>; 
                                            padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                                    <?= htmlspecialchars($label) ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right; font-weight: 600; color: #333;">
                                R$ <?= number_format($pedido['total_geral'], 2, ',', '.') ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <a href="<?= $basePath ?>/admin/pedidos/<?= (int)$pedido['id'] ?>" 
                                   style="display: inline-block; padding: 0.5rem 1rem; background: #023A8D; color: white; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                                    <i class="bi bi-eye icon"></i> Ver pedido
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="bi bi-inbox icon" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="margin: 0; font-size: 1.1rem;">Este cliente ainda não realizou nenhum pedido.</p>
        </div>
    <?php endif; ?>
</div>

<style>
    .icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }
    @media (max-width: 768px) {
        .card > div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
        }
        table {
            min-width: 600px;
        }
    }
</style>


