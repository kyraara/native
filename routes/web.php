<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/order', [OrderController::class, 'create'])->name('order.create');
Route::post('/order', [OrderController::class, 'store'])->name('order.store');
Route::match(['get', 'post'], '/track', [OrderController::class, 'trackSearch'])->name('order.trackSearch');
Route::get('/track/{token}', [OrderController::class, 'track'])->name('order.track');

// Admin routes
Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('admin.orders.export');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order:tracking_token}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::patch('/orders/{order:tracking_token}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
    Route::delete('/orders/{order:tracking_token}', [AdminOrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');
});

require __DIR__.'/auth.php';
