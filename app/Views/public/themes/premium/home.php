<!doctype html>
<html lang="es">
<head><?php require BASE_PATH . '/app/Views/public/shared/head.php'; ?></head>
<body class="theme theme-premium <?= !empty($business['hero_image_url']) ? 'has-hero-image' : '' ?> store-bg-<?= e($business['fondo_estilo'] ?? 'claro') ?>">
<main class="shell premium-shell">
    <header class="top premium-top">
        <div class="brand"><?= e($business['nombre']) ?></div>
        <nav><button class="chip" onclick="openCart()">Pedido <span id="cartCount">0</span></button></nav>
    </header>

    <section class="hero hero-bg premium-hero">
        <div class="tag">Restaurante premium</div>
        <h1><?= e($business['hero_titulo'] ?? 'Una experiencia sobria, tambien para llevar.') ?></h1>
        <p><?= e($business['hero_subtitulo'] ?? 'Ordena ahora o consulta nuestras sucursales disponibles.') ?></p>
        <?php if (!empty($business['comer_aqui_url'])): ?>
            <a class="primary hero-cta" href="<?= e($business['comer_aqui_url']) ?>" target="_blank" rel="noopener">Reservar mesa</a>
        <?php endif; ?>
    </section>

    <section class="premium-experience">
        <article>Ingredientes seleccionados</article><article>Servicio cuidado</article><article>Pedido directo</article>
    </section>

    <section class="section premium-menu-block">
        <div class="premium-side"><h2>Sucursales</h2><div class="branches-public" id="branches"></div></div>
        <div>
            <h2>Menu para ordenar</h2>
            <div class="chips" id="categories">
                <button class="chip active" data-category="all">Recomendados</button>
                <?php foreach ($categories as $category): ?>
                    <button class="chip" data-category="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?></button>
                <?php endforeach; ?>
            </div>
            <div class="grid" id="products"></div>
        </div>
    </section>
</main>
<?php require BASE_PATH . '/app/Views/public/shared/cart-drawer.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/product-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/checkout-modal.php'; ?>
<?php require BASE_PATH . '/app/Views/public/shared/runtime.php'; ?>
</body>
</html>
