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
        .zn-layout{display:grid;gap:18px}
        #zonesMap{height:340px;width:100%;border-radius:20px;border:1px solid color-mix(in srgb,var(--brand) 14%,#e5e5e5);overflow:hidden;box-shadow:0 14px 34px color-mix(in srgb,var(--brand) 7%,transparent);background:#eef2f6}
        #zonesMap .leaflet-container{position:relative;z-index:0}
        .zn-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
        .zn-toolbar .zn-branch{min-width:220px;flex:1;position:relative}
        .zn-toolbar select{width:100%;padding:12px 14px;border:1px solid var(--line,#eadfd3);border-radius:14px;background:#fff;font:inherit;font-weight:750;font-size:14px;color:#57473d;cursor:pointer}
        .zn-toolbar select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 15%,transparent)}
        .zn-stats{display:flex;gap:10px;flex-wrap:wrap}
        .zn-stat{background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:16px;padding:12px 16px;box-shadow:0 10px 26px color-mix(in srgb,var(--brand) 6%,transparent)}
        .zn-stat small{display:block;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#716b67}
        .zn-stat b{font-size:21px}
        .zn-legend{display:flex;gap:14px;flex-wrap:wrap;font-size:12px;color:#57473d;font-weight:700;padding:2px}
        .zn-legend span{display:inline-flex;gap:6px;align-items:center}
        .zn-legend i{width:12px;height:12px;border-radius:4px;background:#e3e9f2;display:inline-block}
        .zn-callout{display:flex;gap:10px;align-items:flex-start;padding:14px 16px;background:#fffbeb;border:1px solid #f7e8b8;border-radius:16px;color:#92400e;font-size:13.5px;font-weight:650;line-height:1.5}
        .zn-callout a{color:#92400e;font-weight:900;text-decoration:underline}
        .zn-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:16px}
        .zn-card{position:relative;display:flex;flex-direction:column;gap:10px;background:#fff;border:1px solid color-mix(in srgb,var(--brand) 14%,#eadfd3);border-radius:20px;padding:16px;box-shadow:0 12px 28px color-mix(in srgb,var(--brand) 7%,transparent);transition:.16s ease;overflow:hidden}
        .zn-card:hover{transform:translateY(-2px);box-shadow:0 18px 42px color-mix(in srgb,var(--brand) 13%,transparent)}
        .zn-card.is-off{opacity:.62}
        .zn-card.is-off .zn-stripe{background:linear-gradient(180deg,#cbd5e1,#94a3b8)}
        .zn-stripe{position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--stripe,#cc4b25)}
        .zn-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
        .zn-card-top h3{margin:0;font-size:16px;line-height:1.2;padding-left:8px}
        .zn-badge{font-size:10.5px;font-weight:900;letter-spacing:.03em;text-transform:uppercase;border-radius:999px;padding:5px 10px;white-space:nowrap;flex:0 0 auto}
        .zn-badge.radio{background:#ffedd5;color:#9a3412}
        .zn-badge.manual{background:#e0e7ff;color:#3730a3}
        .zn-meta{display:grid;gap:7px;font-size:13px;color:#57473d;padding-left:8px}
        .zn-meta b{color:#222}
        .zn-cost{display:flex;align-items:baseline;gap:7px;padding:9px 11px;background:color-mix(in srgb,var(--brand) 6%,#fff);border-radius:12px}
        .zn-cost .val{font-size:20px;font-weight:950;color:var(--ink,#171514)}
        .zn-cost small{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#716b67}
        .zn-range{display:grid;gap:3px}
        .zn-rangebar{height:7px;border-radius:999px;background:#eef2f6;overflow:hidden}
        .zn-rangebar i{display:block;height:100%;border-radius:999px;background:var(--stripe,#cc4b25)}
        .zn-card-foot{display:flex;align-items:center;gap:8px;margin-top:auto;padding-top:6px;border-top:1px dashed color-mix(in srgb,var(--brand) 22%,#eee)}
        .zn-card-foot button.zn-edit{flex:1;border:1px solid var(--line,#eadfd3);background:#fff;color:var(--brand);border-radius:12px;padding:9px 10px;font-weight:850;font-size:13px;cursor:pointer}
        .zn-card-foot button.zn-edit:hover{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 7%,white)}
        .switch-wrap{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:#57473d;cursor:pointer}
        .switch-wrap input{position:absolute;opacity:0;width:0;height:0}
        .sw{position:relative;display:block;width:40px;height:23px;border-radius:999px;background:#d8d2cc;transition:.18s;flex:0 0 auto}
        .sw:after{content:"";position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.25);transition:.18s}
        .switch-wrap input:checked+.sw{background:var(--brand)}
        .switch-wrap input:checked+.sw:after{left:20px}
        .zn-empty{padding:38px;text-align:center;color:#716b67;background:#fff;border:1px dashed color-mix(in srgb,var(--brand) 28%,#eadfd3);border-radius:20px}
        .zn-drawer-backdrop{position:fixed;inset:0;background:rgba(20,16,14,.45);display:none;z-index:6}
        .zn-drawer-backdrop.open{display:block}
        .zn-drawer{position:fixed;top:0;right:0;height:100%;width:min(560px,100%);background:#fff;z-index:7;transform:translateX(102%);transition:transform .22s ease;overflow-y:auto;padding:22px;box-shadow:-18px 0 60px rgba(0,0,0,.18)}
        .zn-drawer.open{transform:none}
        .zn-drawer-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px}
        .zn-drawer-head h2{margin:0;font-size:20px}
        .zn-form label{display:grid;gap:6px;font-weight:800;font-size:12.5px;color:#57473d;margin-bottom:12px}
        .zn-form input[type=text],.zn-form input[type=number],.zn-form select,.zn-form textarea{width:100%;padding:11px 12px;border:1px solid #e2d8cd;border-radius:12px;font:inherit;font-size:14px;background:#fff}
        .zn-form input:focus,.zn-form select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 14%,transparent)}
        .zn-form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .zn-type{display:flex;gap:8px}
        .zn-type button{flex:1;border:2px solid #eee2d6;background:#fff;border-radius:14px;padding:12px 10px;font-weight:850;font-size:13px;color:#57473d;cursor:pointer;display:grid;gap:3px;place-items:center;text-align:center;transition:.14s}
        .zn-type button small{font-weight:600;font-size:11px;color:#94867c}
        .zn-type button.active{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 8%,#fff);color:var(--brand)}
        .zn-type button.active small{color:color-mix(in srgb,var(--brand) 75%,#000)}
        .zn-radio-fields{display:none;grid-gap:12px;margin:6px 0 4px;padding:12px;background:color-mix(in srgb,#ea580c 8%,#fff);border:1px solid #f6d7bd;border-radius:16px}
        .zn-radio-fields.open{display:grid}
        .zn-radio-fields label{margin:0}
        .zn-hint{font-size:12px;color:#94867c;font-weight:650;line-height:1.4}
        .zn-live-preview{height:230px;border-radius:16px;overflow:hidden;background:#eef2f6;border:1px solid #e3e9f2;margin:4px 0 10px}
        .zn-toast-zone{position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:12}
        .zn-toast{background:#171514;color:#fff;padding:12px 16px;border-radius:14px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px rgba(0,0,0,.3);animation:znIn .18s ease;max-width:340px}
        .zn-toast.error{background:#b42318}
        @keyframes znIn{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
        @media (max-width:640px){.zn-form-row{grid-template-columns:1fr}#zonesMap{height:280px}}
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <header class="crud-header">
        <div><h1>Zonas de entrega</h1><p>Define hasta dónde llega <?= e($business['nombre']) ?> y cuánto cuesta el envío en cada zona.</p></div>
        <div class="crud-actions">
            <button class="primary" <?= $branchId ? '' : 'disabled title="Primero crea una sucursal"' ?> onclick="openZone(0)">+ Nueva zona</button>
        </div>
    </header>

    <?php if (!$branches): ?>
        <div class="zn-callout">Necesitas al menos una sucursal para crear zonas de entrega. <a href="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">Crear sucursal →</a></div>
    <?php endif; ?>

    <?php if ($branches): ?>
        <?php $activeCosts = array_map(fn (array $z): float => (float) $z['costo_envio'], array_filter($zones, fn (array $z): bool => (int) $z['activa'] === 1)); ?>
        <section class="zn-card zn-layout" style="border-radius:24px;padding:20px">
            <div class="zn-toolbar">
                <form method="get" action="/admin/delivery-zones" class="zn-branch">
                    <label style="font-size:11px;font-weight:900;color:#716b67;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Sucursal</label>
                    <select name="branch_id" onchange="this.form.submit()">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= (int) $branch['id'] ?>" <?= $branchId === (int) $branch['id'] ? 'selected' : '' ?>><?= e($branch['nombre']) ?><?= ($branch['latitud'] === null || $branch['longitud'] === null) ? ' · sin ubicación' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="tenant" value="<?= e($_GET['tenant'] ?? '') ?>">
                </form>
                <div class="zn-stats">
                    <div class="zn-stat"><small>Zonas</small><b><?= count($zones) ?></b></div>
                    <div class="zn-stat"><small>Activas</small><b><?= count(array_filter($zones, fn (array $z): bool => (int) $z['activa'] === 1)) ?></b></div>
                    <div class="zn-stat"><small>Envío mínimo</small><b><?= $activeCosts ? money(min($activeCosts)) : '—' ?></b></div>
                    <div class="zn-stat"><small>Envío máximo</small><b><?= $activeCosts ? money(max($activeCosts)) : '—' ?></b></div>
                </div>
            </div>

            <?php if ($selectedBranch && ($selectedBranch['latitud'] === null || $selectedBranch['longitud'] === null)): ?>
                <div class="zn-callout">
                    <span>📍</span>
                    <span>Esta sucursal no tiene ubicación, así que los clientes no pueden calcular distancia. <a href="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) . '&editar=' . (int) $branchId : '?editar=' . (int) $branchId ?>">Configura el mapa en Sucursales</a> para habilitar el delivery.</span>
                </div>
            <?php endif; ?>

            <?php if ($branchId && $selectedBranch && $selectedBranch['latitud'] !== null && $selectedBranch['longitud'] !== null): ?>
                <div id="zonesMap"></div>
                <div class="zn-legend">
                    <span><i style="background:var(--brand)"></i> Sucursal</span>
                    <span><i style="background:#ffedd5"></i> Anillo activo</span>
                    <span>Arrastra el mapa para ver la cobertura</span>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($branchId && $selectedBranch && $selectedBranch['latitud'] !== null): ?>
        <?php if ($zones): ?>
            <div class="zn-grid">
                <?php foreach ($zones as $zone): ?>
                    <?php $isRadio = $zone['tipo_zona'] === 'radio'; ?>
                    <?php $hasta = $zone['radio_hasta_km'] !== null ? (float) $zone['radio_hasta_km'] : ($isRadio ? max(6, (float) ($zone['radio_desde_km'] ?? 0) + 3) : 0); ?>
                    <?php $desde = $zone['radio_desde_km'] !== null ? (float) $zone['radio_desde_km'] : 0; ?>
                    <?php $pct = $hasta > 0 ? min(100, max(8, (($hasta - $desde) / $hasta) * 100)) : 100; ?>
                    <article class="zn-card <?= (int) $zone['activa'] === 1 ? '' : 'is-off' ?>"
                         data-zone-id="<?= (int) $zone['id'] ?>"
                         data-type="<?= $isRadio ? 'radio' : 'manual' ?>"
                         data-desde="<?= $desde ?>" data-hasta="<?= $zone['radio_hasta_km'] !== null ? (float) $zone['radio_hasta_km'] : '' ?>"
                         data-name="<?= e(mb_strtolower($zone['nombre'])) ?>">
                        <span class="zn-stripe" style="--stripe:<?= $isRadio ? '#ea580c' : '#2563eb' ?>"></span>
                        <div class="zn-card-top">
                            <h3><?= e($zone['nombre']) ?></h3>
                            <span class="zn-badge <?= $isRadio ? 'radio' : 'manual' ?>"><?= $isRadio ? 'Distancia' : 'Manual' ?></span>
                        </div>
                        <?php if ($isRadio): ?>
                            <div class="zn-meta zn-range">
                                <span>Rango: <b><?= $desde > 0 ? number_format($desde, 1) : '0' ?> – <?= $zone['radio_hasta_km'] !== null ? number_format((float) $zone['radio_hasta_km'], 1) . ' km' : 'sin límite' ?></b></span>
                                <div class="zn-rangebar"><i style="width:<?= round($pct) ?>%"></i></div>
                            </div>
                        <?php else: ?>
                            <div class="zn-meta"><span>El cliente elige esta zona manualmente. <a href="/admin/menu<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" style="color:var(--brand)">Revisa el menú</a> si quieres vincular colonias.</span></div>
                        <?php endif; ?>
                        <div class="zn-cost">
                            <small>Envío</small><span class="val"><?= money($zone['costo_envio']) ?></span>
                            <small style="margin-left:auto">Mínimo</small><span class="val" style="font-size:16px"><?= $zone['pedido_minimo'] !== null ? money($zone['pedido_minimo']) : 'Sin mínimo' ?></span>
                        </div>
                        <div class="zn-card-foot">
                            <span class="switch-wrap" title="Activa / inactiva">
                                <input type="checkbox" class="zn-toggle" data-id="<?= (int) $zone['id'] ?>" <?= (int) $zone['activa'] === 1 ? 'checked' : '' ?>>
                                <span class="sw"></span>
                            </span>
                            <button type="button" class="zn-edit" onclick="openZone(<?= (int) $zone['id'] ?>)">Editar</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="zn-empty">
                Aún no hay zonas para <b><?= e($selectedBranch['nombre']) ?></b>.<br>
                <span style="font-size:13px">Empieza con una zona "Por distancia" (ej. 0–5 km) y otra más lejana, o crea zonas manuales por colonia.</span>
            </div>
        <?php endif; ?>
    <?php elseif ($branchId): ?>
        <div class="zn-callout"><span>🧭</span><span>Cuando esta sucursal tenga ubicación en el mapa podrás crear zonas por radio de distancia. Puedes crear zonas manuales desde el botón de arriba.</span></div>
        <?php if ($zones): ?>
            <div class="zn-grid" style="margin-top:16px">
                <?php foreach ($zones as $zone): ?>
                    <article class="zn-card <?= (int) $zone['activa'] === 1 ? '' : 'is-off' ?>" data-zone-id="<?= (int) $zone['id'] ?>" data-type="<?= $zone['tipo_zona'] === 'radio' ? 'radio' : 'manual' ?>" data-name="<?= e(mb_strtolower($zone['nombre'])) ?>">
                        <span class="zn-stripe" style="--stripe:<?= $zone['tipo_zona'] === 'radio' ? '#ea580c' : '#2563eb' ?>"></span>
                        <div class="zn-card-top">
                            <h3><?= e($zone['nombre']) ?></h3>
                            <span class="zn-badge <?= $zone['tipo_zona'] === 'radio' ? 'radio' : 'manual' ?>"><?= $zone['tipo_zona'] === 'radio' ? 'Distancia' : 'Manual' ?></span>
                        </div>
                        <div class="zn-cost"><small>Envío</small><span class="val"><?= money($zone['costo_envio']) ?></span></div>
                        <div class="zn-card-foot">
                            <span class="switch-wrap"><input type="checkbox" class="zn-toggle" data-id="<?= (int) $zone['id'] ?>" <?= (int) $zone['activa'] === 1 ? 'checked' : '' ?>><span class="sw"></span></span>
                            <button type="button" class="zn-edit" onclick="openZone(<?= (int) $zone['id'] ?>)">Editar</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

<div class="zn-drawer-backdrop" id="znBackdrop" onclick="closeZone()"></div>
<aside class="zn-drawer" id="znDrawer" aria-label="Editor de zona">
    <div class="zn-drawer-head">
        <h2 id="znTitle">Nueva zona</h2>
        <button class="chip" onclick="closeZone()">Cerrar ✕</button>
    </div>
    <form method="post" id="znForm" class="zn-form" action="/admin/delivery-zones">
        <input type="hidden" name="sucursal_id" value="<?= (int) $branchId ?>">
        <input type="hidden" name="id" id="znId" value="">
        <label>Nombre de la zona
            <input type="text" name="nombre" id="znNombre" required placeholder="Ej. Centro · Zona Norte · Playas">
            <small class="zn-hint">Aparece en el checkout; usa nombres que el cliente reconozca.</small>
        </label>
        <div style="margin-bottom:14px">
            <b style="font-size:12.5px;color:#57473d;display:block;margin-bottom:8px">Cómo la elige el cliente</b>
            <div class="zn-type" role="radiogroup">
                <button type="button" class="active" data-tipo="manual" id="znBtnManual">🗺️ Manual<small>El cliente selecciona su colonia o sector</small></button>
                <button type="button" data-tipo="radio" id="znBtnRadio">🎯 Por distancia<small>Se calcula con el mapa automáticamente</small></button>
            </div>
        </div>
        <div class="zn-radio-fields" id="znRadioFields">
            <div class="zn-form-row">
                <label>Desde (km)<input type="number" name="radio_desde_km" id="znDesde" step="0.1" min="0" placeholder="0"></label>
                <label>Hasta (km)<input type="number" name="radio_hasta_km" id="znHasta" step="0.1" min="0" placeholder="Sin límite"><small class="zn-hint">Deja vacío para "hasta el borde".</small></label>
            </div>
            <div class="zn-live-preview" id="znPreview"></div>
            <small class="zn-hint" id="znRadioHint">Configura la ubicación de la sucursal para previsualizar el anillo.</small>
        </div>
        <div class="zn-form-row">
            <label>Costo de envío<input type="number" name="costo_envio" id="znCosto" step="0.01" min="0" value="0" required></label>
            <label>Pedido mínimo<input type="number" name="pedido_minimo" id="znMinimo" step="0.01" min="0" placeholder="Sin mínimo"></label>
        </div>
        <button class="primary" style="width:100%" id="znSubmit">Crear zona</button>
    </form>
</aside>

<div class="zn-toast-zone" id="znToasts"></div>

<script type="application/json" id="znBranchData"><?= json_encode($selectedBranch ? ['lat' => $selectedBranch['latitud'] !== null ? (float) $selectedBranch['latitud'] : null, 'lon' => $selectedBranch['longitud'] !== null ? (float) $selectedBranch['longitud'] : null, 'nombre' => $selectedBranch['nombre']] : null, JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="znZonesData"><?= json_encode(array_map(static fn (array $z): array => [
    'id' => (int) $z['id'],
    'nombre' => (string) $z['nombre'],
    'tipo' => (string) $z['tipo_zona'],
    'activa' => (int) $z['activa'],
    'desde' => $z['radio_desde_km'] !== null ? (float) $z['radio_desde_km'] : null,
    'hasta' => $z['radio_hasta_km'] !== null ? (float) $z['radio_hasta_km'] : null,
    'costo' => (float) $z['costo_envio'],
    'minimo' => $z['pedido_minimo'] !== null ? (float) $z['pedido_minimo'] : null,
], $zones), JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const DEFAULT_CENTER = [19.4326, -99.1332];
const branch = JSON.parse(document.getElementById('znBranchData').textContent);
let zones = JSON.parse(document.getElementById('znZonesData').textContent);
const hasBranchGeo = branch && branch.lat !== null && branch.lon !== null;
let mainMap = null;
let previewMap = null;
let previewCircle = null;
let previewInner = null;
let mainCircles = [];

const ZONE_COLORS = ['#2563eb', '#ea580c', '#7c3aed', '#0891b2', '#ca8a04', '#be123c', '#16a34a', '#4f46e5'];
function zoneColor(id) {
    return ZONE_COLORS[id % ZONE_COLORS.length];
}

function znToast(message, ok = true) {
    const zone = document.getElementById('znToasts');
    const el = document.createElement('div');
    el.className = 'zn-toast' + (ok ? '' : ' error');
    el.textContent = message;
    zone.appendChild(el);
    setTimeout(() => el.remove(), 3400);
}

function initMainMap() {
    if (!hasBranchGeo || !document.getElementById('zonesMap') || typeof L === 'undefined') return;
    mainMap = L.map('zonesMap').setView([branch.lat, branch.lon], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(mainMap);
    L.circleMarker([branch.lat, branch.lon], { radius: 9, color: '#fff', weight: 3, fillColor: getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#cc4b25', fillOpacity: 1 })
        .addTo(mainMap).bindPopup('<b>' + escapeHtml(branch.nombre) + '</b>');
    drawRadioRings();
}

function drawRadioRings() {
    if (!mainMap) return;
    mainCircles.forEach(layer => mainMap.removeLayer(layer));
    mainCircles = [];
    zones.filter(z => z.tipo === 'radio' && z.activa === 1 && z.hasta).forEach(z => {
        const color = zoneColor(z.id);
        const inner = z.desde > 0 ? L.circle([branch.lat, branch.lon], { radius: z.desde * 1000, color: color, weight: 1, opacity: 0, fillColor: '#fff', fillOpacity: 0.85 }).addTo(mainMap) : null;
        const outer = L.circle([branch.lat, branch.lon], { radius: z.hasta * 1000, color: color, fillColor: color, fillOpacity: 0.14, weight: 2 }).addTo(mainMap)
            .bindPopup('<b>' + escapeHtml(z.nombre) + '</b><br>' + (z.desde ? z.desde + '–' : '0–') + z.hasta + ' km · envío $' + Number(z.costo).toFixed(2));
        mainCircles.push(outer);
        if (inner) mainCircles.push(inner);
    });
}

function toggleZone(id, input) {
    const previous = input.checked;
    const body = new URLSearchParams({ id: String(id), ajax: '1', sucursal_id: document.querySelector('[name=sucursal_id]').value });
    const tenant = new URLSearchParams(location.search).get('tenant');
    if (tenant) body.set('tenant', tenant);
    fetch('/admin/delivery-zones/toggle' + (tenant ? '?tenant=' + encodeURIComponent(tenant) : ''), {
        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body,
    }).then(r => r.json()).then(data => {
        if (!data.ok) throw new Error(data.message || 'No se pudo actualizar.');
        input.checked = !previous;
        znToast(data.message || 'Listo.');
        const card = input.closest('.zn-card');
        if (card && data.zone) {
            const active = Number(data.zone.activa) === 1;
            card.classList.toggle('is-off', !active);
            const z = zones.find(item => item.id === Number(id));
            if (z) z.activa = active ? 1 : 0;
            drawRadioRings();
        }
    }).catch(error => {
        input.checked = previous;
        znToast(error.message || 'Ocurrió un error.', false);
    });
}

document.querySelectorAll('.zn-toggle').forEach(input => {
    if (input.closest('.zn-card')) input.addEventListener('change', () => toggleZone(input.dataset.id, input));
});

function setType(tipo) {
    document.getElementById('znBtnManual').classList.toggle('active', tipo === 'manual');
    document.getElementById('znBtnRadio').classList.toggle('active', tipo === 'radio');
    document.getElementById('znRadioFields').classList.toggle('open', tipo === 'radio');
    document.getElementById('znDesde').disabled = tipo !== 'radio';
    document.getElementById('znHasta').disabled = tipo !== 'radio';
    if (tipo === 'radio') updatePreview();
}

document.getElementById('znBtnManual').addEventListener('click', () => setType('manual'));
document.getElementById('znBtnRadio').addEventListener('click', () => setType('radio'));

function initPreview() {
    if (!hasBranchGeo || typeof L === 'undefined' || previewMap) return;
    previewMap = L.map('znPreview').setView([branch.lat, branch.lon], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(previewMap);
    L.circleMarker([branch.lat, branch.lon], { radius: 6, color: '#fff', weight: 2.5, fillColor: '#2563eb', fillOpacity: 1 }).addTo(previewMap);
    previewCircle = L.circle([branch.lat, branch.lon], { radius: 5000, color: '#ea580c', weight: 2, fillOpacity: 0.15, fillColor: '#ea580c' }).addTo(previewMap);
    previewInner = L.circle([branch.lat, branch.lon], { radius: 0, color: '#ea580c', opacity: 0, weight: 0, fillColor: '#fff', fillOpacity: 0.8 }).addTo(previewMap);
    document.getElementById('znRadioHint').textContent = 'Arrastra y haz zoom para ver la cobertura';
}

function updatePreview() {
    if (!previewMap) return;
    const desde = parseFloat(document.getElementById('znDesde').value || '0') || 0;
    const hastaRaw = document.getElementById('znHasta').value;
    const hasta = hastaRaw === '' ? null : parseFloat(hastaRaw);
    const maxR = hasta !== null ? hasta : Math.max(6, desde + 4);
    const minR = hasta !== null ? Math.max(maxR, 0.5) : maxR;
    previewCircle.setRadius(maxR * 1000);
    previewInner.setRadius(desde * 1000);
    previewInner.setOpacity(desde > 0 ? 0.8 : 0);
    previewMap.fitBounds([[branch.lat - maxR / 111, branch.lon - maxR / 90], [branch.lat + maxR / 111, branch.lon + maxR / 90]], { padding: [16, 16] });
    setTimeout(() => previewMap.invalidateSize(), 60);
}

document.getElementById('znDesde').addEventListener('input', updatePreview);
document.getElementById('znHasta').addEventListener('input', updatePreview);

function openZone(id) {
    const editing = Number(id) > 0;
    const zone = editing ? zones.find(z => z.id === Number(id)) : null;
    document.getElementById('znTitle').textContent = zone ? 'Editar ' + zone.nombre : 'Nueva zona';
    document.getElementById('znSubmit').textContent = zone ? 'Guardar cambios' : 'Crear zona';
    document.getElementById('znForm').action = '/admin/delivery-zones' + (zone ? '/update' : '') + location.search;
    document.getElementById('znId').value = zone ? zone.id : '';
    document.getElementById('znNombre').value = zone ? zone.nombre : '';
    document.getElementById('znCosto').value = zone ? Number(zone.costo).toFixed(2) : '0';
    document.getElementById('znMinimo').value = zone && zone.minimo !== null && zone.minimo !== undefined ? Number(zone.minimo).toFixed(2) : '';
    document.getElementById('znDesde').value = zone && zone.desde !== null ? zone.desde : '';
    document.getElementById('znHasta').value = zone && zone.hasta !== null ? zone.hasta : '';
    setType(zone && zone.tipo === 'radio' ? 'radio' : 'manual');
    document.getElementById('znDrawer').classList.add('open');
    document.getElementById('znBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        initPreview();
        if (previewMap) {
            previewMap.invalidateSize();
            if (zone && zone.tipo === 'radio') updatePreview();
        }
    }, 240);
    setTimeout(() => document.getElementById('znNombre').focus(), 320);
}

function closeZone() {
    document.getElementById('znDrawer').classList.remove('open');
    document.getElementById('znBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeZone();
});

document.getElementById('znForm').addEventListener('submit', () => {
    const tipo = document.querySelector('.zn-type button.active').dataset.tipo;
    if (tipo === 'manual') {
        document.getElementById('znDesde').value = '';
        document.getElementById('znHasta').value = '';
    }
});

initMainMap();

const params = new URLSearchParams(location.search);
if (params.get('modo') === 'nueva') {
    openZone(0);
} else if (params.get('editar') && Number(params.get('editar')) > 0) {
    openZone(Number(params.get('editar')));
}
if (params.get('modo') || params.get('editar')) {
    params.delete('modo');
    params.delete('editar');
    history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params.toString() : ''));
}
<?php if ($success): ?>znToast(<?= json_encode($success) ?>, true);<?php endif; ?>
<?php if ($error): ?>znToast(<?= json_encode($error) ?>, false);<?php endif; ?>
</script>
</body>
</html>
