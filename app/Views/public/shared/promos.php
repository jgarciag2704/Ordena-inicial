<?php
$promoted = array_values(array_filter($products, fn (array $p): bool => (int) ($p['destacado'] ?? 0) === 1));
if (!$promoted) {
    return;
}
?>
<section class="section promo-section" id="promos">
    <div class="promo-head">
        <div>
            <div class="tag">No te lo pierdas</div>
            <h2>⭐ Promociones</h2>
        </div>
        <p class="muted">Los favoritos de <?= e($business['nombre']) ?>, listos para pedir.</p>
    </div>
    <div class="promo-rail">
        <?php foreach ($promoted as $product): ?>
            <article class="card promo-card" onclick="customize(<?= (int) $product['id'] ?>)">
                <?php if (!empty($product['imagen_thumb'])): ?>
                    <img class="photo product-img promo-photo" src="<?= e($product['imagen_thumb']) ?>" alt="<?= e($product['nombre']) ?>">
                <?php else: ?>
                    <div class="photo promo-photo"></div>
                <?php endif; ?>
                <span class="promo-flag">Promo</span>
                <div class="promo-body">
                    <h3><?= e($product['nombre']) ?></h3>
                    <?php if (!empty($product['descripcion'])): ?><p><?= e($product['descripcion']) ?></p><?php endif; ?>
                    <div class="price"><?= money($product['precio']) ?><button class="primary small">Agregar</button></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
