<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="home-config-page">
    <div class="page-description">
        <p>
            Configure os elementos da página inicial da sua loja. Aqui você gerencia as categorias em destaque, 
            seções de produtos, banners e newsletter. As configurações de tema (cores, layout) ficam em "Tema da Loja".
        </p>
    </div>
    
    <div class="cards-grid">
        <a href="<?= $basePath ?>/admin/home/categorias-pills" class="config-card">
            <h3 class="card-title">Faixa de Categorias (Bolotas)</h3>
            <p class="card-description">
                Configure as categorias em destaque logo abaixo do header. Essas são as "bolotas" de categorias 
                que aparecem na home da loja.
            </p>
            <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
        </a>
        
        <a href="<?= $basePath ?>/admin/home/secoes-categorias" class="config-card">
            <h3 class="card-title">Seções de Categorias</h3>
            <p class="card-description">
                Defina as linhas de produtos da home (títulos e categorias). Configure quantos produtos aparecem 
                em cada seção e quais categorias são exibidas.
            </p>
            <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
        </a>
        
        <a href="<?= $basePath ?>/admin/home/banners" class="config-card">
            <h3 class="card-title">Banners da Home</h3>
            <p class="card-description">
                Gerencie os banners do hero (banner principal) e os banners retrato. Configure imagens, textos, 
                links e ordem de exibição.
            </p>
            <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
        </a>
        
        <a href="<?= $basePath ?>/admin/newsletter" class="config-card">
            <h3 class="card-title">Newsletter</h3>
            <p class="card-description">
                Veja os e-mails cadastrados na newsletter da home. Visualize e gerencie os inscritos no formulário 
                de newsletter da página inicial.
            </p>
            <span class="card-link">Gerenciar <i class="bi bi-arrow-right icon"></i></span>
        </a>
    </div>
</div>

<style>
.home-config-page {
    max-width: 1200px;
}
.page-description {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.page-description p {
    color: #666;
    line-height: 1.6;
    margin: 0;
}
.cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
}
.config-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    text-decoration: none;
    color: inherit;
    display: block;
}
.config-card:hover {
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


