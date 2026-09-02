<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-burger <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell burger-shell">
    <header class="top burger-top">
        <div>
            <div class="tag">Pedido directo</div>
            <div class="brand"><?= e($business['nombre']) ?></div>
        </div>
        <button class="chip" onclick="openCart()">Carrito <span id="cartCount">0</span></button>
    </header>

    <section class="hero hero-bg burger-hero">
        <div class="burger-copy">
            <div class="tag">Abierto ahora · 25-35 min</div>
            <h1><?= e($business['hero_titulo'] ?? 'Tu antojo, directo del restaurante.') ?></h1>
            <p><?= e($business['hero_subtitulo'] ?? 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.') ?></p>
        </div>
        <aside class="burger-promo">
            <span>Promo destacada</span>
            <b>Combo urbano</b>
            <small>Hamburguesa, papas y bebida.</small>
        </aside>
    </section>

    <section class="section">
        <h2>Sucursales</h2>
        <div class="branches-public" id="branches"></div>
    </section>

    <section class="section burger-menu-block">
        <h2>Menú</h2>
        <div class="chips" id="categories">
            <button class="chip active" data-category="all">Populares</button>
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
