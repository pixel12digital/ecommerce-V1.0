<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="media-library-page">
    <div class="page-header">
        <h1>Biblioteca de Mídia</h1>
        <p class="page-description">
            Gerencie todas as imagens do seu tenant. Use a busca e filtros para encontrar imagens específicas.
        </p>
    </div>

    <!-- Filtros e Busca -->
    <div class="admin-filters">
        <form method="GET" action="<?= $basePath ?>/admin/midias" id="filtros-form">
            <div class="admin-filter-group">
                <label for="busca">Buscar por nome</label>
                <input 
                    type="text" 
                    id="busca" 
                    name="q" 
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" 
                    placeholder="Digite o nome do arquivo..."
                >
            </div>
            <div class="admin-filter-group">
                <label for="pasta">Filtrar por pasta</label>
                <select id="pasta" name="folder">
                    <option value="">Todas as pastas</option>
                    <?php foreach ($estatisticas as $folder => $stat): ?>
                        <option value="<?= htmlspecialchars($folder) ?>" <?= (isset($_GET['folder']) && $_GET['folder'] === $folder) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stat['label']) ?> (<?= $stat['count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="admin-filter-group">
                <button type="submit" class="admin-btn admin-btn-primary">Filtrar</button>
                <a href="<?= $basePath ?>/admin/midias" class="admin-btn" style="margin-left: 0.5rem;">Limpar</a>
            </div>
        </form>
    </div>

    <!-- Estatísticas -->
    <?php if (!empty($estatisticas)): ?>
        <div class="stats-grid">
            <?php foreach ($estatisticas as $folder => $stat): ?>
                <div class="stat-card">
                    <div class="stat-label"><?= htmlspecialchars($stat['label']) ?></div>
                    <div class="stat-value"><?= $stat['count'] ?> arquivo(s)</div>
                    <div class="stat-size"><?= number_format($stat['totalSize'] / 1024, 2) ?> KB</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Grid de Imagens -->
    <div class="media-grid-container">
        <?php if (empty($imagens)): ?>
            <div class="empty-state">
                <p>Nenhuma imagem encontrada.</p>
                <?php if (!empty($_GET['q']) || !empty($_GET['folder'])): ?>
                    <a href="<?= $basePath ?>/admin/midias" class="admin-btn">Ver todas as imagens</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="media-grid">
                <?php foreach ($imagens as $img): ?>
                    <div class="media-item">
                        <div class="media-thumbnail">
                            <img src="<?= $basePath . htmlspecialchars($img['url']) ?>" 
                                 alt="<?= htmlspecialchars($img['filename']) ?>"
                                 loading="lazy">
                        </div>
                        <div class="media-info">
                            <div class="media-filename" title="<?= htmlspecialchars($img['filename']) ?>">
                                <?= htmlspecialchars($img['filename']) ?>
                            </div>
                            <div class="media-folder">
                                <?= htmlspecialchars($img['folderLabel']) ?>
                            </div>
                            <button type="button" 
                                    class="btn-copy-url" 
                                    data-url="<?= htmlspecialchars($img['url']) ?>"
                                    title="Copiar URL">
                                <i class="bi bi-clipboard icon"></i> Copiar URL
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.media-library-page {
    max-width: 1400px;
}
.page-header {
    margin-bottom: 2rem;
}
.page-header h1 {
    font-size: 2rem;
    color: #333;
    margin-bottom: 0.5rem;
}
.page-description {
    color: #666;
    font-size: 0.95rem;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}
.stat-label {
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
}
.stat-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}
.stat-size {
    font-size: 0.75rem;
    color: #999;
}
.media-grid-container {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 1.5rem;
}
.media-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    background: white;
}
.media-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.media-thumbnail {
    width: 100%;
    padding-top: 100%;
    position: relative;
    overflow: hidden;
    background: #f5f5f5;
}
.media-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-info {
    padding: 0.75rem;
}
.media-filename {
    font-size: 0.875rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    word-break: break-word;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.media-folder {
    font-size: 0.75rem;
    color: #666;
    margin-bottom: 0.5rem;
}
.btn-copy-url {
    width: 100%;
    padding: 0.5rem;
    background: #F7931E;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.875rem;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
.btn-copy-url:hover {
    background: #d67f1a;
}
.empty-state {
    text-align: center;
    padding: 3rem;
    color: #666;
}
.empty-state p {
    font-size: 1.1rem;
    margin-bottom: 1rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Copiar URL ao clicar no botão
    var copyButtons = document.querySelectorAll('.btn-copy-url');
    copyButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.dataset.url;
            var basePath = '<?= $basePath ?>';
            var fullUrl = basePath + url;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(fullUrl).then(function() {
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check icon"></i> Copiado!';
                    btn.style.background = '#4caf50';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.style.background = '#F7931E';
                    }, 2000);
                }).catch(function(err) {
                    console.error('Erro ao copiar:', err);
                    alert('Erro ao copiar URL. Tente novamente.');
                });
            } else {
                // Fallback para navegadores antigos
                var textarea = document.createElement('textarea');
                textarea.value = fullUrl;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check icon"></i> Copiado!';
                    btn.style.background = '#4caf50';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.style.background = '#F7931E';
                    }, 2000);
                } catch (err) {
                    alert('Erro ao copiar URL. URL: ' + fullUrl);
                }
                document.body.removeChild(textarea);
            }
        });
    });
});
</script>

