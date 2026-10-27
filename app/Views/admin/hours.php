<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Horarios · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .hr-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:16px 0}
        .hr-toolbar .hr-branch{flex:1;min-width:220px}
        .hr-toolbar label{font-size:11px;font-weight:900;color:#716b67;text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px}
        .hr-toolbar select{width:100%;padding:12px 14px;border:1px solid var(--line,#eadfd3);border-radius:14px;background:#fff;font:inherit;font-weight:750;font-size:14px;color:#57473d;cursor:pointer}
        .hr-toolbar select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 15%,transparent)}
        .hr-toolbar .chip{align-self:flex-end}
        .hr-summary{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
        .hr-chip{display:inline-flex;align-items:center;gap:8px;background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:999px;padding:8px 15px;font-size:12.5px;font-weight:800;color:#57473d;box-shadow:0 8px 20px color-mix(in srgb,var(--brand) 5%,transparent)}
        .hr-chip .dot{width:8px;height:8px;border-radius:50%;background:#16a34a}
        .hr-chip .dot.closed{background:#dc2626}
        .hr-grid{display:grid;gap:10px}
        .hr-row{display:grid;grid-template-columns:150px 1fr auto;gap:14px;align-items:center;background:#fff;border:1px solid color-mix(in srgb,var(--brand) 10%,#eadfd3);border-radius:18px;padding:14px 18px;box-shadow:0 10px 24px color-mix(in srgb,var(--brand) 5%,transparent);transition:.14s ease}
        .hr-row.today{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 4%,#fff)}
        .hr-row.is-closed{background:#fafafa;opacity:.75}
        .hr-day{display:flex;flex-direction:column;gap:2px}
        .hr-day b{font-size:15px}
        .hr-day small{font-size:11px;font-weight:800;color:#94867c;text-transform:uppercase;letter-spacing:.04em}
        .hr-day .today-tag{color:var(--brand)}
        .hr-times{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .hr-time{display:grid;gap:3px}
        .hr-time span{font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:#94867c}
        .hr-time input{padding:9px 12px;border:1px solid #e2d8cd;border-radius:11px;font:inherit;font-size:14px;font-weight:750;background:#fff}
        .hr-time input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 14%,transparent)}
        .hr-row.is-closed .hr-time input{background:#f3f0ec;color:#b3a89e;border-style:dashed}
        .hr-row.is-closed .hr-times .hr-time{opacity:.55}
        .hr-arrow{color:#c9bdb2;font-weight:900;margin-top:12px}
        .hr-open{display:inline-flex;align-items:center;gap:6px;margin-top:12px;font-size:12px;font-weight:800;color:#166534;background:#e8fbf1;border-radius:999px;padding:4px 10px}
        .hr-open.closed{color:#64748b;background:#f1f3f6}
        .hr-row-actions{display:flex;align-items:center;gap:12px;margin-top:2px}
        .switch-wrap{display:inline-flex;align-items:center;gap:7px;font-size:12px;font-weight:800;color:#57473d;cursor:pointer}
        .switch-wrap input{position:absolute;opacity:0;width:0;height:0}
        .sw{position:relative;display:block;width:40px;height:23px;border-radius:999px;background:#d8d2cc;transition:.18s;flex:0 0 auto}
        .sw:after{content:"";position:absolute;top:3px;left:3px;width:17px;height:17px;border-radius:50%;background:#fff;box-shadow:0 2px 5px rgba(0,0,0,.25);transition:.18s}
        .switch-wrap input:checked+.sw{background:var(--brand)}
        .switch-wrap input:checked+.sw:after{left:20px}
        .hr-copy-btn{border:1px solid var(--line,#eadfd3);background:#fff;color:var(--brand);border-radius:11px;padding:8px 11px;font-weight:850;font-size:12px;cursor:pointer;white-space:nowrap}
        .hr-copy-btn:hover{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 7%,white)}
        .hr-quick{display:flex;gap:8px;flex-wrap:wrap;margin:6px 0 14px}
        .hr-quick button{border:1px dashed color-mix(in srgb,var(--brand) 40%,#eadfd3);background:#fff;color:var(--brand);border-radius:999px;padding:9px 15px;font-weight:850;font-size:12.5px;cursor:pointer}
        .hr-quick button:hover{border-style:solid;background:color-mix(in srgb,var(--brand) 6%,white)}
        .hr-savebar{position:sticky;bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:14px;background:#171514;color:#fff;border-radius:18px;padding:12px 16px 12px 20px;margin-top:18px;box-shadow:0 20px 50px rgba(0,0,0,.28);transform:translateY(120%);transition:transform .22s ease}
        .hr-savebar.visible{transform:none}
        .hr-savebar span{font-size:13.5px;font-weight:750}
        .hr-savebar .primary{border-radius:12px}
        .hr-note{font-size:12.5px;color:#94867c;margin:4px 0 14px;font-weight:650}
        .hr-toast-zone{position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:12}
        .hr-toast{background:#171514;color:#fff;padding:12px 16px;border-radius:14px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px rgba(0,0,0,.3);animation:hrIn .18s ease}
        .hr-toast.error{background:#b42318}
        @keyframes hrIn{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
        @media (max-width:720px){.hr-row{grid-template-columns:1fr;gap:10px}.hr-row-actions{justify-content:space-between}}
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <header class="crud-header">
        <div><h1>Horarios</h1><p>Define los horarios disponibles por sucursal.</p></div>
        <div class="crud-actions">
            <a class="chip" style="text-decoration:none" href="/admin/branches<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">🏬 Sucursales</a>
        </div>
    </header>

    <?php if (!$branches): ?>
        <p class="alert error">Primero crea una sucursal para poder configurar sus horarios.</p>
    <?php else: ?>
        <div class="hr-toolbar">
            <form method="get" action="/admin/hours" class="hr-branch">
                <label>Sucursal</label>
                <div style="display:flex;gap:8px">
                    <select name="branch_id" onchange="this.form.submit()">
                        <?php foreach ($branches as $branch): ?>
                            <option value="<?= (int) $branch['id'] ?>" <?= (int) $branch['id'] === (int) $branchId ? 'selected' : '' ?>><?= e($branch['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($_GET['tenant'])): ?><input type="hidden" name="tenant" value="<?= e((string) $_GET['tenant']) ?>"><?php endif; ?>
                </div>
            </form>
        </div>

        <div class="hr-summary" id="hrSummary"></div>
        <p class="hr-note">El horario define en qué horarios pueden hacer pedidos. Si cierras un día, los clientes no podrán ordenar en esa sucursal ese día.</p>

        <div class="hr-quick">
            <button type="button" onclick="applyPattern('week')">📋 Misma apertura en lunes a viernes</button>
            <button type="button" onclick="applyPattern('all')">🗓️ Aplicar el mismo horario todos los días</button>
            <button type="button" onclick="applyPattern('weekend')">🎉 Igual que hoy para finde (sáb y dom)</button>
        </div>

        <form method="post" action="/admin/hours<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" id="hrForm">
            <input type="hidden" name="branch_id" value="<?= (int) $branchId ?>">
            <div class="hr-grid">
                <?php foreach ($hours as $hour): ?>
                    <?php $dayNum = (int) $hour['dia_semana']; ?>
                    <div class="hr-row <?= (int) $hour['cerrado'] === 1 ? 'is-closed' : '' ?>" data-day="<?= $dayNum ?>">
                        <div class="hr-day">
                            <b><?= e($hour['dia_nombre']) ?></b>
                            <small class="today-tag" style="display:none">Hoy</small>
                        </div>
                        <div class="hr-times">
                            <input type="hidden" class="hr-closed-value" name="hours[<?= $dayNum ?>][cerrado]" value="<?= (int) $hour['cerrado'] === 1 ? '1' : '' ?>">
                            <label class="hr-time"><span>Abre</span>
                                <input type="time" name="hours[<?= $dayNum ?>][abre]" value="<?= e(substr((string) $hour['abre'], 0, 5)) ?>">
                            </label>
                            <span class="hr-arrow">→</span>
                            <label class="hr-time"><span>Cierra</span>
                                <input type="time" name="hours[<?= $dayNum ?>][cierra]" value="<?= e(substr((string) $hour['cierra'], 0, 5)) ?>">
                            </label>
                            <span class="hr-open <?= (int) $hour['cerrado'] === 1 ? 'closed' : '' ?>" data-open-pill>
                                <?= (int) $hour['cerrado'] === 1 ? 'Cerrado' : 'Abierto' ?> <em style="font-style:normal" data-open-range><?= $hour['abre'] !== null && $hour['cierra'] !== null ? e(substr((string) $hour['abre'], 0, 5) . ' – ' . substr((string) $hour['cierra'], 0, 5)) : '' ?></em>
                            </span>
                        </div>
                        <div class="hr-row-actions">
                            <button type="button" class="hr-copy-btn" onclick="copyDay(<?= $dayNum ?>)" title="Copiar este horario y pegarlo en otros días">⧉ Copiar</button>
                            <label class="switch-wrap" title="¿Abre este día?">
                                <input type="checkbox" class="hr-closed-toggle" data-day="<?= $dayNum ?>" <?= (int) $hour['cerrado'] !== 1 ? 'checked' : '' ?>>
                                <span class="sw"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hr-savebar" id="hrSavebar">
                <span>⚠️ Tienes cambios sin guardar</span>
                <button class="primary" type="submit">Guardar horarios</button>
            </div>
        </form>
    <?php endif; ?>
</main>

<div class="hr-toast-zone" id="hrToasts"></div>

<script>
let copiedDay = null;
let dirty = false;

function hrToast(message, ok = true) {
    const zone = document.getElementById('hrToasts');
    const el = document.createElement('div');
    el.className = 'hr-toast' + (ok ? '' : ' error');
    el.textContent = message;
    zone.appendChild(el);
    setTimeout(() => el.remove(), 3200);
}

function rowInputs(row) {
    return {
        abre: row.querySelector('input[name$="[abre]"]'),
        cierra: row.querySelector('input[name$="[cierra]"]'),
        toggle: row.querySelector('.hr-closed-toggle'),
        hidden: row.querySelector('.hr-closed-value'),
    };
}

function refreshRow(row) {
    const { abre, cierra, toggle, hidden } = rowInputs(row);
    const open = toggle.checked;
    hidden.value = open ? '' : '1';
    row.classList.toggle('is-closed', !open);
    const pill = row.querySelector('[data-open-pill]');
    const range = row.querySelector('[data-open-range]');
    pill.classList.toggle('closed', !open);
    pill.firstChild.textContent = open ? 'Abierto ' : 'Cerrado ';
    range.textContent = open && abre.value && cierra.value ? abre.value + ' – ' + cierra.value : '';
    renderSummary();
}

function renderSummary() {
    const zone = document.getElementById('hrSummary');
    zone.innerHTML = [...document.querySelectorAll('.hr-row')].map(row => {
        const open = row.querySelector('.hr-closed-toggle').checked;
        const { abre, cierra } = rowInputs(row);
        const name = row.querySelector('.hr-day b').textContent;
        return '<span class="hr-chip"><span class="dot ' + (open ? '' : 'closed') + '"></span>' + name + ' · ' + (open && abre.value ? abre.value + '–' + (cierra.value || '…') : 'cerrado') + '</span>';
    }).join('');
}

function markDirty() {
    dirty = true;
    document.getElementById('hrSavebar').classList.add('visible');
}

document.querySelectorAll('.hr-closed-toggle').forEach(toggle => {
    toggle.addEventListener('change', () => { refreshRow(toggle.closest('.hr-row')); markDirty(); });
});
document.querySelectorAll('.hr-times input[type=time]').forEach(input => {
    input.addEventListener('input', () => { refreshRow(input.closest('.hr-row')); markDirty(); });
});

function dayNumber(name) {
    return document.querySelector(`.hr-row[data-day="${name}"] .hr-closed-toggle`).closest('.hr-row');
}

function copyDay(num) {
    const row = dayNumber(num);
    const { abre, cierra, toggle } = rowInputs(row);
    copiedDay = { abre: abre.value, cierra: cierra.value, open: toggle.checked };
    hrToast('Horario de ' + row.querySelector('.hr-day b').textContent + ' copiado. Úsalo en los botones de abajo o pega en cada día.');
}

document.querySelectorAll('.hr-row').forEach(row => {
    row.querySelector('.hr-copy-btn').addEventListener('contextmenu', event => {
        event.preventDefault();
        if (!copiedDay) return;
        applyToRow(row, copiedDay);
    });
});

function applyToRow(row, value) {
    const { abre, cierra, toggle } = rowInputs(row);
    toggle.checked = value.open;
    abre.value = value.abre || '';
    cierra.value = value.cierra || '';
    refreshRow(row);
    markDirty();
}

function applyPattern(kind) {
    if (!copiedDay) { hrToast('Primero copia el horario de un día con el botón ⧉ Copiar.', false); return; }
    const rows = [...document.querySelectorAll('.hr-row')];
    let targets = [];
    if (kind === 'week') targets = rows.filter(r => [1, 2, 3, 4, 5].includes(Number(r.dataset.day)));
    if (kind === 'all') targets = rows;
    if (kind === 'weekend') targets = rows.filter(r => [6, 7].includes(Number(r.dataset.day)));
    targets.forEach(row => applyToRow(row, copiedDay));
    hrToast(targets.length + ' día(s) actualizados con el horario copiado.');
}

function highlightToday() {
    const jsDay = new Date().getDay();
    const today = jsDay === 0 ? 7 : jsDay;
    const row = document.querySelector(`.hr-row[data-day="${today}"]`);
    if (!row) return;
    row.classList.add('today');
    row.querySelector('.today-tag').style.display = 'inline';
}

window.addEventListener('beforeunload', event => {
    if (dirty) { event.preventDefault(); event.returnValue = ''; }
});

document.getElementById('hrForm').addEventListener('submit', () => { dirty = false; });

highlightToday();
renderSummary();
</script>
<?php if ($success): ?><script>hrToast(<?= json_encode($success) ?>, true);</script><?php endif; ?>
<?php if ($error): ?><script>hrToast(<?= json_encode($error) ?>, false);</script><?php endif; ?>
</body>
</html>
