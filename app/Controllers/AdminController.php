<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Order;
use App\Models\AdminMenu;
use App\Models\AdminSettings;
use App\Models\Branding;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ThemeResolver;
use App\Services\TenantResolver;

final class AdminController extends Controller
{
    private const STATUSES = ['nuevo', 'confirmado', 'preparacion', 'listo', 'camino', 'entregado', 'cancelado'];

    public function loginForm(): void
    {
        if (!$this->resolveTenant()) {
            $this->view('errors/tenant-not-found');
            return;
        }
        $this->view('admin/login', ['business' => $this->app->tenant()->get(), 'error' => null]);
    }

    public function login(): void
    {
        if (!$this->resolveTenant()) {
            $this->view('errors/tenant-not-found');
            return;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = (new User($this->app))->findAdminByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->view('admin/login', ['business' => $this->app->tenant()->get(), 'error' => 'Credenciales inválidas.']);
            return;
        }

        $_SESSION['admin_' . $this->app->tenant()->id()] = (int) $user['id'];

        if ((int) ($user['debe_cambiar_password'] ?? 0) === 1) {
            redirect('/admin/password' . $this->tenantQuery());
        }

        redirect('/admin' . $this->tenantQuery());
    }

    public function logout(): void
    {
        if ($this->resolveTenant()) {
            unset($_SESSION['admin_' . $this->app->tenant()->id()]);
        }
        redirect('/admin/login' . $this->tenantQuery());
    }

    public function dashboard(): void
    {
        if (!$this->guard()) {
            return;
        }

        $orders = (new Order($this->app))->all();
        $byStatus = array_fill_keys(self::STATUSES, []);
        foreach ($orders as $order) {
            $byStatus[$order['estado']][] = $order;
        }

        $this->view('admin/dashboard', [
            'business' => $this->app->tenant()->get(),
            'statuses' => self::STATUSES,
            'byStatus' => $byStatus,
        ]);
    }

    public function menu(): void
    {
        if (!$this->guard()) {
            return;
        }

        $menu = new AdminMenu($this->app);
        $this->view('admin/menu', [
            'business' => $this->app->tenant()->get(),
            'categories' => $menu->categories(),
            'products' => $menu->products(),
            'optionsByProduct' => $menu->optionsByProduct(),
            'error' => $_SESSION['admin_menu_error'] ?? null,
            'success' => $_SESSION['admin_menu_success'] ?? null,
        ]);
        unset($_SESSION['admin_menu_error'], $_SESSION['admin_menu_success']);
    }

    public function storeCategory(): void
    {
        if (!$this->guard()) {
            return;
        }

        $name = trim((string) ($_POST['nombre'] ?? ''));
        if ($name === '') {
            $_SESSION['admin_menu_error'] = 'Escribe el nombre de la categoría.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        (new AdminMenu($this->app))->createCategory($name);
        $_SESSION['admin_menu_success'] = 'Categoría guardada.';
        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function storeProduct(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'precio' => (float) ($_POST['precio'] ?? 0),
            'imagen_thumb' => null,
        ];

        if ($data['categoria_id'] <= 0 || $data['nombre'] === '' || $data['precio'] <= 0) {
            $_SESSION['admin_menu_error'] = 'Selecciona categoría, nombre y precio válido.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        try {
            $data['imagen_thumb'] = (new ImageService())->productThumbnail($_FILES['foto'] ?? [], $this->app->tenant()->id());
            (new AdminMenu($this->app))->createProduct($data);
            $_SESSION['admin_menu_success'] = 'Producto agregado al menú.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function updateProduct(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'id' => (int) ($_POST['id'] ?? 0),
            'categoria_id' => (int) ($_POST['categoria_id'] ?? 0),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'precio' => (float) ($_POST['precio'] ?? 0),
            'imagen_thumb' => null,
        ];

        if ($data['id'] <= 0 || $data['categoria_id'] <= 0 || $data['nombre'] === '' || $data['precio'] <= 0) {
            $_SESSION['admin_menu_error'] = 'Revisa categoría, nombre y precio del producto.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        try {
            $data['imagen_thumb'] = (new ImageService())->productThumbnail($_FILES['foto'] ?? [], $this->app->tenant()->id());
            (new AdminMenu($this->app))->updateProduct($data);
            $_SESSION['admin_menu_success'] = 'Producto actualizado.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function toggleProduct(): void
    {
        if (!$this->guard()) {
            return;
        }

        try {
            (new AdminMenu($this->app))->toggleProduct((int) ($_POST['id'] ?? 0));
            $_SESSION['admin_menu_success'] = 'Disponibilidad actualizada.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function storeOption(): void
    {
        if (!$this->guard()) {
            return;
        }

        $type = (string) ($_POST['tipo'] ?? 'multiple');
        $data = [
            'producto_id' => (int) ($_POST['producto_id'] ?? 0),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'tipo' => in_array($type, ['multiple', 'unica', 'texto'], true) ? $type : 'multiple',
            'requerida' => isset($_POST['requerida']),
        ];

        if ($data['producto_id'] <= 0 || $data['nombre'] === '') {
            $_SESSION['admin_menu_error'] = 'Selecciona producto y escribe el nombre del grupo.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        try {
            (new AdminMenu($this->app))->createOption($data);
            $_SESSION['admin_menu_success'] = 'Grupo de opciones agregado.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function storeOptionValue(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'producto_opcion_id' => (int) ($_POST['producto_opcion_id'] ?? 0),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'precio_extra' => (float) ($_POST['precio_extra'] ?? 0),
        ];

        if ($data['producto_opcion_id'] <= 0 || $data['nombre'] === '' || $data['precio_extra'] < 0) {
            $_SESSION['admin_menu_error'] = 'Selecciona grupo, nombre del extra y precio válido.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        try {
            (new AdminMenu($this->app))->createOptionValue($data);
            $_SESSION['admin_menu_success'] = 'Extra agregado.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function updateOptionValue(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'id' => (int) ($_POST['id'] ?? 0),
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'precio_extra' => (float) ($_POST['precio_extra'] ?? 0),
        ];

        if ($data['id'] <= 0 || $data['nombre'] === '' || $data['precio_extra'] < 0) {
            $_SESSION['admin_menu_error'] = 'Revisa nombre y precio del extra.';
            redirect('/admin/menu' . $this->tenantQuery());
        }

        try {
            (new AdminMenu($this->app))->updateOptionValue($data);
            $_SESSION['admin_menu_success'] = 'Extra actualizado.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function deleteOptionValue(): void
    {
        if (!$this->guard()) {
            return;
        }

        try {
            (new AdminMenu($this->app))->deleteOptionValue((int) ($_POST['id'] ?? 0));
            $_SESSION['admin_menu_success'] = 'Extra eliminado.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_menu_error'] = $exception->getMessage();
        }

        redirect('/admin/menu' . $this->tenantQuery());
    }

    public function branches(): void
    {
        if (!$this->guard()) {
            return;
        }

        $settings = new AdminSettings($this->app);
        $this->view('admin/branches', [
            'business' => $this->app->tenant()->get(),
            'branches' => $settings->branches(),
            'error' => $_SESSION['admin_settings_error'] ?? null,
            'success' => $_SESSION['admin_settings_success'] ?? null,
        ]);
        unset($_SESSION['admin_settings_error'], $_SESSION['admin_settings_success']);
    }

    public function storeBranch(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = $this->branchData();
        if ($data['nombre'] === '' || $data['direccion'] === '') {
            $_SESSION['admin_settings_error'] = 'Nombre y dirección son obligatorios.';
            redirect('/admin/branches' . $this->tenantQuery());
        }

        (new AdminSettings($this->app))->createBranch($data);
        $_SESSION['admin_settings_success'] = 'Sucursal creada.';
        redirect('/admin/branches' . $this->tenantQuery());
    }

    public function updateBranch(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = $this->branchData();
        $data['id'] = (int) ($_POST['id'] ?? 0);
        if ($data['id'] <= 0 || $data['nombre'] === '' || $data['direccion'] === '') {
            $_SESSION['admin_settings_error'] = 'Revisa la sucursal seleccionada.';
            redirect('/admin/branches' . $this->tenantQuery());
        }

        (new AdminSettings($this->app))->updateBranch($data);
        $_SESSION['admin_settings_success'] = 'Sucursal actualizada.';
        redirect('/admin/branches' . $this->tenantQuery());
    }

    public function toggleBranch(): void
    {
        if (!$this->guard()) {
            return;
        }

        (new AdminSettings($this->app))->toggleBranch((int) ($_POST['id'] ?? 0));
        $_SESSION['admin_settings_success'] = 'Disponibilidad de sucursal actualizada.';
        redirect('/admin/branches' . $this->tenantQuery());
    }

    public function hours(): void
    {
        if (!$this->guard()) {
            return;
        }

        $settings = new AdminSettings($this->app);
        $branches = $settings->branches();
        $branchId = (int) ($_GET['branch_id'] ?? 0) ?: ($settings->defaultBranchId() ?? 0);

        if ($branchId && !$settings->branchBelongsToTenant($branchId)) {
            $branchId = $settings->defaultBranchId() ?? 0;
        }

        $this->view('admin/hours', [
            'business' => $this->app->tenant()->get(),
            'branches' => $branches,
            'branchId' => $branchId,
            'hours' => $branchId ? $settings->hours($branchId) : [],
            'success' => $_SESSION['admin_settings_success'] ?? null,
            'error' => $_SESSION['admin_settings_error'] ?? null,
        ]);
        unset($_SESSION['admin_settings_success'], $_SESSION['admin_settings_error']);
    }

    public function updateHours(): void
    {
        if (!$this->guard()) {
            return;
        }

        $branchId = (int) ($_POST['branch_id'] ?? 0);
        try {
            (new AdminSettings($this->app))->updateHours($branchId, $_POST['hours'] ?? []);
            $_SESSION['admin_settings_success'] = 'Horarios actualizados.';
        } catch (\Throwable $exception) {
            $_SESSION['admin_settings_error'] = $exception->getMessage();
        }

        $separator = isset($_GET['tenant']) ? '&' : '?';
        redirect('/admin/hours' . $this->tenantQuery() . $separator . 'branch_id=' . $branchId);
    }

    public function branding(): void
    {
        if (!$this->guard()) {
            return;
        }

        $this->view('admin/branding', [
            'business' => $this->app->tenant()->get(),
            'themes' => Branding::THEMES,
            'fonts' => Branding::FONTS,
            'backgrounds' => Branding::BACKGROUNDS,
            'error' => $_SESSION['admin_branding_error'] ?? null,
            'success' => $_SESSION['admin_branding_success'] ?? null,
        ]);
        unset($_SESSION['admin_branding_error'], $_SESSION['admin_branding_success']);
    }

    public function updateBranding(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'theme_key' => (new ThemeResolver())->resolve((string) ($_POST['theme_key'] ?? '')),
            'color_primario' => (string) ($_POST['color_primario'] ?? '#cc4b25'),
            'color_secundario' => (string) ($_POST['color_secundario'] ?? '#2b201b'),
            'color_fondo' => (string) ($_POST['color_fondo'] ?? '#fffaf4'),
            'color_texto' => (string) ($_POST['color_texto'] ?? '#171514'),
            'fuente' => (string) ($_POST['fuente'] ?? 'Inter, system-ui, sans-serif'),
            'hero_titulo' => trim((string) ($_POST['hero_titulo'] ?? '')),
            'hero_subtitulo' => trim((string) ($_POST['hero_subtitulo'] ?? '')),
            'comer_aqui_url' => trim((string) ($_POST['comer_aqui_url'] ?? '')),
            'fondo_estilo' => (string) ($_POST['fondo_estilo'] ?? 'calido'),
            'hero_image_url' => null,
            'remove_hero_image' => isset($_POST['remove_hero_image']),
            'hero_overlay_color' => (string) ($_POST['hero_overlay_color'] ?? '#000000'),
            'hero_overlay_opacity' => (float) ($_POST['hero_overlay_opacity'] ?? 0.35),
            'hero_blur' => (int) ($_POST['hero_blur'] ?? 0),
        ];

        if (!$this->validBranding($data)) {
            $_SESSION['admin_branding_error'] = 'Revisa colores, fuente, fondo y textos.';
            redirect('/admin/branding' . $this->tenantQuery());
        }

        try {
            $data['hero_image_url'] = (new ImageService())->heroBackground($_FILES['hero_image'] ?? [], $this->app->tenant()->id());
            (new Branding($this->app))->update($data);
        } catch (\Throwable $exception) {
            $_SESSION['admin_branding_error'] = $exception->getMessage();
            redirect('/admin/branding' . $this->tenantQuery());
        }
        $this->app->tenant()->set(array_merge($this->app->tenant()->get(), $data));
        $_SESSION['admin_branding_success'] = 'Personalización guardada.';
        redirect('/admin/branding' . $this->tenantQuery());
    }

    public function passwordForm(): void
    {
        if (!$this->guard(false)) {
            return;
        }

        $this->view('admin/password', ['business' => $this->app->tenant()->get(), 'error' => null]);
    }

    public function password(): void
    {
        if (!$this->guard(false)) {
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!$this->validPassword($password) || $password !== $confirm) {
            $this->view('admin/password', ['business' => $this->app->tenant()->get(), 'error' => 'La contraseña debe tener mínimo 8 caracteres y al menos una mayúscula. Puede incluir números y símbolos.']);
            return;
        }

        (new User($this->app))->updatePassword((int) $_SESSION['admin_' . $this->app->tenant()->id()], $password);
        redirect('/admin' . $this->tenantQuery());
    }

    public function order(): void
    {
        if (!$this->guard()) {
            return;
        }

        $order = (new Order($this->app))->findWithDetails((int) ($_GET['id'] ?? 0));
        if (!$order) {
            http_response_code(404);
            echo 'Pedido no encontrado';
            return;
        }

        $this->view('admin/order', ['business' => $this->app->tenant()->get(), 'order' => $order, 'statuses' => self::STATUSES]);
    }

    public function status(): void
    {
        if (!$this->guard()) {
            return;
        }

        (new Order($this->app))->updateStatus((int) ($_POST['id'] ?? 0), (string) ($_POST['estado'] ?? ''));
        redirect('/admin/order?id=' . (int) ($_POST['id'] ?? 0) . $this->tenantQuery('&'));
    }

    private function guard(bool $requireChangedPassword = true): bool
    {
        if (!$this->resolveTenant()) {
            $this->view('errors/tenant-not-found');
            return false;
        }

        $adminId = (int) ($_SESSION['admin_' . $this->app->tenant()->id()] ?? 0);
        if (!$adminId) {
            redirect('/admin/login' . $this->tenantQuery());
        }

        $admin = (new User($this->app))->find($adminId);
        if (!$admin) {
            unset($_SESSION['admin_' . $this->app->tenant()->id()]);
            redirect('/admin/login' . $this->tenantQuery());
        }

        if ($requireChangedPassword && (int) ($admin['debe_cambiar_password'] ?? 0) === 1) {
            redirect('/admin/password' . $this->tenantQuery());
        }

        return true;
    }

    private function validPassword(string $password): bool
    {
        return strlen($password) >= 8 && preg_match('/[A-Z]/', $password) === 1;
    }

    private function validBranding(array $data): bool
    {
        foreach (['color_primario', 'color_secundario', 'color_fondo', 'color_texto'] as $color) {
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $data[$color]) !== 1) {
                return false;
            }
        }

        return isset(Branding::THEMES[$data['theme_key']])
            && isset(Branding::FONTS[$data['fuente']])
            && preg_match('/^#[0-9a-fA-F]{6}$/', $data['hero_overlay_color']) === 1
            && $data['hero_overlay_opacity'] >= 0
            && $data['hero_overlay_opacity'] <= 0.85
            && $data['hero_blur'] >= 0
            && $data['hero_blur'] <= 18
            && in_array($data['fondo_estilo'], Branding::BACKGROUNDS, true)
            && $data['hero_titulo'] !== ''
            && $data['hero_subtitulo'] !== ''
            && ($data['comer_aqui_url'] === '' || filter_var($data['comer_aqui_url'], FILTER_VALIDATE_URL));
    }

    private function resolveTenant(): ?array
    {
        return (new TenantResolver($this->app))->resolve();
    }

    private function tenantQuery(string $prefix = '?'): string
    {
        return isset($_GET['tenant']) ? $prefix . 'tenant=' . urlencode((string) $_GET['tenant']) : '';
    }

    private function branchData(): array
    {
        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'direccion' => trim((string) ($_POST['direccion'] ?? '')),
            'telefono' => trim((string) ($_POST['telefono'] ?? '')),
        ];
    }
}
