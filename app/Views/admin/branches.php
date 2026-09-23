<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Sucursales · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .geocode-results { display: grid; gap: 8px; margin-top: 8px; }
        .geocode-result { text-align: left; background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 10px; cursor: pointer; }
        .geocode-result:hover { border-color: var(--brand, #cc4b25); }
        .geocode-result b { display: block; font-size: 0.9rem; }
        .geocode-result small { color: #666; font-size: 0.75rem; }
        .geocode-message { font-size: 0.85rem; color: #666; margin: 0; }
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>Sucursales</h1><p>Administra los puntos de venta del negocio.</p></div>
        <button class="primary" onclick="document.querySelector('#newBranch').showModal()">+ Nueva sucursal</button>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="crud-card">
        <div class="crud-table branches-table">
            <div class="crud-row crud-row-head">
                <span>Nombre</span><span>Dirección</span><span>Ubicación</span><span>Estado</span><span>Acciones</span>
            </div>
            <?php foreach ($branches as $branch): ?>
                <details class="crud-item">
                    <summary class="crud-row">
                        <span><b><?= e($branch['nombre']) ?></b></span>
                        <span><?= e($branch['direccion']) ?></span>
                        <span><?= $branch['latitud'] !== null && $branch['longitud'] !== null ? 'Configurada' : '<span class="warning">Sin ubicación</span>' ?></span>
                        <span><span class="status-pill <?= (int) $branch['activa'] === 1 ? 'ok' : 'off' ?>"><?= (int) $branch['activa'] === 1 ? 'Activa' : 'Inactiva' ?></span></span>
                        <span class="row-actions">
                            <span class="btn-edit">Editar</span>
                            <form method="post" action="/admin/branches/toggle<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <input type="hidden" name="id" value="<?= (int) $branch['id'] ?>">
                                <button class="btn-muted"><?= (int) $branch['activa'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        </span>
                    </summary>
                    <div class="crud-detail">
                        <form class="edit-grid branch-edit" method="post" action="/admin/branches/update<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                            <input type="hidden" name="id" value="<?= (int) $branch['id'] ?>">
                            <label>Nombre<input name="nombre" required value="<?= e($branch['nombre']) ?>"></label>
                            <label>Teléfono<input name="telefono" value="<?= e($branch['telefono']) ?>"></label>
                            <label class="wide">Dirección<textarea name="direccion" required><?= e($branch['direccion']) ?></textarea></label>
                            <label class="wide">Referencias<textarea name="direccion_referencia"><?= e($branch['direccion_referencia'] ?? '') ?></textarea></label>
                            <input type="hidden" name="latitud" id="lat-<?= (int) $branch['id'] ?>" value="<?= $branch['latitud'] !== null ? (float) $branch['latitud'] : '' ?>">
                            <input type="hidden" name="longitud" id="lon-<?= (int) $branch['id'] ?>" value="<?= $branch['longitud'] !== null ? (float) $branch['longitud'] : '' ?>">
                            <div class="wide map-container">
                                <label>Ubicación en mapa</label>
                                <div class="branch-map" id="map-<?= (int) $branch['id'] ?>" data-lat="<?= $branch['latitud'] !== null ? (float) $branch['latitud'] : '' ?>" data-lon="<?= $branch['longitud'] !== null ? (float) $branch['longitud'] : '' ?>"></div>
                                <small class="muted">Buscá una dirección o arrastrá el pin para ajustar.</small>
                                <div class="map-search">
                                    <input type="text" class="map-search-input" placeholder="Buscar dirección..." onkeydown="if(event.key==='Enter'){event.preventDefault();searchBranchAddress(this, <?= (int) $branch['id'] ?>);}">
                                    <button type="button" class="chip" onclick="searchBranchAddress(this, <?= (int) $branch['id'] ?>)">Buscar dirección</button>
                                </div>
                                <div class="geocode-results" id="geocode-results-<?= (int) $branch['id'] ?>"></div>
                                <div class="geocode-message" id="geocode-message-<?= (int) $branch['id'] ?>" style="display:none;margin-top:8px;"></div>
                                <?php if ($branch['latitud'] === null || $branch['longitud'] === null): ?>
                                    <p class="warning">Esta sucursal no tiene ubicación. No se podrá ofrecer delivery hasta configurarla.</p>
                                <?php endif; ?>
                            </div>
                            <button class="primary">Guardar cambios</button>
                        </form>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<dialog id="newBranch" class="crud-modal">
    <form class="card" method="post" action="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
        <div class="modal-head"><div><h2>Nueva sucursal</h2><p class="muted">Agrega un punto de venta.</p></div><button type="button" class="chip" onclick="document.querySelector('#newBranch').close()">Cerrar</button></div>
        <label>Nombre<input name="nombre" required placeholder="Sucursal Centro"></label>
        <label>Teléfono<input name="telefono" placeholder="10 dígitos"></label>
        <label>Dirección<textarea name="direccion" required placeholder="Calle, número, colonia"></textarea></label>
        <label>Referencias<textarea name="direccion_referencia" placeholder="Entre calles, color de fachada, etc."></textarea></label>
        <input type="hidden" name="latitud" id="lat-new" value="">
        <input type="hidden" name="longitud" id="lon-new" value="">
        <div class="map-container">
            <label>Ubicación en mapa</label>
            <div class="branch-map" id="map-new" data-lat="" data-lon=""></div>
            <small class="muted">Buscá una dirección o arrastrá el pin para ajustar.</small>
        <div class="map-search">
            <input type="text" class="map-search-input" placeholder="Buscar dirección..." onkeydown="if(event.key==='Enter'){event.preventDefault();searchBranchAddress(this, 'new');}">
            <button type="button" class="chip" onclick="searchBranchAddress(this, 'new')">Buscar dirección</button>
        </div>
        <div class="geocode-results" id="geocode-results-new"></div>
        <div class="geocode-message" id="geocode-message-new" style="display:none;margin-top:8px;"></div>
        </div>
        <button class="primary" style="width:100%">Crear sucursal</button>
    </form>
</dialog>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const defaultCenter = [19.4326, -99.1332];
const branchMaps = {};

function initBranchMaps() {
    document.querySelectorAll('.branch-map').forEach(container => {
        const id = container.id.replace('map-', '');
        const latInput = document.getElementById('lat-' + id);
        const lonInput = document.getElementById('lon-' + id);
        const lat = parseFloat(container.dataset.lat);
        const lon = parseFloat(container.dataset.lon);
        const hasLocation = !isNaN(lat) && !isNaN(lon);

        const map = L.map(container).setView(hasLocation ? [lat, lon] : defaultCenter, hasLocation ? 16 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker(hasLocation ? [lat, lon] : defaultCenter, { draggable: true }).addTo(map);
        if (hasLocation) marker.setLatLng([lat, lon]);

        marker.on('dragend', () => {
            const pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(8);
            lonInput.value = pos.lng.toFixed(8);
        });

        branchMaps[id] = { map, marker, latInput, lonInput };
    });
}

let lastAdminGeocodeRequest = 0;
const ADMIN_GEOCODE_THROTTLE_MS = 1100;

async function searchBranchAddress(button, id) {
    const container = button.closest('.map-container');
    const input = container.querySelector('.map-search-input').value.trim();
    const resultsContainer = document.getElementById('geocode-results-' + id);
    const messageContainer = document.getElementById('geocode-message-' + id);
    if (!input) return;

    const now = Date.now();
    if (now - lastAdminGeocodeRequest < ADMIN_GEOCODE_THROTTLE_MS) {
        showAdminGeocodeMessage(messageContainer, 'Espera un momento antes de buscar de nuevo.');
        return;
    }
    lastAdminGeocodeRequest = now;

    button.disabled = true;
    resultsContainer.innerHTML = '';
    showAdminGeocodeMessage(messageContainer, 'Buscando...');

    try {
        const searchParams = new URLSearchParams(window.location.search);
        searchParams.set('q', input);
        const response = await fetch('/checkout/geocode?' + searchParams.toString());
        const data = await response.json();

        if (!response.ok && data.error) {
            showAdminGeocodeMessage(messageContainer, data.error);
            return;
        }

        if (data.error || !data.results.length) {
            showAdminGeocodeMessage(messageContainer, 'No se encontraron resultados. Probá con otra dirección.');
            return;
        }

        resultsContainer.innerHTML = data.results.map((result, index) => `
            <button type="button" class="geocode-result" onclick="selectAdminGeocodeResult(${index}, '${id}')">
                <b>${escapeHtml(result.display_name)}</b>
                <small>${Number(result.lat).toFixed(6)}, ${Number(result.lon).toFixed(6)}</small>
            </button>
        `).join('');
        resultsContainer.dataset.results = JSON.stringify(data.results);
        showAdminGeocodeMessage(messageContainer, 'Seleccioná una dirección de la lista.');
    } catch (e) {
        showAdminGeocodeMessage(messageContainer, 'La búsqueda está tardando un momento. Intenta nuevamente en unos segundos.');
    } finally {
        button.disabled = false;
    }
}

function selectAdminGeocodeResult(index, id) {
    const resultsContainer = document.getElementById('geocode-results-' + id);
    const messageContainer = document.getElementById('geocode-message-' + id);
    const results = JSON.parse(resultsContainer.dataset.results || '[]');
    const result = results[index];
    if (!result || !branchMaps[id]) return;

    const { map, marker, latInput, lonInput } = branchMaps[id];
    const latLng = [result.lat, result.lon];
    map.setView(latLng, 16);
    marker.setLatLng(latLng);
    latInput.value = result.lat.toFixed(8);
    lonInput.value = result.lon.toFixed(8);
    resultsContainer.innerHTML = '';
    hideAdminGeocodeMessage(messageContainer);
}

function showAdminGeocodeMessage(container, message) {
    if (!container) return;
    container.textContent = message;
    container.style.display = 'block';
}

function hideAdminGeocodeMessage(container) {
    if (!container) return;
    container.style.display = 'none';
    container.textContent = '';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

document.querySelectorAll('details.crud-item').forEach(details => {
    details.addEventListener('toggle', () => {
        const map = details.querySelector('.branch-map');
        if (map && branchMaps[map.id.replace('map-', '')]) {
            setTimeout(() => branchMaps[map.id.replace('map-', '')].map.invalidateSize(), 100);
        }
    });
});

initBranchMaps();
</script>
</body>
</html>
