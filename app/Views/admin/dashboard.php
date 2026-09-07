<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pedidos · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <link rel="stylesheet" href="/assets/orders.css?v=<?= filemtime(BASE_PATH . '/public/assets/orders.css') ?>">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed order-admin-page store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <?php
    $totalOrders = array_sum(array_map('count', $byStatus));
    $activeOrders = $totalOrders - count($byStatus['entregado']) - count($byStatus['cancelado']);
    ?>
    <header class="crud-header">
        <div><h1>Pedidos</h1><p>Avanza cada pedido con la siguiente acción operativa.</p></div>
        <div class="order-summary">
            <span><b><?= $activeOrders ?></b> activos</span>
            <span><b><?= count($byStatus['nuevo']) ?></b> nuevos</span>
            <span><b><?= count($byStatus['listo']) ?></b> listos</span>
        </div>
    </header>
    <section class="section admin-board">
        <?php foreach ($statuses as $index => $status): ?>
            <div class="card admin-column admin-column-<?= e($status) ?>">
                <div class="column-head">
                    <span class="step-dot"><?= $index + 1 ?></span>
                    <div>
                        <h3><?= e($statusLabels[$status] ?? $status) ?></h3>
                        <small><?= count($byStatus[$status]) ?> pedido<?= count($byStatus[$status]) === 1 ? '' : 's' ?></small>
                    </div>
                </div>
                <?php foreach ($byStatus[$status] as $order): ?>
                    <article class="order-card">
                        <a class="order-card-main" href="/admin/order?id=<?= (int) $order['id'] ?><?= isset($_GET['tenant']) ? '&tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                            <span class="order-card-top"><b><?= e($order['folio']) ?></b><strong><?= money($order['total']) ?></strong></span>
                            <span class="order-card-client"><?= e($order['cliente_nombre']) ?></span>
                            <span class="order-card-meta"><?= e($order['tipo']) ?> · <?= e($order['cliente_telefono'] ?? '') ?></span>
                        </a>
                        <?php if ($order['siguientes_estados']): ?>
                            <form class="quick-status" method="post" action="/admin/order/status<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                                <input type="hidden" name="estado" value="<?= e($order['siguientes_estados'][0]) ?>">
                                <input type="hidden" name="return_to" value="/admin<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <button class="small primary" type="submit">Siguiente: <?= e($statusLabels[$order['siguientes_estados'][0]] ?? $order['siguientes_estados'][0]) ?></button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
                <?php if (!$byStatus[$status]): ?><p class="empty-column">Sin pedidos aquí</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
