<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$clientes = $clientes ?? [];
$filtros = $filtros ?? ['q' => '', 'data_inicio' => '', 'data_fim' => ''];
$paginacao = $paginacao ?? ['total' => 0, 'totalPages' => 1, 'currentPage' => 1, 'hasPrev' => false, 'hasNext' => false];
$message = $message ?? null;
$messageType = $messageType ?? 'success';
?>
<div class="admin-content-header">
    <h1><i class="bi bi-people icon"></i> Clientes</h1>
    <p>Gerencie os clientes cadastrados na sua loja</p>
</div>

<?php if ($message): ?>
    <div class="admin-alert admin-alert-<?= $messageType ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- Filtros e Busca - Fase 10 -->
<div class="admin-filters">
    <form method="GET" action="<?= $basePath ?>/admin/clientes">
        <div class="admin-filter-group">
            <label for="filter-q">Buscar por nome, e-mail ou documento</label>
            <input type="text" id="filter-q" name="q" value="<?= htmlspecialchars($filtros['q']) ?>" placeholder="Digite para buscar...">
        </div>
        <div class="admin-filter-group">
            <label for="filter-data-inicio">Data inicial</label>
            <input type="date" id="filter-data-inicio" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
        </div>
        <div class="admin-filter-group">
            <label for="filter-data-fim">Data final</label>
            <input type="date" id="filter-data-fim" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
        </div>
        <button type="submit" class="admin-btn admin-btn-primary">
            <i class="bi bi-funnel icon"></i>
            Filtrar
        </button>
    </form>
    <?php if (!empty($filtros['q']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
            <a href="<?= $basePath ?>/admin/clientes" style="color: #666; text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-x-circle icon"></i>
                Limpar filtros
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Tabela de Clientes - Fase 10 -->
<?php if (!empty($clientes)): ?>
    <div class="admin-table">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Documento</th>
                    <th>Telefone</th>
                    <th>Data de Cadastro</th>
                    <th style="text-align: center;">Pedidos</th>
                    <th style="text-align: center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                    <tr>
                        <td>
                            <strong style="color: #333;"><?= htmlspecialchars($cliente['name']) ?></strong>
                        </td>
                        <td style="color: #666;">
                            <?= htmlspecialchars($cliente['email']) ?>
                        </td>
                        <td style="color: #666;">
                            <?= htmlspecialchars($cliente['document'] ?? '-') ?>
                        </td>
                        <td style="color: #666;">
                            <?= htmlspecialchars($cliente['phone'] ?? '-') ?>
                        </td>
                        <td style="color: #666;">
                            <?= date('d/m/Y', strtotime($cliente['created_at'])) ?>
                        </td>
                        <td style="text-align: center;">
                            <span style="background: #e3f2fd; color: #1976d2; padding: 0.375rem 0.875rem; border-radius: 6px; font-size: 0.875rem; font-weight: 600;">
                                <?= (int)($cliente['total_pedidos'] ?? 0) ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <a href="<?= $basePath ?>/admin/clientes/<?= (int)$cliente['id'] ?>" class="admin-btn admin-btn-outline" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                <i class="bi bi-eye icon"></i>
                                Ver detalhes
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação - Fase 10 -->
    <?php if ($paginacao['totalPages'] > 1): ?>
        <div class="admin-pagination">
            <?php if ($paginacao['hasPrev']): ?>
                <a href="<?= $basePath ?>/admin/clientes?page=<?= $paginacao['currentPage'] - 1 ?><?= !empty($filtros['q']) ? '&q=' . urlencode($filtros['q']) : '' ?><?= !empty($filtros['data_inicio']) ? '&data_inicio=' . urlencode($filtros['data_inicio']) : '' ?><?= !empty($filtros['data_fim']) ? '&data_fim=' . urlencode($filtros['data_fim']) : '' ?>">
                    <i class="bi bi-chevron-left icon"></i>
                    Anterior
                </a>
            <?php endif; ?>

            <span class="admin-pagination-info">
                Página <?= $paginacao['currentPage'] ?> de <?= $paginacao['totalPages'] ?> 
                (<?= $paginacao['total'] ?> cliente<?= $paginacao['total'] != 1 ? 's' : '' ?>)
            </span>

            <?php if ($paginacao['hasNext']): ?>
                <a href="<?= $basePath ?>/admin/clientes?page=<?= $paginacao['currentPage'] + 1 ?><?= !empty($filtros['q']) ? '&q=' . urlencode($filtros['q']) : '' ?><?= !empty($filtros['data_inicio']) ? '&data_inicio=' . urlencode($filtros['data_inicio']) : '' ?><?= !empty($filtros['data_fim']) ? '&data_fim=' . urlencode($filtros['data_fim']) : '' ?>">
                    Próxima
                    <i class="bi bi-chevron-right icon"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="admin-empty-message">
        <i class="bi bi-people icon"></i>
        <p>
            <?php if (!empty($filtros['q']) || !empty($filtros['data_inicio']) || !empty($filtros['data_fim'])): ?>
                Nenhum cliente encontrado com os filtros aplicados.
            <?php else: ?>
                Nenhum cliente cadastrado ainda.
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>

<style>
/* Fase 10 – Ajustes layout Admin - Clientes */
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
        min-width: 800px;
    }
}
</style>


