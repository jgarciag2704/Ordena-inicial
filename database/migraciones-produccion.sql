-- =============================================================
-- MIGRACIÓN PRODUCCIÓN — compila TODOS los cambios pendientes
-- -------------------------------------------------------------
-- Lleva una BD creada con database/schema.sql ORIGINAL
-- (sin módulo de delivery ni cuentas de cliente) a la versión
-- actual del código:
--   · ubicación de sucursales (cálculo de envío)
--   · zonas de entrega por sucursal (manuales y por radio)
--   · campos de delivery y pago en efectivo en pedidos
--   · snapshots de entrega (coordenadas, distancia, envío)
--   · cuentas de cliente (login por teléfono + contraseña)
--
-- ADVERTENCIA: EJECUTAR UNA SOLA VEZ, en orden, contra la BD.
-- No es 100% idempotente (CHANGE COLUMN y ADD CONSTRAINT).
-- Compatible con MariaDB 10.5+.
-- =============================================================

-- -------------------------------------------------------------
-- 1) UBICACIÓN DE SUCURSALES
-- -------------------------------------------------------------
ALTER TABLE sucursales
  ADD COLUMN IF NOT EXISTS latitud DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS longitud DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_referencia VARCHAR(255) NULL;

-- Opcional: cargar coordenadas de la sucursal existente
-- (reemplaza por las coordenadas reales de tu local).
-- UPDATE sucursales
--   SET latitud = 19.43260000, longitud = -99.13320000
-- WHERE nombre = 'Sucursal Centro' AND latitud IS NULL;

-- -------------------------------------------------------------
-- 2) ZONAS DE ENTREGA POR SUCURSAL
-- -------------------------------------------------------------
ALTER TABLE zonas_entrega
  ADD COLUMN IF NOT EXISTS sucursal_id BIGINT UNSIGNED NULL AFTER negocio_id,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Renombra costo -> costo_envio (falla si ya se ejecutó antes)
ALTER TABLE zonas_entrega
  CHANGE COLUMN costo costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Permite pedido_minimo nulo
ALTER TABLE zonas_entrega
  MODIFY COLUMN pedido_minimo DECIMAL(10,2) NULL;

-- Asigna la primera sucursal de cada negocio a las zonas existentes
UPDATE zonas_entrega z
  JOIN sucursales s ON s.negocio_id = z.negocio_id
  SET z.sucursal_id = s.id
  WHERE z.sucursal_id IS NULL;

-- Desactiva zonas sin sucursal asignable
UPDATE zonas_entrega
  SET activa = 0
  WHERE sucursal_id IS NULL;

-- Obliga sucursal_id y ajusta índices/llaves
ALTER TABLE zonas_entrega
  MODIFY COLUMN sucursal_id BIGINT UNSIGNED NOT NULL,
  DROP INDEX IF EXISTS zonas_entrega_negocio_nombre_unique,
  ADD UNIQUE KEY zonas_entrega_sucursal_nombre_unique (sucursal_id, nombre),
  ADD INDEX zonas_entrega_negocio_idx (negocio_id),
  ADD INDEX zonas_entrega_sucursal_idx (sucursal_id),
  ADD CONSTRAINT zonas_entrega_sucursal_fk FOREIGN KEY (sucursal_id) REFERENCES sucursales(id);

-- -------------------------------------------------------------
-- 3) ZONAS DE ENTREGA POR RADIO
-- -------------------------------------------------------------
ALTER TABLE zonas_entrega
  ADD COLUMN IF NOT EXISTS tipo_zona ENUM('manual','radio') NOT NULL DEFAULT 'manual',
  ADD COLUMN IF NOT EXISTS radio_desde_km DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS radio_hasta_km DECIMAL(6,2) NULL;

-- -------------------------------------------------------------
-- 4) PEDIDOS: DELIVERY Y PAGO EN EFECTIVO
-- -------------------------------------------------------------
ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS direccion_calle VARCHAR(120) NULL AFTER direccion_entrega,
  ADD COLUMN IF NOT EXISTS direccion_numero VARCHAR(40) NULL AFTER direccion_calle,
  ADD COLUMN IF NOT EXISTS direccion_colonia VARCHAR(120) NULL AFTER direccion_numero,
  ADD COLUMN IF NOT EXISTS direccion_referencias TEXT NULL AFTER direccion_colonia,
  ADD COLUMN IF NOT EXISTS zona_entrega_id BIGINT UNSIGNED NULL AFTER direccion_referencias,
  ADD COLUMN IF NOT EXISTS zona_entrega_nombre_snapshot VARCHAR(120) NULL AFTER zona_entrega_id,
  ADD COLUMN IF NOT EXISTS costo_envio_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER zona_entrega_nombre_snapshot,
  ADD COLUMN IF NOT EXISTS efectivo_con DECIMAL(10,2) NULL AFTER mesa,
  ADD COLUMN IF NOT EXISTS cambio_estimado DECIMAL(10,2) NULL AFTER efectivo_con,
  ADD CONSTRAINT pedidos_zona_entrega_fk FOREIGN KEY (zona_entrega_id) REFERENCES zonas_entrega(id);

-- -------------------------------------------------------------
-- 5) PEDIDOS: SNAPSHOTS DE ENTREGA
-- -------------------------------------------------------------
ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS sucursal_latitud_snapshot DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS sucursal_longitud_snapshot DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_latitud DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_longitud DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS distancia_entrega_km DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS pedido_minimo_snapshot DECIMAL(10,2) NULL;

-- -------------------------------------------------------------
-- 6) CUENTAS DE CLIENTE
-- -------------------------------------------------------------
ALTER TABLE clientes
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER telefono,
  ADD COLUMN contrasena_set_en DATETIME NULL AFTER telefono_verificado_en;