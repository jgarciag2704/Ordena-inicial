INSERT INTO negocios (nombre, slug, folio_prefijo, folio_consecutivo, theme_key, hero_titulo, hero_subtitulo, activo)
VALUES ('La Burguería', 'laburgueria', 'LB', 1000, 'burger', 'Tu antojo, directo del restaurante.', 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), folio_prefijo = VALUES(folio_prefijo), activo = 1;

INSERT INTO super_admins (nombre, email, password_hash, debe_cambiar_password, activo)
VALUES ('Juan Garcia', 'jgarciag2704@gmail.com', '$2y$10$B7PX89RAaY0AgWtJcwzhDu2Mfi.5gr1APXwn.rueimREKywyIa5g2', 1, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;

SET @negocio_id := (SELECT id FROM negocios WHERE slug = 'laburgueria');

INSERT INTO sucursales (negocio_id, nombre, direccion, telefono, activa)
VALUES (@negocio_id, 'Sucursal Centro', 'Av. Principal 123, Centro', '5555555555', 1)
ON DUPLICATE KEY UPDATE direccion = VALUES(direccion), telefono = VALUES(telefono), activa = 1;

INSERT INTO usuarios (negocio_id, nombre, email, password_hash, debe_cambiar_password, rol, activo)
VALUES (@negocio_id, 'Administrador', 'admin@laburgueria.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llCk7Vj8S0/2/HpMIgoi', 0, 'admin', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;

INSERT INTO categorias (negocio_id, nombre, orden, activo) VALUES
(@negocio_id, 'Hamburguesas', 10, 1),
(@negocio_id, 'Combos', 20, 1),
(@negocio_id, 'Papas y extras', 30, 1),
(@negocio_id, 'Bebidas', 40, 1)
ON DUPLICATE KEY UPDATE orden = VALUES(orden), activo = 1;

SET @hamburguesas := (SELECT id FROM categorias WHERE negocio_id = @negocio_id AND nombre = 'Hamburguesas');
SET @combos := (SELECT id FROM categorias WHERE negocio_id = @negocio_id AND nombre = 'Combos');
SET @papas := (SELECT id FROM categorias WHERE negocio_id = @negocio_id AND nombre = 'Papas y extras');
SET @bebidas := (SELECT id FROM categorias WHERE negocio_id = @negocio_id AND nombre = 'Bebidas');

INSERT INTO productos (negocio_id, categoria_id, nombre, descripcion, precio, disponible, orden) VALUES
(@negocio_id, @hamburguesas, 'Hamburguesa Clásica', 'Carne, queso, vegetales y aderezo.', 120, 1, 10),
(@negocio_id, @hamburguesas, 'Doble Bacon', 'Dos carnes, tocino y queso cheddar.', 165, 1, 20),
(@negocio_id, @combos, 'Combo familiar', '4 hamburguesas, papas y bebidas.', 540, 1, 10),
(@negocio_id, @papas, 'Papas sazonadas', 'Crujientes con nuestro sazonador.', 65, 1, 10),
(@negocio_id, @bebidas, 'Refresco', '355 ml, elige tu sabor.', 35, 1, 10),
(@negocio_id, @bebidas, 'Malteada de fresa', 'Cremosa y preparada al momento.', 75, 1, 20)
ON DUPLICATE KEY UPDATE categoria_id = VALUES(categoria_id), descripcion = VALUES(descripcion), precio = VALUES(precio), disponible = 1, orden = VALUES(orden);

INSERT INTO zonas_entrega (negocio_id, nombre, costo, pedido_minimo, activa)
VALUES (@negocio_id, 'Zona cercana', 35, 120, 1)
ON DUPLICATE KEY UPDATE costo = VALUES(costo), pedido_minimo = VALUES(pedido_minimo), activa = 1;

INSERT INTO producto_opciones (negocio_id, producto_id, nombre, tipo, requerida, orden, activo)
SELECT @negocio_id, p.id, 'Extras', 'multiple', 0, 10, 1 FROM productos p WHERE p.negocio_id = @negocio_id
ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), requerida = VALUES(requerida), orden = VALUES(orden), activo = 1;

INSERT INTO producto_opciones (negocio_id, producto_id, nombre, tipo, requerida, orden, activo)
SELECT @negocio_id, p.id, 'Indicaciones libres', 'texto', 0, 20, 1 FROM productos p WHERE p.negocio_id = @negocio_id
ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), requerida = VALUES(requerida), orden = VALUES(orden), activo = 1;

INSERT INTO producto_opcion_valores (negocio_id, producto_opcion_id, nombre, precio_extra, orden, activo)
SELECT @negocio_id, o.id, 'Queso extra', 15, 10, 1 FROM producto_opciones o WHERE o.negocio_id = @negocio_id AND o.nombre = 'Extras'
ON DUPLICATE KEY UPDATE precio_extra = VALUES(precio_extra), orden = VALUES(orden), activo = 1;

INSERT INTO producto_opcion_valores (negocio_id, producto_opcion_id, nombre, precio_extra, orden, activo)
SELECT @negocio_id, o.id, 'Sin cebolla', 0, 20, 1 FROM producto_opciones o WHERE o.negocio_id = @negocio_id AND o.nombre = 'Extras'
ON DUPLICATE KEY UPDATE precio_extra = VALUES(precio_extra), orden = VALUES(orden), activo = 1;
