<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ordena · <?= e($business['nombre']) ?></title>
<link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
<link rel="stylesheet" href="/assets/themes/<?= e($theme) ?>/theme.css?v=<?= filemtime(BASE_PATH . '/public/assets/themes/' . $theme . '/theme.css') ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
    :root {
        --brand: <?= e($business['color_primario'] ?? '#cc4b25') ?>;
        --dark: <?= e($business['color_secundario'] ?? '#2b201b') ?>;
        --cream: <?= e($business['color_fondo'] ?? '#fffaf4') ?>;
        --ink: <?= e($business['color_texto'] ?? '#171514') ?>;
        --hero-image: <?= !empty($business['hero_image_url']) ? "url('" . e($business['hero_image_url']) . "')" : 'none' ?>;
        --hero-overlay-color: <?= e($business['hero_overlay_color'] ?? '#000000') ?>;
        --hero-overlay-opacity: <?= e((string) ($business['hero_overlay_opacity'] ?? '0.35')) ?>;
        --hero-blur: <?= (int) ($business['hero_blur'] ?? 0) ?>px;
    }
    body { font-family: <?= e($business['fuente'] ?? 'Inter, system-ui, sans-serif') ?>; }
    /* Búsqueda y mapa de delivery */
    .delivery-search-box { position: relative; z-index: 10; }
    .delivery-search-box > label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.95rem; }
    .delivery-search-input-wrap { display: flex; gap: 8px; }
    .delivery-search-input-wrap input {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 10px;
        font-size: 0.95rem;
    }
    .delivery-search-input-wrap input:focus { outline: none; border-color: var(--brand, #cc4b25); box-shadow: 0 0 0 3px rgba(204,75,37,0.12); }
    .delivery-search-input-wrap button { white-space: nowrap; border-radius: 10px; }

    .geocode-results-dropdown {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 6px);
        max-height: 260px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        display: none;
    }
    .geocode-result {
        display: block;
        width: 100%;
        text-align: left;
        background: #fff;
        border: none;
        border-bottom: 1px solid #f0f0f0;
        padding: 12px 14px;
        cursor: pointer;
        font: inherit;
    }
    .geocode-result:last-child { border-bottom: none; }
    .geocode-result:hover, .geocode-result:focus { background: #fff8f5; }
    .geocode-result b { display: block; font-size: 0.92rem; color: #222; font-weight: 600; }
    .geocode-result small { display: block; color: #777; font-size: 0.78rem; margin-top: 3px; }
    .geocode-result .coord { color: #999; font-size: 0.72rem; }

    .geocode-status {
        margin-top: 8px;
        padding: 9px 12px;
        border-radius: 8px;
        font-size: 0.85rem;
        display: none;
    }
    .geocode-status.info { background: #f3f4f6; color: #4b5563; }
    .geocode-status.success { background: #ecfdf5; color: #065f46; }
    .geocode-status.error { background: #fef2f2; color: #991b1b; }
    .geocode-status.warning { background: #fffbeb; color: #92400e; }

    .delivery-map-wrap { margin-top: 14px; border-radius: 12px; overflow: hidden; border: 1px solid #e5e5e5; position: relative; height: 320px; min-height: 320px; background: #f9f9f9; }
    #deliveryMap { height: 100%; width: 100%; }
    .map-hint {
        position: absolute;
        bottom: 10px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255,255,255,0.95);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.78rem;
        color: #555;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        pointer-events: none;
    }

    .delivery-summary {
        margin-top: 12px;
        background: #fafafa;
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 12px 14px;
        display: grid;
        gap: 8px;
    }
    .delivery-summary .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
    }
    .delivery-summary .summary-row span { color: #666; }
    .delivery-summary .summary-row b { color: #222; font-weight: 600; }
    .delivery-summary .summary-row.outside b { color: #b91c1c; }

    .custom-pin-container { background: transparent !important; border: none !important; }
    .custom-pin {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        border: 2px solid #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.35);
    }
    .custom-pin span { line-height: 1; }
    .custom-pin-tip {
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 8px solid;
        margin: -2px auto 0;
    }

    .account-fab {
        position: fixed;
        left: 20px;
        bottom: 20px;
        background: var(--dark, #2b201b);
        color: #fff;
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
        border-radius: 999px;
        padding: 12px 18px;
        font-weight: 800;
        z-index: 4;
    }
    .account-menu {
        position: fixed;
        left: 20px;
        bottom: 78px;
        display: none;
        gap: 6px;
        background: #fff;
        border: 1px solid #e8e0d8;
        border-radius: 14px;
        padding: 8px;
        box-shadow: 0 14px 34px rgba(0,0,0,0.16);
        z-index: 4;
        min-width: 160px;
    }
    .account-menu button {
        width: 100%;
        text-align: left;
        background: none;
        color: var(--ink, #171514);
        padding: 10px 12px;
        border-radius: 10px;
        font-weight: 650;
    }
    .account-menu button:hover { background: var(--cream, #fffaf4); }

    .account-callout {
        padding: 12px 14px;
        border-radius: 10px;
        font-size: 0.9rem;
        margin-bottom: 12px;
    }
    .account-callout.warning { background: #fffbeb; color: #92400e; border: 1px solid #f7e8b8; }
    .account-callout.success { background: #ecfdf5; color: #065f46; border: 1px solid #c6f2dc; }

    .order-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        margin-bottom: 8px;
    }
    .order-status {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 750;
        white-space: nowrap;
        background: #f3f4f6;
        color: #4b5563;
    }
    .order-status.status-nuevo { background: #fef3c7; color: #92400e; }
    .order-status.status-confirmado, .order-status.status-preparacion { background: #e0e7ff; color: #3730a3; }
    .order-status.status-listo, .order-status.status-camino { background: #ecfdf5; color: #065f46; }
    .order-status.status-entregado { background: #d1fae5; color: #065f46; }
    .order-status.status-cancelado { background: #fee2e2; color: #991b1b; }
    .order-item { display: grid; gap: 2px; padding: 12px 14px; margin-bottom: 8px; }

    .modalbox a { color: var(--brand, #cc4b25); }

    .form-error {
        display: none;
        margin: 8px 0 0;
        padding: 10px 12px;
        border-radius: 8px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        font-size: 0.85rem;
        font-weight: 650;
        line-height: 1.35;
    }
    .form-error.visible { display: block; }

    @media (max-width: 480px) {
        .delivery-search-input-wrap { flex-direction: column; }
        .delivery-search-input-wrap button { width: 100%; justify-content: center; }
        #deliveryMap { height: 260px; }
        .account-fab { left: 12px; bottom: 12px; padding: 10px 14px; font-size: 0.85rem; }
        .account-menu { left: 12px; bottom: 66px; }
    }
</style>
