<?php

namespace App\Modules\Verification\Services;

use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * VB4: deterministic, rule-based fraud detection (NO AI). Opens auto moderation
 * reports (reusing H11 listing_reports) for:
 *   - duplicate/stolen photos: the same image hash on listings of >1 distinct owner;
 *   - rapid relist: one owner posting many same-title active listings in a window.
 * Idempotent — one open auto-report per (listing, reason).
 */
class FraudRuleService
{
    /** @return int auto-reports opened */
    public function scan(): int
    {
        return $this->duplicatePhotoScan() + $this->rapidRelistScan();
    }

    private function duplicatePhotoScan(): int
    {
        // Collect (hash, listing) with an owner key, from both image tables.
        $rows = collect();

        foreach ([
            ['table' => 'vehicle_images', 'fk' => 'vehicle_id', 'listings' => 'vehicles', 'class' => Vehicle::class],
            ['table' => 'product_images', 'fk' => 'product_id', 'listings' => 'products', 'class' => Product::class],
        ] as $src) {
            $records = DB::table("{$src['table']} as img")
                ->join("{$src['listings']} as l", 'l.id', '=', "img.{$src['fk']}")
                ->whereNotNull('img.image_hash')
                ->whereNull('l.deleted_at')
                ->get([
                    'img.image_hash as hash',
                    "img.{$src['fk']} as listing_id",
                    'l.vendor_id as vendor_id',
                    DB::raw($src['class'] === Vehicle::class ? 'l.user_id as seller_id' : 'NULL as seller_id'),
                ]);

            foreach ($records as $r) {
                $rows->push([
                    'hash'       => $r->hash,
                    'class'      => $src['class'],
                    'listing_id' => $r->listing_id,
                    'owner'      => $r->vendor_id ?? $r->seller_id ?? 'unknown',
                ]);
            }
        }

        $created = 0;
        foreach ($rows->groupBy('hash') as $group) {
            if ($group->pluck('owner')->unique()->count() < 2) {
                continue; // same owner reusing their own photo is fine
            }
            foreach ($group->unique(fn ($r) => $r['class'] . $r['listing_id']) as $r) {
                $listing = $r['class']::find($r['listing_id']);
                if ($listing) {
                    $created += $this->flag($listing, 'duplicate', 'Auto-flag: image also used by another seller (possible stolen photo).');
                }
            }
        }

        return $created;
    }

    private function rapidRelistScan(): int
    {
        $threshold = max(2, (int) config('verification.fraud.rapid_relist_threshold', 4));
        $window = now()->subHours((int) config('verification.fraud.rapid_relist_window_hours', 24));
        $created = 0;

        // Products: group active by vendor + lower(title) within the window.
        $dupes = DB::table('products')
            ->where('status', 'active')->whereNull('deleted_at')
            ->whereNotNull('vendor_id')->where('created_at', '>=', $window)
            ->selectRaw('vendor_id, lower(title) as t, count(*) as c')
            ->groupBy('vendor_id', DB::raw('lower(title)'))
            ->havingRaw('count(*) >= ?', [$threshold])
            ->get();

        foreach ($dupes as $d) {
            Product::where('vendor_id', $d->vendor_id)->whereRaw('lower(title) = ?', [$d->t])
                ->where('status', 'active')->where('created_at', '>=', $window)
                ->get()->each(function ($p) use (&$created) {
                    $created += $this->flag($p, 'duplicate', 'Auto-flag: rapid relisting of the same item.');
                });
        }

        return $created;
    }

    /** Open one auto-report per (listing, reason), reusing H11 listing_reports. */
    private function flag(Model $listing, string $reason, string $note): int
    {
        $exists = $listing->reports()
            ->where('source', 'auto')->where('reason', $reason)->where('status', 'open')->exists();

        if ($exists) {
            return 0;
        }

        $listing->reports()->create(['source' => 'auto', 'reason' => $reason, 'note' => $note, 'status' => 'open']);

        return 1;
    }
}
