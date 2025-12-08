<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home da Loja - Store Admin</title>
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
        .header-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .header-nav a {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .header-nav a:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-header {
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .page-description {
            color: #666;
            font-size: 1rem;
        }
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .card-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        .card-link {
            display: inline-block;
            background: #F7931E;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .card-link:hover {
            background: #e6851a;
        }
    </style>
</head>
<body>
    <?php
    // Obter caminho base se necessário
    $basePath = '';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
        $basePath = '/ecommerce-v1.0/public';
    }
    ?>
    <div class="header">
        <h2>Store Admin</h2>
        <div class="header-nav">
            <a href="<?= $basePath ?>/admin">Dashboard</a>
            <a href="<?= $basePath ?>/admin/produtos">Produtos</a>
            <a href="<?= $basePath ?>/admin/home" style="background: rgba(255, 255, 255, 0.2);">Home da Loja</a>
            <a href="<?= $basePath ?>/admin/pedidos">Pedidos</a>
            <a href="<?= $basePath ?>/admin/tema">Tema da Loja</a>
            <a href="<?= $basePath ?>/admin/logout">Sair</a>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Home da Loja</h1>
            <p class="page-description">
                Configure os elementos da página inicial da sua loja. Aqui você gerencia as categorias em destaque, 
                seções de produtos, banners e newsletter. As configurações de tema (cores, layout) ficam em "Tema da Loja".
            </p>
        </div>
        
        <div class="cards-grid">
            <a href="<?= $basePath ?>/admin/home/categorias-pills" class="card">
                <h3 class="card-title">Faixa de Categorias (Bolotas)</h3>
                <p class="card-description">
                    Configure as categorias em destaque logo abaixo do header. Essas são as "bolotas" de categorias 
                    que aparecem na home da loja.
                </p>
                <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
            </a>
            
            <a href="<?= $basePath ?>/admin/home/secoes-categorias" class="card">
                <h3 class="card-title">Seções de Categorias</h3>
                <p class="card-description">
                    Defina as linhas de produtos da home (títulos e categorias). Configure quantos produtos aparecem 
                    em cada seção e quais categorias são exibidas.
                </p>
                <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
            </a>
            
            <a href="<?= $basePath ?>/admin/home/banners" class="card">
                <h3 class="card-title">Banners da Home</h3>
                <p class="card-description">
                    Gerencie os banners do hero (banner principal) e os banners retrato. Configure imagens, textos, 
                    links e ordem de exibição.
                </p>
                <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
            </a>
            
            <a href="<?= $basePath ?>/admin/newsletter" class="card">
                <h3 class="card-title">Newsletter</h3>
                <p class="card-description">
                    Veja os e-mails cadastrados na newsletter da home. Visualize e gerencie os inscritos no formulário 
                    de newsletter da página inicial.
                </p>
                <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
            </a>
        </div>
    </div>
</body>
</html>


