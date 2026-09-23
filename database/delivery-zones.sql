-- Migración incremental: zonas de entrega por sucursal
-- Aplica esta migración si tu base fue creada antes del módulo de delivery.

ALTER TABLE zonas_entrega
  ADD COLUMN IF NOT EXISTS sucursal_id BIGINT UNSIGNED NULL AFTER negocio_id,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Renombrar costo a costo_envio solo si aún no existe costo_envio
ALTER TABLE zonas_entrega
  CHANGE COLUMN costo costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0;

-- Permitir pedido_minimo nulo
ALTER TABLE zonas_entrega
  MODIFY COLUMN pedido_minimo DECIMAL(10,2) NULL;

-- Asignar la primera sucursal de cada negocio a las zonas existentes que no tengan sucursal
UPDATE zonas_entrega z
  JOIN sucursales s ON s.negocio_id = z.negocio_id
  SET z.sucursal_id = s.id
  WHERE z.sucursal_id IS NULL;

-- Si aún quedan zonas sin sucursal (negocio sin sucursales), desactívalas para evitar errores
UPDATE zonas_entrega
  SET activa = 0
  WHERE sucursal_id IS NULL;

-- Ahora forzar NOT NULL en sucursal_id
ALTER TABLE zonas_entrega
  MODIFY COLUMN sucursal_id BIGINT UNSIGNED NOT NULL,
  DROP INDEX IF EXISTS zonas_entrega_negocio_nombre_unique,
  ADD UNIQUE KEY zonas_entrega_sucursal_nombre_unique (sucursal_id, nombre),
  ADD INDEX zonas_entrega_negocio_idx (negocio_id),
  ADD INDEX zonas_entrega_sucursal_idx (sucursal_id),
  ADD CONSTRAINT zonas_entrega_sucursal_fk FOREIGN KEY (sucursal_id) REFERENCES sucursales(id);
