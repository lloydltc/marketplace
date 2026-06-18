<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserTierController extends Controller
{
    public function __construct(private readonly TierService $tierService) {}

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'tier' => ['required', 'string', 'in:' . implode(',', config('tiers.tiers', ['unverified', 'premium']))],
        ]);

        $this->tierService->upgradeSellerTier($user, $request->input('tier'));

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', "User tier updated to \"{$request->input('tier')}\".");
    }
}
