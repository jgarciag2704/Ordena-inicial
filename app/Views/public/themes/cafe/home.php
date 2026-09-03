<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-cafe <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell cafe-shell">
    <header class="top cafe-top">
        <div class="brand"><?= e($business['nombre']) ?></div>
        <button class="chip" onclick="openCart()">Carrito <span id="cartCount">0</span></button>
    </header>

    <section class="hero hero-bg cafe-hero">
        <div class="tag">Cafeteria de especialidad</div>
        <h1><?= e($business['hero_titulo'] ?? 'Un momento dulce, directo a tu mesa.') ?></h1>
        <p><?= e($business['hero_subtitulo'] ?? 'Pan, cafe y postres preparados con calma.') ?></p>
    </section>

    <section class="cafe-gallery">
        <article>Postres suaves</article><article>Cafe caliente</article><article>Favoritos del dia</article>
    </section>

    <section class="section cafe-menu-block">
        <div class="cafe-menu-head">
            <h2>Menu</h2>
            <div class="branches-public" id="branches"></div>
        </div>
        <div class="chips" id="categories">
            <button class="chip active" data-category="all">Destacados</button>
            <?php foreach ($categories as $category): ?>
                <button class="chip" data-category="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?></button>
            <?php endforeach; ?>
        </div>
        <div class="grid" id="products"></div>
    </section>
</main>
<?php require BASE_PATH . '/app/Views/public/shared/cart-drawer.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/product-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/checkout-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/runtime.php'; ?>
</body>
</html>
