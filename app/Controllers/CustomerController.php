<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\PhoneVerificationService;
use App\Services\TenantResolver;

final class CustomerController extends Controller
{
    public function me(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $customer = $this->sessionCustomer();
        if (!$customer) {
            $this->json(['logged' => false]);
            return;
        }

        $this->json([
            'logged' => true,
            'customer' => [
                'id' => (int) $customer['id'],
                'nombre' => $customer['nombre'],
                'telefono' => $customer['telefono'],
                'telefono_verificado' => (bool) $customer['telefono_verificado_en'],
            ],
        ]);
    }

    public function register(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        $name = trim((string) ($input['name'] ?? ''));
        $phone = (new PhoneVerificationService($this->app))->normalizeDigits((string) ($input['phone'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if ($name === '') {
            $this->json(['error' => 'Escribe tu nombre.'], 422);
            return;
        }
        if (!preg_match('/^\d{10}$/', $phone)) {
            $this->json(['error' => 'Ingresa un celular de 10 dígitos.'], 422);
            return;
        }
        $passwordError = $this->validatePassword($password);
        if ($passwordError !== null) {
            $this->json(['error' => $passwordError], 422);
            return;
        }

        $customerModel = new Customer($this->app);
        $existing = $customerModel->findByPhone($phone);

        if ($existing) {
            if ($customerModel->isVerified($existing)) {
                $this->json(['error' => 'Este teléfono ya tiene una cuenta en este negocio. Iniciá sesión.', 'already_account' => true], 422);
                return;
            }

            $customerModel->setPassword((int) $existing['id'], password_hash($password, PASSWORD_DEFAULT));
            $result = (new PhoneVerificationService($this->app))->issueCode($phone);
            if (!$result['ok']) {
                $this->json(['error' => $result['error']], 422);
                return;
            }
            $result['pending_phone'] = $phone;
            $this->json($result);
            return;
        }

        $customerModel->register($name, $phone, password_hash($password, PASSWORD_DEFAULT));
        $result = (new PhoneVerificationService($this->app))->issueCode($phone);
        if (!$result['ok']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }
        $result['pending_phone'] = $phone;
        $this->json($result);
    }

    public function verify(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        $phone = (new PhoneVerificationService($this->app))->normalizeDigits((string) ($input['phone'] ?? ''));
        $code = trim((string) ($input['code'] ?? ''));

        $result = (new PhoneVerificationService($this->app))->verify($phone, $code);
        if (!$result['ok']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }

        $customer = $result['customer'];
        if (!$customer) {
            $this->json(['error' => 'No encontramos tu cuenta. Creala de nuevo.'], 422);
            return;
        }

        $this->loginTo($customer);
        $this->json(['ok' => true]);
    }

    public function resend(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $phone = (new PhoneVerificationService($this->app))->normalizeDigits((string) ($this->input()['phone'] ?? ''));
        $customer = (new Customer($this->app))->findByPhone($phone);

        if (!$customer) {
            $this->json(['error' => 'Primero crea tu cuenta con este teléfono.'], 422);
            return;
        }

        $result = (new PhoneVerificationService($this->app))->issueCode($phone);
        if (!$result['ok']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }
        $result['pending_phone'] = $phone;
        $this->json($result);
    }

    public function login(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        $phone = (new PhoneVerificationService($this->app))->normalizeDigits((string) ($input['phone'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        $customer = (new Customer($this->app))->findByPhone($phone);
        if (!$customer || !password_verify($password, (string) ($customer['password_hash'] ?? ''))) {
            $this->json(['error' => 'Teléfono o contraseña incorrectos.'], 422);
            return;
        }

        if (!(new Customer($this->app))->isVerified($customer)) {
            $this->json(['error' => 'Tu número aún no está verificado. Creá tu cuenta y verificá tu código.'], 422);
            return;
        }

        $this->loginTo($customer);
        $this->json(['ok' => true]);
    }

    public function logout(): void
    {
        if ($this->resolveTenant()) {
            unset($_SESSION['customer_' . $this->app->tenant()->id()]);
        }
        $this->json(['ok' => true]);
    }

    public function orders(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $customer = $this->sessionCustomer();
        if (!$customer) {
            $this->json(['error' => 'Iniciá sesión para ver tus pedidos.'], 401);
            return;
        }

        $orders = (new Order($this->app))->forCustomer((int) $customer['id']);
        foreach ($orders as &$order) {
            $order['estado_label'] = Order::statusLabel((string) $order['estado']);
        }

        $this->json(['orders' => $orders]);
    }

    public function orderByFolio(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $folio = trim((string) ($_GET['folio'] ?? ''));
        if ($folio === '') {
            $this->json(['error' => 'Falta el folio del pedido.'], 422);
            return;
        }

        $customer = $this->sessionCustomer();
        if (!$customer) {
            $this->json(['error' => 'Iniciá sesión para ver tus pedidos.'], 401);
            return;
        }

        $orderModel = new Order($this->app);
        $order = $orderModel->findForCustomerByFolio((int) $customer['id'], $folio);
        if (!$order) {
            $this->json(['error' => 'Pedido no encontrado.'], 404);
            return;
        }

        $detail = $orderModel->findWithDetails((int) $order['id']);
        $detail['estado_label'] = Order::statusLabel((string) $detail['estado']);

        $this->json(['order' => $detail]);
    }

    private function loginTo(array $customer): void
    {
        $_SESSION['customer_' . $this->app->tenant()->id()] = (int) $customer['id'];
    }

    private function sessionCustomer(): ?array
    {
        $id = $_SESSION['customer_' . $this->app->tenant()->id()] ?? null;
        if (!$id) {
            return null;
        }
        return (new Customer($this->app))->findById((int) $id);
    }

    private function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'La contraseña debe tener al menos 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'La contraseña debe incluir al menos una mayúscula.';
        }
        return null;
    }

    private function resolveTenant(): ?array
    {
        return (new TenantResolver($this->app))->resolve();
    }
}