<?php
$basePath = $basePath ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($requestUri, '/ecommerce-v1.0/public') === 0) {
    $basePath = '/ecommerce-v1.0/public';
}
$addresses = $addresses ?? [];
$message = $message ?? null;
$messageType = $messageType ?? 'success';
$editingAddress = $editingAddress ?? null;
?>
<style>
/* Fase 10 - Responsividade para endereços */
@media (max-width: 768px) {
    .addresses-grid {
        grid-template-columns: 1fr !important;
        gap: 1.5rem !important;
    }
    .form-row {
        grid-template-columns: 1fr !important;
    }
}
</style>
<?php ob_start(); ?>
<div class="content-header">
    <h1>Meus Endereços</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

/* Fase 10 - Responsividade */
@media (max-width: 768px) {
    .addresses-grid {
        grid-template-columns: 1fr !important;
    }
}
<div class="addresses-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
    <div>
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 700; color: #333;">
            <?= $editingAddress ? 'Editar Endereço' : 'Adicionar Novo Endereço' ?>
        </h3>
        <form method="POST" action="<?= $basePath ?>/minha-conta/enderecos">
            <input type="hidden" name="id" value="<?= $editingAddress['id'] ?? '' ?>">
            
            <div style="margin-bottom: 1.25rem;">
                <label for="address-type" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Tipo *</label>
                <select id="address-type" name="type" required style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
                    <option value="shipping" <?= ($editingAddress['type'] ?? 'shipping') === 'shipping' ? 'selected' : '' ?>>Entrega</option>
                    <option value="billing" <?= ($editingAddress['type'] ?? '') === 'billing' ? 'selected' : '' ?>>Faturamento</option>
                </select>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="address-zipcode" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">CEP *</label>
                <input type="text" id="address-zipcode" name="zipcode" value="<?= htmlspecialchars($editingAddress['zipcode'] ?? '') ?>" 
                       required placeholder="00000-000" maxlength="9"
                       style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="address-street" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Logradouro *</label>
                <input type="text" id="address-street" name="street" value="<?= htmlspecialchars($editingAddress['street'] ?? '') ?>" 
                       required placeholder="Rua, Avenida, etc."
                       style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="address-number" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Número</label>
                    <input type="text" id="address-number" name="number" value="<?= htmlspecialchars($editingAddress['number'] ?? '') ?>" 
                           placeholder="123"
                           style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
                </div>
                <div>
                    <label for="address-complement" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Complemento</label>
                    <input type="text" id="address-complement" name="complement" value="<?= htmlspecialchars($editingAddress['complement'] ?? '') ?>" 
                           placeholder="Apto, Bloco, etc."
                           style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="address-neighborhood" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Bairro *</label>
                <input type="text" id="address-neighborhood" name="neighborhood" value="<?= htmlspecialchars($editingAddress['neighborhood'] ?? '') ?>" 
                       required placeholder="Nome do bairro"
                       style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="address-city" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Cidade *</label>
                    <input type="text" id="address-city" name="city" value="<?= htmlspecialchars($editingAddress['city'] ?? '') ?>" 
                           required placeholder="Nome da cidade"
                           style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border-color 0.2s;">
                </div>
                <div>
                    <label for="address-state" style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #555; font-size: 0.95rem;">Estado (UF) *</label>
                    <input type="text" id="address-state" name="state" value="<?= htmlspecialchars($editingAddress['state'] ?? '') ?>" 
                           required maxlength="2" placeholder="SP"
                           style="width: 100%; padding: 0.875rem; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; text-transform: uppercase; transition: border-color 0.2s;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; transition: background 0.2s;">
                    <input type="checkbox" name="is_default" value="1" 
                           <?= ($editingAddress['is_default'] ?? 0) ? 'checked' : '' ?>
                           style="width: 18px; height: 18px; cursor: pointer;">
                    <span style="font-weight: 500; color: #555;">Marcar como endereço padrão</span>
                </label>
            </div>

            <button type="submit" style="padding: 0.875rem 2rem; background: #023A8D; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s, transform 0.2s; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="bi bi-<?= $editingAddress ? 'check-circle' : 'plus-circle' ?> icon"></i>
                <?= $editingAddress ? 'Atualizar' : 'Salvar' ?> Endereço
            </button>
        </form>
    </div>

    <div>
        <h3 style="margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 700; color: #333;">Endereços Cadastrados</h3>
        <?php if (!empty($addresses)): ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($addresses as $address): ?>
                    <div style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid #e0e0e0; position: relative; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <?php if ($address['is_default']): ?>
                            <span style="position: absolute; top: 1rem; right: 1rem; background: #2E7D32; color: white; padding: 0.375rem 0.875rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-check-circle icon" style="font-size: 0.875rem;"></i>
                                Padrão
                            </span>
                        <?php endif; ?>
                        <div style="margin-bottom: 0.75rem;">
                            <strong style="font-size: 1rem; color: #333; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-<?= $address['type'] === 'shipping' ? 'truck' : 'credit-card' ?> icon"></i>
                                <?= $address['type'] === 'shipping' ? 'Entrega' : 'Faturamento' ?>
                            </strong>
                        </div>
                        <div style="line-height: 1.8; color: #666; font-size: 0.95rem;">
                            <?= htmlspecialchars($address['street']) ?><?= $address['number'] ? ', ' . htmlspecialchars($address['number']) : '' ?><br>
                            <?php if ($address['complement']): ?>
                                <?= htmlspecialchars($address['complement']) ?><br>
                            <?php endif; ?>
                            <?= htmlspecialchars($address['neighborhood']) ?><br>
                            <?= htmlspecialchars($address['city']) ?> - <?= htmlspecialchars($address['state']) ?><br>
                            CEP: <?= htmlspecialchars($address['zipcode']) ?>
                        </div>
                        <div style="margin-top: 1.25rem; display: flex; gap: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                            <a href="<?= $basePath ?>/minha-conta/enderecos?editar=<?= $address['id'] ?>" 
                               style="color: #023A8D; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-pencil icon"></i>
                                Editar
                            </a>
                            <span style="color: #ddd;">|</span>
                            <a href="<?= $basePath ?>/minha-conta/enderecos/excluir/<?= $address['id'] ?>" 
                               onclick="return confirm('Tem certeza que deseja excluir este endereço?')"
                               style="color: #c62828; text-decoration: none; font-size: 0.9rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.5rem;">
                                <i class="bi bi-trash icon"></i>
                                Excluir
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: #666; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                <i class="bi bi-geo-alt icon" style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.5; color: #ccc;"></i>
                <p style="font-size: 1rem; margin: 0;">Nenhum endereço cadastrado</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layout.php'; ?>


