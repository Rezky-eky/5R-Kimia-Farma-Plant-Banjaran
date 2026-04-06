<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoActionController;
use App\Http\Controllers\GoBoostController;
use App\Http\Controllers\GoCareController;
use App\Http\Controllers\GoOfferController;
use App\Http\Controllers\GoSaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        // Fitur "Daftar" (register) dinonaktifkan: route masih ada, tapi link tidak ditampilkan.
        'canRegister' => false,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // GO ACTION Routes
    Route::get('/go-action/create', [GoActionController::class, 'create'])->name('go_action.create');
    Route::post('/go-action/import-dbr', [GoActionController::class, 'importDbr'])->name('go_action.import_dbr');
    Route::post('/go-action', [GoActionController::class, 'store'])->name('go_action.store');
    Route::get('/go-action/dbr', [GoActionController::class, 'dbrIndex'])->name('go_action.dbr_index');
    
    // GO BOOST Routes
    Route::get('/go-boost', [GoBoostController::class, 'index'])->name('go_boost.index');
    Route::get('/go-boost/create', [GoBoostController::class, 'create'])->name('go_boost.create');
    Route::post('/go-boost', [GoBoostController::class, 'store'])->name('go_boost.store');
    
    // GO CARE Routes
    Route::get('/go-care/create', [GoCareController::class, 'create'])->name('go_care.create');
    Route::post('/go-care', [GoCareController::class, 'store'])->name('go_care.store');
    
    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    
    // GO BOOST Perbaikan Routes
    Route::post('/go-boost/{id}/perbaikan', [GoBoostController::class, 'submitPerbaikan'])->name('go_boost.submitPerbaikan');

    // Go Offer (tawaran barang DBR ke departemen lain)
    Route::get('/go-offer', [GoOfferController::class, 'index'])->name('go_offer.index');
    Route::get('/go-offer/create', [GoOfferController::class, 'create'])->name('go_offer.create');
    Route::post('/go-offer', [GoOfferController::class, 'store'])->name('go_offer.store');
    Route::post('/go-offer/{id}/accept', [GoOfferController::class, 'accept'])->name('go_offer.accept');
    Route::post('/go-offer/{id}/reject', [GoOfferController::class, 'reject'])->name('go_offer.reject');

    // Go Sale (penjualan barang DBR dengan harga kesepakatan)
    Route::get('/go-sale', [GoSaleController::class, 'index'])->name('go_sale.index');
    Route::get('/go-sale/create', [GoSaleController::class, 'create'])->name('go_sale.create');
    Route::post('/go-sale', [GoSaleController::class, 'store'])->name('go_sale.store');
    Route::post('/go-sale/{id}/accept', [GoSaleController::class, 'accept'])->name('go_sale.accept');
    Route::post('/go-sale/{id}/reject', [GoSaleController::class, 'reject'])->name('go_sale.reject');
    Route::post('/go-sale/{id}/complete', [GoSaleController::class, 'complete'])->name('go_sale.complete');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Audit Routes
    Route::get('/audit', [AdminController::class, 'auditIndex'])->name('audit.index');
    Route::get('/audit/{id}', [AdminController::class, 'auditDetail'])->name('audit.detail');
    Route::post('/audit/{id}', [AdminController::class, 'storeAudit'])->name('audit.store');
    Route::post('/audit/{id}/approve', [AdminController::class, 'auditApprove'])->name('audit.approve');
    Route::post('/audit/{id}/reject', [AdminController::class, 'auditReject'])->name('audit.reject');
    
    // Reward Routes
    Route::get('/reward', [AdminController::class, 'rewardIndex'])->name('reward.index');
    Route::post('/reward', [AdminController::class, 'rewardStore'])->name('reward.store');
    Route::put('/reward/{id}', [AdminController::class, 'rewardUpdate'])->name('reward.update');
    Route::delete('/reward/{id}', [AdminController::class, 'rewardDestroy'])->name('reward.destroy');
    
    // Data Management Routes
    Route::get('/go-action', [AdminController::class, 'goActionIndex'])->name('go_action.index');
    Route::get('/go-boost', [AdminController::class, 'goBoostIndex'])->name('go_boost.index');
    Route::get('/go-boost/{id}', [AdminController::class, 'goBoostDetail'])->name('go_boost.detail');
    Route::get('/go-care', [AdminController::class, 'goCareIndex'])->name('go_care.index');
    Route::get('/go-care/{id}', [AdminController::class, 'goCareDetail'])->name('go_care.detail');

    // Laporan 5R Keseluruhan (dulu Kelola Audit)
    // audit.index, audit.detail, audit.store tetap dipakai

    // Go Reward - Dashboard pemenang (ganti Kelola Reward)
    Route::get('/go-reward', [AdminController::class, 'goReward'])->name('go_reward');
});

require __DIR__.'/auth.php';
