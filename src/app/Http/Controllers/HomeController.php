<?php

namespace App\Http\Controllers;

use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly VehicleRepositoryInterface $vehicles
    ) {}

    public function index(): View
    {
        // Public marketplace landing — visible to guests before they authenticate.
        // Ranking (FBS boost / featured priority) is applied inside the repositories.
        $products = $this->products->paginatePublic([], 8)->items();
        $vehicles = $this->vehicles->paginatePublic([], 8)->items();

        // H6: discovery rails — browse by type / make, all count-driven.
        $typeCounts   = $this->vehicles->countByType();
        $popularMakes = $this->vehicles->popularMakes(10);

        return view('customer.dashboard', compact('products', 'vehicles', 'typeCounts', 'popularMakes'));
    }
}
