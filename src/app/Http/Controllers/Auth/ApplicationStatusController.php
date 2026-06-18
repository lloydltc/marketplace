<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApplicationStatusController extends Controller
{
    public function pending(Request $request): View|RedirectResponse
    {
        if ($request->user()->isActive()) {
            return redirect()->intended('/');
        }

        return view('auth.pending-review');
    }

    public function rejected(Request $request): View|RedirectResponse
    {
        if ($request->user()->isActive()) {
            return redirect()->intended('/');
        }

        return view('auth.application-rejected');
    }
}
