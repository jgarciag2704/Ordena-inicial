<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>WhatsApp · Super Admin</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="superadmin-page">
<main class="shell admin-crud">
    <nav class="admin-navbar super-navbar">
        <a class="nav-brand" href="/superadmin">Ordena Super Admin</a>
        <div class="nav-links">
            <a class="nav-link" href="/superadmin">Negocios</a>
            <a class="nav-link" href="/superadmin/whatsapp">WhatsApp</a>
        </div>
        <form method="post" action="/superadmin/logout"><button class="chip">Salir</button></form>
    </nav>

    <header class="crud-header">
        <div>
            <h1>WhatsApp</h1>
            <p>Estado de vinculación de WhatsApp de todos los negocios.</p>
        </div>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <?php if (!$enabled): ?>
        <p class="alert error">Evolution API no está configurada en el servidor. Define EVOLUTION_API_URL y EVOLUTION_API_API_KEY en .env</p>
    <?php endif; ?>

    <section class="crud-card">
        <div class="crud-table">
            <div class="crud-row crud-row-head">
                <span>Negocio</span>
                <span>Slug</span>
                <span>Teléfono vinculado</span>
                <span>Estado</span>
                <span>Conectado</span>
                <span>Acciones</span>
            </div>

            <?php foreach ($businesses as $biz): ?>
                <?php $waStatus = $biz['whatsapp_instance_status'] ?? 'disconnected'; ?>
                <div class="crud-row">
                    <span>
                        <b><?= e($biz['nombre']) ?></b><br>
                        <small class="muted">#<?= (int) $biz['id'] ?></small>
                    </span>
                    <span><code><?= e($biz['slug']) ?></code></span>
                    <span><?= $biz['whatsapp_instance_phone'] ? e($biz['whatsapp_instance_phone']) : '<span class="muted">—</span>' ?></span>
                    <span>
                        <?php if ($waStatus === 'connected'): ?>
                            <span class="status-pill ok">Conectado</span>
                        <?php elseif ($waStatus === 'connecting'): ?>
                            <span class="status-pill" style="background:#fff3cd;color:#856404;">Conectando</span>
                        <?php elseif ($waStatus === 'error'): ?>
                            <span class="status-pill off">Error</span>
                        <?php else: ?>
                            <span class="status-pill off">Desconectado</span>
                        <?php endif; ?>
                    </span>
                    <span>
                        <?php if ($biz['whatsapp_instance_connected_at']): ?>
                            <?= e(date('d/m/Y H:i', strtotime((string) $biz['whatsapp_instance_connected_at']))) ?>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </span>
                    <span class="row-actions">
                        <?php if ($waStatus === 'connected' || ($biz['whatsapp_instance_name'] ?? null)): ?>
                            <form method="post" action="/superadmin/whatsapp/disconnect" onsubmit="return confirm('¿Desconectar y eliminar la instancia de WhatsApp de <?= e($biz['nombre']) ?>?');">
                                <input type="hidden" name="business_id" value="<?= (int) $biz['id'] ?>">
                                <button class="btn-muted">Desconectar</button>
                            </form>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <?php if (!$businesses): ?>
                <p class="empty-state">No hay negocios activos.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
