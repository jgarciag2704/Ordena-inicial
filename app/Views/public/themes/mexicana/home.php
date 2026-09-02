<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-mexicana <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell mexicana-shell">
    <header class="top mexicana-top">
        <div><div class="tag">Comida casera</div><div class="brand"><?= e($business['nombre']) ?></div></div>
        <button class="chip" onclick="openCart()">Carrito <span id="cartCount">0</span></button>
    </header>

    <section class="hero hero-bg mexicana-hero">
        <div>
            <h1><?= e($business['hero_titulo'] ?? 'Sabor de casa para pedir facil.') ?></h1>
            <p><?= e($business['hero_subtitulo'] ?? 'Elige categoria, arma tu pedido y recoge o recibe en casa.') ?></p>
        </div>
        <div class="branches-public" id="branches"></div>
    </section>

    <section class="section mexicana-categories">
        <h2>Elige que se te antoja</h2>
        <div class="chips" id="categories">
            <button class="chip active" data-category="all">Todo</button>
            <?php foreach ($categories as $category): ?>
                <button class="chip" data-category="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?></button>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section mexicana-menu-block">
        <div class="grid" id="products"></div>
    </section>
</main>
<?php require BASE_PATH . '/app/Views/public/shared/cart-drawer.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/product-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/checkout-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/runtime.php'; ?>
</body>
</html>
