<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Order extends Model
{
    public function create(array $checkout, array $cart): array
    {
        $db = $this->db();
        $db->beginTransaction();

        try {
            $branchId = (int) $checkout['branch_id'];
            $customerId = (new Customer($this->app))->findOrCreate($checkout['name'], $checkout['phone']);
            $folio = $this->nextFolio();
            $total = array_reduce($cart, fn (float $sum, array $item): float => $sum + (float) $item['total'], 0.0);

            $stmt = $db->prepare('INSERT INTO pedidos (negocio_id, sucursal_id, cliente_id, folio, tipo, estado, forma_pago, direccion_entrega, mesa, total) VALUES (?, ?, ?, ?, ?, "nuevo", ?, ?, ?, ?)');
            $stmt->execute([
                $this->negocioId(),
                $branchId,
                $customerId,
                $folio,
                $checkout['mode'],
                $checkout['mode'] === 'delivery' ? 'efectivo_entrega' : 'pago_sucursal',
                $checkout['address'] ?: null,
                $checkout['table'] ?: null,
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

    public function all(): array
    {
        $stmt = $this->db()->prepare('SELECT p.*, c.nombre cliente_nombre, c.telefono cliente_telefono FROM pedidos p JOIN clientes c ON c.id = p.cliente_id AND c.negocio_id = p.negocio_id WHERE p.negocio_id = ? ORDER BY p.created_at DESC');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
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
        $allowed = ['nuevo', 'confirmado', 'preparacion', 'listo', 'camino', 'entregado', 'cancelado'];
        if (!in_array($status, $allowed, true)) {
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
