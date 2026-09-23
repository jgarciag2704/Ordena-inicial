-- Migración incremental: zonas de entrega por radio de distancia

ALTER TABLE zonas_entrega
  ADD COLUMN IF NOT EXISTS tipo_zona ENUM('manual','radio') NOT NULL DEFAULT 'manual',
  ADD COLUMN IF NOT EXISTS radio_desde_km DECIMAL(6,2) NULL,
  ADD COLUMN IF NOT EXISTS radio_hasta_km DECIMAL(6,2) NULL;
