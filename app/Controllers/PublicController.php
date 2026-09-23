<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Menu;
use App\Models\Order;
use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Services\CartService;
use App\Services\DeliveryCalculator;
use App\Services\GeocodingService;
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
        $deliveryZone = new DeliveryZone($this->app);
        $business = $this->app->tenant()->get();
        $theme = (new ThemeResolver())->resolve($business['theme_key'] ?? null);
        $branches = $branch->publicList();

        $zonesByBranch = [];
        $radioZonesByBranch = [];
        foreach ($branches as $b) {
            $zonesByBranch[(int) $b['id']] = $deliveryZone->activeForBranch((int) $b['id']);
            $radioZonesByBranch[(int) $b['id']] = $deliveryZone->activeRadioZonesForBranch((int) $b['id']);
        }

        $this->view('public/themes/' . $theme . '/home', [
            'business' => $business,
            'theme' => $theme,
            'branches' => $branches,
            'zonesByBranch' => $zonesByBranch,
            'radioZonesByBranch' => $radioZonesByBranch,
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
        $address = trim((string) ($input['address'] ?? ''));
        $table = trim((string) ($input['table'] ?? ''));

        $customerSession = $this->customerSession();
        if ($customerSession) {
            if (!(new Customer($this->app))->isVerified($customerSession)) {
                $this->json(['error' => 'Tu número aún no está verificado. Completá la verificación en tu cuenta.'], 422);
                return;
            }
            $name = $customerSession['nombre'];
            $phone = $customerSession['telefono'];
            $clienteId = (int) $customerSession['id'];
        } else {
            $this->json(['error' => 'Iniciá sesión para confirmar tu pedido.'], 401);
            return;
        }

        if ($name === '' || strlen($phone) !== 10) {
            $this->json(['error' => 'Inicia sesión con un teléfono válido.'], 422);
            return;
        }

        $delivery = [
            'calle' => trim((string) ($input['calle'] ?? '')),
            'numero' => trim((string) ($input['numero'] ?? '')),
            'colonia' => trim((string) ($input['colonia'] ?? '')),
            'referencias' => trim((string) ($input['referencias'] ?? '')),
            'zone_id' => (int) ($input['zone_id'] ?? 0),
        ];

        if ($name === '' || strlen($phone) !== 10) {
            $this->json(['error' => 'Ingresa nombre y celular de 10 dígitos.'], 422);
            return;
        }
        if ($branchId <= 0 || !(new Branch($this->app))->isOpen($branchId)) {
            $this->json(['error' => 'Selecciona una sucursal abierta para continuar.'], 422);
            return;
        }

        $subtotal = (new CartService($this->app))->total();
        $deliveryFee = 0.0;
        $zoneSnapshot = null;
        $distanceKm = 0.0;
        $deliveryLat = null;
        $deliveryLon = null;
        $minOrder = 0.0;
        $branchSnapshot = null;
        $hasRadio = false;
        $zone = null;

        if ($mode === 'delivery') {
            if ($delivery['calle'] === '' || $delivery['numero'] === '' || $delivery['colonia'] === '') {
                $this->json(['error' => 'Ingresa calle, número y colonia de entrega.'], 422);
                return;
            }

            $hasRadio = (new DeliveryZone($this->app))->hasRadioZones($branchId);

            if ($hasRadio) {
                $deliveryLat = (float) ($input['delivery_lat'] ?? 0);
                $deliveryLon = (float) ($input['delivery_lon'] ?? 0);

                if ($deliveryLat === 0.0 || $deliveryLon === 0.0) {
                    $this->json(['error' => 'Confirma tu ubicación en el mapa.'], 422);
                    return;
                }

                $calculation = (new DeliveryCalculator($this->app))->calculate($branchId, $deliveryLat, $deliveryLon);
                if (!$calculation) {
                    $this->json(['error' => 'La sucursal seleccionada no puede entregar a domicilio porque no tiene ubicación configurada.'], 422);
                    return;
                }

                if (!$calculation['applicable']) {
                    $this->json(['error' => 'La ubicación queda fuera de las zonas de entrega activas.'], 422);
                    return;
                }

                $zone = $calculation['zone'];
                $deliveryFee = $calculation['delivery_fee'];
                $minOrder = $calculation['min_order'];
                $distanceKm = $calculation['distance_km'];
                $branchSnapshot = $calculation['branch'];
            } else {
                if ($delivery['zone_id'] <= 0) {
                    $this->json(['error' => 'Selecciona una zona de entrega.'], 422);
                    return;
                }

                $zone = (new DeliveryZone($this->app))->findActive($delivery['zone_id']);
                if (!$zone || (int) $zone['sucursal_id'] !== $branchId) {
                    $this->json(['error' => 'La zona de entrega no está disponible para la sucursal seleccionada.'], 422);
                    return;
                }

                $deliveryFee = (float) $zone['costo_envio'];
                $minOrder = $zone['pedido_minimo'] !== null ? (float) $zone['pedido_minimo'] : 0.0;
            }

            if ($minOrder > 0 && $subtotal < $minOrder) {
                $this->json(['error' => 'El pedido mínimo para esta zona es ' . money($minOrder) . '. Agrega más productos.'], 422);
                return;
            }

            $zoneSnapshot = [
                'id' => (int) $zone['id'],
                'nombre' => $zone['nombre'],
                'costo_envio' => $deliveryFee,
            ];
        }

        if ($mode === 'mesa' && $table === '') {
            $this->json(['error' => 'Ingresa número de mesa.'], 422);
            return;
        }

        $payExact = !empty($input['pay_exact']);
        $cashAmount = $payExact ? 0.0 : (float) ($input['cash_amount'] ?? 0);

        $total = $subtotal + $deliveryFee;

        $_SESSION['checkout_' . $this->app->tenant()->id()] = [
            'mode' => $mode,
            'branch_id' => $branchId,
            'cliente_id' => $clienteId,
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'calle' => $delivery['calle'],
            'numero' => $delivery['numero'],
            'colonia' => $delivery['colonia'],
            'referencias' => $delivery['referencias'],
            'delivery_lat' => $deliveryLat,
            'delivery_lon' => $deliveryLon,
            'has_radio' => $hasRadio,
            'zone_id' => $hasRadio ? (int) $zone['id'] : $delivery['zone_id'],
            'zone_snapshot' => $zoneSnapshot,
            'delivery_fee' => $deliveryFee,
            'distance_km' => $distanceKm,
            'subtotal' => $subtotal,
            'total' => $total,
            'min_order' => $minOrder,
            'table' => $table,
            'pay_exact' => $payExact,
            'cash_amount' => $cashAmount,
            'branch_lat' => $branchSnapshot ? (float) $branchSnapshot['latitud'] : null,
            'branch_lon' => $branchSnapshot ? (float) $branchSnapshot['longitud'] : null,
        ];

        $this->json(['ok' => true, 'subtotal' => $subtotal, 'delivery_fee' => $deliveryFee, 'total' => $total]);
    }

    public function confirmCheckout(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $customerSession = $this->customerSession();
        if (!$customerSession || !(new Customer($this->app))->isVerified($customerSession)) {
            $this->json(['error' => 'Necesitás iniciar sesión con tu número verificado para confirmar el pedido.'], 401);
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

        $checkout['cliente_id'] = (int) $customerSession['id'];
        $checkout['name'] = $customerSession['nombre'];
        $checkout['phone'] = $customerSession['telefono'];

        if ($checkout['mode'] === 'delivery') {
            if (!empty($checkout['has_radio'])) {
                $calculation = $this->recalculateDelivery($checkout);
                if ($calculation['error']) {
                    $this->json(['error' => $calculation['error']], 422);
                    return;
                }
                $checkout = array_merge($checkout, $calculation['data']);
            }


            $payExact = !empty($checkout['pay_exact']);
            $cashAmount = $payExact ? (float) $checkout['total'] : (float) ($checkout['cash_amount'] ?? 0);
            if (!$payExact && $cashAmount < (float) $checkout['total']) {
                $this->json(['error' => 'El monto con el que pagarás no puede ser menor al total.'], 422);
                return;
            }
            $checkout['cash_amount'] = $cashAmount;
            $checkout['change_amount'] = $cashAmount - (float) $checkout['total'];
        }

        $order = (new Order($this->app))->create($checkout, $cart->all());
        $cart->clear();
        unset($_SESSION['checkout_' . $this->app->tenant()->id()]);

        $this->json(['ok' => true, 'order' => $order, 'mode' => $checkout['mode']]);
    }

    public function geocode(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $query = trim((string) ($_GET['q'] ?? ''));
        $results = (new GeocodingService($this->app))->search($query);

        $this->json(['results' => $results]);
    }

    public function reverseGeocode(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $lat = (float) ($_GET['lat'] ?? 0);
        $lon = (float) ($_GET['lon'] ?? 0);

        if ($lat === 0.0 || $lon === 0.0) {
            $this->json(['error' => 'Coordenadas inválidas.'], 422);
            return;
        }

        $result = (new GeocodingService($this->app))->reverse($lat, $lon);
        if ($result === null) {
            $this->json(['error' => 'No pudimos identificar la dirección de esa ubicación.'], 404);
            return;
        }

        $this->json(['result' => $result]);
    }

    public function calculateDelivery(): void
    {
        if (!$this->resolveTenant()) {
            $this->json(['error' => 'Negocio no encontrado'], 404);
            return;
        }

        $input = $this->input();
        $branchId = (int) ($input['branch_id'] ?? 0);
        $lat = (float) ($input['lat'] ?? 0);
        $lon = (float) ($input['lon'] ?? 0);

        if ($branchId <= 0 || $lat === 0.0 || $lon === 0.0) {
            $this->json(['error' => 'Selecciona sucursal y una ubicación de entrega.'], 422);
            return;
        }

        $calculation = (new DeliveryCalculator($this->app))->calculate($branchId, $lat, $lon);
        if (!$calculation) {
            $this->json(['error' => 'La sucursal seleccionada no tiene ubicación configurada para calcular envío.'], 422);
            return;
        }

        if (!$calculation['applicable']) {
            $this->json([
                'applicable' => false,
                'distance_km' => $calculation['distance_km'],
                'error' => 'La ubicación queda fuera de las zonas de entrega activas.',
            ], 422);
            return;
        }

        $this->json([
            'applicable' => true,
            'distance_km' => $calculation['distance_km'],
            'zone_id' => (int) $calculation['zone']['id'],
            'zone_name' => $calculation['zone']['nombre'],
            'delivery_fee' => $calculation['delivery_fee'],
            'min_order' => $calculation['min_order'],
        ]);
    }

    private function recalculateDelivery(array $checkout): array
    {
        $branchId = (int) ($checkout['branch_id'] ?? 0);
        $lat = (float) ($checkout['delivery_lat'] ?? 0);
        $lon = (float) ($checkout['delivery_lon'] ?? 0);

        if ($branchId <= 0 || $lat === 0.0 || $lon === 0.0) {
            return ['error' => 'Faltan coordenadas de entrega.', 'data' => []];
        }

        $calculation = (new DeliveryCalculator($this->app))->calculate($branchId, $lat, $lon);
        if (!$calculation) {
            return ['error' => 'La sucursal no tiene ubicación configurada.', 'data' => []];
        }

        if (!$calculation['applicable']) {
            return ['error' => 'La ubicación queda fuera de las zonas de entrega activas.', 'data' => []];
        }

        $subtotal = (new CartService($this->app))->total();
        $minOrder = $calculation['min_order'];
        if ($minOrder > 0 && $subtotal < $minOrder) {
            return ['error' => 'El pedido mínimo para esta zona es ' . money($minOrder) . '. Agrega más productos.', 'data' => []];
        }

        $deliveryFee = $calculation['delivery_fee'];
        $total = $subtotal + $deliveryFee;

        return [
            'error' => null,
            'data' => [
                'delivery_fee' => $deliveryFee,
                'subtotal' => $subtotal,
                'total' => $total,
                'zone_id' => (int) $calculation['zone']['id'],
                'zone_snapshot' => [
                    'id' => (int) $calculation['zone']['id'],
                    'nombre' => $calculation['zone']['nombre'],
                    'costo_envio' => $deliveryFee,
                ],
                'distance_km' => $calculation['distance_km'],
                'branch_lat' => (float) $calculation['branch']['latitud'],
                'branch_lon' => (float) $calculation['branch']['longitud'],
                'min_order' => $minOrder,
            ],
        ];
    }

    private function resolveTenant(): ?array
    {
        return (new TenantResolver($this->app))->resolve();
    }

    private function customerSession(): ?array
    {
        $id = $_SESSION['customer_' . $this->app->tenant()->id()] ?? null;
        if (!$id) {
            return null;
        }
        return (new Customer($this->app))->findById((int) $id);
    }

    private function cartResponse(): void
    {
        $cart = new CartService($this->app);
        $this->json(['items' => $cart->all(), 'total' => $cart->total(), 'count' => count($cart->all())]);
    }
}
