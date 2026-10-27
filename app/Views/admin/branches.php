<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sucursales · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .qb-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:16px 0}
        .qb-search{flex:1;min-width:220px;position:relative}
        .qb-search input{width:100%;padding:12px 16px 12px 42px;border:1px solid var(--line,#eadfd3);border-radius:14px;font-size:14px;background:#fff}
        .qb-search input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 15%,transparent)}
        .qb-search svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);opacity:.5}
        .qb-filters{display:flex;gap:6px;flex-wrap:wrap}
        .qb-filters button{padding:10px 16px;border-radius:999px;border:1px solid var(--line,#eadfd3);background:#fff;font-weight:800;font-size:13px;cursor:pointer;color:#57473d}
        .qb-filters button.active{background:var(--brand);border-color:var(--brand);color:#fff}
        .qb-stats{display:flex;gap:10px;flex-wrap:wrap;margin:6px 0 14px}
        .qb-stat{background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:16px;padding:12px 16px;box-shadow:0 10px 26px color-mix(in srgb,var(--brand) 6%,transparent)}
        .qb-stat small{display:block;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#716b67}
        .qb-stat b{font-size:21px}
        .qb-stat.warn b{color:#b45309}
        .qb-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
        .qb-card{display:flex;flex-direction:column;background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:22px;overflow:hidden;box-shadow:0 12px 30px color-mix(in srgb,var(--brand) 7%,transparent);transition:.16s ease}
        .qb-card:hover{transform:translateY(-2px);box-shadow:0 20px 44px color-mix(in srgb,var(--brand) 13%,transparent)}
        .qb-card.is-off{opacity:.62}
        .qb-map{height:150px;background:#eef2f6;position:relative}
        .qb-map .leaflet-container{position:relative;z-index:0}
        .qb-card-body{padding:14px 16px;display:grid;gap:6px;flex:1}
        .qb-card-body h3{margin:0;font-size:16.5px}
        .qb-line{display:flex;gap:7px;align-items:flex-start;font-size:12.5px;color:#57473d;line-height:1.45}
        .qb-line .ico{flex:0 0 auto;width:16px;text-align:center}
        .qb-card-foot{display:flex;align-items:center;gap:8px;padding:12px 14px;border-top:1px solid #f0e7de;background:color-mix(in srgb,var(--brand) 4%,#fff)}
        .qb-state-pill{font-size:11px;font-weight:900;border-radius:999px;padding:5px 11px;background:color-mix(in srgb,var(--brand) 12%,white);color:var(--dark,#2b201b)}
        .qb-state-pill.off{background:#f1f3f6;color:#64748b}
        .qb-state-pill.nowarn{background:#fee2e2;color:#991b1b}
        .qb-card-foot button.qb-edit{flex:1;border:1px solid var(--line,#eadfd3);background:#fff;color:var(--brand);border-radius:12px;padding:9px 10px;font-weight:850;font-size:13px;cursor:pointer}
        .qb-card-foot button.qb-edit:hover{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 7%,white)}
        .switch-wrap{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:#57473d;cursor:pointer}
        .switch-wrap input{position:absolute;opacity:0;width:0;height:0}
        .sw{position:relative;display:block;width:40px;height:23px;border-radius:999px;background:#d8d2cc;transition:.18s;flex:0 0 auto}
        .sw:after{content:"";position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.25);transition:.18s}
        .switch-wrap input:checked+.sw{background:var(--brand)}
        .switch-wrap input:checked+.sw:after{left:20px}
        .qb-empty{padding:46px;text-align:center;color:#716b67;background:#fff;border:1px dashed color-mix(in srgb,var(--brand) 30%,#eadfd3);border-radius:22px}
        .qb-drawer-backdrop{position:fixed;inset:0;background:rgba(20,16,14,.45);display:none;z-index:6}
        .qb-drawer-backdrop.open{display:block}
        .qb-drawer{position:fixed;top:0;right:0;height:100%;width:min(560px,100%);background:#fff;z-index:7;transform:translateX(102%);transition:transform .22s ease;overflow-y:auto;padding:22px;box-shadow:-18px 0 60px rgba(0,0,0,.18)}
        .qb-drawer.open{transform:none}
        .qb-drawer-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:6px}
        .qb-drawer-head h2{margin:0;font-size:20px}
        .qb-drawer input[type=text],.qb-drawer textarea{width:100%;padding:11px 12px;border:1px solid #e2d8cd;border-radius:12px;font:inherit;font-size:14px;background:#fff}
        .qb-drawer input:focus,.qb-drawer textarea:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 14%,transparent)}
        .qb-drawer label{display:grid;gap:6px;font-weight:800;font-size:12.5px;color:#57473d;margin-bottom:12px}
        .qb-drawer-map{height:220px;border-radius:16px;border:1px solid #e3e9f2;overflow:hidden;margin:6px 0 8px}
        .qb-drawer .leaflet-container{position:relative;z-index:0}
        .map-search{display:flex;gap:8px}
        .map-search input{flex:1;min-width:0}
        .map-search button{white-space:nowrap}
        .geocode-results{display:grid;gap:8px;margin-top:8px}
        .geocode-result{text-align:left;background:#fff;border:1px solid #ddd;border-radius:10px;padding:10px 12px;cursor:pointer}
        .geocode-result:hover{border-color:var(--brand,#cc4b25)}
        .geocode-result b{display:block;font-size:.9rem}
        .geocode-result small{color:#666;font-size:.75rem}
        .geocode-message{font-size:.85rem;color:#666;margin:8px 0 0}
        .geo-coords{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:12px;color:#716b67;font-weight:700}
        .geo-coords code{background:#f3e8df;border-radius:8px;padding:3px 8px;font-size:11.5px}
        .qb-warning{margin:10px 0 0;padding:12px 14px;background:#fffbeb;color:#92400e;border:1px solid #f7e8b8;border-radius:14px;font-size:13px;font-weight:700}
        .qb-toast-zone{position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:12}
        .qb-toast{background:#171514;color:#fff;padding:12px 16px;border-radius:14px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px rgba(0,0,0,.3);animation:qbIn .18s ease}
        .qb-toast.error{background:#b42318}
        @keyframes qbIn{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
        .qb-quick-actions{display:flex;gap:8px;margin:4px 0 14px;flex-wrap:wrap}
        .qb-quick-actions a{text-decoration:none}
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <header class="crud-header">
        <div><h1>Sucursales</h1><p>Administra los puntos de venta del negocio.</p></div>
        <div class="crud-actions">
            <a class="chip" style="text-decoration:none" href="/admin/hours<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">🕐 Ver horarios</a>
            <button class="primary" onclick="openEditor()">+ Nueva sucursal</button>
        </div>
    </header>

    <?php $missingGeo = array_filter($branches, fn (array $b): bool => $b['latitud'] === null || $b['longitud'] === null); ?>
    <?php if ($missingGeo): ?>
        <div class="qb-warning">
            <?= count($missingGeo) ?> sucursal<?= count($missingGeo) === 1 ? '' : 'es' ?> sin ubicación: no se podrá ofrecer delivery en <?= e($missingGeo[0]['nombre']) ?><?= count($missingGeo) > 1 ? ' ni en las demás marcadas' : '' ?> hasta configurar el mapa. Ábrelas desde la tarjeta y busca la dirección.
        </div>
    <?php endif; ?>

    <div class="qb-toolbar">
        <div class="qb-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
            <input id="qbSearch" type="search" placeholder="Buscar sucursal o dirección…" autocomplete="off">
        </div>
        <div class="qb-filters">
            <button class="active" data-filter="all">Todas</button>
            <button data-filter="on">Abiertas</button>
            <button data-filter="off">Cerradas</button>
            <button data-filter="nowarn">Sin ubicación</button>
        </div>
    </div>

    <div class="qb-stats">
        <div class="qb-stat"><small>Sucursales</small><b><?= count($branches) ?></b></div>
        <div class="qb-stat"><small>Abiertas</small><b><?= count(array_filter($branches, fn (array $b): bool => (int) $b['activa'] === 1)) ?></b></div>
        <div class="qb-stat warn"><small>Sin ubicación</small><b><?= count($missingGeo) ?></b></div>
    </div>

    <?php if ($branches): ?>
        <div class="qb-grid">
            <?php foreach ($branches as $branch): ?>
                <?php $hasGeo = $branch['latitud'] !== null && $branch['longitud'] !== null; ?>
                <article class="qb-card <?= (int) $branch['activa'] === 1 ? '' : 'is-off' ?>"
                         data-name="<?= e(mb_strtolower($branch['nombre'] . ' ' . $branch['direccion'])) ?>"
                         data-state="<?= (int) $branch['activa'] === 1 ? 'on' : 'off' ?>"
                         data-geo="<?= $hasGeo ? '1' : '0' ?>">
                    <div class="qb-map" id="card-map-<?= (int) $branch['id'] ?>" data-lat="<?= $hasGeo ? (float) $branch['latitud'] : '' ?>" data-lon="<?= $hasGeo ? (float) $branch['longitud'] : '' ?>"></div>
                    <div class="qb-card-body">
                        <h3><?= e($branch['nombre']) ?></h3>
                        <div class="qb-line"><span class="ico">📍</span><span><?= e($branch['direccion']) ?></span></div>
                        <?php if (!empty($branch['direccion_referencia'])): ?>
                            <div class="qb-line"><span class="ico">🧭</span><span><?= e($branch['direccion_referencia']) ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($branch['telefono'])): ?>
                            <div class="qb-line"><span class="ico">📞</span><span><?= e($branch['telefono']) ?></span></div>
                        <?php endif; ?>
                    </div>
                    <div class="qb-card-foot">
                        <?php if (!$hasGeo): ?>
                            <span class="qb-state-pill nowarn">Sin ubicación</span>
                        <?php else: ?>
                            <span class="qb-state-pill">Delivery listo</span>
                        <?php endif; ?>
                        <label class="switch-wrap" title="Abierta / cerrada">
                            <input type="checkbox" class="qb-toggle" data-id="<?= (int) $branch['id'] ?>" <?= (int) $branch['activa'] === 1 ? 'checked' : '' ?>>
                            <span class="sw"></span>
                        </label>
                        <button type="button" class="qb-edit" onclick="openEditor(<?= (int) $branch['id'] ?>)">Editar</button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="qb-empty">Aún no hay sucursales. Crea la primera para que los clientes puedan recoger o recibir pedidos.</div>
    <?php endif; ?>
</main>

<div class="qb-drawer-backdrop" id="qbBackdrop" onclick="closeEditor()"></div>
<aside class="qb-drawer" id="qbDrawer" aria-label="Editor de sucursal">
    <div class="qb-drawer-head">
        <h2 id="qbDrawerTitle">Nueva sucursal</h2>
        <button class="chip" onclick="closeEditor()">Cerrar ✕</button>
    </div>
    <form method="post" id="qbForm" action="/admin/branches">
        <input type="hidden" name="id" id="qbId" value="">
        <label>Nombre<input type="text" name="nombre" id="qbNombre" required placeholder="Sucursal Centro"></label>
        <label>Teléfono<input type="text" name="telefono" id="qbTelefono" placeholder="10 dígitos"></label>
        <label>Dirección<textarea name="direccion" id="qbDireccion" rows="2" required placeholder="Calle, número, colonia"></textarea></label>
        <label>Referencias<textarea name="direccion_referencia" id="qbRef" rows="2" placeholder="Entre calles, color de fachada…"></textarea></label>
        <div style="display:grid;gap:4px;margin-bottom:6px">
            <b style="font-size:12.5px;color:#57473d">Ubicación en mapa</b>
            <small style="color:#716b67;font-size:11.5px">Busca la dirección o arrastra el pin. Sin ubicación no habrá delivery en esta sucursal.</small>
        </div>
        <input type="hidden" name="latitud" id="qbLat" value="">
        <input type="hidden" name="longitud" id="qbLon" value="">
        <div class="qb-drawer-map" id="qbMap"></div>
        <div class="map-search">
            <input type="text" id="qbSearchInput" placeholder="Buscar dirección…" onkeydown="if(event.key==='Enter'){event.preventDefault();searchBranchAddress();}">
            <button type="button" class="chip" onclick="searchBranchAddress()">Buscar</button>
        </div>
        <div class="geocode-results" id="qbResults"></div>
        <p class="geocode-message" id="qbMessage" style="display:none"></p>
        <div class="geo-coords" id="qbCoords" style="display:none"><span>Coordenadas:</span> <code id="qbCoordText"></code></div>
        <button class="primary" style="width:100%;margin-top:14px" id="qbSubmit">Crear sucursal</button>
    </form>
</aside>

<div class="qb-toast-zone" id="qbToasts"></div>

<script type="application/json" id="qbData"><?= json_encode(array_map(static fn (array $b): array => [
    'id' => (int) $b['id'],
    'nombre' => (string) $b['nombre'],
    'telefono' => (string) ($b['telefono'] ?? ''),
    'direccion' => (string) $b['direccion'],
    'direccion_referencia' => (string) ($b['direccion_referencia'] ?? ''),
    'lat' => $b['latitud'] !== null ? (float) $b['latitud'] : null,
    'lon' => $b['longitud'] !== null ? (float) $b['longitud'] : null,
], $branches), JSON_UNESCAPED_UNICODE) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const DEFAULT_CENTER = [19.4326, -99.1332];
const branches = JSON.parse(document.getElementById('qbData').textContent);
let qbFilter = 'all';
let qbQuery = '';
let drawerMap = null;
let drawerMarker = null;

function qbToast(message, ok = true) {
    const zone = document.getElementById('qbToasts');
    const el = document.createElement('div');
    el.className = 'qb-toast' + (ok ? '' : ' error');
    el.textContent = message;
    zone.appendChild(el);
    setTimeout(() => el.remove(), 3400);
}

function applyFilters() {
    document.querySelectorAll('.qb-card').forEach(card => {
        const stateOk = qbFilter === 'all' || (qbFilter === 'on' ? card.dataset.state === 'on' : qbFilter === 'off' ? card.dataset.state === 'off' : card.dataset.geo === '0');
        const textOk = !qbQuery || card.dataset.name.includes(qbQuery);
        card.style.display = stateOk && textOk ? '' : 'none';
    });
}

document.getElementById('qbSearch').addEventListener('input', event => {
    qbQuery = event.target.value.trim().toLowerCase();
    applyFilters();
});

document.querySelectorAll('.qb-filters button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.qb-filters button').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    qbFilter = button.dataset.filter;
    applyFilters();
}));

async function toggleBranch(id, input) {
    const previous = input.checked;
    try {
        const body = new URLSearchParams({ id: String(id), ajax: '1' });
        const tenant = new URLSearchParams(location.search).get('tenant');
        if (tenant) body.set('tenant', tenant);
        const response = await fetch('/admin/branches/toggle' + (tenant ? '?tenant=' + encodeURIComponent(tenant) : ''), {
            method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body,
        });
        const data = await response.json();
        if (!data.ok) throw new Error(data.message || 'No se pudo actualizar.');
        input.checked = !previous;
        qbToast(data.message || 'Listo.');
        const card = input.closest('.qb-card');
        if (card && data.branch) {
            const active = Number(data.branch.activa) === 1;
            card.dataset.state = active ? 'on' : 'off';
            card.classList.toggle('is-off', !active);
        }
    } catch (error) {
        input.checked = previous;
        qbToast(error.message || 'Ocurrió un error.', false);
    }
    applyFilters();
}

document.querySelectorAll('.qb-toggle').forEach(input => input.addEventListener('change', () => toggleBranch(input.dataset.id, input)));

function initCardMaps() {
    document.querySelectorAll('.qb-map[id^="card-map-"]').forEach(container => {
        const lat = parseFloat(container.dataset.lat);
        const lon = parseFloat(container.dataset.lon);
        const hasLocation = !isNaN(lat) && !isNaN(lon);
        const map = L.map(container, { zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false, touchZoom: false, attributionControl: false }).setView(hasLocation ? [lat, lon] : DEFAULT_CENTER, hasLocation ? 14 : 4);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        if (hasLocation) {
            L.circleMarker([lat, lon], { radius: 8, color: '#fff', weight: 2.5, fillColor: getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#cc4b25', fillOpacity: 1 }).addTo(map);
        } else {
            const label = document.createElement('div');
            label.style.cssText = 'position:absolute;inset:0;display:grid;place-items:center;font-size:12px;font-weight:800;color:#94a3b8;background:repeating-linear-gradient(45deg,#f1f5f9,#f1f5f9 10px,#eef2f6 10px,#eef2f6 20px);z-index:1';
            label.textContent = 'Sin ubicación';
            container.appendChild(label);
        }
    });
}

function setDrawerCoords(lat, lon) {
    document.getElementById('qbLat').value = lat;
    document.getElementById('qbLon').value = lon;
    const box = document.getElementById('qbCoords');
    if (lat === '' || lon === '') {
        box.style.display = 'none';
        if (drawerMarker) drawerMarker.setLatLng(DEFAULT_CENTER);
        return;
    }
    box.style.display = 'flex';
    document.getElementById('qbCoordText').textContent = Number(lat).toFixed(6) + ', ' + Number(lon).toFixed(6);
    if (drawerMap && drawerMarker) {
        drawerMap.setView([lat, lon], 16);
        drawerMarker.setLatLng([lat, lon]);
    }
}

function openEditor(id, draft) {
    const isEdit = Number(id) > 0;
    const branch = isEdit ? branches.find(b => b.id === Number(id)) : null;
    document.getElementById('qbDrawerTitle').textContent = branch ? 'Editar ' + branch.nombre : 'Nueva sucursal';
    document.getElementById('qbSubmit').textContent = branch ? 'Guardar cambios' : 'Crear sucursal';
    document.getElementById('qbForm').action = '/admin/branches' + (branch ? '/update' : '') + location.search;
    document.getElementById('qbId').value = branch ? branch.id : '';
    document.getElementById('qbNombre').value = draft && draft.nombre !== undefined ? draft.nombre : (branch ? branch.nombre : '');
    document.getElementById('qbTelefono').value = draft && draft.telefono !== undefined ? draft.telefono : (branch ? branch.telefono : '');
    document.getElementById('qbDireccion').value = draft && draft.direccion !== undefined ? draft.direccion : (branch ? branch.direccion : '');
    document.getElementById('qbRef').value = draft && draft.direccion_referencia !== undefined ? draft.direccion_referencia : (branch ? branch.direccion_referencia : '');
    document.getElementById('qbSearchInput').value = '';
    document.getElementById('qbResults').innerHTML = '';
    hideGeoMessage();
    document.getElementById('qbDrawer').classList.add('open');
    document.getElementById('qbBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';

    if (!drawerMap) {
        drawerMap = L.map('qbMap').setView(DEFAULT_CENTER, 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(drawerMap);
        drawerMarker = L.marker(DEFAULT_CENTER, { draggable: true }).addTo(drawerMap);
        drawerMarker.on('dragend', () => {
            const pos = drawerMarker.getLatLng();
            setDrawerCoords(pos.lat.toFixed(8), pos.lng.toFixed(8));
        });
    }

    const draftCoords = draft && draft.lat ? [Number(draft.lat), Number(draft.lon)] : (branch && branch.lat !== null && branch.lon !== null ? [branch.lat, branch.lon] : null);
    if (draftCoords) {
        drawerMarker.setLatLng(draftCoords);
        drawerMap.setView(draftCoords, 16);
        setDrawerCoords(draftCoords[0].toFixed(8), draftCoords[1].toFixed(8));
    } else {
        drawerMarker.setLatLng(DEFAULT_CENTER);
        drawerMap.setView(DEFAULT_CENTER, 12);
        setDrawerCoords('', '');
    }
    setTimeout(() => drawerMap.invalidateSize(), 260);
    setTimeout(() => document.getElementById('qbNombre').focus(), 300);
}

function closeEditor() {
    document.getElementById('qbDrawer').classList.remove('open');
    document.getElementById('qbBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

let lastGeocodeRequest = 0;
const GEOCODE_THROTTLE_MS = 1100;

async function searchBranchAddress() {
    const input = document.getElementById('qbSearchInput').value.trim();
    const resultsContainer = document.getElementById('qbResults');
    if (!input) return;
    const now = Date.now();
    if (now - lastGeocodeRequest < GEOCODE_THROTTLE_MS) {
        showGeoMessage('Espera un momento antes de buscar de nuevo.');
        return;
    }
    lastGeocodeRequest = now;

    resultsContainer.innerHTML = '';
    showGeoMessage('Buscando…');
    try {
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('q', input);
        const response = await fetch('/checkout/geocode?' + searchParams.toString());
        const data = await response.json();
        if (!response.ok && data.error) { showGeoMessage(data.error); return; }
        if (data.error || !data.results.length) { showGeoMessage('No se encontraron resultados. Prueba con otra dirección.'); return; }
        resultsContainer.dataset.results = JSON.stringify(data.results);
        resultsContainer.innerHTML = data.results.map((result, index) => `
            <button type="button" class="geocode-result">
                <b>${escapeHtml(result.display_name)}</b>
                <small>${Number(result.lat).toFixed(6)}, ${Number(result.lon).toFixed(6)}</small>
            </button>`).join('');
        resultsContainer.querySelectorAll('.geocode-result').forEach((btn, index) => btn.addEventListener('click', () => {
            const results = JSON.parse(resultsContainer.dataset.results || '[]');
            const result = results[index];
            if (!result) return;
            setDrawerCoords(Number(result.lat).toFixed(8), Number(result.lon).toFixed(8));
            resultsContainer.innerHTML = '';
            hideGeoMessage();
        }));
        showGeoMessage('Selecciona una dirección de la lista.');
    } catch (e) {
        showGeoMessage('La búsqueda está tardando un momento. Intenta de nuevo en unos segundos.');
    }
}

function showGeoMessage(message) {
    const el = document.getElementById('qbMessage');
    el.textContent = message;
    el.style.display = 'block';
}

function hideGeoMessage() {
    const el = document.getElementById('qbMessage');
    el.style.display = 'none';
    el.textContent = '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

const QB_DRAFT_KEY = 'qb-branch-draft';

function saveDraft() {
    try {
        sessionStorage.setItem(QB_DRAFT_KEY, JSON.stringify({
            id: document.getElementById('qbId').value,
            nombre: document.getElementById('qbNombre').value,
            telefono: document.getElementById('qbTelefono').value,
            direccion: document.getElementById('qbDireccion').value,
            direccion_referencia: document.getElementById('qbRef').value,
            lat: document.getElementById('qbLat').value,
            lon: document.getElementById('qbLon').value,
        }));
    } catch (e) { /* almacenamiento no disponible */ }
}

document.getElementById('qbForm').addEventListener('submit', saveDraft);

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeEditor();
});

initCardMaps();

function loadDraft() {
    try { return JSON.parse(sessionStorage.getItem(QB_DRAFT_KEY) || 'null'); } catch (e) { return null; }
}

const qbOpenParam = new URLSearchParams(location.search).get('editar');
<?php if ($success): ?>sessionStorage.removeItem(QB_DRAFT_KEY);<?php endif; ?>
const qbDraft = loadDraft();
if (qbOpenParam) {
    openEditor(Number(qbOpenParam), qbDraft && String(qbDraft.id) === String(qbOpenParam) ? qbDraft : null);
    sessionStorage.removeItem(QB_DRAFT_KEY);
    history.replaceState(null, '', location.pathname + (new URLSearchParams(location.search).get('tenant') ? '?tenant=' + encodeURIComponent(new URLSearchParams(location.search).get('tenant')) : ''));
} else if (qbDraft && qbDraft.nombre) {
    openDraftNotice(qbDraft);
}

function openDraftNotice(draft) {
    const zone = document.getElementById('qbToasts');
    const el = document.createElement('div');
    el.className = 'qb-toast';
    el.style.display = 'grid';
    el.style.gap = '8px';
    el.innerHTML = '<span>Tienes una sucursal sin guardar.</span>';
    const btn = document.createElement('button');
    btn.className = 'chip';
    btn.style.justifySelf = 'start';
    btn.textContent = 'Continuar donde iba';
    btn.addEventListener('click', () => {
        el.remove();
        openEditor(draft.id, draft);
    });
    el.appendChild(btn);
    zone.appendChild(el);
    setTimeout(() => el.remove(), 8000);
}
<?php if ($success): ?>qbToast(<?= json_encode($success) ?>, true);<?php endif; ?>
<?php if ($error): ?>qbToast(<?= json_encode($error) ?>, false);<?php endif; ?>
</script>
</body>
</html>
