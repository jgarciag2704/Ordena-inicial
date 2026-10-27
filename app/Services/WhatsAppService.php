<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use PDO;

class WhatsAppService
{
    public function __construct(private readonly App $app)
    {
    }

    public function isEnabled(): bool
    {
        return (new EvolutionApiService($this->app))->isEnabled();
    }

    public function instanceNameFor(int $businessId, string $slug): string
    {
        return 'biz-' . preg_replace('/[^a-z0-9-]/', '', strtolower($slug));
    }

    public function ensureInstance(int $businessId, string $slug): string
    {
        $business = $this->business($businessId);
        $current = $business['whatsapp_instance_name'] ?? null;

        if ($current !== null && $current !== '') {
            return (string) $current;
        }

        $name = $this->instanceNameFor($businessId, $slug);
        $token = bin2hex(random_bytes(16));
        $api = new EvolutionApiService($this->app);

        try {
            $result = $api->createInstance($name, $token);
            $instanceToken = $result['hash']['apikey'] ?? $token;
        } catch (\Throwable $e) {
            if (stripos($e->getMessage(), 'already exists') === false && stripos($e->getMessage(), 'already') === false) {
                throw $e;
            }
            $instanceToken = $token;
        }

        $this->updateBusiness($businessId, [
            'whatsapp_instance_name' => $name,
            'whatsapp_instance_token' => $instanceToken,
            'whatsapp_instance_status' => 'disconnected',
        ]);

        return $name;
    }

    public function refreshStatus(int $businessId): string
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;

        if ($name === null || $name === '') {
            return 'disconnected';
        }

        $api = new EvolutionApiService($this->app);

        try {
            $state = $api->connectionState($name);
            $rawState = strtolower((string) ($state['instance']['state'] ?? $state['state'] ?? ''));
        } catch (\Throwable $e) {
            $this->updateBusiness($businessId, [
                'whatsapp_instance_status' => 'error',
            ]);
            return 'error';
        }

        if (str_contains($rawState, 'open')) {
            $phone = $business['whatsapp_instance_phone'] ?? null;
            if ($phone === null || $phone === '') {
                $phone = $this->tryResolvePhone($api, $name);
            }
            $this->updateBusiness($businessId, [
                'whatsapp_instance_status' => 'connected',
                'whatsapp_instance_phone' => $phone,
                'whatsapp_instance_connected_at' => $business['whatsapp_instance_connected_at'] ?? date('Y-m-d H:i:s'),
            ]);
            return 'connected';
        }

        if (str_contains($rawState, 'connecting')) {
            $this->updateBusiness($businessId, ['whatsapp_instance_status' => 'connecting']);
            return 'connecting';
        }

        if (str_contains($rawState, 'close') || $rawState === '') {
            $this->updateBusiness($businessId, [
                'whatsapp_instance_status' => 'disconnected',
                'whatsapp_instance_phone' => null,
                'whatsapp_instance_connected_at' => null,
            ]);
            return 'disconnected';
        }

        $this->updateBusiness($businessId, ['whatsapp_instance_status' => 'disconnected']);
        return 'disconnected';
    }

    public function qrCode(int $businessId): array
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;

        if ($name === null || $name === '') {
            throw new \RuntimeException('No hay instancia de WhatsApp configurada para este negocio.');
        }

        $api = new EvolutionApiService($this->app);
        $data = $api->connectQr($name);

        $this->updateBusiness($businessId, ['whatsapp_instance_status' => 'connecting']);

        return $data;
    }

    public function generatePairingCode(int $businessId, string $phoneNumber): array
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;

        if ($name === null || $name === '') {
            throw new \RuntimeException('No hay instancia de WhatsApp configurada para este negocio.');
        }

        $api = new EvolutionApiService($this->app);
        $data = $api->generatePairingCode($name, $phoneNumber);

        $this->updateBusiness($businessId, ['whatsapp_instance_status' => 'connecting']);

        return $data;
    }

    public function disconnect(int $businessId): void
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;

        if ($name !== null && $name !== '') {
            try {
                (new EvolutionApiService($this->app))->logout($name);
            } catch (\Throwable $e) {
                error_log('[WhatsApp] Error al cerrar sesión de ' . $name . ': ' . $e->getMessage());
            }
        }

        $this->updateBusiness($businessId, [
            'whatsapp_instance_status' => 'disconnected',
            'whatsapp_instance_phone' => null,
            'whatsapp_instance_connected_at' => null,
        ]);
    }

    public function canSendOtp(int $businessId): array
    {
        $business = $this->business($businessId);
        $status = $business['whatsapp_instance_status'] ?? 'disconnected';

        if ($status !== 'connected') {
            return [
                'ok' => false,
                'error' => 'whatsapp_not_linked',
                'message' => 'Este negocio aún no ha vinculado su WhatsApp. Contacta al administrador.',
            ];
        }

        return ['ok' => true];
    }

    public function sendOtp(int $businessId, string $to, string $code, string $businessName, ?int $verificacionId = null): array
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;
        $token = $business['whatsapp_instance_token'] ?? null;

        error_log("[WhatsApp] sendOtp START: business=$businessId to=$to code=$code businessName=$businessName");
        error_log("[WhatsApp] Instance: name=" . ($name ?? 'NULL') . " token=" . ($token ? substr($token, 0, 8) . '...' : 'NULL') . " status=" . ($business['whatsapp_instance_status'] ?? 'NULL'));

        if ($name === null || $name === '') {
            error_log("[WhatsApp] FAIL: instance name is null/empty");
            return ['ok' => false, 'error' => 'whatsapp_not_linked'];
        }

        $digits = preg_replace('/\D+/', '', $to);
        if (strlen($digits) === 10) {
            $digits = '521' . $digits;
        }
        $recipient = $digits;
        error_log("[WhatsApp] Recipient formatted: $to -> $recipient");

        $throttleKey = "whatsapp:throttle:{$businessId}";
        $redis = new RedisService($this->app);
        $minInterval = 8;

        if ($redis->get($throttleKey) !== null) {
            error_log("[WhatsApp] FAIL: rate limited");
            return ['ok' => false, 'error' => 'whatsapp_rate_limited'];
        }

        $text = $this->buildOtpMessage($businessName, $code);
        $api = new EvolutionApiService($this->app);
        
        if ($token !== null && $token !== '') {
            $api->withInstanceToken($token);
        }
        
        $status = 'enviado';
        $respuesta = null;

        try {
            error_log("[WhatsApp] Sending message to $recipient via instance $name...");
            $result = $api->sendText($name, $recipient, $text);
            $respuesta = json_encode($result, JSON_UNESCAPED_UNICODE);
            error_log("[WhatsApp] Send result: $respuesta");
            
            if (isset($result['response']['message']) && is_array($result['response']['message'])) {
                foreach ($result['response']['message'] as $msg) {
                    if (isset($msg['exists']) && $msg['exists'] === false) {
                        $status = 'error';
                        $respuesta = 'number_not_found';
                        error_log("[WhatsApp] Number not found on WhatsApp: " . ($recipient ?? ''));
                        break;
                    }
                }
            }
            
            if ($status === 'enviado') {
                $redis->set($throttleKey, ['time' => time()], $minInterval);
                error_log("[WhatsApp] SUCCESS: message sent to " . ($recipient ?? ''));
            }
        } catch (\Throwable $e) {
            $status = 'error';
            $respuesta = $e->getMessage();
            error_log('[WhatsApp] ERROR: ' . $e->getMessage() . ' to: ' . ($recipient ?? ''));
        }

        $db = $this->app->db();
        $stmt = $db->prepare('INSERT INTO whatsapp_envios (negocio_id, verificacion_id, para, texto, status, respuesta) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$businessId, $verificacionId, $recipient, $text, $status, $respuesta]);
        error_log("[WhatsApp] Logged to DB: status=$status respuesta=$respuesta");

        if ($status === 'error') {
            if ($respuesta === 'number_not_found') {
                return ['ok' => false, 'error' => 'number_not_found'];
            }
            return ['ok' => false, 'error' => 'whatsapp_send_failed'];
        }

        return ['ok' => true];
    }

    private function buildOtpMessage(string $businessName, string $code): string
    {
        $templates = [
            "Hola 👋 Tu código de verificación para *{business}* es:\n\n*{code}*\n\nNo compartas este código con nadie.",
            "*{code}* es tu código de verificación de *{business}*.\n\nSi no lo solicitaste, ignora este mensaje.",
            "Para verificar tu número en *{business}* usa el código:\n\n*{code}*\n\nNunca compartas este código.",
            "Tu código para *{business}*: *{code}*\n\nVálido por unos minutos. No lo compartas.",
            "¡Hola! Usa este código para entrar a *{business}*:\n\n*{code}*\n\nNo lo compartas con nadie.",
        ];

        $template = $templates[array_rand($templates)];

        return str_replace(
            ['{business}', '{code}'],
            [$businessName, $code],
            $template
        );
    }

    public function business(int $businessId): array
    {
        $stmt = $this->app->db()->prepare('SELECT * FROM negocios WHERE id = ? LIMIT 1');
        $stmt->execute([$businessId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    public function allBusinessesWithWhatsApp(): array
    {
        $stmt = $this->app->db()->query('SELECT id, nombre, slug, whatsapp_instance_name, whatsapp_instance_status, whatsapp_instance_phone, whatsapp_instance_connected_at, activo FROM negocios WHERE activo = 1 ORDER BY nombre');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function forceDisconnect(int $businessId): void
    {
        $business = $this->business($businessId);
        $name = $business['whatsapp_instance_name'] ?? null;

        if ($name !== null && $name !== '') {
            try {
                $api = new EvolutionApiService($this->app);
                $api->logout($name);
                $api->deleteInstance($name);
            } catch (\Throwable $e) {
                error_log('[WhatsApp] Error al forzar desconexión de ' . $name . ': ' . $e->getMessage());
            }
        }

        $this->updateBusiness($businessId, [
            'whatsapp_instance_name' => null,
            'whatsapp_instance_status' => 'disconnected',
            'whatsapp_instance_phone' => null,
            'whatsapp_instance_connected_at' => null,
        ]);
    }

    private function updateBusiness(int $businessId, array $data): void
    {
        $sets = [];
        $values = [];
        foreach ($data as $col => $val) {
            $sets[] = $col . ' = ?';
            $values[] = $val;
        }
        $values[] = $businessId;

        $this->app->db()->prepare('UPDATE negocios SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($values);
    }

    private function tryResolvePhone(EvolutionApiService $api, string $name): ?string
    {
        try {
            $instances = $api->fetchInstances();
            foreach ($instances as $instance) {
                $instanceName = $instance['name'] ?? $instance['instanceName'] ?? '';
                if ($instanceName === $name) {
                    $number = $instance['number'] ?? $instance['owner'] ?? null;
                    if ($number !== null && $number !== '') {
                        $digits = preg_replace('/\D+/', '', (string) $number);
                        return '+' . $digits;
                    }
                }
            }
        } catch (\Throwable $e) {
        }
        return null;
    }
}
