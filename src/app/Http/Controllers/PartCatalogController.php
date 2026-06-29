<?php

namespace App\Http\Controllers;

use App\Modules\Categories\Models\Category;
use App\Modules\Parts\Models\Part;
use App\Modules\Parts\Services\CoPurchaseService;
use App\Modules\Parts\Services\FitmentContext;
use App\Modules\Parts\Services\VinDecoder;
use App\Modules\Vehicles\Models\VehicleMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PM4: public parts catalog browse — fitment-filtered (primary path), category +
 * facet browse, keyword/OEM search, basic VIN search, empty → RFQ.
 */
class PartCatalogController extends Controller
{
    public function __construct(private readonly FitmentContext $context) {}

    public function index(Request $request): View
    {
        $sel = $this->context->has() ? $this->context->selection() : null;

        $query = Part::query()
            ->active()
            // Only buyable parts: at least one active offering.
            ->whereHas('offerings', fn ($o) => $o->where('status', 'active'))
            ->withMin(['offerings as price_from' => fn ($o) => $o->where('status', 'active')], 'price_usd')
            ->with(['category', 'media']);

        // Fitment filter (only-compatible) when a vehicle is selected.
        if ($sel) {
            $query->compatibleWith($sel);
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        if ($brand = $request->input('brand')) {
            $query->where('brand', $brand);
        }

        if ($request->boolean('in_stock')) {
            $query->whereHas('offerings', fn ($o) => $o->where('status', 'active')->where('quantity', '>', 0));
        }

        if ($term = trim((string) $request->input('q', ''))) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like) {
                $q->where('name', 'ilike', $like)
                  ->orWhere('brand', 'ilike', $like)
                  ->orWhereHas('oemNumbers', fn ($o) => $o->where('number', 'ilike', $like));
            });
        }

        match ($request->input('sort')) {
            'price_asc'  => $query->orderBy('price_from'),
            'price_desc' => $query->orderByDesc('price_from'),
            'name'       => $query->orderBy('name'),
            default      => $query->orderByDesc('created_at'),
        };

        $parts = $query->paginate((int) config('parts.per_page', 24))->withQueryString();

        // Facets.
        $categories = Category::query()->whereHas('parts')->orderBy('name')->get();
        $brands = Part::query()->active()->whereNotNull('brand')->distinct()->orderBy('brand')->pluck('brand');

        return view('parts.index', [
            'parts'      => $parts,
            'categories' => $categories,
            'brands'     => $brands,
            'context'    => $this->context,
        ]);
    }

    /**
     * Part detail. Basic in PM4 (so the catalog links resolve); the full offers
     * compare / alternatives / frequently-bought-together is built in PM5.
     */
    public function show(Part $part, CoPurchaseService $coPurchase): View
    {
        abort_unless($part->isActive(), 404);

        $part->load([
            'category', 'media', 'oemNumbers', 'guides',
            'fitments.make', 'fitments.vehicleModel',
            'alternatives.alternative.media',
        ]);

        $offers = $part->offerings()
            ->where('status', 'active')
            ->with('vendor')
            ->orderBy('price_usd')
            ->get();

        $fitsSelection = $this->context->has() ? $part->fitsSelection($this->context->selection()) : null;

        // PM5: alternatives (curated + OEM-derived, de-duplicated) and FBT.
        $curated = $part->alternatives->map->alternative->filter(fn ($p) => $p && $p->isActive());
        $alternatives = $curated
            ->concat($part->relatedByOem())
            ->unique('id')
            ->reject(fn ($p) => $p->id === $part->id)
            ->take(8)
            ->values();

        $frequentlyBought = $coPurchase->frequentlyBoughtWith($part, (int) config('parts.fbt_count', 4));

        // PM6: service kits that include an offering of this part.
        $kits = \App\Modules\Parts\Models\PartBundle::active()
            ->whereHas('items.product', fn ($q) => $q->where('part_id', $part->id))
            ->with('items.product')
            ->limit(4)
            ->get();

        // PM10: part → compatible vehicles for sale (cross-sell, absorbs H10).
        $compatibleVehicles = $part->compatibleVehicles((int) config('compatibility.vehicles_per_part', 6));

        return view('parts.show', [
            'part'               => $part,
            'offers'             => $offers,
            'context'            => $this->context,
            'fitsSelection'      => $fitsSelection,
            'alternatives'       => $alternatives,
            'frequentlyBought'   => $frequentlyBought,
            'kits'               => $kits,
            'compatibleVehicles' => $compatibleVehicles,
        ]);
    }

    /** PM4: basic VIN search — decode (best-effort) → seed fitment context → browse. */
    public function vinSearch(Request $request, VinDecoder $decoder): RedirectResponse
    {
        $request->validate(['vin' => ['required', 'string', 'max:32']]);

        $decoded = $decoder->decode($request->input('vin'));

        if (! $decoded['valid']) {
            return back()->withErrors(['vin' => 'That doesn\'t look like a valid 17-character VIN.']);
        }

        $selection = ['year' => $decoded['year']];

        if ($decoded['make_hint']) {
            $make = VehicleMake::where('name', $decoded['make_hint'])->first();
            if ($make) {
                $selection['make_id'] = $make->id;
            }
        }

        // Only a make+model selection drives the fitment filter; a VIN with just a
        // make/year pre-fills the selector for the buyer to finish + confirm.
        return redirect()->route('parts.index')->with('vin_prefill', $selection)
            ->with('status', 'We decoded what we could from your VIN — confirm your vehicle to filter.');
    }
}
