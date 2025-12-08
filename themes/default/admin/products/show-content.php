<?php
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="product-detail-page">
    <div style="margin-bottom: 2rem;">
        <a href="<?= $basePath ?>/admin/produtos" class="btn-back"><i class="bi bi-arrow-left icon"></i> Voltar para lista</a>
    </div>
    
    <div class="info-section">
        <h2 class="section-title">Informações Gerais</h2>
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
                <span class="info-label">Nome</span>
                <span class="info-value"><?= htmlspecialchars($produto['nome']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Slug</span>
                <span class="info-value"><?= htmlspecialchars($produto['slug']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">SKU</span>
                <span class="info-value"><?= htmlspecialchars($produto['sku'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Tipo</span>
                <span class="info-value"><?= htmlspecialchars($produto['tipo']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status</span>
                <span class="info-value">
                    <span class="status-badge status-<?= $produto['status'] ?>">
                        <?= \App\Support\LangHelper::productStatusLabel($produto['status']) ?>
                    </span>
                </span>
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h2 class="section-title">Preços</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Preço Regular</span>
                <span class="info-value">R$ <?= number_format($produto['preco_regular'], 2, ',', '.') ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Preço Promocional</span>
                <span class="info-value">
                    <?= $produto['preco_promocional'] ? 'R$ ' . number_format($produto['preco_promocional'], 2, ',', '.') : '-' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Data Início Promoção</span>
                <span class="info-value"><?= $produto['data_promocao_inicio'] ?? '-' ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Data Fim Promoção</span>
                <span class="info-value"><?= $produto['data_promocao_fim'] ?? '-' ?></span>
            </div>
        </div>
    </div>
    
    <div class="info-section">
        <h2 class="section-title">Estoque</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Gerencia Estoque</span>
                <span class="info-value"><?= \App\Support\LangHelper::boolLabel($produto['gerencia_estoque']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Quantidade</span>
                <span class="info-value"><?= $produto['quantidade_estoque'] ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status Estoque</span>
                <span class="info-value">
                    <span class="status-badge <?= $produto['status_estoque'] === 'instock' ? 'stock-in' : 'stock-out' ?>">
                        <?= \App\Support\LangHelper::stockStatusLabel($produto['status_estoque'] ?? null) ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Permite Pedidos em Falta</span>
                <span class="info-value"><?= htmlspecialchars($produto['permite_pedidos_falta']) ?></span>
            </div>
        </div>
    </div>
    
    <?php if ($produto['peso'] || $produto['comprimento'] || $produto['largura'] || $produto['altura']): ?>
        <div class="info-section">
            <h2 class="section-title">Dimensões</h2>
            <div class="info-grid">
                <?php if ($produto['peso']): ?>
                    <div class="info-item">
                        <span class="info-label">Peso</span>
                        <span class="info-value"><?= number_format($produto['peso'], 2, ',', '.') ?> kg</span>
                    </div>
                <?php endif; ?>
                <?php if ($produto['comprimento']): ?>
                    <div class="info-item">
                        <span class="info-label">Comprimento</span>
                        <span class="info-value"><?= number_format($produto['comprimento'], 2, ',', '.') ?> cm</span>
                    </div>
                <?php endif; ?>
                <?php if ($produto['largura']): ?>
                    <div class="info-item">
                        <span class="info-label">Largura</span>
                        <span class="info-value"><?= number_format($produto['largura'], 2, ',', '.') ?> cm</span>
                    </div>
                <?php endif; ?>
                <?php if ($produto['altura']): ?>
                    <div class="info-item">
                        <span class="info-label">Altura</span>
                        <span class="info-value"><?= number_format($produto['altura'], 2, ',', '.') ?> cm</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($imagens)): ?>
        <div class="info-section">
            <h2 class="section-title">Imagens (<?= count($imagens) ?>)</h2>
            <div class="images-gallery">
                <?php foreach ($imagens as $imagem): ?>
                    <div class="image-item">
                        <?php 
                        $caminho = ltrim($imagem['caminho_arquivo'], '/');
                        $imgSrc = $basePath . '/' . $caminho;
                        ?>
                        <img src="<?= htmlspecialchars($imgSrc) ?>" 
                             alt="<?= htmlspecialchars($imagem['alt_text'] ?? $imagem['titulo'] ?? '') ?>"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImagem não encontrada%3C/text%3E%3C/svg%3E';">
                        <div class="image-type <?= $imagem['tipo'] === 'gallery' ? 'gallery' : '' ?>">
                            <?= htmlspecialchars($imagem['tipo']) ?>
                        </div>
                        <?php 
                        $nomeArquivo = basename($imagem['caminho_arquivo']);
                        $displayNome = $imagem['titulo'] ?? $nomeArquivo;
                        ?>
                        <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #666; word-break: break-word;">
                            <?= htmlspecialchars($displayNome) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($categorias)): ?>
        <div class="info-section">
            <h2 class="section-title">Categorias (<?= count($categorias) ?>)</h2>
            <div class="categories-list">
                <?php foreach ($categorias as $categoria): ?>
                    <div class="category-item"><?= htmlspecialchars($categoria['nome']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($tags)): ?>
        <div class="info-section">
            <h2 class="section-title">Tags (<?= count($tags) ?>)</h2>
            <div class="tags-list">
                <?php foreach ($tags as $tag): ?>
                    <div class="tag-item"><?= htmlspecialchars($tag['nome']) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($metadados)): ?>
        <div class="info-section">
            <h2 class="section-title">Metadados (<?= count($metadados) ?>)</h2>
            <table class="metadata-table">
                <thead>
                    <tr>
                        <th>Chave</th>
                        <th>Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metadados as $meta): ?>
                        <tr>
                            <td><?= htmlspecialchars($meta['chave']) ?></td>
                            <td><?= htmlspecialchars($meta['valor']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="info-section">
        <h2 class="section-title">Descrições</h2>
        <div class="info-grid">
            <div class="info-item" style="grid-column: 1 / -1;">
                <span class="info-label">Descrição Curta</span>
                <div class="info-value description-content" style="margin-top: 0.5rem; line-height: 1.6;">
                    <?php if ($produto['descricao_curta']): ?>
                        <?= $produto['descricao_curta'] ?>
                    <?php else: ?>
                        <span style="color: #999;">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="info-item" style="grid-column: 1 / -1;">
                <span class="info-label">Descrição Completa</span>
                <div class="info-value description-content" style="margin-top: 0.5rem; line-height: 1.6;">
                    <?php if ($produto['descricao']): ?>
                        <?= $produto['descricao'] ?>
                    <?php else: ?>
                        <span style="color: #999;">-</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.product-detail-page {
    max-width: 1400px;
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
    font-size: 1.1rem;
}
.images-gallery {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}
.image-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}
.image-item img {
    max-width: 100%;
    height: 200px;
    object-fit: contain;
    background: #f0f0f0;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}
.image-type {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #023A8D;
    color: white;
    border-radius: 4px;
    font-size: 0.8rem;
    margin-top: 0.5rem;
}
.image-type.gallery {
    background: #F7931E;
}
.categories-list, .tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}
.category-item, .tag-item {
    padding: 0.5rem 1rem;
    background: #e0e0e0;
    border-radius: 4px;
}
.metadata-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
.metadata-table th,
.metadata-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #e0e0e0;
}
.metadata-table th {
    background: #f5f5f5;
    font-weight: 600;
    color: #555;
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    display: inline-block;
}
.status-publish {
    background: #d4edda;
    color: #155724;
}
.status-draft {
    background: #fff3cd;
    color: #856404;
}
.stock-in {
    background: #d4edda;
    color: #155724;
}
.stock-out {
    background: #f8d7da;
    color: #721c24;
}
.description-content {
    max-width: 100%;
    overflow-x: auto;
}
.description-content ul,
.description-content ol {
    margin: 1rem 0;
    padding-left: 2rem;
}
.description-content li {
    margin: 0.5rem 0;
}
</style>


