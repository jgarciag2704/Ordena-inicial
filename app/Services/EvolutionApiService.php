<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

class EvolutionApiService
{
    private ?string $instanceToken = null;

    public function __construct(private readonly App $app)
    {
    }

    public function withInstanceToken(string $token): self
    {
        $this->instanceToken = $token;
        return $this;
    }

    public function createInstance(string $name, ?string $token = null): array
    {
        $body = [
            'instanceName' => $name,
            'integration' => 'WHATSAPP-BAILEYS',
            'qrcode' => true,
            'rejectCall' => true,
            'groupsIgnore' => true,
            'alwaysOnline' => false,
            'readMessages' => false,
            'readStatus' => false,
            'syncFullHistory' => false,
        ];
        
        if ($token !== null) {
            $body['token'] = $token;
        }
        
        return $this->request('POST', '/instance/create', $body);
    }

    public function fetchInstances(): array
    {
        return $this->request('GET', '/instance/fetchInstances');
    }

    public function connectionState(string $name): array
    {
        return $this->request('GET', '/instance/connectionState/' . urlencode($name));
    }

    public function connectQr(string $name): array
    {
        return $this->request('GET', '/instance/connect/' . urlencode($name));
    }

    public function generatePairingCode(string $name, string $phoneNumber): array
    {
        return $this->request('POST', '/instance/connect/' . urlencode($name), [
            'number' => $phoneNumber
        ]);
    }

    public function logout(string $name): void
    {
        $this->request('DELETE', '/instance/logout/' . urlencode($name));
    }

    public function deleteInstance(string $name): void
    {
        $this->request('DELETE', '/instance/delete/' . urlencode($name));
    }

    public function sendText(string $name, string $to, string $message): array
    {
        return $this->request('POST', '/message/sendText/' . urlencode($name), [
            'number' => $to,
            'text' => $message,
        ]);
    }

    public function sendPresence(string $name, string $to, string $presence = 'composing'): void
    {
        $this->request('POST', '/chat/presence/' . urlencode($name), [
            'number' => $to,
            'presence' => $presence,
        ]);
    }

    public function isEnabled(): bool
    {
        $url = (string) $this->app->config('evolution.url', '');
        $key = (string) $this->app->config('evolution.api_key', '');
        return $url !== '' && $key !== '';
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = rtrim((string) $this->app->config('evolution.url', ''), '/') . $path;
        $globalApiKey = (string) $this->app->config('evolution.api_key', '');
        $timeout = (int) $this->app->config('evolution.timeout', 20);

        $apiKey = $this->instanceToken ?? $globalApiKey;

        if ($url === '' || $apiKey === '') {
            throw new \RuntimeException('Evolution API no está configurada. Define EVOLUTION_API_URL y EVOLUTION_API_API_KEY en .env');
        }

        $maskedKey = substr($apiKey, 0, 6) . '...' . substr($apiKey, -4);

        error_log("[EvoAPI] $method $path | token=$maskedKey | timeout={$timeout}s");

        $options = [
            'http' => [
                'method' => $method,
                'header' => "apikey: {$apiKey}\r\nContent-Type: application/json\r\n",
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ];

        if ($body !== null) {
            $options['http']['content'] = json_encode($body);
            $maskedBody = $body;
            if (isset($maskedBody['token'])) {
                $maskedBody['token'] = substr($maskedBody['token'], 0, 6) . '...';
            }
            error_log('[EvoAPI] Body: ' . json_encode($maskedBody, JSON_UNESCAPED_UNICODE));
        }

        $startTime = microtime(true);
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        $elapsed = round(microtime(true) - $startTime, 2);

        if ($result === false) {
            $error = error_get_last();
            $errorLog = strip_tags($error['message'] ?? 'unknown');
            error_log("[EvoAPI] FAIL ({$elapsed}s): $errorLog");
            throw new \RuntimeException('Evolution API: error de red o timeout al contactar ' . $url . ' (' . $errorLog . ')');
        }

        $httpCode = $this->lastHttpCode();
        $bodyPreview = substr($result, 0, 300);

        error_log("[EvoAPI] Response ({$elapsed}s) HTTP $httpCode: $bodyPreview");

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Evolution API: respuesta no válida (JSON inválido). HTTP ' . $httpCode . ' Body: ' . $bodyPreview);
        }

        if ($httpCode >= 400) {
            $message = $decoded['message'] ?? $decoded['error'] ?? ('HTTP ' . $httpCode);
            throw new \RuntimeException('Evolution API: ' . $message);
        }

        return $decoded;
    }

    private function lastHttpCode(): int
    {
        if (!isset($http_response_header)) {
            return 0;
        }
        foreach (array_reverse($http_response_header) as $header) {
            if (preg_match('#^HTTP/\d\.\d (\d{3})#', $header, $m)) {
                return (int) $m[1];
            }
        }
        return 0;
    }
}
