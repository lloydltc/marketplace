<?php

namespace App\Http\Controllers;

use App\Modules\Products\Repositories\ProductRepositoryInterface;
use App\Modules\Vehicles\Repositories\VehicleRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lightweight JSON autocomplete endpoints for the public search boxes.
 * Public (no auth) so guests browsing get suggestions too.
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly VehicleRepositoryInterface $vehicles
    ) {}

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
