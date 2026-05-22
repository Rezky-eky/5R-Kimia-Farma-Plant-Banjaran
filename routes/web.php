<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoActionController;
use App\Http\Controllers\GoBoostController;
use App\Http\Controllers\GoCareController;
use App\Http\Controllers\GoCheckController;
use App\Http\Controllers\GoCheckManagementController;
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

    // Go Check — tim 5R (finder)
    Route::middleware('five_r.team')->group(function () {
        Route::get('/go-check/create', [GoCheckController::class, 'create'])->name('go_check.create');
        Route::post('/go-check', [GoCheckController::class, 'store'])->name('go_check.store');
    });
    Route::post('/go-check/{id}/perbaikan', [GoCheckController::class, 'submitPerbaikan'])->name('go_check.submitPerbaikan');

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
    
    // Reward Routes
    Route::get('/reward', [AdminController::class, 'rewardIndex'])->name('reward.index');
    Route::post('/reward', [AdminController::class, 'rewardStore'])->name('reward.store');
    Route::put('/reward/{id}', [AdminController::class, 'rewardUpdate'])->name('reward.update');
    Route::delete('/reward/{id}', [AdminController::class, 'rewardDestroy'])->name('reward.destroy');
    
    // Data Management Routes
    Route::get('/go-action', [AdminController::class, 'goActionIndex'])->name('go_action.index');
    Route::get('/go-action/realisasi-mingguan', [AdminController::class, 'goActionWeeklyRealization'])->name('go_action.weekly_realization');
    Route::get('/go-action/realisasi-mingguan-export.xlsx', [AdminController::class, 'goActionWeeklyRealizationExport'])->name('go_action.weekly_realization_export');
    Route::get('/go-action/dbr-import', [AdminController::class, 'goActionDbrImport'])->name('go_action.dbr_import');
    Route::post('/go-action/dbr-import', [AdminController::class, 'goActionDbrImportStore'])->name('go_action.dbr_import_store');
    Route::get('/go-action/dbr-template.xlsx', [AdminController::class, 'goActionDbrTemplate'])->name('go_action.dbr_template');
    Route::get('/go-action/dbr-export.xlsx', [AdminController::class, 'goActionDbrExport'])->name('go_action.dbr_export');
    Route::get('/go-boost', [AdminController::class, 'goBoostIndex'])->name('go_boost.index');
    Route::get('/go-boost/{id}', [AdminController::class, 'goBoostDetail'])->name('go_boost.detail');
    Route::post('/go-boost/{id}/approve', [AdminController::class, 'goBoostApprove'])->name('go_boost.approve');
    Route::post('/go-boost/{id}/reject', [AdminController::class, 'goBoostReject'])->name('go_boost.reject');
    Route::get('/go-care', [AdminController::class, 'goCareIndex'])->name('go_care.index');
    Route::get('/go-care/{id}', [AdminController::class, 'goCareDetail'])->name('go_care.detail');
    Route::post('/go-care/{id}/approve', [AdminController::class, 'goCareApprove'])->name('go_care.approve');
    Route::post('/go-care/{id}/reject', [AdminController::class, 'goCareReject'])->name('go_care.reject');

    // Laporan 5R Keseluruhan (dulu Kelola Audit)
    // audit.index, audit.detail, audit.store tetap dipakai

    // Go Reward - Dashboard pemenang (ganti Kelola Reward)
    Route::get('/go-reward', [AdminController::class, 'goReward'])->name('go_reward');
});

// Kelola Go Check — Admin, Ketua 5R, Sekretaris 5R
Route::middleware(['auth', 'go_check.management'])->prefix('kelola-go-check')->name('go_check.management.')->group(function () {
    Route::get('/', [GoCheckManagementController::class, 'dashboard'])->name('dashboard');
    Route::get('/tim-5r', [GoCheckManagementController::class, 'teamIndex'])->name('team');
    Route::post('/tim-5r/role', [GoCheckManagementController::class, 'updateMemberRole'])->name('team.role');
    Route::post('/tim-5r/penugasan', [GoCheckManagementController::class, 'syncAssignments'])->name('team.assignments');
    Route::get('/data', [GoCheckManagementController::class, 'goCheckIndex'])->name('index');
    Route::post('/{id}/approve', [GoCheckManagementController::class, 'approve'])->name('approve');
    Route::post('/{id}/reject', [GoCheckManagementController::class, 'reject'])->name('reject');
});

require __DIR__.'/auth.php';
