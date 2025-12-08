<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$avaliacoes = $avaliacoes ?? [];
$produtos = $produtos ?? [];
$filtros = $filtros ?? ['status' => '', 'produto_id' => null, 'nota' => null, 'q' => ''];
$paginacao = $paginacao ?? ['total' => 0, 'totalPages' => 1, 'currentPage' => 1, 'hasPrev' => false, 'hasNext' => false];
$message = $message ?? null;
$messageType = $messageType ?? 'success';
?>
<div class="admin-content-header">
    <h1><i class="bi bi-star icon"></i> Avaliações de Produtos</h1>
    <p>Gerencie as avaliações dos clientes sobre os produtos</p>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>" style="margin-bottom: 2rem; padding: 1rem; border-radius: 4px; <?= $messageType === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' ?>">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" action="<?= $basePath ?>/admin/avaliacoes" class="filters" style="background: white; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
        <div class="filter-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Status</label>
            <select name="status" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                <option value="">Todos</option>
                <option value="pendente" <?= $filtros['status'] === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                <option value="aprovado" <?= $filtros['status'] === 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                <option value="rejeitado" <?= $filtros['status'] === 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
            </select>
        </div>
        <div class="filter-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Produto</label>
            <select name="produto_id" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                <option value="">Todos</option>
                <?php foreach ($produtos as $produto): ?>
                    <option value="<?= (int)$produto['id'] ?>" <?= $filtros['produto_id'] == $produto['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($produto['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Nota</label>
            <select name="nota" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                <option value="">Todas</option>
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= $filtros['nota'] == $i ? 'selected' : '' ?>><?= $i ?> estrela<?= $i > 1 ? 's' : '' ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="filter-group">
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;">Buscar</label>
            <input type="text" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Produto ou cliente..." style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>
        <div>
            <button type="submit" class="btn-filter" style="padding: 0.75rem 1.5rem; background: #F7931E; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer;">
                <i class="bi bi-search icon"></i> Filtrar
            </button>
        </div>
    </div>
    <?php if (!empty($filtros['status']) || !empty($filtros['produto_id']) || !empty($filtros['nota']) || !empty($filtros['q'])): ?>
        <div style="margin-top: 1rem;">
            <a href="<?= $basePath ?>/admin/avaliacoes" style="color: #666; text-decoration: none; font-size: 0.875rem;">
                <i class="bi bi-x-circle icon"></i> Limpar filtros
            </a>
        </div>
    <?php endif; ?>
</form>

<!-- Tabela de Avaliações -->
<?php if (!empty($avaliacoes)): ?>
    <div class="reviews-table" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Produto</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Cliente</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Nota</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Título</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Status</th>
                    <th style="padding: 1rem; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Data</th>
                    <th style="padding: 1rem; text-align: center; font-weight: 600; color: #333; border-bottom: 2px solid #dee2e6;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($avaliacoes as $avaliacao): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <strong style="color: #333;"><?= htmlspecialchars($avaliacao['produto_nome'] ?? 'N/A') ?></strong>
                        </td>
                        <td style="padding: 1rem; color: #666;">
                            <?= htmlspecialchars($avaliacao['customer_name'] ?? 'N/A') ?><br>
                            <small style="color: #999;"><?= htmlspecialchars($avaliacao['customer_email'] ?? '') ?></small>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.125rem; justify-content: center;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $avaliacao['nota']): ?>
                                        <i class="bi bi-star-fill" style="color: #FFC107; font-size: 1rem;"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star" style="color: #ddd; font-size: 1rem;"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        </td>
                        <td style="padding: 1rem; color: #666;">
                            <?= htmlspecialchars($avaliacao['titulo'] ?? '-') ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
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
                                        padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.875rem; font-weight: 600;">
                                <?= htmlspecialchars($label) ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; color: #666;">
                            <?= date('d/m/Y H:i', strtotime($avaliacao['created_at'])) ?>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                                <a href="<?= $basePath ?>/admin/avaliacoes/<?= (int)$avaliacao['id'] ?>" 
                                   style="display: inline-block; padding: 0.5rem 1rem; background: #023A8D; color: white; text-decoration: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600;">
                                    <i class="bi bi-eye icon"></i> Ver
                                </a>
                                <?php if ($avaliacao['status'] === 'pendente' || $avaliacao['status'] === 'rejeitado'): ?>
                                    <form method="POST" action="<?= $basePath ?>/admin/avaliacoes/<?= (int)$avaliacao['id'] ?>/aprovar" style="display: inline;">
                                        <button type="submit" style="padding: 0.5rem 1rem; background: #28a745; color: white; border: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                                            <i class="bi bi-check icon"></i> Aprovar
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($avaliacao['status'] === 'pendente' || $avaliacao['status'] === 'aprovado'): ?>
                                    <form method="POST" action="<?= $basePath ?>/admin/avaliacoes/<?= (int)$avaliacao['id'] ?>/rejeitar" style="display: inline;">
                                        <button type="submit" style="padding: 0.5rem 1rem; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                                            <i class="bi bi-x icon"></i> Rejeitar
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($paginacao['totalPages'] > 1): ?>
        <div class="pagination" style="margin-top: 2rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem;">
            <?php if ($paginacao['hasPrev']): ?>
                <a href="<?= $basePath ?>/admin/avaliacoes?page=<?= $paginacao['currentPage'] - 1 ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['produto_id']) ? '&produto_id=' . (int)$filtros['produto_id'] : '' ?><?= !empty($filtros['nota']) ? '&nota=' . (int)$filtros['nota'] : '' ?><?= !empty($filtros['q']) ? '&q=' . urlencode($filtros['q']) : '' ?>" 
                   style="padding: 0.5rem 1rem; background: white; color: #023A8D; border: 1px solid #023A8D; border-radius: 4px; text-decoration: none; font-weight: 600;">
                    <i class="bi bi-chevron-left icon"></i> Anterior
                </a>
            <?php endif; ?>

            <span style="color: #666; padding: 0.5rem 1rem;">
                Página <?= $paginacao['currentPage'] ?> de <?= $paginacao['totalPages'] ?> 
                (<?= $paginacao['total'] ?> avaliação<?= $paginacao['total'] != 1 ? 'ões' : 'ão' ?>)
            </span>

            <?php if ($paginacao['hasNext']): ?>
                <a href="<?= $basePath ?>/admin/avaliacoes?page=<?= $paginacao['currentPage'] + 1 ?><?= !empty($filtros['status']) ? '&status=' . urlencode($filtros['status']) : '' ?><?= !empty($filtros['produto_id']) ? '&produto_id=' . (int)$filtros['produto_id'] : '' ?><?= !empty($filtros['nota']) ? '&nota=' . (int)$filtros['nota'] : '' ?><?= !empty($filtros['q']) ? '&q=' . urlencode($filtros['q']) : '' ?>" 
                   style="padding: 0.5rem 1rem; background: white; color: #023A8D; border: 1px solid #023A8D; border-radius: 4px; text-decoration: none; font-weight: 600;">
                    Próxima <i class="bi bi-chevron-right icon"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div style="background: white; padding: 3rem; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <i class="bi bi-star icon" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p style="color: #666; font-size: 1.1rem; margin: 0;">
            <?php if (!empty($filtros['status']) || !empty($filtros['produto_id']) || !empty($filtros['nota']) || !empty($filtros['q'])): ?>
                Nenhuma avaliação encontrada com os filtros aplicados.
            <?php else: ?>
                Nenhuma avaliação cadastrada ainda.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<style>
/* Fase 10 – Ajustes layout Admin - Avaliações */
.admin-content-header {
    margin-bottom: 2rem;
}
.admin-content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #333;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}
.admin-content-header p {
    color: #666;
    font-size: 1rem;
}
@media (max-width: 768px) {
    .admin-filters form {
        flex-direction: column;
    }
    .admin-filter-group {
        min-width: 100%;
    }
    .admin-table {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .admin-table table {
        min-width: 1000px;
    }
}
</style>


