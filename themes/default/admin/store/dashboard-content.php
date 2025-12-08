<?php
// Obter caminho base se necessário
$basePath = '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
?>

<div class="dashboard-welcome">
    <div class="welcome-card">
        <h2>Bem-vindo</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Loja:</span>
                <span class="info-value"><?= htmlspecialchars($tenant->name) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Slug:</span>
                <span class="info-value"><?= htmlspecialchars($tenant->slug) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= htmlspecialchars($tenant->status) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Plano:</span>
                <span class="info-value"><?= htmlspecialchars($tenant->plan) ?></span>
            </div>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3>Ações Rápidas</h3>
        <div class="action-buttons">
            <a href="<?= $basePath ?>/admin/system/updates" class="action-btn">
                <i class="bi bi-gear-fill action-icon"></i>
                <span class="action-text">Atualizações do Sistema</span>
            </a>
            <a href="<?= $basePath ?>/admin/produtos" class="action-btn">
                <i class="bi bi-bag action-icon"></i>
                <span class="action-text">Produtos</span>
            </a>
            <a href="<?= $basePath ?>/admin/home" class="action-btn">
                <i class="bi bi-house-door action-icon"></i>
                <span class="action-text">Home da Loja</span>
            </a>
            <a href="<?= $basePath ?>/admin/pedidos" class="action-btn">
                <i class="bi bi-receipt action-icon"></i>
                <span class="action-text">Pedidos</span>
            </a>
            <a href="<?= $basePath ?>/admin/tema" class="action-btn">
                <i class="bi bi-palette action-icon"></i>
                <span class="action-text">Tema da Loja</span>
            </a>
        </div>
    </div>
</div>

<style>
.dashboard-welcome {
    max-width: 1200px;
}
.welcome-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.welcome-card h2 {
    margin-bottom: 1.5rem;
    color: #333;
    font-size: 1.75rem;
}
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}
.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.info-label {
    font-weight: 600;
    color: #666;
    font-size: 0.875rem;
}
.info-value {
    color: #333;
    font-size: 1rem;
}
.quick-actions {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.quick-actions h3 {
    margin-bottom: 1.5rem;
    color: #333;
    font-size: 1.5rem;
}
.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}
.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: #F7931E;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: transform 0.2s, box-shadow 0.2s;
    text-align: center;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.action-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: white;
}
.action-text {
    font-weight: 600;
    font-size: 0.95rem;
}
</style>


