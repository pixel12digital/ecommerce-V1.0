<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$message = $message ?? null;
$messageType = $messageType ?? 'success';

// Helper para URLs de mídia
use App\Support\MediaUrlHelper;
if (!function_exists('media_url')) {
    function media_url(string $relativePath): string {
        return MediaUrlHelper::url($relativePath);
    }
}
?>

<div class="product-edit-page">
    <?php if ($message): ?>
        <div class="admin-alert admin-alert-<?= $messageType ?>" style="margin-bottom: 2rem;">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <?php
            // Construir URL de retorno preservando contexto de navegação
            $backUrl = $basePath . '/admin/produtos';
            if (isset($navigationContext)) {
                $backParams = [];
                if ($navigationContext['page'] !== null && $navigationContext['page'] > 1) {
                    $backParams['page'] = $navigationContext['page'];
                }
                if (!empty($navigationContext['q'])) {
                    $backParams['q'] = $navigationContext['q'];
                }
                if (!empty($navigationContext['status'])) {
                    $backParams['status'] = $navigationContext['status'];
                }
                if ($navigationContext['categoria_id'] !== null) {
                    $backParams['categoria_id'] = $navigationContext['categoria_id'];
                }
                if ($navigationContext['somente_com_imagem']) {
                    $backParams['somente_com_imagem'] = '1';
                }
                if (!empty($navigationContext['sort'])) {
                    $backParams['sort'] = $navigationContext['sort'];
                }
                if (!empty($navigationContext['direction'])) {
                    $backParams['direction'] = $navigationContext['direction'];
                }
                if (!empty($backParams)) {
                    $backUrl .= '?' . http_build_query($backParams);
                }
            }
            ?>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="admin-btn admin-btn-secondary">
                <i class="bi bi-arrow-left icon"></i>
                Voltar para lista
            </a>
        </div>
        <div>
            <a href="<?= $basePath ?>/produto/<?= htmlspecialchars($produto['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="admin-btn admin-btn-outline">
                <i class="bi bi-eye icon"></i>
                Ver na loja
            </a>
        </div>
    </div>

    <!-- Barra Sticky com Ações Rápidas -->
    <?php if (($produto['tipo'] ?? 'simple') === 'variable'): ?>
    <div id="sticky-actions-bar" style="position: sticky; top: 0; background: white; padding: 1rem; border-bottom: 2px solid #023A8D; box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000; margin-bottom: 2rem; display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; max-width: 1400px; margin: 0 auto;">
            <div style="font-weight: 600; color: #023A8D;">
                <i class="bi bi-lightning-charge icon"></i>
                Ações Rápidas
            </div>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button type="button" id="sticky-save-attributes" class="admin-btn admin-btn-secondary" style="white-space: nowrap;">
                    <i class="bi bi-save icon"></i>
                    Salvar Atributos
                </button>
                <button type="button" id="sticky-save-and-generate" class="admin-btn admin-btn-primary" style="white-space: nowrap;">
                    <i class="bi bi-magic icon"></i>
                    Salvar e Gerar Variações
                </button>
                <button type="button" id="sticky-generate-variations" class="admin-btn admin-btn-outline" style="white-space: nowrap;">
                    <i class="bi bi-magic icon"></i>
                    Gerar Variações
                </button>
                <button type="submit" class="admin-btn admin-btn-success" style="white-space: nowrap;">
                    <i class="bi bi-check-circle icon"></i>
                    Salvar Produto
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= $basePath ?>/admin/produtos/<?= $produto['id'] ?>" enctype="multipart/form-data">
        <!-- Campos hidden para preservar contexto de navegação -->
        <input type="hidden" name="return_to" value="edit" id="return_to">
        <?php if (isset($navigationContext)): ?>
            <?php if ($navigationContext['page'] !== null): ?>
                <input type="hidden" name="nav_page" value="<?= $navigationContext['page'] ?>">
            <?php endif; ?>
            <?php if (!empty($navigationContext['q'])): ?>
                <input type="hidden" name="nav_q" value="<?= htmlspecialchars($navigationContext['q']) ?>">
            <?php endif; ?>
            <?php if (!empty($navigationContext['status'])): ?>
                <input type="hidden" name="nav_status" value="<?= htmlspecialchars($navigationContext['status']) ?>">
            <?php endif; ?>
            <?php if ($navigationContext['categoria_id'] !== null): ?>
                <input type="hidden" name="nav_categoria_id" value="<?= $navigationContext['categoria_id'] ?>">
            <?php endif; ?>
            <?php if ($navigationContext['somente_com_imagem']): ?>
                <input type="hidden" name="nav_somente_com_imagem" value="1">
            <?php endif; ?>
            <?php if (!empty($navigationContext['sort'])): ?>
                <input type="hidden" name="nav_sort" value="<?= htmlspecialchars($navigationContext['sort']) ?>">
            <?php endif; ?>
            <?php if (!empty($navigationContext['direction'])): ?>
                <input type="hidden" name="nav_direction" value="<?= htmlspecialchars($navigationContext['direction']) ?>">
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Seção: Dados Gerais -->
        <div class="info-section">
            <h2 class="section-title">Dados Gerais</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome *</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($produto['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Slug</label>
                    <input type="text" name="slug" value="<?= htmlspecialchars($produto['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Será gerado automaticamente se vazio">
                </div>
                
                <div class="form-group">
                    <label>SKU</label>
                    <input type="text" name="sku" value="<?= htmlspecialchars($produto['sku'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label>Tipo *</label>
                    <select name="tipo" id="produto_tipo" required>
                        <option value="simple" <?= ($produto['tipo'] ?? 'simple') === 'simple' ? 'selected' : '' ?>>Produto Simples</option>
                        <option value="variable" <?= ($produto['tipo'] ?? '') === 'variable' ? 'selected' : '' ?>>Produto Variável</option>
                    </select>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                        Produto variável permite criar variações (ex: tamanhos, cores)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required>
                        <option value="publish" <?= $produto['status'] === 'publish' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('publish') ?>
                        </option>
                        <option value="draft" <?= $produto['status'] === 'draft' ? 'selected' : '' ?>>
                            <?= \App\Support\LangHelper::productStatusLabel('draft') ?>
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="exibir_no_catalogo" value="1" 
                               <?= (!isset($produto['exibir_no_catalogo']) || $produto['exibir_no_catalogo'] == 1) ? 'checked' : '' ?>>
                        <span>Exibir este produto no catálogo da loja</span>
                    </label>
                    <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.25rem;">
                        Quando desmarcado, o produto não aparecerá nas listagens da loja, mas ainda poderá ser acessado diretamente pela URL.
                    </small>
                </div>
            </div>
        </div>

        <!-- Seção: Atributos do Produto (apenas se tipo = variable) -->
        <?php if (($produto['tipo'] ?? 'simple') === 'variable'): ?>
        <div class="info-section" id="secao-atributos">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h2 class="section-title" style="margin: 0;">Atributos do Produto</h2>
                    <p style="color: #666; margin: 0.5rem 0 0 0;">
                        Selecione os atributos que este produto usa e marque quais serão usados para gerar variações.
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" id="btn-save-attributes" class="admin-btn admin-btn-secondary" style="white-space: nowrap;">
                        <i class="bi bi-save icon"></i>
                        Salvar Atributos
                    </button>
                    <button type="button" id="btn-save-and-generate" class="admin-btn admin-btn-primary" style="white-space: nowrap;">
                        <i class="bi bi-magic icon"></i>
                        Salvar e Gerar Variações
                    </button>
                </div>
            </div>

            <?php if (!empty($todosAtributos)): ?>
                <!-- Dropdown para adicionar atributo -->
                <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 4px; border: 1px solid #ddd;">
                    <label style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Adicionar Atributo:</label>
                    <select id="add-atributo-select" style="width: 100%; max-width: 400px; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
                        <option value="">Selecione um atributo para adicionar</option>
                        <?php foreach ($todosAtributos as $attr): ?>
                            <?php if (!in_array($attr['id'], $atributosProdutoIds ?? [])): ?>
                                <option value="<?= $attr['id'] ?>" data-nome="<?= htmlspecialchars($attr['nome']) ?>" data-tipo="<?= htmlspecialchars($attr['tipo']) ?>">
                                    <?= htmlspecialchars($attr['nome']) ?> (<?= htmlspecialchars($attr['tipo']) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="btn-add-atributo" class="admin-btn admin-btn-primary" style="margin-top: 0.5rem;">
                        <i class="bi bi-plus-circle icon"></i>
                        Adicionar Atributo
                    </button>
                </div>

                <div class="atributos-list">
                    <?php 
                    $atributosProdutoIds = $atributosProdutoIds ?? [];
                    $termosPorAtributo = $termosPorAtributo ?? [];
                    $atributosProduto = $atributosProduto ?? [];
                    ?>
                    <?php foreach ($todosAtributos as $attr): ?>
                        <?php 
                        $isSelected = in_array($attr['id'], $atributosProdutoIds);
                        $termosSelecionados = $termosPorAtributo[$attr['id']] ?? [];
                        $termosSelecionadosIds = array_column($termosSelecionados, 'id');
                        $usadoParaVariacao = false;
                        foreach ($atributosProduto as $ap) {
                            $apAtributoId = $ap['atributo_id'] ?? $ap['id'] ?? null;
                            if ($apAtributoId && $apAtributoId == $attr['id'] && ($ap['usado_para_variacao'] ?? 0) == 1) {
                                $usadoParaVariacao = true;
                                break;
                            }
                        }
                        ?>
                        <div class="atributo-item" style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                                <input type="checkbox" name="atributos[]" value="<?= $attr['id'] ?>" 
                                       class="atributo-checkbox" 
                                       data-atributo-id="<?= $attr['id'] ?>"
                                       <?= $isSelected ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($attr['nome']) ?> (<?= htmlspecialchars($attr['tipo']) ?>)</span>
                            </label>

                            <div class="atributo-options" style="margin-left: 2rem; <?= !$isSelected ? 'display: none;' : '' ?>">
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                    <input type="checkbox" name="atributos_para_variacao[<?= $attr['id'] ?>]" value="1"
                                           class="usado-para-variacao-checkbox"
                                           <?= $usadoParaVariacao ? 'checked' : '' ?>>
                                    <span>Usar para gerar variações</span>
                                </label>

                                <div class="termos-list" style="margin-top: 0.5rem;">
                                    <label style="font-size: 0.875rem; color: #666; display: block; margin-bottom: 0.25rem;">Termos disponíveis:</label>
                                    <?php 
                                    $termosDisponiveis = $termosPorAtributoDisponivel[$attr['id']] ?? [];
                                    ?>
                                    <?php if (!empty($termosDisponiveis)): ?>
                                        <div style="display: flex; flex-direction: column; gap: 1rem; max-height: 400px; overflow-y: auto; padding-right: 0.5rem;">
                                            <?php foreach ($termosDisponiveis as $termo): ?>
                                                <?php 
                                                $termoSelecionado = null;
                                                foreach ($termosSelecionados as $ts) {
                                                    if ($ts['id'] == $termo['id']) {
                                                        $termoSelecionado = $ts;
                                                        break;
                                                    }
                                                }
                                                $isChecked = in_array($termo['id'], $termosSelecionadosIds);
                                                ?>
                                                <div class="termo-item" style="border: 1px solid #e0e0e0; padding: 1rem; border-radius: 4px; background: #fafafa;">
                                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 500; margin-bottom: 0.75rem;">
                                                        <input type="checkbox" 
                                                               name="atributo_<?= $attr['id'] ?>_termos[]" 
                                                               value="<?= $termo['id'] ?>"
                                                               class="termo-checkbox"
                                                               data-atributo-id="<?= $attr['id'] ?>"
                                                               data-termo-id="<?= $termo['id'] ?>"
                                                               <?= $isChecked ? 'checked' : '' ?>>
                                                        <span><?= htmlspecialchars($termo['nome']) ?></span>
                                                        <?php if ($termo['valor_cor']): ?>
                                                            <span style="display: inline-block; width: 24px; height: 24px; background: <?= htmlspecialchars($termo['valor_cor']) ?>; border: 2px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);"></span>
                                                        <?php endif; ?>
                                                        <?php if ($termo['imagem']): ?>
                                                            <img src="<?= media_url($termo['imagem']) ?>" alt="<?= htmlspecialchars($termo['nome']) ?>" style="width: 24px; height: 24px; object-fit: cover; border: 2px solid #ddd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                                        <?php endif; ?>
                                                    </label>
                                                    
                                                    <?php if ($isChecked && ($attr['tipo'] === 'color' || $attr['tipo'] === 'image')): ?>
                                                        <div class="termo-config" style="margin-left: 2rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #e0e0e0;">
                                                            <?php if ($attr['tipo'] === 'color'): ?>
                                                                <div style="margin-bottom: 0.75rem;">
                                                                    <label style="display: block; font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Cor HEX:</label>
                                                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                                        <input type="color" 
                                                                               name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_hex" 
                                                                               value="<?= htmlspecialchars($termo['valor_cor'] ?? '#000000') ?>"
                                                                               style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer;">
                                                                        <input type="text" 
                                                                               name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_hex_text" 
                                                                               value="<?= htmlspecialchars($termo['valor_cor'] ?? '') ?>"
                                                                               placeholder="#000000"
                                                                               pattern="^#[0-9A-Fa-f]{6}$"
                                                                               style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-family: monospace;">
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div style="margin-bottom: 0.75rem;">
                                                                <label style="display: block; font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Swatch (imagem miniatura):</label>
                                                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                                    <?php if ($termo['imagem']): ?>
                                                                        <img src="<?= media_url($termo['imagem']) ?>" alt="Swatch" class="swatch-preview" style="width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                                                                    <?php else: ?>
                                                                        <div class="swatch-preview" style="width: 40px; height: 40px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sem</div>
                                                                    <?php endif; ?>
                                                                    <input type="file" 
                                                                           name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_swatch" 
                                                                           accept="image/*"
                                                                           class="swatch-upload"
                                                                           data-atributo-id="<?= $attr['id'] ?>"
                                                                           data-termo-id="<?= $termo['id'] ?>"
                                                                           style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                                                    <?php if ($termo['imagem']): ?>
                                                                        <input type="hidden" name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_swatch_path" value="<?= htmlspecialchars($termo['imagem']) ?>">
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            
                                                            <div>
                                                                <label style="display: block; font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Imagem do produto para este termo (opcional):</label>
                                                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                                                    <?php if ($termoSelecionado && !empty($termoSelecionado['imagem_produto'])): ?>
                                                                        <img src="<?= media_url($termoSelecionado['imagem_produto']) ?>" alt="Imagem produto" class="produto-image-preview" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                                                                    <?php else: ?>
                                                                        <div class="produto-image-preview" style="width: 80px; height: 80px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sem</div>
                                                                    <?php endif; ?>
                                                                    <input type="file" 
                                                                           name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_produto_image" 
                                                                           accept="image/*"
                                                                           class="produto-image-upload"
                                                                           data-atributo-id="<?= $attr['id'] ?>"
                                                                           data-termo-id="<?= $termo['id'] ?>"
                                                                           style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                                                    <?php if ($termoSelecionado && !empty($termoSelecionado['imagem_produto'])): ?>
                                                                        <input type="hidden" name="atributo_<?= $attr['id'] ?>_termo_<?= $termo['id'] ?>_produto_image_path" value="<?= htmlspecialchars($termoSelecionado['imagem_produto']) ?>">
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
                                            <p style="margin: 0 0 0.5rem 0; color: #856404; font-weight: 500; font-size: 0.875rem;">
                                                <i class="bi bi-exclamation-triangle icon"></i>
                                                Nenhum termo cadastrado para este atributo.
                                            </p>
                                            <a href="<?= $basePath ?>/admin/atributos/<?= $attr['id'] ?>/editar" target="_blank" class="admin-btn admin-btn-sm admin-btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                                <i class="bi bi-plus-circle icon"></i>
                                                Cadastrar termos deste atributo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666;">
                    Nenhum atributo cadastrado. <a href="<?= $basePath ?>/admin/atributos/novo">Criar atributo</a>
                </p>
            <?php endif; ?>
        </div>

        <!-- Seção: Variações -->
        <div class="info-section" id="secao-variacoes">
            <h2 class="section-title" id="variacoes">Variações</h2>
            
            <div style="margin-bottom: 1rem;">
                <button type="button" id="btn-gerar-variacoes" class="admin-btn admin-btn-primary">
                    <i class="bi bi-magic icon"></i>
                    Gerar Variações
                </button>
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    <strong>⚠️ IMPORTANTE:</strong> Antes de gerar variações, você deve:<br>
                    1. Adicionar os atributos (Cor, Tamanho) na seção "Atributos do Produto" acima<br>
                    2. Marcar "Usar para gerar variações" para cada atributo<br>
                    3. Selecionar os termos de cada atributo<br>
                    4. <strong>SALVAR o formulário</strong> (botão "Salvar" no final da página)<br>
                    5. Depois, clique em "Gerar Variações" novamente
                </small>
            </div>

            <div id="variacoes-container">
                <?php if (!empty($variacoes)): ?>
                    <table class="variacoes-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f5f5f5;">
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Combinação</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">SKU</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Preço Regular</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Preço Promo</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Gerencia Estoque</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Quantidade</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Backorder</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Imagem</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #ddd;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($variacoes as $variacao): ?>
                                <tr data-variacao-id="<?= $variacao['id'] ?>">
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <?php 
                                        $combinacao = [];
                                        foreach ($variacao['atributos'] ?? [] as $attr) {
                                            $combinacao[] = $attr['atributo_nome'] . ': ' . $attr['termo_nome'];
                                        }
                                        echo htmlspecialchars(implode(', ', $combinacao));
                                        ?>
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <input type="text" 
                                               name="variacoes[<?= $variacao['id'] ?>][sku]" 
                                               value="<?= htmlspecialchars($variacao['sku'] ?? '') ?>"
                                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <input type="text" 
                                               name="variacoes[<?= $variacao['id'] ?>][preco_regular]" 
                                               value="<?= $variacao['preco_regular'] ? number_format($variacao['preco_regular'], 2, ',', '') : '' ?>"
                                               placeholder="Herdar do produto"
                                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <input type="text" 
                                               name="variacoes[<?= $variacao['id'] ?>][preco_promocional]" 
                                               value="<?= $variacao['preco_promocional'] ? number_format($variacao['preco_promocional'], 2, ',', '') : '' ?>"
                                               placeholder="Herdar do produto"
                                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <input type="checkbox" 
                                               name="variacoes[<?= $variacao['id'] ?>][gerencia_estoque]" 
                                               value="1"
                                               <?= ($variacao['gerencia_estoque'] ?? 0) == 1 ? 'checked' : '' ?>>
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <input type="number" 
                                               name="variacoes[<?= $variacao['id'] ?>][quantidade_estoque]" 
                                               value="<?= (int)($variacao['quantidade_estoque'] ?? 0) ?>"
                                               min="0"
                                               style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <select name="variacoes[<?= $variacao['id'] ?>][permite_pedidos_falta]">
                                            <option value="no" <?= ($variacao['permite_pedidos_falta'] ?? 'no') === 'no' ? 'selected' : '' ?>>Não</option>
                                            <option value="notify" <?= ($variacao['permite_pedidos_falta'] ?? '') === 'notify' ? 'selected' : '' ?>>Notificar</option>
                                            <option value="yes" <?= ($variacao['permite_pedidos_falta'] ?? '') === 'yes' ? 'selected' : '' ?>>Sim</option>
                                        </select>
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <?php if (!empty($variacao['imagem'])): ?>
                                                <img src="<?= media_url($variacao['imagem']) ?>" alt="Variação" class="variacao-image-preview" style="width: 60px; height: 60px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;">
                                            <?php else: ?>
                                                <div class="variacao-image-preview" style="width: 60px; height: 60px; border: 1px solid #ddd; border-radius: 4px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.75rem;">Sem</div>
                                            <?php endif; ?>
                                            <input type="file" 
                                                   name="variacoes[<?= $variacao['id'] ?>][imagem]" 
                                                   accept="image/*"
                                                   class="variacao-image-upload"
                                                   data-variacao-id="<?= $variacao['id'] ?>"
                                                   style="flex: 1; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.875rem;">
                                            <?php if (!empty($variacao['imagem'])): ?>
                                                <input type="hidden" name="variacoes[<?= $variacao['id'] ?>][imagem_path]" value="<?= htmlspecialchars($variacao['imagem']) ?>">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td style="padding: 0.75rem; border: 1px solid #ddd;">
                                        <select name="variacoes[<?= $variacao['id'] ?>][status]">
                                            <option value="publish" <?= ($variacao['status'] ?? 'publish') === 'publish' ? 'selected' : '' ?>>Publicado</option>
                                            <option value="draft" <?= ($variacao['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Rascunho</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #666; font-style: italic;">Nenhuma variação gerada. Clique em "Gerar Variações" para criar.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção: Preços -->
        <div class="info-section">
            <h2 class="section-title">Preços</h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Preço Regular *</label>
                    <input type="text" name="preco_regular" id="preco_regular" 
                           value="<?= number_format($produto['preco_regular'], 2, ',', '') ?>" 
                           placeholder="0,00" required
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 380,00)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Preço Promocional</label>
                    <input type="text" name="preco_promocional" id="preco_promocional" 
                           value="<?= $produto['preco_promocional'] ? number_format($produto['preco_promocional'], 2, ',', '') : '' ?>" 
                           placeholder="0,00"
                           class="price-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Digite o preço usando vírgula (ex: 350,00)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Data Início Promoção</label>
                    <input type="date" name="data_promocao_inicio" 
                           value="<?= $produto['data_promocao_inicio'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label>Data Fim Promoção</label>
                    <input type="date" name="data_promocao_fim" 
                           value="<?= $produto['data_promocao_fim'] ?? '' ?>">
                </div>
            </div>
        </div>

        <!-- Seção: Estoque -->
        <?php if (($produto['tipo'] ?? 'simple') === 'variable'): ?>
            <!-- Para produto variável: ocultar bloco de estoque e mostrar apenas aviso -->
            <div class="info-section">
                <h2 class="section-title">Estoque</h2>
                <div style="padding: 1.5rem; background: #e7f3ff; border-left: 4px solid #023A8D; border-radius: 4px;">
                    <p style="margin: 0; color: #023A8D; font-size: 1rem; line-height: 1.6;">
                        <i class="bi bi-info-circle" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;"></i>
                        <strong>Produto Variável:</strong> O estoque é definido por variação. 
                        <a href="#variacoes" style="color: #023A8D; text-decoration: underline; font-weight: 600;">
                            Configure o estoque na seção "Variações"
                        </a>.
                    </p>
                </div>
                <!-- Hidden inputs para manter valores no banco (compatibilidade) -->
                <input type="hidden" name="gerencia_estoque" value="0">
                <input type="hidden" name="quantidade_estoque" value="0">
                <input type="hidden" name="status_estoque" value="outofstock">
                <input type="hidden" name="permite_pedidos_falta" value="no">
            </div>
        <?php else: ?>
            <!-- Para produto simples: mostrar bloco de estoque normalmente -->
            <div class="info-section">
                <h2 class="section-title">Estoque</h2>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="gerencia_estoque" value="1" 
                                   <?= $produto['gerencia_estoque'] ? 'checked' : '' ?>> 
                            Gerencia Estoque
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantidade</label>
                        <input type="number" name="quantidade_estoque" value="<?= $produto['quantidade_estoque'] ?>" 
                               min="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Status de Estoque *</label>
                        <select name="status_estoque" required>
                            <option value="instock" <?= $produto['status_estoque'] === 'instock' ? 'selected' : '' ?>>
                                <?= \App\Support\LangHelper::stockStatusLabel('instock') ?>
                            </option>
                            <option value="outofstock" <?= $produto['status_estoque'] === 'outofstock' ? 'selected' : '' ?>>
                                <?= \App\Support\LangHelper::stockStatusLabel('outofstock') ?>
                            </option>
                            <option value="onbackorder" <?= $produto['status_estoque'] === 'onbackorder' ? 'selected' : '' ?>>
                                <?= \App\Support\LangHelper::stockStatusLabel('onbackorder') ?>
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="permite_pedidos_falta" value="1" 
                                   <?= $produto['permite_pedidos_falta'] ? 'checked' : '' ?>> 
                            Permite Pedidos em Falta
                        </label>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Seção: Dimensões e Frete -->
        <div class="info-section">
            <h2 class="section-title">Dimensões e Frete</h2>
            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                Essas informações são necessárias para o cálculo automático de frete. Preencha com os valores da embalagem do produto.
            </p>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Peso (kg)</label>
                    <input type="number" name="peso" id="peso" 
                           value="<?= $produto['peso'] ? number_format($produto['peso'], 2, '.', '') : '' ?>" 
                           placeholder="0.00" 
                           step="0.01" 
                           min="0"
                           class="dimension-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Peso do produto em quilogramas (ex: 0.5)
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Comprimento (cm)</label>
                    <input type="number" name="comprimento" id="comprimento" 
                           value="<?= $produto['comprimento'] ? number_format($produto['comprimento'], 2, '.', '') : '' ?>" 
                           placeholder="0.00" 
                           step="0.01" 
                           min="0"
                           class="dimension-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Comprimento da embalagem em centímetros
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Largura (cm)</label>
                    <input type="number" name="largura" id="largura" 
                           value="<?= $produto['largura'] ? number_format($produto['largura'], 2, '.', '') : '' ?>" 
                           placeholder="0.00" 
                           step="0.01" 
                           min="0"
                           class="dimension-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Largura da embalagem em centímetros
                    </small>
                </div>
                
                <div class="form-group">
                    <label>Altura (cm)</label>
                    <input type="number" name="altura" id="altura" 
                           value="<?= $produto['altura'] ? number_format($produto['altura'], 2, '.', '') : '' ?>" 
                           placeholder="0.00" 
                           step="0.01" 
                           min="0"
                           class="dimension-input">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        Altura da embalagem em centímetros
                    </small>
                </div>
            </div>
        </div>

        <!-- Seção: Descrições -->
        <div class="info-section">
            <h2 class="section-title">Descrições</h2>
            
            <div class="form-group">
                <label>Descrição Curta</label>
                <textarea name="descricao_curta" rows="3" 
                          placeholder="Breve descrição do produto"><?= htmlspecialchars($produto['descricao_curta'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Descrição Completa</label>
                <textarea name="descricao" rows="10" 
                          placeholder="Descrição detalhada do produto"><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Seção: Categorias -->
        <div class="info-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2 class="section-title" style="margin: 0;">Categorias</h2>
                <a href="<?= $basePath ?>/admin/categorias" 
                   style="font-size: 0.875rem; color: #023A8D; text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;"
                   onmouseover="this.style.textDecoration='underline'"
                   onmouseout="this.style.textDecoration='none'">
                    <i class="bi bi-gear icon"></i>
                    Gerenciar categorias
                </a>
            </div>
            
            <div class="form-group">
                <label>Selecione as categorias deste produto</label>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; padding: 1rem; background: #f9f9f9;">
                    <?php 
                    $categoriasProdutoIds = $categoriasProdutoIds ?? [];
                    $todasCategorias = $todasCategorias ?? [];
                    if (!empty($todasCategorias) && is_array($todasCategorias)):
                        foreach ($todasCategorias as $categoria): 
                            if (!isset($categoria['id']) || !isset($categoria['nome'])) {
                                continue; // Pular itens inválidos
                            }
                            $indent = ($categoria['level'] ?? 0) * 20;
                    ?>
                        <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem; cursor: pointer; border-radius: 4px; transition: background 0.2s; padding-left: <?= $indent + 12 ?>px;" 
                               onmouseover="this.style.background='#f0f0f0'" 
                               onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="categorias[]" value="<?= $categoria['id'] ?>" 
                                   <?= in_array($categoria['id'], $categoriasProdutoIds) ? 'checked' : '' ?>>
                            <span style="font-weight: <?= ($categoria['level'] ?? 0) > 0 ? 'normal' : '600' ?>; color: <?= ($categoria['level'] ?? 0) > 0 ? '#666' : '#333' ?>;">
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </span>
                        </label>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                    <?php if (empty($todasCategorias)): ?>
                        <p style="color: #999; font-style: italic;">Nenhuma categoria cadastrada. Crie categorias primeiro.</p>
                    <?php endif; ?>
                </div>
                <small style="color: #666; font-size: 0.875rem; display: block; margin-top: 0.5rem;">
                    Selecione uma ou mais categorias para organizar seus produtos. Um produto pode pertencer a múltiplas categorias.
                </small>
            </div>
        </div>

        <!-- Seção: Mídia do Produto -->
        <div class="info-section">
            <h2 class="section-title">Mídia do Produto</h2>
            
            <!-- Imagem de Destaque -->
            <div class="media-section">
                <h3 style="margin-bottom: 1rem; font-size: 1.25rem; color: #555;">Imagem de Destaque</h3>
                
                <div class="featured-image-container">
                    <?php if ($imagemPrincipal): ?>
                        <div class="current-image">
                            <img src="<?= media_url($imagemPrincipal['caminho_arquivo']) ?>" 
                                 alt="Imagem de destaque atual" 
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E'">
                            <div class="image-label">Imagem atual</div>
                        </div>
                    <?php else: ?>
                        <div class="current-image placeholder">
                            <div class="placeholder-content">
                                <i class="bi bi-image icon" style="font-size: 3rem; color: #999;"></i>
                                <div class="image-label">Sem imagem de destaque</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="image-actions">
                        <div class="form-group">
                            <label>Escolher imagem de destaque</label>
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <input type="text" 
                                       id="imagem_destaque_path_display" 
                                       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>" 
                                       placeholder="Selecione uma imagem na biblioteca"
                                       readonly
                                       style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem; background: #f8f9fa;">
                                <!-- Campo hidden que será enviado no POST -->
                                <input type="hidden" 
                                       name="imagem_destaque_path" 
                                       id="imagem_destaque_path" 
                                       value="<?= htmlspecialchars($produto['imagem_principal'] ?? '') ?>">
                                <!-- Campo hidden para sinalizar remoção da imagem de destaque -->
                                <input type="hidden" 
                                       name="remove_featured" 
                                       id="remove_featured" 
                                       value="0">
                                <button type="button" 
                                        class="js-open-media-library admin-btn admin-btn-primary" 
                                        data-media-target="#imagem_destaque_path"
                                        data-folder="produtos"
                                        data-preview="#imagem_destaque_preview"
                                        style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                                    <i class="bi bi-image icon"></i> Escolher da biblioteca
                                </button>
                                <?php if ($imagemPrincipal): ?>
                                <button type="button" 
                                        id="btn-remove-featured"
                                        class="admin-btn" 
                                        onclick="removeFeaturedImage()"
                                        style="padding: 0.75rem 1.5rem; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                                    <i class="bi bi-trash icon"></i> Remover imagem
                                </button>
                                <?php endif; ?>
                            </div>
                            <div id="imagem_destaque_preview" style="margin-top: 0.75rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher uma imagem da biblioteca de mídia.
                    </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Galeria de Imagens -->
            <div class="media-section" style="margin-top: 2rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.25rem; color: #555;">Galeria de Imagens</h3>
                
                <?php if (!empty($galeria)): ?>
                    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                        <i class="bi bi-info-circle icon"></i> Arraste as imagens para reordená-las
                    </p>
                    <div class="gallery-grid product-gallery" id="product-gallery">
                        <?php foreach ($galeria as $index => $img): ?>
                            <div class="gallery-item product-gallery__item" 
                                 data-imagem-id="<?= (int)$img['id'] ?>"
                                 draggable="true">
                                <div class="product-gallery__thumb">
                                    <img src="<?= media_url($img['caminho_arquivo']) ?>" 
                                         alt="Imagem da galeria"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'150\' height=\'150\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImagem%3C/text%3E%3C/svg%3E'">
                                </div>
                                <div class="gallery-item-actions">
                                    <button type="button" class="btn-set-main" 
                                            onclick="setMainFromGallery(<?= $img['id'] ?>)"
                                            title="Definir como imagem de destaque">
                                        <i class="bi bi-star-fill icon"></i>
                                    </button>
                                    <label class="btn-remove">
                                        <input type="checkbox" name="remove_imagens[]" value="<?= $img['id'] ?>">
                                        <i class="bi bi-trash icon"></i>
                                    </label>
                                </div>
                                <input type="hidden"
                                       name="galeria_ordem[<?= (int)$img['id'] ?>]"
                                       value="<?= (int)($img['ordem'] ?? ($index + 1)) ?>"
                                       class="product-gallery__ordem-input">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666; margin-bottom: 1rem;">Nenhuma imagem na galeria.</p>
                <?php endif; ?>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label>Adicionar imagens à galeria</label>
                    <div style="display: flex; gap: 0.5rem; align-items: flex-start; margin-bottom: 0.75rem;">
                        <button type="button" 
                                class="js-open-media-library admin-btn admin-btn-primary" 
                                data-media-target="#galeria_paths_container"
                                data-folder="produtos"
                                data-multiple="true"
                                style="padding: 0.75rem 1.5rem; background: var(--pg-admin-primary, #F7931E); color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1rem; white-space: nowrap;">
                            <i class="bi bi-image icon"></i> Adicionar da biblioteca
                        </button>
                    </div>
                    <!-- Container para inputs hidden das imagens da biblioteca -->
                    <div id="galeria_paths_container" style="display: none;">
                        <?php 
                        // Preencher com imagens existentes da galeria para preservar ao salvar
                        foreach ($galeria as $img): 
                        ?>
                            <input type="hidden" 
                                   name="galeria_paths[]" 
                                   value="<?= htmlspecialchars($img['caminho_arquivo']) ?>"
                                   data-imagem-id="<?= (int)$img['id'] ?>">
                        <?php endforeach; ?>
                    </div>
                    <!-- Container para preview das novas imagens da biblioteca -->
                    <div id="galeria_preview_container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem; margin-top: 1rem;"></div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Use o botão acima para escolher imagens da biblioteca de mídia.
                    </small>
                </div>
            </div>

            <!-- Campo oculto para definir main da galeria -->
            <input type="hidden" name="main_from_gallery_id" id="main_from_gallery_id" value="">
        </div>

        <!-- Seção: Vídeos do Produto -->
        <div class="info-section">
            <h2 class="section-title">Vídeos do Produto</h2>
            
            <?php if (!empty($videos)): ?>
                <div class="videos-list">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <div class="video-fields">
                                <div class="form-group">
                                    <label>Título (opcional)</label>
                                    <input type="text" name="videos[<?= $video['id'] ?>][titulo]" 
                                           value="<?= htmlspecialchars($video['titulo'] ?? '') ?>" 
                                           placeholder="Ex: Vídeo demonstrativo">
                                </div>
                                <div class="form-group">
                                    <label>URL do Vídeo *</label>
                                    <input type="url" name="videos[<?= $video['id'] ?>][url]" 
                                           value="<?= htmlspecialchars($video['url']) ?>" 
                                           placeholder="https://www.youtube.com/watch?v=..." required>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="videos[<?= $video['id'] ?>][ativo]" value="1" 
                                               <?= $video['ativo'] ? 'checked' : '' ?>> 
                                        Ativo
                                    </label>
                                </div>
                            </div>
                            <div class="video-actions">
                                <label class="btn-remove-video">
                                    <input type="checkbox" name="remove_videos[]" value="<?= $video['id'] ?>">
                                    <i class="bi bi-trash icon"></i> Remover
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #666; margin-bottom: 1rem;">Nenhum vídeo cadastrado.</p>
            <?php endif; ?>
            
            <!-- Novo vídeo -->
            <div class="new-videos-section" style="margin-top: 2rem;">
                <h4 style="margin-bottom: 1rem; font-size: 1.1rem; color: #555;">Adicionar Novo Vídeo</h4>
                <div id="new-videos-container">
                    <div class="video-item new-video">
                        <div class="video-fields">
                            <div class="form-group">
                                <label>Título (opcional)</label>
                                <input type="text" name="novo_videos[0][titulo]" placeholder="Ex: Vídeo demonstrativo">
                            </div>
                            <div class="form-group">
                                <label>URL do Vídeo *</label>
                                <input type="url" name="novo_videos[0][url]" 
                                       placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-add-video" onclick="addNewVideoField()">
                    <i class="bi bi-plus-circle icon"></i> Adicionar mais um vídeo
                </button>
            </div>
        </div>

        <!-- Informações somente leitura -->
        <div class="info-section" style="background: #f8f9fa;">
            <h2 class="section-title">Informações do Sistema</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">ID Interno</span>
                    <span class="info-value">#<?= $produto['id'] ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">ID Original WP</span>
                    <span class="info-value"><?= $produto['id_original_wp'] ?? '-' ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tipo</span>
                    <span class="info-value"><?= htmlspecialchars($produto['tipo'] ?? 'simple', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <?php if (!empty($categorias)): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Categorias</span>
                        <span class="info-value">
                            <?php foreach ($categorias as $cat): ?>
                                <span class="badge-category"><?= htmlspecialchars($cat['nome']) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($tags)): ?>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <span class="info-label">Tags</span>
                        <span class="info-value">
                            <?php foreach ($tags as $tag): ?>
                                <span class="badge-tag"><?= htmlspecialchars($tag['nome']) ?></span>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <button type="button" 
                        class="admin-btn admin-btn-danger js-open-excluir-produto-modal"
                        data-id="<?= (int)$produto['id'] ?>"
                        data-nome="<?= htmlspecialchars($produto['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        style="padding: 0.875rem 1.5rem;">
                    <i class="bi bi-trash icon"></i>
                    Excluir Produto
                </button>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <button type="submit" class="admin-btn admin-btn-primary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                    <i class="bi bi-check-circle icon"></i>
                    Salvar Alterações
                </button>
                <button type="submit" class="admin-btn admin-btn-secondary" style="padding: 1rem 2rem; font-size: 1.1rem;" onclick="document.getElementById('return_to').value = 'list'; return true;">
                    <i class="bi bi-check-circle icon"></i>
                    Salvar e Voltar
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal de Exclusão de Produto (reutilizado da listagem) -->
<div class="pg-modal-overlay" id="modal-excluir-produto" style="display: none;">
    <div class="pg-modal-dialog">
        <div class="pg-modal-content">
            <div class="pg-modal-header">
                <h5 class="pg-modal-title">Excluir Produto</h5>
                <button type="button" class="pg-modal-close" onclick="window.fecharModalExclusaoProduto()" aria-label="Fechar">&times;</button>
            </div>
            <div class="pg-modal-body">
                <p style="margin: 0; color: #333; font-size: 1rem; line-height: 1.6;">
                    Tem certeza que deseja excluir o produto <strong id="modal-produto-nome"></strong>?
                </p>
                <p style="margin: 1rem 0 0 0; color: #d32f2f; font-size: 0.875rem;">
                    <i class="bi bi-exclamation-triangle icon"></i>
                    Esta ação não pode ser desfeita.
                </p>
            </div>
            <div class="pg-modal-footer">
                <form method="POST" id="form-excluir-produto" style="display: inline;">
                    <button type="button" class="admin-btn admin-btn-secondary" onclick="window.fecharModalExclusaoProduto()">
                        Cancelar
                    </button>
                    <button type="submit" class="admin-btn admin-btn-danger">
                        <i class="bi bi-trash icon"></i>
                        Excluir Produto
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Funções para modal de exclusão (compatível com products.js)
if (typeof window.fecharModalExclusaoProduto === 'undefined') {
    window.fecharModalExclusaoProduto = function() {
        var modal = document.getElementById('modal-excluir-produto');
        if (modal) {
            modal.style.display = 'none';
        }
    };
}

// Abrir modal de exclusão
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        var deleteBtn = e.target.closest('.js-open-excluir-produto-modal');
        if (deleteBtn) {
            e.preventDefault();
            var produtoId = deleteBtn.getAttribute('data-id');
            var produtoNome = deleteBtn.getAttribute('data-nome');
            
            var modal = document.getElementById('modal-excluir-produto');
            var modalNome = document.getElementById('modal-produto-nome');
            var formExcluir = document.getElementById('form-excluir-produto');
            
            if (modal && modalNome && formExcluir) {
                modalNome.textContent = produtoNome;
                
                // Obter basePath
                var basePath = '';
                var scripts = document.getElementsByTagName('script');
                for (var i = 0; i < scripts.length; i++) {
                    var src = scripts[i].src || '';
                    if (src.indexOf('/admin/js/') !== -1) {
                        var match = src.match(/^(.+)\/admin\/js\//);
                        if (match) {
                            basePath = match[1];
                            break;
                        }
                    }
                }
                // Fallback: tentar obter do contexto PHP se disponível
                if (!basePath && typeof window.basePath !== 'undefined') {
                    basePath = window.basePath;
                }
                
                formExcluir.action = basePath + '/admin/produtos/' + produtoId + '/excluir';
                modal.style.display = 'flex';
            }
        }
    });
    
    // Fechar modal ao clicar no overlay
    var modalExcluir = document.getElementById('modal-excluir-produto');
    if (modalExcluir) {
        modalExcluir.addEventListener('click', function(e) {
            if (e.target === this) {
                window.fecharModalExclusaoProduto();
            }
        });
    }
});
</script>

<style>
/* Estilos do modal (se não estiverem no CSS global) */
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
}

.admin-btn-danger {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.625rem 1.25rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
}

.admin-btn-danger:hover {
    background: #c82333;
}
</style>
</div>

<script>
let newVideoIndex = 1;

function addNewVideoField() {
    const container = document.getElementById('new-videos-container');
    const newVideo = document.createElement('div');
    newVideo.className = 'video-item new-video';
    newVideo.innerHTML = `
        <div class="video-fields">
            <div class="form-group">
                <label>Título (opcional)</label>
                <input type="text" name="novo_videos[${newVideoIndex}][titulo]" placeholder="Ex: Vídeo demonstrativo">
            </div>
            <div class="form-group">
                <label>URL do Vídeo *</label>
                <input type="url" name="novo_videos[${newVideoIndex}][url]" 
                       placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
            </div>
        </div>
        <div class="video-actions">
            <button type="button" class="btn-remove-video" onclick="this.closest('.video-item').remove()">
                <i class="bi bi-trash icon"></i> Remover
            </button>
        </div>
    `;
    container.appendChild(newVideo);
    newVideoIndex++;
}

function setMainFromGallery(imageId) {
    document.getElementById('main_from_gallery_id').value = imageId;
    // Marcar visualmente a seleção
    document.querySelectorAll('.btn-set-main').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.closest('.btn-set-main').classList.add('active');
    
    // Mostrar mensagem
    alert('Imagem selecionada! Clique em "Salvar alterações" para aplicar.');
}

// Drag-and-Drop para Galeria
(function() {
    const gallery = document.getElementById('product-gallery');
    if (!gallery) return;
    
    let draggedElement = null;
    let draggedIndex = null;
    
    const items = gallery.querySelectorAll('.product-gallery__item');
    
    items.forEach((item, index) => {
        // Drag Start
        item.addEventListener('dragstart', function(e) {
            draggedElement = this;
            draggedIndex = Array.from(gallery.children).indexOf(this);
            this.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
        });
        
        // Drag End
        item.addEventListener('dragend', function(e) {
            this.classList.remove('is-dragging');
            gallery.querySelectorAll('.product-gallery__item').forEach(el => {
                el.classList.remove('drag-over');
            });
            updateOrder();
        });
        
        // Drag Over
        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            
            const afterElement = getDragAfterElement(gallery, e.clientY);
            const dragging = document.querySelector('.is-dragging');
            
            if (afterElement == null) {
                gallery.appendChild(dragging);
            } else {
                gallery.insertBefore(dragging, afterElement);
            }
        });
        
        // Drag Enter
        item.addEventListener('dragenter', function(e) {
            e.preventDefault();
            if (this !== draggedElement) {
                this.classList.add('drag-over');
            }
        });
        
        // Drag Leave
        item.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        // Drop
        item.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
        });
    });
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.product-gallery__item:not(.is-dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    function updateOrder() {
        const items = gallery.querySelectorAll('.product-gallery__item');
        items.forEach((item, index) => {
            const input = item.querySelector('.product-gallery__ordem-input');
            if (input) {
                // Ordem começa em 1 (a imagem principal tem ordem 0)
                input.value = index + 1;
            }
        });
    }
})();

// Gerenciar comportamento do campo Status de Estoque baseado em Gerencia Estoque
(function() {
    var gerenciaEstoqueCheckbox = document.querySelector('input[name="gerencia_estoque"]');
    var statusEstoqueSelect = document.querySelector('select[name="status_estoque"]');
    var statusEstoqueGroup = statusEstoqueSelect ? statusEstoqueSelect.closest('.form-group') : null;
    
    function updateStatusEstoqueField() {
        if (!gerenciaEstoqueCheckbox || !statusEstoqueSelect) return;
        
        var isGerenciando = gerenciaEstoqueCheckbox.checked;
        
        if (isGerenciando) {
            // Desabilitar select e adicionar texto explicativo
            statusEstoqueSelect.disabled = true;
            statusEstoqueSelect.style.opacity = '0.6';
            statusEstoqueSelect.style.cursor = 'not-allowed';
            
            // Adicionar ou atualizar texto de ajuda
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (!helpText) {
                helpText = document.createElement('small');
                helpText.className = 'help-text-estoque';
                helpText.style.cssText = 'color: #666; display: block; margin-top: 0.5rem; font-style: italic;';
                helpText.textContent = 'Quando o gerenciamento de estoque está ativo, o status é definido automaticamente com base na quantidade em estoque.';
                statusEstoqueGroup.appendChild(helpText);
            }
            helpText.style.display = 'block';
        } else {
            // Habilitar select e remover texto de ajuda
            statusEstoqueSelect.disabled = false;
            statusEstoqueSelect.style.opacity = '1';
            statusEstoqueSelect.style.cursor = 'pointer';
            
            var helpText = statusEstoqueGroup.querySelector('.help-text-estoque');
            if (helpText) {
                helpText.style.display = 'none';
            }
        }
    }
    
    // Aplicar ao carregar a página
    if (gerenciaEstoqueCheckbox && statusEstoqueSelect) {
        updateStatusEstoqueField();
        
        // Aplicar quando checkbox mudar
        gerenciaEstoqueCheckbox.addEventListener('change', updateStatusEstoqueField);
    }
})();

// Máscara de preço (aceitar vírgula, converter para ponto antes de enviar)
(function() {
    function formatPrice(value) {
        // Remove tudo exceto números e vírgula
        value = value.replace(/[^\d,]/g, '');
        // Substitui múltiplas vírgulas por uma única
        value = value.replace(/,+/g, ',');
        // Garante que há no máximo uma vírgula
        var parts = value.split(',');
        if (parts.length > 2) {
            value = parts[0] + ',' + parts.slice(1).join('');
        }
        return value;
    }
    
    function convertPriceToFloat(value) {
        if (!value || value.trim() === '') return '';
        // Converte vírgula para ponto
        return value.replace(',', '.');
    }
    
    var precoRegular = document.getElementById('preco_regular');
    var precoPromocional = document.getElementById('preco_promocional');
    
    if (precoRegular) {
        precoRegular.addEventListener('input', function() {
            this.value = formatPrice(this.value);
        });
        
        precoRegular.addEventListener('blur', function() {
            if (this.value && !this.value.includes(',')) {
                // Se não tem vírgula, adiciona ,00
                this.value = this.value + ',00';
            }
        });
    }
    
    if (precoPromocional) {
        precoPromocional.addEventListener('input', function() {
            this.value = formatPrice(this.value);
        });
        
        precoPromocional.addEventListener('blur', function() {
            if (this.value && !this.value.includes(',')) {
                this.value = this.value + ',00';
            }
        });
    }
    
    // Converter antes de enviar formulário
    var form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (precoRegular && precoRegular.value) {
                precoRegular.value = convertPriceToFloat(precoRegular.value);
            }
            if (precoPromocional && precoPromocional.value) {
                precoPromocional.value = convertPriceToFloat(precoPromocional.value);
            }
            
            // Log para debug: verificar quantos inputs de galeria estão sendo enviados
            var galeriaInputs = document.querySelectorAll('#galeria_paths_container input[name="galeria_paths[]"]');
            console.log('[Form Submit] 📋 VERIFICAÇÃO ANTES DO SUBMIT:');
            console.log('[Form Submit] Total de inputs de galeria que serão enviados:', galeriaInputs.length);
            var galeriaPaths = [];
            galeriaInputs.forEach(function(input) {
                var imageId = input.getAttribute('data-imagem-id') || 'nova';
                galeriaPaths.push(input.value);
                console.log('[Form Submit]   - Caminho:', input.value, '(ID:', imageId + ')');
            });
            console.log('[Form Submit] Caminhos de galeria:', galeriaPaths);
            
            // Verificar se há imagens marcadas para remoção (para log, mas não é mais necessário)
            var removeInputs = document.querySelectorAll('input[name="remove_imagens[]"]:checked');
            console.log('[Form Submit] Imagens marcadas para remoção (checkbox):', removeInputs.length);
            if (removeInputs.length > 0) {
                console.log('[Form Submit] ⚠️ ATENÇÃO: Há checkboxes marcados, mas inputs hidden já foram removidos');
            }
        });
    }
})();

// Função para remover imagem de destaque
window.removeFeaturedImage = function() {
    console.log('[Imagem Destaque] 🔴 CLICK NO BOTAO DE REMOCAO DA IMAGEM DE DESTAQUE');
    
    var imagemDestaqueInput = document.getElementById('imagem_destaque_path');
    var imagemDestaqueDisplay = document.getElementById('imagem_destaque_path_display');
    var removeFeaturedInput = document.getElementById('remove_featured');
    var previewContainer = document.getElementById('imagem_destaque_preview');
    var currentImageContainer = document.querySelector('.current-image');
    var btnRemove = document.getElementById('btn-remove-featured');
    
    if (!removeFeaturedInput) {
        console.error('[Imagem Destaque] ❌ Campo remove_featured não encontrado!');
        return;
    }
    
    // Marcar para remoção
    removeFeaturedInput.value = '1';
    console.log('[Imagem Destaque] ✅ Campo remove_featured marcado como 1');
    
    // Limpar campos
    if (imagemDestaqueInput) {
        imagemDestaqueInput.value = '';
        console.log('[Imagem Destaque] ✅ Campo imagem_destaque_path limpo');
    }
    if (imagemDestaqueDisplay) {
        imagemDestaqueDisplay.value = '';
        console.log('[Imagem Destaque] ✅ Campo imagem_destaque_path_display limpo');
    }
    
    // Limpar preview
    if (previewContainer) {
        previewContainer.innerHTML = '';
        console.log('[Imagem Destaque] ✅ Preview limpo');
    }
    
    // Atualizar visual para placeholder
    if (currentImageContainer) {
        currentImageContainer.classList.add('placeholder');
        currentImageContainer.style.opacity = '0.5';
        currentImageContainer.style.border = '2px solid #dc3545';
        currentImageContainer.innerHTML = 
            '<div class="placeholder-content" style="position: relative;">' +
            '<i class="bi bi-image icon" style="font-size: 3rem; color: #999;"></i>' +
            '<div class="image-label">Sem imagem de destaque</div>' +
            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 53, 69, 0.9); color: white; padding: 0.5rem 1rem; border-radius: 4px; font-size: 0.875rem; font-weight: bold; z-index: 10;">Será removida</div>' +
            '</div>';
        console.log('[Imagem Destaque] ✅ Visual atualizado para placeholder com indicador de remoção');
    }
    
    // Esconder botão de remoção (opcional - pode manter visível até salvar)
    // if (btnRemove) {
    //     btnRemove.style.display = 'none';
    // }
    
    console.log('[Imagem Destaque] ✅ Remoção configurada. Ao salvar, a imagem será removida do banco.');
};

// Atualizar preview da imagem de destaque quando selecionada
(function() {
    var imagemDestaqueInput = document.getElementById('imagem_destaque_path');
    var imagemDestaqueDisplay = document.getElementById('imagem_destaque_path_display');
    var removeFeaturedInput = document.getElementById('remove_featured');
    var btnRemove = document.getElementById('btn-remove-featured');
    
    function updateImagePreview(url) {
        if (!url) return;
        
        // Atualizar campo de exibição
        if (imagemDestaqueDisplay) {
            imagemDestaqueDisplay.value = url;
        }
        
        // Construir URL completa da imagem
        var imageUrl = url;
        if (!imageUrl.startsWith('/')) {
            imageUrl = '/' + imageUrl;
        }
        
        // Atualizar preview pequeno (#imagem_destaque_preview)
        var previewSmall = document.getElementById('imagem_destaque_preview');
        if (previewSmall) {
            previewSmall.innerHTML = '<img src="' + imageUrl + '" alt="Preview" style="max-width: 200px; max-height: 200px; border-radius: 4px; margin-top: 0.5rem; border: 1px solid #ddd; padding: 4px;" onerror="this.parentElement.innerHTML=\'<div style=\\\'color: #999; padding: 1rem; text-align: center;\\\'>Erro ao carregar imagem</div>\'">';
        }
        
        // Atualizar preview principal (.current-image img)
        var mainPreview = document.querySelector('.current-image img');
        if (mainPreview) {
            mainPreview.src = imageUrl;
            mainPreview.onerror = function() {
                this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E';
            };
            // Remover classe placeholder se existir
            var currentImageContainer = mainPreview.closest('.current-image');
            if (currentImageContainer) {
                currentImageContainer.classList.remove('placeholder');
            }
        } else {
            // Se não existe img, pode ser placeholder - substituir
            var placeholderContainer = document.querySelector('.current-image.placeholder');
            if (placeholderContainer) {
                placeholderContainer.classList.remove('placeholder');
                var imgElement = document.createElement('img');
                imgElement.src = imageUrl;
                imgElement.alt = 'Imagem de destaque';
                imgElement.onerror = function() {
                    this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'300\'%3E%3Crect fill=\'%23ddd\' width=\'300\' height=\'300\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3ESem imagem%3C/text%3E%3C/svg%3E';
                };
                placeholderContainer.innerHTML = '';
                placeholderContainer.appendChild(imgElement);
                var label = document.createElement('div');
                label.className = 'image-label';
                label.textContent = 'Imagem atual';
                placeholderContainer.appendChild(label);
            }
        }
    }
    
    if (imagemDestaqueInput) {
        // Listener para mudanças no campo hidden
        imagemDestaqueInput.addEventListener('change', function() {
            updateImagePreview(this.value);
        });
        
        // Também escutar eventos customizados do media-picker
        imagemDestaqueInput.addEventListener('input', function() {
            updateImagePreview(this.value);
        });
        
        // Carregar preview inicial se houver valor
        if (imagemDestaqueInput.value) {
            updateImagePreview(imagemDestaqueInput.value);
        }
    }
})();

// Processar seleção múltipla da biblioteca de mídia para galeria
(function() {
    var container = document.getElementById('galeria_paths_container');
    var previewContainer = document.getElementById('galeria_preview_container');
    
    // Mostrar container se já houver imagens existentes
    if (container && container.querySelectorAll('input[type="hidden"]').length > 0) {
        container.style.display = 'block';
    }
    
    if (container) {
        console.log('[Galeria] Container encontrado, adicionando listener para media-picker:multiple-selected');
        
        container.addEventListener('media-picker:multiple-selected', function(event) {
            console.log('[Galeria] Evento media-picker:multiple-selected recebido!');
            console.log('[Galeria] URLs recebidas:', event.detail.urls);
            
            var urls = event.detail.urls;
            if (!urls || !Array.isArray(urls)) {
                console.error('[Galeria] URLs inválidas:', urls);
                return;
            }
            
            var addedCount = 0;
            var skippedCount = 0;
            
            // Criar inputs hidden para cada URL
            urls.forEach(function(url) {
                if (!url || typeof url !== 'string') {
                    console.warn('[Galeria] URL inválida ignorada:', url);
                    return;
                }
                
                // Verificar se já não existe (por valor ou por imagem existente)
                var existing = container.querySelector('input[value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existing) {
                    console.log('[Galeria] URL já existe (por valor), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                // Verificar se já existe uma imagem com esse caminho na galeria existente
                var existingByPath = container.querySelector('input[data-imagem-id][value="' + url.replace(/"/g, '&quot;') + '"]');
                if (existingByPath) {
                    console.log('[Galeria] URL já existe (por data-imagem-id), ignorando:', url);
                    skippedCount++;
                    return;
                }
                
                console.log('[Galeria] Adicionando nova URL:', url);
                
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'galeria_paths[]';
                input.value = url;
                container.appendChild(input);
                addedCount++;
                
                // Adicionar preview
                var previewItem = document.createElement('div');
                previewItem.style.cssText = 'position: relative; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; aspect-ratio: 1;';
                var imageUrl = url;
                if (!imageUrl.startsWith('/')) {
                    imageUrl = '/' + imageUrl;
                }
                // Escapar aspas simples para evitar problemas no onclick
                var escapedUrl = url.replace(/'/g, "\\'");
                previewItem.innerHTML = 
                    '<img src="' + imageUrl + '" style="width: 100%; height: 100%; object-fit: cover;" ' +
                    'onerror="this.parentElement.remove()">' +
                    '<button type="button" onclick="removeGalleryPreview(this, \'' + escapedUrl + '\')" ' +
                    'style="position: absolute; top: 0.25rem; right: 0.25rem; background: #dc3545; color: white; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 0.875rem; display: flex; align-items: center; justify-content: center;">' +
                    '<i class="bi bi-x"></i></button>';
                previewContainer.appendChild(previewItem);
            });
            
            console.log('[Galeria] Resumo: ' + addedCount + ' adicionadas, ' + skippedCount + ' ignoradas');
            console.log('[Galeria] Total de inputs hidden agora:', container.querySelectorAll('input[type="hidden"]').length);
            
            // Mostrar containers se houver imagens
            if (container.querySelectorAll('input[type="hidden"]').length > 0) {
                container.style.display = 'block';
                previewContainer.style.display = 'grid';
            }
        });
        
        // Também escutar no document para garantir que o evento seja capturado
        document.addEventListener('media-picker:multiple-selected', function(event) {
            // Verificar se o evento é para o nosso container
            if (event.target && event.target.id === 'galeria_paths_container') {
                console.log('[Galeria] Evento capturado via document listener');
                // O listener do container já vai processar, não precisa fazer nada aqui
            }
        });
    } else {
        console.error('[Galeria] Container #galeria_paths_container não encontrado!');
    }
    
    // Adicionar event listeners para os botões de remoção das imagens existentes
    (function() {
        // Usar event delegation para capturar cliques nos botões de remoção
        document.addEventListener('click', function(e) {
            // Verificar se o clique foi em um botão de remoção (label.btn-remove, seu ícone, ou qualquer elemento dentro)
            var btnRemove = e.target.closest('.btn-remove');
            
            // Se não encontrou pelo closest, verificar se o clique foi diretamente no ícone dentro do label
            if (!btnRemove && e.target.closest('label.btn-remove')) {
                btnRemove = e.target.closest('label.btn-remove');
            }
            
            // Se ainda não encontrou, verificar se o clique foi no ícone bi-trash
            if (!btnRemove && (e.target.classList.contains('bi-trash') || e.target.closest('.bi-trash'))) {
                btnRemove = e.target.closest('label.btn-remove') || e.target.closest('.gallery-item-actions')?.querySelector('.btn-remove');
            }
            
            if (btnRemove) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('[Galeria] 🔴 CLICK NO BOTAO DE REMOCAO - Elemento:', btnRemove);
                
                // Encontrar o checkbox dentro do label
                var checkbox = btnRemove.querySelector('input[type="checkbox"][name="remove_imagens[]"]');
                if (checkbox) {
                    var imagemId = checkbox.value;
                    console.log('[Galeria] 🔴 Processando remoção da imagem ID:', imagemId);
                    
                    // Encontrar o item da galeria correspondente
                    var galleryItem = btnRemove.closest('.gallery-item');
                    if (!galleryItem) {
                        console.warn('[Galeria] ⚠️ Item da galeria (.gallery-item) não encontrado');
                        return;
                    }
                    
                    // IMPORTANTE: Remover o input hidden correspondente de galeria_paths[] IMEDIATAMENTE
                    // Comportamento WordPress-like: imagem é desvinculada imediatamente
                    var container = document.getElementById('galeria_paths_container');
                    if (!container) {
                        console.error('[Galeria] ❌ Container #galeria_paths_container não encontrado');
                        return;
                    }
                    
                    // Listar todos os inputs antes da remoção (para debug)
                    var allInputsBefore = container.querySelectorAll('input[name="galeria_paths[]"]');
                    console.log('[Galeria] 📋 Inputs antes da remoção:', allInputsBefore.length);
                    allInputsBefore.forEach(function(input, idx) {
                        var id = input.getAttribute('data-imagem-id') || 'nova';
                        console.log('[Galeria]   [' + idx + '] ID:', id, 'Caminho:', input.value);
                    });
                    
                    // Buscar o input hidden que tem data-imagem-id correspondente
                    var inputToRemove = container.querySelector('input[data-imagem-id="' + imagemId + '"]');
                    if (inputToRemove) {
                        var imagePath = inputToRemove.value;
                        console.log('[Galeria] ✅ Input encontrado! Removendo de galeria_paths[] - ID:', imagemId, 'Caminho:', imagePath);
                        
                        // Remover o input hidden
                        inputToRemove.remove();
                        
                        // Verificar se foi removido
                        var verifyRemoval = container.querySelector('input[data-imagem-id="' + imagemId + '"]');
                        if (verifyRemoval) {
                            console.error('[Galeria] ❌ ERRO: Input ainda existe após remoção!');
                        } else {
                            console.log('[Galeria] ✅ Input removido com sucesso!');
                        }
                        
                        // Atualizar contador
                        var remainingInputs = container.querySelectorAll('input[name="galeria_paths[]"]').length;
                        console.log('[Galeria] ✅ Total restante em galeria_paths[]:', remainingInputs);
                        
                        // Listar inputs restantes (para debug)
                        var allInputsAfter = container.querySelectorAll('input[name="galeria_paths[]"]');
                        console.log('[Galeria] 📋 Inputs após remoção:', allInputsAfter.length);
                        allInputsAfter.forEach(function(input, idx) {
                            var id = input.getAttribute('data-imagem-id') || 'nova';
                            console.log('[Galeria]   [' + idx + '] ID:', id, 'Caminho:', input.value);
                        });
                    } else {
                        console.error('[Galeria] ❌ Input hidden NÃO encontrado para imagem ID:', imagemId);
                        console.log('[Galeria] 🔍 Tentando buscar por seletor alternativo...');
                        
                        // Tentar buscar todos os inputs e verificar manualmente
                        var allInputs = container.querySelectorAll('input[name="galeria_paths[]"]');
                        console.log('[Galeria] Total de inputs encontrados:', allInputs.length);
                        var inputRemoved = false;
                        allInputs.forEach(function(input, idx) {
                            var id = input.getAttribute('data-imagem-id');
                            console.log('[Galeria]   Input [' + idx + ']: data-imagem-id =', id, 'value =', input.value);
                            if (id == imagemId) {
                                console.log('[Galeria] ✅ ENCONTRADO! Removendo...');
                                input.remove();
                                inputRemoved = true;
                            }
                        });
                        
                        // Se ainda não encontrou o input, buscar pelo caminho da imagem no gallery-item
                        if (!inputRemoved) {
                            var galleryImage = galleryItem.querySelector('img');
                            if (galleryImage && galleryImage.src) {
                                // Extrair o caminho relativo da URL completa
                                var imageUrl = galleryImage.src;
                                var match = imageUrl.match(/\/uploads\/tenants\/\d+\/produtos\/[^"']+/);
                                if (match) {
                                    var imagePath = match[0];
                                    console.log('[Galeria] 🔍 Tentando encontrar input pelo caminho:', imagePath);
                                    var inputByPath = container.querySelector('input[value="' + imagePath.replace(/"/g, '&quot;') + '"]');
                                    if (inputByPath) {
                                        console.log('[Galeria] ✅ Input encontrado pelo caminho! Removendo...');
                                        inputByPath.remove();
                                        inputRemoved = true;
                                    }
                                }
                            }
                        }
                    }
                    
                    // Marcar checkbox como checked (para compatibilidade, mas não é mais necessário)
                    checkbox.checked = true;
                    console.log('[Galeria] ✅ Checkbox de remoção MARCADO para imagem ID:', imagemId);
                    
                    // COMPORTAMENTO WORDPRESS-LIKE: Remover o elemento visual IMEDIATAMENTE
                    // A imagem deve desaparecer da galeria assim que o usuário clica para remover
                    console.log('[Galeria] 🗑️ Removendo elemento visual da galeria IMEDIATAMENTE - ID:', imagemId);
                    
                    // Adicionar animação de fade-out antes de remover
                    galleryItem.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    galleryItem.style.opacity = '0';
                    galleryItem.style.transform = 'scale(0.8)';
                    
                    // Remover o elemento após a animação
                    setTimeout(function() {
                        if (galleryItem && galleryItem.parentNode) {
                            galleryItem.remove();
                            console.log('[Galeria] ✅ Elemento visual removido da galeria - ID:', imagemId);
                            
                            // Verificar se ainda há imagens na galeria
                            var galleryContainer = document.querySelector('.product-gallery__list');
                            if (galleryContainer) {
                                var remainingItems = galleryContainer.querySelectorAll('.gallery-item').length;
                                console.log('[Galeria] 📊 Total de imagens restantes na galeria:', remainingItems);
                                
                                // Se não houver mais imagens, mostrar mensagem
                                if (remainingItems === 0) {
                                    var gallerySection = galleryContainer.closest('.info-section');
                                    if (gallerySection) {
                                        var emptyMessage = gallerySection.querySelector('p');
                                        if (!emptyMessage || emptyMessage.textContent.indexOf('Nenhuma imagem') === -1) {
                                            var msg = document.createElement('p');
                                            msg.style.cssText = 'color: #666; margin-bottom: 1rem;';
                                            msg.textContent = 'Nenhuma imagem na galeria.';
                                            galleryContainer.appendChild(msg);
                                        }
                                    }
                                }
                            }
                        }
                    }, 300);
                    
                    console.log('[Galeria] ✅ Imagem desvinculada IMEDIATAMENTE da galeria (comportamento WordPress-like)');
                } else {
                    console.error('[Galeria] ❌ Checkbox não encontrado dentro do botão de remoção. HTML:', btnRemove.innerHTML);
                }
            }
        });
    })();
    
    // Função para remover preview da galeria
    window.removeGalleryPreview = function(btn, url) {
        console.log('[Galeria] removeGalleryPreview chamado para URL:', url);
        
        // Buscar container novamente (pode não estar no escopo)
        var container = document.getElementById('galeria_paths_container');
        var previewContainer = document.getElementById('galeria_preview_container');
        
        if (!container) {
            console.error('[Galeria] Container #galeria_paths_container não encontrado');
            return;
        }
        
        var previewItem = btn.closest('div');
        if (!previewItem) {
            console.error('[Galeria] Preview item não encontrado');
            return;
        }
        
        // Encontrar o input hidden correspondente a essa URL
        // Escapar caracteres especiais para querySelector
        var escapedUrl = url.replace(/"/g, '&quot;').replace(/'/g, "&#39;").replace(/\[/g, '\\[').replace(/\]/g, '\\]');
        var input = container.querySelector('input[value="' + escapedUrl + '"]');
        
        if (input) {
            // Verificar se é imagem existente (tem data-imagem-id) ou nova
            if (input.hasAttribute('data-imagem-id')) {
                // É imagem existente - remover input hidden IMEDIATAMENTE (comportamento WordPress-like)
                var imagemId = input.getAttribute('data-imagem-id');
                console.log('[Galeria] 🔴 Imagem existente encontrada, removendo IMEDIATAMENTE - ID:', imagemId);
                
                // Remover o input hidden de galeria_paths[] IMEDIATAMENTE
                input.remove();
                console.log('[Galeria] ✅ Input hidden removido de galeria_paths[] - ID:', imagemId);
                
                // Marcar checkbox de remoção (para compatibilidade, mas não é mais necessário)
                var removeCheckbox = document.querySelector('input[name="remove_imagens[]"][value="' + imagemId + '"]');
                if (removeCheckbox) {
                    removeCheckbox.checked = true;
                    console.log('[Galeria] ✅ Checkbox de remoção marcado para imagem ID:', imagemId);
                }
                
                // Remover visualmente o preview IMEDIATAMENTE (comportamento WordPress-like)
                previewItem.style.opacity = '0.5';
                previewItem.style.border = '2px solid #dc3545';
                previewItem.style.transition = 'all 0.3s ease';
                
                // Adicionar indicador visual de que será removida
                var existingIndicator = previewItem.querySelector('.removal-indicator');
                if (!existingIndicator) {
                    var indicator = document.createElement('div');
                    indicator.className = 'removal-indicator';
                    indicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(220, 53, 69, 0.9); color: white; padding: 0.5rem; border-radius: 4px; font-size: 0.875rem; font-weight: bold; z-index: 10; pointer-events: none;';
                    indicator.textContent = 'Será removida';
                    previewItem.style.position = 'relative';
                    previewItem.appendChild(indicator);
                }
                
                console.log('[Galeria] ✅ Imagem desvinculada IMEDIATAMENTE da galeria (comportamento WordPress-like)');
            } else {
                // É imagem nova - remover input e preview IMEDIATAMENTE
                console.log('[Galeria] 🔴 Imagem nova encontrada, removendo input e preview IMEDIATAMENTE');
                input.remove();
                previewItem.remove();
                console.log('[Galeria] ✅ Imagem nova removida completamente');
            }
        } else {
            console.warn('[Galeria] ⚠️ Input hidden não encontrado para URL:', url);
            // Remover preview mesmo assim
            previewItem.remove();
        }
        
        // Atualizar contadores
        var totalInputs = container ? container.querySelectorAll('input[type="hidden"]').length : 0;
        var totalPreviews = previewContainer ? previewContainer.querySelectorAll('div').length : 0;
        console.log('[Galeria] Total de inputs restantes:', totalInputs);
        console.log('[Galeria] Total de previews restantes:', totalPreviews);
        
        // Esconder container de preview se não houver mais imagens novas
        if (previewContainer && previewContainer.querySelectorAll('div').length === 0) {
            previewContainer.style.display = 'none';
        }
        
        // Container de paths sempre fica visível se houver imagens (existentes ou novas)
        if (container && container.querySelectorAll('input[type="hidden"]').length === 0) {
            container.style.display = 'none';
        }
    };
})();

</script>

<style>
.product-edit-page {
    max-width: 1400px;
}
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}
.alert-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
.btn-back {
    padding: 0.75rem 1.5rem;
    background: #023A8D;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}
.btn-view-store {
    padding: 0.75rem 1.5rem;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    display: inline-block;
}
.info-section {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.section-title {
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 2px solid #023A8D;
    padding-bottom: 0.5rem;
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.form-group label {
    font-weight: 600;
    color: #555;
    font-size: 0.9rem;
}
.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group input[type="url"],
.form-group input[type="file"],
.form-group select,
.form-group textarea {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    font-family: inherit;
}
.form-group textarea {
    resize: vertical;
}
.form-group input[type="checkbox"] {
    width: auto;
    margin-right: 0.5rem;
}
.media-section {
    margin-top: 1.5rem;
}
.featured-image-container {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
}
.current-image {
    width: 300px;
    height: 300px;
    border: 2px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    position: relative;
}
.current-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}
.current-image.placeholder .placeholder-content {
    text-align: center;
    color: #999;
}
.image-label {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 0.5rem;
    text-align: center;
    font-size: 0.875rem;
}
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.gallery-item {
    position: relative;
    border: 2px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    aspect-ratio: 1;
}
.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Drag-and-Drop Styles */
.product-gallery__item {
    cursor: grab;
    transition: transform 0.2s, opacity 0.2s;
}
.product-gallery__item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.product-gallery__item.is-dragging {
    opacity: 0.5;
    cursor: grabbing;
    transform: scale(0.95);
}
.product-gallery__item.drag-over {
    border-color: var(--cor-primaria, #2E7D32);
    border-width: 3px;
}
.product-gallery__thumb {
    width: 100%;
    height: 100%;
    position: relative;
}
.product-gallery__ordem-input {
    display: none;
}
.gallery-item-actions {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    display: flex;
    gap: 0.5rem;
}
.btn-set-main,
.btn-remove {
    background: rgba(255,255,255,0.9);
    border: none;
    border-radius: 4px;
    padding: 0.5rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.btn-set-main.active {
    background: #F7931E;
    color: white;
}
.btn-remove {
    cursor: pointer;
}
.btn-remove input[type="checkbox"] {
    display: none;
}
.btn-remove:has(input:checked) {
    background: #dc3545;
    color: white;
}
.videos-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.video-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.video-fields {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}
.video-actions {
    display: flex;
    align-items: flex-start;
}
.btn-remove-video {
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-remove-video input[type="checkbox"] {
    display: none;
}
.btn-remove-video:has(input:checked) {
    opacity: 0.5;
}
.btn-add-video {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 0.75rem 1.5rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
/* Botão salvar agora usa admin-btn admin-btn-primary */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.info-item {
    display: flex;
    flex-direction: column;
}
.info-label {
    font-weight: 600;
    color: #555;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}
.info-value {
    color: #333;
    font-size: 1rem;
}
.badge-category,
.badge-tag {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #e0e0e0;
    border-radius: 4px;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}
</style>

<script>
(function() {
    const produtoTipoSelect = document.getElementById('produto_tipo');
    const secaoAtributos = document.getElementById('secao-atributos');
    const secaoVariacoes = document.getElementById('secao-variacoes');
    const btnGerarVariacoes = document.getElementById('btn-gerar-variacoes');

    // Mostrar/ocultar seções baseado no tipo
    function toggleSecoes() {
        const isVariable = produtoTipoSelect.value === 'variable';
        if (secaoAtributos) secaoAtributos.style.display = isVariable ? 'block' : 'none';
        if (secaoVariacoes) secaoVariacoes.style.display = isVariable ? 'block' : 'none';
    }

    if (produtoTipoSelect) {
        produtoTipoSelect.addEventListener('change', toggleSecoes);
        toggleSecoes(); // Executar ao carregar
    }

    // Dropdown "Adicionar atributo"
    const addAtributoSelect = document.getElementById('add-atributo-select');
    const btnAddAtributo = document.getElementById('btn-add-atributo');
    
    if (btnAddAtributo && addAtributoSelect) {
        btnAddAtributo.addEventListener('click', function() {
            const selectedOption = addAtributoSelect.options[addAtributoSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                alert('Selecione um atributo para adicionar');
                return;
            }
            
            const atributoId = selectedOption.value;
            const atributoNome = selectedOption.getAttribute('data-nome');
            const atributoTipo = selectedOption.getAttribute('data-tipo');
            
            // Verificar se já está na lista
            const existingCheckbox = document.querySelector(`input[name="atributos[]"][value="${atributoId}"]`);
            if (existingCheckbox) {
                alert('Este atributo já foi adicionado');
                return;
            }
            
            // Marcar como selecionado
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'atributos[]';
            checkbox.value = atributoId;
            checkbox.className = 'atributo-checkbox';
            checkbox.setAttribute('data-atributo-id', atributoId);
            checkbox.checked = true;
            
            // Criar item do atributo
            const atributoItem = document.createElement('div');
            atributoItem.className = 'atributo-item';
            atributoItem.style.cssText = 'border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;';
            atributoItem.innerHTML = `
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; margin-bottom: 0.5rem;">
                    ${checkbox.outerHTML}
                    <span>${atributoNome} (${atributoTipo})</span>
                </label>
                <div class="atributo-options" style="margin-left: 2rem; display: block;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.9rem;">
                        <input type="checkbox" name="atributos_para_variacao[${atributoId}]" value="1" class="usado-para-variacao-checkbox" checked>
                        <span>Usar para gerar variações</span>
                    </label>
                    <div class="termos-list" style="margin-top: 0.5rem;">
                        <label style="font-size: 0.875rem; color: #666; display: block; margin-bottom: 0.25rem;">Termos disponíveis:</label>
                        <p style="color: #999; font-size: 0.875rem; font-style: italic; padding: 0.5rem; background: #f8f9fa; border-radius: 4px;">
                            Carregando termos... (recarregue a página para ver os termos)
                        </p>
                    </div>
                </div>
            `;
            
            // Adicionar à lista
            const atributosList = document.querySelector('.atributos-list');
            if (atributosList) {
                atributosList.appendChild(atributoItem);
                
                // Adicionar event listener ao checkbox
                const newCheckbox = atributoItem.querySelector('.atributo-checkbox');
                if (newCheckbox) {
                    newCheckbox.addEventListener('change', function() {
                        const options = this.closest('.atributo-item').querySelector('.atributo-options');
                        if (options) {
                            options.style.display = this.checked ? 'block' : 'none';
                        }
                    });
                }
            }
            
            // Limpar seleção
            addAtributoSelect.value = '';
        });
    }

    // Mostrar/ocultar opções de atributo quando checkbox é marcado
    document.querySelectorAll('.atributo-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const atributoId = this.getAttribute('data-atributo-id');
            const options = this.closest('.atributo-item').querySelector('.atributo-options');
            if (options) {
                options.style.display = this.checked ? 'block' : 'none';
            }
        });
    });

    // Mostrar/ocultar campos de configuração quando termo é marcado/desmarcado
    document.querySelectorAll('.termo-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const termoItem = this.closest('.termo-item');
            const termoConfig = termoItem ? termoItem.querySelector('.termo-config') : null;
            if (termoConfig) {
                termoConfig.style.display = this.checked ? 'block' : 'none';
            }
        });
        // Executar ao carregar para mostrar campos de termos já marcados
        if (checkbox.checked) {
            const termoItem = checkbox.closest('.termo-item');
            const termoConfig = termoItem ? termoItem.querySelector('.termo-config') : null;
            if (termoConfig) {
                termoConfig.style.display = 'block';
            }
        }
    });

    // Sincronizar color picker com campo de texto
    document.querySelectorAll('input[type="color"]').forEach(function(colorPicker) {
        const textInput = colorPicker.nextElementSibling;
        if (textInput && textInput.tagName === 'INPUT') {
            // Color picker -> Text
            colorPicker.addEventListener('input', function() {
                textInput.value = this.value.toUpperCase();
            });
            // Text -> Color picker
            textInput.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    colorPicker.value = this.value.toUpperCase();
                }
            });
        }
    });

    // Preview de imagens quando arquivo é selecionado
    document.querySelectorAll('.swatch-upload').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = input.previousElementSibling;
                    if (preview && preview.classList.contains('swatch-preview')) {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.cssText = 'width: 40px; height: 40px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;';
                            img.alt = 'Swatch';
                            preview.replaceWith(img);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    document.querySelectorAll('.produto-image-upload').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = input.previousElementSibling;
                    if (preview && preview.classList.contains('produto-image-preview')) {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;';
                            img.alt = 'Imagem produto';
                            preview.replaceWith(img);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Preview de imagem de variação
    document.querySelectorAll('.variacao-image-upload').forEach(function(input) {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = input.previousElementSibling;
                    if (preview && preview.classList.contains('variacao-image-preview')) {
                        if (preview.tagName === 'IMG') {
                            preview.src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.cssText = 'width: 60px; height: 60px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;';
                            img.alt = 'Variação';
                            preview.replaceWith(img);
                        }
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Botões de ação rápida na seção de atributos
    const btnSaveAttributes = document.getElementById('btn-save-attributes');
    const btnSaveAndGenerate = document.getElementById('btn-save-and-generate');
    const form = document.querySelector('form[method="POST"]');

    // Função para coletar dados do formulário de atributos
    function collectAttributesData() {
        const formData = new FormData();
        
        // Coletar atributos selecionados
        const atributosCheckboxes = document.querySelectorAll('input[name="atributos[]"]:checked');
        atributosCheckboxes.forEach(function(checkbox) {
            formData.append('atributos[]', checkbox.value);
        });

        // Coletar atributos marcados para variação
        const atributosParaVariacao = document.querySelectorAll('input[name^="atributos_para_variacao"]:checked');
        atributosParaVariacao.forEach(function(checkbox) {
            const match = checkbox.name.match(/\[(\d+)\]/);
            if (match) {
                formData.append(`atributos_para_variacao[${match[1]}]`, '1');
            }
        });

        // Coletar termos selecionados por atributo
        atributosCheckboxes.forEach(function(checkbox) {
            const atributoId = checkbox.value;
            const termosCheckboxes = document.querySelectorAll(`input[name="atributo_${atributoId}_termos[]"]:checked`);
            termosCheckboxes.forEach(function(termoCheckbox) {
                formData.append(`atributo_${atributoId}_termos[]`, termoCheckbox.value);
            });

            // Coletar hex colors, swatches e imagens de produto
            termosCheckboxes.forEach(function(termoCheckbox) {
                const termoId = termoCheckbox.value;
                
                // Hex color
                const hexInput = document.querySelector(`input[name="atributo_${atributoId}_termo_${termoId}_hex_text"]`);
                if (hexInput && hexInput.value) {
                    formData.append(`atributo_${atributoId}_termo_${termoId}_hex_text`, hexInput.value);
                }

                // Swatch path
                const swatchPathInput = document.querySelector(`input[name="atributo_${atributoId}_termo_${termoId}_swatch_path"]`);
                if (swatchPathInput && swatchPathInput.value) {
                    formData.append(`atributo_${atributoId}_termo_${termoId}_swatch_path`, swatchPathInput.value);
                }

                // Produto image path
                const produtoImagePathInput = document.querySelector(`input[name="atributo_${atributoId}_termo_${termoId}_produto_image_path"]`);
                if (produtoImagePathInput && produtoImagePathInput.value) {
                    formData.append(`atributo_${atributoId}_termo_${termoId}_produto_image_path`, produtoImagePathInput.value);
                }

                // Uploads de arquivos
                const swatchFile = document.querySelector(`input[name="atributo_${atributoId}_termo_${termoId}_swatch"]`);
                if (swatchFile && swatchFile.files[0]) {
                    formData.append(`atributo_${atributoId}_termo_${termoId}_swatch`, swatchFile.files[0]);
                }

                const produtoImageFile = document.querySelector(`input[name="atributo_${atributoId}_termo_${termoId}_produto_image"]`);
                if (produtoImageFile && produtoImageFile.files[0]) {
                    formData.append(`atributo_${atributoId}_termo_${termoId}_produto_image`, produtoImageFile.files[0]);
                }
            });
        });

        return formData;
    }

    // Botão Salvar Atributos
    if (btnSaveAttributes) {
        btnSaveAttributes.addEventListener('click', function() {
            const produtoId = <?= $produto['id'] ?>;
            const basePath = '<?= $basePath ?>';
            const formData = collectAttributesData();

            btnSaveAttributes.disabled = true;
            btnSaveAttributes.innerHTML = '<i class="bi bi-hourglass-split icon"></i> Salvando...';

            fetch(basePath + '/admin/produtos/' + produtoId + '/atributos/salvar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + (data.message || 'Atributos salvos com sucesso!'));
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao salvar atributos');
                }
            })
            .catch(error => {
                console.error('Erro ao salvar atributos:', error);
                alert('Erro ao salvar atributos:\n\n' + error.message);
                btnSaveAttributes.disabled = false;
                btnSaveAttributes.innerHTML = '<i class="bi bi-save icon"></i> Salvar Atributos';
            });
        });
    }

    // Botão Salvar e Gerar Variações
    if (btnSaveAndGenerate) {
        btnSaveAndGenerate.addEventListener('click', function() {
            const produtoId = <?= $produto['id'] ?>;
            const basePath = '<?= $basePath ?>';
            const formData = collectAttributesData();

            // Validações
            const atributosParaVariacao = document.querySelectorAll('input[name^="atributos_para_variacao"]:checked');
            if (atributosParaVariacao.length === 0) {
                alert('⚠️ ATENÇÃO:\n\nVocê precisa marcar "Usar para gerar variações" para pelo menos um atributo.');
                return;
            }

            let termosSelecionados = 0;
            atributosParaVariacao.forEach(function(checkbox) {
                const match = checkbox.name.match(/\[(\d+)\]/);
                if (match) {
                    const atributoId = match[1];
                    const termos = document.querySelectorAll(`input[name="atributo_${atributoId}_termos[]"]:checked`);
                    termosSelecionados += termos.length;
                }
            });

            if (termosSelecionados === 0) {
                alert('⚠️ ATENÇÃO:\n\nVocê precisa selecionar pelo menos um termo para cada atributo marcado para variação.');
                return;
            }

            if (!confirm('Isso irá salvar os atributos e gerar todas as combinações possíveis.\n\nContinuar?')) {
                return;
            }

            btnSaveAndGenerate.disabled = true;
            btnSaveAndGenerate.innerHTML = '<i class="bi bi-hourglass-split icon"></i> Processando...';

            fetch(basePath + '/admin/produtos/' + produtoId + '/atributos/salvar-e-gerar-variacoes', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + (data.message || 'Atributos salvos e variações geradas com sucesso!'));
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao processar');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro:\n\n' + error.message);
                btnSaveAndGenerate.disabled = false;
                btnSaveAndGenerate.innerHTML = '<i class="bi bi-magic icon"></i> Salvar e Gerar Variações';
            });
        });
    }

    // Botão Gerar Variações
    if (btnGerarVariacoes) {
        btnGerarVariacoes.addEventListener('click', function() {
            const produtoId = <?= $produto['id'] ?>;
            const basePath = '<?= $basePath ?>';
            const form = document.querySelector('form[method="POST"]');

            // Verificar se há atributos marcados para variação no formulário
            const atributosParaVariacao = document.querySelectorAll('input[name^="atributos_para_variacao"]:checked');
            if (atributosParaVariacao.length === 0) {
                alert('⚠️ ATENÇÃO:\n\nVocê precisa:\n1. Marcar os atributos (Cor, Tamanho)\n2. Marcar "Usar para gerar variações" para cada atributo\n3. Selecionar os termos de cada atributo\n4. SALVAR o formulário antes de gerar variações\n\nDepois de salvar, você poderá gerar as variações.');
                return;
            }

            // Verificar se há termos selecionados
            let termosSelecionados = 0;
            atributosParaVariacao.forEach(function(checkbox) {
                const atributoId = checkbox.name.match(/\[(\d+)\]/)[1];
                const termos = document.querySelectorAll(`input[name="atributo_${atributoId}_termos[]"]:checked`);
                termosSelecionados += termos.length;
            });

            if (termosSelecionados === 0) {
                alert('⚠️ ATENÇÃO:\n\nVocê precisa selecionar pelo menos um termo para cada atributo marcado para variação.\n\nDepois de selecionar os termos, SALVE o formulário e então gere as variações.');
                return;
            }

            if (!confirm('Isso irá gerar todas as combinações possíveis dos atributos marcados para variação.\n\nIMPORTANTE: Certifique-se de que você já SALVOU o formulário com os atributos e termos selecionados.\n\nContinuar?')) {
                return;
            }

            btnGerarVariacoes.disabled = true;
            btnGerarVariacoes.textContent = 'Gerando...';

            fetch(basePath + '/admin/produtos/' + produtoId + '/variacoes/gerar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new Error('Resposta não é JSON. Status: ' + response.status + '. Resposta: ' + text.substring(0, 200));
                    });
                }
                
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Erro HTTP ' + response.status);
                    });
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Variações geradas com sucesso!');
                    location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao gerar variações');
                }
            })
            .catch(error => {
                console.error('Erro ao gerar variações:', error);
                let mensagem = 'Erro ao gerar variações:\n\n' + error.message;
                
                if (error.message.includes('Nenhum atributo marcado') || error.message.includes('Nenhum termo selecionado')) {
                    mensagem += '\n\n⚠️ SOLUÇÃO:\n';
                    mensagem += '1. Marque os atributos (Cor, Tamanho) na seção "Atributos do Produto"\n';
                    mensagem += '2. Marque "Usar para gerar variações" para cada atributo\n';
                    mensagem += '3. Selecione os termos de cada atributo\n';
                    mensagem += '4. Clique em "Salvar" no final do formulário\n';
                    mensagem += '5. Depois, clique novamente em "Gerar Variações"';
                }
                
                alert(mensagem);
                btnGerarVariacoes.disabled = false;
                btnGerarVariacoes.innerHTML = '<i class="bi bi-magic icon"></i> Gerar Variações';
            });
        });
    }

    // Salvar variações em lote ao submeter formulário
    const form = document.querySelector('form[method="POST"]');
    if (form && secaoVariacoes && secaoVariacoes.style.display !== 'none') {
        form.addEventListener('submit', function(e) {
            const variacoes = [];
            document.querySelectorAll('tr[data-variacao-id]').forEach(function(row) {
                const variacaoId = row.getAttribute('data-variacao-id');
                const inputs = row.querySelectorAll('input, select');
                const variacaoData = { id: variacaoId };
                
                inputs.forEach(function(input) {
                    const name = input.name;
                    const match = name.match(/variacoes\[(\d+)\]\[(\w+)\]/);
                    if (match && match[1] === variacaoId) {
                        const field = match[2];
                        if (input.type === 'checkbox') {
                            variacaoData[field] = input.checked ? 1 : 0;
                        } else if (input.type === 'number') {
                            variacaoData[field] = parseInt(input.value) || 0;
                        } else {
                            variacaoData[field] = input.value;
                        }
                    }
                });
                
                variacoes.push(variacaoData);
            });

            if (variacoes.length > 0) {
                // Adicionar campo hidden com JSON
                let hiddenInput = document.getElementById('variacoes_json');
                if (!hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.id = 'variacoes_json';
                    hiddenInput.name = 'variacoes_json';
                    form.appendChild(hiddenInput);
                }
                hiddenInput.value = JSON.stringify(variacoes);
            }
        });
    }
    
    // Barra sticky - mostrar/ocultar baseado no scroll
    const stickyBar = document.getElementById('sticky-actions-bar');
    if (stickyBar) {
        // Conectar botões da barra sticky aos botões da seção
        const stickySaveAttributes = document.getElementById('sticky-save-attributes');
        const stickySaveAndGenerate = document.getElementById('sticky-save-and-generate');
        const stickyGenerateVariations = document.getElementById('sticky-generate-variations');

        if (stickySaveAttributes && btnSaveAttributes) {
            stickySaveAttributes.addEventListener('click', function() {
                btnSaveAttributes.click();
            });
        }

        if (stickySaveAndGenerate && btnSaveAndGenerate) {
            stickySaveAndGenerate.addEventListener('click', function() {
                btnSaveAndGenerate.click();
            });
        }

        if (stickyGenerateVariations && btnGerarVariacoes) {
            stickyGenerateVariations.addEventListener('click', function() {
                btnGerarVariacoes.click();
            });
        }

        // Mostrar barra sticky quando scrollar para baixo
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > 200) {
                stickyBar.style.display = 'block';
            } else {
                stickyBar.style.display = 'none';
            }
            lastScrollTop = scrollTop;
        });
    }

    // Scroll automático para âncora se presente na URL
    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(function() {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
})();
</script>


