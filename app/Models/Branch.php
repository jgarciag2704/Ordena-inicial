<?php

declare(strict_types=1);

namespace App\Models;

final class Branch extends Model
{
    public function publicList(): array
    {
        $day = (int) date('N');
        $time = date('H:i:s');
        $stmt = $this->db()->prepare('SELECT s.id, s.nombre, s.direccion, s.telefono, h.abre, h.cierra, h.cerrado,
            CASE WHEN h.cerrado = 0 AND h.abre IS NOT NULL AND h.cierra IS NOT NULL AND ? BETWEEN h.abre AND h.cierra THEN 1 ELSE 0 END abierta
            FROM sucursales s
            LEFT JOIN sucursal_horarios h ON h.sucursal_id = s.id AND h.negocio_id = s.negocio_id AND h.dia_semana = ?
            WHERE s.negocio_id = ? AND s.activa = 1
            ORDER BY abierta DESC, s.nombre');
        $stmt->execute([$time, $day, $this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function isOpen(int $id): bool
    {
        $day = (int) date('N');
        $time = date('H:i:s');
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM sucursales s
            JOIN sucursal_horarios h ON h.sucursal_id = s.id AND h.negocio_id = s.negocio_id AND h.dia_semana = ?
            WHERE s.id = ? AND s.negocio_id = ? AND s.activa = 1 AND h.cerrado = 0 AND ? BETWEEN h.abre AND h.cierra');
        $stmt->execute([$day, $id, $this->negocioId(), $time]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
