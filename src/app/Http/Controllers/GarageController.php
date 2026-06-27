<?php

namespace App\Http\Controllers;

use App\Modules\Parts\Models\GarageVehicle;
use App\Modules\Parts\Services\FitmentContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PM7: My Garage. Buyers save vehicles; activating one drives the fitment context
 * across the parts catalog.
 */
class GarageController extends Controller
{
    public function __construct(private readonly FitmentContext $context) {}

    public function index(Request $request): View
    {
        $vehicles = GarageVehicle::where('user_id', $request->user()->id)
            ->with(['make', 'vehicleModel', 'variant'])
            ->orderByDesc('is_default')->orderByDesc('created_at')
            ->get();

        return view('garage.index', compact('vehicles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'make_id'         => ['required', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'        => ['required', 'uuid', 'exists:vehicle_models,id'],
            'year'            => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'variant_id'      => ['nullable', 'uuid', 'exists:vehicle_variants,id'],
            'engine_id'       => ['nullable', 'uuid', 'exists:vehicle_engines,id'],
            'transmission_id' => ['nullable', 'uuid', 'exists:vehicle_transmissions,id'],
            'nickname'        => ['nullable', 'string', 'max:60'],
        ]);

        $validated['user_id'] = $request->user()->id;

        // First saved vehicle becomes the default.
        $validated['is_default'] = ! GarageVehicle::where('user_id', $request->user()->id)->exists();

        $vehicle = GarageVehicle::create($validated);

        // Activate it immediately for a one-tap fits-confirmed browse.
        $this->context->set($vehicle->toSelection());

        return redirect()->route('garage.index')->with('status', 'Vehicle saved to your garage.');
    }

    public function activate(Request $request, GarageVehicle $garageVehicle): RedirectResponse
    {
        abort_unless($garageVehicle->user_id === $request->user()->id, 403);

        $this->context->set($garageVehicle->toSelection());

        return redirect()->route('parts.index')->with('status', 'Showing parts for ' . $garageVehicle->label() . '.');
    }

    public function destroy(Request $request, GarageVehicle $garageVehicle): RedirectResponse
    {
        abort_unless($garageVehicle->user_id === $request->user()->id, 403);

        $garageVehicle->delete();

        return back()->with('status', 'Vehicle removed from your garage.');
    }
}
