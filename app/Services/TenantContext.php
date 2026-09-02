<?php

declare(strict_types=1);

namespace App\Services;

final class TenantContext
{
    private ?array $business = null;

    public function set(array $business): void
    {
        $this->business = $business;
    }

    public function get(): ?array
    {
        return $this->business;
    }

    public function id(): int
    {
        if (!$this->business) {
            throw new \RuntimeException('Tenant no resuelto.');
        }

        return (int) $this->business['id'];
    }
}
