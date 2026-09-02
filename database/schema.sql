SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS pedido_detalle_opciones;
DROP TABLE IF EXISTS pedido_detalles;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS verificaciones_telefono;
DROP TABLE IF EXISTS sucursal_horarios;
DROP TABLE IF EXISTS negocio_horarios;
DROP TABLE IF EXISTS zonas_entrega;
DROP TABLE IF EXISTS producto_opcion_valores;
DROP TABLE IF EXISTS producto_opciones;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS sucursales;
DROP TABLE IF EXISTS super_admins;
DROP TABLE IF EXISTS negocios;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE super_admins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE negocios (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE,
  folio_prefijo VARCHAR(12) NOT NULL,
  folio_consecutivo INT UNSIGNED NOT NULL DEFAULT 1000,
  theme_key ENUM('burger','cafe','mexicana','sushi','premium') NOT NULL DEFAULT 'burger',
  color_primario VARCHAR(7) NOT NULL DEFAULT '#cc4b25',
  color_secundario VARCHAR(7) NOT NULL DEFAULT '#2b201b',
  color_fondo VARCHAR(7) NOT NULL DEFAULT '#fffaf4',
  color_texto VARCHAR(7) NOT NULL DEFAULT '#171514',
  fuente VARCHAR(80) NOT NULL DEFAULT 'Inter, system-ui, sans-serif',
  hero_titulo VARCHAR(160) NULL,
  hero_subtitulo VARCHAR(255) NULL,
  comer_aqui_url VARCHAR(255) NULL,
  hero_image_url VARCHAR(255) NULL,
  hero_overlay_color VARCHAR(7) NOT NULL DEFAULT '#000000',
  hero_overlay_opacity DECIMAL(3,2) NOT NULL DEFAULT 0.35,
  hero_blur TINYINT UNSIGNED NOT NULL DEFAULT 0,
  fondo_estilo ENUM('claro','calido','oscuro','degradado') NOT NULL DEFAULT 'calido',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sucursales (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  direccion VARCHAR(255) NOT NULL,
  telefono VARCHAR(20) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY sucursales_negocio_nombre_unique (negocio_id, nombre),
  INDEX sucursales_negocio_idx (negocio_id),
  CONSTRAINT sucursales_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0,
  rol ENUM('admin') NOT NULL DEFAULT 'admin',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY usuarios_negocio_email_unique (negocio_id, email),
  CONSTRAINT usuarios_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  telefono_verificado_en DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY clientes_negocio_telefono_unique (negocio_id, telefono),
  CONSTRAINT clientes_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  orden INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY categorias_negocio_nombre_unique (negocio_id, nombre),
  CONSTRAINT categorias_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE productos (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  categoria_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(140) NOT NULL,
  descripcion VARCHAR(255) NULL,
  precio DECIMAL(10,2) NOT NULL,
  imagen_thumb VARCHAR(255) NULL,
  disponible TINYINT(1) NOT NULL DEFAULT 1,
  orden INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY productos_negocio_nombre_unique (negocio_id, nombre),
  INDEX productos_negocio_categoria_idx (negocio_id, categoria_id),
  CONSTRAINT productos_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT productos_categoria_fk FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_opciones (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  tipo ENUM('multiple','unica','texto') NOT NULL DEFAULT 'multiple',
  requerida TINYINT(1) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY producto_opciones_negocio_producto_nombre_unique (negocio_id, producto_id, nombre),
  INDEX producto_opciones_negocio_producto_idx (negocio_id, producto_id),
  CONSTRAINT producto_opciones_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT producto_opciones_producto_fk FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE producto_opcion_valores (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  producto_opcion_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  precio_extra DECIMAL(10,2) NOT NULL DEFAULT 0,
  orden INT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY producto_opcion_valores_negocio_opcion_nombre_unique (negocio_id, producto_opcion_id, nombre),
  INDEX producto_opcion_valores_negocio_opcion_idx (negocio_id, producto_opcion_id),
  CONSTRAINT producto_opcion_valores_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT producto_opcion_valores_opcion_fk FOREIGN KEY (producto_opcion_id) REFERENCES producto_opciones(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedidos (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NOT NULL,
  cliente_id BIGINT UNSIGNED NOT NULL,
  folio VARCHAR(24) NOT NULL,
  tipo ENUM('pickup','mesa','delivery') NOT NULL,
  estado ENUM('nuevo','confirmado','preparacion','listo','camino','entregado','cancelado') NOT NULL DEFAULT 'nuevo',
  forma_pago ENUM('pago_sucursal','efectivo_entrega') NOT NULL,
  direccion_entrega TEXT NULL,
  mesa VARCHAR(30) NULL,
  total DECIMAL(10,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY pedidos_negocio_folio_unique (negocio_id, folio),
  INDEX pedidos_negocio_estado_idx (negocio_id, estado),
  CONSTRAINT pedidos_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT pedidos_sucursal_fk FOREIGN KEY (sucursal_id) REFERENCES sucursales(id),
  CONSTRAINT pedidos_cliente_fk FOREIGN KEY (cliente_id) REFERENCES clientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalles (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  pedido_id BIGINT UNSIGNED NOT NULL,
  producto_id BIGINT UNSIGNED NULL,
  nombre_snapshot VARCHAR(140) NOT NULL,
  precio_unitario_snapshot DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  notas TEXT NULL,
  INDEX pedido_detalles_negocio_pedido_idx (negocio_id, pedido_id),
  CONSTRAINT pedido_detalles_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT pedido_detalles_pedido_fk FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
  CONSTRAINT pedido_detalles_producto_fk FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pedido_detalle_opciones (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  pedido_detalle_id BIGINT UNSIGNED NOT NULL,
  opcion_nombre_snapshot VARCHAR(120) NOT NULL,
  valor_nombre_snapshot VARCHAR(120) NOT NULL,
  precio_extra_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0,
  INDEX pedido_detalle_opciones_negocio_detalle_idx (negocio_id, pedido_detalle_id),
  CONSTRAINT pedido_detalle_opciones_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT pedido_detalle_opciones_detalle_fk FOREIGN KEY (pedido_detalle_id) REFERENCES pedido_detalles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE zonas_entrega (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  costo DECIMAL(10,2) NOT NULL DEFAULT 0,
  pedido_minimo DECIMAL(10,2) NOT NULL DEFAULT 0,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY zonas_entrega_negocio_nombre_unique (negocio_id, nombre),
  CONSTRAINT zonas_entrega_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE negocio_horarios (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  dia_semana TINYINT UNSIGNED NOT NULL,
  dia_nombre VARCHAR(20) NOT NULL,
  abre TIME NULL,
  cierra TIME NULL,
  cerrado TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY negocio_horarios_negocio_dia_unique (negocio_id, dia_semana),
  CONSTRAINT negocio_horarios_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sucursal_horarios (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  sucursal_id BIGINT UNSIGNED NOT NULL,
  dia_semana TINYINT UNSIGNED NOT NULL,
  dia_nombre VARCHAR(20) NOT NULL,
  abre TIME NULL,
  cierra TIME NULL,
  cerrado TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY sucursal_horarios_sucursal_dia_unique (sucursal_id, dia_semana),
  INDEX sucursal_horarios_negocio_idx (negocio_id),
  CONSTRAINT sucursal_horarios_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id),
  CONSTRAINT sucursal_horarios_sucursal_fk FOREIGN KEY (sucursal_id) REFERENCES sucursales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE verificaciones_telefono (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  codigo VARCHAR(10) NOT NULL,
  verificado_en DATETIME NULL,
  expira_en DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX verificaciones_negocio_telefono_idx (negocio_id, telefono),
  CONSTRAINT verificaciones_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
