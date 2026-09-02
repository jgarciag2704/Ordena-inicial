<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Models\Menu;

final class CartService
{
    public function __construct(private readonly App $app)
    {
    }

    public function key(): string
    {
        return 'cart_' . $this->app->tenant()->id();
    }

    public function all(): array
    {
        return $_SESSION[$this->key()] ?? [];
    }

    public function add(int $productId, array $valueIds, string $notes, int $quantity): void
    {
        $product = (new Menu($this->app))->product($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Producto no disponible.');
        }

        $quantity = max(1, min($quantity, 20));
        $selected = $this->selectedOptions($product, $valueIds);

        for ($i = 0; $i < $quantity; $i++) {
            $total = (float) $product['precio'];
            foreach ($selected as $option) {
                $total += (float) $option['price_extra'];
            }
            $_SESSION[$this->key()][] = [
                'product_id' => (int) $product['id'],
                'name' => $product['nombre'],
                'base_price' => (float) $product['precio'],
                'total' => $total,
                'notes' => trim($notes),
                'options' => $selected,
            ];
        }
    }

    public function remove(int $index): void
    {
        $cart = $this->all();
        if (array_key_exists($index, $cart)) {
            array_splice($cart, $index, 1);
            $_SESSION[$this->key()] = $cart;
        }
    }

    public function clear(): void
    {
        unset($_SESSION[$this->key()]);
    }

    public function total(): float
    {
        return array_reduce($this->all(), fn (float $sum, array $item): float => $sum + (float) $item['total'], 0.0);
    }

    private function selectedOptions(array $product, array $valueIds): array
    {
        $valueIds = array_map('intval', $valueIds);
        $selected = [];

        foreach ($product['opciones'] as $option) {
            if ($option['tipo'] === 'texto') {
                continue;
            }
            foreach ($option['valores'] as $value) {
                if (in_array((int) $value['id'], $valueIds, true)) {
                    $selected[] = [
                        'option_name' => $option['nombre'],
                        'value_name' => $value['nombre'],
                        'price_extra' => (float) $value['precio_extra'],
                    ];
                }
            }
        }

        return $selected;
    }
}
