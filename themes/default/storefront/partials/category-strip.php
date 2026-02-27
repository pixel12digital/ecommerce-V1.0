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
                           class="pg-category-pill js-category-pill"
                           data-categoria-slug="<?= htmlspecialchars($pill['categoria_slug']) ?>"
                           data-categoria-id="<?= htmlspecialchars($pill['categoria_id'] ?? '') ?>"
                           aria-label="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>">
                            <div class="pg-category-pill-circle">
                                <?php if ($pill['icone_path']): ?>
                                    <img src="<?= media_url($pill['icone_path']) ?>" 
                                         alt="<?= htmlspecialchars($pill['label'] ?: $pill['categoria_nome']) ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="pg-category-pill-placeholder" style="display: none;">
                                        <i class="bi bi-image icon"></i>
                                    </div>
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
                    <?php
                    // Separar categorias pai e filhas
                    $categoriasPai = [];
                    $categoriasFilhas = [];
                    foreach ($allCategories as $cat) {
                        if (empty($cat['categoria_pai_id'])) {
                            $categoriasPai[] = $cat;
                        } else {
                            $categoriasFilhas[$cat['categoria_pai_id']][] = $cat;
                        }
                    }
                    ?>
                    <?php foreach ($categoriasPai as $catPai): ?>
                        <?php
                        $catPaiId = $catPai['categoria_id'] ?? $catPai['id'] ?? null;
                        $catPaiSlug = $catPai['categoria_slug'] ?? '';
                        // Se não tem slug (ex: "Sem Categoria"), usar query string especial
                        $catPaiUrl = $catPaiSlug ? ($basePath . '/produtos?categoria=' . htmlspecialchars($catPaiSlug)) : ($basePath . '/produtos');
                        ?>
                        <li>
                            <a href="<?= $catPaiUrl ?>" 
                               class="pg-category-menu-link">
                                <?= htmlspecialchars($catPai['label'] ?? $catPai['categoria_nome']) ?>
                            </a>
                            <?php if ($catPaiId && !empty($categoriasFilhas[$catPaiId])): ?>
                                <ul class="pg-category-menu-sublist">
                                    <?php foreach ($categoriasFilhas[$catPaiId] as $catFilha): ?>
                                        <li>
                                            <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($catFilha['categoria_slug']) ?>" 
                                               class="pg-category-menu-link pg-category-menu-sublink">
                                                <?= htmlspecialchars($catFilha['label'] ?? $catFilha['categoria_nome']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($categoriasPai) && !empty($categoriasFilhas)): ?>
                        <?php foreach ($categoriasFilhas as $filhas): ?>
                            <?php foreach ($filhas as $catFilha): ?>
                                <li>
                                    <a href="<?= $basePath ?>/produtos?categoria=<?= htmlspecialchars($catFilha['categoria_slug']) ?>" 
                                       class="pg-category-menu-link">
                                        <?= htmlspecialchars($catFilha['label'] ?? $catFilha['categoria_nome']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <li><p style="padding: 8px 10px; color: #666; font-size: 15px;">Nenhuma categoria disponível.</p></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Modal de Seleção de Tamanho -->
<div class="pg-size-filter-modal" id="pgSizeFilterModal" hidden>
    <div class="pg-size-filter-backdrop js-close-size-modal"></div>
    <div class="pg-size-filter-panel" role="dialog" aria-modal="true" aria-labelledby="pgSizeFilterTitle">
        <div class="pg-size-filter-header">
            <h3 id="pgSizeFilterTitle">Filtrar por Tamanho</h3>
            <button type="button" class="pg-size-filter-close js-close-size-modal" aria-label="Fechar">×</button>
        </div>
        <div class="pg-size-filter-body">
            <p style="margin-bottom: 1rem; color: #666;">Selecione os tamanhos que deseja visualizar:</p>
            <div id="pgSizeFilterOptions" class="pg-size-filter-options">
                <!-- Tamanhos serão carregados via JavaScript -->
            </div>
        </div>
        <div class="pg-size-filter-footer">
            <button type="button" class="pg-btn pg-btn-secondary js-close-size-modal">Ver Todos</button>
            <button type="button" class="pg-btn pg-btn-primary js-apply-size-filter">Aplicar Filtro</button>
        </div>
    </div>
</div>

<style>
.pg-size-filter-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pg-size-filter-modal[hidden] {
    display: none;
}

.pg-size-filter-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
}

.pg-size-filter-panel {
    position: relative;
    background: white;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.pg-size-filter-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pg-size-filter-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.pg-size-filter-close {
    background: none;
    border: none;
    font-size: 2rem;
    line-height: 1;
    cursor: pointer;
    color: #666;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.pg-size-filter-close:hover {
    color: #333;
}

.pg-size-filter-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
}

.pg-size-filter-options {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 0.75rem;
}

.pg-size-option {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
    font-weight: 500;
    text-align: center;
}

.pg-size-option:hover {
    border-color: #2E7D32;
    background: #f5f5f5;
}

.pg-size-option.selected {
    border-color: #2E7D32;
    background: #2E7D32;
    color: white;
}

.pg-size-filter-footer {
    padding: 1.5rem;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.pg-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.pg-btn-primary {
    background: #2E7D32;
    color: white;
}

.pg-btn-primary:hover {
    background: #1b5e20;
}

.pg-btn-secondary {
    background: #f5f5f5;
    color: #333;
}

.pg-btn-secondary:hover {
    background: #e0e0e0;
}
</style>

<script>
(function() {
    const modal = document.getElementById('pgSizeFilterModal');
    const optionsContainer = document.getElementById('pgSizeFilterOptions');
    let currentCategorySlug = '';
    let currentCategoryId = '';
    let availableSizes = [];
    
    // Interceptar clique nas bolotas de categoria
    document.querySelectorAll('.js-category-pill').forEach(function(pill) {
        pill.addEventListener('click', function(e) {
            e.preventDefault();
            currentCategorySlug = this.getAttribute('data-categoria-slug');
            currentCategoryId = this.getAttribute('data-categoria-id');
            
            // Buscar tamanhos disponíveis para esta categoria
            fetchAvailableSizes(currentCategoryId, currentCategorySlug);
        });
    });
    
    // Buscar tamanhos disponíveis via AJAX
    function fetchAvailableSizes(categoriaId, categoriaSlug) {
        const basePath = '<?= $basePath ?>';
        
        // Fazer requisição para buscar tamanhos
        fetch(basePath + '/api/tamanhos-categoria?categoria=' + encodeURIComponent(categoriaSlug))
            .then(response => response.json())
            .then(data => {
                if (data.tamanhos && data.tamanhos.length > 0) {
                    availableSizes = data.tamanhos;
                    renderSizeOptions();
                    openModal();
                } else {
                    // Se não houver tamanhos, ir direto para a categoria
                    window.location.href = basePath + '/produtos?categoria=' + encodeURIComponent(categoriaSlug);
                }
            })
            .catch(error => {
                console.error('Erro ao buscar tamanhos:', error);
                // Em caso de erro, ir direto para a categoria
                window.location.href = basePath + '/produtos?categoria=' + encodeURIComponent(categoriaSlug);
            });
    }
    
    // Renderizar opções de tamanho
    function renderSizeOptions() {
        optionsContainer.innerHTML = '';
        availableSizes.forEach(function(size) {
            const option = document.createElement('div');
            option.className = 'pg-size-option';
            option.textContent = size.nome;
            option.setAttribute('data-size-id', size.id);
            
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
            });
            
            optionsContainer.appendChild(option);
        });
    }
    
    // Abrir modal
    function openModal() {
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Fechar modal
    function closeModal() {
        modal.setAttribute('hidden', '');
        document.body.style.overflow = '';
    }
    
    // Event listeners para fechar modal
    document.querySelectorAll('.js-close-size-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const basePath = '<?= $basePath ?>';
            // Ir para categoria sem filtro de tamanho
            window.location.href = basePath + '/produtos?categoria=' + encodeURIComponent(currentCategorySlug);
        });
    });
    
    // Aplicar filtro de tamanho
    document.querySelector('.js-apply-size-filter').addEventListener('click', function() {
        const selectedSizes = Array.from(document.querySelectorAll('.pg-size-option.selected'))
            .map(opt => opt.getAttribute('data-size-id'));
        
        const basePath = '<?= $basePath ?>';
        let url = basePath + '/produtos?categoria=' + encodeURIComponent(currentCategorySlug);
        
        if (selectedSizes.length > 0) {
            selectedSizes.forEach(function(sizeId) {
                url += '&tamanhos[]=' + encodeURIComponent(sizeId);
            });
        }
        
        window.location.href = url;
    });
})();
</script>

