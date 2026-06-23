<?php

namespace App\Http\Controllers;

use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public search: a unified results page across both vehicles and parts (D2) plus
 * the lightweight JSON autocomplete endpoints. Public (no auth) so guests search too.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly VehicleRepositoryInterface $vehicles
    ) {}

    /**
     * Unified landing search — one query, both entity types, sectioned and ranked
     * (FBS boost for parts, featured priority for vehicles — applied in the repos).
     */
    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $vehicles = null;
        $products = null;

        if ($q !== '') {
            $vehicles = $this->vehicles->paginatePublic(['search' => $q], 8);
            $products = $this->products->paginatePublic(['search' => $q], 8);
        }

        return view('search.results', compact('q', 'vehicles', 'products'));
    }

    public function products(Request $request): JsonResponse
    {
        return response()->json($this->suggest(
            $request,
            fn (string $term) => $this->products->suggest($term)
        ));
    }

    public function vehicles(Request $request): JsonResponse
    {
        return response()->json($this->suggest(
            $request,
            fn (string $term) => $this->vehicles->suggest($term)
        ));
    }

    /**
     * @param  callable(string): array<int, string>  $resolver
     * @return array<int, string>
     */
    private function suggest(Request $request, callable $resolver): array
    {
        $term = trim((string) $request->input('q', ''));

        // Require at least 2 characters to avoid noisy, expensive lookups.
        if (mb_strlen($term) < 2) {
            return [];
        }

        return $resolver($term);
    }
}
