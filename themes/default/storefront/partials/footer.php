<?php
// Partial: Footer completo
// Variáveis esperadas: $basePath, $theme, $loja
// Carregar configuração do footer
$footerConfig = \App\Services\ThemeConfig::getFooterConfig();

// Buscar categorias de destaque para o footer
$db = \App\Core\Database::getConnection();
$tenantId = \App\Tenant\TenantContext::id();
$stmt = $db->prepare("
    SELECT hcp.*, c.nome as categoria_nome, c.slug as categoria_slug
    FROM home_category_pills hcp
    LEFT JOIN categorias c ON c.id = hcp.categoria_id AND c.tenant_id = :tenant_id_join
    WHERE hcp.tenant_id = :tenant_id_where AND hcp.ativo = 1
    ORDER BY hcp.ordem ASC, hcp.id ASC
    LIMIT :limit
");
$limit = $footerConfig['sections']['categorias']['limit'] ?? 6;
$stmt->bindValue(':tenant_id_join', $tenantId, \PDO::PARAM_INT);
$stmt->bindValue(':tenant_id_where', $tenantId, \PDO::PARAM_INT);
$stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
$stmt->execute();
$footerCategories = $stmt->fetchAll();
?>
<footer class="pg-footer">
    <div class="pg-footer-main">
        <div class="pg-container pg-footer-grid">
            <?php if (!empty($footerConfig['sections']['ajuda']['enabled'])): ?>
                <div class="pg-footer-col">
                    <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['ajuda']['title']) ?></h4>
                    <ul class="pg-footer-links">
                        <?php foreach ($footerConfig['sections']['ajuda']['links'] as $linkKey => $link): ?>
                            <?php if (!empty($link['enabled'])): ?>
                                <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($footerConfig['sections']['minha_conta']['enabled'])): ?>
                <div class="pg-footer-col">
                    <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['minha_conta']['title']) ?></h4>
                    <ul class="pg-footer-links">
                        <?php foreach ($footerConfig['sections']['minha_conta']['links'] as $linkKey => $link): ?>
                            <?php if (!empty($link['enabled'])): ?>
                                <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($footerConfig['sections']['institucional']['enabled'])): ?>
                <div class="pg-footer-col">
                    <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['institucional']['title']) ?></h4>
                    <ul class="pg-footer-links">
                        <?php foreach ($footerConfig['sections']['institucional']['links'] as $linkKey => $link): ?>
                            <?php if (!empty($link['enabled'])): ?>
                                <li><a href="<?= $basePath ?><?= htmlspecialchars($link['route']) ?>"><?= htmlspecialchars($link['label']) ?></a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($footerConfig['sections']['categorias']['enabled']) && !empty($footerCategories)): ?>
                <div class="pg-footer-col">
                    <h4 class="pg-footer-title"><?= htmlspecialchars($footerConfig['sections']['categorias']['title']) ?></h4>
                    <ul class="pg-footer-links">
                        <?php foreach ($footerCategories as $category): ?>
                            <li><a href="<?= $basePath ?>/categoria/<?= htmlspecialchars($category['categoria_slug']) ?>"><?= htmlspecialchars($category['label'] ?: $category['categoria_nome']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="pg-footer-col pg-footer-contact">
                <h4 class="pg-footer-title">Contato</h4>
                <?php if ($theme['footer_phone']): ?>
                    <div class="pg-footer-contact-item">
                        <i class="bi bi-telephone icon"></i>
                        <span><?= htmlspecialchars($theme['footer_phone']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($theme['footer_whatsapp']): ?>
                    <div class="pg-footer-contact-item">
                        <i class="bi bi-whatsapp icon"></i>
                        <span><?= htmlspecialchars($theme['footer_whatsapp']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($theme['footer_email']): ?>
                    <div class="pg-footer-contact-item">
                        <i class="bi bi-envelope icon"></i>
                        <span><?= htmlspecialchars($theme['footer_email']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($theme['footer_address']): ?>
                    <div class="pg-footer-contact-item">
                        <i class="bi bi-geo-alt icon"></i>
                        <span><?= htmlspecialchars($theme['footer_address']) ?></span>
                    </div>
                <?php endif; ?>
                <?php
                $footerCnpj = trim($theme['footer_cnpj'] ?? '');
                if ($footerCnpj !== ''):
                    $cnpjDigits = preg_replace('/\D/', '', $footerCnpj);
                    $cnpjFormatted = (strlen($cnpjDigits) === 14)
                        ? substr($cnpjDigits, 0, 2) . '.' . substr($cnpjDigits, 2, 3) . '.' . substr($cnpjDigits, 5, 3) . '/' . substr($cnpjDigits, 8, 4) . '-' . substr($cnpjDigits, 12, 2)
                        : $footerCnpj;
                ?>
                    <div class="pg-footer-contact-item pg-footer-cnpj">
                        <span>CNPJ: <?= htmlspecialchars($cnpjFormatted) ?></span>
                    </div>
                <?php endif; ?>
                <div class="pg-footer-social">
                    <?php if ($theme['footer_social_instagram']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_instagram']) ?>" target="_blank" rel="noopener"><i class="bi bi-instagram icon"></i></a>
                    <?php endif; ?>
                    <?php if ($theme['footer_social_facebook']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_facebook']) ?>" target="_blank" rel="noopener"><i class="bi bi-facebook icon"></i></a>
                    <?php endif; ?>
                    <?php if ($theme['footer_social_youtube']): ?>
                        <a href="<?= htmlspecialchars($theme['footer_social_youtube']) ?>" target="_blank" rel="noopener"><i class="bi bi-youtube icon"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="pg-footer-bottom">
        <div class="pg-footer-bottom-inner">
            <span class="pg-footer-copy">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($loja['nome']) ?>. Todos os direitos reservados.
            </span>
            <span class="pg-footer-dev">
                Desenvolvido por
                <a href="https://pixel12digital.com.br" target="_blank" rel="noopener">
                    Pixel12Digital
                </a>
            </span>
        </div>
    </div>
</footer>

