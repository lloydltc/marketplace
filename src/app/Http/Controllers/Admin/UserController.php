<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Verification\Services\TierService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly TierService $tierService) {}

    public function index(Request $request): View
    {
        $query = User::query()->orderBy('created_at', 'desc');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('tier')) {
            $query->where('tier', $request->input('tier'));
        }

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'ILIKE', "%{$term}%")
                  ->orWhere('email', 'ILIKE', "%{$term}%");
            });
        }

        $users = $query->paginate(30);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user): View
    {
        $vehicleCount  = $user->vehicles()->whereNull('deleted_at')->count();
        $remainingSlots = $this->tierService->sellerRemainingVehicleSlots($user);
        $vehicleLimit   = $this->tierService->sellerVehicleLimit($user);

        return view('admin.users.show', compact('user', 'vehicleCount', 'remainingSlots', 'vehicleLimit'));
    }
}
