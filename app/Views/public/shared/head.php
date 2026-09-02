<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ordena · <?= e($business['nombre']) ?></title>
<link rel="stylesheet" href="/assets/styles.css?v=<?= filemtime(BASE_PATH . '/public/assets/styles.css') ?>">
<link rel="stylesheet" href="/assets/themes/<?= e($theme) ?>/theme.css?v=<?= filemtime(BASE_PATH . '/public/assets/themes/' . $theme . '/theme.css') ?>">
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
</style>
