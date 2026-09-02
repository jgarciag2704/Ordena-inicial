<style>
    :root {
        --brand: <?= e($business['color_primario'] ?? '#cc4b25') ?>;
        --dark: <?= e($business['color_secundario'] ?? '#2b201b') ?>;
        --cream: <?= e($business['color_fondo'] ?? '#fffaf4') ?>;
        --ink: <?= e($business['color_texto'] ?? '#171514') ?>;
    }
    body { font-family: <?= e($business['fuente'] ?? 'Inter, system-ui, sans-serif') ?>; }
</style>
