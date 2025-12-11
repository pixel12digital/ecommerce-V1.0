<?php
// Partial: Faixa de Categorias (bolotas)
// Variáveis esperadas: $basePath, $theme, $categoryPills, $allCategories
?>
<!-- Faixa de Categorias -->
<section class="pg-category-strip">
    <div class="pg-category-strip-inner">
        <a href="#" class="pg-category-main-button js-open-category-menu" 
           role="button" 
           aria-expanded="false" 
           aria-controls="pgCategoryMenu"
           aria-label="Abrir menu de categorias">
            <span class="pg-category-main-button-icon">
                <i class="bi bi-list icon"></i>
            </span>
            <span class="pg-category-main-button-label">Categorias</span>
        </a>
        <div class="pg-category-pills-viewport">
            <div class="pg-category-pills-scroll">
                <?php if (!empty($categoryPills)): ?>
                    <?php foreach ($categoryPills as $pill): ?>
                        <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($pill['categoria_slug']) ?>" 
                           class="pg-category-pill"
                           aria-label="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>">
                            <div class="pg-category-pill-circle">
                                <?php if ($pill['icone_path']): ?>
                                    <img src="<?= media_url($pill['icone_path']) ?>" 
                                         alt="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>">
                                <?php else: ?>
                                    <div class="pg-category-pill-placeholder">
                                        <i class="bi bi-image icon"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="pg-category-pill-label">
                                <?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Menu de Categorias (Overlay) -->
<div class="pg-category-menu-overlay" id="pgCategoryMenu" hidden>
    <div class="pg-category-menu-backdrop js-close-category-menu"></div>
    <div class="pg-category-menu-panel" role="dialog" aria-modal="true" aria-labelledby="pgCategoryMenuTitle">
        <div class="pg-category-menu-header">
            <h2 id="pgCategoryMenuTitle">Categorias</h2>
            <button type="button" class="pg-category-menu-close js-close-category-menu" aria-label="Fechar menu de categorias">
                ×
            </button>
        </div>
        <div class="pg-category-menu-body">
            <ul class="pg-category-menu-list">
                <?php if (!empty($allCategories)): ?>
                    <?php foreach ($allCategories as $cat): ?>
                        <li>
                            <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($cat['categoria_slug']) ?>" 
                               class="pg-category-menu-link">
                                <?= htmlspecialchars($cat['label'] ?? $cat['categoria_nome']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li><p style="padding: 8px 10px; color: #666; font-size: 15px;">Nenhuma categoria disponível.</p></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

