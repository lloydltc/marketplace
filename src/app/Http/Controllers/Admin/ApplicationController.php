<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationApprovedMailable;
use App\Mail\ApplicationRejectedMailable;
use App\Models\User;
use App\Modules\Vendors\Events\VendorApprovedEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('vendors')
            ->whereIn('role', ['vendor_admin', 'private_seller'])
            ->where('status', 'pending')
            ->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $applications = $query->paginate(30);

        return view('admin.applications.index', compact('applications'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update(['status' => 'active']);

        if ($user->role === 'vendor_admin') {
            $vendor = $user->vendors()->first();

            if ($vendor) {
                $vendor->update(['status' => 'approved', 'verified_at' => now()]);
                event(new VendorApprovedEvent($vendor->refresh()));
            }
        } else {
            Mail::to($user->email)->send(new ApplicationApprovedMailable($user));
        }

        return redirect()->route('admin.applications.index')
            ->with('status', "Application approved for {$user->name}.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user->update(['status' => 'rejected']);

        if ($user->role === 'vendor_admin') {
            $vendor = $user->vendors()->first();
            $vendor?->update(['status' => 'closed']);
        }

        Mail::to($user->email)->send(new ApplicationRejectedMailable($user, $request->reason));

        return redirect()->route('admin.applications.index')
            ->with('status', "Application rejected for {$user->name}.");
    }
}
