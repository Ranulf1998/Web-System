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
use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

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
    Route::post('/register/payment/session', [TenantController::class, 'createStripeRegistrationSession'])->name('tenant.register.payment.session');
    Route::get('/register/payment/success', [TenantController::class, 'stripeSuccess'])->name('tenant.register.payment.success');
    Route::get('/shop-login', [TenantController::class, 'shopLogin'])->name('tenant.shop-login');

    Route::get('/super-admin/login', [SuperAdminController::class, 'showLoginForm'])->name('super-admin.login');
    Route::post('/super-admin/login', [SuperAdminController::class, 'login'])->name('super-admin.login.store');

    Route::middleware('auth')->group(function () {
        Route::get('/super-admin/dashboard', [SuperAdminController::class, 'dashboard'])->name('super-admin.dashboard');
        Route::post('/super-admin/tenants/{tenant}/suspend', [SuperAdminController::class, 'suspendTenant'])->name('super-admin.tenants.suspend');
        Route::post('/super-admin/tenants/{tenant}/unsuspend', [SuperAdminController::class, 'unsuspendTenant'])->name('super-admin.tenants.unsuspend');
        Route::post('/super-admin/tenants/{tenant}/approve', [SuperAdminController::class, 'approveTenant'])->name('super-admin.tenants.approve');
        Route::post('/super-admin/tenants/{tenant}/decline', [SuperAdminController::class, 'declineTenant'])->name('super-admin.tenants.decline');
        Route::post('/super-admin/tenants/{tenant}/subscription/renew', [SuperAdminController::class, 'renewTenantSubscription'])->name('super-admin.tenants.subscription.renew');
        Route::patch('/super-admin/tenants/{tenant}/subscription/plan', [SuperAdminController::class, 'changeTenantSubscriptionPlan'])->name('super-admin.tenants.subscription.plan.update');
        Route::patch('/super-admin/support-tickets/{supportTicket}/status', [SuperAdminController::class, 'updateSupportTicketStatus'])->name('super-admin.support-tickets.status.update');
        Route::post('/super-admin/central-admins', [SuperAdminController::class, 'storeCentralAdmin'])->name('super-admin.central-admins.store');
        Route::patch('/super-admin/central-admins/{user}/role', [SuperAdminController::class, 'updateCentralAdminRole'])->name('super-admin.central-admins.role.update');
        Route::delete('/super-admin/central-admins/{user}', [SuperAdminController::class, 'destroyCentralAdmin'])->name('super-admin.central-admins.destroy');
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
                ->name('tenant.login');
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
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');
            Route::put('/dashboard/layout', [DashboardController::class, 'updateLayout'])->name('tenant.dashboard.layout.update');
            Route::post('/dashboard/layout/reset', [DashboardController::class, 'resetLayout'])->name('tenant.dashboard.layout.reset');

            Route::post('logout', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
                ->name('tenant.logout');

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

            Route::resource('brewing-guides', BrewingGuideController::class)
                ->parameters(['brewing-guides' => 'brewingGuide']);
            Route::get('/support/tickets', [SupportTicketController::class, 'create'])
                ->middleware('can:manage users')
                ->name('support-tickets.create');
            Route::post('/support/tickets', [SupportTicketController::class, 'store'])
                ->middleware('can:manage users')
                ->name('support-tickets.store');
            // ... more routes
            Route::get('/checkout', [PaymentController::class, 'checkout'])->name('checkout');
            Route::post('/create-checkout-session', [PaymentController::class, 'createCheckoutSession'])->name('checkout.session');
            Route::get('/checkout/success', [PaymentController::class, 'success'])->name('checkout.success');
            Route::get('/checkout/cancel', [PaymentController::class, 'cancel'])->name('checkout.cancel');
        });

        // If you have password reset or other auth routes, include them as needed.
        // You can also require __DIR__.'/auth.php' but careful: that file may define routes
        // without subdomain. Instead, copy the necessary routes here.
    });