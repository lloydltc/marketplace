<?php

namespace App\Modules\Verification\Services;

use App\Models\Vendor;
use App\Modules\Verification\Models\VendorReputation;
use Illuminate\Support\Facades\DB;

/**
 * VB3: computes a vendor's reputation score (0–100) from config-weighted
 * components. Each component is 0–100 or null (no data yet); the final score is
 * the weighted average over the components that DO have data — so a brand-new
 * vendor with no orders/leads/ratings degrades gracefully rather than scoring 0
 * on everything. No AI.
 */
class ReputationService
{
    public function __construct(private readonly TierEvaluator $tiers) {}

    /** Compute, persist (snapshot + cached score), and re-evaluate the tier. */
    public function recompute(Vendor $vendor): int
    {
        $components = $this->components($vendor);
        $score = $this->weightedScore($components);

        VendorReputation::updateOrCreate(
            ['vendor_id' => $vendor->id],
            ['score' => $score, 'components' => $components, 'computed_at' => now()],
        );

        $vendor->forceFill(['reputation_score' => $score])->save();

        // Top-Rated tier depends on the score → re-evaluate.
        $this->tiers->recompute($vendor);

        return $score;
    }

    /**
     * @return array<string, int|null>
     */
    public function components(Vendor $vendor): array
    {
        return [
            'rating'     => $this->ratingComponent($vendor),
            'response'   => $this->responseComponent($vendor),
            'conversion' => $this->conversionComponent($vendor),
            'disputes'   => $this->disputesComponent($vendor),
            'quality'    => $this->qualityComponent($vendor),
        ];
    }

    /** @param array<string, int|null> $components */
    private function weightedScore(array $components): int
    {
        $weights = (array) config('verification.reputation.weights', []);
        $sum = 0.0;
        $wTotal = 0.0;

        foreach ($components as $key => $value) {
            if ($value === null) {
                continue;
            }
            $w = (float) ($weights[$key] ?? 0);
            $sum += $w * $value;
            $wTotal += $w;
        }

        return $wTotal > 0 ? (int) round($sum / $wTotal) : 0;
    }

    /** Avg listing rating (0–5) × 20, across rated products + vehicles. Null if none rated. */
    private function ratingComponent(Vendor $vendor): ?int
    {
        $ratings = [];
        foreach (['products', 'vehicles'] as $rel) {
            $avg = $vendor->{$rel}()->where('review_count', '>', 0)->avg('rating');
            if ($avg !== null) {
                $ratings[] = (float) $avg;
            }
        }

        if ($ratings === []) {
            return null;
        }

        return (int) round(min(100, (array_sum($ratings) / count($ratings)) * 20));
    }

    /** Share of leads acted on (anything but "new"). Null if no leads. */
    private function responseComponent(Vendor $vendor): ?int
    {
        $total = $this->leads($vendor)->count();
        if ($total === 0) {
            return null;
        }

        $responded = $this->leads($vendor)->where('status', '!=', 'new')->count();

        return (int) round($responded / $total * 100);
    }

    /** Share of leads that converted. Null if no leads. */
    private function conversionComponent(Vendor $vendor): ?int
    {
        $total = $this->leads($vendor)->count();
        if ($total === 0) {
            return null;
        }

        $converted = $this->leads($vendor)->where('status', 'converted')->count();

        return (int) round($converted / $total * 100);
    }

    /** 100 minus the cancel/refund rate. Null if no orders. */
    private function disputesComponent(Vendor $vendor): ?int
    {
        $total = DB::table('orders')->where('vendor_id', $vendor->id)->count();
        if ($total === 0) {
            return null;
        }

        $bad = DB::table('orders')->where('vendor_id', $vendor->id)
            ->whereIn('status', ['cancelled', 'refunded'])->count();

        return (int) round((1 - $bad / $total) * 100);
    }

    /** Share of active listings with both a description and an image. Null if none. */
    private function qualityComponent(Vendor $vendor): ?int
    {
        $active = $vendor->products()->where('status', 'active')->count()
            + $vendor->vehicles()->where('status', 'active')->count();

        if ($active === 0) {
            return null;
        }

        $complete = $vendor->products()->where('status', 'active')
                ->whereNotNull('description')->where('description', '!=', '')
                ->whereHas('images')->count()
            + $vendor->vehicles()->where('status', 'active')
                ->whereNotNull('description')->where('description', '!=', '')
                ->whereHas('images')->count();

        return (int) round($complete / $active * 100);
    }

    private function leads(Vendor $vendor)
    {
        return DB::table('leads')->where('vendor_id', $vendor->id);
    }
}
