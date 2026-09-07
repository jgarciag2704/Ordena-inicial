<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-cafe <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell cafe-shell">
    <header class="top cafe-top">
        <div>
            <div class="tag">Pedido directo</div>
            <div class="brand"><?= e($business['nombre']) ?></div>
        </div>
        <button class="chip cafe-cart-chip" onclick="openCart()"><span>Carrito</span><b id="cartCount">0</b></button>
    </header>

    <section class="hero hero-bg cafe-hero">
        <div class="cafe-hero-copy">
            <div class="tag">Cafeteria de especialidad</div>
            <h1><?= e($business['hero_titulo'] ?? 'Un momento dulce, directo a tu mesa.') ?></h1>
            <p><?= e($business['hero_subtitulo'] ?? 'Pan, cafe y postres preparados con calma.') ?></p>
            <button class="primary hero-cta" onclick="document.querySelector('#products').scrollIntoView({behavior:'smooth'})">Ver menu</button>
        </div>
    </section>

    <section class="cafe-gallery">
        <article><span>01</span><b>Postres suaves</b><small>Porciones listas para acompanar tu bebida.</small></article>
        <article><span>02</span><b>Cafe caliente</b><small>Opciones preparadas al momento.</small></article>
        <article><span>03</span><b>Favoritos del dia</b><small>Lo mas pedido para ordenar rapido.</small></article>
    </section>

    <section class="section cafe-branches-block">
        <div>
            <div class="tag">Donde ordenar</div>
            <h2>Sucursales disponibles</h2>
        </div>
        <div class="branches-public" id="branches"></div>
    </section>

    <section class="section cafe-menu-block">
        <div class="cafe-menu-head">
            <div>
                <div class="tag">Elige tu antojo</div>
                <h2>Menu</h2>
            </div>
            <p class="muted">Filtra por categoria y agrega tus favoritos al carrito.</p>
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
