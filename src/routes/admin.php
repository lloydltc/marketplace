<?php

use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\Admin\CashSessionController;
use App\Http\Controllers\Admin\ConciergeController as AdminConciergeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryZoneController;
use App\Http\Controllers\Admin\DispatchController;
use App\Http\Controllers\Admin\PayoutController;
use App\Http\Controllers\Admin\PlatformSettingController;
use App\Http\Controllers\Admin\PromotionPackageController;
use App\Http\Controllers\Admin\RfqModerationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WalletAdjustmentController;
use App\Http\Controllers\Admin\UserTierController;
use App\Http\Controllers\Admin\VendorTierController;
use App\Modules\Categories\Controllers\Admin\CategoryController;
use App\Modules\Products\Controllers\Admin\ProductApprovalController;
use App\Modules\Products\Controllers\Admin\ProductController as AdminProductController;
use App\Modules\Vehicles\Controllers\Admin\VehicleApprovalController;
use App\Modules\Vehicles\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Modules\Vendors\Controllers\Admin\VendorApprovalController;
use App\Modules\Vendors\Controllers\Admin\VendorBankAccountAdminController;
use App\Modules\Vendors\Controllers\Admin\VendorController;
use App\Modules\Vendors\Controllers\Admin\VendorDocumentController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Pending applications (vendor + private seller)
Route::prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::post('{user}/approve', [ApplicationController::class, 'approve'])->name('approve');
    Route::post('{user}/reject', [ApplicationController::class, 'reject'])->name('reject');
});

// Platform settings (fees, thresholds, limits) — super_admin only.
Route::middleware('role:super_admin')->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [PlatformSettingController::class, 'index'])->name('index');
    Route::put('/', [PlatformSettingController::class, 'update'])->name('update');
});

// Vendor payouts (weekly batch + approval)
Route::prefix('payouts')->name('payouts.')->group(function () {
    Route::get('/', [PayoutController::class, 'index'])->name('index');
    Route::post('generate', [PayoutController::class, 'generate'])->name('generate');
    Route::post('{payout}/approve', [PayoutController::class, 'approve'])->name('approve');
    Route::post('{payout}/mark-paid', [PayoutController::class, 'markPaid'])->name('mark-paid');
    Route::post('{payout}/reject', [PayoutController::class, 'reject'])->name('reject');
});

// Manual wallet adjustment (audited)
Route::post('vendors/{vendor}/wallet/adjust', [WalletAdjustmentController::class, 'store'])->name('vendors.wallet.adjust');

// Delivery dispatch (assign riders to FBS orders)
Route::get('dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
Route::post('dispatch/{order}/assign', [DispatchController::class, 'assign'])->name('dispatch.assign');

// Rider cash reconciliation (gates FBS-COD settlement)
Route::prefix('cash-sessions')->name('cash-sessions.')->group(function () {
    Route::get('/', [CashSessionController::class, 'index'])->name('index');
    Route::post('{session}/reconcile', [CashSessionController::class, 'reconcile'])->name('reconcile');
    Route::post('{session}/resolve', [CashSessionController::class, 'resolve'])->name('resolve');
});

// Delivery zones
Route::prefix('delivery-zones')->name('delivery-zones.')->group(function () {
    Route::get('/', [DeliveryZoneController::class, 'index'])->name('index');
    Route::post('/', [DeliveryZoneController::class, 'store'])->name('store');
    Route::post('{zone}/toggle', [DeliveryZoneController::class, 'toggle'])->name('toggle');
});

// RFQ moderation (spam/abuse)
Route::prefix('rfq')->name('rfq.')->group(function () {
    Route::get('/', [RfqModerationController::class, 'index'])->name('index');
    Route::post('{partRequest}/reject', [RfqModerationController::class, 'reject'])->name('reject');
});

// Concierge workflow queue
Route::prefix('concierge')->name('concierge.')->group(function () {
    Route::get('/', [AdminConciergeController::class, 'index'])->name('index');
    Route::get('{conciergeRequest}', [AdminConciergeController::class, 'show'])->name('show');
    Route::post('{conciergeRequest}/quote', [AdminConciergeController::class, 'quote'])->name('quote');
    Route::post('{conciergeRequest}/transition', [AdminConciergeController::class, 'transition'])->name('transition');
});

// Promotion packages catalog + revenue summary
Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::get('/', [PromotionPackageController::class, 'index'])->name('index');
    Route::post('/', [PromotionPackageController::class, 'store'])->name('store');
    Route::post('{package}/toggle', [PromotionPackageController::class, 'toggle'])->name('toggle');
});

// Category management
Route::resource('categories', CategoryController::class)->except(['show']);

// Leads (D6 — site-wide funnel)
Route::get('leads', [\App\Http\Controllers\LeadController::class, 'adminIndex'])->name('leads.index');
Route::put('leads/{lead}', [\App\Http\Controllers\LeadController::class, 'update'])->name('leads.update');

// Vehicle feature definitions (D4 — dynamic, admin-managed specs/features)
Route::resource('vehicle-features', \App\Modules\Vehicles\Controllers\Admin\FeatureDefinitionController::class)->except(['show', 'destroy']);
Route::post('vehicle-features/{vehicle_feature}/toggle', [\App\Modules\Vehicles\Controllers\Admin\FeatureDefinitionController::class, 'toggle'])->name('vehicle-features.toggle');

// Product management
Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [AdminProductController::class, 'index'])->name('index');
    Route::get('{product}', [AdminProductController::class, 'show'])->name('show');
    Route::post('{product}/approve', [ProductApprovalController::class, 'approve'])->name('approve');
    Route::post('{product}/reject', [ProductApprovalController::class, 'reject'])->name('reject');
});

// Vehicle management
Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/', [AdminVehicleController::class, 'index'])->name('index');
    Route::get('{vehicle}', [AdminVehicleController::class, 'show'])->name('show');
    Route::post('{vehicle}/approve', [VehicleApprovalController::class, 'approve'])->name('approve');
    Route::post('{vehicle}/reject', [VehicleApprovalController::class, 'reject'])->name('reject');
});

// HR4: vehicle history reports admin (sources, manual entry, refunds)
Route::prefix('history')->name('history.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\HistoryController::class, 'index'])->name('index');
    Route::post('sources/{source}', [\App\Http\Controllers\Admin\HistoryController::class, 'updateSource'])->name('sources.update');
    Route::post('{report}/service-records', [\App\Http\Controllers\Admin\HistoryController::class, 'addServiceRecord'])->name('service-records.add');
    Route::post('{report}/refund', [\App\Http\Controllers\Admin\HistoryController::class, 'refund'])->name('refund');
});

// H11: listing moderation queue
Route::get('moderation', [\App\Http\Controllers\Admin\ModerationController::class, 'index'])->name('moderation.index');
Route::post('moderation/{report}/resolve', [\App\Http\Controllers\Admin\ModerationController::class, 'resolve'])->name('moderation.resolve');

// PM9: canonical parts catalog admin (CRUD + OEM/fitment authoring + import + merge)
Route::prefix('parts')->name('parts.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\PartController::class, 'index'])->name('index');
    Route::get('create', [\App\Http\Controllers\Admin\PartController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\PartController::class, 'store'])->name('store');
    Route::get('import', [\App\Http\Controllers\Admin\PartImportController::class, 'create'])->name('import');
    Route::post('import', [\App\Http\Controllers\Admin\PartImportController::class, 'process'])->name('import.process');
    Route::post('merge', [\App\Http\Controllers\Admin\PartMergeController::class, 'merge'])->name('merge');
    Route::get('{part}/edit', [\App\Http\Controllers\Admin\PartController::class, 'edit'])->name('edit');
    Route::put('{part}', [\App\Http\Controllers\Admin\PartController::class, 'update'])->name('update');
    Route::delete('{part}', [\App\Http\Controllers\Admin\PartController::class, 'destroy'])->name('destroy');
    Route::post('{part}/oem', [\App\Http\Controllers\Admin\PartController::class, 'addOem'])->name('oem.add');
    Route::delete('{part}/oem/{oem}', [\App\Http\Controllers\Admin\PartController::class, 'removeOem'])->name('oem.remove');
    Route::post('{part}/fitments', [\App\Http\Controllers\Admin\PartController::class, 'addFitment'])->name('fitments.add');
    Route::delete('{part}/fitments/{fitment}', [\App\Http\Controllers\Admin\PartController::class, 'removeFitment'])->name('fitments.remove');
});

// Vendor management
Route::prefix('vendors')->name('vendors.')->group(function () {
    Route::get('/', [VendorController::class, 'index'])->name('index');
    Route::get('{vendor}', [VendorController::class, 'show'])->name('show');

    // Approval actions
    Route::post('{vendor}/approve', [VendorApprovalController::class, 'approve'])->name('approve');
    Route::post('{vendor}/reject', [VendorApprovalController::class, 'reject'])->name('reject');
    Route::post('{vendor}/suspend', [VendorApprovalController::class, 'suspend'])->name('suspend');
    Route::post('{vendor}/reactivate', [VendorApprovalController::class, 'reactivate'])->name('reactivate');

    // Document review
    Route::get('{vendor}/documents', [VendorDocumentController::class, 'index'])->name('documents');
    Route::post('{vendor}/documents/{document}/review', [VendorDocumentController::class, 'review'])->name('documents.review');

    // Bank account verification
    Route::post('{vendor}/bank-accounts/{account}/verify', [VendorBankAccountAdminController::class, 'verify'])->name('bank.verify');

    // Tier management
    Route::post('{vendor}/tier', [VendorTierController::class, 'update'])->name('tier.update');

    // VB2: per-dimension verification decisions (recomputes badge tier, audited)
    Route::post('{vendor}/verifications/{dimension}', [\App\Http\Controllers\Admin\VendorVerificationController::class, 'update'])->name('verifications.update');

    // VB4: badge revocation/reinstatement + manual tier grant (audited)
    Route::post('{vendor}/badge', [\App\Http\Controllers\Admin\VendorBadgeController::class, 'update'])->name('badge.update');

    // H8: featured-dealer placement (paid)
    Route::post('{vendor}/feature', [\App\Http\Controllers\Admin\DealerFeatureController::class, 'store'])->name('feature');
    Route::delete('{vendor}/feature', [\App\Http\Controllers\Admin\DealerFeatureController::class, 'destroy'])->name('unfeature');
});

// User management (private sellers + all roles)
Route::prefix('users')->name('users.')->group(function () {
    // Read-only listing/detail — admin + super_admin (route group is role:super_admin|admin)
    Route::get('/', [UserController::class, 'index'])->name('index');

    // Privileged, destructive user management — super_admin only, all audit-logged (R6).
    Route::middleware('role:super_admin')->group(function () {
        Route::get('create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::post('{user}/suspend', [UserManagementController::class, 'suspend'])->name('suspend');
        Route::post('{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('reactivate');
        Route::put('{user}/role', [UserManagementController::class, 'updateRole'])->name('role');
        Route::post('{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('reset-password');
        Route::post('{user}/verify-email', [UserManagementController::class, 'verifyEmail'])->name('verify-email');
    });

    Route::get('{user}', [UserController::class, 'show'])->name('show');
    Route::post('{user}/tier', [UserTierController::class, 'update'])->name('tier.update');
});
