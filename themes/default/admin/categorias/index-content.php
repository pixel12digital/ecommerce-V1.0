<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="categorias-page">
    <div class="admin-content-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h1 class="admin-page-title" style="font-size: 1.875rem; font-weight: 700; color: #333; margin: 0;">
            Categorias
        </h1>
        <a href="<?= $basePath ?>/admin/categorias/criar" class="admin-btn admin-btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
            <i class="bi bi-plus-circle icon"></i>
            Nova categoria
        </a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="admin-alert admin-alert-<?= htmlspecialchars($messageType ?? 'error') ?>" style="margin-bottom: 2rem;">
            <i class="bi bi-<?= ($messageType ?? 'error') === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-filters">
        <form method="GET" action="<?= $basePath ?>/admin/categorias">
            <div class="admin-filter-group">
                <label for="filter-q">Buscar por nome ou slug</label>
                <input type="text" id="filter-q" name="q" value="<?= htmlspecialchars($filtros['q'] ?? '') ?>" placeholder="Digite para buscar...">
            </div>
            <button type="submit" class="admin-btn admin-btn-primary">
                <i class="bi bi-funnel icon"></i>
                Filtrar
            </button>
            <?php if (!empty($filtros['q'])): ?>
                <a href="<?= $basePath ?>/admin/categorias" class="admin-btn admin-btn-secondary">
                    Limpar filtros
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php 
    $categoriasTree = $categoriasTree ?? [];
    if (!empty($categoriasTree) && is_array($categoriasTree)): 
    ?>
        <div class="admin-table">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Slug</th>
                        <th>Categoria Pai</th>
                        <th>Produtos</th>
                        <th>Subcategorias</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    function renderCategoryRow($categoria, $basePath, $level = 0) {
                        $indent = $level * 20;
                        $prefix = str_repeat('├─ ', $level);
                        ?>
                        <tr>
                            <td style="padding-left: calc(1rem + <?= $indent ?>px);">
                                <?php if ($level > 0): ?>
                                    <span style="color: #999; margin-right: 0.5rem;"><?= $prefix ?></span>
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($categoria['nome']) ?></strong>
                            </td>
                            <td>
                                <code style="background: #f0f0f0; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.875rem;">
                                    <?= htmlspecialchars($categoria['slug']) ?>
                                </code>
                            </td>
                            <td>
                                <?php if ($categoria['categoria_pai_id']): ?>
                                    <span style="color: #666; font-size: 0.875rem;">
                                        <?= htmlspecialchars($categoria['categoria_pai_nome'] ?? 'ID: ' . $categoria['categoria_pai_id']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= $categoria['total_produtos'] ?? 0 ?></strong>
                            </td>
                            <td>
                                <strong><?= $categoria['total_subcategorias'] ?? 0 ?></strong>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="<?= $basePath ?>/admin/categorias/<?= $categoria['id'] ?>/editar" 
                                       class="btn-view">
                                        <i class="bi bi-pencil icon"></i>
                                        Editar
                                    </a>
                                    <button type="button" 
                                            class="btn-delete" 
                                            data-categoria-id="<?= $categoria['id'] ?>"
                                            data-categoria-nome="<?= htmlspecialchars($categoria['nome']) ?>"
                                            onclick="window.abrirModalExclusao(<?= $categoria['id'] ?>, '<?= htmlspecialchars(addslashes($categoria['nome'])) ?>')">
                                        <i class="bi bi-trash icon"></i>
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                        // Renderizar filhos recursivamente
                        if (!empty($categoria['filhos'])) {
                            foreach ($categoria['filhos'] as $filho) {
                                renderCategoryRow($filho, $basePath, $level + 1);
                            }
                        }
                    }
                    
                    foreach ($categoriasTree as $categoria) {
                        renderCategoryRow($categoria, $basePath, 0);
                    }
                    ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="admin-empty-message">
            <i class="bi bi-folder-x icon"></i>
            <p>
                <?php if (!empty($filtros['q'])): ?>
                    Nenhuma categoria encontrada com o termo "<?= htmlspecialchars($filtros['q']) ?>"
                <?php else: ?>
                    Nenhuma categoria cadastrada
                <?php endif; ?>
            </p>
            <?php if (empty($filtros['q'])): ?>
                <a href="<?= $basePath ?>/admin/categorias/criar" class="admin-btn admin-btn-primary" style="margin-top: 1rem;">
                    <i class="bi bi-plus-circle icon"></i>
                    Criar primeira categoria
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de Exclusão -->
<div class="pg-modal-overlay" id="modal-excluir-categoria" style="display: none;">
    <div class="pg-modal-dialog">
        <div class="pg-modal-content">
            <div class="pg-modal-header">
                <h5 class="pg-modal-title">Confirmar Exclusão</h5>
                <button type="button" class="pg-modal-close" onclick="window.fecharModalExclusao()" aria-label="Fechar">&times;</button>
            </div>
            <div class="pg-modal-body">
                <p style="margin: 0; color: #333; font-size: 1rem; line-height: 1.6;">
                    Tem certeza que deseja excluir a categoria <strong id="modal-categoria-nome"></strong>?
                </p>
                <p style="margin: 1rem 0 0 0; color: #d32f2f; font-size: 0.875rem;">
                    <i class="bi bi-exclamation-triangle icon"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="pg-modal-footer">
                <form method="POST" id="form-excluir-categoria" style="display: inline;">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="window.fecharModalExclusao()">
                        Cancelar
                    </button>
                    <button type="submit" class="admin-btn admin-btn-danger">
                        <i class="bi bi-trash icon"></i>
                        Excluir Categoria
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Funções globais para o modal
window.abrirModalExclusao = function(categoriaId, categoriaNome) {
    var modalNome = document.getElementById('modal-categoria-nome');
    var formExcluir = document.getElementById('form-excluir-categoria');
    var modal = document.getElementById('modal-excluir-categoria');
    
    if (modalNome && formExcluir && modal) {
        modalNome.textContent = categoriaNome;
        formExcluir.action = '<?= $basePath ?>/admin/categorias/' + categoriaId + '/excluir';
        modal.style.display = 'flex';
    }
};

window.fecharModalExclusao = function() {
    var modal = document.getElementById('modal-excluir-categoria');
    if (modal) {
        modal.style.display = 'none';
    }
};

// Inicializar após o DOM estar pronto
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('modal-excluir-categoria');
    if (modal) {
        // Fechar modal ao clicar no overlay
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.fecharModalExclusao();
            }
        });
    }
});
</script>

<style>
/* Fase 10 – Ajustes layout Admin - Categorias */
.categorias-page {
    max-width: 1400px;
}

/* Estilos de alert, empty-state e admin-btn já vêm do CSS global */

.btn-view {
    padding: 0.5rem 1rem;
    background: var(--pg-admin-primary);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s, transform 0.2s;
    border: none;
    cursor: pointer;
}

.btn-view:hover {
    background: var(--pg-admin-primary-hover);
    transform: translateY(-1px);
}

.btn-delete {
    padding: 0.5rem 1rem;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s, transform 0.2s;
}

.btn-delete:hover {
    background: #c82333;
    transform: translateY(-1px);
}

/* Modal de Exclusão - Estilos do tema */
.pg-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.pg-modal-dialog {
    width: 100%;
    max-width: 500px;
    display: flex;
    flex-direction: column;
}

.pg-modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.pg-modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--pg-admin-primary);
    color: white;
}

.pg-modal-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
}

.pg-modal-close {
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    color: white;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s;
}

.pg-modal-close:hover {
    background: rgba(255, 255, 255, 0.2);
}

.pg-modal-body {
    padding: 1.5rem;
}

.pg-modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    background: #f9f9f9;
}
</style>

