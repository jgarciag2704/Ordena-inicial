<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Branch;
use App\Models\DeliveryZone;

final class DeliveryCalculator
{
    public function __construct(private readonly App $app)
    {
    }

    /**
     * Calcula distancia, zona aplicable, costo de envío y pedido mínimo.
     * Devuelve null si la sucursal no tiene coordenadas o no hay zona aplicable.
     */
    public function calculate(int $branchId, float $destinationLat, float $destinationLon): ?array
    {
        $branch = (new Branch($this->app))->find($branchId);
        if (!$branch) {
            throw new \InvalidArgumentException('La sucursal no existe.');
        }

        $branchLat = $branch['latitud'] !== null ? (float) $branch['latitud'] : null;
        $branchLon = $branch['longitud'] !== null ? (float) $branch['longitud'] : null;

        if ($branchLat === null || $branchLon === null) {
            return null;
        }

        $distanceKm = DistanceCalculator::between($branchLat, $branchLon, $destinationLat, $destinationLon);
        $zone = (new DeliveryZone($this->app))->findForDistance($branchId, $distanceKm);

        if (!$zone) {
            return [
                'branch' => $branch,
                'distance_km' => $distanceKm,
                'zone' => null,
                'delivery_fee' => 0.0,
                'min_order' => 0.0,
                'applicable' => false,
            ];
        }

        return [
            'branch' => $branch,
            'distance_km' => $distanceKm,
            'zone' => $zone,
            'delivery_fee' => (float) $zone['costo_envio'],
            'min_order' => $zone['pedido_minimo'] !== null ? (float) $zone['pedido_minimo'] : 0.0,
            'applicable' => true,
        ];
    }
}
