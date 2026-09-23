const state = {
  products: window.ORDENA.products || [],
  branches: window.ORDENA.branches || [],
  zonesByBranch: window.ORDENA.zonesByBranch || {},
  radioZonesByBranch: window.ORDENA.radioZonesByBranch || {},
  cart: window.ORDENA.cart || [],
  mode: 'pickup',
  branchId: null,
  selected: null,
  category: 'all',
  customer: null,
  delivery: {
    lat: null,
    lon: null,
    fee: 0,
    minOrder: 0,
    distanceKm: 0,
    zoneName: '',
    zoneId: null,
    useMap: false,
  },
};

const money = value => '$' + Number(value).toLocaleString('es-MX', { maximumFractionDigits: 0 });
const apiUrl = path => {
  const [basePath, existingQuery] = path.split('?');
  const params = new URLSearchParams(existingQuery || '');
  const locationParams = new URLSearchParams(window.location.search);
  for (const [key, value] of locationParams) {
    if (!params.has(key)) params.set(key, value);
  }
  if (!params.has('tenant') && window.ORDENA?.tenant) {
    params.set('tenant', window.ORDENA.tenant);
  }
  return basePath + (params.toString() ? '?' + params.toString() : '');
};
const subtotal = () => state.cart.reduce((sum, item) => sum + Number(item.total), 0);

function renderProducts() {
  const visible = state.products.filter(product => state.category === 'all' || String(product.categoria_id) === state.category);
  document.querySelector('#products').innerHTML = visible.map(product => `
    <article class="card">
      ${product.imagen_thumb ? `<img class="photo product-img" src="${escapeHtml(product.imagen_thumb)}" alt="${escapeHtml(product.nombre)}">` : '<div class="photo"></div>'}
      <h3>${escapeHtml(product.nombre)}</h3>
      <p>${escapeHtml(product.descripcion || '')}</p>
      <div class="price">${money(product.precio)}<button class="primary small" onclick="customize(${product.id})">Agregar</button></div>
    </article>
  `).join('');
}

function renderBranches() {
  const container = document.querySelector('#branches');
  if (!container) return;
  container.innerHTML = branchCardsHtml();
}

function branchCardsHtml() {
  const openBranch = state.branches.find(branch => Number(branch.abierta) === 1);
  state.branchId = state.branchId || (openBranch ? Number(openBranch.id) : null);
  return state.branches.length ? state.branches.map(branch => {
    const isOpen = Number(branch.abierta) === 1;
    const active = Number(branch.id) === Number(state.branchId);
    const hours = branch.abre && branch.cierra ? `${String(branch.abre).slice(0, 5)} - ${String(branch.cierra).slice(0, 5)}` : 'Sin horario configurado';
    const hasLocation = branch.latitud !== null && branch.longitud !== null;
    return `
      <button class="branch-card ${active ? 'active' : ''} ${isOpen ? '' : 'disabled'}" ${isOpen ? `onclick="selectBranch(${branch.id})"` : 'disabled'}>
        <span class="branch-top"><b>${escapeHtml(branch.nombre)}</b><span class="branch-status ${isOpen ? 'open' : 'closed'}">${isOpen ? 'Abierta' : 'Cerrada'}</span></span>
        <span class="branch-address">${escapeHtml(branch.direccion || '')}</span>
        <span class="branch-hours">Hoy: ${escapeHtml(hours)}${hasLocation ? '' : ' · Sin ubicación'}</span>
      </button>
    `;
  }).join('') : '<p class="muted">Este negocio aún no tiene sucursales activas.</p>';
}

function selectBranch(id) {
  state.branchId = Number(id);
  state.delivery = { lat: null, lon: null, fee: 0, minOrder: 0, distanceKm: 0, zoneName: '', zoneId: null, useMap: false };
  renderBranches();
  const checkoutBranches = document.querySelector('#checkoutBranches');
  if (checkoutBranches) checkoutBranches.innerHTML = branchCardsHtml();
  renderDeliverySection();
}

function selectedBranch() {
  return state.branches.find(branch => Number(branch.id) === Number(state.branchId));
}

function hasRadioZones(branchId) {
  return (state.radioZonesByBranch[branchId] || []).length > 0;
}

function renderDeliverySection() {
  const branch = selectedBranch();
  const useMap = branch && branch.latitud !== null && branch.longitud !== null && hasRadioZones(branch.id);
  state.delivery.useMap = useMap;

  const manualBlock = document.querySelector('#manualZoneBlock');
  const mapBlock = document.querySelector('#mapZoneBlock');
  if (manualBlock) manualBlock.style.display = useMap ? 'none' : 'grid';
  if (mapBlock) mapBlock.style.display = useMap ? 'grid' : 'none';

  if (useMap) {
    setTimeout(initDeliveryMap, 50);
  }
  updateDeliveryTotals();
}

function renderDeliveryZones() {
  const select = document.querySelector('#zoneId');
  if (!select) return;
  const zones = state.zonesByBranch[state.branchId] || [];
  select.innerHTML = '<option value="">Selecciona zona</option>' + zones.map(zone => `
    <option value="${Number(zone.id)}" data-fee="${Number(zone.costo_envio)}" data-min="${zone.pedido_minimo !== null ? Number(zone.pedido_minimo) : ''}">
      ${escapeHtml(zone.nombre)} — ${money(zone.costo_envio)}${zone.pedido_minimo !== null ? ' (mín. ' + money(zone.pedido_minimo) + ')' : ''}
    </option>
  `).join('');
  updateDeliveryTotals();
}

function selectCheckoutMode(mode) {
  state.mode = mode;
  document.querySelectorAll('#checkoutModes .mode').forEach(button => button.classList.toggle('active', button.dataset.mode === mode));
  const deliveryBlock = document.querySelector('#deliveryBlock');
  const tableBlock = document.querySelector('#tableBlock');
  if (deliveryBlock) deliveryBlock.style.display = mode === 'delivery' ? 'grid' : 'none';
  if (tableBlock) tableBlock.style.display = mode === 'mesa' ? 'grid' : 'none';
  if (mode === 'delivery') renderDeliverySection();
  updateDeliveryTotals();
}

function updateDeliveryTotals() {
  const sub = subtotal();
  const feeDisplay = document.querySelector('#deliveryFeeDisplay');
  const totalDisplay = document.querySelector('#checkoutTotalDisplay');
  const minWarning = document.querySelector('#minOrderWarning');
  const distanceDisplay = document.querySelector('#distanceDisplay');
  const zoneDisplay = document.querySelector('#zoneDisplay');
  const feeSummary = document.querySelector('#deliveryFeeSummary');
  const distanceSummary = document.querySelector('#distanceSummary');
  const zoneSummary = document.querySelector('#zoneSummary');

  if (state.mode !== 'delivery') {
    if (feeDisplay) feeDisplay.textContent = money(0);
    if (totalDisplay) totalDisplay.textContent = money(sub);
    if (minWarning) minWarning.style.display = 'none';
    return;
  }

  if (state.delivery.useMap) {
    const distanceText = state.delivery.distanceKm > 0 ? `${Number(state.delivery.distanceKm).toFixed(2)} km` : '—';
    const zoneText = state.delivery.zoneName || '—';
    const feeText = money(state.delivery.fee);
    if (feeDisplay) feeDisplay.textContent = feeText;
    if (totalDisplay) totalDisplay.textContent = money(sub + state.delivery.fee);
    if (distanceDisplay) distanceDisplay.textContent = distanceText;
    if (zoneDisplay) zoneDisplay.textContent = zoneText;
    if (feeSummary) feeSummary.textContent = feeText;
    if (distanceSummary) distanceSummary.textContent = distanceText;
    if (zoneSummary) zoneSummary.textContent = zoneText;
    if (minWarning) {
      minWarning.textContent = state.delivery.minOrder > 0 ? `Pedido mínimo para esta zona: ${money(state.delivery.minOrder)}` : '';
      minWarning.style.display = state.delivery.minOrder > 0 && sub < state.delivery.minOrder ? 'block' : 'none';
    }
    const summary = document.querySelector('#deliverySummary');
    if (summary) {
      summary.classList.toggle('outside', !state.delivery.zoneId);
    }
  } else {
    const zoneSelect = document.querySelector('#zoneId');
    const option = zoneSelect ? zoneSelect.options[zoneSelect.selectedIndex] : null;
    const fee = option && option.value ? Number(option.dataset.fee || 0) : 0;
    const min = option && option.dataset.min ? Number(option.dataset.min) : 0;
    if (feeDisplay) feeDisplay.textContent = money(fee);
    if (totalDisplay) totalDisplay.textContent = money(sub + fee);
    if (distanceDisplay) distanceDisplay.textContent = '—';
    if (zoneDisplay) zoneDisplay.textContent = option && option.value ? escapeHtml(option.text) : '—';
    if (minWarning) {
      minWarning.textContent = min > 0 ? `Pedido mínimo para esta zona: ${money(min)}` : '';
      minWarning.style.display = min > 0 && sub < min ? 'block' : 'none';
    }
  }
}

let deliveryMap = null;
let deliveryMarker = null;
let branchMarker = null;
let deliveryPinDragged = false;

function pinIcon(label, color) {
  return L.divIcon({
    className: 'custom-pin-container',
    html: `<div class="custom-pin" style="background:${color};"><span>${label}</span></div><div class="custom-pin-tip" style="border-top-color:${color};"></div>`,
    iconSize: [28, 36],
    iconAnchor: [14, 34],
    popupAnchor: [0, -34],
  });
}

function initDeliveryMap() {
  const container = document.querySelector('#deliveryMap');
  if (!container) return;

  const branch = selectedBranch();
  if (!branch || branch.latitud === null || branch.longitud === null) {
    container.innerHTML = '<p class="muted" style="padding:20px;">La sucursal no tiene ubicación configurada. Configurala en el admin para ver el mapa.</p>';
    return;
  }

  // Si el mapa anterior quedó huérfano (se cerró el modal), lo destruimos
  if (deliveryMap && deliveryMap.getContainer() !== container) {
    try { deliveryMap.remove(); } catch (e) {}
    deliveryMap = null;
    deliveryMarker = null;
    branchMarker = null;
  }

  if (typeof L === 'undefined') {
    container.innerHTML = '<p class="muted" style="padding:20px;">Error: no se cargó la librería del mapa. Recargá la página.</p>';
    return;
  }

  const center = [parseFloat(branch.latitud), parseFloat(branch.longitud)];

  function buildMap() {
    try {
      if (!deliveryMap) {
        deliveryMap = L.map(container).setView(center, 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors'
        }).addTo(deliveryMap);

        branchMarker = L.marker(center, { icon: pinIcon('S', '#2563eb'), zIndexOffset: -100 }).addTo(deliveryMap).bindPopup('Sucursal');

        deliveryMarker = L.marker(center, { icon: pinIcon('E', '#cc4b25'), draggable: true, zIndexOffset: 100 }).addTo(deliveryMap);
        deliveryMarker.on('dragend', () => { deliveryPinDragged = true; onDeliveryPinMove(); });
      } else {
        deliveryMap.setView(center, 13);
        if (branchMarker) branchMarker.setLatLng(center);
      }

      // Dibujar círculos de rangos
      deliveryMap.eachLayer(layer => {
        if (layer instanceof L.Circle) deliveryMap.removeLayer(layer);
      });
      (state.radioZonesByBranch[branch.id] || []).forEach(zone => {
        if (zone.radio_hasta_km) {
          L.circle(center, {
            radius: parseFloat(zone.radio_hasta_km) * 1000,
            color: '#cc4b25',
            fillColor: '#cc4b25',
            fillOpacity: 0.05,
            weight: 1,
          }).addTo(deliveryMap).bindPopup(`${escapeHtml(zone.nombre)}: $${Number(zone.costo_envio).toFixed(0)}`);
        }
      });

      // Asegurar que el mapa se renderice con el tamaño correcto después de que el modal esté visible
      setTimeout(() => {
        if (deliveryMap) {
          deliveryMap.invalidateSize();
          deliveryMap.setView(center, 13);
        }
      }, 300);
    } catch (err) {
      container.innerHTML = '<p class="muted" style="padding:20px;">Error al cargar el mapa: ' + escapeHtml(err.message) + '</p>';
    }
  }

  buildMap();
}

let lastGeocodeRequest = 0;
const GEOCODE_THROTTLE_MS = 1100;
let reverseGeocodeTimer = null;

async function searchDeliveryAddress() {
  const input = document.querySelector('#deliverySearch');
  const button = document.querySelector('#deliverySearchBtn');
  const btnText = document.querySelector('#searchBtnText');
  const resultsContainer = document.querySelector('#geocodeResults');
  if (!input || !input.value.trim()) return;

  const now = Date.now();
  if (now - lastGeocodeRequest < GEOCODE_THROTTLE_MS) {
    showGeocodeMessage('Esperá un momento antes de buscar de nuevo.', 'warning');
    return;
  }
  lastGeocodeRequest = now;

  if (button) button.disabled = true;
  if (btnText) btnText.textContent = 'Buscando...';
  resultsContainer.innerHTML = '';
  resultsContainer.style.display = 'none';
  showGeocodeMessage('Buscando direcciones...', 'info');

  try {
    const response = await fetch(apiUrl('/checkout/geocode?q=' + encodeURIComponent(input.value.trim())));
    const data = await response.json();

    if (!response.ok && data.error) {
      showGeocodeMessage(data.error, 'error');
      return;
    }

    if (data.error || !data.results.length) {
      showGeocodeMessage('No encontramos esa dirección exacta. Probá solo con calle y colonia, luego ajustá el pin al número.', 'warning');
      return;
    }

    resultsContainer.innerHTML = data.results.map((result, index) => `
      <button type="button" class="geocode-result" onclick="selectGeocodeResult(${index})">
        <b>${escapeHtml(shortAddress(result.display_name))}</b>
        <small>${escapeHtml(result.display_name)}</small>
        <small class="coord">${Number(result.lat).toFixed(5)}, ${Number(result.lon).toFixed(5)}</small>
      </button>
    `).join('');
    resultsContainer.style.display = 'block';
    state.lastGeocodeResults = data.results;
    showGeocodeMessage('Seleccioná una dirección de la lista o mové el pin en el mapa.', 'success');
  } catch (e) {
    showGeocodeMessage('La búsqueda está tardando. Intentá de nuevo en unos segundos.', 'error');
  } finally {
    if (button) button.disabled = false;
    if (btnText) btnText.textContent = 'Buscar';
  }
}

function shortAddress(full) {
  const parts = full.split(',').map(p => p.trim());
  return parts.slice(0, 2).join(', ');
}

function fillAddressFromNominatim(address) {
  if (!address) return;
  const calle = address.road || address.pedestrian || address.footway || address.living_street || address.cycleway || address.path || '';
  const numero = address.house_number || '';
  const colonia = address.neighbourhood || address.suburb || address.quarter || address.hamlet || address.city_district || '';
  const set = (id, value) => {
    const input = document.querySelector('#' + id);
    if (input && value) input.value = value;
  };
  set('calle', calle);
  set('numero', numero);
  set('colonia', colonia);
}

function selectGeocodeResult(index) {
  const results = state.lastGeocodeResults || [];
  const result = results[index];
  if (!result) return;

  document.querySelector('#geocodeResults').style.display = 'none';
  hideGeocodeMessage();
  document.querySelector('#deliverySearch').value = result.display_name;
  fillAddressFromNominatim(result.address);
  setDeliveryLocation(result.lat, result.lon);
  showGeocodeMessage('Ubicación aproximada. Arrastrá el pin rojo hasta tu número exacto.', 'success');
}

function showGeocodeMessage(message, type = 'info') {
  const container = document.querySelector('#geocodeMessage');
  if (!container) return;
  container.className = 'geocode-status ' + type;
  container.textContent = message;
  container.style.display = 'block';
}

function hideGeocodeMessage() {
  const container = document.querySelector('#geocodeMessage');
  if (!container) return;
  container.style.display = 'none';
  container.textContent = '';
}

function setDeliveryLocation(lat, lon) {
  initDeliveryMap();
  if (!deliveryMap || !deliveryMarker) return;
  deliveryMarker.setLatLng([lat, lon]);
  deliveryMap.setView([lat, lon], 17);
  onDeliveryPinMove();
}

async function onDeliveryPinMove() {
  if (!deliveryMarker) return;
  const pos = deliveryMarker.getLatLng();
  state.delivery.lat = parseFloat(pos.lat.toFixed(8));
  state.delivery.lon = parseFloat(pos.lng.toFixed(8));

  const branch = selectedBranch();
  if (!branch) return;

  document.querySelector('#deliveryLat').value = state.delivery.lat;
  document.querySelector('#deliveryLon').value = state.delivery.lon;

  // Calcular distancia localmente para mostrar inmediatamente
  state.delivery.distanceKm = haversine(parseFloat(branch.latitud), parseFloat(branch.longitud), state.delivery.lat, state.delivery.lon);
  showDeliverySummary(true);
  showGeocodeMessage('Calculando zona y costo de envío...', 'info');

  // Validar contra servidor
  try {
    const response = await post('/checkout/delivery-calculate', {
      branch_id: state.branchId,
      lat: state.delivery.lat,
      lon: state.delivery.lon,
    });

    if (response.error || !response.applicable) {
      state.delivery.fee = 0;
      state.delivery.minOrder = 0;
      state.delivery.zoneName = response.error || 'Fuera de cobertura';
      state.delivery.zoneId = null;
      showGeocodeMessage(state.delivery.zoneName, 'error');
    } else {
      state.delivery.fee = response.delivery_fee;
      state.delivery.minOrder = response.min_order;
      state.delivery.zoneName = response.zone_name;
      state.delivery.zoneId = response.zone_id;
      state.delivery.distanceKm = response.distance_km;
      hideGeocodeMessage();
    }
  } catch (e) {
    state.delivery.zoneName = 'Error de cálculo';
    state.delivery.zoneId = null;
    showGeocodeMessage('No pudimos calcular el envío. Intentá de nuevo.', 'error');
  }

  updateDeliveryTotals();

  // Autocompletar calle/colonia desde el pin final (solo si el usuario arrastró)
  if (deliveryPinDragged) {
    deliveryPinDragged = false;
    clearTimeout(reverseGeocodeTimer);
    reverseGeocodeTimer = setTimeout(() => {
      lookupAddressFromPin(pos.lat, pos.lng);
    }, 900);
  }
}

function lookupAddressFromPin(lat, lon) {
  fetch(apiUrl(`/checkout/reverse-geocode?lat=${lat.toFixed(7)}&lon=${lon.toFixed(7)}`))
    .then(response => response.json())
    .then(data => {
      if (data.result) fillAddressFromNominatim(data.result.address || {});
    })
    .catch(() => {});
}

function showDeliverySummary(show) {
  const summary = document.querySelector('#deliverySummary');
  if (summary) summary.style.display = show ? 'grid' : 'none';
}

function haversine(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function renderCart(payload) {
  if (payload) state.cart = payload.items;
  const total = subtotal();
  document.querySelector('#cartCount').textContent = state.cart.length;
  document.querySelector('#cartTotal').textContent = money(total);
  document.querySelector('#totalAside').textContent = money(total);
  document.querySelector('#cartItems').innerHTML = state.cart.length ? state.cart.map((item, index) => `
    <div class="row">
      <div>
        <b>${escapeHtml(item.name)}</b><br>
        <small class="muted">${summary(item)}</small>
      </div>
      <div><b>${money(item.total)}</b><button class="chip small" onclick="removeItem(${index})">x</button></div>
    </div>
  `).join('') : '<p class="muted">Aún no agregas productos.</p>';
}

function customize(productId) {
  state.selected = state.products.find(product => Number(product.id) === Number(productId));
  if (!state.selected) return;

  const options = (state.selected.opciones || []).map(option => {
    if (option.tipo === 'texto') return '';
    const inputs = option.valores.map(value => `
      <label class="option-line">
        <input class="option-value" type="${option.tipo === 'unica' ? 'radio' : 'checkbox'}" name="values-${option.id}" value="${value.id}">
        ${escapeHtml(value.nombre)} ${Number(value.precio_extra) > 0 ? '+' + money(value.precio_extra) : ''}
      </label>
    `).join('');
    return `<fieldset><legend>${escapeHtml(option.nombre)}</legend>${inputs}</fieldset>`;
  }).join('');

  document.querySelector('#productForm').innerHTML = `
    <h2>${escapeHtml(state.selected.nombre)}</h2>
    <p class="muted">Personaliza esta pieza antes de agregarla. Si agregas varias, se guardan como piezas separadas.</p>
    ${options}
    <label>Indicaciones libres<textarea id="notes" placeholder="Ej. sin cebolla, término medio, salsa aparte"></textarea></label>
    <label>Cantidad<input id="quantity" type="number" min="1" max="20" value="1"></label>
    <button class="primary" style="width:100%" onclick="addConfigured()">Agregar al carrito</button>
  `;
  document.querySelector('#productModal').classList.add('open');
}

async function addConfigured() {
  const values = [...document.querySelectorAll('.option-value:checked')].map(input => Number(input.value));
  const response = await post('/cart/add', {
    product_id: state.selected.id,
    values,
    notes: document.querySelector('#notes').value,
    quantity: Number(document.querySelector('#quantity').value || 1),
  });
  if (!response.error) {
    closeAll();
    renderCart(response);
  }
}

async function removeItem(index) {
  const response = await post('/cart/remove', { index });
  if (!response.error) renderCart(response);
}

function checkout() {
  if (!state.cart.length) return;
  closeAll();
  const sub = subtotal();
  const accountBlock = state.customer
    ? '<div class="account-callout success"><b>' + escapeHtml(state.customer.nombre) + '</b> · ' + escapeHtml(state.customer.telefono) + ' · pedido verificado.</div>'
    : '<div class="account-callout warning">Para confirmar tu pedido necesitás iniciar sesión con tu número verificado.</div>';
  const confirmBtn = state.customer
    ? '<button class="primary" style="width:100%" onclick="confirmOrder()">Confirmar pedido</button>'
    : '<button class="primary" style="width:100%" onclick="openAccount()">Iniciar sesión / Crear cuenta</button>';
  document.querySelector('#checkoutContent').innerHTML = `
    <button class="chip" onclick="closeAll()">Cerrar x</button>
    <h2>Revisa y finaliza tu pedido</h2>
    <p class="muted">Elige cómo quieres recibirlo y desde qué sucursal se preparará.</p>
    ${accountBlock}
    <div class="modes checkout-modes" id="checkoutModes">
      <button class="mode ${state.mode === 'pickup' ? 'active' : ''}" data-mode="pickup" onclick="selectCheckoutMode('pickup')" type="button"><strong>Recoger</strong><span>Pasas por tu pedido</span></button>
      <button class="mode ${state.mode === 'mesa' ? 'active' : ''}" data-mode="mesa" onclick="selectCheckoutMode('mesa')" type="button"><strong>En mesa</strong><span>Consumes aqui</span></button>
      <button class="mode ${state.mode === 'delivery' ? 'active' : ''}" data-mode="delivery" onclick="selectCheckoutMode('delivery')" type="button"><strong>A domicilio</strong><span>Pago contra entrega</span></button>
    </div>
    <h3>Sucursal</h3>
    <div class="branches-public checkout-branches" id="checkoutBranches">${branchCardsHtml()}</div>
    ${state.customer
      ? `<label>Nombre<input id="name" value="${escapeHtml(state.customer.nombre)}" disabled></label><label>Celular<input id="phone" value="${escapeHtml(state.customer.telefono)}" disabled></label>`
      : ''}
    <div id="deliveryBlock" style="display:${state.mode === 'delivery' ? 'grid' : 'none'}; gap:12px;">
      <div id="manualZoneBlock" style="display:none;">
        <label>Zona de entrega
          <select id="zoneId" onchange="updateDeliveryTotals()"><option value="">Selecciona zona</option></select>
        </label>
      </div>
      <div id="mapZoneBlock" class="map-zone-block" style="display:none;">
        <div class="delivery-search-box">
          <label for="deliverySearch">¿Dónde entregamos?</label>
          <div class="delivery-search-input-wrap">
            <input id="deliverySearch" type="text" placeholder="Calle y colonia (ej. Hidalgo, Celaya)" onkeydown="if(event.key==='Enter'){event.preventDefault();searchDeliveryAddress();}">
            <button type="button" id="deliverySearchBtn" class="primary" onclick="searchDeliveryAddress()">
              <span id="searchBtnText">Buscar</span>
            </button>
          </div>
          <small class="muted" style="display:block;margin-top:6px;font-size:0.8rem;">Buscá la calle y ajustá el pin rojo a tu número exacto.</small>
          <div id="geocodeResults" class="geocode-results-dropdown" style="display:none;"></div>
          <div id="geocodeMessage" class="geocode-status info" style="display:none;"></div>
        </div>
        <div class="delivery-map-wrap">
          <div id="deliveryMap"><p class="muted" style="padding:20px;text-align:center;">Cargando mapa...</p></div>
          <div class="map-hint">Arrastrá el pin para ajustar tu ubicación exacta</div>
        </div>
        <div id="deliverySummary" class="delivery-summary" style="display:none;">
          <div class="summary-row"><span>Distancia</span><b id="distanceSummary">—</b></div>
          <div class="summary-row"><span>Zona</span><b id="zoneSummary">—</b></div>
          <div class="summary-row"><span>Envío</span><b id="deliveryFeeSummary">$0</b></div>
        </div>
        <input type="hidden" id="deliveryLat">
        <input type="hidden" id="deliveryLon">
      </div>
      <label>Calle<input id="calle" placeholder="Ej. Insurgentes"></label>
      <label>Número exterior<input id="numero" placeholder="Ej. 123"></label>
      <label>Colonia<input id="colonia" placeholder="Ej. Centro"></label>
      <label>Referencias<textarea id="referencias" placeholder="Portón azul, timbrar en 301, etc."></textarea></label>
      <div class="totals" style="margin-top:8px;">
        <div class="row"><span>Distancia</span><b id="distanceDisplay">—</b></div>
        <div class="row"><span>Zona</span><b id="zoneDisplay">—</b></div>
        <div class="row"><span>Subtotal</span><b id="subtotalDisplay">${money(sub)}</b></div>
        <div class="row"><span>Envío</span><b id="deliveryFeeDisplay">${money(0)}</b></div>
        <div class="row"><b>Total</b><b id="checkoutTotalDisplay">${money(sub)}</b></div>
      </div>
      <small class="warning" id="minOrderWarning" style="display:none;color:#b91c1c;"></small>
      <label>Forma de pago en efectivo</label>
      <label class="option-line"><input type="radio" name="payment" value="exact" checked onchange="toggleCashAmount()"> Pago exacto</label>
      <label class="option-line"><input type="radio" name="payment" value="cash" onchange="toggleCashAmount()"> Pagaré con</label>
      <label id="cashAmountLabel" style="display:none;">Monto con el que pagarás<input id="cashAmount" inputmode="numeric" placeholder="Ej. 500"></label>
      <p class="muted" style="font-size:0.85rem;">Pago contra entrega en efectivo. El restaurante realizará el envío con sus propios repartidores.</p>
    </div>
    <label id="tableBlock" style="display:${state.mode === 'mesa' ? 'grid' : 'none'}">Mesa<input id="table" placeholder="Ej. 4"></label>
    ${confirmBtn}
  `;
  document.querySelector('#checkoutModal').classList.add('open');
  renderDeliveryZones();
  renderDeliverySection();
  toggleCashAmount();
}

function toggleCashAmount() {
  const payment = document.querySelector('input[name="payment"]:checked')?.value || 'exact';
  const label = document.querySelector('#cashAmountLabel');
  if (label) label.style.display = payment === 'cash' ? 'grid' : 'none';
}

async function confirmOrder() {
  if (!state.customer) return openAccount();
  if (!state.branchId) return alert('Selecciona una sucursal abierta para continuar.');

  const branch = selectedBranch();
  if (state.mode === 'delivery' && branch && (branch.latitud === null || branch.longitud === null)) {
    return alert('La sucursal seleccionada no tiene ubicación configurada para delivery.');
  }

  const payload = {
    mode: state.mode,
    branch_id: state.branchId,
    table: document.querySelector('#table')?.value || '',
  };

  if (state.mode === 'delivery') {
    payload.calle = document.querySelector('#calle')?.value || '';
    payload.numero = document.querySelector('#numero')?.value || '';
    payload.colonia = document.querySelector('#colonia')?.value || '';
    payload.referencias = document.querySelector('#referencias')?.value || '';

    if (state.delivery.useMap) {
      if (!state.delivery.lat || !state.delivery.lon || !state.delivery.zoneId) {
        return alert('Selecciona una dirección de entrega válida dentro de la cobertura.');
      }
      payload.delivery_lat = state.delivery.lat;
      payload.delivery_lon = state.delivery.lon;
    } else {
      payload.zone_id = Number(document.querySelector('#zoneId')?.value || 0);
    }

    const payment = document.querySelector('input[name="payment"]:checked')?.value || 'exact';
    payload.pay_exact = payment === 'exact';
    payload.cash_amount = payment === 'cash' ? Number(document.querySelector('#cashAmount')?.value || 0) : 0;
  }

  const response = await post('/checkout/start', payload);
  if (response.error) return alert(response.error);

  const confirm = await post('/checkout/confirm', {});
  if (confirm.error) return alert(confirm.error);

  state.cart = [];
  renderCart();
  document.querySelector('#checkoutContent').innerHTML = `
    <button class="chip" onclick="closeAll()">Cerrar x</button>
    <div class="tag">PEDIDO RECIBIDO</div>
    <h2>Gracias por ordenar</h2>
    <p>Tu folio es <b>${escapeHtml(confirm.order.folio)}</b>.</p>
    <div class="card"><b>${confirm.mode === 'delivery' ? 'Efectivo contra entrega' : 'Pago en sucursal'}</b><p>El restaurante confirmará tu pedido.</p></div>
    <button class="primary" style="width:100%" onclick="goToMyOrders()">Ver mis pedidos</button>
  `;
}

function goToMyOrders() {
  closeAll();
  openMyOrders();
}

/* ===================== Cuenta de cliente ===================== */

function openAccount() {
  const modal = document.querySelector('#accountModal');
  if (!modal) return;
  renderAccount('login');
  modal.classList.add('open');
}

function renderAccount(view, phone) {
  const content = document.querySelector('#accountContent');
  const cerrar = '<button class="chip" onclick="closeAll()">Cerrar x</button>';
  if (view === 'register') {
    content.innerHTML = `
      ${cerrar}
      <h2>Crear cuenta</h2>
      <p class="muted">Verificaremos tu número una sola vez con un código por SMS.</p>
      <label>Nombre<input id="regName" placeholder="Tu nombre"></label>
      <label>Teléfono<input id="regPhone" inputmode="numeric" maxlength="10" placeholder="Ingresá tu celular a 10 dígitos" oninput="this.value=this.value.replace(/\\D/g,'')"></label>
      <label>Contraseña<input id="regPassword" type="password" placeholder="Mínimo 8 caracteres y una mayúscula"></label>
      <p class="form-error" id="regStatus"></p>
      <button class="primary" style="width:100%" onclick="accountRegister()">Crear cuenta</button>
      <p class="muted" style="text-align:center;margin-top:10px;">¿Ya tienes cuenta? <a href="#" onclick="renderAccount('login');return false;">Iniciar sesión</a></p>
    `;
    return;
  }
  if (view === 'verify') {
    content.innerHTML = `
      ${cerrar}
      <h2>Verifica tu número</h2>
      <p class="muted" id="verifyHint">Enviamos un código al <b>${escapeHtml(phone || '')}</b>.</p>
      <label>Código de 6 dígitos<input id="verifyCode" inputmode="numeric" maxlength="6" placeholder="000000" oninput="this.value=this.value.replace(/\\D/g,'')"></label>
      <p class="form-error" id="verifyStatus"></p>
      <button class="primary" style="width:100%" onclick="accountVerify()">Verificar y entrar</button>
      <button class="chip" style="width:100%;margin-top:8px;" onclick="accountResend()">Reenviar código</button>
    `;
    return;
  }
  if (view === 'orders') {
    openMyOrders();
    return;
  }
  content.innerHTML = `
    ${cerrar}
    <h2>Iniciar sesión</h2>
    <p class="muted">Ingresá con el teléfono y la contraseña que usaste en este negocio.</p>
    <label>Teléfono<input id="loginPhone" inputmode="numeric" maxlength="10" placeholder="Tu celular a 10 dígitos" oninput="this.value=this.value.replace(/\\D/g,'')"></label>
    <label>Contraseña<input id="loginPassword" type="password" placeholder="Tu contraseña"></label>
    <p class="form-error" id="loginStatus"></p>
    <button class="primary" style="width:100%" onclick="accountLogin()">Entrar</button>
    <p class="muted" style="text-align:center;margin-top:10px;">¿No tienes cuenta? <a href="#" onclick="renderAccount('register');return false;">Crear cuenta</a></p>
  `;
}

function showFormError(el, message) {
  if (el) {
    el.textContent = message;
    el.classList.add('visible');
  }
}

async function accountLogin() {
  const status = document.querySelector('#loginStatus');
  const phone = (document.querySelector('#loginPhone').value || '').replace(/\D/g, '');
  const password = document.querySelector('#loginPassword').value;
  if (!/^\d{10}$/.test(phone)) return showFormError(status, 'Ingresa tu celular a 10 dígitos.');
  if (password.length < 8) return showFormError(status, 'Tu contraseña debe tener al menos 8 caracteres.');
  const response = await post('/auth/login', { phone, password });
  if (response.error) {
    if (status) status.textContent = response.error;
    return;
  }
  await afterAccountSuccess();
}

async function accountRegister() {
  const status = document.querySelector('#regStatus');
  const name = document.querySelector('#regName').value.trim();
  const phone = (document.querySelector('#regPhone').value || '').replace(/\D/g, '');
  const password = document.querySelector('#regPassword').value;

  if (!name) return showFormError(status, 'Escribe tu nombre.');
  if (!/^\d{10}$/.test(phone)) return showFormError(status, 'Ingresa un celular de 10 dígitos (solo números).');
  if (password.length < 8) return showFormError(status, 'La contraseña debe tener al menos 8 caracteres.');
  if (!/[A-Z]/.test(password)) return showFormError(status, 'La contraseña debe incluir al menos una mayúscula.');

  const response = await post('/auth/register', { name, phone, password });
  if (response.error) {
    if (response.already_account) {
      renderAccount('login');
      const phoneInput = document.querySelector('#loginPhone');
      const loginPassword = document.querySelector('#loginPassword');
      if (phoneInput) phoneInput.value = phone;
      if (loginPassword) loginPassword.value = password;
      const loginStatus = document.querySelector('#loginStatus');
      if (loginStatus) {
        loginStatus.textContent = response.error;
        loginStatus.classList.add('visible');
      }
      return;
    }
    if (status) status.textContent = response.error;
    return;
  }
  window.__pendingPhone = response.pending_phone;
  renderAccount('verify', response.pending_phone);
  let hint = 'Enviamos un código al ' + response.pending_phone + '.';
  if (response.dev_only && response.code) hint += ' (Modo desarrollo: código ' + response.code + ')';
  const verifyHint = document.querySelector('#verifyHint');
  if (verifyHint) verifyHint.textContent = hint;
}

async function accountVerify() {
  const status = document.querySelector('#verifyStatus');
  const code = (document.querySelector('#verifyCode').value || '').replace(/\D/g, '');
  if (code.length !== 6) return showFormError(status, 'Ingresa el código de 6 dígitos que te enviamos.');
  const result = await post('/auth/verify', {
    phone: window.__pendingPhone || '',
    code,
  });
  if (result.error) {
    if (status) status.textContent = result.error;
    return;
  }
  await afterAccountSuccess();
}

async function accountResend() {
  const response = await post('/auth/resend', { phone: window.__pendingPhone || '' });
  const status = document.querySelector('#verifyStatus');
  if (response.error) {
    if (status) status.textContent = response.error;
    return;
  }
  let hint = 'Reenviamos un código al ' + response.pending_phone + '.';
  if (response.dev_only && response.code) hint += ' (Modo desarrollo: código ' + response.code + ')';
  document.querySelector('#verifyHint').textContent = hint;
  if (status) status.textContent = 'Código reenviado.';
}

async function afterAccountSuccess() {
  await loadCustomer();
  renderAccountBar();
  closeAll();
  const checkoutContent = document.querySelector('#checkoutContent');
  if (checkoutContent && checkoutContent.closest('#checkoutModal.open')) {
    checkout();
  }
}

async function accountLogout() {
  await post('/auth/logout', {});
  state.customer = null;
  renderAccountBar();
  closeAll();
}

async function loadCustomer() {
  try {
    const response = await fetch(apiUrl('/auth/me'));
    const data = await response.json();
    state.customer = data.logged ? data.customer : null;
  } catch (e) {
    state.customer = null;
  }
  return state.customer;
}

function renderAccountBar() {
  const label = document.querySelector('#accountFabLabel');
  if (!label) return;
  if (state.customer) {
    label.textContent = state.customer.nombre.split(' ')[0];
    const menu = document.querySelector('#accountMenu');
    if (menu) menu.style.display = 'grid';
  } else {
    label.textContent = 'Ingresar';
    const menu = document.querySelector('#accountMenu');
    if (menu) menu.style.display = 'none';
  }
}

function toggleAccountMenu() {
  if (!state.customer) return openAccount();
  const menu = document.querySelector('#accountMenu');
  if (menu) menu.style.display = menu.style.display === 'grid' ? 'none' : 'grid';
}

async function openMyOrders() {
  const modal = document.querySelector('#accountModal');
  if (!modal) return;
  const content = document.querySelector('#accountContent');
  content.innerHTML = `<button class="chip" onclick="closeAll()">Cerrar x</button><h2>Mis pedidos</h2><p class="muted">Cargando...</p>`;
  modal.classList.add('open');

  const response = await fetch(apiUrl('/mis-pedidos'));
  const data = await response.json();
  if (data.error || !data.orders) {
    content.innerHTML = `<button class="chip" onclick="closeAll()">Cerrar x</button><h2>Mis pedidos</h2><p class="muted">${escapeHtml(data.error || 'Sin pedidos todavía.')}</p>`;
    if (data.error && !state.customer) {
      content.innerHTML += '<button class="primary" style="width:100%" onclick="openAccount()">Iniciar sesión</button>';
    }
    return;
  }

  content.innerHTML = `
    <button class="chip" onclick="closeAll()">Cerrar x</button>
    <h2>Mis pedidos</h2>
    ${data.orders.length ? data.orders.map(order => `
      <div class="order-row card" onclick="showOrderDetail('${escapeHtml(order.folio)}')">
        <div><b>${escapeHtml(order.folio)}</b><br><small class="muted">${escapeHtml(order.created_at)} · ${money(order.total)}</small></div>
        <span class="order-status status-${escapeHtml(order.estado)}">${escapeHtml(order.estado_label)}</span>
      </div>
    `).join('') : '<p class="muted">Todavía no has hecho pedidos.</p>'}
  `;
}

async function showOrderDetail(folio) {
  const content = document.querySelector('#accountContent');
  content.innerHTML = `<button class="chip" onclick="closeAll()">Cerrar x</button><h2>Pedido ${escapeHtml(folio)}</h2><p class="muted">Cargando...</p>`;
  const response = await fetch(apiUrl('/mis-pedidos/ver?folio=' + encodeURIComponent(folio)));
  const data = await response.json();
  if (data.error) {
    content.innerHTML = `<button class="chip" onclick="closeAll()">Cerrar x</button><h2>Pedido ${escapeHtml(folio)}</h2><p class="muted">${escapeHtml(data.error)}</p>`;
    return;
  }
  const order = data.order;
  const typeLabel = { pickup: 'Recoger', mesa: 'En mesa', delivery: 'A domicilio' }[order.tipo] || order.tipo;
  content.innerHTML = `
    <button class="chip" onclick="closeAll()">Cerrar x</button>
    <h2>Pedido ${escapeHtml(order.folio)}</h2>
    <span class="order-status status-${escapeHtml(order.estado)}">${escapeHtml(order.estado_label)}</span>
    <div class="delivery-summary" style="margin-top:10px;">
      <div class="summary-row"><span>Modalidad</span><b>${typeLabel}</b></div>
      <div class="summary-row"><span>Fecha</span><b>${escapeHtml(order.created_at)}</b></div>
      ${order.direccion_entrega ? `<div class="summary-row"><span>Dirección</span><b>${escapeHtml(order.direccion_entrega)}</b></div>` : ''}
      <div class="summary-row"><span>Total</span><b>${money(order.total)}</b></div>
    </div>
    <h3 style="margin-top:14px;">Productos</h3>
    ${(order.detalles || []).map(item => `
      <div class="order-item card">
        <b>${escapeHtml(item.nombre_snapshot)}</b>
        <small class="muted">${money(item.total)}${item.notas ? ' · ' + escapeHtml(item.notas) : ''}</small>
        ${(item.opciones || []).map(op => `<small class="muted" style="display:block;">+ ${escapeHtml(op.valor_nombre_snapshot)}</small>`).join('')}
      </div>
    `).join('')}
    <button class="chip" style="width:100%;margin-top:12px;" onclick="openMyOrders()">Volver a mis pedidos</button>
  `;
}

function openCart() { document.querySelector('#drawer').classList.add('open'); renderCart(); }
function closeAll() { document.querySelectorAll('.drawer,.modal').forEach(element => element.classList.remove('open')); }

async function post(path, payload) {
  const response = await fetch(apiUrl(path), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  return response.json();
}

function summary(item) {
  const options = (item.options || []).map(option => `${option.value_name}${Number(option.price_extra) > 0 ? ' +' + money(option.price_extra) : ''}`);
  if (item.notes) options.push(item.notes);
  return escapeHtml(options.join(' · ') || 'Sin indicaciones');
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}

document.querySelectorAll('#modes .mode').forEach(button => button.onclick = () => {
  document.querySelectorAll('.mode').forEach(item => item.classList.remove('active'));
  button.classList.add('active');
  state.mode = button.dataset.mode;
});

document.querySelectorAll('#categories .chip').forEach(button => button.onclick = () => {
  document.querySelectorAll('#categories .chip').forEach(item => item.classList.remove('active'));
  button.classList.add('active');
  state.category = button.dataset.category;
  renderProducts();
});

renderProducts();
renderBranches();
renderCart();
loadCustomer().then(() => renderAccountBar());
