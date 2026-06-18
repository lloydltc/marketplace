<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SellerApplicationRequest;
use App\Http\Requests\Auth\VendorApplicationRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function createVendor(): View
    {
        return view('auth.apply-vendor');
    }

    public function storeVendor(VendorApplicationRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'vendor_admin',
            'status'   => 'pending',
        ]);

        $user->assignRole('vendor_admin');

        $vendor = Vendor::create([
            'name'                  => $request->business_name,
            'contact_email'         => $request->email,
            'phone'                 => $request->phone,
            'address'               => $request->address,
            'description'           => $request->description,
            'business_registration' => $request->business_registration,
            'status'                => 'pending',
            'tier'                  => 'unverified',
            'commission_rate'       => 10,
        ]);

        $vendor->users()->attach($user->id, [
            'vendor_role' => 'admin',
            'invited_at'  => now(),
            'joined_at'   => now(),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }

    public function createSeller(): View
    {
        return view('auth.apply-seller');
    }

    public function storeSeller(SellerApplicationRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => 'private_seller',
            'status'   => 'pending',
        ]);

        $user->assignRole('private_seller');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
