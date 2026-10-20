<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>WhatsApp · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
    <style>
        .wa-status-card { padding: 24px; text-align: center; }
        .wa-status-badge { display: inline-block; padding: 8px 20px; border-radius: 999px; font-weight: 600; font-size: 0.95rem; margin-bottom: 12px; }
        .wa-status-badge.connected { background: #d4edda; color: #155724; }
        .wa-status-badge.disconnected { background: #f8d7da; color: #721c24; }
        .wa-status-badge.connecting { background: #fff3cd; color: #856404; }
        .wa-status-badge.error { background: #f8d7da; color: #721c24; }
        .wa-phone { font-size: 1.3rem; font-weight: 600; margin: 8px 0; }
        .wa-qr-container { display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 20px; }
        .wa-qr-container img { max-width: 280px; width: 100%; border-radius: 12px; border: 2px solid #ddd; }
        .wa-pairing-code { font-size: 2rem; font-weight: 700; letter-spacing: 3px; padding: 15px 25px; background: #f5f5f5; border-radius: 8px; font-family: monospace; }
        .wa-not-enabled { padding: 24px; text-align: center; background: #fff3cd; border-radius: 12px; margin: 16px; }
        .wa-test-form { display: flex; gap: 8px; align-items: end; margin-top: 16px; justify-content: center; }
        .wa-test-form input { width: 180px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; }
        .wa-polling-msg { font-size: 0.85rem; color: #666; margin-top: 8px; }
        
        .wa-method-selector { display: flex; gap: 8px; margin: 20px 0; justify-content: center; flex-wrap: wrap; }
        .wa-method-btn { 
            padding: 12px 24px; 
            border: 2px solid #ddd; 
            background: white; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .wa-method-btn:hover { border-color: var(--brand, #cc4b25); }
        .wa-method-btn.active { border-color: var(--brand, #cc4b25); background: var(--brand, #cc4b25); color: white; }
        
        .wa-method-content { padding: 16px; text-align: center; }
        .wa-method-content img { max-width: 280px; width: 100%; border-radius: 12px; border: 2px solid #ddd; margin: 0 auto; }
        
        .wa-pairing-form { display: flex; gap: 8px; align-items: stretch; margin: 16px 0; flex-wrap: wrap; }
        .wa-pairing-form input { 
            flex: 1; 
            min-width: 200px;
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            font-size: 1rem; 
        }
        .wa-pairing-form button {
            padding: 12px 24px;
            white-space: nowrap;
        }
        
        #waPairingResult { margin-top: 16px; }
        
        dialog.crud-modal {
            max-width: 500px;
            width: 90%;
            padding: 0;
            border: none;
            border-radius: 12px;
        }
        
        dialog.crud-modal .card {
            padding: 0;
        }
        
        dialog.crud-modal .modal-head {
            padding: 20px 24px;
            border-bottom: 1px solid #eee;
        }
        
        dialog.crud-modal .modal-head h2 {
            margin: 0 0 4px 0;
            font-size: 1.3rem;
        }
        
        dialog.crud-modal .modal-head p {
            margin: 0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 600px) {
            .wa-method-selector {
                flex-direction: column;
            }
            .wa-method-btn {
                width: 100%;
            }
            .wa-pairing-form {
                flex-direction: column;
            }
            .wa-pairing-form button {
                width: 100%;
            }
            .wa-test-form {
                flex-direction: column;
            }
            .wa-test-form input {
                width: 100%;
            }
            dialog.crud-modal {
                width: 95%;
                max-width: none;
            }
        }
    </style>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>WhatsApp</h1><p>Vincula el WhatsApp del negocio para enviar códigos de verificación a tus clientes.</p></div>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <?php if (!$enabled): ?>
        <div class="wa-not-enabled">
            <h3>WhatsApp no disponible</h3>
            <p class="muted">El servidor de WhatsApp no está configurado. Contacta al administrador del sistema.</p>
        </div>
    <?php else: ?>
        <section class="crud-card wa-status-card">
            <div class="wa-status-badge <?= e($status) ?>"><?= e(ucfirst($status === 'disconnected' ? 'Desconectado' : ($status === 'connected' ? 'Conectado' : ($status === 'connecting' ? 'Conectando...' : 'Error')))) ?></div>

            <?php if ($status === 'connected'): ?>
                <div class="wa-phone"><?= e($business['whatsapp_instance_phone'] ?? 'Número vinculado') ?></div>
                <?php if ($business['whatsapp_instance_connected_at']): ?>
                    <p class="muted">Conectado desde <?= e(date('d/m/Y H:i', strtotime((string) $business['whatsapp_instance_connected_at']))) ?></p>
                <?php endif; ?>

                <div class="wa-test-form">
                    <form method="post" action="/admin/whatsapp/test<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" onsubmit="this.querySelector('button').disabled=true;">
                        <label>Teléfono de prueba (10 dígitos):
                            <input name="telefono" inputmode="numeric" maxlength="10" required pattern="\d{10}" placeholder="5512345678" oninput="this.value=this.value.replace(/\D/g,'')">
                        </label>
                        <button class="primary" type="submit">Enviar prueba</button>
                    </form>
                </div>

                <form method="post" action="/admin/whatsapp/disconnect<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" style="margin-top:20px;" onsubmit="return confirm('¿Desconectar WhatsApp? Los clientes no podrán verificar su número hasta que lo vincules de nuevo.');">
                    <button class="chip" type="submit">Desconectar</button>
                </form>
            <?php elseif ($status === 'connecting'): ?>
                <p class="wa-polling-msg">Esperando escaneo del QR...</p>
                <button class="primary" onclick="openConnectModal()">Regenerar QR</button>
            <?php else: ?>
                <p class="muted">El negocio necesita vincular su WhatsApp para poder enviar códigos de verificación a los clientes.</p>
                <button class="primary" onclick="openConnectModal()">Vincular WhatsApp</button>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<dialog id="waConnectModal" class="crud-modal">
    <div class="card">
        <div class="modal-head">
            <div><h2>Vincular WhatsApp</h2><p class="muted">Elige cómo vincular tu número</p></div>
            <button type="button" class="chip" onclick="closeConnectModal()">Cerrar</button>
        </div>
        
        <div class="wa-method-selector">
            <button type="button" class="wa-method-btn active" onclick="showMethod('qr')">QR Code</button>
            <button type="button" class="wa-method-btn" onclick="showMethod('pairing')">Código de emparejamiento</button>
        </div>
        
        <div id="waQrContainer" class="wa-method-content">
            <p class="wa-polling-msg">Generando QR...</p>
        </div>
        
        <div id="waPairingContainer" class="wa-method-content" style="display:none;">
            <p class="muted">Ingresa tu número de WhatsApp con código de país (sin el símbolo +):</p>
            <div class="wa-pairing-form">
                <input type="text" id="waPairingPhone" placeholder="5214611086760" maxlength="15" pattern="\d{10,15}">
                <button class="primary" onclick="requestPairingCode()">Generar código</button>
            </div>
            <div id="waPairingResult" style="display:none;"></div>
        </div>
    </div>
</dialog>

<script>
let pollTimer = null;
let currentMethod = 'qr';

function showMethod(method) {
    currentMethod = method;
    document.querySelectorAll('.wa-method-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.wa-method-content').forEach(c => c.style.display = 'none');
    
    if (method === 'qr') {
        document.querySelector('.wa-method-btn:nth-child(1)').classList.add('active');
        document.querySelector('#waQrContainer').style.display = 'block';
        openConnectModal();
    } else {
        document.querySelector('.wa-method-btn:nth-child(2)').classList.add('active');
        document.querySelector('#waPairingContainer').style.display = 'block';
    }
}

async function openConnectModal() {
    const modal = document.querySelector('#waConnectModal');
    const container = document.querySelector('#waQrContainer');
    modal.showModal();
    container.innerHTML = '<p class="wa-polling-msg">Generando QR...</p>';

    const tenantParam = new URLSearchParams(window.location.search).get('tenant');
    const tenantSuffix = tenantParam ? '?tenant=' + encodeURIComponent(tenantParam) : '';

    try {
        const response = await fetch('/admin/whatsapp/connect' + tenantSuffix, { method: 'POST' });
        const data = await response.json();

        if (!data.ok) {
            container.innerHTML = '<p class="alert error">' + escapeHtml(data.error || 'Error al generar QR') + '</p>';
            return;
        }

        renderQr(container, data.qr);
        startPolling();
    } catch (e) {
        container.innerHTML = '<p class="alert error">Error de conexión. Intenta de nuevo.</p>';
    }
}

async function requestPairingCode() {
    const phoneInput = document.querySelector('#waPairingPhone');
    const phone = phoneInput.value.trim();
    const resultContainer = document.querySelector('#waPairingResult');
    
    if (!/^\d{10,15}$/.test(phone)) {
        resultContainer.innerHTML = '<p class="alert error">Ingresa un número válido (solo dígitos, 10-15 caracteres)</p>';
        resultContainer.style.display = 'block';
        return;
    }

    resultContainer.innerHTML = '<p class="wa-polling-msg">Generando código...</p>';
    resultContainer.style.display = 'block';

    const tenantParam = new URLSearchParams(window.location.search).get('tenant');
    const tenantSuffix = tenantParam ? '?tenant=' + encodeURIComponent(tenantParam) : '';

    try {
        const response = await fetch('/admin/whatsapp/pairing-code' + tenantSuffix, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone })
        });
        const data = await response.json();

        if (!data.ok) {
            resultContainer.innerHTML = '<p class="alert error">' + escapeHtml(data.error || 'Error al generar código') + '</p>';
            return;
        }

        if (data.code) {
            renderPairingCode(resultContainer, data.code);
            startPolling();
        } else {
            resultContainer.innerHTML = '<p class="alert error">No se recibió código. Intenta de nuevo.</p>';
        }
    } catch (e) {
        resultContainer.innerHTML = '<p class="alert error">Error de conexión. Intenta de nuevo.</p>';
    }
}

function renderQr(container, qrData) {
    let html = '';

    if (qrData.base64) {
        const src = qrData.base64.startsWith('data:') ? qrData.base64 : 'data:image/png;base64,' + qrData.base64;
        html += '<img src="' + src + '" alt="QR de vinculación">';
    }

    html += '<p class="wa-polling-msg">Escanea este QR con WhatsApp en tu celular</p>';
    container.innerHTML = html;
}

function renderPairingCode(container, pairingCode) {
    let html = '';
    
    if (pairingCode) {
        html += '<p class="muted">Ingresa este código en WhatsApp:</p>';
        html += '<div class="wa-pairing-code">' + escapeHtml(pairingCode) + '</div>';
        html += '<p class="muted" style="margin-top:12px;">WhatsApp → Menú → Dispositivos vinculados → Vincular dispositivo → Vincular con número de teléfono</p>';
    }
    
    html += '<p class="wa-polling-msg">Esperando vinculación... esta ventana se cerrará automáticamente.</p>';
    container.innerHTML = html;
}

function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(checkStatus, 3000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

async function checkStatus() {
    try {
        const tenantParam = new URLSearchParams(window.location.search).get('tenant');
        const tenantSuffix = tenantParam ? '?tenant=' + encodeURIComponent(tenantParam) : '';
        const response = await fetch('/admin/whatsapp/status' + tenantSuffix);
        const data = await response.json();

        if (data.status === 'connected') {
            stopPolling();
            closeConnectModal();
            window.location.reload();
        }
    } catch (e) {
    }
}

function closeConnectModal() {
    stopPolling();
    document.querySelector('#waConnectModal').close();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[char]));
}
</script>
</body>
</html>
