<?php
// Partial: Newsletter
// Variáveis esperadas: $basePath, $theme
?>
<!-- Newsletter -->
<section class="newsletter">
    <div class="newsletter-container">
        <h2><?= htmlspecialchars($theme['newsletter_title'] ?? 'Receba nossas ofertas') ?></h2>
        <p><?= htmlspecialchars($theme['newsletter_subtitle'] ?? 'Cadastre-se e receba promoções exclusivas em seu e-mail') ?></p>
        <?php if (isset($_GET['newsletter'])): ?>
            <?php if ($_GET['newsletter'] === 'ok'): ?>
                <div class="newsletter-message success">
                    <i class="bi bi-check-circle icon" style="font-size: 1.25rem;"></i>
                    <span>Inscrição realizada com sucesso!</span>
                </div>
            <?php elseif ($_GET['newsletter'] === 'exists'): ?>
                <div class="newsletter-message warning">
                    <i class="bi bi-exclamation-triangle icon" style="font-size: 1.25rem;"></i>
                    <span>Este e-mail já está cadastrado.</span>
                </div>
            <?php elseif ($_GET['newsletter'] === 'error'): ?>
                <div class="newsletter-message error">
                    <i class="bi bi-x-circle icon" style="font-size: 1.25rem;"></i>
                    <span>Erro ao processar inscrição. Tente novamente.</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <form method="POST" action="<?= $basePath ?>/newsletter/inscrever" class="newsletter-form">
            <input type="text" name="nome" placeholder="Seu nome" aria-label="Nome">
            <input type="email" name="email" placeholder="Seu e-mail" required aria-label="E-mail">
            <button type="submit">Cadastrar</button>
        </form>
    </div>
</section>

