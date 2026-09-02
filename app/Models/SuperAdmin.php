<?php

declare(strict_types=1);

namespace App\Models;

final class SuperAdmin extends Model
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM super_admins WHERE email = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM super_admins WHERE id = ? AND activo = 1 LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->db()->prepare('UPDATE super_admins SET password_hash = ?, debe_cambiar_password = 0, updated_at = NOW() WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
}
