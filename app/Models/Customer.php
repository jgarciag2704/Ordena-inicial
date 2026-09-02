<?php

declare(strict_types=1);

namespace App\Models;

final class Customer extends Model
{
    public function findOrCreate(string $name, string $phone): int
    {
        $stmt = $this->db()->prepare('SELECT id FROM clientes WHERE negocio_id = ? AND telefono = ? LIMIT 1');
        $stmt->execute([$this->negocioId(), $phone]);
        $id = $stmt->fetchColumn();

        if ($id) {
            $update = $this->db()->prepare('UPDATE clientes SET nombre = ?, telefono_verificado_en = NOW() WHERE id = ? AND negocio_id = ?');
            $update->execute([$name, $id, $this->negocioId()]);
            return (int) $id;
        }

        $insert = $this->db()->prepare('INSERT INTO clientes (negocio_id, nombre, telefono, telefono_verificado_en) VALUES (?, ?, ?, NOW())');
        $insert->execute([$this->negocioId(), $name, $phone]);
        return (int) $this->db()->lastInsertId();
    }
}
