<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="newsletter-page">
    <div class="admin-filters">
        <form method="GET">
            <div class="admin-filter-group">
                <label for="filter-q">Buscar por nome ou e-mail</label>
                <input type="text" id="filter-q" name="q" value="<?= htmlspecialchars($filtro['q'] ?? '') ?>" 
                       placeholder="Digite para buscar...">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-search icon"></i>
                Buscar
            </button>
        </form>
        <?php if (!empty($filtro['q'])): ?>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <a href="<?= $basePath ?>/admin/newsletter" style="color: #666; text-decoration: none; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="bi bi-x-circle icon"></i>
                    Limpar filtros
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($inscricoes)): ?>
        <div class="admin-empty-message">
            <i class="bi bi-envelope icon"></i>
            <p>Nenhuma inscrição encontrada.</p>
        </div>
    <?php else: ?>
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Origem</th>
                        <th>Data de Inscrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscricoes as $inscricao): ?>
                        <tr>
                            <td><?= htmlspecialchars($inscricao['nome'] ?: '-') ?></td>
                            <td style="color: #666;"><?= htmlspecialchars($inscricao['email']) ?></td>
                            <td>
                                <span style="background: #e3f2fd; color: #1976d2; padding: 0.25rem 0.75rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500;">
                                    <?= htmlspecialchars($inscricao['origem'] ?: 'home') ?>
                                </span>
                            </td>
                            <td style="color: #666;"><?= date('d/m/Y H:i', strtotime($inscricao['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
/* Fase 10 – Ajustes layout Admin - Newsletter */
.newsletter-page {
    max-width: 1400px;
}
</style>


