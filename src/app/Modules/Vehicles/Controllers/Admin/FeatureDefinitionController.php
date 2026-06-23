<?php

namespace App\Modules\Vehicles\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Vehicles\Models\FeatureDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * D4: admin CRUD for vehicle feature definitions. Admins can add/edit/retire
 * features at runtime without a deploy; filterable ones power D3 facets.
 */
class FeatureDefinitionController extends Controller
{
    public function index(): View
    {
        $features = FeatureDefinition::ordered()->get();

        return view('admin.vehicle-features.index', compact('features'));
    }

    public function create(): View
    {
        return view('admin.vehicle-features.create', ['feature' => new FeatureDefinition()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        FeatureDefinition::create($data);

        return redirect()->route('admin.vehicle-features.index')
            ->with('status', "Feature \"{$data['name']}\" added.");
    }

    public function edit(FeatureDefinition $vehicle_feature): View
    {
        return view('admin.vehicle-features.edit', ['feature' => $vehicle_feature]);
    }

    public function update(Request $request, FeatureDefinition $vehicle_feature): RedirectResponse
    {
        $vehicle_feature->update($this->validated($request, $vehicle_feature));

        return redirect()->route('admin.vehicle-features.index')
            ->with('status', 'Feature updated.');
    }

    /** Retire / reactivate (preserves historical values rather than deleting). */
    public function toggle(FeatureDefinition $vehicle_feature): RedirectResponse
    {
        $vehicle_feature->update(['is_active' => ! $vehicle_feature->is_active]);

        return back()->with('status', $vehicle_feature->is_active ? 'Feature reactivated.' : 'Feature retired.');
    }

    private function validated(Request $request, ?FeatureDefinition $existing = null): array
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:80'],
            'type'               => ['required', Rule::in(FeatureDefinition::TYPES)],
            'unit'               => ['nullable', 'string', 'max:20'],
            'options'            => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('type') === 'enum')], // comma-separated
            'group'              => ['nullable', 'string', 'max:40'],
            'sort_order'         => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_filterable'      => ['nullable', 'boolean'],
            'applies_to_types'   => ['nullable', 'array'],
            'applies_to_types.*' => [Rule::in(\App\Modules\Vehicles\Models\Vehicle::types())],
        ]);

        // enum requires options; parse the comma-separated input into an array.
        $options = null;
        if ($validated['type'] === 'enum') {
            $options = collect(explode(',', (string) ($validated['options'] ?? '')))
                ->map(fn ($o) => trim($o))->filter()->values()->all();
            if ($options === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'options' => 'Enum features need at least one option (comma-separated).',
                ]);
            }
        }

        return [
            'name'             => $validated['name'],
            'key'              => $existing?->key ?? Str::slug($validated['name'], '_'),
            'type'             => $validated['type'],
            'unit'             => $validated['unit'] ?? null,
            'options'          => $options,
            // Empty selection = applies to all types (stored NULL).
            'applies_to_types' => ! empty($validated['applies_to_types']) ? array_values($validated['applies_to_types']) : null,
            'group'            => $validated['group'] ?? null,
            'sort_order'       => $validated['sort_order'] ?? 0,
            'is_filterable'    => (bool) ($validated['is_filterable'] ?? false),
        ];
    }
}
