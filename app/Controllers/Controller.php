<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;

abstract class Controller
{
    public function __construct(protected readonly App $app)
    {
    }

    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/app/Views/' . $view . '.php';
    }

    protected function json(array $payload, int $status = 200): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }

    protected function input(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }
}
