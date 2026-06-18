<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForcePasswordChangeController extends Controller
{
    public function show(): View
    {
        return view('auth.force-password-change');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password'              => Hash::make($request->password),
            'force_password_change' => false,
        ])->save();

        $destination = match ($user->role) {
            'super_admin', 'admin'          => route('admin.dashboard'),
            'vendor_admin', 'vendor_worker' => route('vendor.dashboard'),
            'agent'                         => route('agent.dashboard'),
            'private_seller'                => route('seller.dashboard'),
            default                         => '/',
        };

        return redirect()->to($destination);
    }
}
