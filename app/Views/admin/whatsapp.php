<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>WhatsApp · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .wa-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.1fr);gap:18px;align-items:start;margin-top:8px}
        @media (max-width:980px){.wa-layout{grid-template-columns:1fr}}
        .wa-card{background:#fff;border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:22px;padding:22px;box-shadow:0 12px 30px color-mix(in srgb,var(--brand) 7%,transparent)}
        .wa-card h2{margin:0 0 4px;font-size:17px;display:flex;align-items:center;gap:8px}
        .wa-card .wa-sub{margin:0 0 16px;font-size:13px;color:#716b67}
        .wa-status-hero{display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;padding:10px 0 4px}
        .wa-avatar{position:relative;width:88px;height:88px;border-radius:50%;background:color-mix(in srgb,var(--brand) 10%,#fff);display:grid;place-items:center;margin-bottom:6px}
        .wa-avatar .wa-dot{position:absolute;right:4px;bottom:4px;width:22px;height:22px;border-radius:50%;border:4px solid #fff}
        .wa-dot.connected{background:#16a34a}
        .wa-dot.connecting{background:#eab308}
        .wa-dot.disconnected,.wa-dot.error{background:#dc2626}
        .wa-avatar svg{width:40px;height:40px;fill:var(--brand)}
        .wa-status-pill{font-size:13px;font-weight:900;border-radius:999px;padding:6px 16px;letter-spacing:.02em}
        .wa-status-pill.connected{background:#dcfce7;color:#166534}
        .wa-status-pill.connecting{background:#fef3c7;color:#92400e}
        .wa-status-pill.disconnected,.wa-status-pill.error{background:#fee2e2;color:#991b1b}
        .wa-phone-line{font-size:1.25rem;font-weight:850;margin-top:4px}
        .wa-since{font-size:12.5px;color:#94867c;font-weight:700}
        .wa-actions{display:grid;gap:10px;margin-top:18px}
        .wa-test-row{display:flex;gap:8px;flex-wrap:wrap}
        .wa-test-row input{flex:1;min-width:150px;padding:11px 14px;border:1px solid #e2d8cd;border-radius:12px;font:inherit;font-size:14px}
        .wa-test-row input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--brand) 14%,transparent)}
        .wa-test-note{font-size:11.5px;color:#94867c;margin:6px 2px 0}
        .wa-link-line{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 14px;background:color-mix(in srgb,var(--brand) 4%,#fff);border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:14px;font-size:13px;font-weight:700;color:#57473d;flex-wrap:wrap}
        .wa-link-line .grow{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
        .wa-link-line input{padding:8px 11px;border:1px solid #e2d8cd;border-radius:10px;font:inherit;font-size:13px;min-width:140px;flex:1}
        .wa-link-line button{padding:8px 14px}
        .wa-methods{display:flex;gap:8px;margin:4px 0 12px;flex-wrap:wrap}
        .wa-method-btn{flex:1;min-width:140px;border:2px solid #eee2d6;background:#fff;border-radius:14px;padding:11px;font-weight:850;font-size:13px;color:#57473d;cursor:pointer;transition:.14s}
        .wa-method-btn small{display:block;font-weight:600;font-size:11px;color:#94867c}
        .wa-method-btn.active{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 8%,#fff);color:var(--brand)}
        .wa-content{min-height:120px}
        .wa-qr-frame{display:grid;place-items:center;gap:10px}
        .wa-qr-frame img{width:min(260px,100%);border-radius:16px;border:6px solid #171514;background:#fff;box-shadow:0 16px 40px rgba(0,0,0,.14)}
        .wa-pairing-code{font-size:2rem;font-weight:900;letter-spacing:6px;padding:16px 22px;background:#f5f0ea;border:1px dashed #cbb9a8;border-radius:14px;font-family:ui-monospace,monospace;user-select:all;cursor:copy}
        .wa-steps{display:grid;gap:8px;margin-top:6px}
        .wa-step{display:flex;gap:10px;align-items:flex-start;font-size:12.5px;color:#57473d;font-weight:650}
        .wa-step i{flex:0 0 auto;width:22px;height:22px;border-radius:50%;background:color-mix(in srgb,var(--brand) 14%,white);color:var(--brand);font-style:normal;font-weight:900;display:grid;place-items:center;font-size:11.5px}
        .wa-poll-line{display:inline-flex;align-items:center;gap:8px;margin-top:14px;font-size:12.5px;font-weight:800;color:#94867c}
        .wa-poll-dot{width:9px;height:9px;border-radius:50%;background:#eab308;animation:waPulse 1.1s infinite}
        @keyframes waPulse{0%,100%{opacity:1}50%{opacity:.25}}
        .wa-alert{padding:11px 14px;border-radius:12px;font-size:13px;font-weight:700}
        .wa-alert.error{background:#fee2e2;color:#991b1b}
        dialog.crud-modal{max-width:480px;width:92%;padding:0;border:none;border-radius:18px}
        dialog.crud-modal .card{padding:0;border:none;box-shadow:none;border-radius:0}
        dialog.crud-modal .modal-head{padding:18px 22px;border-bottom:1px solid #eee}
        dialog.crud-modal .modal-head h2{margin:0;font-size:1.2rem}
        dialog.crud-modal .modal-head p{margin:0;font-size:.88rem}
        /* Actividad */
        .wa-stats{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
        .wa-stat{flex:1;min-width:110px;background:color-mix(in srgb,var(--brand) 4%,#fff);border:1px solid color-mix(in srgb,var(--brand) 12%,#eadfd3);border-radius:14px;padding:10px 14px}
        .wa-stat small{display:block;font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.05em;color:#94867c}
        .wa-stat b{font-size:20px}
        .wa-log{display:grid;gap:8px;max-height:560px;overflow-y:auto;padding:4px 2px}
        .wa-log-item{display:grid;gap:4px;padding:12px 14px;background:#fff;border:1px solid #f0e7de;border-radius:14px}
        .wa-log-top{display:flex;justify-content:space-between;gap:10px;align-items:center;font-size:12px}
        .wa-log-top b{font-size:13.5px;color:#222}
        .wa-log-top time{color:#94867c;font-weight:700;white-space:nowrap}
        .wa-log-msg{font-size:12.5px;color:#57473d;line-height:1.5;white-space:pre-line;border-left:3px solid color-mix(in srgb,var(--brand) 30%,#eee);padding-left:10px}
        .wa-badge{font-size:10.5px;font-weight:900;border-radius:999px;padding:3px 9px;letter-spacing:.03em;text-transform:uppercase}
        .wa-badge.enviado{background:#dcfce7;color:#166534}
        .wa-badge.error{background:#fee2e2;color:#991b1b}
        .wa-badge.rechazado{background:#f3f4f6;color:#6b7280}
        .wa-empty{padding:34px;text-align:center;color:#94867c;background:#faf7f3;border:1px dashed #e3d8cd;border-radius:16px;font-size:13.5px;font-weight:650;line-height:1.6}
        .wa-empty small{display:block;font-weight:500;font-size:12px;margin-top:6px}
        .wa-toast-zone{position:fixed;bottom:20px;right:20px;display:grid;gap:8px;z-index:12}
        .wa-toast{background:#171514;color:#fff;padding:12px 16px;border-radius:14px;font-size:13.5px;font-weight:700;box-shadow:0 16px 40px rgba(0,0,0,.3);animation:waIn .18s ease}
        .wa-toast.error{background:#b42318}
        @keyframes waIn{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>

    <header class="crud-header">
        <div><h1>WhatsApp</h1><p>Vincula el WhatsApp del negocio para enviar códigos de verificación a tus clientes.</p></div>
        <div class="crud-actions">
            <button class="chip" onclick="openConnectModal()">🔗 Vincular / regenerar QR</button>
        </div>
    </header>

    <?php if (!$enabled): ?>
        <div class="wa-card" style="text-align:center;background:#fffbeb;border-color:#f7e8b8">
            <h2 style="justify-content:center">WhatsApp no disponible</h2>
            <p class="wa-sub" style="margin:0">El servidor de WhatsApp no está configurado. Contacta al administrador del sistema.</p>
        </div>
    <?php else: ?>
        <div class="wa-layout">
            <section class="wa-card">
                <h2>Estado de la conexión</h2>
                <p class="wa-sub">Los códigos OTP se envían desde este número.</p>

                <div class="wa-status-hero">
                    <div class="wa-avatar">
                        <svg viewBox="0 0 32 32"><path d="M16 2.9c-7.2 0-13 5.8-13 13 0 2.3.6 4.5 1.7 6.4L2.9 29l6.9-1.8c1.9 1 4 1.5 6.2 1.5 7.2 0 13-5.8 13-13s-5.8-13-13-13zm0 23.7c-2 0-3.9-.5-5.6-1.5l-.4-.2-4.1 1.1 1.1-4-.3-.4A10.7 10.7 0 0 1 5.3 16c0-5.9 4.8-10.7 10.7-10.7S26.7 10.1 26.7 16 21.9 26.6 16 26.6zm5.9-8c-.3-.2-1.9-.9-2.2-1s-.5-.2-.7.2-.8 1-1 1.2-.4.2-.7 0c-1.9-.9-3.1-2.8-3.2-2.9-.1-.2-.8-1-.9-1.3-.1-.3 0-.4.2-.6l.5-.6c.2-.2.2-.3.3-.5v-.5c-.1-.2-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.7c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.3 5.2 4.6.7.3 1.3.5 1.8.6.7.2 1.4.2 1.9.1.6-.1 1.9-.8 2.1-1.5.3-.8.3-1.4.2-1.5-.1-.2-.3-.3-.6-.4z"/></svg>
                        <span class="wa-dot <?= e($status) ?>"></span>
                    </div>
                    <span class="wa-status-pill <?= e($status) ?>">
                        <?= e(match ($status) { 'connected' => 'Conectado', 'connecting' => 'Conectando…', 'error' => 'Error', default => 'Desconectado' }) ?>
                    </span>

                    <?php if ($status === 'connected'): ?>
                        <div class="wa-phone"><?= e($business['whatsapp_instance_phone'] ?? 'Número vinculado') ?></div>
                        <?php if ($business['whatsapp_instance_connected_at']): ?>
                            <p class="wa-since">Conectado desde <?= e(date('d/m/Y H:i', strtotime((string) $business['whatsapp_instance_connected_at']))) ?></p>
                        <?php endif; ?>
                    <?php elseif ($status === 'connecting'): ?>
                        <p class="wa-since">Esperando a que el cliente escanee el QR…</p>
                    <?php else: ?>
                        <p class="wa-since">Necesitas vincular un número para enviar códigos.</p>
                    <?php endif; ?>
                </div>

                <?php if ($status === 'connected'): ?>
                    <div class="wa-actions">
                        <form method="post" action="/admin/whatsapp/test<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Enviando…';">
                            <div class="wa-test-row">
                                <input name="telefono" inputmode="numeric" maxlength="10" required pattern="\d{10}" placeholder="5512345678" oninput="this.value=this.value.replace(/\D/g,'')" aria-label="Teléfono de prueba">
                                <button class="primary" type="submit">Enviar prueba</button>
                            </div>
                            <p class="wa-test-note">Te envía un código de ejemplo a ese número para verificar la conexión. Puedes revisar el resultado en "Actividad de envíos".</p>
                        </form>
                        <form method="post" action="/admin/whatsapp/disconnect<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" onsubmit="return confirm('¿Desconectar WhatsApp? Los clientes no podrán verificar su número hasta que lo vincules de nuevo.');">
                            <button class="chip" type="submit" style="width:100%">Desconectar este número</button>
                        </form>
                    </div>
                <?php endif; ?>
            </section>

            <section class="wa-card">
                <h2>Actividad de envíos</h2>
                <p class="wa-sub">Los últimos códigos enviados a tus clientes.</p>

                <div class="wa-stats">
                    <div class="wa-stat"><small>Enviados</small><b><?= (int) ($stats['enviados'] ?? 0) ?></b></div>
                    <div class="wa-stat"><small>Total</small><b><?= (int) ($stats['total'] ?? 0) ?></b></div>
                    <div class="wa-stat"><small>Hoy</small><b><?= (int) ($stats['hoy'] ?? 0) ?></b></div>
                </div>

                <?php if ($envios): ?>
                    <div class="wa-log">
                        <?php foreach ($envios as $envio): ?>
                            <div class="wa-log-item">
                                <div class="wa-log-top">
                                    <b>+<?= e($envio['para']) ?></b>
                                    <span class="wa-badge <?= e($envio['status']) ?>"><?= e($envio['status']) ?></span>
                                    <time><?= e(date('d/m/Y H:i', strtotime((string) $envio['created_at']))) ?></time>
                                </div>
                                <div class="wa-log-msg"><?= e(preg_replace('/\b\d{6}\b/', '••••••', (string) $envio['texto'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="wa-empty">
                        Aún no hay envíos registrados.
                        <small>Cuando un cliente pida verificarse por WhatsApp, su mensaje aparecerá aquí con el código oculto por seguridad.</small>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
</main>

<dialog id="waConnectModal" class="crud-modal">
    <div class="card">
        <div class="modal-head">
            <div><h2>Vincular WhatsApp</h2><p class="muted">Elige cómo vincular tu número</p></div>
            <button type="button" class="chip" onclick="closeConnectModal()">Cerrar</button>
        </div>

        <div class="wa-methods">
            <button type="button" class="wa-method-btn active" id="waBtnQr" onclick="showMethod('qr')">📱 Escanear QR<small>Desde el celular del negocio</small></button>
            <button type="button" class="wa-method-btn" id="waBtnPair" onclick="showMethod('pairing')">🔑 Código de 8 dígitos<small>Sin escanear QR</small></button>
        </div>

        <div class="wa-content">
            <div id="waQrContainer" class="wa-qr-frame">
                <p class="wa-poll-line"><span class="wa-poll-dot"></span> Generando QR…</p>
            </div>

            <div id="waPairingContainer" style="display:none;">
                <div class="wa-link-line">
                    <span class="grow">
                        <b style="font-size:12.5px;color:#57473d">📞 Número con código de país (sin +)</b>
                        <input type="text" id="waPairingPhone" placeholder="5214611086760" maxlength="15" oninput="this.value=this.value.replace(/\D/g,'')">
                    </span>
                    <button class="primary" onclick="requestPairingCode()">Generar código</button>
                </div>
                <div id="waPairingResult" style="display:none;margin-top:14px;"></div>
            </div>
        </div>

        <p class="wa-poll-line" id="waPollLine" style="display:none"><span class="wa-poll-dot"></span> Esperando vinculación… esta ventana se cerrará sola.</p>
        <div style="margin-top:14px" id="waModalFooter">
            <p class="wa-test-note">Si ya habías vinculado antes y cambias de celular, desinstala el WhatsApp o desvincula el dispositivo desde la app antes de escanear.</p>
        </div>
    </div>
</dialog>

<div class="wa-toast-zone" id="waToasts"></div>

<script>
let pollTimer = null;

function waToast(message, ok = true) {
    const zone = document.getElementById('waToasts');
    const el = document.createElement('div');
    el.className = 'wa-toast' + (ok ? '' : ' error');
    el.textContent = message;
    zone.appendChild(el);
    setTimeout(() => el.remove(), 3400);
}

function tenantSuffix() {
    const tenant = new URLSearchParams(window.location.search).get('tenant');
    return tenant ? '?tenant=' + encodeURIComponent(tenant) : '';
}

function showMethod(method) {
    document.getElementById('waBtnQr').classList.toggle('active', method === 'qr');
    document.getElementById('waBtnPair').classList.toggle('active', method === 'pairing');
    document.getElementById('waQrContainer').style.display = method === 'qr' ? 'grid' : 'none';
    document.getElementById('waPairingContainer').style.display = method === 'pairing' ? 'block' : 'none';
    if (method === 'qr') fetchQr();
}

async function fetchQr() {
    const container = document.getElementById('waQrContainer');
    container.innerHTML = '<p class="wa-poll-line"><span class="wa-poll-dot"></span> Generando QR…</p>';
    try {
        const response = await fetch('/admin/whatsapp/connect' + tenantSuffix(), { method: 'POST' });
        const data = await response.json();
        if (!data.ok) {
            container.innerHTML = '<p class="wa-alert error">' + escapeHtml(data.error || 'Error al generar QR') + '</p>';
            return;
        }
        const src = data.qr && data.qr.base64 ? (data.qr.base64.startsWith('data:') ? data.qr.base64 : 'data:image/png;base64,' + data.qr.base64) : null;
        container.innerHTML = (src ? '<img src="' + src + '" alt="QR de vinculación">' : '')
            + '<p class="wa-test-note" style="text-align:center;margin:0">Escanea este QR desde WhatsApp → Dispositivos vinculados.</p>'
            + '<div class="wa-steps">'
            + '<div class="wa-step"><i>1</i><span>Abre WhatsApp en el celular del negocio.</span></div>'
            + '<div class="wa-step"><i>2</i><span>Menú ⋮ → Dispositivos vinculados → Vincular dispositivo.</span></div>'
            + '<div class="wa-step"><i>3</i><span>Apunta la cámara a este código.</span></div>'
            + '</div>';
        startPolling();
    } catch (e) {
        container.innerHTML = '<p class="wa-alert error">Error de conexión. Intenta de nuevo.</p>';
    }
}

async function openConnectModal() {
    document.querySelector('#waConnectModal').showModal();
    showMethod('qr');
}

function closeConnectModal() {
    stopPolling();
    document.querySelector('#waConnectModal').close();
}

async function requestPairingCode() {
    const phone = document.getElementById('waPairingPhone').value.trim();
    const resultContainer = document.getElementById('waPairingResult');
    if (!/^\d{10,15}$/.test(phone)) {
        resultContainer.style.display = 'block';
        resultContainer.innerHTML = '<p class="wa-alert error">Ingresa un número válido: solo dígitos, con código de país (ej. 5214611086760).</p>';
        return;
    }
    resultContainer.style.display = 'block';
    resultContainer.innerHTML = '<p class="wa-poll-line"><span class="wa-poll-dot"></span> Generando código…</p>';
    try {
        const response = await fetch('/admin/whatsapp/pairing-code' + tenantSuffix(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone }),
        });
        const data = await response.json();
        if (!data.ok) {
            resultContainer.innerHTML = '<p class="wa-alert error">' + escapeHtml(data.error || 'Error al generar código') + '</p>';
            return;
        }
        if (!data.code) {
            resultContainer.innerHTML = '<p class="wa-alert error">No se recibió código. Intenta de nuevo.</p>';
            return;
        }
        resultContainer.innerHTML =
            '<p class="wa-test-note" style="margin:0 0 6px">Toca el código para copiarlo:</p>'
            + '<div class="wa-pairing-code" title="Copiar" onclick="copyPairing(this)">' + escapeHtml(data.code) + '</div>'
            + '<div class="wa-steps" style="margin-top:12px">'
            + '<div class="wa-step"><i>1</i><span>Abre WhatsApp en el celular del negocio.</span></div>'
            + '<div class="wa-step"><i>2</i><span>Menú ⋮ → Dispositivos vinculados → Vincular dispositivo.</span></div>'
            + '<div class="wa-step"><i>3</i><span>Toca "¿No puedes escanear el código QR?" y escribe estos 8 dígitos.</span></div>'
            + '</div>';
        startPolling();
    } catch (e) {
        resultContainer.innerHTML = '<p class="wa-alert error">Error de conexión. Intenta de nuevo.</p>';
    }
}

function copyPairing(el) {
    const text = el.textContent.trim();
    const done = () => waToast('Código copiado: ' + text);
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(done);
    } else {
        const ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); } catch (e) { /* noop */ }
        ta.remove();
        done();
    }
}

function startPolling() {
    document.getElementById('waPollLine').style.display = 'inline-flex';
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(checkStatus, 3000);
}

function stopPolling() {
    document.getElementById('waPollLine').style.display = 'none';
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

async function checkStatus() {
    try {
        const response = await fetch('/admin/whatsapp/status' + tenantSuffix());
        const data = await response.json();
        if (data.status === 'connected') {
            stopPolling();
            closeConnectModal();
            waToast('WhatsApp vinculado correctamente.');
            setTimeout(() => window.location.reload(), 700);
        }
    } catch (e) { /* sigue intentando */ }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}
</script>
<?php if ($success): ?>waToast(<?= json_encode($success) ?>, true);<?php endif; ?>
<?php if ($error): ?>waToast(<?= json_encode($error) ?>, false);<?php endif; ?>
</body>
</html>
