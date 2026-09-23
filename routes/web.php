<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\CustomerController;
use App\Controllers\PublicController;
use App\Controllers\SuperAdminController;

$router->get('/', [PublicController::class, 'index']);
$router->post('/cart/add', [PublicController::class, 'addCart']);
$router->post('/cart/remove', [PublicController::class, 'removeCart']);
$router->post('/checkout/start', [PublicController::class, 'startCheckout']);
$router->post('/checkout/confirm', [PublicController::class, 'confirmCheckout']);
$router->get('/checkout/geocode', [PublicController::class, 'geocode']);
$router->get('/checkout/reverse-geocode', [PublicController::class, 'reverseGeocode']);
$router->post('/checkout/delivery-calculate', [PublicController::class, 'calculateDelivery']);

$router->get('/auth/me', [CustomerController::class, 'me']);
$router->post('/auth/register', [CustomerController::class, 'register']);
$router->post('/auth/verify', [CustomerController::class, 'verify']);
$router->post('/auth/resend', [CustomerController::class, 'resend']);
$router->post('/auth/login', [CustomerController::class, 'login']);
$router->post('/auth/logout', [CustomerController::class, 'logout']);
$router->get('/mis-pedidos', [CustomerController::class, 'orders']);
$router->get('/mis-pedidos/ver', [CustomerController::class, 'orderByFolio']);

$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/login', [AdminController::class, 'loginForm']);
$router->post('/admin/login', [AdminController::class, 'login']);
$router->post('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin/password', [AdminController::class, 'passwordForm']);
$router->post('/admin/password', [AdminController::class, 'password']);
$router->get('/admin/menu', [AdminController::class, 'menu']);
$router->post('/admin/menu/categories', [AdminController::class, 'storeCategory']);
$router->post('/admin/menu/products', [AdminController::class, 'storeProduct']);
$router->post('/admin/menu/products/update', [AdminController::class, 'updateProduct']);
$router->post('/admin/menu/products/toggle', [AdminController::class, 'toggleProduct']);
$router->post('/admin/menu/options', [AdminController::class, 'storeOption']);
$router->post('/admin/menu/option-values', [AdminController::class, 'storeOptionValue']);
$router->post('/admin/menu/option-values/update', [AdminController::class, 'updateOptionValue']);
$router->post('/admin/menu/option-values/delete', [AdminController::class, 'deleteOptionValue']);
$router->get('/admin/branches', [AdminController::class, 'branches']);
$router->post('/admin/branches', [AdminController::class, 'storeBranch']);
$router->post('/admin/branches/update', [AdminController::class, 'updateBranch']);
$router->post('/admin/branches/toggle', [AdminController::class, 'toggleBranch']);
$router->get('/admin/hours', [AdminController::class, 'hours']);
$router->post('/admin/hours', [AdminController::class, 'updateHours']);
$router->get('/admin/delivery-zones', [AdminController::class, 'deliveryZones']);
$router->post('/admin/delivery-zones', [AdminController::class, 'storeDeliveryZone']);
$router->post('/admin/delivery-zones/update', [AdminController::class, 'updateDeliveryZone']);
$router->post('/admin/delivery-zones/toggle', [AdminController::class, 'toggleDeliveryZone']);
$router->get('/admin/branding', [AdminController::class, 'branding']);
$router->post('/admin/branding', [AdminController::class, 'updateBranding']);
$router->get('/admin/order', [AdminController::class, 'order']);
$router->post('/admin/order/status', [AdminController::class, 'status']);

$router->get('/superadmin', [SuperAdminController::class, 'dashboard']);
$router->get('/superadmin/login', [SuperAdminController::class, 'loginForm']);
$router->post('/superadmin/login', [SuperAdminController::class, 'login']);
$router->post('/superadmin/logout', [SuperAdminController::class, 'logout']);
$router->get('/superadmin/password', [SuperAdminController::class, 'passwordForm']);
$router->post('/superadmin/password', [SuperAdminController::class, 'password']);
$router->post('/superadmin/businesses', [SuperAdminController::class, 'storeBusiness']);
$router->post('/superadmin/businesses/reset-admin-password', [SuperAdminController::class, 'resetBusinessAdminPassword']);
