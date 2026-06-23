<?php

namespace App\Providers;

use App\Models\Vendor;
use App\Modules\Categories\Models\Category;
use App\Modules\Categories\Repositories\CategoryRepository;
use App\Modules\Categories\Repositories\CategoryRepositoryInterface;
use App\Modules\Vehicles\Events\VehicleApprovedEvent;
use App\Modules\Vehicles\Events\VehicleCreatedEvent;
use App\Modules\Vehicles\Events\VehicleRejectedEvent;
use App\Modules\Vehicles\Listeners\SendVehicleApprovedNotification;
use App\Modules\Vehicles\Listeners\SendVehicleRejectedNotification;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleMakeRepository;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepository;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use App\Policies\VehiclePolicy;
use App\Modules\Products\Events\ProductApprovedEvent;
use App\Modules\Products\Events\ProductCreatedEvent;
use App\Modules\Products\Events\ProductRejectedEvent;
use App\Modules\Products\Events\ProductStockDepletedEvent;
use App\Modules\Products\Listeners\AutoApproveOrQueueProduct;
use App\Modules\Products\Listeners\DeactivateProductOnZeroStock;
use App\Modules\Products\Listeners\SendProductApprovedNotification;
use App\Modules\Products\Listeners\SendProductRejectedNotification;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Settings\Services\SettingsService;
use App\Modules\Vendors\Events\VendorApprovedEvent;
use App\Modules\Vendors\Events\VendorRejectedEvent;
use App\Modules\Vendors\Events\VendorSuspendedEvent;
use App\Modules\Vendors\Listeners\SendVendorApprovedNotification;
use App\Modules\Vendors\Listeners\SendVendorRejectedNotification;
use App\Modules\Vendors\Listeners\SendVendorSuspendedNotification;
use App\Modules\Vendors\Repositories\VendorRepository;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use App\Modules\Verification\Events\TierUpgradedEvent;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\VendorPolicy;
use App\Modules\Vehicles\Models\FeatureDefinition;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(VendorRepositoryInterface::class, VendorRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(VehicleRepositoryInterface::class, VehicleRepository::class);
        $this->app->bind(VehicleMakeRepositoryInterface::class, VehicleMakeRepository::class);

        // Platform settings: single cached instance shared app-wide.
        $this->app->singleton(SettingsService::class);

        // Pesepay gateway client built from config (keys live in env).
        $this->app->singleton(
            \App\Modules\Payments\Services\PesepayClient::class,
            fn () => \App\Modules\Payments\Services\PesepayClient::fromConfig(),
        );
    }

    public function boot(): void
    {
        // ─── Event → Listener wiring ──────────────────────────────────────────
        Event::listen(VendorApprovedEvent::class, SendVendorApprovedNotification::class);
        Event::listen(VendorRejectedEvent::class, SendVendorRejectedNotification::class);
        Event::listen(VendorSuspendedEvent::class, SendVendorSuspendedNotification::class);

        Event::listen(ProductCreatedEvent::class, AutoApproveOrQueueProduct::class);
        Event::listen(ProductApprovedEvent::class, SendProductApprovedNotification::class);
        Event::listen(ProductRejectedEvent::class, SendProductRejectedNotification::class);
        Event::listen(ProductStockDepletedEvent::class, DeactivateProductOnZeroStock::class);

        // ─── Authorization policies ───────────────────────────────────────────
        Gate::policy(Vendor::class, VendorPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);

        Event::listen(VehicleApprovedEvent::class, SendVehicleApprovedNotification::class);
        Event::listen(VehicleRejectedEvent::class, SendVehicleRejectedNotification::class);

        // Phase 13: order completion settles into the vendor wallet (idempotent).
        Event::listen(
            \App\Modules\Orders\Events\OrderCompletedEvent::class,
            \App\Modules\Wallet\Listeners\SettleCompletedOrder::class,
        );

        // TierUpgradedEvent — no listener yet; payment phase will hook in here
        // Event::listen(TierUpgradedEvent::class, ActivatePremiumSubscription::class);

        // D4: the dynamic vehicle feature inputs render on every vehicle form
        // (vendor + private seller, create + edit) from the admin-managed defs.
        View::composer('partials.vehicle-form-fields', function ($view) {
            $view->with('featureDefinitions', FeatureDefinition::active()->ordered()->get());
        });
    }
}
