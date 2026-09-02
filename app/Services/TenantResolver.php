<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Business;

final class TenantResolver
{
    public function __construct(private readonly App $app)
    {
    }

    public function resolve(): ?array
    {
        $slug = $this->slugFromRequest();
        if (!$slug) {
            return null;
        }

        $business = (new Business($this->app))->findActiveBySlug($slug);
        if ($business) {
            $this->app->tenant()->set($business);
        }

        return $business;
    }

    private function slugFromRequest(): ?string
    {
        if (!empty($_GET['tenant'])) {
            return $this->cleanSlug((string) $_GET['tenant']);
        }

        $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (str_ends_with($host, '.ordena.localhost')) {
            return $this->cleanSlug(substr($host, 0, -strlen('.ordena.localhost')));
        }

        if (str_ends_with($host, '.ordena.garciacore.com')) {
            return $this->cleanSlug(substr($host, 0, -strlen('.ordena.garciacore.com')));
        }

        $parts = explode('.', $host);
        return count($parts) > 2 ? $this->cleanSlug($parts[0]) : null;
    }

    private function cleanSlug(string $slug): ?string
    {
        $slug = strtolower(trim($slug));
        return preg_match('/^[a-z0-9-]{2,80}$/', $slug) ? $slug : null;
    }
}
