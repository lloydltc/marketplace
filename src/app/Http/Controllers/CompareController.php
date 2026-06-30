<?php

namespace App\Http\Controllers;

use App\Modules\Vehicles\Models\Vehicle;
use App\Support\CompareList;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * H7 + AC3: side-by-side vehicle comparison. Public (guests included) — the set
 * lives in the session via CompareList, or is loaded from a shareable ?v= URL.
 */
class CompareController extends Controller
{
    public function __construct(private readonly CompareList $compare) {}

    public function show(Request $request): View
    {
        // AC3: a shared ?v=id1,id2 URL takes precedence (capped), else the session set.
        $shared = array_filter(explode(',', (string) $request->query('v', '')));
        $ids = $shared !== []
            ? array_slice($shared, 0, (int) config('engagement.compare.max_items', 5))
            : $this->compare->ids();

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
