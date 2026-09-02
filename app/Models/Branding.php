<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\ThemeResolver;

final class Branding extends Model
{
    public const THEMES = ThemeResolver::THEMES;

    public const FONTS = [
        'Inter, system-ui, sans-serif' => 'Moderna',
        'Georgia, serif' => 'Elegante',
        'Trebuchet MS, system-ui, sans-serif' => 'Casual',
        'Arial, system-ui, sans-serif' => 'Simple',
        'Courier New, monospace' => 'Retro',
    ];

    public const BACKGROUNDS = ['claro', 'calido', 'oscuro', 'degradado'];

    public function update(array $data): void
    {
        $stmt = $this->db()->prepare('UPDATE negocios SET theme_key = ?, color_primario = ?, color_secundario = ?, color_fondo = ?, color_texto = ?, fuente = ?, hero_titulo = ?, hero_subtitulo = ?, comer_aqui_url = ?, fondo_estilo = ?, hero_image_url = CASE WHEN ? = 1 THEN NULL ELSE COALESCE(?, hero_image_url) END, hero_overlay_color = ?, hero_overlay_opacity = ?, hero_blur = ? WHERE id = ?');
        $stmt->execute([
            $data['theme_key'],
            $data['color_primario'],
            $data['color_secundario'],
            $data['color_fondo'],
            $data['color_texto'],
            $data['fuente'],
            $data['hero_titulo'],
            $data['hero_subtitulo'],
            $data['comer_aqui_url'] ?: null,
            $data['fondo_estilo'],
            $data['remove_hero_image'] ? 1 : 0,
            $data['hero_image_url'],
            $data['hero_overlay_color'],
            $data['hero_overlay_opacity'],
            $data['hero_blur'],
            $this->negocioId(),
        ]);
    }
}
