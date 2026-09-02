<?php

declare(strict_types=1);

namespace App\Models;

final class Menu extends Model
{
    public function categories(): array
    {
        $stmt = $this->db()->prepare('SELECT id, nombre FROM categorias WHERE negocio_id = ? AND activo = 1 ORDER BY orden, nombre');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function products(): array
    {
        $stmt = $this->db()->prepare('SELECT id, categoria_id, nombre, descripcion, precio, imagen_thumb FROM productos WHERE negocio_id = ? AND disponible = 1 ORDER BY orden, nombre');
        $stmt->execute([$this->negocioId()]);
        $products = $stmt->fetchAll();

        $options = $this->options();
        foreach ($products as &$product) {
            $product['opciones'] = $options[(int) $product['id']] ?? [];
        }

        return $products;
    }

    public function product(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT id, nombre, descripcion, precio FROM productos WHERE id = ? AND negocio_id = ? AND disponible = 1 LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        $product = $stmt->fetch() ?: null;
        if ($product) {
            $product['opciones'] = $this->options($id)[$id] ?? [];
        }
        return $product;
    }

    private function options(?int $productId = null): array
    {
        $sql = 'SELECT o.id opcion_id, o.producto_id, o.nombre opcion_nombre, o.tipo, o.requerida, v.id valor_id, v.nombre valor_nombre, v.precio_extra
            FROM producto_opciones o
            LEFT JOIN producto_opcion_valores v ON v.producto_opcion_id = o.id AND v.negocio_id = o.negocio_id AND v.activo = 1
            WHERE o.negocio_id = ? AND o.activo = 1';
        $params = [$this->negocioId()];
        if ($productId !== null) {
            $sql .= ' AND o.producto_id = ?';
            $params[] = $productId;
        }
        $sql .= ' ORDER BY o.orden, v.orden, v.nombre';

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $pid = (int) $row['producto_id'];
            $oid = (int) $row['opcion_id'];
            $grouped[$pid][$oid] ??= [
                'id' => $oid,
                'nombre' => $row['opcion_nombre'],
                'tipo' => $row['tipo'],
                'requerida' => (bool) $row['requerida'],
                'valores' => [],
            ];
            if ($row['valor_id']) {
                $grouped[$pid][$oid]['valores'][] = [
                    'id' => (int) $row['valor_id'],
                    'nombre' => $row['valor_nombre'],
                    'precio_extra' => (float) $row['precio_extra'],
                ];
            }
        }

        foreach ($grouped as &$productOptions) {
            $productOptions = array_values($productOptions);
        }

        return $grouped;
    }
}
