<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seções de Categorias - Store Admin</title>
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
        .card h3 {
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }
        .section-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            background: #F7931E;
            color: white;
        }
        .success-message {
            background: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
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
        <h2>Seções de Categorias</h2>
        <div>
            <a href="<?= $basePath ?>/admin/home">Home da Loja</a> | 
            <a href="<?= $basePath ?>/admin">Dashboard</a> | 
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">Configurações salvas com sucesso!</div>
        <?php endif; ?>

        <div class="card">
            <h3>Configurar Seções da Home</h3>
            <form method="POST" action="<?= $basePath ?>/admin/home/secoes-categorias">
                <?php foreach ($sections as $section): ?>
                    <div class="section-item">
                        <h4 style="margin-bottom: 1rem;"><?= htmlspecialchars($section['slug_secao']) ?></h4>
                        <input type="hidden" name="sections[<?= $section['id'] ?>][id]" value="<?= $section['id'] ?>">
                        
                        <div class="form-group">
                            <label>Título *</label>
                            <input type="text" name="sections[<?= $section['id'] ?>][titulo]" 
                                   value="<?= htmlspecialchars($section['titulo']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Subtítulo</label>
                            <input type="text" name="sections[<?= $section['id'] ?>][subtitulo]" 
                                   value="<?= htmlspecialchars($section['subtitulo'] ?? '') ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select name="sections[<?= $section['id'] ?>][categoria_id]">
                                    <option value="0">Nenhuma</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" 
                                                <?= $cat['id'] == $section['categoria_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Quantidade de Produtos</label>
                                <input type="number" name="sections[<?= $section['id'] ?>][quantidade_produtos]" 
                                       value="<?= $section['quantidade_produtos'] ?>" min="1" max="20">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="sections[<?= $section['id'] ?>][ativo]" value="1" 
                                       <?= $section['ativo'] ? 'checked' : '' ?>> Ativo
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn">Salvar Todas as Seções</button>
            </form>
        </div>
    </div>
</body>
</html>


