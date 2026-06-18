<?php

use App\Http\Controllers\Rider\DashboardController;
use App\Http\Controllers\Rider\DeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Deliveries (assigned work, pickup, proof of delivery + COD cash)
Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
Route::post('deliveries/{delivery}/pickup', [DeliveryController::class, 'pickUp'])->name('deliveries.pickup');
Route::post('deliveries/{delivery}/deliver', [DeliveryController::class, 'deliver'])->name('deliveries.deliver');
