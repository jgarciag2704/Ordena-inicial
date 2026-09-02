<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Horarios · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>Horarios</h1><p>Define los horarios disponibles por sucursal.</p></div>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="crud-tools">
        <form method="get" action="/admin/hours">
            <?php if (isset($_GET['tenant'])): ?><input type="hidden" name="tenant" value="<?= e((string) $_GET['tenant']) ?>"><?php endif; ?>
            <label>Sucursal</label>
            <div class="tool-inline">
                <select name="branch_id" onchange="this.form.submit()">
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === (int) $branchId ? 'selected' : '' ?>><?= e($branch['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
                <a class="chip" href="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">Gestionar sucursales</a>
            </div>
        </form>
    </section>

    <?php if (!$branches): ?>
        <p class="alert error">Primero crea una sucursal.</p>
    <?php else: ?>
    <form class="crud-card hours-card" method="post" action="/admin/hours<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
        <input type="hidden" name="branch_id" value="<?= (int) $branchId ?>">
        <div class="crud-row crud-row-head hours-row">
            <span>Día</span><span>Apertura</span><span>Cierre</span><span>Estado</span>
        </div>
        <?php foreach ($hours as $hour): ?>
            <div class="crud-row hours-row">
                <span><b><?= e($hour['dia_nombre']) ?></b></span>
                <span><input type="time" name="hours[<?= (int) $hour['dia_semana'] ?>][abre]" value="<?= e(substr((string) $hour['abre'], 0, 5)) ?>"></span>
                <span><input type="time" name="hours[<?= (int) $hour['dia_semana'] ?>][cierra]" value="<?= e(substr((string) $hour['cierra'], 0, 5)) ?>"></span>
                <span><label class="check-inline"><input type="checkbox" name="hours[<?= (int) $hour['dia_semana'] ?>][cerrado]" value="1" <?= (int) $hour['cerrado'] === 1 ? 'checked' : '' ?>> Cerrado</label></span>
            </div>
        <?php endforeach; ?>
        <div class="form-footer"><button class="primary">Guardar horarios</button></div>
    </form>
    <?php endif; ?>
</main>
</body>
</html>
