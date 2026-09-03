<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Menú · <?= e($business['nombre']) ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <?php require BASE_PATH . '/app/Views/admin/partials/theme.php'; ?>
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
            <button class="primary" onclick="document.querySelector('#newProduct').showModal()">+ Nuevo producto</button>
        </div>
    </header>

    <?php if ($error): ?><p class="alert error"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= e($success) ?></p><?php endif; ?>

    <section class="crud-tools">
        <form method="post" action="/admin/menu/categories<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
            <label>Nueva categoría</label>
            <div class="tool-inline">
                <input name="nombre" required placeholder="Ej. Hamburguesas premium">
                <button class="chip">Agregar</button>
            </div>
        </form>
    </section>

    <section class="crud-card">
        <div class="crud-table">
            <div class="crud-row crud-row-head">
                <span>Producto</span>
                <span>Categoría</span>
                <span>Precio</span>
                <span>Extras</span>
                <span>Estado</span>
                <span>Acciones</span>
            </div>

            <?php foreach ($products as $product): ?>
                <?php $productOptions = $optionsByProduct[(int) $product['id']] ?? []; ?>
                <details class="crud-item">
                    <summary class="crud-row">
                        <span class="product-cell">
                            <?php if ($product['imagen_thumb']): ?>
                                <img class="list-thumb" src="<?= e($product['imagen_thumb']) ?>" alt="<?= e($product['nombre']) ?>">
                            <?php else: ?>
                                <span class="list-thumb empty-thumb"></span>
                            <?php endif; ?>
                            <span><b><?= e($product['nombre']) ?></b><small><?= e($product['descripcion']) ?></small></span>
                        </span>
                        <span><?= e($product['categoria_nombre']) ?></span>
                        <span><?= money($product['precio']) ?></span>
                        <span><span class="soft-badge"><?= count($productOptions) ?> grupos</span></span>
                        <span><span class="status-pill <?= (int) $product['disponible'] === 1 ? 'ok' : 'off' ?>"><?= (int) $product['disponible'] === 1 ? 'Activo' : 'Oculto' ?></span></span>
                        <span class="row-actions">
                            <span class="btn-edit">Editar</span>
                            <form method="post" action="/admin/menu/products/toggle<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                <button class="btn-muted"><?= (int) $product['disponible'] === 1 ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        </span>
                    </summary>

                    <div class="crud-detail">
                        <form class="edit-grid" method="post" enctype="multipart/form-data" action="/admin/menu/products/update<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                            <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                            <label>Nombre<input name="nombre" required value="<?= e($product['nombre']) ?>"></label>
                            <label>Categoría
                                <select name="categoria_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int) $category['id'] ?>" <?= (int) $category['id'] === (int) $product['categoria_id'] ? 'selected' : '' ?>><?= e($category['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Precio<input name="precio" type="number" min="1" step="0.01" required value="<?= e((string) $product['precio']) ?>"></label>
                            <label>Foto nueva<input name="foto" type="file" accept="image/jpeg,image/png,image/webp"></label>
                            <label class="wide">Descripción<textarea name="descripcion"><?= e($product['descripcion']) ?></textarea></label>
                            <button class="primary">Guardar cambios</button>
                        </form>

                        <div class="extras-crud">
                            <div class="extras-title">
                                <h3>Extras y opciones</h3>
                                <span class="muted">Configura lo que el cliente elige antes de agregar al carrito.</span>
                            </div>

                            <?php foreach ($productOptions as $option): ?>
                                <div class="extra-group">
                                    <div class="extra-group-head">
                                        <div>
                                            <b><?= e($option['nombre']) ?></b>
                                            <span class="mini-pill"><?= e(['multiple' => 'Múltiple', 'unica' => 'Única', 'texto' => 'Texto'][$option['tipo']] ?? $option['tipo']) ?></span>
                                            <?php if ($option['requerida']): ?><span class="mini-pill required">Obligatoria</span><?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if ($option['tipo'] !== 'texto'): ?>
                                        <div class="value-list">
                                            <?php foreach ($option['valores'] as $value): ?>
                                                <div class="value-edit">
                                                    <form method="post" action="/admin/menu/option-values/update<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                                        <input type="hidden" name="id" value="<?= (int) $value['id'] ?>">
                                                        <input name="nombre" required value="<?= e($value['nombre']) ?>">
                                                        <input name="precio_extra" type="number" min="0" step="0.01" required value="<?= e((string) $value['precio_extra']) ?>">
                                                        <button class="chip small">Guardar</button>
                                                    </form>
                                                    <form method="post" action="/admin/menu/option-values/delete<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>" onsubmit="return confirm('¿Eliminar este extra?')">
                                                        <input type="hidden" name="id" value="<?= (int) $value['id'] ?>">
                                                        <button class="danger-button small">Eliminar</button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <form class="extra-inline" method="post" action="/admin/menu/option-values<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                            <input type="hidden" name="producto_opcion_id" value="<?= (int) $option['id'] ?>">
                                            <input name="nombre" required placeholder="Nuevo extra">
                                            <input name="precio_extra" type="number" min="0" step="0.01" required placeholder="Precio extra">
                                            <button class="chip small">Agregar</button>
                                        </form>
                                    <?php else: ?>
                                        <p class="muted">Campo de indicaciones libres.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <form class="add-option-bar" method="post" action="/admin/menu/options<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
                                <input type="hidden" name="producto_id" value="<?= (int) $product['id'] ?>">
                                <input name="nombre" required placeholder="Nuevo grupo: Salsas, Extras, Bebida">
                                <select name="tipo">
                                    <option value="multiple">Múltiple</option>
                                    <option value="unica">Única</option>
                                    <option value="texto">Texto libre</option>
                                </select>
                                <label><input type="checkbox" name="requerida" value="1"> Obligatorio</label>
                                <button class="primary small">Agregar grupo</button>
                            </form>
                        </div>
                    </div>
                </details>
            <?php endforeach; ?>

            <?php if (!$products): ?>
                <p class="empty-state">Aún no hay productos. Usa el botón `Nuevo producto` para empezar.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<dialog id="newProduct" class="crud-modal">
    <form class="card" method="post" enctype="multipart/form-data" action="/admin/menu/products<?= isset($_GET['tenant']) ? '?tenant=' . urlencode((string) $_GET['tenant']) : '' ?>">
        <div class="modal-head">
            <div><h2>Nuevo producto</h2><p class="muted">Agrega platillos con foto optimizada.</p></div>
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
        <button class="primary" style="width:100%">Crear producto</button>
    </form>
</dialog>
</body>
</html>
