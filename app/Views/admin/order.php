<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($order['folio']) ?> · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <a class="chip" href="/admin<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">Volver</a>
    <section class="hero">
        <div class="tag">PEDIDO <?= e($order['estado']) ?></div>
        <h1><?= e($order['folio']) ?></h1>
        <p><?= e($order['cliente_nombre']) ?> · <?= e($order['cliente_telefono']) ?> · <?= e($order['tipo']) ?></p>
    </section>
    <div class="grid order-grid">
        <section class="card">
            <h2>Detalle</h2>
            <?php foreach ($order['detalles'] as $detail): ?>
                <div class="row">
                    <div>
                        <b><?= e($detail['nombre_snapshot']) ?></b><br>
                        <?php foreach ($detail['opciones'] as $option): ?>
                            <small class="muted"><?= e($option['opcion_nombre_snapshot']) ?>: <?= e($option['valor_nombre_snapshot']) ?> <?= $option['precio_extra_snapshot'] > 0 ? '+' . money($option['precio_extra_snapshot']) : '' ?></small><br>
                        <?php endforeach; ?>
                        <?php if ($detail['notas']): ?><small class="muted">Indicaciones: <?= e($detail['notas']) ?></small><?php endif; ?>
                    </div>
                    <b><?= money($detail['total']) ?></b>
                </div>
            <?php endforeach; ?>
            <div class="row"><b>Total</b><b><?= money($order['total']) ?></b></div>
        </section>
        <section class="card">
            <h2>Cliente y entrega</h2>
            <p><b>Pago:</b> <?= e($order['forma_pago']) ?></p>
            <?php if ($order['direccion_entrega']): ?><p><b>Dirección:</b> <?= e($order['direccion_entrega']) ?></p><?php endif; ?>
            <?php if ($order['mesa']): ?><p><b>Mesa:</b> <?= e($order['mesa']) ?></p><?php endif; ?>
            <form method="post" action="/admin/order/status<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                <label>Estado
                    <select name="estado">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $status === $order['estado'] ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="primary" style="width:100%">Actualizar estado</button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
