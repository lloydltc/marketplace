<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PM9: admin canonical-parts catalog CRUD + OEM and fitment authoring. Every
 * mutation is audit-logged (R6).
 */
class PartController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->input('q', ''));

        $parts = Part::query()
            ->when($term !== '', fn ($q) => $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('brand', 'ilike', "%{$term}%")
                ->orWhere('primary_oem', 'ilike', "%{$term}%"))
            ->withCount(['offerings', 'fitments'])
            ->with('category')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.parts.index', compact('parts', 'term'));
    }

    public function create(): View
    {
        return view('admin.parts.create', ['categories' => $this->categories()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePart($request);
        $part = Part::create($data);

        AuditLog::record($request->user(), 'catalog.part.create', $part, ['name' => $part->name]);

        return redirect()->route('admin.parts.edit', $part)->with('status', 'Part created.');
    }

    public function edit(Part $part): View
    {
        $part->load(['oemNumbers', 'fitments.make', 'fitments.vehicleModel']);

        return view('admin.parts.edit', [
            'part'       => $part,
            'categories' => $this->categories(),
            'makes'      => VehicleMake::with('models')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Part $part): RedirectResponse
    {
        $part->update($this->validatePart($request));

        AuditLog::record($request->user(), 'catalog.part.update', $part, ['name' => $part->name]);

        return back()->with('status', 'Part updated.');
    }

    public function destroy(Request $request, Part $part): RedirectResponse
    {
        $part->delete();

        AuditLog::record($request->user(), 'catalog.part.delete', $part, ['name' => $part->name]);

        return redirect()->route('admin.parts.index')->with('status', 'Part deleted.');
    }

    // ─── OEM numbers ──────────────────────────────────────────────────────────────

    public function addOem(Request $request, Part $part): RedirectResponse
    {
        $validated = $request->validate([
            'number' => ['required', 'string', 'max:100'],
            'type'   => ['required', 'in:oem,aftermarket,cross_ref'],
            'brand'  => ['nullable', 'string', 'max:100'],
        ]);

        $part->oemNumbers()->firstOrCreate(
            ['number' => $validated['number'], 'type' => $validated['type']],
            ['brand' => $validated['brand'] ?? null],
        );

        AuditLog::record($request->user(), 'catalog.part.oem.add', $part, $validated);

        return back()->with('status', 'OEM number added.');
    }

    public function removeOem(Request $request, Part $part, string $oem): RedirectResponse
    {
        $part->oemNumbers()->whereKey($oem)->delete();

        AuditLog::record($request->user(), 'catalog.part.oem.remove', $part, ['oem' => $oem]);

        return back()->with('status', 'OEM number removed.');
    }

    // ─── Fitment authoring (with ranges) ──────────────────────────────────────────

    public function addFitment(Request $request, Part $part): RedirectResponse
    {
        $validated = $request->validate([
            'make_id'         => ['required', 'uuid', 'exists:vehicle_makes,id'],
            'model_id'        => ['required', 'uuid', 'exists:vehicle_models,id'],
            'generation_id'   => ['nullable', 'uuid', 'exists:vehicle_generations,id'],
            'variant_id'      => ['nullable', 'uuid', 'exists:vehicle_variants,id'],
            'engine_id'       => ['nullable', 'uuid', 'exists:vehicle_engines,id'],
            'transmission_id' => ['nullable', 'uuid', 'exists:vehicle_transmissions,id'],
            'year_start'      => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'year_end'        => ['nullable', 'integer', 'min:1900', 'max:2100', 'gte:year_start'],
        ]);

        $part->fitments()->create($validated);

        AuditLog::record($request->user(), 'catalog.part.fitment.add', $part, $validated);

        return back()->with('status', 'Fitment rule added.');
    }

    public function removeFitment(Request $request, Part $part, string $fitment): RedirectResponse
    {
        $part->fitments()->whereKey($fitment)->delete();

        AuditLog::record($request->user(), 'catalog.part.fitment.remove', $part, ['fitment' => $fitment]);

        return back()->with('status', 'Fitment rule removed.');
    }

    private function validatePart(Request $request): array
    {
        return $request->validate([
            'name'            => ['required', 'string', 'max:200'],
            'brand'           => ['nullable', 'string', 'max:100'],
            'category_id'     => ['nullable', 'uuid', 'exists:categories,id'],
            'primary_oem'     => ['nullable', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:5000'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:600'],
            'warranty_terms'  => ['nullable', 'string', 'max:1000'],
            'is_universal'    => ['sometimes', 'boolean'],
            'status'          => ['required', 'in:active,inactive'],
        ]);
    }

    private function categories()
    {
        return Category::orderBy('name')->get();
    }
}
