<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Order extends Model
{
    public const STATUS_LABELS = [
        'nuevo' => 'Nuevo',
        'confirmado' => 'Confirmado',
        'preparacion' => 'En preparación',
        'listo' => 'Listo',
        'camino' => 'En camino',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];

    private const STATUS_FLOW = [
        'nuevo' => ['confirmado', 'cancelado'],
        'confirmado' => ['preparacion', 'cancelado'],
        'preparacion' => ['listo', 'cancelado'],
        'listo' => ['camino', 'cancelado'],
        'camino' => ['entregado', 'cancelado'],
        'entregado' => [],
        'cancelado' => [],
    ];

    public static function statuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? $status;
    }

    public static function nextStatuses(string $status, ?string $type = null): array
    {
        return self::STATUS_FLOW[$status] ?? [];
    }

    public function create(array $checkout, array $cart): array
    {
        $db = $this->db();
        $db->beginTransaction();

        try {
            $branchId = (int) $checkout['branch_id'];
            $customerId = !empty($checkout['cliente_id'])
                ? (int) $checkout['cliente_id']
                : (new Customer($this->app))->findOrCreate($checkout['name'], $checkout['phone']);
            $folio = $this->nextFolio();
            $subtotal = array_reduce($cart, fn (float $sum, array $item): float => $sum + (float) $item['total'], 0.0);
            $isDelivery = $checkout['mode'] === 'delivery';
            $deliveryFee = $isDelivery ? (float) ($checkout['delivery_fee'] ?? 0) : 0.0;
            $total = (float) ($checkout['total'] ?? ($subtotal + $deliveryFee));

            $addressParts = $isDelivery ? $this->buildAddress($checkout) : null;
            $zoneSnapshot = $checkout['zone_snapshot'] ?? null;

            $stmt = $db->prepare('INSERT INTO pedidos (negocio_id, sucursal_id, cliente_id, folio, tipo, estado, forma_pago, direccion_entrega, direccion_calle, direccion_numero, direccion_colonia, direccion_referencias, direccion_latitud, direccion_longitud, distancia_entrega_km, zona_entrega_id, zona_entrega_nombre_snapshot, costo_envio_snapshot, pedido_minimo_snapshot, sucursal_latitud_snapshot, sucursal_longitud_snapshot, mesa, efectivo_con, cambio_estimado, total) VALUES (?, ?, ?, ?, ?, "nuevo", ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $this->negocioId(),
                $branchId,
                $customerId,
                $folio,
                $checkout['mode'],
                $isDelivery ? 'efectivo_entrega' : 'pago_sucursal',
                $addressParts ? $addressParts['full'] : ($checkout['address'] ?: null),
                $addressParts ? $addressParts['calle'] : null,
                $addressParts ? $addressParts['numero'] : null,
                $addressParts ? $addressParts['colonia'] : null,
                $addressParts ? $addressParts['referencias'] : null,
                $isDelivery && !empty($checkout['delivery_lat']) ? (float) $checkout['delivery_lat'] : null,
                $isDelivery && !empty($checkout['delivery_lon']) ? (float) $checkout['delivery_lon'] : null,
                $isDelivery && !empty($checkout['distance_km']) ? (float) $checkout['distance_km'] : null,
                $isDelivery ? ($checkout['zone_id'] ?: null) : null,
                $zoneSnapshot ? $zoneSnapshot['nombre'] : null,
                $deliveryFee,
                $isDelivery && isset($checkout['min_order']) && $checkout['min_order'] > 0 ? (float) $checkout['min_order'] : null,
                $isDelivery && !empty($checkout['branch_lat']) ? (float) $checkout['branch_lat'] : null,
                $isDelivery && !empty($checkout['branch_lon']) ? (float) $checkout['branch_lon'] : null,
                $checkout['table'] ?: null,
                $isDelivery ? ($checkout['cash_amount'] ?: null) : null,
                $isDelivery ? ($checkout['change_amount'] ?? 0.0) : null,
                $total,
            ]);
            $orderId = (int) $db->lastInsertId();

            foreach ($cart as $item) {
                $detail = $db->prepare('INSERT INTO pedido_detalles (negocio_id, pedido_id, producto_id, nombre_snapshot, precio_unitario_snapshot, total, notas) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $detail->execute([
                    $this->negocioId(),
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['base_price'],
                    $item['total'],
                    $item['notes'] ?: null,
                ]);
                $detailId = (int) $db->lastInsertId();

                foreach ($item['options'] as $option) {
                    $opt = $db->prepare('INSERT INTO pedido_detalle_opciones (negocio_id, pedido_detalle_id, opcion_nombre_snapshot, valor_nombre_snapshot, precio_extra_snapshot) VALUES (?, ?, ?, ?, ?)');
                    $opt->execute([$this->negocioId(), $detailId, $option['option_name'], $option['value_name'], $option['price_extra']]);
                }
            }

            $db->commit();
            return ['id' => $orderId, 'folio' => $folio, 'total' => $total];
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private function buildAddress(array $checkout): array
    {
        $parts = [
            'calle' => $checkout['calle'] ?? '',
            'numero' => $checkout['numero'] ?? '',
            'colonia' => $checkout['colonia'] ?? '',
            'referencias' => $checkout['referencias'] ?? '',
        ];

        $full = trim($parts['calle'] . ' ' . $parts['numero'] . ', ' . $parts['colonia'], ', ');
        if ($parts['referencias'] !== '') {
            $full .= ' (' . $parts['referencias'] . ')';
        }

        return array_merge($parts, ['full' => $full]);
    }

    public function all(): array
    {
        $stmt = $this->db()->prepare('SELECT p.*, c.nombre cliente_nombre, c.telefono cliente_telefono FROM pedidos p JOIN clientes c ON c.id = p.cliente_id AND c.negocio_id = p.negocio_id WHERE p.negocio_id = ? ORDER BY p.created_at DESC');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function forCustomer(int $customerId): array
    {
        $stmt = $this->db()->prepare('SELECT p.* FROM pedidos p WHERE p.negocio_id = ? AND p.cliente_id = ? ORDER BY p.created_at DESC');
        $stmt->execute([$this->negocioId(), $customerId]);
        return $stmt->fetchAll();
    }

    public function findForCustomerByFolio(int $customerId, string $folio): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM pedidos WHERE negocio_id = ? AND cliente_id = ? AND folio = ? LIMIT 1');
        $stmt->execute([$this->negocioId(), $customerId, $folio]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT p.*, c.nombre cliente_nombre, c.telefono cliente_telefono FROM pedidos p JOIN clientes c ON c.id = p.cliente_id AND c.negocio_id = p.negocio_id WHERE p.id = ? AND p.negocio_id = ? LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }

        $details = $this->db()->prepare('SELECT * FROM pedido_detalles WHERE pedido_id = ? AND negocio_id = ? ORDER BY id');
        $details->execute([$id, $this->negocioId()]);
        $order['detalles'] = $details->fetchAll();

        $options = $this->db()->prepare('SELECT * FROM pedido_detalle_opciones WHERE negocio_id = ? AND pedido_detalle_id IN (SELECT id FROM pedido_detalles WHERE pedido_id = ? AND negocio_id = ?) ORDER BY id');
        $options->execute([$this->negocioId(), $id, $this->negocioId()]);
        $byDetail = [];
        foreach ($options->fetchAll() as $option) {
            $byDetail[(int) $option['pedido_detalle_id']][] = $option;
        }
        foreach ($order['detalles'] as &$detail) {
            $detail['opciones'] = $byDetail[(int) $detail['id']] ?? [];
        }

        return $order;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!array_key_exists($status, self::STATUS_LABELS)) {
            return false;
        }

        $current = $this->db()->prepare('SELECT estado, tipo FROM pedidos WHERE id = ? AND negocio_id = ? LIMIT 1');
        $current->execute([$id, $this->negocioId()]);
        $order = $current->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            return false;
        }

        if ($status === $order['estado']) {
            return true;
        }

        if (!in_array($status, self::nextStatuses((string) $order['estado'], (string) $order['tipo']), true)) {
            return false;
        }

        $stmt = $this->db()->prepare('UPDATE pedidos SET estado = ? WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$status, $id, $this->negocioId()]);
        return $stmt->rowCount() > 0;
    }

    private function defaultBranchId(): int
    {
        $stmt = $this->db()->prepare('SELECT id FROM sucursales WHERE negocio_id = ? AND activa = 1 ORDER BY id LIMIT 1');
        $stmt->execute([$this->negocioId()]);
        return (int) $stmt->fetchColumn();
    }

    private function nextFolio(): string
    {
        $stmt = $this->db()->prepare('SELECT folio_prefijo, folio_consecutivo FROM negocios WHERE id = ? FOR UPDATE');
        $stmt->execute([$this->negocioId()]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        $next = (int) $business['folio_consecutivo'] + 1;

        $update = $this->db()->prepare('UPDATE negocios SET folio_consecutivo = ? WHERE id = ?');
        $update->execute([$next, $this->negocioId()]);

        return $business['folio_prefijo'] . '-' . $next;
    }
}
