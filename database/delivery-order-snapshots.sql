-- Migración incremental: snapshots extendidos de delivery en pedidos

ALTER TABLE pedidos
  ADD COLUMN IF NOT EXISTS sucursal_latitud_snapshot DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS sucursal_longitud_snapshot DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_latitud DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_longitud DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS distancia_entrega_km DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS pedido_minimo_snapshot DECIMAL(10,2) NULL;
