<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SuperAdmin;
use App\Models\SuperAdminBusiness;

final class SuperAdminController extends Controller
{
    public function loginForm(): void
    {
        $this->view('superadmin/login', ['error' => null]);
    }

    public function login(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $admin = (new SuperAdmin($this->app))->findByEmail($email);

        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            $this->view('superadmin/login', ['error' => 'Credenciales inválidas.']);
            return;
        }

        $_SESSION['super_admin_id'] = (int) $admin['id'];

        if ((int) $admin['debe_cambiar_password'] === 1) {
            redirect('/superadmin/password');
        }

        redirect('/superadmin');
    }

    public function logout(): void
    {
        unset($_SESSION['super_admin_id']);
        redirect('/superadmin/login');
    }

    public function passwordForm(): void
    {
        if (!$this->guard(false)) {
            return;
        }
        $this->view('superadmin/password', ['error' => null]);
    }

    public function password(): void
    {
        if (!$this->guard(false)) {
            return;
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if (!$this->validPassword($password) || $password !== $confirm) {
            $this->view('superadmin/password', ['error' => 'La contraseña debe tener mínimo 8 caracteres y al menos una mayúscula. Puede incluir números y símbolos.']);
            return;
        }

        (new SuperAdmin($this->app))->updatePassword((int) $_SESSION['super_admin_id'], $password);
        redirect('/superadmin');
    }

    public function dashboard(): void
    {
        if (!$this->guard()) {
            return;
        }

        $this->view('superadmin/dashboard', [
            'businesses' => (new SuperAdminBusiness($this->app))->allActive(),
            'error' => $_SESSION['superadmin_error'] ?? null,
            'success' => $_SESSION['superadmin_success'] ?? null,
        ]);
        unset($_SESSION['superadmin_error'], $_SESSION['superadmin_success']);
    }

    public function storeBusiness(): void
    {
        if (!$this->guard()) {
            return;
        }

        $data = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'slug' => strtolower(trim((string) ($_POST['slug'] ?? ''))),
            'folio_prefijo' => strtoupper(trim((string) ($_POST['folio_prefijo'] ?? ''))),
            'sucursal_nombre' => trim((string) ($_POST['sucursal_nombre'] ?? 'Sucursal principal')),
            'sucursal_direccion' => trim((string) ($_POST['sucursal_direccion'] ?? '')),
            'sucursal_telefono' => trim((string) ($_POST['sucursal_telefono'] ?? '')),
            'admin_nombre' => trim((string) ($_POST['admin_nombre'] ?? 'Administrador')),
            'admin_email' => trim((string) ($_POST['admin_email'] ?? '')),
            'admin_password' => (string) ($_POST['admin_password'] ?? ''),
        ];

        if (!$this->validBusiness($data)) {
            $_SESSION['superadmin_error'] = 'Revisa los campos obligatorios, slug, prefijo, email y contraseña admin. La contraseña requiere mínimo 8 caracteres y una mayúscula.';
            redirect('/superadmin');
        }

        try {
            (new SuperAdminBusiness($this->app))->create($data);
            $_SESSION['superadmin_success'] = 'Negocio creado correctamente.';
        } catch (\Throwable $exception) {
            $_SESSION['superadmin_error'] = 'No se pudo crear el negocio: ' . $exception->getMessage();
        }

        redirect('/superadmin');
    }

    public function resetBusinessAdminPassword(): void
    {
        if (!$this->guard()) {
            return;
        }

        $businessId = (int) ($_POST['business_id'] ?? 0);
        $email = (new SuperAdminBusiness($this->app))->resetAdminPassword($businessId);

        $_SESSION[$email ? 'superadmin_success' : 'superadmin_error'] = $email
            ? 'Contraseña restablecida para ' . $email . '. Nueva temporal: Temporal1.'
            : 'No se encontró un admin activo para ese negocio.';

        redirect('/superadmin');
    }

    private function guard(bool $requireChangedPassword = true): bool
    {
        $id = (int) ($_SESSION['super_admin_id'] ?? 0);
        $admin = $id ? (new SuperAdmin($this->app))->find($id) : null;

        if (!$admin) {
            redirect('/superadmin/login');
        }

        if ($requireChangedPassword && (int) $admin['debe_cambiar_password'] === 1) {
            redirect('/superadmin/password');
        }

        return true;
    }

    private function validBusiness(array $data): bool
    {
        return $data['nombre'] !== ''
            && preg_match('/^[a-z0-9-]{2,80}$/', $data['slug'])
            && preg_match('/^[A-Z0-9]{2,12}$/', $data['folio_prefijo'])
            && $data['sucursal_nombre'] !== ''
            && $data['sucursal_direccion'] !== ''
            && $data['admin_nombre'] !== ''
            && filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)
            && $this->validPassword($data['admin_password']);
    }

    private function validPassword(string $password): bool
    {
        return strlen($password) >= 8 && preg_match('/[A-Z]/', $password) === 1;
    }
}
