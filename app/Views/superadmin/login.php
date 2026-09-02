<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Super Admin · Ordena</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="shell">
    <section class="hero">
        <div class="tag">SUPER ADMIN</div>
        <h1>Administración global</h1>
        <p>Alta y gestión inicial de negocios.</p>
    </section>
    <form class="card" method="post" action="/superadmin/login" style="max-width:420px">
        <?php if ($error): ?><p class="tag"><?= e($error) ?></p><?php endif; ?>
        <label>Email<input name="email" type="email" required value="jgarciag2704@gmail.com"></label>
        <label>Contraseña<input name="password" type="password" required></label>
        <button class="primary" style="width:100%">Entrar</button>
    </form>
</main>
</body>
</html>
