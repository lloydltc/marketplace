<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Suspended accounts (R6) are logged out immediately and cannot use the app.
        if ($user && $user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been suspended. Contact support.']);
        }

        // Rejected accounts are blocked from the app entirely.
        if ($user && $user->isRejected()) {
            return redirect()->route('application.rejected');
        }

        // Pending sellers/vendors are NOT bounced to a dead-end (remediation R4/F12):
        // they reach their dashboard and may list while unverified. Transaction
        // gating (display-only) is enforced at cart/checkout, not here.
        return $next($request);
    }
}
