<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Super Admin · Ordena</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="superadmin-page">
<main class="shell admin-crud">
    <nav class="admin-navbar super-navbar">
        <a class="nav-brand" href="/superadmin">Ordena Super Admin</a>
        <div class="nav-links"><a class="nav-link" href="/superadmin">Negocios</a></div>
        <form method="post" action="/superadmin/logout"><button class="chip">Salir</button></form>
    </nav>

    <header class="crud-header">
        <div>
            <h1>Negocios</h1>
            <p>Gestiona los tenants, accesos iniciales y tiendas activas.</p>
        </div>
        <button class="primary" onclick="document.querySelector('#newBusiness').showModal()">+ Nuevo negocio</button>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="crud-card">
        <div class="crud-table super-business-table">
            <div class="crud-row crud-row-head">
                <span>Negocio</span>
                <span>Slug</span>
                <span>Folio</span>
                <span>Pedidos</span>
                <span>Estado</span>
                <span>Acciones</span>
            </div>

            <?php foreach ($businesses as $business): ?>
                <div class="crud-row">
                    <span>
                        <b><?= e($business['nombre']) ?></b><br>
                        <small class="muted">Tenant #<?= (int) $business['id'] ?></small>
                    </span>
                    <span><code><?= e($business['slug']) ?></code></span>
                    <span><span class="soft-badge"><?= e($business['folio_prefijo']) ?></span></span>
                    <span><?= (int) $business['pedidos_count'] ?></span>
                    <span><span class="status-pill ok">Activo</span></span>
                    <span class="row-actions super-actions">
                        <a class="btn-view" href="/?tenant=<?= urlencode($business['slug']) ?>" target="_blank">Tienda</a>
                        <a class="btn-edit" href="/admin/login?tenant=<?= urlencode($business['slug']) ?>" target="_blank">Admin</a>
                        <form method="post" action="/superadmin/businesses/reset-admin-password" onsubmit="return confirm('¿Restablecer contraseña del admin a Temporal1?')">
                            <input type="hidden" name="business_id" value="<?= (int) $business['id'] ?>">
                            <button class="btn-muted">Reset pass</button>
                        </form>
                    </span>
                </div>
            <?php endforeach; ?>

            <?php if (!$businesses): ?>
                <p class="empty-state">Aún no hay negocios. Usa `+ Nuevo negocio` para crear el primero.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<dialog id="newBusiness" class="crud-modal business-modal">
    <form class="card" method="post" action="/superadmin/businesses">
        <div class="modal-head">
            <div><h2>Nuevo negocio</h2><p class="muted">Crea el tenant, su sucursal principal y el admin inicial.</p></div>
            <button type="button" class="chip" onclick="document.querySelector('#newBusiness').close()">Cerrar</button>
        </div>

        <div class="form-section-title">Negocio</div>
        <div class="edit-grid super-form-grid">
            <label>Nombre<input name="nombre" required placeholder="Ej. Punto Rosa"></label>
            <label>Slug<input name="slug" required pattern="[a-z0-9-]{2,80}" placeholder="puntorosa"></label>
            <label>Prefijo de folio<input name="folio_prefijo" required pattern="[A-Za-z0-9]{2,12}" placeholder="PR"></label>
        </div>

        <div class="form-section-title">Sucursal principal</div>
        <div class="edit-grid super-form-grid two-cols">
            <label>Nombre<input name="sucursal_nombre" required value="Sucursal principal"></label>
            <label>Teléfono<input name="sucursal_telefono" placeholder="10 dígitos"></label>
            <label class="wide">Dirección<textarea name="sucursal_direccion" required placeholder="Calle, número, colonia"></textarea></label>
        </div>

        <div class="form-section-title">Administrador del negocio</div>
        <div class="edit-grid super-form-grid two-cols">
            <label>Nombre<input name="admin_nombre" required value="Administrador"></label>
            <label>Email<input name="admin_email" type="email" required placeholder="admin@negocio.com"></label>
            <label class="wide">Contraseña inicial<input name="admin_password" type="password" required minlength="8" pattern="(?=.*[A-Z]).{8,}" placeholder="Mín. 8 y una mayúscula; permite símbolos"></label>
        </div>

        <button class="primary" style="width:100%">Crear negocio</button>
    </form>
</dialog>
</body>
</html>
