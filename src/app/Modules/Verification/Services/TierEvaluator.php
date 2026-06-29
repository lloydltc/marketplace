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
