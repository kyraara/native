<?php

use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MajorController as AdminMajorController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\NotificationSettingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/order', [OrderController::class, 'create'])->block(10, 10)->name('order.create');
Route::post('/order', [OrderController::class, 'store'])->middleware('throttle:5,1,order-submit:')->block(60, 10)->name('order.store');
Route::match(['get', 'post'], '/track', [OrderController::class, 'trackSearch'])->middleware('throttle:15,1,tracking-search:')->block(10, 10)->name('order.trackSearch');
Route::get('/track/{token}', [OrderController::class, 'track'])->middleware('throttle:30,1,tracking-page:')->name('order.track');
Route::get('/track/{token}/status', [OrderController::class, 'trackStatus'])->middleware('throttle:60,1,tracking-status:')->name('order.trackStatus');

// AI Customer Service (Cuy-AI)
Route::prefix('chat')->middleware('throttle:20,1,cuy-ai:')->group(function () {
    Route::post('/send', [ChatController::class, 'send'])->block(60, 10)->name('chat.send');
    Route::post('/reset', [ChatController::class, 'reset'])->block(10, 10)->name('chat.reset');
    Route::get('/history', [ChatController::class, 'history'])->block(10, 10)->name('chat.history');
});

// Admin routes
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/notifications', NotificationController::class)->name('admin.notifications');
    Route::get('/reports/revenue', [RevenueController::class, 'index'])->name('admin.reports.revenue');
    Route::get('/reports/revenue/export', [RevenueController::class, 'export'])->name('admin.reports.revenue.export');
    Route::get('/orders/export', [AdminOrderController::class, 'export'])->name('admin.orders.export');
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/create', [AdminOrderController::class, 'create'])->name('admin.orders.create');
    Route::post('/orders', [AdminOrderController::class, 'store'])->name('admin.orders.store');
    Route::get('/orders/{order:tracking_token}/edit', [AdminOrderController::class, 'edit'])->name('admin.orders.edit');
    Route::put('/orders/{order:tracking_token}', [AdminOrderController::class, 'update'])->name('admin.orders.update');
    Route::get('/orders/{order:tracking_token}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::get('/orders/{order:tracking_token}/attachment', [AdminOrderController::class, 'downloadAttachment'])->name('admin.orders.attachment');
    Route::patch('/orders/{order:tracking_token}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.updateStatus');
    Route::delete('/orders/{order:tracking_token}', [AdminOrderController::class, 'destroy'])->name('admin.orders.destroy');
    Route::get('/catalog', [AdminCatalogController::class, 'index'])->name('admin.catalog.index');
    Route::post('/majors', [AdminMajorController::class, 'store'])->name('admin.majors.store');
    Route::put('/majors/{major}', [AdminMajorController::class, 'update'])->name('admin.majors.update');
    Route::delete('/majors/{major}', [AdminMajorController::class, 'destroy'])->name('admin.majors.destroy');
    Route::get('/services/create', [AdminServiceController::class, 'create'])->name('admin.services.create');
    Route::post('/services', [AdminServiceController::class, 'store'])->name('admin.services.store');
    Route::get('/services/{service}/edit', [AdminServiceController::class, 'edit'])->name('admin.services.edit');
    Route::post('/services/{service}/duplicate', [AdminServiceController::class, 'duplicate'])->name('admin.services.duplicate');
    Route::put('/services/{service}', [AdminServiceController::class, 'update'])->name('admin.services.update');
    Route::delete('/services/{service}', [AdminServiceController::class, 'destroy'])->name('admin.services.destroy');
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('admin.settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('admin.settings.update');
    Route::get('/settings/notifications', [NotificationSettingController::class, 'index'])->name('admin.settings.notifications');
    Route::put('/settings/notifications', [NotificationSettingController::class, 'update'])->name('admin.settings.notifications.update');
    Route::post('/settings/notifications/test', [NotificationSettingController::class, 'test'])->middleware('throttle:5,1')->name('admin.settings.notifications.test');
    Route::post('/settings/notifications/test-email', [NotificationSettingController::class, 'testEmail'])->middleware('throttle:5,1')->name('admin.settings.notifications.testEmail');
    Route::get('/settings/testimonials', [TestimonialController::class, 'index'])->name('admin.testimonials.index');
    Route::get('/settings/testimonials/create', [TestimonialController::class, 'create'])->name('admin.testimonials.create');
    Route::post('/settings/testimonials', [TestimonialController::class, 'store'])->name('admin.testimonials.store');
    Route::get('/settings/testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])->name('admin.testimonials.edit');
    Route::put('/settings/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('admin.testimonials.update');
    Route::patch('/settings/testimonials/{testimonial}/visibility', [TestimonialController::class, 'visibility'])->name('admin.testimonials.visibility');
    Route::delete('/settings/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('admin.testimonials.destroy');
});

require __DIR__.'/auth.php';
