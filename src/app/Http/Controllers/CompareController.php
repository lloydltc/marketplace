<?php

namespace App\Http\Controllers;

use App\Modules\Vehicles\Models\Vehicle;
use App\Support\CompareList;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * H7: side-by-side vehicle comparison. Public (guests included) — the set lives in
 * the session via CompareList.
 */
class CompareController extends Controller
{
    public function __construct(private readonly CompareList $compare) {}

    public function show(): View
    {
        // Preserve the buyer's chosen order; only show listings still publicly live.
        $ids = $this->compare->ids();

        $vehicles = collect();
        if ($ids !== []) {
            $vehicles = Vehicle::query()
                ->active()
                ->whereIn('id', $ids)
                ->with(['make', 'vehicleModel', 'vendor', 'seller', 'images', 'featureValues.definition'])
                ->get()
                ->sortBy(fn ($v) => array_search($v->id, $ids, true))
                ->values();
        }

        return view('compare.show', compact('vehicles'));
    }

    public function add(Vehicle $vehicle): RedirectResponse
    {
        abort_unless($vehicle->isActive(), 404);

        if (! $this->compare->add($vehicle->id)) {
            return back()->with('status', 'Compare list is full (max ' . config('engagement.compare.max_items') . '). Remove one to add another.');
        }

        return back()->with('status', 'Added to compare.');
    }

    public function remove(Vehicle $vehicle): RedirectResponse
    {
        $this->compare->remove($vehicle->id);

        return back()->with('status', 'Removed from compare.');
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return back()->with('status', 'Compare list cleared.');
    }
}
