<?php

namespace App\Http\Controllers;

use App\Modules\Parts\Models\Part;
use App\Support\PartCompareList;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * PM8: side-by-side parts comparison (public, session-backed).
 */
class PartCompareController extends Controller
{
    public function __construct(private readonly PartCompareList $compare) {}

    public function show(): View
    {
        $ids = $this->compare->ids();

        $parts = collect();
        if ($ids !== []) {
            $parts = Part::query()
                ->active()
                ->whereIn('id', $ids)
                ->with(['category', 'media', 'oemNumbers', 'fitments'])
                ->withMin(['offerings as price_from' => fn ($o) => $o->where('status', 'active')], 'price_usd')
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $ids, true))
                ->values();
        }

        return view('parts.compare', compact('parts'));
    }

    public function add(Part $part): RedirectResponse
    {
        abort_unless($part->isActive(), 404);

        if (! $this->compare->add($part->id)) {
            return back()->with('status', 'Compare list is full (max ' . config('parts.compare_max') . ').');
        }

        return back()->with('status', 'Added to compare.');
    }

    public function remove(Part $part): RedirectResponse
    {
        $this->compare->remove($part->id);

        return back()->with('status', 'Removed from compare.');
    }

    public function clear(): RedirectResponse
    {
        $this->compare->clear();

        return back()->with('status', 'Compare list cleared.');
    }
}
