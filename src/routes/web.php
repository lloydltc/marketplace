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

// Design-system component gallery — non-production only (renders every base component)
if (! app()->environment('production')) {
    Route::get('_dev/components', function () {
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            range(1, 10), 48, 10, 2, ['path' => url('_dev/components')]
        );

        return view('dev.components', compact('paginator'));
    })->name('dev.components');
}

// Public product browsing (no auth required)
Route::get('products', [PublicProductController::class, 'index'])->name('products.index');
Route::get('products/{product}', [PublicProductController::class, 'show'])->name('products.show');

// Public vehicle browsing (no auth required)
Route::get('vehicles', [PublicVehicleController::class, 'index'])->name('vehicles.index');
Route::get('vehicles/{vehicle}', [PublicVehicleController::class, 'show'])->name('vehicles.show');

// D6: record a contact/lead and reveal seller details (public, guest-friendly, rate-limited)
Route::post('vehicles/{vehicle}/contact', [\App\Http\Controllers\ListingContactController::class, 'vehicle'])
    ->middleware('throttle:20,1')->name('vehicles.contact');

// H3: download all listing images as a zip (watermarked derivatives)
Route::get('vehicles/{vehicle}/images/download', [PublicVehicleController::class, 'downloadImages'])
    ->middleware('throttle:10,1')->name('vehicles.images.download');

// Unified public search results (products + vehicles) — D2
Route::get('search', [SearchController::class, 'index'])->name('search.index');

// Public search autocomplete (JSON)
Route::get('search/products', [SearchController::class, 'products'])->name('search.products');
Route::get('search/vehicles', [SearchController::class, 'vehicles'])->name('search.vehicles');

// H6: live inventory count for the vehicle filter form (JSON)
Route::get('search/vehicles/count', [SearchController::class, 'vehicleCount'])->name('search.vehicles.count');

// PM4: public parts catalog browse + basic VIN search
Route::get('parts', [\App\Http\Controllers\PartCatalogController::class, 'index'])->name('parts.index');
Route::post('parts/vin', [\App\Http\Controllers\PartCatalogController::class, 'vinSearch'])
    ->middleware('throttle:20,1')->name('parts.vin');
Route::get('parts/{part:slug}', [\App\Http\Controllers\PartCatalogController::class, 'show'])->name('parts.show');

// PM6: public service-kit bundles
Route::get('kits/{bundle:slug}', [\App\Http\Controllers\BundleController::class, 'show'])->name('bundles.show');

// PM3: cascading fitment selector — JSON cascade + session select/clear (public)
Route::get('fitment/models', [\App\Http\Controllers\FitmentController::class, 'models'])->name('fitment.models');
Route::get('fitment/generations', [\App\Http\Controllers\FitmentController::class, 'generations'])->name('fitment.generations');
Route::get('fitment/variants', [\App\Http\Controllers\FitmentController::class, 'variants'])->name('fitment.variants');
Route::post('fitment/select', [\App\Http\Controllers\FitmentController::class, 'select'])->name('fitment.select');
Route::post('fitment/clear', [\App\Http\Controllers\FitmentController::class, 'clear'])->name('fitment.clear');

// H11: report a listing for moderation (public, rate-limited)
Route::post('vehicles/{vehicle}/report', [\App\Http\Controllers\ReportController::class, 'vehicle'])
    ->middleware('throttle:10,1')->name('vehicles.report');
Route::post('products/{product}/report', [\App\Http\Controllers\ReportController::class, 'product'])
    ->middleware('throttle:10,1')->name('products.report');

// H8: public dealer directory + storefronts (approved vendors only)
Route::get('dealers', [\App\Http\Controllers\DealerController::class, 'index'])->name('dealers.index');
Route::get('dealers/{vendor:slug}', [\App\Http\Controllers\DealerController::class, 'show'])->name('dealers.show');

// H7: side-by-side vehicle comparison (session-backed, public)
Route::get('compare', [\App\Http\Controllers\CompareController::class, 'show'])->name('compare.show');
Route::post('compare/{vehicle}', [\App\Http\Controllers\CompareController::class, 'add'])->name('compare.add');
Route::delete('compare/{vehicle}', [\App\Http\Controllers\CompareController::class, 'remove'])->name('compare.remove');
Route::delete('compare', [\App\Http\Controllers\CompareController::class, 'clear'])->name('compare.clear');

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

    // PM6: add a whole service kit (expands to component cart lines)
    Route::post('kits/{bundle}/add', [\App\Http\Controllers\BundleController::class, 'addToCart'])->name('bundles.add');

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
        Route::patch('saved-searches/{savedSearch}', [SavedSearchController::class, 'update'])->name('saved-searches.update');
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
