<?php

declare(strict_types=1);

namespace App\Models;

final class DeliveryZone extends Model
{
    public function allForBranch(int $branchId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega WHERE negocio_id = ? AND sucursal_id = ? ORDER BY tipo_zona, radio_desde_km, nombre');
        $stmt->execute([$this->negocioId(), $branchId]);
        return $stmt->fetchAll();
    }

    public function activeForBranch(int $branchId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega WHERE negocio_id = ? AND sucursal_id = ? AND activa = 1 ORDER BY tipo_zona, radio_desde_km, nombre');
        $stmt->execute([$this->negocioId(), $branchId]);
        return $stmt->fetchAll();
    }

    public function activeRadioZonesForBranch(int $branchId): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega WHERE negocio_id = ? AND sucursal_id = ? AND activa = 1 AND tipo_zona = "radio" ORDER BY radio_desde_km');
        $stmt->execute([$this->negocioId(), $branchId]);
        return $stmt->fetchAll();
    }

    public function hasRadioZones(int $branchId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM zonas_entrega WHERE negocio_id = ? AND sucursal_id = ? AND activa = 1 AND tipo_zona = "radio"');
        $stmt->execute([$this->negocioId(), $branchId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega WHERE id = ? AND negocio_id = ? LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        return $stmt->fetch() ?: null;
    }

    public function findActive(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega WHERE id = ? AND negocio_id = ? AND activa = 1 LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Busca la zona activa de tipo radio que corresponde a una distancia dada.
     * Si radio_hasta_km es null, el rango es abierto (desde radio_desde_km en adelante).
     */
    public function findForDistance(int $branchId, float $distanceKm): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM zonas_entrega
            WHERE negocio_id = ? AND sucursal_id = ? AND activa = 1 AND tipo_zona = "radio"
              AND radio_desde_km <= ?
              AND (radio_hasta_km IS NULL OR ? < radio_hasta_km)
            ORDER BY radio_desde_km DESC
            LIMIT 1');
        $stmt->execute([$this->negocioId(), $branchId, $distanceKm, $distanceKm]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): void
    {
        if (!$this->branchBelongsToTenant((int) $data['sucursal_id'])) {
            throw new \InvalidArgumentException('La sucursal no pertenece a este negocio.');
        }

        if ($this->nameExists((int) $data['sucursal_id'], (string) $data['nombre'])) {
            throw new \InvalidArgumentException('Ya existe una zona con ese nombre para esta sucursal.');
        }

        $this->validateRadioRange($data);

        $stmt = $this->db()->prepare('INSERT INTO zonas_entrega (negocio_id, sucursal_id, nombre, tipo_zona, radio_desde_km, radio_hasta_km, costo_envio, pedido_minimo, activa) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)');
        $stmt->execute([
            $this->negocioId(),
            $data['sucursal_id'],
            $data['nombre'],
            $data['tipo_zona'],
            $data['radio_desde_km'],
            $data['radio_hasta_km'] ?? null,
            $data['costo_envio'],
            $data['pedido_minimo'] ?: null,
        ]);
    }

    public function update(array $data): void
    {
        $zone = $this->find((int) $data['id']);
        if (!$zone) {
            throw new \InvalidArgumentException('La zona no pertenece a este negocio.');
        }

        if (!$this->branchBelongsToTenant((int) $data['sucursal_id'])) {
            throw new \InvalidArgumentException('La sucursal no pertenece a este negocio.');
        }

        if ($this->nameExists((int) $data['sucursal_id'], (string) $data['nombre'], (int) $data['id'])) {
            throw new \InvalidArgumentException('Ya existe una zona con ese nombre para esta sucursal.');
        }

        $this->validateRadioRange($data, (int) $data['id']);

        $stmt = $this->db()->prepare('UPDATE zonas_entrega SET sucursal_id = ?, nombre = ?, tipo_zona = ?, radio_desde_km = ?, radio_hasta_km = ?, costo_envio = ?, pedido_minimo = ? WHERE id = ? AND negocio_id = ?');
        $stmt->execute([
            $data['sucursal_id'],
            $data['nombre'],
            $data['tipo_zona'],
            $data['radio_desde_km'],
            $data['radio_hasta_km'] ?? null,
            $data['costo_envio'],
            $data['pedido_minimo'] ?: null,
            $data['id'],
            $this->negocioId(),
        ]);
    }

    public function toggle(int $id): void
    {
        $zone = $this->find($id);
        if (!$zone) {
            throw new \InvalidArgumentException('La zona no pertenece a este negocio.');
        }

        $stmt = $this->db()->prepare('UPDATE zonas_entrega SET activa = IF(activa = 1, 0, 1) WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$id, $this->negocioId()]);
    }

    public function branchBelongsToTenant(int $branchId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM sucursales WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$branchId, $this->negocioId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function validateRadioRange(array $data, int $excludeId = 0): void
    {
        $type = $data['tipo_zona'] ?? 'manual';
        if ($type !== 'radio') {
            return;
        }

        $from = isset($data['radio_desde_km']) && $data['radio_desde_km'] !== '' ? (float) $data['radio_desde_km'] : null;
        $to = isset($data['radio_hasta_km']) && $data['radio_hasta_km'] !== '' ? (float) $data['radio_hasta_km'] : null;

        if ($from === null || $from < 0) {
            throw new \InvalidArgumentException('El radio inicial debe ser mayor o igual a 0.');
        }

        if ($to !== null && $to <= $from) {
            throw new \InvalidArgumentException('El radio final debe ser mayor que el radio inicial.');
        }

        $stmt = $this->db()->prepare('SELECT id, radio_desde_km, radio_hasta_km FROM zonas_entrega
            WHERE negocio_id = ? AND sucursal_id = ? AND tipo_zona = "radio" AND id <> ? AND activa = 1');
        $stmt->execute([$this->negocioId(), $data['sucursal_id'], $excludeId]);

        foreach ($stmt->fetchAll() as $existing) {
            $existingFrom = (float) $existing['radio_desde_km'];
            $existingTo = $existing['radio_hasta_km'] !== null ? (float) $existing['radio_hasta_km'] : null;

            if ($this->rangesOverlap($from, $to, $existingFrom, $existingTo)) {
                throw new \InvalidArgumentException('Los rangos de entrega no pueden traslaparse con otro rango activo.');
            }
        }
    }

    private function rangesOverlap(float $from1, ?float $to1, float $from2, ?float $to2): bool
    {
        $end1 = $to1 ?? PHP_FLOAT_MAX;
        $end2 = $to2 ?? PHP_FLOAT_MAX;

        return $from1 < $end2 && $from2 < $end1;
    }

    private function nameExists(int $branchId, string $name, int $excludeId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM zonas_entrega WHERE negocio_id = ? AND sucursal_id = ? AND nombre = ? AND id <> ?');
        $stmt->execute([$this->negocioId(), $branchId, $name, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
