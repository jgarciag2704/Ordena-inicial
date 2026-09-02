<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Cambiar Contraseña · Ordena</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="shell">
    <section class="hero">
        <div class="tag">PRIMER ACCESO</div>
        <h1>Cambia tu contraseña temporal</h1>
        <p>Debes definir una contraseña nueva antes de entrar al panel. Mínimo 8 caracteres y al menos una mayúscula. Puede incluir números y símbolos.</p>
    </section>
    <form class="card" method="post" action="/superadmin/password" style="max-width:420px">
        <?php if ($error): ?><p class="tag"><?= e($error) ?></p><?php endif; ?>
        <label>Nueva contraseña<input name="password" type="password" required minlength="8" pattern="(?=.*[A-Z]).{8,}"></label>
        <label>Confirmar contraseña<input name="password_confirm" type="password" required minlength="8" pattern="(?=.*[A-Z]).{8,}"></label>
        <button class="primary" style="width:100%">Guardar y continuar</button>
    </form>
</main>
</body>
</html>
