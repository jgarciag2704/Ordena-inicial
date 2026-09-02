<?php

declare(strict_types=1);

namespace App\Models;

final class Business extends Model
{
    public function findActiveBySlug(string $slug): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM negocios WHERE slug = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$slug]);
        return $stmt->fetch() ?: null;
    }
}
