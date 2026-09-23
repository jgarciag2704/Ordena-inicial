<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

final class GeocodingService
{
    private const CACHE_HIT_TTL = 30 * 24 * 60 * 60; // 30 días
    private const CACHE_MISS_TTL = 10 * 60; // 10 minutos
    private const RATE_LIMIT_SECONDS = 1;
    private const RATE_LIMIT_LOCK_TTL = 5;
    private const RATE_LIMIT_KEY = 'geocoding:last_request';

    public function __construct(private readonly App $app)
    {
    }

    /**
     * Busca direcciones por texto. Respeta rate limit global y usa caché.
     * Devuelve array de resultados o lanza excepción controlada.
     */
    public function search(string $query): array
    {
        $query = $this->normalizeQuery($query);
        if ($query === '') {
            return [];
        }

        $cacheKey = 'geocode:search:' . md5($query);
        $cached = (new RedisService($this->app))->get($cacheKey);
        if ($cached !== null) {
            return $cached['results'];
        }

        $this->enforceRateLimit();

        $url = $this->buildUrl('/search', [
            'format' => 'json',
            'limit' => '5',
            'q' => $query,
        ]);

        $results = $this->fetch($url);
        $mapped = array_map([$this, 'mapResult'], $results);

        $ttl = empty($mapped) ? self::CACHE_MISS_TTL : self::CACHE_HIT_TTL;
        (new RedisService($this->app))->set($cacheKey, ['results' => $mapped], $ttl);

        return $mapped;
    }

    /**
     * Geocodificación inversa: obtiene dirección a partir de latitud/longitud.
     */
    public function reverse(float $lat, float $lon): ?array
    {
        $cacheKey = 'geocode:reverse:' . md5("{$lat},{$lon}");
        $cached = (new RedisService($this->app))->get($cacheKey);
        if ($cached !== null) {
            return $cached['result'];
        }

        $this->enforceRateLimit();

        $url = $this->buildUrl('/reverse', [
            'format' => 'json',
            'lat' => (string) $lat,
            'lon' => (string) $lon,
        ]);

        $data = $this->fetch($url);
        $result = $this->mapResult($data);

        $ttl = $result === null ? self::CACHE_MISS_TTL : self::CACHE_HIT_TTL;
        (new RedisService($this->app))->set($cacheKey, ['result' => $result], $ttl);

        return $result;
    }

    private function buildUrl(string $path, array $params): string
    {
        $baseUrl = rtrim($this->app->config('geocoding.base_url', 'https://nominatim.openstreetmap.org'), '/');
        return $baseUrl . $path . '?' . http_build_query($params);
    }

    private function fetch(string $url): array
    {
        $userAgent = $this->userAgent();
        $timeout = $this->app->config('geocoding.timeout', 10);

        $headers = [
            'Accept-Language: es',
        ];
        if ($userAgent !== '') {
            $headers[] = 'User-Agent: ' . $userAgent;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headers,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $statusCode = $this->extractStatusCode($http_response_header ?? []);

        if ($statusCode === 429) {
            throw new \RuntimeException('La búsqueda está tardando un momento. Intenta nuevamente en unos segundos.', 429);
        }

        if ($response === false || $statusCode >= 500) {
            throw new \RuntimeException('La búsqueda está tardando un momento. Intenta nuevamente en unos segundos.', 503);
        }

        if ($statusCode >= 400) {
            throw new \RuntimeException('No se pudo completar la búsqueda. Revisa la dirección e intenta de nuevo.', $statusCode);
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function userAgent(): string
    {
        $userAgent = trim((string) $this->app->config('geocoding.user_agent', ''));

        if ($userAgent === '') {
            error_log('[Ordena] ADVERTENCIA: GEOCODING_USER_AGENT no está configurado. Configuralo en .env con tu dominio y correo de soporte para cumplir las políticas de Nominatim.');
            return 'OrdenaInicial/1.0 (configure-your-domain-and-contact@example.com)';
        }

        return $userAgent;
    }

    private function extractStatusCode(array $headers): int
    {
        if (!isset($headers[0])) {
            return 0;
        }
        if (preg_match('/HTTP\/\d\.\d\s+(\d{3})/', $headers[0], $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    private function mapResult(array $item): ?array
    {
        if (!isset($item['lat'], $item['lon'])) {
            return null;
        }

        return [
            'display_name' => $item['display_name'] ?? '',
            'lat' => (float) $item['lat'],
            'lon' => (float) $item['lon'],
            'type' => $item['type'] ?? '',
            'address' => $item['address'] ?? [],
        ];
    }

    private function normalizeQuery(string $query): string
    {
        return trim(preg_replace('/\s+/', ' ', $query));
    }

    private function enforceRateLimit(): void
    {
        $redis = new RedisService($this->app);
        $lastRequest = $redis->getLastRequestTime(self::RATE_LIMIT_KEY);
        $now = microtime(true);

        if ($lastRequest !== null && ($now - $lastRequest) < self::RATE_LIMIT_SECONDS) {
            $wait = (int) ceil((self::RATE_LIMIT_SECONDS - ($now - $lastRequest)) * 1000);
            usleep($wait * 1000);
        }

        $redis->setLastRequestTime(self::RATE_LIMIT_KEY, microtime(true), self::RATE_LIMIT_LOCK_TTL);
    }
}
