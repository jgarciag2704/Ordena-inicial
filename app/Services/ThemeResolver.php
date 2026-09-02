<?php

declare(strict_types=1);

namespace App\Services;

final class ThemeResolver
{
    public const THEMES = [
        'burger' => 'Hamburgueseria urbana',
        'cafe' => 'Cafeteria / reposteria',
        'mexicana' => 'Mexicana / casera',
        'sushi' => 'Sushi / asiatica',
        'premium' => 'Restaurante premium',
    ];

    public const DEFAULT = 'burger';

    public function resolve(?string $theme): string
    {
        $theme = strtolower(trim((string) $theme));
        return array_key_exists($theme, self::THEMES) ? $theme : self::DEFAULT;
    }
}
