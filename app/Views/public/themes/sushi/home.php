<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-sushi <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'oscuro') ?>">
<main class="shell sushi-shell">
    <header class="top sushi-top">
        <div class="brand"><?= e($business['nombre']) ?></div>
        <button class="chip" onclick="openCart()">Carrito <span id="cartCount">0</span></button>
    </header>

    <section class="hero hero-bg sushi-hero">
        <div class="tag">Sushi · Asian kitchen</div>
        <h1><?= e($business['hero_titulo'] ?? 'Precision, frescura y sabor.') ?></h1>
        <p><?= e($business['hero_subtitulo'] ?? 'Rollos, bowls y entradas en un menu compacto.') ?></p>
    </section>

    <section class="section sushi-layout">
        <aside>
            <h2>Categorias</h2>
            <div class="chips" id="categories">
                <button class="chip active" data-category="all">Todo</button>
                <?php foreach ($categories as $category): ?>
                    <button class="chip" data-category="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="branches-public" id="branches"></div>
        </aside>
        <div class="grid" id="products"></div>
    </section>
</main>
<?php require BASE_PATH . '/app/Views/public/shared/cart-drawer.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/product-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/checkout-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/runtime.php'; ?>
</body>
</html>
