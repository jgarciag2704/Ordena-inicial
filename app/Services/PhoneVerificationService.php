<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Customer;
use PDO;

/**
 * Emisión y validación de códigos OTP para cuentas de clientes.
 * Usa la tabla verificaciones_telefono y Redis para límites.
 */
final class PhoneVerificationService
{
    public function __construct(private readonly App $app)
    {
    }

    /**
     * Genera, guarda y envía un código OTP para un teléfono.
     * Devuelve la información de envío. En modo de desarrollo el código se incluye.
     */
    public function issueCode(string $phone): array
    {
        $negocioId = $this->app->tenant()->id();
        $phone = $this->normalizeDigits($phone);

        if (!preg_match('/^\d{10}$/', $phone)) {
            return ['ok' => false, 'error' => 'El teléfono debe tener 10 dígitos.'];
        }

        $cooldownKey = "otp:cooldown:{$negocioId}:{$phone}";
        $redis = new RedisService($this->app);
        if ($redis->get($cooldownKey) !== null) {
            $cooldown = (int) $this->app->config('otp.resend_cooldown_seconds', 60);
            return ['ok' => false, 'error' => "Esperá {$cooldown} segundos antes de pedir otro código."];
        }

        $code = (string) random_int(100000, 999999);
        $ttlMinutes = (int) $this->app->config('otp.ttl_minutes', 10);
        $expira = date('Y-m-d H:i:s', time() + $ttlMinutes * 60);

        $db = $this->app->db();
        $stmt = $db->prepare('INSERT INTO verificaciones_telefono (negocio_id, telefono, codigo, expira_en) VALUES (?, ?, ?, ?)');
        $stmt->execute([$negocioId, $phone, $code, $expira]);

        $sms = new SmsService($this->app);
        $sms->send($phone, 'Tu código de verificación para ' . $this->app->tenant()->get()['nombre'] . ' es: ' . $code . '. No lo compartas.');

        $redis->set($cooldownKey, ['time' => time()], (int) $this->app->config('otp.resend_cooldown_seconds', 60));

        return [
            'ok' => true,
            'dev_only' => $sms->isDevOnly(),
            'code' => $sms->isDevOnly() ? $code : null,
            'expires_in' => $ttlMinutes * 60,
        ];
    }

    /**
     * Valida el OTP. En caso de éxito marca verificado y devuelve el cliente.
     */
    public function verify(string $phone, string $code): array
    {
        $negocioId = $this->app->tenant()->id();
        $phone = $this->normalizeDigits($phone);
        $code = trim($code);

        if (!preg_match('/^\d{10}$/', $phone)) {
            return ['ok' => false, 'error' => 'El teléfono debe tener 10 dígitos.'];
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'error' => 'Ingresa el código de 6 dígitos.'];
        }

        $attemptKey = "otp:attempts:{$negocioId}:{$phone}";
        $redis = new RedisService($this->app);
        $attempts = (int) ($redis->get($attemptKey)['count'] ?? 0);
        $maxAttempts = (int) $this->app->config('otp.max_attempts', 5);

        if ($attempts >= $maxAttempts) {
            return ['ok' => false, 'error' => 'Demasiados intentos. Pedí un código nuevo y esperá antes de reintentar.'];
        }

        $db = $this->app->db();
        $stmt = $db->prepare('SELECT * FROM verificaciones_telefono WHERE negocio_id = ? AND telefono = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$negocioId, $phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || strtotime((string) $row['expira_en']) < time()) {
            return ['ok' => false, 'error' => 'El código expiró. Pedí uno nuevo.'];
        }

        if ($row['verificado_en'] !== null || !hash_equals((string) $row['codigo'], $code)) {
            $redis->set($attemptKey, ['count' => $attempts + 1], (int) $this->app->config('otp.ttl_minutes', 10) * 60);
            return ['ok' => false, 'error' => 'Código incorrecto.'];
        }

        $mark = $db->prepare('UPDATE verificaciones_telefono SET verificado_en = NOW() WHERE id = ?');
        $mark->execute([(int) $row['id']]);

        $customer = (new Customer($this->app))->markVerified($phone);

        return ['ok' => true, 'customer' => $customer];
    }

    public function normalizeDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone);
    }
}