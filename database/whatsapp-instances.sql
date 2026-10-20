ALTER TABLE negocios
  ADD COLUMN whatsapp_instance_name         VARCHAR(80)  NULL,
  ADD COLUMN whatsapp_instance_token        VARCHAR(120) NULL,
  ADD COLUMN whatsapp_instance_status       ENUM('disconnected','connecting','connected','error')
                                           NOT NULL DEFAULT 'disconnected',
  ADD COLUMN whatsapp_instance_phone        VARCHAR(32)  NULL,
  ADD COLUMN whatsapp_instance_connected_at DATETIME     NULL,
  ADD UNIQUE KEY negocios_whatsapp_instance_unique (whatsapp_instance_name);

CREATE TABLE whatsapp_envios (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  negocio_id BIGINT UNSIGNED NOT NULL,
  verificacion_id BIGINT UNSIGNED NULL,
  para VARCHAR(32) NOT NULL,
  texto TEXT NOT NULL,
  status ENUM('enviado','error','rechazado') NOT NULL DEFAULT 'enviado',
  respuesta TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX whatsapp_envios_negocio_idx (negocio_id),
  INDEX whatsapp_envios_verificacion_idx (verificacion_id),
  CONSTRAINT whatsapp_envios_negocio_fk FOREIGN KEY (negocio_id) REFERENCES negocios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
