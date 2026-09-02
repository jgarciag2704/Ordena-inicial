<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pedidos · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>Pedidos</h1><p>Gestiona el tablero operativo por estado.</p></div>
    </header>
    <section class="section admin-board">
        <?php foreach ($statuses as $status): ?>
            <div class="card admin-column">
                <h3><?= e($status) ?> <span class="tag"><?= count($byStatus[$status]) ?></span></h3>
                <?php foreach ($byStatus[$status] as $order): ?>
                    <a class="order-link" href="/admin/order?id=<?= (int) $order['id'] ?><?= isset($_GET['tenant']) ? '&tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                        <b><?= e($order['folio']) ?></b><br>
                        <small class="muted"><?= e($order['cliente_nombre']) ?> · <?= e($order['tipo']) ?> · <?= money($order['total']) ?></small>
                    </a>
                <?php endforeach; ?>
                <?php if (!$byStatus[$status]): ?><p class="muted">Sin pedidos.</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </section>
</main>
</body>
</html>
