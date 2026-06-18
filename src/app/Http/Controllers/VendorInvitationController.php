<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorInvitationRequest;
use App\Jobs\Mail\SendVendorInvitationJob;
use App\Models\VendorInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VendorInvitationController extends Controller
{
    public function create(): View
    {
        return view('vendor.invitation.create');
    }

    public function store(StoreVendorInvitationRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');

        $invitation = VendorInvitation::create([
            'vendor_id'     => $vendor->id,
            'invited_by'    => $request->user()->id,
            'email'         => $request->email,
            'temp_password' => $request->temp_password,
            'token'         => (string) Str::uuid(),
            'expires_at'    => now()->addHours(48),
        ]);

        dispatch(new SendVendorInvitationJob($invitation->load('vendor', 'inviter')));

        return back()->with('status', 'invitation-sent');
    }

    public function accept(Request $request, string $token): View|RedirectResponse
    {
        $invitation = VendorInvitation::pending()->where('token', $token)->first();

        if (! $invitation) {
            return view('vendor.invitation.accept', ['invalid' => true]);
        }

        return view('vendor.invitation.accept', compact('invitation'));
    }

    public function acceptStore(Request $request, string $token): RedirectResponse
    {
        $invitation = VendorInvitation::pending()->where('token', $token)->firstOrFail();

        $user = \App\Models\User::create([
            'name'                  => $request->input('name', $invitation->email),
            'email'                 => $invitation->email,
            'password'              => Hash::make($invitation->temp_password),
            'role'                  => 'vendor_worker',
            'force_password_change' => true,
            'email_verified_at'     => now(),
        ]);

        $user->assignRole('vendor_worker');

        $invitation->vendor->users()->attach($user->id, [
            'vendor_role' => 'worker',
            'invited_at'  => $invitation->created_at,
            'joined_at'   => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        Auth::login($user);

        return redirect()->route('password.force-change');
    }
}
