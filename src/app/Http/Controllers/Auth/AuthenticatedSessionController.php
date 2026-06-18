<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, Navigation $nav): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Land each role on its dashboard (single source of truth: config/navigation.php).
        // Customers have no dashboard — home is the shop.
        $dashboard = $nav->dashboardRoute($request->user()->role);

        return redirect()->intended($dashboard ? route($dashboard) : '/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
