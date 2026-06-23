<?php

use App\Http\Controllers\Vendor\DashboardController;
use App\Http\Controllers\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Vendor\PromotionController as VendorPromotionController;
use App\Http\Controllers\Vendor\RfqController as VendorRfqController;
use App\Http\Controllers\Vendor\TeamController;
use App\Http\Controllers\Vendor\WalletController as VendorWalletController;
use App\Http\Controllers\VendorInvitationController;
use App\Modules\Media\Controllers\Vendor\ProductImageController;
use App\Modules\Media\Controllers\Vendor\VehicleImageController as VendorVehicleImageController;
use App\Modules\Products\Controllers\Vendor\ProductController as VendorProductController;
use App\Modules\Vehicles\Controllers\Vendor\VehicleController as VendorVehicleController;
use App\Modules\Vendors\Controllers\Vendor\BankAccountController;
use App\Modules\Vendors\Controllers\Vendor\DocumentController;
use App\Modules\Vendors\Controllers\Vendor\ProfileController;
use Illuminate\Support\Facades\Route;

// Dashboard (vendor_admin + vendor_worker)
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Products (read-only for workers, full CRUD for admin)
// NOTE: {product} is UUID-constrained so it never shadows literal paths like
// /products/create (registered later in the vendor_admin group).
Route::get('products', [VendorProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [VendorProductController::class, 'show'])->name('products.show')->whereUuid('product');

// Vehicles (read-only for workers)
Route::get('vehicles', [VendorVehicleController::class, 'index'])->name('vehicles.index');
Route::get('vehicles/{vehicle}', [VendorVehicleController::class, 'show'])->name('vehicles.show')->whereUuid('vehicle');

// Profile (vendor_admin + vendor_worker can view; only vendor_admin can edit)
Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');

// Orders (vendor_admin + vendor_worker can view & advance fulfilment)
Route::get('orders', [VendorOrderController::class, 'index'])->name('orders.index');
Route::get('orders/{order}', [VendorOrderController::class, 'show'])->name('orders.show');
Route::post('orders/{order}/transition', [VendorOrderController::class, 'transition'])->name('orders.transition');

// Leads (D6 — buyers who contacted the vendor about a listing)
Route::get('leads', [\App\Http\Controllers\LeadController::class, 'vendorIndex'])->name('leads.index');
Route::put('leads/{lead}', [\App\Http\Controllers\LeadController::class, 'update'])->name('leads.update');

// Listing analytics (H5)
Route::get('analytics', [\App\Http\Controllers\ListingAnalyticsController::class, 'vendorIndex'])->name('analytics.index');

// RFQ — browse open part requests and quote
Route::get('requests', [VendorRfqController::class, 'index'])->name('requests.index');
Route::post('requests/{partRequest}/quote', [VendorRfqController::class, 'quote'])->name('requests.quote');

Route::middleware('role:vendor_admin')->group(function () {
    // Vehicle management (create/edit/delete restricted to vendor_admin)
    Route::get('vehicles/create', [VendorVehicleController::class, 'create'])->name('vehicles.create');
    Route::post('vehicles', [VendorVehicleController::class, 'store'])->middleware('throttle:30,1')->name('vehicles.store');
    Route::get('vehicles/{vehicle}/edit', [VendorVehicleController::class, 'edit'])->name('vehicles.edit');
    Route::put('vehicles/{vehicle}', [VendorVehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('vehicles/{vehicle}', [VendorVehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::post('vehicles/{vehicle}/renew', [VendorVehicleController::class, 'renew'])->name('vehicles.renew');

    // Vehicle image management
    Route::post('vehicles/{vehicle}/images', [VendorVehicleImageController::class, 'store'])->middleware('throttle:60,1')->name('vehicles.images.store');
    Route::delete('vehicles/{vehicle}/images/{image}', [VendorVehicleImageController::class, 'destroy'])->name('vehicles.images.destroy');
    Route::post('vehicles/{vehicle}/images/reorder', [VendorVehicleImageController::class, 'reorder'])->name('vehicles.images.reorder');

    // Product management (create/edit/delete restricted to vendor_admin)
    Route::get('products/create', [VendorProductController::class, 'create'])->name('products.create');
    Route::post('products', [VendorProductController::class, 'store'])->middleware('throttle:30,1')->name('products.store');
    Route::get('products/{product}/edit', [VendorProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [VendorProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [VendorProductController::class, 'destroy'])->name('products.destroy');

    // Product image management
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->middleware('throttle:60,1')->name('products.images.store');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
    Route::post('products/{product}/images/reorder', [ProductImageController::class, 'reorder'])->name('products.images.reorder');

    // Invitations
    Route::get('invite', [VendorInvitationController::class, 'create'])->name('invitation.create');
    Route::post('invite', [VendorInvitationController::class, 'store'])->name('invitation.store');

    // Team management (scoped to this vendor server-side)
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::put('team/{user}/role', [TeamController::class, 'updateRole'])->name('team.role');
    Route::delete('team/{user}', [TeamController::class, 'remove'])->name('team.remove');

    // Profile editing
    Route::get('profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Bank accounts
    Route::get('bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
    Route::post('bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
    Route::delete('bank-accounts/{account}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

    // Documents
    Route::get('documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('documents', [DocumentController::class, 'store'])->name('documents.store');

    // Wallet (vendor_admin only — money)
    Route::get('wallet', [VendorWalletController::class, 'show'])->name('wallet.show');
    Route::post('wallet/top-up', [VendorWalletController::class, 'topUp'])->name('wallet.topup');

    // Listing promotion (vendor_admin only — money)
    Route::post('vehicles/{vehicle}/promote', [VendorPromotionController::class, 'promote'])->name('vehicles.promote');
    Route::get('promotions/packages', [VendorPromotionController::class, 'packages'])->name('promotions.packages');
    Route::post('promotions/packages/{package}/buy', [VendorPromotionController::class, 'buyPackage'])->name('promotions.buy');
});
