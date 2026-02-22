<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\TenantStorageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TenantBrandingController;
use App\Http\Controllers\BrewingGuideController;
use App\Http\Controllers\AccountabilityController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Main Domain Routes (brewcloud.test)
|--------------------------------------------------------------------------
*/
Route::domain(config('app.domain'))->group(function () {
    // Public landing page
    Route::get('/', function () {
        return view('welcome');
    })->name('home');

    // Tenant registration (sign up for a new coffee shop)
    Route::get('/register', [TenantController::class, 'create'])->name('tenant.register');
    Route::post('/register', [TenantController::class, 'store']);
    Route::get('/shop-login', [TenantController::class, 'shopLogin'])->name('tenant.shop-login');

    Route::get('/super-admin/login', [SuperAdminController::class, 'showLoginForm'])->name('super-admin.login');
    Route::post('/super-admin/login', [SuperAdminController::class, 'login'])->name('super-admin.login.store');

    Route::middleware('auth')->group(function () {
        Route::get('/super-admin/dashboard', [SuperAdminController::class, 'dashboard'])->name('super-admin.dashboard');
        Route::post('/super-admin/logout', [SuperAdminController::class, 'logout'])->name('super-admin.logout');
    });

    // Optional: super admin routes can go here (with separate middleware)
});

/*
|--------------------------------------------------------------------------
| Tenant Subdomain Routes ({tenant}.brewcloud.test)
|--------------------------------------------------------------------------
*/
Route::domain('{subdomain}.' . config('app.domain'))
    ->middleware(['web', 'tenant']) // 'tenant' middleware must be registered in Kernel
    ->group(function () {

        // Guest routes (login, register for users of this tenant)
        Route::middleware('guest')->group(function () {
            Route::get('login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])
                ->name('login');
            Route::post('login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);

            // If you want tenant-specific user registration (e.g., cashier accounts)
            // you can add registration routes here, but ensure they create users under the current tenant.
            Route::get('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'create'])
                ->name('register');
            Route::post('register', [App\Http\Controllers\Auth\RegisteredUserController::class, 'store']);

            // Password reset routes
            Route::get('forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
                ->name('password.request');
            Route::post('forgot-password', [App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
                ->name('password.email');
            Route::get('reset-password/{token}', [App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
                ->name('password.reset');
            Route::post('reset-password', [App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
                ->name('password.store');
        });

        // Authenticated routes (require login)
        Route::middleware('auth')->group(function () {
            // Dashboard
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::post('logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

            // Email verification routes
            Route::get('verify-email', [App\Http\Controllers\Auth\EmailVerificationPromptController::class, '__invoke'])
                ->name('verification.notice');
            Route::get('verify-email/{id}/{hash}', [App\Http\Controllers\Auth\VerifyEmailController::class, '__invoke'])
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');
            Route::post('email/verification-notification', [App\Http\Controllers\Auth\EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

            // Password confirmation
            Route::get('confirm-password', [App\Http\Controllers\Auth\ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');
            Route::post('confirm-password', [App\Http\Controllers\Auth\ConfirmablePasswordController::class, 'store']);

            // Password update
            Route::put('password', [App\Http\Controllers\Auth\PasswordController::class, 'update'])
                ->name('password.update');

            Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
            Route::post('/pos/cart/add', [PosController::class, 'addItem'])->name('pos.cart.add');
            Route::post('/pos/cart/remove', [PosController::class, 'removeItem'])->name('pos.cart.remove');
            Route::post('/pos/submit', [PosController::class, 'submit'])->name('pos.submit');

            Route::get('/tenant-files/{path}', [TenantStorageController::class, 'show'])
                ->where('path', '.*')
                ->name('tenant.files.show');

            // Profile management (using Breeze controllers)
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

            // Tenant-specific resource routes (products, orders, inventory, etc.)
            Route::resource('products', App\Http\Controllers\ProductController::class);
            Route::resource('orders', OrderController::class);
            Route::get('/sales', [App\Http\Controllers\SalesController::class, 'index'])->name('sales.index');
            Route::resource('users', UsersController::class)->except(['show']);
            Route::resource('roles', RolesController::class)->except(['show']);
            Route::get('/accountability', [AccountabilityController::class, 'index'])
                ->middleware('can:manage users')
                ->name('accountability.index');

            Route::get('/branding', [TenantBrandingController::class, 'edit'])
                ->middleware('can:manage users')
                ->name('branding.edit');
            Route::put('/branding', [TenantBrandingController::class, 'update'])
                ->middleware('can:manage users')
                ->name('branding.update');

            Route::resource('brewing-guides', BrewingGuideController::class);
            // ... more routes
        });

        // If you have password reset or other auth routes, include them as needed.
        // You can also require __DIR__.'/auth.php' but careful: that file may define routes
        // without subdomain. Instead, copy the necessary routes here.
    });