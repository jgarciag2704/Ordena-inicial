-- Migración incremental: ubicación de sucursales para cálculo de delivery

ALTER TABLE sucursales
  ADD COLUMN IF NOT EXISTS latitud DECIMAL(10,8) NULL,
  ADD COLUMN IF NOT EXISTS longitud DECIMAL(11,8) NULL,
  ADD COLUMN IF NOT EXISTS direccion_referencia VARCHAR(255) NULL;
