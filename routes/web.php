<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Customer;
use App\Http\Controllers\Delivery\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [Customer\HomeController::class, 'index'])->name('home');
Route::get('/search/suggest', [Customer\SearchController::class, 'suggest'])->middleware('throttle:120,1')->name('search.suggest');
Route::get('/products', [Customer\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [Customer\ProductController::class, 'show'])->name('products.show');
Route::get('/categories/{category}', [Customer\CategoryController::class, 'show'])->name('categories.show');
Route::get('/pages/{page}', [Customer\PageController::class, 'show'])
    ->whereIn('page', array_keys(Customer\PageController::PAGES))
    ->name('pages.show');

Route::get('/cart', [Customer\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [Customer\CartController::class, 'store'])->name('cart.items.store');
Route::patch('/cart/items/{cartItem}', [Customer\CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [Customer\CartController::class, 'destroy'])->name('cart.items.destroy');

Route::middleware('guest')->group(function () {
    // Customers: mobile number + SMS code (signs up new numbers too).
    Route::get('/login', [OtpLoginController::class, 'create'])->name('login');
    Route::post('/login/otp', [OtpLoginController::class, 'send'])->middleware('throttle:otp-send')->name('login.otp');
    Route::get('/login/verify', [OtpLoginController::class, 'showVerify'])->name('login.verify');
    Route::post('/login/verify', [OtpLoginController::class, 'verify'])->middleware('throttle:otp-verify');
    Route::redirect('/register', '/login')->name('register');

    // Admin and delivery staff: password.
    Route::get('/staff/login', [LoginController::class, 'create'])->name('staff.login');
    Route::post('/staff/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/checkout', [Customer\CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [Customer\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

    Route::get('/orders', [Customer\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:order_number}', [Customer\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order:order_number}/cancel', [Customer\OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/orders/{order:order_number}/upi-reference', [Customer\OrderController::class, 'upiReference'])->name('orders.upi-reference');

    Route::get('/account/addresses', [Customer\AddressController::class, 'index'])->name('addresses.index');
    Route::post('/account/addresses', [Customer\AddressController::class, 'store'])->name('addresses.store');
    Route::post('/account/addresses/{address}/default', [Customer\AddressController::class, 'makeDefault'])->name('addresses.default');
    Route::delete('/account/addresses/{address}', [Customer\AddressController::class, 'destroy'])->name('addresses.destroy');

    Route::get('/account/profile', [Customer\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/account/profile', [Customer\ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('categories', Admin\CategoryController::class)->except('show');
    Route::resource('products', Admin\ProductController::class)->except('show');

    Route::get('/inventory', [Admin\InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [Admin\InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/movements', [Admin\InventoryController::class, 'movements'])->name('inventory.movements');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/confirm', [Admin\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/orders/{order}/prepare', [Admin\OrderController::class, 'prepare'])->name('orders.prepare');
    Route::post('/orders/{order}/assign-delivery', [Admin\OrderController::class, 'assignDelivery'])->name('orders.assign-delivery');
    Route::post('/orders/{order}/dispatch', [Admin\OrderController::class, 'dispatch'])->name('orders.dispatch');
    Route::post('/orders/{order}/deliver', [Admin\OrderController::class, 'deliver'])->name('orders.deliver');
    Route::post('/orders/{order}/cancel', [Admin\OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('/payments/{payment}/verify', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');

    Route::get('/delivery-users', [Admin\DeliveryUserController::class, 'index'])->name('delivery-users.index');
    Route::post('/delivery-users', [Admin\DeliveryUserController::class, 'store'])->name('delivery-users.store');
    Route::post('/delivery-users/{user}/toggle', [Admin\DeliveryUserController::class, 'toggle'])->name('delivery-users.toggle');

    Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers/{user}/toggle', [Admin\CustomerController::class, 'toggle'])->name('customers.toggle');
    Route::get('/login-history', [Admin\LoginHistoryController::class, 'index'])->name('login-history.index');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');

    Route::get('/reports/sales', [Admin\ReportController::class, 'sales'])->name('reports.sales');
});

Route::middleware(['auth', 'role:delivery'])->prefix('delivery')->name('delivery.')->group(function () {
    Route::get('/', [DeliveryController::class, 'index'])->name('index');
    Route::get('/orders/{order}', [DeliveryController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pickup', [DeliveryController::class, 'pickup'])->name('orders.pickup');
    Route::post('/orders/{order}/delivered', [DeliveryController::class, 'delivered'])->name('orders.delivered');
});
