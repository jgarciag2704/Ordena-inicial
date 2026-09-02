<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sucursales · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>Sucursales</h1><p>Administra los puntos de venta del negocio.</p></div>
        <button class="primary" onclick="document.querySelector('#newBranch').showModal()">+ Nueva sucursal</button>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="crud-card">
        <div class="crud-table branches-table">
            <div class="crud-row crud-row-head">
                <span>Nombre</span><span>Dirección</span><span>Teléfono</span><span>Estado</span><span>Acciones</span>
            </div>
            <?php foreach ($branches as $branch): ?>
                <details class="crud-item">
                    <summary class="crud-row">
                        <span><b><?= e($branch['nombre']) ?></b></span>
                        <span><?= e($branch['direccion']) ?></span>
                        <span><?= e($branch['telefono']) ?></span>
                        <span><span class="status-pill <?= (int) $branch['activa'] === 1 ? 'ok' : 'off' ?>"><?= (int) $branch['activa'] === 1 ? 'Activa' : 'Inactiva' ?></span></span>
                        <span class="row-actions">
                            <span class="btn-edit">Editar</span>
                            <form method="post" action="/admin/branches/toggle<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <input type="hidden" name="id" value="<?= (int) $branch['id'] ?>">
                                <button class="btn-muted"><?= (int) $branch['activa'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        </span>
                    </summary>
                    <div class="crud-detail">
                        <form class="edit-grid branch-edit" method="post" action="/admin/branches/update<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                            <input type="hidden" name="id" value="<?= (int) $branch['id'] ?>">
                            <label>Nombre<input name="nombre" required value="<?= e($branch['nombre']) ?>"></label>
                            <label>Teléfono<input name="telefono" value="<?= e($branch['telefono']) ?>"></label>
                            <label class="wide">Dirección<textarea name="direccion" required><?= e($branch['direccion']) ?></textarea></label>
                            <button class="primary">Guardar cambios</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<dialog id="newBranch" class="crud-modal">
    <form class="card" method="post" action="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
        <div class="modal-head"><div><h2>Nueva sucursal</h2><p class="muted">Agrega un punto de venta.</p></div><button type="button" class="chip" onclick="document.querySelector('#newBranch').close()">Cerrar</button></div>
        <label>Nombre<input name="nombre" required placeholder="Sucursal Centro"></label>
        <label>Teléfono<input name="telefono" placeholder="10 dígitos"></label>
        <label>Dirección<textarea name="direccion" required placeholder="Calle, número, colonia"></textarea></label>
        <button class="primary" style="width:100%">Crear sucursal</button>
    </form>
</dialog>
</body>
</html>
