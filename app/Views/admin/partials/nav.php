<?php $tenantQuery = isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : ''; ?>
<nav class="admin-navbar">
    <a class="nav-brand" href="/admin<?= $tenantQuery ?>"><?= e($business['nombre']) ?></a>
    <div class="nav-links">
        <a class="nav-link" href="/admin<?= $tenantQuery ?>">Pedidos</a>
        <a class="nav-link" href="/admin/menu<?= $tenantQuery ?>">Menú</a>
        <a class="nav-link" href="/admin/branches<?= $tenantQuery ?>">Sucursales</a>
        <a class="nav-link" href="/admin/hours<?= $tenantQuery ?>">Horarios</a>
        <a class="nav-link" href="/admin/branding<?= $tenantQuery ?>">Personalización</a>
    </div>
    <form method="post" action="/admin/logout<?= $tenantQuery ?>">
        <button class="chip">Salir</button>
    </form>
</nav>
