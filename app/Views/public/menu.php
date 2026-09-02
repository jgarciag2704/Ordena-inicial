<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ordena · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        :root {
            --brand: <?= e($business['color_primario'] ?? '#cc4b25') ?>;
            --dark: <?= e($business['color_secundario'] ?? '#2b201b') ?>;
            --cream: <?= e($business['color_fondo'] ?? '#fffaf4') ?>;
            --ink: <?= e($business['color_texto'] ?? '#171514') ?>;
        }
        body { font-family: <?= e($business['fuente'] ?? 'Inter, system-ui, sans-serif') ?>; }
    </style>
</head>
<body class="store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <header class="top">
        <div>
            <div class="tag">PEDIDO DIRECTO</div>
            <div class="brand"><?= e($business['nombre']) ?></div>
        </div>
        <button class="chip" onclick="openCart()">Carrito <span id="cartCount">0</span></button>
    </header>

    <section class="hero">
        <div class="tag">Abierto ahora · 25-35 min</div>
        <h1><?= e($business['hero_titulo'] ?? 'Tu antojo, directo del restaurante.') ?></h1>
        <p><?= e($business['hero_subtitulo'] ?? 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.') ?></p>
        <?php if (!empty($business['comer_aqui_url'])): ?>
            <a class="primary hero-cta" href="<?= e($business['comer_aqui_url']) ?>" target="_blank" rel="noopener">Comer aquí</a>
        <?php endif; ?>
    </section>

    <section class="section">
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

<button class="cart" onclick="openCart()">Ver carrito · <span id="cartTotal">$0</span></button>

<div class="drawer" id="drawer">
    <aside class="panel">
        <button class="chip" onclick="closeAll()">Cerrar x</button>
        <h2>Tu carrito</h2>
        <div id="cartItems"></div>
        <div class="totals"><div class="row"><b>Total</b><b id="totalAside">$0</b></div></div>
        <button class="primary" style="width:100%" onclick="checkout()">Continuar pedido</button>
    </aside>
</div>

<div class="modal" id="productModal">
    <section class="modalbox">
        <button class="chip" onclick="closeAll()">Cerrar x</button>
        <div id="productForm"></div>
    </section>
</div>

<div class="modal" id="checkoutModal">
    <section class="modalbox" id="checkoutContent"></section>
</div>

<script>
window.ORDENA = <?= json_encode(['products' => $products, 'branches' => $branches, 'cart' => $cart, 'total' => $total], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="/assets/app.js"></script>
</body>
</html>
