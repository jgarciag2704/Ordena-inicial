<?php

declare(strict_types=1);

namespace App\Models;

final class SuperAdminBusiness extends Model
{
    public function allActive(): array
    {
        $stmt = $this->db()->query('SELECT n.*, COUNT(p.id) pedidos_count FROM negocios n LEFT JOIN pedidos p ON p.negocio_id = n.id WHERE n.activo = 1 GROUP BY n.id ORDER BY n.created_at DESC');
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $db = $this->db();
        $db->beginTransaction();

        try {
            $business = $db->prepare('INSERT INTO negocios (nombre, slug, folio_prefijo, folio_consecutivo, activo) VALUES (?, ?, ?, 1000, 1)');
            $business->execute([$data['nombre'], $data['slug'], $data['folio_prefijo']]);
            $businessId = (int) $db->lastInsertId();

            $branch = $db->prepare('INSERT INTO sucursales (negocio_id, nombre, direccion, telefono, activa) VALUES (?, ?, ?, ?, 1)');
            $branch->execute([$businessId, $data['sucursal_nombre'], $data['sucursal_direccion'], $data['sucursal_telefono'] ?: null]);
            $branchId = (int) $db->lastInsertId();

            $hours = $db->prepare('INSERT INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado) VALUES (?, ?, ?, ?, "10:00", "22:00", 0)');
            foreach (['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'] as $index => $label) {
                $hours->execute([$businessId, $branchId, $index + 1, $label]);
            }

            $user = $db->prepare('INSERT INTO usuarios (negocio_id, nombre, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, "admin", 1)');
            $user->execute([$businessId, $data['admin_nombre'], $data['admin_email'], password_hash($data['admin_password'], PASSWORD_DEFAULT)]);

            $db->commit();
            return $businessId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function resetAdminPassword(int $businessId): ?string
    {
        $stmt = $this->db()->prepare('SELECT u.id, u.email FROM usuarios u JOIN negocios n ON n.id = u.negocio_id WHERE u.negocio_id = ? AND n.activo = 1 AND u.rol = "admin" AND u.activo = 1 ORDER BY u.id LIMIT 1');
        $stmt->execute([$businessId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return null;
        }

        $update = $this->db()->prepare('UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 1 WHERE id = ? AND negocio_id = ?');
        $update->execute([password_hash('Temporal1', PASSWORD_DEFAULT), $admin['id'], $businessId]);

        return $admin['email'];
    }
}
