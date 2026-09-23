-- Cuentas de clientes: login por teléfono + contraseña.
-- Aplica después de schema.sql (o de cualquier base existente sin esta columna).
ALTER TABLE clientes
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER telefono,
  ADD COLUMN contrasena_set_en DATETIME NULL AFTER telefono_verificado_en;