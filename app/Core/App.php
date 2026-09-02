<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\TenantContext;
use PDO;

final class App
{
    private ?PDO $pdo = null;
    private TenantContext $tenantContext;

    public function __construct(private readonly array $config)
    {
        $this->tenantContext = new TenantContext();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $value = $this->config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function db(): PDO
    {
        if ($this->pdo === null) {
            $db = $this->config('db');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
            $this->pdo = new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $this->pdo;
    }

    public function tenant(): TenantContext
    {
        return $this->tenantContext;
    }
}
