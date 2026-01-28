<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="atributos-page">
    <div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 class="admin-page-title">Atributos</h1>
        <a href="<?= $basePath ?>/admin/atributos/novo" class="admin-btn admin-btn-primary">
            <i class="bi bi-plus-circle icon"></i>
            Novo atributo
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="admin-alert admin-alert-<?= htmlspecialchars($messageType ?? 'error') ?>">
            <i class="bi bi-<?= ($messageType ?? 'error') === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-filters" style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;">
        <form method="GET" action="<?= $basePath ?>/admin/atributos" style="display: flex; gap: 0.75rem; align-items: center;">
            <div style="position: relative; flex: 1; max-width: 400px;">
                <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #666; font-size: 1.1rem; pointer-events: none;"></i>
                <input type="text" 
                       name="q" 
                       value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" 
                       placeholder="Buscar atributos por nome ou slug..." 
                       style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s, box-shadow 0.2s;"
                       onfocus="this.style.borderColor='var(--pg-admin-primary, #F7931E)'; this.style.boxShadow='0 0 0 3px rgba(247, 147, 30, 0.1)'"
                       onblur="this.style.borderColor='#ddd'; this.style.boxShadow='none'">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary" style="padding: 0.75rem 1.5rem; white-space: nowrap;">
                <i class="bi bi-funnel icon"></i>
                Filtrar
            </button>
            <?php if (!empty($filtros['q'])): ?>
                <a href="<?= $basePath ?>/admin/atributos" class="admin-btn admin-btn-secondary" style="padding: 0.75rem 1.5rem; white-space: nowrap;">
                    <i class="bi bi-x-circle icon"></i>
                    Limpar
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($atributos)): ?>
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Tipo</th>
                        <th>Termos</th>
                        <th>Produtos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($atributos as $attr): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($attr['nome']) ?></strong></td>
                            <td><code><?= htmlspecialchars($attr['slug']) ?></code></td>
                            <td><?= htmlspecialchars($attr['tipo']) ?></td>
                            <td><?= (int)($attr['total_termos'] ?? 0) ?></td>
                            <td><?= (int)($attr['total_produtos'] ?? 0) ?></td>
                            <td>
                                <a href="<?= $basePath ?>/admin/atributos/<?= $attr['id'] ?>/editar" class="admin-btn admin-btn-sm">Editar</a>
                                <form method="POST" action="<?= $basePath ?>/admin/atributos/<?= $attr['id'] ?>/excluir" style="display: inline;" onsubmit="return confirm('Tem certeza?')">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Nenhum atributo cadastrado.</p>
    <?php endif; ?>
</div>
