<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$avaliacao = $avaliacao ?? null;

if (!$avaliacao) {
    echo '<p>Avaliação não encontrada.</p>';
    return;
}
?>
<div class="admin-content-header">
    <h1><i class="bi bi-star icon"></i> Avaliação #<?= (int)$avaliacao['id'] ?></h1>
    <a href="<?= $basePath ?>/admin/avaliacoes" style="color: #023A8D; text-decoration: none; font-size: 0.875rem;">
        <i class="bi bi-arrow-left icon"></i> Voltar para lista
    </a>
</div>

<!-- Dados da Avaliação -->
<div class="card" style="background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
        <i class="bi bi-info-circle icon"></i> Informações da Avaliação
    </h2>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Produto</label>
            <p style="margin: 0; color: #333; font-size: 1rem;">
                <strong><?= htmlspecialchars($avaliacao['produto_nome'] ?? 'N/A') ?></strong>
                <?php if (!empty($avaliacao['produto_slug'])): ?>
                    <br><a href="<?= $basePath ?>/admin/produtos/<?= (int)$avaliacao['produto_id'] ?>" style="color: #023A8D; text-decoration: none; font-size: 0.875rem;">
                        Ver produto no admin
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Cliente</label>
            <p style="margin: 0; color: #333; font-size: 1rem;">
                <strong><?= htmlspecialchars($avaliacao['customer_name'] ?? 'N/A') ?></strong><br>
                <small style="color: #666;"><?= htmlspecialchars($avaliacao['customer_email'] ?? '') ?></small>
                <?php if (!empty($avaliacao['customer_id'])): ?>
                    <br><a href="<?= $basePath ?>/admin/clientes/<?= (int)$avaliacao['customer_id'] ?>" style="color: #023A8D; text-decoration: none; font-size: 0.875rem;">
                        Ver cliente no admin
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Nota</label>
            <div style="display: flex; gap: 0.25rem; align-items: center;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <?php if ($i <= $avaliacao['nota']): ?>
                        <i class="bi bi-star-fill" style="color: #FFC107; font-size: 1.5rem;"></i>
                    <?php else: ?>
                        <i class="bi bi-star" style="color: #ddd; font-size: 1.5rem;"></i>
                    <?php endif; ?>
                <?php endfor; ?>
                <span style="margin-left: 0.5rem; font-size: 1.25rem; font-weight: 600; color: #333;">
                    <?= (int)$avaliacao['nota'] ?>/5
                </span>
            </div>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Status</label>
            <p style="margin: 0;">
                <?php
                $statusClass = [
                    'pendente' => 'warning',
                    'aprovado' => 'success',
                    'rejeitado' => 'danger',
                ];
                $statusLabel = [
                    'pendente' => 'Pendente',
                    'aprovado' => 'Aprovado',
                    'rejeitado' => 'Rejeitado',
                ];
                $status = $avaliacao['status'] ?? 'pendente';
                $class = $statusClass[$status] ?? 'secondary';
                $label = $statusLabel[$status] ?? ucfirst($status);
                ?>
                <span style="background: <?= $class === 'success' ? '#d4edda' : ($class === 'warning' ? '#fff3cd' : '#f8d7da') ?>; 
                            color: <?= $class === 'success' ? '#155724' : ($class === 'warning' ? '#856404' : '#721c24') ?>; 
                            padding: 0.5rem 1rem; border-radius: 12px; font-size: 1rem; font-weight: 600;">
                    <?= htmlspecialchars($label) ?>
                </span>
            </p>
        </div>
        <div>
            <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Data de Criação</label>
            <p style="margin: 0; color: #333; font-size: 1rem;">
                <?= date('d/m/Y H:i', strtotime($avaliacao['created_at'])) ?>
            </p>
        </div>
        <?php if (!empty($avaliacao['numero_pedido'])): ?>
            <div>
                <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.25rem; font-size: 0.875rem;">Pedido Relacionado</label>
                <p style="margin: 0; color: #333; font-size: 1rem;">
                    <strong><?= htmlspecialchars($avaliacao['numero_pedido']) ?></strong>
                    <?php if (!empty($avaliacao['pedido_id'])): ?>
                        <br><a href="<?= $basePath ?>/admin/pedidos/<?= (int)$avaliacao['pedido_id'] ?>" style="color: #023A8D; text-decoration: none; font-size: 0.875rem;">
                            Ver pedido
                        </a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Título e Comentário -->
<?php if (!empty($avaliacao['titulo']) || !empty($avaliacao['comentario'])): ?>
    <div class="card" style="background: white; border-radius: 8px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
            <i class="bi bi-chat-left-text icon"></i> Conteúdo da Avaliação
        </h2>
        <?php if (!empty($avaliacao['titulo'])): ?>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.5rem; font-size: 0.875rem;">Título</label>
                <p style="margin: 0; color: #333; font-size: 1.125rem; font-weight: 600;">
                    <?= htmlspecialchars($avaliacao['titulo']) ?>
                </p>
            </div>
        <?php endif; ?>
        <?php if (!empty($avaliacao['comentario'])): ?>
            <div>
                <label style="display: block; font-weight: 600; color: #666; margin-bottom: 0.5rem; font-size: 0.875rem;">Comentário</label>
                <p style="margin: 0; color: #333; line-height: 1.6; white-space: pre-wrap;">
                    <?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Ações -->
<div class="card" style="background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin: 0 0 1.5rem 0; font-size: 1.25rem; color: #333; border-bottom: 2px solid #023A8D; padding-bottom: 0.5rem;">
        <i class="bi bi-gear icon"></i> Ações
    </h2>
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <?php if ($avaliacao['status'] === 'pendente' || $avaliacao['status'] === 'rejeitado'): ?>
            <form method="POST" action="<?= $basePath ?>/admin/avaliacoes/<?= (int)$avaliacao['id'] ?>/aprovar">
                <button type="submit" style="padding: 0.75rem 2rem; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                    <i class="bi bi-check-circle icon"></i> Aprovar Avaliação
                </button>
            </form>
        <?php endif; ?>
        <?php if ($avaliacao['status'] === 'pendente' || $avaliacao['status'] === 'aprovado'): ?>
            <form method="POST" action="<?= $basePath ?>/admin/avaliacoes/<?= (int)$avaliacao['id'] ?>/rejeitar">
                <button type="submit" style="padding: 0.75rem 2rem; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 1rem; font-weight: 600; cursor: pointer;">
                    <i class="bi bi-x-circle icon"></i> Rejeitar Avaliação
                </button>
            </form>
        <?php endif; ?>
    </div>
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
    }
</style>


