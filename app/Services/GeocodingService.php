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

    private const COUNTRY_ALPHA3 = [
        'MX' => 'MEX', 'US' => 'USA', 'CA' => 'CAN', 'GT' => 'GTM', 'BZ' => 'BLZ', 'SV' => 'SLV',
        'HN' => 'HND', 'NI' => 'NIC', 'CR' => 'CRI', 'PA' => 'PAN', 'CU' => 'CUB', 'DO' => 'DOM',
        'HT' => 'HTI', 'JM' => 'JAM', 'TT' => 'TTO', 'CO' => 'COL', 'VE' => 'VEN', 'EC' => 'ECU',
        'PE' => 'PER', 'BO' => 'BOL', 'PY' => 'PRY', 'BR' => 'BRA', 'UY' => 'URY', 'CL' => 'CHL',
        'AR' => 'ARG', 'ES' => 'ESP', 'FR' => 'FRA', 'IT' => 'ITA', 'DE' => 'DEU', 'GB' => 'GBR', 'PT' => 'PRT',
    ];

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

        $cacheKey = 'geocode:search:v2:' . md5($this->provider() . '|' . trim((string) $this->app->config('geocoding.country', '')) . '|' . $query);
        $cached = (new RedisService($this->app))->get($cacheKey);
        if ($cached !== null) {
            return $cached['results'];
        }

        $this->enforceRateLimit();
        $this->enforceIpLimit('search');

        if ($this->provider() === 'positionstack' && $this->positionstackKey() !== '') {
            $results = $this->positionstackSearch($query);
        } else {
            if ($this->provider() === 'positionstack') {
                error_log('[Ordena] ADVERTENCIA: GEOCODING_PROVIDER=positionstack pero falta POSITIONSTACK_ACCESS_KEY. Usando Nominatim mientras tanto.');
            }
            $results = array_map([$this, 'mapResult'], $this->nominatimSearch($query));
        }

        $results = array_values(array_filter($results));

        $ttl = empty($results) ? self::CACHE_MISS_TTL : self::CACHE_HIT_TTL;
        (new RedisService($this->app))->set($cacheKey, ['results' => $results], $ttl);

        return $results;
    }

    /**
     * Geocodificación inversa: obtiene dirección a partir de latitud/longitud.
     */
    public function reverse(float $lat, float $lon): ?array
    {
        $cacheKey = 'geocode:reverse:v2:' . md5($this->provider() . '|' . trim((string) $this->app->config('geocoding.country', '')) . '|' . "{$lat},{$lon}");
        $cached = (new RedisService($this->app))->get($cacheKey);
        if ($cached !== null) {
            return $cached['result'];
        }

        $this->enforceRateLimit();
        $this->enforceIpLimit('reverse');

        if ($this->provider() === 'positionstack' && $this->positionstackKey() !== '') {
            $result = $this->positionstackReverse((string) $lat, (string) $lon);
        } else {
            if ($this->provider() === 'positionstack') {
                error_log('[Ordena] ADVERTENCIA: GEOCODING_PROVIDER=positionstack pero falta POSITIONSTACK_ACCESS_KEY. Usando Nominatim mientras tanto.');
            }
            $result = $this->mapResult($this->nominatimReverse($lat, $lon));
        }

        $ttl = $result === null ? self::CACHE_MISS_TTL : self::CACHE_HIT_TTL;
        (new RedisService($this->app))->set($cacheKey, ['result' => $result], $ttl);

        return $result;
    }

    private function provider(): string
    {
        return (string) $this->app->config('geocoding.provider', 'nominatim');
    }

    private function nominatimSearch(string $query): array
    {
        return $this->fetch($this->buildUrl('/search', [
            'format' => 'json',
            'limit' => '5',
            'q' => $query,
        ]));
    }

    private function nominatimReverse(float $lat, float $lon): array
    {
        return $this->fetch($this->buildUrl('/reverse', [
            'format' => 'json',
            'lat' => (string) $lat,
            'lon' => (string) $lon,
        ]));
    }

    private function positionstackSearch(string $query): array
    {
        $params = [
            'query' => $query,
            'limit' => '5',
        ];
        $country = trim((string) $this->app->config('geocoding.country', ''));
        if ($country !== '') {
            $params['country'] = $country;
        }

        $body = $this->fetch($this->buildPositionstackUrl('/v1/forward', $params));

        $items = [];
        foreach (($body['data'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (!$this->countryMatches($item)) {
                continue;
            }
            $mapped = $this->mapPositionstackItem($item);
            if ($mapped !== null) {
                $items[] = $mapped;
            }
        }

        return $items;
    }

    /**
     * Positionstack ignora el parámetro "country" en el plan free, así que
     * descartamos del lado nuestro los resultados de otros países.
     */
    private function countryMatches(array $item): bool
    {
        $configured = strtoupper(trim((string) $this->app->config('geocoding.country', '')));
        if ($configured === '') {
            return true;
        }

        $alpha3 = self::COUNTRY_ALPHA3[$configured] ?? null;
        $code = strtoupper((string) ($item['country_code'] ?? ''));
        if ($alpha3 === null || $code === '') {
            return true;
        }

        return $code === $alpha3;
    }

    private function positionstackReverse(string $lat, string $lon): ?array
    {
        $body = $this->fetch($this->buildPositionstackUrl('/v1/reverse', [
            'query' => "{$lat},{$lon}",
            'limit' => '1',
        ]));

        $item = $body['data'][0] ?? null;
        return is_array($item) ? $this->mapPositionstackItem($item) : null;
    }

    private function positionstackKey(): string
    {
        return trim((string) $this->app->config('geocoding.positionstack.access_key', ''));
    }

    private function buildUrl(string $path, array $params): string
    {
        $baseUrl = rtrim($this->app->config('geocoding.base_url', 'https://nominatim.openstreetmap.org'), '/');
        return $baseUrl . $path . '?' . http_build_query($params);
    }

    private function buildPositionstackUrl(string $path, array $params): string
    {
        $base = rtrim((string) $this->app->config('geocoding.positionstack.endpoint', 'https://api.positionstack.com'), '/');
        return $base . $path . '?' . http_build_query(array_merge(['access_key' => $this->positionstackKey()], $params));
    }

    private function fetch(string $url): array
    {
        $userAgent = $this->userAgent();
        $timeout = $this->app->config('geocoding.timeout', 15);

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

    /**
     * Normaliza un resultado de Positionstack al mismo formato que Nominatim
     * (display_name, lat, lon, type, address) para no cambiar el frontend.
     */
    private function mapPositionstackItem(array $item): ?array
    {
        $lat = (float) ($item['latitude'] ?? 0);
        $lon = (float) ($item['longitude'] ?? 0);
        if ($lat === 0.0 && $lon === 0.0) {
            return null;
        }

        return [
            'display_name' => (string) ($item['label'] ?? ''),
            'lat' => $lat,
            'lon' => $lon,
            'type' => (string) ($item['type'] ?? ''),
            'address' => [
                'road' => (string) ($item['street'] ?? ''),
                'house_number' => (string) ($item['number'] ?? ''),
                'neighbourhood' => (string) ($item['neighbourhood'] ?? ''),
                'locality' => (string) ($item['locality'] ?? ''),
                'postcode' => (string) ($item['postal_code'] ?? ''),
                'state' => (string) ($item['region'] ?? ''),
                'country' => (string) ($item['country'] ?? ''),
            ],
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

    /**
     * Tope por IP por hora para que un atacante no consuma la cuota del
     * proveedor de geocoding (importante con Positionstack).
     */
    private function enforceIpLimit(string $kind): void
    {
        $max = (int) $this->app->config(
            'geocoding.max_per_hour_ip_' . $kind,
            $kind === 'reverse' ? 120 : 60
        );
        if ($max <= 0) {
            return;
        }

        $ip = $this->clientIp();
        $count = (new RedisService($this->app))->count("geocode:iphour:{$kind}:{$ip}", 3600);
        if ($count > $max) {
            throw new \RuntimeException('Demasiadas búsquedas en este momento. Intentá de nuevo en unos minutos.', 429);
        }
    }

    private function clientIp(): string
    {
        $ip = preg_replace('/[^0-9a-fA-F:.]+/', '', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        return $ip !== '' ? strtolower($ip) : '0';
    }
}