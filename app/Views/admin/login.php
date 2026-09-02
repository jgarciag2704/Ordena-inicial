<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="shell">
    <section class="hero">
        <div class="tag">ADMIN</div>
        <h1><?= e($business['nombre']) ?></h1>
        <p>Ingresa para ver y actualizar pedidos.</p>
    </section>
    <form class="card" method="post" action="/admin/login<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" style="max-width:420px">
        <?php if ($error): ?><p class="tag"><?= e($error) ?></p><?php endif; ?>
        <label>Email<input name="email" type="email" required value="admin@laburgueria.test"></label>
        <label>Contraseña<input name="password" type="password" required></label>
        <button class="primary" style="width:100%">Entrar</button>
    </form>
</main>
</body>
</html>
