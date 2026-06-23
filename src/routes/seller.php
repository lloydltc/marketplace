<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\Seller\DashboardController;
use App\Http\Controllers\Seller\SalesController;
use App\Modules\Media\Controllers\Seller\VehicleImageController as SellerVehicleImageController;
use App\Modules\Vehicles\Controllers\PrivateSeller\VehicleController as SellerVehicleController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Sales / Enquiries (lead-gen — no transactional orders for private sellers)
Route::get('sales', [SalesController::class, 'index'])->name('sales.index');

// Leads (D6 — buyers who contacted this seller)
Route::get('leads', [LeadController::class, 'sellerIndex'])->name('leads.index');
Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');

// Vehicle listings
Route::get('vehicles', [SellerVehicleController::class, 'index'])->name('vehicles.index');
Route::get('vehicles/create', [SellerVehicleController::class, 'create'])->name('vehicles.create');
Route::post('vehicles', [SellerVehicleController::class, 'store'])->middleware('throttle:30,1')->name('vehicles.store');
Route::get('vehicles/{vehicle}', [SellerVehicleController::class, 'show'])->name('vehicles.show');
Route::post('vehicles/{vehicle}/renew', [SellerVehicleController::class, 'renew'])->name('vehicles.renew');
Route::get('vehicles/{vehicle}/edit', [SellerVehicleController::class, 'edit'])->name('vehicles.edit');
Route::put('vehicles/{vehicle}', [SellerVehicleController::class, 'update'])->name('vehicles.update');
Route::delete('vehicles/{vehicle}', [SellerVehicleController::class, 'destroy'])->name('vehicles.destroy');

// Vehicle image management
Route::post('vehicles/{vehicle}/images', [SellerVehicleImageController::class, 'store'])->middleware('throttle:60,1')->name('vehicles.images.store');
Route::delete('vehicles/{vehicle}/images/{image}', [SellerVehicleImageController::class, 'destroy'])->name('vehicles.images.destroy');
Route::post('vehicles/{vehicle}/images/reorder', [SellerVehicleImageController::class, 'reorder'])->name('vehicles.images.reorder');
