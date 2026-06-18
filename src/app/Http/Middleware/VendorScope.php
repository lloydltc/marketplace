<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, ['vendor_admin', 'vendor_worker'])) {
            $vendor = $user->vendors()->first();

            if ($vendor) {
                $request->merge(['_vendor_id' => $vendor->id]);
                $request->attributes->set('vendor', $vendor);
            }
        }

        return $next($request);
    }
}
