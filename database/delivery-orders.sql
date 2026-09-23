-- Migración incremental: campos de delivery y pago en efectivo en pedidos
-- Aplica esta migración si tu base fue creada antes del módulo de delivery.

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
