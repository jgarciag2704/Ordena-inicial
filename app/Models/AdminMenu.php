<?php

declare(strict_types=1);

namespace App\Models;

final class AdminMenu extends Model
{
    public function categories(): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM categorias WHERE negocio_id = ? ORDER BY orden, nombre');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function products(): array
    {
        $stmt = $this->db()->prepare('SELECT p.*, c.nombre categoria_nombre FROM productos p JOIN categorias c ON c.id = p.categoria_id AND c.negocio_id = p.negocio_id WHERE p.negocio_id = ? ORDER BY c.orden, p.orden, p.nombre');
        $stmt->execute([$this->negocioId()]);
        return $stmt->fetchAll();
    }

    public function optionsByProduct(): array
    {
        $stmt = $this->db()->prepare('SELECT o.id opcion_id, o.producto_id, o.nombre opcion_nombre, o.tipo, o.requerida, o.activo opcion_activa, v.id valor_id, v.nombre valor_nombre, v.precio_extra, v.activo valor_activo
            FROM producto_opciones o
            LEFT JOIN producto_opcion_valores v ON v.producto_opcion_id = o.id AND v.negocio_id = o.negocio_id
            WHERE o.negocio_id = ?
            ORDER BY o.producto_id, o.orden, o.id, v.orden, v.id');
        $stmt->execute([$this->negocioId()]);

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $productId = (int) $row['producto_id'];
            $optionId = (int) $row['opcion_id'];
            $grouped[$productId][$optionId] ??= [
                'id' => $optionId,
                'nombre' => $row['opcion_nombre'],
                'tipo' => $row['tipo'],
                'requerida' => (bool) $row['requerida'],
                'activa' => (bool) $row['opcion_activa'],
                'valores' => [],
            ];

            if ($row['valor_id']) {
                $grouped[$productId][$optionId]['valores'][] = [
                    'id' => (int) $row['valor_id'],
                    'nombre' => $row['valor_nombre'],
                    'precio_extra' => (float) $row['precio_extra'],
                    'activa' => (bool) $row['valor_activo'],
                ];
            }
        }

        foreach ($grouped as &$productOptions) {
            $productOptions = array_values($productOptions);
        }

        return $grouped;
    }

    public function createCategory(string $name): void
    {
        $stmt = $this->db()->prepare('INSERT INTO categorias (negocio_id, nombre, orden, activo) VALUES (?, ?, 100, 1) ON DUPLICATE KEY UPDATE activo = 1');
        $stmt->execute([$this->negocioId(), $name]);
    }

    public function createProduct(array $data): void
    {
        if (!$this->categoryBelongsToTenant((int) $data['categoria_id'])) {
            throw new \InvalidArgumentException('La categoría no pertenece a este negocio.');
        }

        $stmt = $this->db()->prepare('INSERT INTO productos (negocio_id, categoria_id, nombre, descripcion, precio, imagen_thumb, disponible, orden) VALUES (?, ?, ?, ?, ?, ?, 1, 100)');
        $stmt->execute([
            $this->negocioId(),
            $data['categoria_id'],
            $data['nombre'],
            $data['descripcion'] ?: null,
            $data['precio'],
            $data['imagen_thumb'] ?: null,
        ]);

        $productId = (int) $this->db()->lastInsertId();
        $option = $this->db()->prepare('INSERT INTO producto_opciones (negocio_id, producto_id, nombre, tipo, requerida, orden, activo) VALUES (?, ?, "Extras", "multiple", 0, 10, 1)');
        $option->execute([$this->negocioId(), $productId]);
        $optionId = (int) $this->db()->lastInsertId();

        $values = $this->db()->prepare('INSERT INTO producto_opcion_valores (negocio_id, producto_opcion_id, nombre, precio_extra, orden, activo) VALUES (?, ?, ?, ?, ?, 1)');
        $values->execute([$this->negocioId(), $optionId, 'Queso extra', 15, 10]);
        $values->execute([$this->negocioId(), $optionId, 'Sin cebolla', 0, 20]);

        $text = $this->db()->prepare('INSERT INTO producto_opciones (negocio_id, producto_id, nombre, tipo, requerida, orden, activo) VALUES (?, ?, "Indicaciones libres", "texto", 0, 20, 1)');
        $text->execute([$this->negocioId(), $productId]);
    }

    public function updateProduct(array $data): void
    {
        if (!$this->productBelongsToTenant((int) $data['id'])) {
            throw new \InvalidArgumentException('El producto no pertenece a este negocio.');
        }
        if (!$this->categoryBelongsToTenant((int) $data['categoria_id'])) {
            throw new \InvalidArgumentException('La categoría no pertenece a este negocio.');
        }

        $params = [
            $data['categoria_id'],
            $data['nombre'],
            $data['descripcion'] ?: null,
            $data['precio'],
        ];
        $imageSql = '';

        if (!empty($data['imagen_thumb'])) {
            $imageSql = ', imagen_thumb = ?';
            $params[] = $data['imagen_thumb'];
        }

        $params[] = $data['id'];
        $params[] = $this->negocioId();

        $stmt = $this->db()->prepare('UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?' . $imageSql . ' WHERE id = ? AND negocio_id = ?');
        $stmt->execute($params);
    }

    public function toggleProduct(int $id): void
    {
        if (!$this->productBelongsToTenant($id)) {
            throw new \InvalidArgumentException('El producto no pertenece a este negocio.');
        }

        $stmt = $this->db()->prepare('UPDATE productos SET disponible = IF(disponible = 1, 0, 1) WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$id, $this->negocioId()]);
    }

    public function createOption(array $data): void
    {
        if (!$this->productBelongsToTenant((int) $data['producto_id'])) {
            throw new \InvalidArgumentException('El producto no pertenece a este negocio.');
        }

        $stmt = $this->db()->prepare('INSERT INTO producto_opciones (negocio_id, producto_id, nombre, tipo, requerida, orden, activo) VALUES (?, ?, ?, ?, ?, 100, 1)');
        $stmt->execute([
            $this->negocioId(),
            $data['producto_id'],
            $data['nombre'],
            $data['tipo'],
            $data['requerida'] ? 1 : 0,
        ]);
    }

    public function createOptionValue(array $data): void
    {
        $optionId = (int) $data['producto_opcion_id'];
        $name = trim((string) $data['nombre']);

        if (!$this->optionBelongsToTenant($optionId)) {
            throw new \InvalidArgumentException('La opción no pertenece a este negocio.');
        }

        if ($this->optionValueExists($optionId, $name)) {
            throw new \InvalidArgumentException('Ese extra ya existe en este grupo. Usa otro nombre o edita el existente.');
        }

        $stmt = $this->db()->prepare('INSERT INTO producto_opcion_valores (negocio_id, producto_opcion_id, nombre, precio_extra, orden, activo) VALUES (?, ?, ?, ?, 100, 1)');
        $stmt->execute([
            $this->negocioId(),
            $optionId,
            $name,
            $data['precio_extra'],
        ]);
    }

    public function updateOptionValue(array $data): void
    {
        $id = (int) $data['id'];
        $name = trim((string) $data['nombre']);

        $value = $this->optionValue($id);
        if (!$value) {
            throw new \InvalidArgumentException('El extra no pertenece a este negocio.');
        }

        if ($this->optionValueExists((int) $value['producto_opcion_id'], $name, $id)) {
            throw new \InvalidArgumentException('Ya existe otro extra con ese nombre en este grupo.');
        }

        $stmt = $this->db()->prepare('UPDATE producto_opcion_valores SET nombre = ?, precio_extra = ? WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$name, $data['precio_extra'], $id, $this->negocioId()]);
    }

    public function deleteOptionValue(int $id): void
    {
        if (!$this->optionValue($id)) {
            throw new \InvalidArgumentException('El extra no pertenece a este negocio.');
        }

        $stmt = $this->db()->prepare('DELETE FROM producto_opcion_valores WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$id, $this->negocioId()]);
    }

    private function categoryBelongsToTenant(int $categoryId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM categorias WHERE id = ? AND negocio_id = ? AND activo = 1');
        $stmt->execute([$categoryId, $this->negocioId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function productBelongsToTenant(int $productId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM productos WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$productId, $this->negocioId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function optionBelongsToTenant(int $optionId): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM producto_opciones WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$optionId, $this->negocioId()]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function optionValueExists(int $optionId, string $name, int $excludeId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT COUNT(*) FROM producto_opcion_valores WHERE negocio_id = ? AND producto_opcion_id = ? AND nombre = ? AND id <> ?');
        $stmt->execute([$this->negocioId(), $optionId, $name, $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function optionValue(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM producto_opcion_valores WHERE id = ? AND negocio_id = ? LIMIT 1');
        $stmt->execute([$id, $this->negocioId()]);
        return $stmt->fetch() ?: null;
    }
}
