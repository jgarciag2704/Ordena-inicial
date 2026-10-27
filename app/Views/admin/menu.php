<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Menú · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .mq-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:16px 0}
        .mq-search{flex:1;min-width:220px;position:relative}
        .mq-search input{width:100%;padding:12px 16px 12px 42px;border:1px solid var(--line,#eadfd3);border-radius:14px;font-size:14px;background:#fff}
        .mq-search input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 15%,transparent)}
        .mq-search svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);opacity:.5}
        .mq-filters{display:flex;gap:6px;flex-wrap:wrap}
        .mq-filters button{padding:10px 16px;border-radius:999px;border:1px solid var(--line,#eadfd3);background:#fff;font-weight:800;font-size:13px;cursor:pointer;color:#57473d}
        .mq-filters button.active{background:var(--brand);border-color:var(--brand);color:#fff}
        .mq-category{margin:26px 0 10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .mq-category h2{margin:0;font-size:19px;letter-spacing:-.02em}
        .mq-category small{color:var(--muted,#716b67);font-weight:700;background:#f3e8df;border-radius:999px;padding:4px 10px}
        .mq-category .switch-wrap{margin-left:auto}
        .mq-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:14px}
        .mq-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:20px;overflow:hidden;box-shadow:0 12px 30px color-mix(in srgb,var(--brand) 7%,transparent);transition:.16s ease}
        .mq-card:hover{transform:translateY(-2px);box-shadow:0 20px 44px color-mix(in srgb,var(--brand) 13%,transparent)}
        .mq-card.is-off{opacity:.62}
        .mq-card-photo{height:130px;background:linear-gradient(135deg,#f4b476,var(--brand));overflow:hidden}
        .mq-card-photo img{width:100%;height:100%;object-fit:cover;display:block}
        .mq-card-body{display:grid;gap:5px;padding:14px 16px;flex:1}
        .mq-card-body b{font-size:15.5px}
        .mq-card-body p{margin:0;font-size:12.5px;color:#716b67;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.8em}
        .mq-card-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:0 16px 14px}
        .mq-price{font-weight:950;font-size:17px}
        .mq-extras-pill{font-size:11px;font-weight:850;background:color-mix(in srgb,var(--brand) 12%,white);color:var(--dark,#2b201b);border-radius:999px;padding:4px 9px}
        .mq-promo-ribbon{position:absolute;top:10px;left:10px;background:var(--brand);color:#fff;font-size:10.5px;font-weight:950;letter-spacing:.05em;text-transform:uppercase;border-radius:999px;padding:5px 10px;box-shadow:0 8px 16px color-mix(in srgb,var(--brand) 45%,transparent)}
        .mq-card-actions{display:flex;align-items:center;gap:6px;padding:10px 14px;border-top:1px solid #f0e7de;background:color-mix(in srgb,var(--brand) 4%,#fff)}
        .mq-card-actions button.mq-edit{flex:1;border:1px solid var(--line,#eadfd3);background:#fff;color:var(--brand);border-radius:12px;padding:9px 10px;font-weight:850;font-size:13px;cursor:pointer}
        .mq-card-actions button.mq-edit:hover{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 7%,white)}
        .switch-wrap{display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:800;color:#57473d;cursor:pointer}
        .switch-wrap input{position:absolute;opacity:0;width:0;height:0}
        .sw{position:relative;display:block;width:40px;height:23px;border-radius:999px;background:#d8d2cc;transition:.18s;flex:0 0 auto}
        .sw:after{content:"";position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.25);transition:.18s}
        .switch-wrap input:checked+.sw{background:var(--brand)}
        .switch-wrap input:checked+.sw:after{left:20px}
        .switch-wrap.promo input:checked+.sw{background:#eab308}
        .mq-empty{padding:46px;text-align:center;color:#716b67;background:#fff;border:1px dashed color-mix(in srgb,var(--brand) 30%,#eadfd3);border-radius:22px}
        .mq-drawer-backdrop{position:fixed;inset:0;background:rgba(20,16,14,.45);display:none;z-index:6}
        .mq-drawer-backdrop.open{display:block}
        .mq-drawer{position:fixed;top:0;right:0;height:100%;width:min(520px,100%);background:#fff;z-index:7;transform:translateX(102%);transition:transform .22s ease;overflow-y:auto;padding:22px;box-shadow:-18px 0 60px rgba(0,0,0,.18)}
        .mq-drawer.open{transform:none}
        .mq-drawer-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px}
        .mq-drawer-head h2{margin:0;font-size:20px}
        .mq-drawer-section{margin-top:22px}
        .mq-drawer-section h3{margin:0 0 10px;font-size:15px;display:flex;align-items:center;gap:8px}
        .mq-group{border:1px solid #eee2d6;border-radius:16px;padding:12px 14px;margin-bottom:10px;background:color-mix(in srgb,var(--brand) 3%,#fff)}
        .mq-group-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap}
        .mq-group-head b{font-size:14px}
        .mini-pill{font-size:10.5px;font-weight:900;background:#f3e8df;color:#57473d;border-radius:999px;padding:3px 9px}
        .mini-pill.required{background:#fee2e2;color:#991b1b}
        .mq-value{display:flex;gap:6px;align-items:center;margin:6px 0}
        .mq-value input[type=text]{flex:1;min-width:0}
        .mq-value input[type=number]{width:96px}
        .mq-value form{display:flex;gap:6px;flex:1;align-items:center;margin:0}
        .mq-value .danger-button{border:0;border-radius:10px;padding:8px 10px;background:#fff4f0;color:#b42318;font-weight:800;cursor:pointer;font-size:12px}
        .mq-drawer input[type=text],.mq-drawer input[type=number],.mq-drawer select,.mq-drawer textarea{width:100%;padding:11px 12px;border:1px solid #e2d8cd;border-radius:12px;font:inherit;font-size:14px;background:#fff}
        .mq-drawer label{display:grid;gap:6px;font-weight:800;font-size:12.5px;color:#57473d;margin-bottom:12px}
        .mq-toast-zone{position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:12}
        .mq-toast{background:#171514;color:#fff;padding:12px 16px;border-radius:14px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px rgba(0,0,0,.3);animation:mqIn .18s ease}
        .mq-toast.error{background:#b42318}
        @keyframes mqIn{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
        .mq-cat-bar{display:flex;gap:8px;overflow-x:auto;padding:6px 2px 12px;scrollbar-width:thin}
        .mq-cat-chip{white-space:nowrap;border:1px solid var(--line,#eadfd3);background:#fff;border-radius:999px;padding:9px 15px;font-weight:850;font-size:13px;cursor:pointer;color:#57473d;display:inline-flex;gap:7px;align-items:center}
        .mq-cat-chip.active{background:var(--brand);border-color:var(--brand);color:#fff}
        .mq-cat-chip small{opacity:.75;font-weight:800}
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <header class="crud-header">
        <div>
            <h1>Menú</h1>
            <p>Gestiona productos, fotos, categorías y extras de <?= e($business['nombre']) ?>.</p>
        </div>
        <div class="crud-actions">
            <button class="chip" onclick="openCatManager()">⚙️ Categorías</button>
            <button class="primary" onclick="document.querySelector('#newProduct').showModal()">+ Nuevo producto</button>
        </div>
    </header>

    <div class="mq-toolbar">
        <div class="mq-search">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"></circle><path d="m21 21-4.3-4.3"></path></svg>
            <input id="mqSearch" type="search" placeholder="Buscar producto por nombre…" autocomplete="off">
        </div>
        <div class="mq-filters" role="group" aria-label="Filtros de estado">
            <button class="active" data-filter="all">Todos</button>
            <button data-filter="promo">⭐ Promociones</button>
            <button data-filter="off">Ocultos</button>
        </div>
    </div>

    <div class="mq-cat-bar" id="mqCatBar">
        <button class="mq-cat-chip active" data-cat="all">Todas <small><?= count($products) ?></small></button>
        <?php foreach ($categories as $category): ?>
            <?php if ((int) $category['activo'] !== 1) { continue; } ?>
            <button class="mq-cat-chip" data-cat="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?> <small><?= (int) $category['activos'] ?></small></button>
        <?php endforeach; ?>
    </div>

    <?php foreach ($categories as $category): ?>
        <?php
        $catProducts = array_values(array_filter($products, fn (array $p): bool => (int) $p['categoria_id'] === (int) $category['id']));
        if (!$catProducts && (int) $category['activo'] !== 1) { continue; }
        ?>
        <section class="mq-cat-section" data-cat="<?= (int) $category['id'] ?>">
            <div class="mq-category">
                <h2><?= e($category['nombre']) ?></h2>
                <small><?= count($catProducts) ?> producto<?= count($catProducts) === 1 ? '' : 's' ?></small>
                <?php if ((int) $category['activo'] !== 1): ?><span class="soft-badge" style="background:#fee2e2;color:#991b1b">Oculta en la tienda</span><?php endif; ?>
                <label class="switch-wrap" title="Visible en la tienda">
                    <input type="checkbox" class="mq-cat-toggle" data-id="<?= (int) $category['id'] ?>" <?= (int) $category['activo'] === 1 ? 'checked' : '' ?>>
                    <span class="sw"></span> Visible
                </label>
            </div>

            <?php if ($catProducts): ?>
                <div class="mq-grid">
                    <?php foreach ($catProducts as $product): ?>
                        <?php $productOptions = $optionsByProduct[(int) $product['id']] ?? []; ?>
                        <article class="mq-card <?= (int) $product['disponible'] === 1 ? '' : 'is-off' ?>"
                                 data-name="<?= e(mb_strtolower($product['nombre'])) ?>"
                                 data-cat="<?= (int) $product['categoria_id'] ?>"
                                 data-state="<?= (int) $product['disponible'] === 1 ? 'on' : 'off' ?>"
                                 data-promo="<?= (int) ($product['destacado'] ?? 0) === 1 ? '1' : '0' ?>">
                            <?php if ((int) ($product['destacado'] ?? 0) === 1): ?><span class="mq-promo-ribbon">⭐ Promoción</span><?php endif; ?>
                            <div class="mq-card-photo">
                                <?php if ($product['imagen_thumb']): ?>
                                    <img src="<?= e($product['imagen_thumb']) ?>" alt="<?= e($product['nombre']) ?>" loading="lazy">
                                <?php endif; ?>
                            </div>
                            <div class="mq-card-body">
                                <b><?= e($product['nombre']) ?></b>
                                <p><?= e($product['descripcion'] ?: 'Sin descripción todavía.') ?></p>
                            </div>
                            <div class="mq-card-foot">
                                <span class="mq-price"><?= money($product['precio']) ?></span>
                                <span class="mq-extras-pill"><?= count($productOptions) ?> extra<?= count($productOptions) === 1 ? '' : 's' ?></span>
                            </div>
                            <div class="mq-card-actions">
                                <label class="switch-wrap" title="Visible en la tienda">
                                    <input type="checkbox" class="mq-toggle" data-id="<?= (int) $product['id'] ?>" <?= (int) $product['disponible'] === 1 ? 'checked' : '' ?>>
                                    <span class="sw"></span>
                                </label>
                                <label class="switch-wrap promo" title="Marcar como promoción">
                                    <input type="checkbox" class="mq-feature-toggle" data-id="<?= (int) $product['id'] ?>" <?= (int) ($product['destacado'] ?? 0) === 1 ? 'checked' : '' ?>>
                                    <span class="sw"></span>⭐
                                </label>
                                <button type="button" class="mq-edit" onclick="openEditor(<?= (int) $product['id'] ?>)">Editar</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="mq-empty">No hay productos en esta categoría todavía.</p>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if (!$products): ?>
        <div class="mq-empty">Tu menú está vacío. Crea tu primera categoría y producto para empezar a recibir pedidos.</div>
    <?php endif; ?>
</main>

<div class="mq-drawer-backdrop" id="mqBackdrop" onclick="closeEditor()"></div>
<aside class="mq-drawer" id="mqDrawer" aria-label="Editar producto">
    <div id="mqDrawerContent"></div>
</aside>

<dialog id="newProduct" class="crud-modal">
    <form class="card" method="post" enctype="multipart/form-data" action="/admin/menu/products<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
        <div class="modal-head">
            <div><h2>Nuevo producto</h2><p class="muted">Luego podrás editarlo y configurar sus extras.</p></div>
            <button type="button" class="chip" onclick="document.querySelector('#newProduct').close()">Cerrar</button>
        </div>
        <label>Categoría
            <select name="categoria_id" required>
                <option value="">Selecciona</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"><?= e($category['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Nombre<input name="nombre" required placeholder="Ej. Hamburguesa BBQ"></label>
        <label>Descripción<textarea name="descripcion" placeholder="Ingredientes o descripción corta"></textarea></label>
        <label>Precio<input name="precio" type="number" min="1" step="0.01" required placeholder="Ej. 120"></label>
        <label>Foto<input name="foto" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <label class="switch-wrap" style="margin-bottom:14px"><input type="checkbox" name="destacado" value="1"><span class="sw"></span> Marcar como ⭐ Promoción</label>
        <button class="primary" style="width:100%">Crear producto</button>
    </form>
</dialog>

<dialog id="catManager" class="crud-modal">
    <div class="card">
        <div class="modal-head">
            <div><h2>Categorías</h2><p class="muted">Oculta las que ya no uses; no se mostrarán en la tienda.</p></div>
            <button type="button" class="chip" onclick="document.querySelector('#catManager').close()">Cerrar</button>
        </div>
        <?php foreach ($categories as $category): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f0e7de">
                <b><?= e($category['nombre']) ?></b>
                <span class="muted"><?= (int) $category['activos'] ?> activos</span>
                <label class="switch-wrap"><input type="checkbox" class="mq-cat-toggle" data-id="<?= (int) $category['id'] ?>" <?= (int) $category['activo'] === 1 ? 'checked' : '' ?>><span class="sw"></span></label>
            </div>
        <?php endforeach; ?>
        <form method="post" action="/admin/menu/categories<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" style="margin-top:14px">
            <div class="tool-inline">
                <input name="nombre" required placeholder="Nueva categoría (Ej. Bebidas)">
                <button class="primary small">Agregar</button>
            </div>
        </form>
    </div>
</dialog>

<div class="mq-toast-zone" id="mqToasts"></div>

<script type="application/json" id="mqProductsData"><?= json_encode(array_map(static fn (array $p): array => [
    'id' => (int) $p['id'],
    'nombre' => (string) $p['nombre'],
    'descripcion' => (string) ($p['descripcion'] ?? ''),
    'precio' => (string) $p['precio'],
    'categoria_id' => (int) $p['categoria_id'],
    'destacado' => (int) ($p['destacado'] ?? 0),
    'disponible' => (int) $p['disponible'],
], $products), JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="mqOptionsData"><?= json_encode($optionsByProduct, JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/json" id="mqCategoriesData"><?= json_encode(array_map(static fn (array $c): array => ['id' => (int) $c['id'], 'nombre' => (string) $c['nombre']], $categories), JSON_UNESCAPED_UNICODE) ?></script>
<script>
const MQ_TENANT = <?= json_encode(isset($_GET['tenant']) ? (string) $_GET['tenant'] : '') ?>;
const mqProducts = JSON.parse(document.getElementById('mqProductsData').textContent);
const mqOptions = JSON.parse(document.getElementById('mqOptionsData').textContent);
const mqCategories = JSON.parse(document.getElementById('mqCategoriesData').textContent);
const mqTenantQs = MQ_TENANT ? '?tenant=' + encodeURIComponent(MQ_TENANT) : '';
let mqFilter = 'all';
let mqCategory = 'all';
let mqQuery = '';

function mqToast(message, ok = true) {
    const zone = document.getElementById('mqToasts');
    const el = document.createElement('div');
    el.className = 'mq-toast' + (ok ? '' : ' error');
    el.textContent = message;
    zone.appendChild(el);
    setTimeout(() => el.remove(), 3200);
}

function mqEsc(value) {
    return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function applyFilters() {
    document.querySelectorAll('.mq-cat-section').forEach(section => {
        const sectionCat = section.dataset.cat;
        let anyVisible = false;
        section.querySelectorAll('.mq-card').forEach(card => {
            const stateOk = mqFilter === 'all' || (mqFilter === 'promo' ? card.dataset.promo === '1' : mqFilter === 'off' ? card.dataset.state === 'off' : true);
            const textOk = !mqQuery || card.dataset.name.includes(mqQuery);
            const show = stateOk && textOk;
            card.style.display = show ? '' : 'none';
            if (show) anyVisible = true;
        });
        const catOk = mqCategory === 'all' || sectionCat === mqCategory;
        section.style.display = catOk && anyVisible ? '' : 'none';
    });
}

document.getElementById('mqSearch').addEventListener('input', event => {
    mqQuery = event.target.value.trim().toLowerCase();
    applyFilters();
});

document.querySelectorAll('.mq-filters button').forEach(button => button.addEventListener('click', () => {
    document.querySelectorAll('.mq-filters button').forEach(b => b.classList.remove('active'));
    button.classList.add('active');
    mqFilter = button.dataset.filter;
    applyFilters();
}));

document.querySelectorAll('.mq-cat-chip').forEach(chip => chip.addEventListener('click', () => {
    document.querySelectorAll('.mq-cat-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    mqCategory = chip.dataset.cat;
    applyFilters();
}));

async function mqSwitch(url, id, event) {
    event.preventDefault();
    const input = event.target;
    const previous = input.checked;
    try {
        const body = new URLSearchParams({ id: String(id), ajax: '1' });
        if (MQ_TENANT) body.set('tenant', MQ_TENANT);
        const response = await fetch(url + mqTenantQs, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
        const data = await response.json();
        if (!data.ok) throw new Error(data.message || 'No se pudo actualizar.');
        input.checked = !previous;
        mqToast(data.message || 'Listo.');
        const card = input.closest('.mq-card');
        if (card && data.product) {
            const item = mqProducts.find(p => p.id === Number(input.dataset.id));
            if (item) Object.assign(item, { disponible: Number(data.product.disponible), destacado: Number(data.product.destacado) });
            card.dataset.state = data.product.disponible === 1 ? 'on' : 'off';
            card.dataset.promo = data.product.destacado === 1 ? '1' : '0';
            card.classList.toggle('is-off', data.product.disponible !== 1);
            const ribbon = card.querySelector('.mq-promo-ribbon');
            const featuredNow = card.dataset.promo === '1';
            if (featuredNow && !ribbon) {
                const flag = document.createElement('span');
                flag.className = 'mq-promo-ribbon';
                flag.textContent = '⭐ Promoción';
                card.prepend(flag);
            } else if (!featuredNow && ribbon) {
                ribbon.remove();
            }
        }
        if (input.classList.contains('mq-cat-toggle')) {
            document.querySelectorAll(`.mq-cat-toggle[data-id="${input.dataset.id}"]`).forEach(other => { other.checked = input.checked; });
        }
    } catch (error) {
        input.checked = previous;
        mqToast(error.message || 'Ocurrió un error.', false);
    }
    applyFilters();
}

document.querySelectorAll('.mq-toggle').forEach(input => input.addEventListener('change', event => mqSwitch('/admin/menu/products/toggle', input.dataset.id, event)));
document.querySelectorAll('.mq-feature-toggle').forEach(input => input.addEventListener('change', event => mqSwitch('/admin/menu/products/feature', input.dataset.id, event)));
document.querySelectorAll('.mq-cat-toggle').forEach(input => input.addEventListener('change', event => mqSwitch('/admin/menu/categories/toggle', input.dataset.id, event)));

function openEditor(id) {
    const product = mqProducts.find(item => item.id === Number(id));
    if (!product) return;
    const options = mqOptions[id] || [];
    const catOptions = mqCategories.map(c => `<option value="${c.id}" ${c.id === product.categoria_id ? 'selected' : ''}>${mqEsc(c.nombre)}</option>`).join('');
    const optionLabels = { multiple: 'Múltiple', unica: 'Única', texto: 'Texto' };

    const groupsHtml = options.map(group => `
        <div class="mq-group">
            <div class="mq-group-head">
                <b>${mqEsc(group.nombre)}</b>
                <span>
                    <span class="mini-pill">${optionLabels[group.tipo] || group.tipo}</span>
                    ${group.requerida ? '<span class="mini-pill required">Obligatoria</span>' : ''}
                </span>
            </div>
            ${group.tipo !== 'texto' ? `
                <div>${(group.valores || []).map(value => `
                    <div class="mq-value">
                        <form method="post" action="/admin/menu/option-values/update${mqTenantQs}">
                            <input type="hidden" name="id" value="${value.id}"><input type="hidden" name="editar" value="${product.id}">
                            <input type="text" name="nombre" required value="${mqEsc(value.nombre)}">
                            <input type="number" name="precio_extra" min="0" step="0.01" required value="${value.precio_extra}" title="Precio extra">
                            <button class="chip small" type="submit">Guardar</button>
                        </form>
                        <form method="post" action="/admin/menu/option-values/delete${mqTenantQs}" onsubmit="return confirm('¿Eliminar este extra?')">
                            <input type="hidden" name="id" value="${value.id}"><input type="hidden" name="editar" value="${product.id}">
                            <button class="danger-button" type="submit">✕</button>
                        </form>
                    </div>`).join('') || '<p class="muted" style="font-size:12px;margin:4px 0">Sin opciones todavía.</p>'}
                </div>
                <form method="post" action="/admin/menu/option-values${mqTenantQs}" style="display:flex;gap:6px;margin-top:8px">
                    <input type="hidden" name="producto_opcion_id" value="${group.id}"><input type="hidden" name="editar" value="${product.id}">
                    <input type="text" name="nombre" required placeholder="Nuevo extra" style="flex:1;padding:8px 10px;border:1px solid #e2d8cd;border-radius:10px">
                    <input type="number" name="precio_extra" min="0" step="0.01" required placeholder="$" value="0" style="width:80px;padding:8px 10px;border:1px solid #e2d8cd;border-radius:10px">
                    <button class="chip small" type="submit">+ Agregar</button>
                </form>` : '<p class="muted" style="font-size:12px;margin:0">El cliente escribe aquí sus indicaciones libres.</p>'}
        </div>`).join('');

    document.getElementById('mqDrawerContent').innerHTML = `
        <div class="mq-drawer-head">
            <h2>Editar producto</h2>
            <button class="chip" onclick="closeEditor()">Cerrar ✕</button>
        </div>
        <form method="post" enctype="multipart/form-data" action="/admin/menu/products/update${mqTenantQs}" id="mqEditForm">
            <input type="hidden" name="id" value="${product.id}">
            <input type="hidden" name="editar" value="${product.id}">
            <label>Nombre<input type="text" name="nombre" required value="${mqEsc(product.nombre)}"></label>
            <label>Categoría
                <select name="categoria_id" required>${catOptions}</select>
            </label>
            <label>Precio<input type="number" name="precio" min="1" step="0.01" required value="${mqEsc(product.precio)}"></label>
            <label>Descripción<textarea name="descripcion" rows="3">${mqEsc(product.descripcion)}</textarea></label>
            <label>Foto nueva<input name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                <small class="muted" style="font-weight:600">Déjala vacía para conservar la foto actual.</small>
            </label>
            <label class="switch-wrap" style="margin-bottom:16px"><input type="checkbox" name="destacado" value="1" ${product.destacado === 1 ? 'checked' : ''}><span class="sw"></span> ⭐ Promoción en la tienda</label>
            <button class="primary" style="width:100%">Guardar cambios</button>
        </form>
        <div class="mq-drawer-section">
            <h3>Extras y opciones <span class="muted" style="font-weight:600;font-size:12px">(${options.length})</span></h3>
            ${groupsHtml || '<p class="muted" style="font-size:13px">Sin grupos todavía.</p>'}
            <form method="post" action="/admin/menu/options${mqTenantQs}" class="mq-group" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">
                <input type="hidden" name="producto_id" value="${product.id}"><input type="hidden" name="editar" value="${product.id}">
                <input type="text" name="nombre" required placeholder="Nuevo grupo: Salsas, Bebida…" style="flex:1;min-width:150px;padding:9px 11px;border:1px solid #e2d8cd;border-radius:10px">
                <select name="tipo" style="padding:9px;border:1px solid #e2d8cd;border-radius:10px">
                    <option value="multiple">Múltiple</option>
                    <option value="unica">Única</option>
                    <option value="texto">Texto libre</option>
                </select>
                <label class="switch-wrap"><input type="checkbox" name="requerida" value="1"><span class="sw"></span></label>
                <button class="primary small" type="submit">+ Grupo</button>
            </form>
            <p class="muted" style="font-size:11.5px">Guardar grupos o extras recarga la página; tu producto se reabre aquí automáticamente.</p>
        </div>`;
    document.getElementById('mqDrawer').classList.add('open');
    document.getElementById('mqBackdrop').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeEditor() {
    document.getElementById('mqDrawer').classList.remove('open');
    document.getElementById('mqBackdrop').classList.remove('open');
    document.body.style.overflow = '';
}

function openCatManager() {
    document.querySelector('#catManager').showModal();
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeEditor();
});

const mqOpenParam = new URLSearchParams(location.search).get('editar');
if (mqOpenParam) {
    openEditor(Number(mqOpenParam));
    history.replaceState(null, '', location.pathname + (MQ_TENANT ? '?tenant=' + encodeURIComponent(MQ_TENANT) : ''));
}
<?php if ($success): ?>mqToast(<?= json_encode($success) ?>, true);<?php endif; ?>
<?php if ($error): ?>mqToast(<?= json_encode($error) ?>, false);<?php endif; ?>
</script>
</body>
</html>
