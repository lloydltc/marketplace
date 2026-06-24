<?php

namespace App\Modules\Moderation\Services;

use App\Models\ListingReport;
use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;

/**
 * H11: deterministic, rule-based auto-flagging (no AI). Scans live listings and
 * opens a moderation report when a rule trips. Each (listing, reason) gets at
 * most one open auto-report, so re-running the scan never duplicates.
 */
class ModerationRuleService
{
    /** @return int number of new auto-reports created */
    public function scan(): int
    {
        $created = 0;

        Vehicle::query()->where('status', 'active')->chunkById(200, function ($vehicles) use (&$created) {
            foreach ($vehicles as $vehicle) {
                $created += $this->applyRules($vehicle, $vehicle->displayTitle() . ' ' . $vehicle->description, (float) $vehicle->price_usd, true);
            }
        });

        Product::query()->where('status', 'active')->chunkById(200, function ($products) use (&$created) {
            foreach ($products as $product) {
                $created += $this->applyRules($product, $product->title . ' ' . $product->description, (float) $product->price_usd, false);
            }
        });

        return $created;
    }

    private function applyRules(Model $listing, string $text, float $priceUsd, bool $isVehicle): int
    {
        $created = 0;

        // Rule 1: banned keyword in title/description.
        if ($keyword = $this->matchedKeyword($text)) {
            $created += $this->flag($listing, 'prohibited', "Auto-flag: contains banned keyword \"{$keyword}\".");
        }

        // Rule 2 (vehicles only): implausibly low price → likely scam or typo.
        $floor = (int) config('moderation.auto.min_reasonable_vehicle_usd', 0);
        if ($isVehicle && $floor > 0 && $priceUsd > 0 && $priceUsd <= $floor) {
            $created += $this->flag($listing, 'scam', "Auto-flag: price USD {$priceUsd} is below the plausibility floor ({$floor}).");
        }

        return $created;
    }

    private function matchedKeyword(string $text): ?string
    {
        $haystack = mb_strtolower($text);

        foreach (config('moderation.auto.banned_keywords', []) as $keyword) {
            $keyword = mb_strtolower(trim($keyword));
            if ($keyword !== '' && str_contains($haystack, $keyword)) {
                return $keyword;
            }
        }

        return null;
    }

    /** Open an auto-report unless an identical open one already exists. */
    private function flag(Model $listing, string $reason, string $note): int
    {
        $exists = $listing->reports()
            ->where('source', 'auto')
            ->where('reason', $reason)
            ->where('status', 'open')
            ->exists();

        if ($exists) {
            return 0;
        }

        $listing->reports()->create([
            'source' => 'auto',
            'reason' => $reason,
            'note'   => $note,
            'status' => 'open',
        ]);

        return 1;
    }
}
