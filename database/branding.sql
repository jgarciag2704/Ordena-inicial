ALTER TABLE negocios ADD COLUMN IF NOT EXISTS theme_key ENUM('burger','cafe','mexicana','sushi','premium') NOT NULL DEFAULT 'burger' AFTER folio_consecutivo;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS color_primario VARCHAR(7) NOT NULL DEFAULT '#cc4b25' AFTER folio_consecutivo;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS color_secundario VARCHAR(7) NOT NULL DEFAULT '#2b201b' AFTER color_primario;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS color_fondo VARCHAR(7) NOT NULL DEFAULT '#fffaf4' AFTER color_secundario;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS color_texto VARCHAR(7) NOT NULL DEFAULT '#171514' AFTER color_fondo;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS fuente VARCHAR(80) NOT NULL DEFAULT 'Inter, system-ui, sans-serif' AFTER color_texto;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_titulo VARCHAR(160) NULL AFTER fuente;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_subtitulo VARCHAR(255) NULL AFTER hero_titulo;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS comer_aqui_url VARCHAR(255) NULL AFTER hero_subtitulo;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_image_url VARCHAR(255) NULL AFTER comer_aqui_url;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_overlay_color VARCHAR(7) NOT NULL DEFAULT '#000000' AFTER hero_image_url;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_overlay_opacity DECIMAL(3,2) NOT NULL DEFAULT 0.35 AFTER hero_overlay_color;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS hero_blur TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER hero_overlay_opacity;
ALTER TABLE negocios ADD COLUMN IF NOT EXISTS fondo_estilo ENUM('claro','calido','oscuro','degradado') NOT NULL DEFAULT 'calido' AFTER hero_subtitulo;

UPDATE negocios
SET hero_titulo = COALESCE(hero_titulo, 'Tu antojo, directo del restaurante.'),
    hero_subtitulo = COALESCE(hero_subtitulo, 'Arma tu pedido como te gusta. Sin intermediarios y con atención directa.')
WHERE hero_titulo IS NULL OR hero_subtitulo IS NULL;
