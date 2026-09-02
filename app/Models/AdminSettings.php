<?php

declare(strict_types=1);

namespace App\Models;

final class AdminSettings extends Model
{
    public function branches(): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM sucursales WHERE negocio_id = ? ORDER BY activa DESC, nombre');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function createBranch(array $data): void
    {
        $stmt = $this->db()->prepare('INSERT INTO sucursales (negocio_id, nombre, direccion, telefono, activa) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$this->negocioId(), $data['nombre'], $data['direccion'], $data['telefono'] ?: null]);
        $this->ensureHours((int) $this->db()->lastInsertId());
    }

    public function updateBranch(array $data): void
    {
        $stmt = $this->db()->prepare('UPDATE sucursales SET nombre = ?, direccion = ?, telefono = ? WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$data['nombre'], $data['direccion'], $data['telefono'] ?: null, $data['id'], $this->negocioId()]);
    }

    public function toggleBranch(int $id): void
    {
        $stmt = $this->db()->prepare('UPDATE sucursales SET activa = IF(activa = 1, 0, 1) WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$id, $this->negocioId()]);
    }

    public function defaultBranchId(): ?int
    {
        $stmt = $this->db()->prepare('SELECT id FROM sucursales WHERE negocio_id = ? ORDER BY activa DESC, id LIMIT 1');
        $stmt->execute([$this->negocioId()]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public function branchBelongsToTenant(int $branchId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM sucursales WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$branchId, $this->negocioId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function hours(int $branchId): array
    {
        $this->ensureHours($branchId);
        $stmt = $this->db()->prepare('SELECT * FROM sucursal_horarios WHERE negocio_id = ? AND sucursal_id = ? ORDER BY dia_semana');
        $stmt->execute([$this->negocioId(), $branchId]);
        return $stmt->fetchAll();
    }

    public function updateHours(int $branchId, array $rows): void
    {
        if (!$this->branchBelongsToTenant($branchId)) {
            throw new \InvalidArgumentException('La sucursal no pertenece a este negocio.');
        }

        $this->ensureHours($branchId);
        $stmt = $this->db()->prepare('UPDATE sucursal_horarios SET abre = ?, cierra = ?, cerrado = ? WHERE negocio_id = ? AND sucursal_id = ? AND dia_semana = ?');
        foreach ($rows as $day => $row) {
            $stmt->execute([
                $row['abre'] ?: null,
                $row['cierra'] ?: null,
                !empty($row['cerrado']) ? 1 : 0,
                $this->negocioId(),
                $branchId,
                (int) $day,
            ]);
        }
    }

    private function ensureHours(int $branchId): void
    {
        if (!$this->branchBelongsToTenant($branchId)) {
            throw new \InvalidArgumentException('La sucursal no pertenece a este negocio.');
        }

        $labels = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        $stmt = $this->db()->prepare('INSERT IGNORE INTO sucursal_horarios (negocio_id, sucursal_id, dia_semana, dia_nombre, abre, cierra, cerrado) VALUES (?, ?, ?, ?, "10:00", "22:00", 0)');
        foreach ($labels as $index => $label) {
            $stmt->execute([$this->negocioId(), $branchId, $index + 1, $label]);
        }
    }
}
