<?php

use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\VendorScope;
use App\Modules\Media\Exceptions\ImageUploadException;
use App\Modules\Verification\Exceptions\ListingLimitExceededException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'                  => RoleMiddleware::class,
            'force.password.change' => ForcePasswordChange::class,
            'vendor.scope'          => VendorScope::class,
            'check.status'          => \App\Http\Middleware\CheckUserStatus::class,
            'shop.access'           => \App\Http\Middleware\ShopAccess::class,
        ]);

        // P6: correlation id first (so all downstream logging carries it).
        $middleware->web(prepend: [
            \App\Http\Middleware\RequestId::class,
        ]);

        // P3: baseline security headers on every web response.
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // H7: recently-viewed holds only public vehicle UUIDs — no PII or session
        // value — so it's read as a plain cookie (no per-request encrypt/decrypt).
        $middleware->encryptCookies(except: [
            'recently_viewed_vehicles',
        ]);

        // Pesepay posts the webhook server-to-server — exclude it from CSRF.
        $middleware->validateCsrfTokens(except: [
            'payments/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (ListingLimitExceededException $e) {
            return back()->withErrors(['listing_limit' => $e->getMessage()]);
        });

        $exceptions->renderable(function (ImageUploadException $e) {
            return back()->withErrors(['image' => $e->getMessage()]);
        });

        $exceptions->renderable(function (\App\Modules\Wallet\Exceptions\WalletBelowFloorException $e) {
            return back()->withErrors(['wallet_floor' => $e->getMessage()]);
        });

        $exceptions->renderable(function (\App\Modules\Rfq\Exceptions\RfqThresholdException $e) {
            return back()->withErrors(['rfq' => $e->getMessage()]);
        });

        $exceptions->renderable(function (\App\Modules\Products\Exceptions\InsufficientStockException $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        });
    })->create();
