<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use PDO;

abstract class Model
{
    public function __construct(protected readonly App $app)
    {
    }

    protected function db(): PDO
    {
        return $this->app->db();
    }

    protected function negocioId(): int
    {
        return $this->app->tenant()->id();
    }
}
