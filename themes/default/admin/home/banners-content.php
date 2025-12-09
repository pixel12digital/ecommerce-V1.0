<?php
// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}

$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="banners-page">
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3>Banners da Home</h3>
            <div style="display: flex; gap: 0.5rem;">
                <a href="<?= $basePath ?>/admin/home/banners/novo?tipo=hero" class="btn btn-primary">+ Carrossel principal</a>
                <a href="<?= $basePath ?>/admin/home/banners/novo?tipo=portrait" class="btn btn-secondary">+ Banner de apoio</a>
            </div>
        </div>
        
        <div class="filters tabs">
            <a href="<?= $basePath ?>/admin/home/banners" class="tab <?= empty($tipoFiltro) ? 'active' : '' ?>">
                <i class="bi bi-grid icon"></i> Todos
            </a>
            <a href="<?= $basePath ?>/admin/home/banners?tipo=hero" class="tab <?= $tipoFiltro === 'hero' ? 'active' : '' ?>">
                <i class="bi bi-image icon"></i> Carrossel principal (topo)
            </a>
            <a href="<?= $basePath ?>/admin/home/banners?tipo=portrait" class="tab <?= $tipoFiltro === 'portrait' ? 'active' : '' ?>">
                <i class="bi bi-aspect-ratio icon"></i> Banners de apoio (entre seções)
            </a>
        </div>

        <?php if (empty($banners)): ?>
            <p>Nenhum banner configurado ainda.</p>
        <?php else: ?>
            <div class="banner-grid" id="banner-list" data-tipo="<?= htmlspecialchars($tipoFiltro ?: 'all') ?>">
                <?php foreach ($banners as $banner): ?>
                    <div class="banner-card" data-banner-id="<?= $banner['id'] ?>" data-banner-tipo="<?= htmlspecialchars($banner['tipo']) ?>">
                        <div class="banner-thumb">
                            <div class="banner-drag-handle" title="Arrastar para reordenar">
                                <i class="bi bi-grip-vertical" style="font-size: 1.5rem; color: #999; cursor: move;"></i>
                            </div>
                            <?php 
                            // Prioridade: imagem_desktop > imagem_mobile
                            $imagemBanner = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
                            if (!empty($imagemBanner)): 
                            ?>
                                <img src="<?= media_url($imagemBanner) ?>" 
                                     alt="<?= htmlspecialchars($banner['titulo'] ?: 'Banner') ?>" 
                                     class="banner-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php endif; ?>
                            <div class="banner-placeholder" style="<?= (!empty($banner['imagem_desktop']) || !empty($banner['imagem_mobile'])) ? 'display: none;' : '' ?>">
                                <i class="bi bi-image icon" style="font-size: 3rem; color: #ccc;"></i>
                                <span>Sem imagem</span>
                            </div>
                            <div class="banner-badge">
                                <?= $banner['tipo'] === 'hero' ? 'Carrossel principal' : 'Banner de apoio' ?>
                            </div>
                        </div>
                        <div class="banner-info">
                            <h4><?= htmlspecialchars($banner['titulo'] ?: 'Sem título') ?></h4>
                            <?php if ($banner['subtitulo']): ?>
                                <p class="banner-subtitle"><?= htmlspecialchars($banner['subtitulo']) ?></p>
                            <?php endif; ?>
                            <div class="banner-meta">
                                <span class="meta-item">
                                    <i class="bi bi-sort-numeric-down icon"></i> Ordem: <?= $banner['ordem'] ?>
                                </span>
                                <span class="meta-item status-<?= $banner['ativo'] ? 'active' : 'inactive' ?>">
                                    <?php if ($banner['ativo']): ?>
                                        <i class="bi bi-check-circle-fill icon"></i> Ativo
                                    <?php else: ?>
                                        <i class="bi bi-x-circle-fill icon"></i> Inativo
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="banner-actions">
                                <a href="<?= $basePath ?>/admin/home/banners/<?= $banner['id'] ?>/editar" 
                                   class="btn btn-secondary">
                                    <i class="bi bi-pencil icon"></i> Editar
                                </a>
                                <form method="POST" 
                                      action="<?= $basePath ?>/admin/home/banners/<?= $banner['id'] ?>/excluir" 
                                      style="display: inline;"
                                      onsubmit="return confirm('Tem certeza que deseja excluir este banner?');">
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-trash icon"></i> Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.banners-page {
    max-width: 1200px;
}
.card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.card h3 {
    margin-bottom: 0;
}
.filters.tabs {
    margin-bottom: 1.5rem;
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid #e0e0e0;
    padding-bottom: 0;
}
.filters.tabs .tab {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: transparent;
    color: #666;
    text-decoration: none;
    border-radius: 4px 4px 0 0;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}
.filters.tabs .tab:hover {
    background: #f5f5f5;
    color: #333;
}
.filters.tabs .tab.active {
    background: transparent;
    color: var(--pg-admin-primary, #F7931E);
    border-bottom-color: var(--pg-admin-primary, #F7931E);
    font-weight: 600;
}
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}
.btn-primary {
    background: #F7931E;
    color: white;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
.btn-secondary {
    background: #6c757d;
    color: white;
}
.banner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}
.banner-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    background: white;
    transition: box-shadow 0.2s, transform 0.2s;
}
.banner-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}
.banner-thumb {
    position: relative;
    width: 100%;
    height: 200px;
    background: #f5f5f5;
    overflow: hidden;
}
.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.banner-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #999;
    gap: 0.5rem;
}
.banner-badge {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.banner-info {
    padding: 1.25rem;
}
.banner-info h4 {
    margin-bottom: 0.5rem;
    color: #333;
    font-size: 1.1rem;
}
.banner-subtitle {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
    font-style: italic;
}
.banner-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #eee;
}
.meta-item {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: #666;
}
.meta-item.status-active {
    color: #2e7d32;
}
.meta-item.status-inactive {
    color: #d32f2f;
}
.banner-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}
.banner-drag-handle {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 10;
    background: rgba(255, 255, 255, 0.9);
    padding: 0.25rem;
    border-radius: 4px;
    cursor: move;
    opacity: 0;
    transition: opacity 0.2s;
}
.banner-card:hover .banner-drag-handle {
    opacity: 1;
}
.banner-grid.sortable-ghost .banner-card {
    opacity: 0.4;
}
.banner-grid.sortable-drag {
    cursor: grabbing;
}
</style>

<!-- SortableJS para drag-and-drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bannerList = document.getElementById('banner-list');
    if (!bannerList) return;
    
    const tipoFiltro = bannerList.getAttribute('data-tipo');
    // Só ativar drag-and-drop se estiver em uma aba específica (não "Todos")
    if (tipoFiltro === 'all') return;
    
    const tipo = tipoFiltro === 'hero' ? 'hero' : 'portrait';
    
    const sortable = new Sortable(bannerList, {
        handle: '.banner-drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: function(evt) {
            const items = Array.from(bannerList.querySelectorAll('.banner-card'));
            const ids = items.map(card => parseInt(card.getAttribute('data-banner-id')));
            
            // Validar que temos IDs válidos
            if (ids.length === 0 || ids.some(id => isNaN(id) || id <= 0)) {
                console.error('IDs inválidos:', ids);
                location.reload();
                return;
            }
            
            // Enviar nova ordem via AJAX usando FormData
            const formData = new FormData();
            formData.append('tipo', tipo);
            ids.forEach((id) => {
                formData.append('ids[]', id);
            });
            
            fetch('<?= $basePath ?>/admin/home/banners/reordenar', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => {
                // Verificar se a resposta é JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Resposta não é JSON: ' + text.substring(0, 200));
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log('Ordem atualizada com sucesso');
                    // Não recarregar - manter a nova ordem visual
                } else {
                    console.error('Erro ao atualizar ordem:', data.message);
                    alert('Erro ao atualizar ordem: ' + (data.message || 'Erro desconhecido'));
                    // Recarregar página para restaurar ordem original
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erro ao atualizar ordem:', error);
                alert('Erro ao atualizar ordem. A página será recarregada.');
                location.reload();
            });
        }
    });
});
</script>


