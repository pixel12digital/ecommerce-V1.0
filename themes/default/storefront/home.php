<?php
// Helper para URLs de mídia (centralizado)
use App\Support\MediaUrlHelper;

// Função auxiliar para facilitar uso nas views
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}

// Base path
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}

// Capturar conteúdo principal da home em $content
ob_start();
?>

<!-- Hero Slider -->
<?php if (!empty($heroBanners)): ?>
    <section class="home-hero">
        <div class="home-hero-slider" id="home-hero-slider">
            <?php foreach ($heroBanners as $index => $banner): ?>
                <div class="home-hero-slide <?= $index === 0 ? 'active' : '' ?> <?= empty($banner['imagem_desktop']) && empty($banner['imagem_mobile']) ? 'home-hero-slide-text-only' : '' ?>">
                    <?php if (!empty($banner['imagem_desktop']) || !empty($banner['imagem_mobile'])): ?>
                        <picture>
                            <?php if (!empty($banner['imagem_mobile'])): ?>
                                <source media="(max-width: 768px)" srcset="<?= media_url($banner['imagem_mobile']) ?>">
                            <?php endif; ?>
                            <?php 
                            // Fallback: se não houver imagem_desktop, usar imagem_mobile também no desktop
                            $imagemDesktop = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
                            ?>
                            <img src="<?= media_url($imagemDesktop) ?>"
                                 alt="<?= htmlspecialchars($banner['titulo'] ?: 'Banner') ?>"
                                 class="home-hero-image"
                                 loading="eager"
                                 onerror="this.style.display='none'; console.error('Erro ao carregar banner:', this.src);">
                        </picture>
                    <?php endif; ?>
                    <div class="home-hero-content">
                        <?php if (!empty($banner['titulo'])): ?>
                            <h1><?= htmlspecialchars($banner['titulo']) ?></h1>
                        <?php endif; ?>
                        <?php if (!empty($banner['subtitulo'])): ?>
                            <p><?= nl2br(htmlspecialchars($banner['subtitulo'])) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($banner['cta_label']) && !empty($banner['cta_url'])): ?>
                            <a href="<?= $basePath ?><?= htmlspecialchars($banner['cta_url']) ?>" class="hero-button">
                                <?= htmlspecialchars($banner['cta_label']) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php else: ?>
    <!-- Hero padrão se não houver banners cadastrados -->
    <section class="hero">
        <div class="hero-content">
            <h1>Bem-vindo à <?= htmlspecialchars($loja['nome']) ?></h1>
            <p>Os melhores produtos de golfe para você</p>
            <a href="<?= $basePath ?>/produtos" class="hero-button">VER AGORA</a>
        </div>
    </section>
<?php endif; ?>

<!-- Seção Benefícios -->
<section class="benefits">
    <div class="benefits-container">
        <div class="benefit-card">
            <div class="benefit-icon"><i class="bi bi-truck icon store-icon-primary"></i></div>
            <div class="benefit-title">Frete Grátis</div>
            <div class="benefit-text">Acima de R$ 299</div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="bi bi-shield-check icon store-icon-primary"></i></div>
            <div class="benefit-title">Garantia</div>
            <div class="benefit-text">Troca garantida em até 7 dias</div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="bi bi-lightning-charge icon store-icon-primary"></i></div>
            <div class="benefit-title">Entrega Rápida</div>
            <div class="benefit-text">Receba em até 48h</div>
        </div>
        <div class="benefit-card">
            <div class="benefit-icon"><i class="bi bi-lock icon store-icon-primary"></i></div>
            <div class="benefit-title">Compra Segura</div>
            <div class="benefit-text">Seus dados protegidos</div>
        </div>
    </div>
</section>

<!-- Seções de Categorias -->
<?php if (!empty($sections)): ?>
    <?php foreach ($sections as $section): ?>
        <?php if (!empty($section['produtos'])): ?>
            <section class="category-section">
                <div class="section-container">
                    <h2 class="section-title"><?= htmlspecialchars($section['titulo']) ?></h2>
                    <?php if ($section['subtitulo']): ?>
                        <p style="margin-bottom: 1.5rem; color: #666;"><?= htmlspecialchars($section['subtitulo']) ?></p>
                    <?php endif; ?>
                    <div class="products-grid">
                        <?php foreach ($section['produtos'] as $produto): ?>
                            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug']) ?>" class="product-link">
                                <div class="product-card">
                                    <?php if ($produto['imagem_principal'] && !empty($produto['imagem_principal']['caminho_arquivo'])): ?>
                                        <img src="<?= media_url($produto['imagem_principal']['caminho_arquivo']) ?>" 
                                             alt="<?= htmlspecialchars($produto['imagem_principal']['alt_text'] ?? $produto['nome']) ?>"
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="bi bi-image icon" style="font-size: 2rem; color: #ccc;"></i>
                                            <span style="margin-left: 0.5rem;">Sem imagem</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="product-info">
                                        <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                                        <div class="product-price">
                                            <?php if ($produto['preco_promocional']): ?>
                                                <span class="product-price-old">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
                                                <span class="product-price-promo">R$ <?= number_format($produto['preco_promocional'], 2, ',', '.') ?></span>
                                            <?php else: ?>
                                                R$ <?= number_format($produto['preco'] ?? $produto['preco_regular'], 2, ',', '.') ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($section['categoria_slug']): ?>
                        <div style="text-align: center; margin-top: 2rem;">
                            <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($section['categoria_slug']) ?>" 
                               style="color: <?= htmlspecialchars($theme['color_primary']) ?>; font-weight: 600; text-decoration: none;">
                                Ver tudo <i class="bi bi-arrow-right icon"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Banners Retrato -->
<?php if (!empty($portraitBanners)): ?>
    <section class="banners-portrait">
        <div class="banners-container">
            <?php foreach ($portraitBanners as $banner): ?>
                <?php 
                // Fallback: usar imagem_mobile se imagem_desktop não existir
                $imagemBanner = !empty($banner['imagem_desktop']) ? $banner['imagem_desktop'] : ($banner['imagem_mobile'] ?? '');
                ?>
                <div class="banner-portrait" style="<?= !empty($imagemBanner) ? "background-image: url('" . media_url($imagemBanner) . "'); background-size: cover; background-position: center;" : 'background: #f0f0f0;' ?>">
                    <?php if ($banner['titulo']): ?>
                        <h3 style="color: white; margin-bottom: 1rem;"><?= htmlspecialchars($banner['titulo']) ?></h3>
                    <?php endif; ?>
                    <?php if ($banner['cta_label'] && $banner['cta_url']): ?>
                        <a href="<?= $basePath ?><?= htmlspecialchars($banner['cta_url']) ?>" class="banner-link">
                            <?= htmlspecialchars($banner['cta_label']) ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Script adicional para o hero slider
$additionalScripts = '
    <!-- Script do Carrossel Hero -->
    <script>
    (function() {
        \'use strict\';
        
        // Aguardar DOM estar pronto
        function initHeroSlider() {
            try {
                const slider = document.querySelector(\'#home-hero-slider\');
                if (!slider) {
                    console.warn(\'[Hero Slider] Elemento #home-hero-slider não encontrado\');
                    return;
                }

                const slides = Array.from(slider.querySelectorAll(\'.home-hero-slide\'));
                if (slides.length === 0) {
                    console.warn(\'[Hero Slider] Nenhum slide encontrado\');
                    return;
                }

                // Se só tiver um banner, garantir que está visível e sair
                if (slides.length === 1) {
                    slides[0].classList.add(\'active\');
                    return;
                }

                // Inicializar: garantir que primeiro slide está ativo
                let currentIndex = 0;
                slides.forEach((slide, index) => {
                    if (index === 0) {
                        slide.classList.add(\'active\');
                    } else {
                        slide.classList.remove(\'active\');
                    }
                });

                function showSlide(index) {
                    if (index < 0 || index >= slides.length) return;
                    
                    slides.forEach((slide, i) => {
                        if (i === index) {
                            slide.classList.add(\'active\');
                        } else {
                            slide.classList.remove(\'active\');
                        }
                    });
                    currentIndex = index;
                }

                // Trocar slide automaticamente a cada 5 segundos
                let intervalId = setInterval(function() {
                    try {
                        currentIndex = (currentIndex + 1) % slides.length;
                        showSlide(currentIndex);
                    } catch (e) {
                        console.error(\'[Hero Slider] Erro ao trocar slide:\', e);
                        clearInterval(intervalId);
                    }
                }, 5000);
                
                // Limpar intervalo quando a página sair de foco (opcional, economiza recursos)
                document.addEventListener(\'visibilitychange\', function() {
                    if (document.hidden) {
                        clearInterval(intervalId);
                    } else {
                        intervalId = setInterval(function() {
                            try {
                                currentIndex = (currentIndex + 1) % slides.length;
                                showSlide(currentIndex);
                            } catch (e) {
                                console.error(\'[Hero Slider] Erro ao trocar slide:\', e);
                                clearInterval(intervalId);
                            }
                        }, 5000);
                    }
                });
                
            } catch (error) {
                console.error(\'[Hero Slider] Erro ao inicializar carrossel:\', error);
                // Fallback: garantir que pelo menos o primeiro slide está visível
                const slider = document.querySelector(\'#home-hero-slider\');
                if (slider) {
                    const firstSlide = slider.querySelector(\'.home-hero-slide\');
                    if (firstSlide) {
                        firstSlide.classList.add(\'active\');
                    }
                }
            }
        }
        
        // Executar quando DOM estiver pronto
        if (document.readyState === \'loading\') {
            document.addEventListener(\'DOMContentLoaded\', initHeroSlider);
        } else {
            // DOM já está pronto
            initHeroSlider();
        }
    })();
    </script>
';

// Configurar variáveis para o layout base
$pageTitle = htmlspecialchars($loja['nome']);
$showCategoryStrip = true;
$showNewsletter = true;

// Incluir o layout base
include __DIR__ . '/layouts/base.php';
