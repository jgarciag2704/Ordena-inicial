CREATE TABLE IF NOT EXISTS negocio_horarios (
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

CREATE TABLE IF NOT EXISTS sucursal_horarios (
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

INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 1, 'Lunes', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 2, 'Martes', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 3, 'Miércoles', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 4, 'Jueves', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 5, 'Viernes', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 6, 'Sábado', '10:00', '22:00', 0 FROM sucursales;
INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado)
SELECT negocio_id, id, 7, 'Domingo', '10:00', '22:00', 0 FROM sucursales;
