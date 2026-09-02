<?php

declare(strict_types=1);

namespace App\Models;

final class User extends Model
{
    public function findAdminByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM usuarios WHERE negocio_id = ? AND email = ? AND rol = "admin" AND activo = 1 LIMIT 1');
        $stmt->execute([$this->negocioId(), $email]);
        return $stmt->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM usuarios WHERE id = ? AND negocio_id = ? AND rol = "admin" AND activo = 1 LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        return $stmt->fetch() ?: null;
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->db()->prepare('UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 0 WHERE id = ? AND negocio_id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id, $this->negocioId()]);
    }
}
