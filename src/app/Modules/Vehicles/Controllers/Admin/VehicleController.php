<?php

namespace App\Modules\Vehicles\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository
    ) {}

    public function index(Request $request): View
    {
        $vehicles = $this->repository->paginate([
            'status'   => $request->get('status'),
            'make_id'  => $request->get('make_id'),
            'condition' => $request->get('condition'),
        ]);

        return view('admin.vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['make', 'vehicleModel', 'vendor', 'seller']);

        return view('admin.vehicles.show', compact('vehicle'));
    }
}
