<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/**
 * Servicio de caché simple con Redis como primera opción y fallback a archivos.
 * Se usa para rate limiting y cache de geocodificación.
 */
final class RedisService
{
    private ?\Redis $redis = null;
    private bool $redisAvailable = false;
    private string $fileCachePath;

    public function __construct(private readonly App $app)
    {
        $this->fileCachePath = BASE_PATH . '/storage/cache';
        $this->connect();
    }

    private function connect(): void
    {
        if (!extension_loaded('redis')) {
            return;
        }

        $host = $this->app->config('redis.host', 'redis');
        $port = (int) $this->app->config('redis.port', 6379);

        try {
            $redis = new \Redis();
            $redis->connect($host, $port, 1.0);
            $redis->ping();
            $this->redis = $redis;
            $this->redisAvailable = true;
        } catch (\Throwable $e) {
            $this->redisAvailable = false;
        }
    }

    public function isAvailable(): bool
    {
        return $this->redisAvailable;
    }

    public function get(string $key): ?array
    {
        if ($this->redisAvailable) {
            $value = $this->redis->get($key);
            return $value !== false ? $this->decode($value) : null;
        }

        return $this->fileGet($key);
    }

    public function set(string $key, array $value, int $ttlSeconds): bool
    {
        if ($this->redisAvailable) {
            return $this->redis->setex($key, $ttlSeconds, $this->encode($value));
        }

        return $this->fileSet($key, $value, $ttlSeconds);
    }

    public function acquireLock(string $key, int $ttlSeconds): bool
    {
        if ($this->redisAvailable) {
            return $this->redis->set($key, '1', ['NX', 'EX' => $ttlSeconds]) === true;
        }

        return $this->fileLock($key, $ttlSeconds);
    }

    public function releaseLock(string $key): void
    {
        if ($this->redisAvailable) {
            $this->redis->del($key);
            return;
        }

        $this->fileUnlock($key);
    }

    public function getLastRequestTime(string $key): ?float
    {
        $value = $this->get($key);
        return $value ? (float) ($value['time'] ?? 0) : null;
    }

    public function setLastRequestTime(string $key, float $time, int $ttlSeconds): void
    {
        $this->set($key, ['time' => $time], $ttlSeconds);
    }

    private function decode(string $value): ?array
    {
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function filePath(string $key): string
    {
        if (!is_dir($this->fileCachePath) && !mkdir($this->fileCachePath, 0770, true) && !is_dir($this->fileCachePath)) {
            throw new \RuntimeException('No se pudo crear el directorio de caché.');
        }
        return $this->fileCachePath . '/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $key) . '.json';
    }

    private function fileGet(string $key): ?array
    {
        $path = $this->filePath($key);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path) ?: '{}', true);
        if (!is_array($data) || ($data['expires_at'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return $data['value'] ?? null;
    }

    private function fileSet(string $key, array $value, int $ttlSeconds): bool
    {
        $path = $this->filePath($key);
        $data = ['expires_at' => time() + $ttlSeconds, 'value' => $value];
        return file_put_contents($path, $this->encode($data), LOCK_EX) !== false;
    }

    private function fileLock(string $key, int $ttlSeconds): bool
    {
        $path = $this->filePath($key . '_lock');
        if (is_file($path) && filemtime($path) + $ttlSeconds > time()) {
            return false;
        }
        @unlink($path);
        return file_put_contents($path, (string) time(), LOCK_EX) !== false;
    }

    private function fileUnlock(string $key): void
    {
        @unlink($this->filePath($key . '_lock'));
    }
}
