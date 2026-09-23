<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Zonas de entrega · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <link rel="stylesheet" href="/assets/orders.css?v=<?= filemtime(BASE_PATH . '/public/assets/orders.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .dz-container { display: grid; gap: 20px; }
        .dz-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }
        .dz-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .dz-header h1 { font-size: 1.35rem; margin: 0; }
        .dz-header form { margin: 0; }
        .dz-header label { font-weight: 600; font-size: 0.9rem; }
        .dz-header select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 0.95rem;
            min-width: 220px;
        }

        #zonesMap {
            height: 360px;
            width: 100%;
            border-radius: 14px;
            border: 1px solid #e5e5e5;
            overflow: hidden;
        }

        .dz-section-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 4px 0 12px;
            color: #222;
        }

        .dz-zones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .dz-zone-card {
            background: #fafafa;
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .dz-zone-card:hover { border-color: #ddd; box-shadow: 0 4px 14px rgba(0,0,0,0.05); }
        .dz-zone-card.inactive { opacity: 0.7; background: #f5f5f5; }

        .dz-zone-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }
        .dz-zone-name { font-weight: 700; font-size: 1rem; color: #222; margin: 0; }
        .dz-badge {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 4px 8px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .dz-badge.active { background: #dcfce7; color: #166534; }
        .dz-badge.inactive { background: #f3f4f6; color: #6b7280; }
        .dz-badge.radio { background: #ffedd5; color: #9a3412; }
        .dz-badge.manual { background: #e0e7ff; color: #3730a3; }

        .dz-zone-meta { font-size: 0.85rem; color: #555; line-height: 1.5; }
        .dz-zone-meta b { color: #222; }

        .dz-actions { display: flex; gap: 8px; margin-top: auto; }
        .dz-actions .btn { flex: 1; text-align: center; padding: 8px 10px; border-radius: 8px; font-size: 0.85rem; cursor: pointer; border: none; font-weight: 600; }
        .dz-actions .btn-edit { background: #fff; color: var(--brand, #cc4b25); border: 1px solid #ddd; }
        .dz-actions .btn-edit:hover { border-color: var(--brand, #cc4b25); background: #fff8f5; }
        .dz-actions .btn-toggle { background: #f3f4f6; color: #374151; }
        .dz-actions .btn-toggle:hover { background: #e5e7eb; }

        .dz-edit-form {
            display: none;
            margin-top: 12px;
            padding-top: 14px;
            border-top: 1px dashed #ddd;
        }
        .dz-edit-form.open { display: block; }
        .dz-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
        }
        .dz-form-grid label { font-size: 0.8rem; font-weight: 600; color: #444; }
        .dz-form-grid input,
        .dz-form-grid select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .dz-form-grid input:focus,
        .dz-form-grid select:focus { outline: none; border-color: var(--brand, #cc4b25); box-shadow: 0 0 0 3px rgba(204,75,37,0.1); }

        .dz-new-card {
            background: #fff;
            border: 2px dashed #e5e5e5;
            border-radius: 16px;
            padding: 20px;
        }
        .dz-new-card:hover { border-color: #d4d4d4; }
        .dz-new-card h3 { margin: 0 0 14px; font-size: 1.05rem; }

        .dz-empty {
            text-align: center;
            color: #888;
            padding: 28px;
            background: #fafafa;
            border-radius: 12px;
            font-size: 0.95rem;
        }

        .dz-alert {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            margin-bottom: 14px;
        }
        .dz-alert.success { background: #dcfce7; color: #166534; }
        .dz-alert.error { background: #fee2e2; color: #991b1b; }
        .dz-alert.warning { background: #fef3c7; color: #92400e; }

        @media (max-width: 640px) {
            .dz-header { flex-direction: column; align-items: flex-start; }
            .dz-header select { width: 100%; }
            .dz-zones-grid { grid-template-columns: 1fr; }
            #zonesMap { height: 280px; }
        }
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <section class="dz-card">
        <div class="dz-header">
            <h1>Zonas de entrega</h1>
            <form method="get" action="/admin/delivery-zones">
                <label>Sucursal
                    <select name="branch_id" onchange="this.form.submit()">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= (int) $branch['id'] ?>" <?= $branchId === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <input type="hidden" name="tenant" value="<?= e($_GET['tenant'] ?? '') ?>">
            </form>
        </div>

        <?php if ($error): ?><div class="dz-alert error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="dz-alert success"><?= e($success) ?></div><?php endif; ?>

        <?php if ($selectedBranch && ($selectedBranch['latitud'] === null || $selectedBranch['longitud'] === null)): ?>
            <div class="dz-alert warning">Esta sucursal no tiene ubicación configurada. Configúrala en <a href="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">Sucursales</a> antes de crear zonas por radio.</div>
        <?php endif; ?>

        <?php if ($branchId && $selectedBranch && $selectedBranch['latitud'] !== null && $selectedBranch['longitud'] !== null): ?>
            <div id="zonesMap"></div>
        <?php endif; ?>
    </section>

    <?php if ($branchId): ?>
        <section class="dz-card dz-container">
            <h2 class="dz-section-title">Zonas configuradas</h2>

            <?php if ($zones): ?>
                <div class="dz-zones-grid">
                    <?php foreach ($zones as $zone): ?>
                        <article class="dz-zone-card <?= (int) $zone['activa'] === 1 ? '' : 'inactive' ?>" data-zone-id="<?= (int) $zone['id'] ?>">
                            <div class="dz-zone-top">
                                <h3 class="dz-zone-name"><?= e($zone['nombre']) ?></h3>
                                <span class="dz-badge <?= (int) $zone['activa'] === 1 ? 'active' : 'inactive' ?>"><?= (int) $zone['activa'] === 1 ? 'Activa' : 'Inactiva' ?></span>
                            </div>
                            <div class="dz-zone-meta">
                                <span class="dz-badge <?= $zone['tipo_zona'] === 'radio' ? 'radio' : 'manual' ?>"><?= $zone['tipo_zona'] === 'radio' ? 'Por distancia' : 'Manual' ?></span>
                                <?php if ($zone['tipo_zona'] === 'radio'): ?>
                                                                    <div style="margin-top:6px;">Rango: <b><?= $zone['radio_desde_km'] !== null ? (float) $zone['radio_desde_km'] : '0' ?> km — <?= $zone['radio_hasta_km'] !== null ? (float) $zone['radio_hasta_km'] : 'sin límite' ?></b></div>
                                <?php endif; ?>
                                <div>Costo de envío: <b>$<?= number_format((float) $zone['costo_envio'], 2) ?></b></div>
                                <div>Pedido mínimo: <b><?= $zone['pedido_minimo'] !== null ? '$' . number_format((float) $zone['pedido_minimo'], 2) : 'Sin mínimo' ?></b></div>
                            </div>
                            <div class="dz-actions">
                                <button type="button" class="btn btn-edit" onclick="toggleEditForm(<?= (int) $zone['id'] ?>)">Editar</button>
                                <form method="post" action="/admin/delivery-zones/toggle<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" style="flex:1;display:flex;">
                                    <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                                    <input type="hidden" name="sucursal_id" value="<?= (int) $branchId ?>">
                                    <button class="btn btn-toggle" type="submit" style="width:100%;"><?= (int) $zone['activa'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                                </form>
                            </div>

                            <div class="dz-edit-form" id="edit-form-<?= (int) $zone['id'] ?>">
                                <form method="post" action="/admin/delivery-zones/update<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                    <input type="hidden" name="id" value="<?= (int) $zone['id'] ?>">
                                    <input type="hidden" name="sucursal_id" value="<?= (int) $branchId ?>">
                                    <div class="dz-form-grid">
                                        <label>Nombre<input type="text" name="nombre" value="<?= e($zone['nombre']) ?>" required></label>
                                        <label>Tipo
                                            <select name="tipo_zona" class="tipo-zona-select" data-zone-id="<?= (int) $zone['id'] ?>" onchange="toggleRadioFields(this)">
                                                <option value="manual" <?= $zone['tipo_zona'] === 'manual' ? 'selected' : '' ?>>Manual</option>
                                                <option value="radio" <?= $zone['tipo_zona'] === 'radio' ? 'selected' : '' ?>>Por distancia</option>
                                            </select>
                                        </label>
                                        <label class="radio-field radio-<?= (int) $zone['id'] ?>" style="<?= $zone['tipo_zona'] === 'radio' ? '' : 'display:none;' ?>">Desde (km)<input type="number" name="radio_desde_km" step="0.01" min="0" value="<?= $zone['radio_desde_km'] !== null ? (float) $zone['radio_desde_km'] : '' ?>"></label>
                                        <label class="radio-field radio-<?= (int) $zone['id'] ?>" style="<?= $zone['tipo_zona'] === 'radio' ? '' : 'display:none;' ?>">Hasta (km)<input type="number" name="radio_hasta_km" step="0.01" min="0" value="<?= $zone['radio_hasta_km'] !== null ? (float) $zone['radio_hasta_km'] : '' ?>" placeholder="Sin límite"></label>
                                        <label>Costo de envío<input type="number" name="costo_envio" step="0.01" min="0" value="<?= (float) $zone['costo_envio'] ?>" required></label>
                                        <label>Pedido mínimo<input type="number" name="pedido_minimo" step="0.01" min="0" value="<?= $zone['pedido_minimo'] !== null ? (float) $zone['pedido_minimo'] : '' ?>" placeholder="Sin mínimo"></label>
                                    </div>
                                    <div class="dz-actions" style="margin-top:12px;">
                                        <button class="btn btn-edit" type="submit">Guardar cambios</button>
                                        <button type="button" class="btn btn-toggle" onclick="toggleEditForm(<?= (int) $zone['id'] ?>)">Cancelar</button>
                                    </div>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="dz-empty">No hay zonas para esta sucursal. Agregá la primera abajo.</div>
            <?php endif; ?>
        </section>

        <section class="dz-new-card">
            <h3>Agregar zona</h3>
            <form method="post" action="/admin/delivery-zones<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                <input type="hidden" name="sucursal_id" value="<?= (int) $branchId ?>">
                <div class="dz-form-grid">
                    <label>Nombre<input type="text" name="nombre" placeholder="Ej. Zona Norte" required></label>
                    <label>Tipo
                        <select name="tipo_zona" id="newTipoZona" onchange="toggleRadioFields(this)">
                            <option value="manual">Manual</option>
                            <option value="radio">Por distancia</option>
                        </select>
                    </label>
                    <label class="new-radio" style="display:none;">Desde (km)<input type="number" name="radio_desde_km" step="0.01" min="0" placeholder="0"></label>
                    <label class="new-radio" style="display:none;">Hasta (km)<input type="number" name="radio_hasta_km" step="0.01" min="0" placeholder="Sin límite"></label>
                    <label>Costo de envío<input type="number" name="costo_envio" step="0.01" min="0" placeholder="0.00" required></label>
                    <label>Pedido mínimo<input type="number" name="pedido_minimo" step="0.01" min="0" placeholder="Opcional"></label>
                </div>
                <div style="margin-top:16px;">
                    <button class="primary" type="submit">Crear zona</button>
                </div>
            </form>
        </section>
    <?php else: ?>
        <section class="dz-card">
            <div class="dz-empty">Primero crea una sucursal para poder agregar zonas de entrega.</div>
        </section>
    <?php endif; ?>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const branchData = <?= json_encode($selectedBranch ?: null, JSON_UNESCAPED_UNICODE) ?>;
const zonesData = <?= json_encode($zones ?: [], JSON_UNESCAPED_UNICODE) ?>;

function initZonesMap() {
    if (!branchData || !branchData.latitud || !branchData.longitud) return;

    const center = [parseFloat(branchData.latitud), parseFloat(branchData.longitud)];
    const map = L.map('zonesMap').setView(center, 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker(center).addTo(map).bindPopup(branchData.nombre);

    const colors = ['#cc4b25', '#2563eb', '#16a34a', '#9333ea', '#ea580c'];
    zonesData.filter(z => z.tipo_zona === 'radio' && Number(z.activa) === 1 && z.radio_hasta_km).forEach((zone, index) => {
        const color = colors[index % colors.length];
        L.circle(center, {
            radius: parseFloat(zone.radio_hasta_km) * 1000,
            color: color,
            fillColor: color,
            fillOpacity: 0.08,
            weight: 2,
        }).addTo(map).bindPopup(`<b>${escapeHtml(zone.nombre)}</b><br>$${Number(zone.costo_envio).toFixed(0)} envío`);
    });
}

function toggleRadioFields(select) {
    const show = select.value === 'radio';
    if (select.id === 'newTipoZona') {
        document.querySelectorAll('.new-radio').forEach(el => el.style.display = show ? 'grid' : 'none');
    } else {
        document.querySelectorAll('.radio-' + select.dataset.zoneId).forEach(el => el.style.display = show ? 'grid' : 'none');
    }
}

function toggleEditForm(id) {
    const form = document.getElementById('edit-form-' + id);
    if (!form) return;
    form.classList.toggle('open');
    document.querySelectorAll('.dz-edit-form').forEach(other => {
        if (other !== form) other.classList.remove('open');
    });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

initZonesMap();
</script>
</body>
</html>
