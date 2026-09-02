<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Personalización · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
</head>
<body class="admin-themed store-bg-<?= e($business['fondo_estilo'] ?? 'calido') ?>">
<main class="shell admin-crud">
    <?php require BASE_PATH . '/app/Views/admin/partials/nav.php'; ?>
    <header class="crud-header">
        <div><h1>Personalización</h1><p>Haz que la tienda parezca del negocio, no de una plataforma genérica.</p></div>
        <a class="primary" style="text-decoration:none" href="/<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" target="_blank">Ver tienda</a>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="branding-grid">
        <form class="crud-card branding-form" method="post" enctype="multipart/form-data" action="/admin/branding<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
            <h2>Identidad visual</h2>
            <label>Tema base
                <select name="theme_key">
                    <?php foreach ($themes as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= ($business['theme_key'] ?? 'burger') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <p class="muted">El tema define el acomodo fijo de la tienda. El negocio solo personaliza colores, fuente y textos dentro de ese layout.</p>

            <div class="color-grid">
                <label>Color principal<input data-preview="primary" name="color_primario" type="color" value="<?= e($business['color_primario'] ?? '#cc4b25') ?>"></label>
                <label>Color secundario<input data-preview="secondary" name="color_secundario" type="color" value="<?= e($business['color_secundario'] ?? '#2b201b') ?>"></label>
                <label>Fondo<input data-preview="bg" name="color_fondo" type="color" value="<?= e($business['color_fondo'] ?? '#fffaf4') ?>"></label>
                <label>Texto<input data-preview="text" name="color_texto" type="color" value="<?= e($business['color_texto'] ?? '#171514') ?>"></label>
            </div>

            <label>Fuente
                <select data-preview="font" name="fuente">
                    <?php foreach ($fonts as $font => $label): ?>
                        <option value="<?= e($font) ?>" <?= ($business['fuente'] ?? '') === $font ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Estilo de fondo
                <select data-preview="background" name="fondo_estilo">
                    <?php foreach ($backgrounds as $background): ?>
                        <option value="<?= e($background) ?>" <?= ($business['fondo_estilo'] ?? 'calido') === $background ? 'selected' : '' ?>><?= e(ucfirst($background)) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Fondo inicial / portada<input name="hero_image" type="file" accept="image/jpeg,image/png,image/webp"></label>
            <p class="muted">Recomendado: 2400 x 1350 px, formato horizontal 16:9, JPG/PNG/WebP hasta 5 MB. Mantén texto, logos o comida principal al centro para que se vea bien en móvil y desktop.</p>
            <?php if (!empty($business['hero_image_url'])): ?>
                <div class="hero-image-admin">
                    <img src="<?= e($business['hero_image_url']) ?>" alt="Fondo actual">
                    <div>
                        <b>Fondo actual cargado</b>
                        <small>Si subes otra imagen, reemplazará esta portada.</small>
                    </div>
                    <button class="danger-button" name="remove_hero_image" value="1" type="submit" formnovalidate>Eliminar fondo</button>
                </div>
            <?php endif; ?>

            <div class="color-grid">
                <label>Color overlay<input data-preview="overlayColor" name="hero_overlay_color" type="color" value="<?= e($business['hero_overlay_color'] ?? '#000000') ?>"></label>
                <label>Opacidad overlay<input data-preview="overlayOpacity" name="hero_overlay_opacity" type="range" min="0" max="0.85" step="0.05" value="<?= e((string) ($business['hero_overlay_opacity'] ?? '0.35')) ?>"></label>
                <label>Difuminado<input data-preview="blur" name="hero_blur" type="range" min="0" max="18" step="1" value="<?= e((string) ($business['hero_blur'] ?? '0')) ?>"></label>
            </div>

            <label>Título principal<input data-preview="title" name="hero_titulo" maxlength="160" required value="<?= e($business['hero_titulo'] ?? 'Tu antojo, directo del restaurante.') ?>"></label>
            <label>Subtítulo<textarea data-preview="subtitle" name="hero_subtitulo" maxlength="255" required><?= e($business['hero_subtitulo'] ?? 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.') ?></textarea></label>
            <label>Link para Comer aquí<input name="comer_aqui_url" type="url" value="<?= e($business['comer_aqui_url'] ?? '') ?>" placeholder="https://tu-link-para-mesas.com"></label>
            <p class="muted">Si capturas este link, en la tienda aparece un botón `Comer aquí`. Si lo dejas vacío, no se muestra.</p>

            <button class="primary" style="width:100%">Guardar personalización</button>
        </form>

        <aside class="brand-preview" id="brandPreview" style="--preview-primary:<?= e($business['color_primario'] ?? '#cc4b25') ?>;--preview-secondary:<?= e($business['color_secundario'] ?? '#2b201b') ?>;--preview-bg:<?= e($business['color_fondo'] ?? '#fffaf4') ?>;--preview-text:<?= e($business['color_texto'] ?? '#171514') ?>;--preview-overlay:<?= e($business['hero_overlay_color'] ?? '#000000') ?>;--preview-overlay-opacity:<?= e((string) ($business['hero_overlay_opacity'] ?? '0.35')) ?>;--preview-blur:<?= (int) ($business['hero_blur'] ?? 0) ?>px;--preview-image:<?= !empty($business['hero_image_url']) ? "url('" . e($business['hero_image_url']) . "')" : 'none' ?>;font-family:<?= e($business['fuente'] ?? 'Inter, system-ui, sans-serif') ?>">
            <div class="preview-phone <?= e($business['fondo_estilo'] ?? 'calido') ?>">
                <div class="preview-top"><span>Pedido directo</span><b><?= e($business['nombre']) ?></b></div>
                <div class="preview-hero">
                    <span>Abierto ahora · 25-35 min</span>
                    <h2 id="previewTitle"><?= e($business['hero_titulo'] ?? 'Tu antojo, directo del restaurante.') ?></h2>
                    <p id="previewSubtitle"><?= e($business['hero_subtitulo'] ?? 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.') ?></p>
                </div>
                <div class="preview-card"><div></div><b>Hamburguesa especial</b><small>Con queso, salsa de casa y papas</small><button>Agregar</button></div>
            </div>
        </aside>
    </section>
</main>
<script>
const preview = document.querySelector('#brandPreview');
document.querySelectorAll('[data-preview]').forEach(input => input.addEventListener('input', () => {
    const key = input.dataset.preview;
    if (key === 'primary') preview.style.setProperty('--preview-primary', input.value);
    if (key === 'secondary') preview.style.setProperty('--preview-secondary', input.value);
    if (key === 'bg') preview.style.setProperty('--preview-bg', input.value);
    if (key === 'text') preview.style.setProperty('--preview-text', input.value);
    if (key === 'font') preview.style.fontFamily = input.value;
    if (key === 'background') preview.querySelector('.preview-phone').className = 'preview-phone ' + input.value;
    if (key === 'overlayColor') preview.style.setProperty('--preview-overlay', input.value);
    if (key === 'overlayOpacity') preview.style.setProperty('--preview-overlay-opacity', input.value);
    if (key === 'blur') preview.style.setProperty('--preview-blur', input.value + 'px');
    if (key === 'title') document.querySelector('#previewTitle').textContent = input.value;
    if (key === 'subtitle') document.querySelector('#previewSubtitle').textContent = input.value;
}));
</script>
</body>
</html>
