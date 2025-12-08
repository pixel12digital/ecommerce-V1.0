<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners - Store Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: #023A8D;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a { color: white; text-decoration: none; }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .filters {
            margin-bottom: 1rem;
        }
        .filters a {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
        }
        .filters a.active {
            background: #F7931E;
            color: white;
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
        }
        .banner-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e0e0e0;
        }
        .banner-info {
            padding: 1rem;
        }
        .banner-info h4 {
            margin-bottom: 0.5rem;
        }
        .banner-info p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .banner-actions {
            margin-top: 1rem;
            display: flex;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    <div class="header">
        <h2>Banners</h2>
        <div>
            <a href="<?= $basePath ?>/admin/home">Home da Loja</a> | 
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3>Banners Configurados</h3>
                <a href="<?= $basePath ?>/admin/home/banners/novo" class="btn btn-primary">+ Novo Banner</a>
            </div>
            
            <div class="filters">
                <a href="<?= $basePath ?>/admin/home/banners" class="<?= empty($tipoFiltro) ? 'active' : '' ?>">Todos</a>
                <a href="<?= $basePath ?>/admin/home/banners?tipo=hero" class="<?= $tipoFiltro === 'hero' ? 'active' : '' ?>">Hero</a>
                <a href="<?= $basePath ?>/admin/home/banners?tipo=portrait" class="<?= $tipoFiltro === 'portrait' ? 'active' : '' ?>">Retrato</a>
            </div>

            <?php if (empty($banners)): ?>
                <p>Nenhum banner configurado ainda.</p>
            <?php else: ?>
                <div class="banner-grid">
                    <?php foreach ($banners as $banner): ?>
                        <div class="banner-card">
                            <img src="<?= $basePath ?>/<?= htmlspecialchars($banner['imagem_desktop']) ?>" 
                                 alt="Banner" class="banner-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div style="display: none; width: 100%; height: 200px; background: #e0e0e0; align-items: center; justify-content: center; color: #999;">
                                Sem imagem
                            </div>
                            <div class="banner-info">
                                <h4><?= htmlspecialchars($banner['titulo'] ?: 'Sem título') ?></h4>
                                <p><strong>Tipo:</strong> <?= $banner['tipo'] === 'hero' ? 'Hero' : 'Retrato' ?></p>
                                <p><strong>Ordem:</strong> <?= $banner['ordem'] ?></p>
                                <p><strong>Status:</strong> <?= $banner['ativo'] ? '<i class="bi bi-check-circle-fill" style="color: #2e7d32;"></i> Ativo' : '<i class="bi bi-x-circle-fill" style="color: #d32f2f;"></i> Inativo' ?></p>
                                <div class="banner-actions">
                                    <a href="<?= $basePath ?>/admin/home/banners/<?= $banner['id'] ?>/editar" 
                                       class="btn btn-secondary">Editar</a>
                                    <form method="POST" 
                                          action="<?= $basePath ?>/admin/home/banners/<?= $banner['id'] ?>/excluir" 
                                          style="display: inline;"
                                          onsubmit="return confirm('Tem certeza que deseja excluir?');">
                                        <button type="submit" class="btn btn-danger">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>


