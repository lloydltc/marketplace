<?php

namespace App\Http\Controllers;

use App\Models\ListingReport;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * H11: buyers (and guests) report a listing for review. Public + rate-limited.
 * Reports feed the admin moderation queue; the listing stays live until an admin
 * acts.
 */
class ReportController extends Controller
{
    public function vehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        return $this->store($request, $vehicle);
    }

    public function product(Request $request, Product $product): RedirectResponse
    {
        return $this->store($request, $product);
    }

    private function store(Request $request, Model $listing): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:' . implode(',', array_keys(config('moderation.reasons', [])))],
            'note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()?->id;
        $ip     = $request->ip();

        // Dedupe: one open report per reporter (user, or IP for guests) per listing.
        $exists = $listing->reports()->open()
            ->when($userId, fn ($q) => $q->where('reporter_user_id', $userId),
                fn ($q) => $q->whereNull('reporter_user_id')->where('reporter_ip', $ip))
            ->exists();

        if (! $exists) {
            $listing->reports()->create([
                'reporter_user_id' => $userId,
                'reporter_ip'      => $ip,
                'source'           => 'user',
                'reason'           => $validated['reason'],
                'note'             => $validated['note'] ?? null,
                'status'           => 'open',
            ]);
        }

        return back()->with('status', 'Thanks — our team will review this listing.');
    }
}
