<?php

namespace App\Http\Controllers;

use App\Modules\Analytics\Models\ListingDailyStat;
use App\Modules\Analytics\Services\AnalyticsService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * H5: per-listing analytics dashboard for sellers (private + vendor). Reads the
 * pre-aggregated daily stats, scoped server-side to the owner, with this-period
 * vs previous-period deltas. The car-side's core metric.
 */
class ListingAnalyticsController extends Controller
{
    public function sellerIndex(Request $request): View
    {
        return $this->render(
            fn (Builder $q) => $q->where('seller_user_id', $request->user()->id),
        );
    }

    public function vendorIndex(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        return $this->render(fn (Builder $q) => $q->where('vendor_id', $vendor->id));
    }

    private function render(\Closure $scope): View
    {
        $end   = Carbon::today();
        $start = $end->copy()->subDays(29);          // 30-day window
        $prevEnd   = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays(29);

        $current  = $this->totalsByType($scope, $start, $end);
        $previous = $this->totalsByType($scope, $prevStart, $prevEnd);

        $metrics = [];
        foreach (AnalyticsService::TYPES as $type) {
            $now  = $current[$type] ?? 0;
            $was  = $previous[$type] ?? 0;
            $metrics[$type] = [
                'count' => $now,
                'delta' => $now - $was,
            ];
        }

        // Per-listing breakdown for the current window.
        $perListingRows = ListingDailyStat::query()
            ->tap(fn ($q) => $scope($q))
            ->whereBetween('stat_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('subject_id', 'type')
            ->selectRaw('subject_id, type, sum(count) as c')
            ->get()
            ->groupBy('subject_id');

        $vehicles = Vehicle::whereIn('id', $perListingRows->keys())->get()->keyBy('id');

        $perListing = $perListingRows->map(function ($rows, $subjectId) use ($vehicles) {
            $byType = $rows->pluck('c', 'type');

            return [
                'vehicle' => $vehicles->get($subjectId),
                'views'   => (int) ($byType['detail_view'] ?? 0),
                'contacts' => (int) (($byType['phone_reveal'] ?? 0) + ($byType['whatsapp_click'] ?? 0)
                    + ($byType['call_click'] ?? 0) + ($byType['enquiry'] ?? 0)),
            ];
        })->filter(fn ($r) => $r['vehicle'] !== null)
          ->sortByDesc('views')->values();

        return view('analytics.index', compact('metrics', 'perListing', 'start', 'end'));
    }

    /** @return array<string,int> */
    private function totalsByType(\Closure $scope, Carbon $from, Carbon $to): array
    {
        return ListingDailyStat::query()
            ->tap(fn ($q) => $scope($q))
            ->whereBetween('stat_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('type')->selectRaw('type, sum(count) as c')
            ->pluck('c', 'type')->map(fn ($c) => (int) $c)->all();
    }
}
