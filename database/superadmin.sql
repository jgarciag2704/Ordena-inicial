CREATE TABLE IF NOT EXISTS super_admins (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  nombre VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO super_admins (nombre, email, password_hash, debe_cambiar_password, activo)
VALUES ('Juan Garcia', 'jgarciag2704@gmail.com', '$2y$10$B7PX89RAaY0AgWtJcwzhDu2Mfi.5gr1APXwn.rueimREKywyIa5g2', 1, 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;
