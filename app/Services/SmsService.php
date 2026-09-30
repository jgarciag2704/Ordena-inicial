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

    public function send(string $to, string $message, ?string $businessName = null): bool
    {
        $provider = $this->app->config('sms.provider', 'log');

        return match ($provider) {
            'twilio' => $this->sendViaTwilio($to, $message),
            'unimatrix' => $this->sendViaUnimatrix($to, $message, $businessName),
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

    /**
     * Envío de OTP vía Unimatrix (https://www.unimtx.com/docs/api/send).
     * Autenticación Simple (solo AccessKey ID) si no hay secret;
     * autenticación HMAC-SHA256 si se configura UNIMATRIX_ACCESS_KEY_SECRET.
     *
     * UNIMATRIX_TEMPLATE_MODE:
     *  - 'default'  -> usa UNIMATRIX_TEMPLATE_ID_DEFAULT y solo sustituye {code}.
     *  - 'business' -> usa UNIMATRIX_TEMPLATE_ID_BUSINESS y sustituye {business_name} y {code}.
     *   En modo business, si falta el Template ID o business_name, se aborta ANTES de
     *   cualquier petición HTTP (no hay fallback silencioso a la plantilla default).
     */
    private function sendViaUnimatrix(string $to, string $message, ?string $businessName = null): bool
    {
        $accessKeyId = (string) $this->app->config('sms.unimatrix.access_key_id', '');
        if ($accessKeyId === '') {
            error_log('[SmsService] Unimatrix configurado como proveedor pero falta UNIMATRIX_ACCESS_KEY_ID.');
            return false;
        }

        $mode = (string) $this->app->config('sms.unimatrix.template_mode', 'default');

        if ($this->resolveTemplateId($mode, $businessName) === null) {
            // resolveTemplateId ya registró el motivo del fallo.
            return false;
        }

        if ($mode === 'business' && $this->extractOtpCode($message) === null) {
            error_log('[SmsService] Unimatrix: UNIMATRIX_TEMPLATE_MODE=business pero el mensaje de Ordena no contiene el código (no se puede sustituir {code}).');
            return false;
        }

        $endpoint = rtrim((string) $this->app->config('sms.unimatrix.endpoint', 'https://api.unimtx.com'), '/');
        $secret = (string) $this->app->config('sms.unimatrix.access_key_secret', '');
        $timeout = (int) $this->app->config('sms.unimatrix.timeout', 10);

        $query = [
            'action' => 'sms.message.send',
            'accessKeyId' => $accessKeyId,
        ];

        if ($secret !== '') {
            $query['algorithm'] = 'hmac-sha256';
            // Nota: la documentación muestra milisegundos, pero los SDK oficiales
            // de Unimatrix (PHP y Go) envían Unix time en SEGUNDOS (time()).
            $query['timestamp'] = (string) time();
            $query['nonce'] = bin2hex(random_bytes(16));
            $query['signature'] = $this->unimatrixSignature($query, $secret);
        }

        $url = $endpoint . '/?' . http_build_query($query);

        $payload = $this->unimatrixPayload($to, $message, $businessName);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload),
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            error_log('[SmsService] Unimatrix: error de red o timeout al enviar el SMS.');
            return false;
        }

        $body = json_decode($result, true);
        if (!is_array($body)) {
            error_log('[SmsService] Unimatrix: respuesta no válida (JSON inválido o HTML de error).');
            return false;
        }

        if (($body['code'] ?? null) === '0') {
            return true;
        }

        $errorCode = (string) ($body['code'] ?? 'desconocido');
        $errorMessage = (string) ($body['message'] ?? 'error sin mensaje');
        error_log("[SmsService] Unimatrix rechazó el envío (código {$errorCode}: {$errorMessage}).");
        return false;
    }

    /**
     * Genera la firma HMAC-SHA256 de los parámetros de query ordenados
     * alfabéticamente, tal como lo exige la documentación de Unimatrix.
     */
    private function unimatrixSignature(array $query, string $secret): string
    {
        ksort($query);
        $pairs = [];
        foreach ($query as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return base64_encode(hash_hmac('sha256', implode('&', $pairs), $secret, true));
    }

    /**
     * Convierte un teléfono de 10 dígitos (México) al formato E.164.
     * Ej.: 5512345678 -> +525512345678
     */
    private function toE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 10) {
            $digits = '52' . $digits;
        }

        return '+' . $digits;
    }

    /**
     * Construye el body JSON para Unimatrix:
     * - Modo 'default'  -> templateId de la plantilla aprobada + templateData {code}.
     * - Modo 'business' -> templateId de la plantilla con {business_name} + templateData
     *                      {business_name, code}.
     * - Si no hay plantilla o no se puede extraer el código, envía el texto plano
     *   (comportamiento actual). El OTP siempre lo genera Ordena; Unimatrix solo
     *   sustituye las variables de la plantilla.
     */
    private function unimatrixPayload(string $to, string $message, ?string $businessName = null): array
    {
        $sender = (string) $this->app->config('sms.unimatrix.sender', '');
        $mode = (string) $this->app->config('sms.unimatrix.template_mode', 'default');
        $templateId = $this->resolveTemplateId($mode, $businessName);

        $payload = ['to' => $this->toE164($to)];
        if ($sender !== '') {
            $payload['signature'] = $sender;
        }

        $code = $this->extractOtpCode($message);

        if ($templateId === null || $templateId === '' || $code === null) {
            $payload['text'] = $message;

            return $payload;
        }

        $payload['templateId'] = $templateId;
        $payload['templateData'] = $mode === 'business'
            ? ['business_name' => trim((string) $businessName), 'code' => $code]
            : ['code' => $code];

        return $payload;
    }

    /**
     * Resuelve el Template ID según UNIMATRIX_TEMPLATE_MODE y valida la configuración.
     * Devuelve null si hay un fallo duro (modo inválido, Template ID business faltante
     * o business_name vacío), lo que aborta el envío ANTES de cualquier HTTP.
     * Devuelve '' en modo 'default' sin plantilla (se envía texto plano).
     */
    private function resolveTemplateId(string $mode, ?string $businessName): ?string
    {
        if ($mode === 'default') {
            return (string) $this->app->config('sms.unimatrix.template_id_default', '48b9587c');
        }

        if ($mode !== 'business') {
            error_log("[SmsService] Unimatrix: UNIMATRIX_TEMPLATE_MODE inválido ('{$mode}'). Debe ser 'default' o 'business'.");
            return null;
        }

        $templateId = (string) $this->app->config('sms.unimatrix.template_id_business', '');
        if ($templateId === '') {
            error_log('[SmsService] Unimatrix: UNIMATRIX_TEMPLATE_MODE=business pero falta UNIMATRIX_TEMPLATE_ID_BUSINESS (vacío).');
            return null;
        }

        if ($businessName === null || trim((string) $businessName) === '') {
            error_log('[SmsService] Unimatrix: UNIMATRIX_TEMPLATE_MODE=business pero business_name está vacío.');
            return null;
        }

        return $templateId;
    }

    /**
     * Extrae el código OTP (6 dígitos) del mensaje que construye
     * PhoneVerificationService::issueCode(), que termina con
     * "{$code}. No lo compartas." El anclaje al sufijo exacto evita falsos
     * positivos (p. ej., números de 6 dígitos dentro del nombre del negocio).
     * Devuelve null si el mensaje no sigue ese formato (se envía texto plano).
     */
    private function extractOtpCode(string $message): ?string
    {
        if (preg_match('/(\d{6})\.\s*No lo compartas\.?\s*$/', $message, $m) === 1) {
            return $m[1];
        }

        return null;
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