<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

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

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM clientes WHERE negocio_id = ? AND telefono = ? LIMIT 1');
        $stmt->execute([$this->negocioId(), $phone]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM clientes WHERE negocio_id = ? AND id = ? LIMIT 1');
        $stmt->execute([$this->negocioId(), $id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }

    /**
     * Crea una cuenta pendiente de verificación con su contraseña.
     */
    public function register(string $name, string $phone, string $passwordHash): int
    {
        $stmt = $this->db()->prepare('INSERT INTO clientes (negocio_id, nombre, telefono, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->execute([$this->negocioId(), $name, $phone, $passwordHash]);
        return (int) $this->db()->lastInsertId();
    }

    public function setPassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db()->prepare('UPDATE clientes SET password_hash = ?, contrasena_set_en = NOW() WHERE id = ? AND negocio_id = ?');
        $stmt->execute([$passwordHash, $id, $this->negocioId()]);
    }

    public function markVerified(string $phone): ?array
    {
        $customer = $this->findByPhone($phone);
        if (!$customer) {
            return null;
        }

        $stmt = $this->db()->prepare('UPDATE clientes SET telefono_verificado_en = NOW() WHERE id = ? AND negocio_id = ?');
        $stmt->execute([(int) $customer['id'], $this->negocioId()]);
        $customer['telefono_verificado_en'] = date('Y-m-d H:i:s');
        return $customer;
    }

    public function isVerified(array $customer): bool
    {
        return !empty($customer['telefono_verificado_en']);
    }
}