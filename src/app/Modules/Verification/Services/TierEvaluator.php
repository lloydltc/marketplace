<?php

namespace App\Modules\Verification\Services;

use App\Models\Vendor;

/**
 * VB1: computes which trust-badge tiers a vendor has earned, from approved+unexpired
 * verification dimensions, reputation score, and any manual grant. Config-driven —
 * no tier rule is hardcoded. The "primary" tier is the highest-ranked earned one.
 */
class TierEvaluator
{
    /**
     * All tier keys the vendor currently qualifies for.
     *
     * @return list<string>
     */
    public function earnedTiers(Vendor $vendor): array
    {
        // VB4: a revoked badge suppresses all tiers until reinstated.
        if ($vendor->isBadgeRevoked()) {
            return [];
        }

        $approved = $vendor->validVerificationDimensions();   // list<string>
        // Cached reputation score column (populated in VB3); absent → 0.
        $reputation = (int) ($vendor->reputation_score ?? 0);
        $earned = [];

        foreach ((array) config('verification.tiers', []) as $key => $def) {
            if (! empty($def['manual_only'])) {
                if ($vendor->manual_tier === $key) {
                    $earned[] = $key;
                }
                continue;
            }

            $required = $def['required_dimensions'] ?? [];
            if (array_diff($required, $approved) !== []) {
                continue; // missing a required dimension
            }

            if (($def['min_reputation'] ?? null) !== null && $reputation < $def['min_reputation']) {
                continue;
            }

            $earned[] = $key;
        }

        return $earned;
    }

    /** The highest-ranked earned tier key, or null. */
    public function primaryTier(Vendor $vendor): ?string
    {
        $tiers = config('verification.tiers', []);

        $best = null;
        $bestRank = -1;
        foreach ($this->earnedTiers($vendor) as $key) {
            $rank = (int) ($tiers[$key]['rank'] ?? 0);
            if ($rank > $bestRank) {
                $bestRank = $rank;
                $best = $key;
            }
        }

        return $best;
    }

    /**
     * VB5: a vendor-facing progress summary — per-dimension status, earned tiers,
     * and what's still needed for the next (lowest-rank, non-manual) tier.
     *
     * @return array{current: ?string, earned: list<string>, dimensions: array<string, string>, next: ?array}
     */
    public function progress(Vendor $vendor): array
    {
        $approved = $vendor->validVerificationDimensions();
        $byDimension = $vendor->verifications()->pluck('status', 'dimension')->all();

        $dimensions = [];
        foreach (config('verification.dimensions', []) as $dim) {
            $dimensions[$dim] = in_array($dim, $approved, true) ? 'approved' : ($byDimension[$dim] ?? 'missing');
        }

        $earned = $this->earnedTiers($vendor);
        $reputation = (int) ($vendor->reputation_score ?? 0);

        // Next tier = lowest-rank non-manual tier not yet earned.
        $candidates = collect(config('verification.tiers', []))
            ->reject(fn ($def, $key) => ! empty($def['manual_only']) || in_array($key, $earned, true))
            ->sortBy('rank');

        $next = null;
        foreach ($candidates as $key => $def) {
            $next = [
                'tier'                => $key,
                'label'               => $def['label'],
                'missing_dimensions'  => array_values(array_diff($def['required_dimensions'] ?? [], $approved)),
                'needs_reputation'    => ($def['min_reputation'] ?? null) !== null && $reputation < $def['min_reputation']
                    ? $def['min_reputation'] : null,
            ];
            break;
        }

        return ['current' => $vendor->verification_tier, 'earned' => $earned, 'dimensions' => $dimensions, 'next' => $next];
    }

    /** Recompute and persist the vendor's primary badge tier. Returns it. */
    public function recompute(Vendor $vendor): ?string
    {
        $tier = $this->primaryTier($vendor);

        if ($vendor->verification_tier !== $tier) {
            $vendor->forceFill(['verification_tier' => $tier])->save();
        }

        return $tier;
    }
}
