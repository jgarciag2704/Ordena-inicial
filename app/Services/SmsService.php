<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/**
 * Envío de OTP por SMS, intercambiable por proveedor.
 * Mismo patrón que GeocodingService: la config define el proveedor activo.
 */
final class SmsService
{
    public function __construct(private readonly App $app)
    {
    }

    public function send(string $to, string $message): bool
    {
        $provider = $this->app->config('sms.provider', 'log');

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($to, $message),
            default => $this->sendViaLog($to, $message),
        };
    }

    /**
     * Devuelve true si el proveedor actual solo registra en el log
     * (útil para exponer el código en respuestas de desarrollo).
     */
    public function isDevOnly(): bool
    {
        return $this->app->config('sms.provider', 'log') === 'log';
    }

    private function sendViaLog(string $to, string $message): bool
    {
        error_log("[SmsService] (log provider) Para {$to}: {$message}");
        return true;
    }

    private function sendViaTwilio(string $to, string $message): bool
    {
        $sid = $this->app->config('sms.twilio.sid', '');
        $token = $this->app->config('sms.twilio.token', '');
        $from = $this->app->config('sms.twilio.from', '');

        if ($sid === '' || $token === '' || $from === '') {
            error_log('[SmsService] Twilio configurado como proveedor pero faltan credenciales.');
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                ]),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
        ]);

        $endpoint = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $tokenEncoded = base64_encode("{$sid}:{$token}");
        $contextOptions = stream_context_get_options($context);
        $contextOptions['http']['header'] = 'Content-Type: application/x-www-form-urlencoded' . "\r\n" . 'Authorization: Basic ' . $tokenEncoded;
        $result = @file_get_contents($endpoint, false, stream_context_create($contextOptions));

        if ($result === false) {
            return false;
        }

        $body = json_decode($result, true);
        return isset($body['sid']);
    }
}