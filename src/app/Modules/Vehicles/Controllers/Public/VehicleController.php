<?php

namespace App\Modules\Vehicles\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\Vehicle;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private readonly VehicleRepositoryInterface $repository,
        private readonly VehicleMakeRepositoryInterface $makeRepository
    ) {}

    public function index(Request $request): View
    {
        $vehicles = $this->repository->paginatePublic([
            'search'       => $request->input('search'),
            'make_id'      => $request->input('make_id'),
            'model_id'     => $request->input('model_id'),
            'year_min'     => $request->input('year_min'),
            'year_max'     => $request->input('year_max'),
            'mileage_max'  => $request->input('mileage_max'),
            'min_price'    => $request->input('min_price'),
            'max_price'    => $request->input('max_price'),
            'body_type'    => $request->input('body_type'),
            'transmission' => $request->input('transmission'),
            'fuel_type'    => $request->input('fuel_type'),
            'condition'    => $request->input('condition'),
            'sort'         => $request->input('sort', 'latest'),
        ]);

        $makes = $this->makeRepository->allWithModels();

        return view('vehicles.index', compact('vehicles', 'makes'));
    }

    public function show(Vehicle $vehicle): View
    {
        abort_unless($vehicle->isActive(), 404);

        $vehicle->load([
            'make', 'vehicleModel', 'vendor', 'seller',
            'images' => fn ($q) => $q->whereNotNull('processed_at')->orderBy('display_order'),
        ]);

        return view('vehicles.show', compact('vehicle'));
    }
}
