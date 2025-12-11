<?php
// Partial: Header completo da loja
// Variáveis esperadas: $basePath, $theme, $loja, $cartTotalItems, $cartSubtotal
?>
<!-- Top Bar -->
<div class="topbar">
    <?= htmlspecialchars($theme['topbar_text']) ?>
</div>

<!-- Header - Layout em uma linha (Desktop) -->
<header class="header">
    <div class="header-container">
        <!-- Logo - Esquerda -->
        <a href="<?= $basePath ?>/" class="header-logo">
            <?php if (!empty($theme['logo_url'])): ?>
                <img src="<?= media_url($theme['logo_url']) ?>" alt="<?= htmlspecialchars($loja['nome']) ?>" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                <span style="display: none;"><?= htmlspecialchars($loja['nome']) ?></span>
            <?php else: ?>
                <?= htmlspecialchars($loja['nome']) ?>
            <?php endif; ?>
        </a>
        
        <!-- Barra de Busca - Centro (flex-grow) -->
        <div class="header-search">
            <form method="GET" action="<?= $basePath ?>/produtos">
                <input type="text" name="q" placeholder="Buscar produtos...">
                <button type="submit"><i class="bi bi-search icon"></i> Buscar</button>
            </form>
        </div>
        
        <!-- Menu + Ícones - Direita -->
        <div class="header-right">
            <!-- Menu de Navegação -->
            <nav class="header-nav">
                <ul class="header-menu">
                    <?php foreach ($theme['menu_main'] as $item): ?>
                        <?php if (!empty($item['enabled'])): ?>
                            <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <!-- Ícones (Conta + Carrinho) -->
            <div class="header-icons">
                <?php 
                // Verificar se sessão já está ativa antes de iniciar
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $isCustomerLoggedIn = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
                ?>
                <?php if ($isCustomerLoggedIn): ?>
                    <a href="<?= $basePath ?>/minha-conta" class="header-cart">
                        <i class="bi bi-person-circle icon store-icon-primary"></i>
                        <span style="margin-left: 0.5rem;"><?= htmlspecialchars($_SESSION['customer_name'] ?? 'Minha Conta') ?></span>
                    </a>
                <?php else: ?>
                    <a href="<?= $basePath ?>/minha-conta/login" class="header-cart">
                        <i class="bi bi-person icon store-icon-primary"></i>
                        <span style="margin-left: 0.5rem;">Entrar</span>
                    </a>
                <?php endif; ?>
                <a href="<?= $basePath ?>/carrinho" class="header-cart">
                    <div class="cart-icon">
                        <i class="bi bi-cart3 icon store-icon-primary"></i>
                        <?php if ($cartTotalItems > 0): ?>
                            <span class="cart-badge"><?= $cartTotalItems ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($cartTotalItems > 0): ?>
                        <div class="cart-info">
                            <span class="cart-count"><?= $cartTotalItems ?> <?= $cartTotalItems === 1 ? 'item' : 'itens' ?></span>
                            <span class="cart-total">R$ <?= number_format($cartSubtotal, 2, ',', '.') ?></span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>
            
            <!-- Botão Mobile Menu (oculto no desktop) -->
            <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="bi bi-list icon"></i></button>
        </div>
        
        <!-- Menu Mobile (oculto no desktop) -->
        <div class="mobile-menu" id="mobileMenu">
            <ul>
                <?php foreach ($theme['menu_main'] as $item): ?>
                    <?php if (!empty($item['enabled'])): ?>
                        <li><a href="<?= $basePath ?><?= htmlspecialchars($item['url']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</header>

