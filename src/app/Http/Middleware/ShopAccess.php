<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards shopping surfaces (cart, checkout) that allow guests. Authenticated
 * staff roles (admin, super_admin, vendor_worker, agent, rider) are rejected —
 * hiding the menu item is not enough; this enforces it server-side.
 */
class ShopAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! in_array($user->role, config('navigation.shopping_roles', []), true)) {
            abort(403, 'This area is for shoppers only.');
        }

        return $next($request);
    }
}
