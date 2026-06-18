<?php

use App\Http\Controllers\Auth\ApplicationController;
use App\Http\Controllers\Auth\ApplicationStatusController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ConciergeController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RfqController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\VendorInvitationController;
use App\Modules\Products\Controllers\Public\ProductController as PublicProductController;
use App\Modules\Vehicles\Controllers\Public\VehicleController as PublicVehicleController;
use Illuminate\Support\Facades\Route;

// Auth routes (guest + authenticated auth flows) — groups handled internally
require base_path('routes/auth.php');

// Deep health check (DB/cache/storage) for uptime monitoring — P6.
Route::get('health', [HealthController::class, 'show'])->name('health');

// SEO sitemap (P9)
Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Public content / legal pages (P9)
Route::controller(PageController::class)->prefix('p')->name('pages.')->group(function () {
    Route::get('terms', 'terms')->name('terms');
    Route::get('privacy', 'privacy')->name('privacy');
    Route::get('cod-policy', 'codPolicy')->name('cod-policy');
    Route::get('how-fbs-works', 'howFbsWorks')->name('how-fbs-works');
    Route::get('request-a-part', 'rfqGuide')->name('rfq-guide');
    Route::get('fees', 'fees')->name('fees');
});

// Public landing — guests can browse the marketplace before authenticating
Route::get('/', [HomeController::class, 'index'])->name('home');

// Public product browsing (no auth required)
Route::get('products', [PublicProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [PublicProductController::class, 'show'])->name('products.show');

// Public vehicle browsing (no auth required)
Route::get('vehicles', [PublicVehicleController::class, 'index'])->name('vehicles.index');
Route::get('vehicles/{vehicle}', [PublicVehicleController::class, 'show'])->name('vehicles.show');

// Public search autocomplete (JSON)
Route::get('search/products', [SearchController::class, 'products'])->name('search.products');
Route::get('search/vehicles', [SearchController::class, 'vehicles'])->name('search.vehicles');

// "Can't find it? Request it" — RFQ entry point (full lifecycle in Phase 15)
Route::get('requests/new', [RfqController::class, 'create'])->name('rfq.create');

// Concierge — "we find it, verify it, deliver it" (form public; submit needs auth)
Route::get('concierge/new', [ConciergeController::class, 'create'])->name('concierge.create');

// Cart & checkout (session-backed; guests + shoppers only — staff roles 403 via shop.access)
Route::middleware('shop.access')->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart/items', [CartController::class, 'add'])->name('cart.add');
    Route::patch('cart/items/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/items/{product}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('cart', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('checkout/payment', [CheckoutController::class, 'payment'])->name('checkout.payment');
    Route::post('checkout/place', [CheckoutController::class, 'place'])
        ->middleware('throttle:20,1')->name('checkout.place');
    Route::get('checkout/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');
});

// Payments (Pesepay). Webhook is server-to-server: no auth, CSRF-exempt.
Route::post('payments/{order}/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
Route::post('payments/{order}/seamless', [PaymentController::class, 'seamless'])->name('payments.seamless');
Route::get('payments/{order}/return', [PaymentController::class, 'return'])->name('payments.return');
Route::post('payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');

// Public: vendor invitation accept (signed-URL token, no auth required)
Route::get('vendor/invite/{token}', [VendorInvitationController::class, 'accept'])
    ->name('vendor.invitation.accept');
Route::post('vendor/invite/{token}', [VendorInvitationController::class, 'acceptStore'])
    ->name('vendor.invitation.accept.store');

// Self-service applications (guest only)
Route::middleware('guest')->group(function () {
    Route::get('apply/vendor', [ApplicationController::class, 'createVendor'])->name('apply.vendor');
    Route::post('apply/vendor', [ApplicationController::class, 'storeVendor'])->name('apply.vendor.store');
    Route::get('apply/seller', [ApplicationController::class, 'createSeller'])->name('apply.seller');
    Route::post('apply/seller', [ApplicationController::class, 'storeSeller'])->name('apply.seller.store');
});

// Application status pages — auth + verified but NOT blocked by check.status
// (pending/rejected users need to reach these pages)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('application/pending', [ApplicationStatusController::class, 'pending'])->name('application.pending');
    Route::get('application/rejected', [ApplicationStatusController::class, 'rejected'])->name('application.rejected');
});

// Authenticated + verified + active status + force-password-check routes
Route::middleware(['auth', 'verified', 'check.status', 'force.password.change'])->group(function () {

    // ─── Buyer surfaces — customers only (a seller is NOT a customer) ───
    Route::middleware('role:customer')->group(function () {
        // Saved searches
        Route::get('saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
        Route::post('saved-searches', [SavedSearchController::class, 'store'])->name('saved-searches.store');
        Route::delete('saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');

        // Concierge — buyer (the public form lives at /concierge/new above)
        Route::get('concierge', [ConciergeController::class, 'index'])->name('concierge.index');
        Route::post('concierge', [ConciergeController::class, 'store'])->name('concierge.store');
        Route::get('concierge/{conciergeRequest}', [ConciergeController::class, 'show'])->name('concierge.show');
        Route::post('concierge/{conciergeRequest}/pay', [ConciergeController::class, 'pay'])->name('concierge.pay');

        // RFQ — buyer requests (the public form lives at /requests/new above)
        Route::get('requests', [RfqController::class, 'index'])->name('rfq.index');
        Route::post('requests', [RfqController::class, 'store'])
            ->middleware('throttle:15,1')->name('rfq.store');
        Route::get('requests/{partRequest}', [RfqController::class, 'show'])->name('rfq.show');
        Route::post('requests/{partRequest}/quotes/{quote}/accept', [RfqController::class, 'accept'])->name('rfq.accept');
        Route::post('requests/{partRequest}/close', [RfqController::class, 'close'])->name('rfq.close');

        // Buyer order history
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/confirm', [OrderController::class, 'confirmReceipt'])->name('orders.confirm');
    });

    // Admin & Super Admin
    Route::middleware('role:super_admin|admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(base_path('routes/admin.php'));

    // Vendor Admin & Worker
    Route::middleware(['role:vendor_admin|vendor_worker', 'vendor.scope'])
        ->prefix('vendor')
        ->name('vendor.')
        ->group(base_path('routes/vendor.php'));

    // Agent
    Route::middleware('role:agent')
        ->prefix('agent')
        ->name('agent.')
        ->group(base_path('routes/agent.php'));

    // Private Seller
    Route::middleware('role:private_seller')
        ->prefix('seller')
        ->name('seller.')
        ->group(base_path('routes/seller.php'));

    // Rider (delivery personnel)
    Route::middleware('role:rider')
        ->prefix('rider')
        ->name('rider.')
        ->group(base_path('routes/rider.php'));
});
