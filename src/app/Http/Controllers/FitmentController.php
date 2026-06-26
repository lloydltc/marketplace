<?php

namespace App\Http\Controllers;

use App\Modules\Parts\Services\FitmentContext;
use App\Modules\Vehicles\Services\TaxonomyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * PM3: the cascading fitment selector. JSON endpoints feed the dependent
 * dropdowns; select/clear store the buyer's chosen vehicle in the session
 * (FitmentContext) which drives "only-compatible" browsing and fit badges.
 */
class FitmentController extends Controller
{
    public function __construct(
        private readonly TaxonomyService $taxonomy,
        private readonly FitmentContext $context,
    ) {}

    public function models(Request $request): JsonResponse
    {
        $request->validate(['make_id' => ['required', 'uuid']]);

        return response()->json($this->taxonomy->models($request->input('make_id')));
    }

    public function generations(Request $request): JsonResponse
    {
        $request->validate(['model_id' => ['required', 'uuid']]);

        return response()->json($this->taxonomy->generations($request->input('model_id')));
    }

    public function variants(Request $request): JsonResponse
    {
        $request->validate(['model_id' => ['required', 'uuid']]);

        return response()->json($this->taxonomy->variants(
            $request->input('model_id'),
            $request->input('generation_id'),
        ));
    }

    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'make_id'         => ['required', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'        => ['required', 'uuid', 'exists:vehicle_models,id'],
            'year'            => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'generation_id'   => ['nullable', 'uuid', 'exists:vehicle_generations,id'],
            'variant_id'      => ['nullable', 'uuid', 'exists:vehicle_variants,id'],
            'engine_id'       => ['nullable', 'uuid', 'exists:vehicle_engines,id'],
            'transmission_id' => ['nullable', 'uuid', 'exists:vehicle_transmissions,id'],
        ]);

        $this->context->set($validated);

        return back()->with('status', 'Showing parts for ' . $this->context->get()['label'] . '.');
    }

    public function clear(): RedirectResponse
    {
        $this->context->clear();

        return back()->with('status', 'Vehicle filter cleared.');
    }
}
