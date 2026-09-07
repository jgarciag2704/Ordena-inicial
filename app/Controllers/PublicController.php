<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Branch;
use App\Services\CartService;
use App\Services\ThemeResolver;
use App\Services\TenantResolver;

final class PublicController extends Controller
{
    public function index(): void
    {
        if (!$this->resolveTenant()) {
            $this->view('errors/tenant-not-found');
            return;
        }

        $menu = new Menu($this->app);
        $cart = new CartService($this->app);
        $branch = new Branch($this->app);
        $business = $this->app->tenant()->get();
        $theme = (new ThemeResolver())->resolve($business['theme_key'] ?? null);

        $this->view('public/themes/' . $theme . '/home', [
            'business' => $business,
            'theme' => $theme,
            'branches' => $branch->publicList(),
            'categories' => $menu->categories(),
            'products' => $menu->products(),
            'cart' => $cart->all(),
            'total' => $cart->total(),
        ]);
    }

    public function addCart(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        try {
            (new CartService($this->app))->add((int) ($input['product_id'] ?? 0), $input['values'] ?? [], (string) ($input['notes'] ?? ''), (int) ($input['quantity'] ?? 1));
            $this->cartResponse();
        } catch (\Throwable $exception) {
            $this->json(['error' => $exception->getMessage()], 422);
        }
    }

    public function removeCart(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }
        (new CartService($this->app))->remove((int) ($this->input()['index'] ?? -1));
        $this->cartResponse();
    }

    public function startCheckout(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        $mode = in_array($input['mode'] ?? '', ['pickup', 'mesa', 'delivery'], true) ? $input['mode'] : 'pickup';
        $branchId = (int) ($input['branch_id'] ?? 0);
        $name = trim((string) ($input['name'] ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($input['phone'] ?? ''));
        $address = trim((string) ($input['address'] ?? ''));
        $table = trim((string) ($input['table'] ?? ''));

        if ($name === '' || strlen($phone) !== 10) {
            $this->json(['error' => 'Ingresa nombre y celular de 10 dígitos.'], 422);
            return;
        }
        if ($branchId <= 0 || !(new Branch($this->app))->isOpen($branchId)) {
            $this->json(['error' => 'Selecciona una sucursal abierta para continuar.'], 422);
            return;
        }
        if ($mode === 'delivery' && $address === '') {
            $this->json(['error' => 'Ingresa dirección de entrega.'], 422);
            return;
        }
        if ($mode === 'mesa' && $table === '') {
            $this->json(['error' => 'Ingresa número de mesa.'], 422);
            return;
        }

        $_SESSION['checkout_' . $this->app->tenant()->id()] = compact('mode', 'branchId', 'name', 'phone', 'address', 'table');
        $_SESSION['checkout_' . $this->app->tenant()->id()]['branch_id'] = $branchId;
        $this->json(['ok' => true]);
    }

    public function confirmCheckout(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        if (($this->input()['otp'] ?? '') !== '123456') {
            $this->json(['error' => 'Código incorrecto.'], 422);
            return;
        }

        $cart = new CartService($this->app);
        if (!$cart->all()) {
            $this->json(['error' => 'El carrito está vacío.'], 422);
            return;
        }

        $checkout = $_SESSION['checkout_' . $this->app->tenant()->id()] ?? null;
        if (!$checkout) {
            $this->json(['error' => 'Faltan datos de checkout.'], 422);
            return;
        }

        $order = (new Order($this->app))->create($checkout, $cart->all());
        $cart->clear();
        unset($_SESSION['checkout_' . $this->app->tenant()->id()]);

        $this->json(['ok' => true, 'order' => $order, 'mode' => $checkout['mode']]);
    }

    private function resolveTenant(): ?array
    {
        return (new TenantResolver($this->app))->resolve();
    }

    private function cartResponse(): void
    {
        $cart = new CartService($this->app);
        $this->json(['items' => $cart->all(), 'total' => $cart->total(), 'count' => count($cart->all())]);
    }
}
