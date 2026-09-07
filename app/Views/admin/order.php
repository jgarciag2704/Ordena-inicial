<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= e($order['folio']) ?> · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <link rel="stylesheet" href="/assets/orders.css?v=<?= filemtime(BASE_PATH . '/public/assets/orders.css') ?>">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed order-admin-page store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <?php
    $flowLabels = array_filter($statusLabels, fn (string $status): bool => $status !== 'cancelado', ARRAY_FILTER_USE_KEY);
    $currentStep = array_search($order['estado'], array_keys($flowLabels), true);
    ?>
    <a class="chip back-chip" href="/admin<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">Volver al tablero</a>
    <section class="order-hero">
        <div>
            <div class="tag">Pedido <?= e($statusLabels[$order['estado']] ?? $order['estado']) ?></div>
            <h1><?= e($order['folio']) ?></h1>
            <p><?= e($order['cliente_nombre']) ?> · <?= e($order['cliente_telefono']) ?> · <?= e($order['tipo']) ?></p>
        </div>
        <div class="order-total-card">
            <span>Total</span>
            <b><?= money($order['total']) ?></b>
        </div>
    </section>
    <section class="order-progress card">
        <?php foreach ($flowLabels as $status => $label): ?>
            <?php $stepIndex = array_search($status, array_keys($flowLabels), true); ?>
            <div class="progress-step <?= $stepIndex <= $currentStep && $order['estado'] !== 'cancelado' ? 'done' : '' ?> <?= $status === $order['estado'] ? 'current' : '' ?>">
                <span></span>
                <small><?= e($label) ?></small>
            </div>
        <?php endforeach; ?>
    </section>
    <div class="grid order-grid">
        <section class="card order-detail-card">
            <div class="card-title">
                <span>Detalle</span>
                <b><?= count($order['detalles']) ?> producto<?= count($order['detalles']) === 1 ? '' : 's' ?></b>
            </div>
            <?php foreach ($order['detalles'] as $detail): ?>
                <div class="order-item-row">
                    <div>
                        <b><?= e($detail['nombre_snapshot']) ?></b>
                        <?php foreach ($detail['opciones'] as $option): ?>
                            <small><?= e($option['opcion_nombre_snapshot']) ?>: <?= e($option['valor_nombre_snapshot']) ?> <?= $option['precio_extra_snapshot'] > 0 ? '+' . money($option['precio_extra_snapshot']) : '' ?></small>
                        <?php endforeach; ?>
                        <?php if ($detail['notas']): ?><small class="note">Indicaciones: <?= e($detail['notas']) ?></small><?php endif; ?>
                    </div>
                    <b><?= money($detail['total']) ?></b>
                </div>
            <?php endforeach; ?>
            <div class="order-total-row"><b>Total</b><b><?= money($order['total']) ?></b></div>
        </section>
        <section class="card order-actions-card">
            <div class="card-title"><span>Cliente y entrega</span></div>
            <div class="info-list">
                <p><span>Pago</span><b><?= e($order['forma_pago']) ?></b></p>
                <?php if ($order['direccion_entrega']): ?><p><span>Dirección</span><b><?= e($order['direccion_entrega']) ?></b></p><?php endif; ?>
                <?php if ($order['mesa']): ?><p><span>Mesa</span><b><?= e($order['mesa']) ?></b></p><?php endif; ?>
            </div>
            <div class="status-flow">
                <p><span>Estado actual</span><b class="status-current"><?= e($statusLabels[$order['estado']] ?? $order['estado']) ?></b></p>
                <?php if ($nextStatuses): ?>
                    <small>Siguientes acciones disponibles</small>
                    <?php foreach ($nextStatuses as $status): ?>
                        <form method="post" action="/admin/order/status<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <input type="hidden" name="estado" value="<?= e($status) ?>">
                            <button class="<?= $status === 'cancelado' ? 'danger-button' : 'primary next-action' ?>" type="submit"><?= $status === 'cancelado' ? 'Cancelar pedido' : 'Pasar a ' . e($statusLabels[$status] ?? $status) ?></button>
                        </form>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="final-state">Este pedido ya está en un estado final.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
</body>
</html>
