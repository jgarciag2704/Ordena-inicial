const state = {
  products: window.ORDENA.products || [],
  branches: window.ORDENA.branches || [],
  cart: window.ORDENA.cart || [],
  mode: 'pickup',
  branchId: null,
  selected: null,
  category: 'all',
};

const money = value => '$' + Number(value).toLocaleString('es-MX', { maximumFractionDigits: 0 });
const apiUrl = path => path + window.location.search;

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
    return `
      <button class="branch-card ${active ? 'active' : ''} ${isOpen ? '' : 'disabled'}" ${isOpen ? `onclick="selectBranch(${branch.id})"` : 'disabled'}>
        <span class="branch-top"><b>${escapeHtml(branch.nombre)}</b><span class="branch-status ${isOpen ? 'open' : 'closed'}">${isOpen ? 'Abierta' : 'Cerrada'}</span></span>
        <span class="branch-address">${escapeHtml(branch.direccion || '')}</span>
        <span class="branch-hours">Hoy: ${escapeHtml(hours)}</span>
      </button>
    `;
  }).join('') : '<p class="muted">Este negocio aún no tiene sucursales activas.</p>';
}

function selectBranch(id) {
  state.branchId = Number(id);
  renderBranches();
  const checkoutBranches = document.querySelector('#checkoutBranches');
  if (checkoutBranches) checkoutBranches.innerHTML = branchCardsHtml();
}

function selectCheckoutMode(mode) {
  state.mode = mode;
  document.querySelectorAll('#checkoutModes .mode').forEach(button => button.classList.toggle('active', button.dataset.mode === mode));
  document.querySelector('#addressBlock').style.display = mode === 'delivery' ? 'grid' : 'none';
  document.querySelector('#tableBlock').style.display = mode === 'mesa' ? 'grid' : 'none';
}

function renderCart(payload) {
  if (payload) state.cart = payload.items;
  const total = state.cart.reduce((sum, item) => sum + Number(item.total), 0);
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
  const branch = state.branches.find(item => Number(item.id) === Number(state.branchId));
  document.querySelector('#checkoutContent').innerHTML = `
    <button class="chip" onclick="closeAll()">Cerrar x</button>
    <h2>Revisa y finaliza tu pedido</h2>
    <p class="muted">Elige cómo quieres recibirlo y desde qué sucursal se preparará.</p>
    <div class="modes checkout-modes" id="checkoutModes">
      <button class="mode ${state.mode === 'pickup' ? 'active' : ''}" data-mode="pickup" onclick="selectCheckoutMode('pickup')" type="button"><strong>Recoger</strong><span>Pasas por tu pedido</span></button>
      <button class="mode ${state.mode === 'mesa' ? 'active' : ''}" data-mode="mesa" onclick="selectCheckoutMode('mesa')" type="button"><strong>En mesa</strong><span>Consumes aqui</span></button>
      <button class="mode ${state.mode === 'delivery' ? 'active' : ''}" data-mode="delivery" onclick="selectCheckoutMode('delivery')" type="button"><strong>A domicilio</strong><span>Pago contra entrega</span></button>
    </div>
    <h3>Sucursal</h3>
    <div class="branches-public checkout-branches" id="checkoutBranches">${branchCardsHtml()}</div>
    <label>Nombre<input id="name" placeholder="Tu nombre"></label>
    <label>Celular<input id="phone" inputmode="numeric" placeholder="10 dígitos"></label>
    <label id="addressBlock" style="display:${state.mode === 'delivery' ? 'grid' : 'none'}">Dirección y referencias<textarea id="address" placeholder="Calle, número, colonia y referencias"></textarea></label>
    <label id="tableBlock" style="display:${state.mode === 'mesa' ? 'grid' : 'none'}">Mesa<input id="table" placeholder="Ej. 4"></label>
    <button class="primary" style="width:100%" onclick="sendCode()">Verificar mi número</button>
  `;
  document.querySelector('#checkoutModal').classList.add('open');
}

async function sendCode() {
  if (!state.branchId) return alert('Selecciona una sucursal abierta para continuar.');
  const response = await post('/checkout/start', {
    mode: state.mode,
    branch_id: state.branchId,
    name: document.querySelector('#name').value,
    phone: document.querySelector('#phone').value,
    address: document.querySelector('#address')?.value || '',
    table: document.querySelector('#table')?.value || '',
  });
  if (response.error) return alert(response.error);

  document.querySelector('#checkoutContent').innerHTML = `
    <h2>Verifica tu número</h2>
    <p class="muted">En producción se enviará un código por WhatsApp o SMS. Para esta etapa usa <b>123456</b>.</p>
    <label>Código de 6 dígitos<input id="otp" inputmode="numeric" placeholder="123456"></label>
    <button class="primary" style="width:100%" onclick="confirmOrder()">Confirmar pedido</button>
  `;
}

async function confirmOrder() {
  const response = await post('/checkout/confirm', { otp: document.querySelector('#otp').value });
  if (response.error) return alert(response.error);
  state.cart = [];
  renderCart();
  document.querySelector('#checkoutContent').innerHTML = `
    <div class="tag">PEDIDO RECIBIDO</div>
    <h2>Gracias por ordenar</h2>
    <p>Tu folio es <b>${escapeHtml(response.order.folio)}</b>.</p>
    <div class="card"><b>${response.mode === 'delivery' ? 'Efectivo contra entrega' : 'Pago en sucursal'}</b><p>El restaurante confirmará tu pedido.</p></div>
    <button class="primary" style="width:100%" onclick="location.reload()">Listo</button>
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
