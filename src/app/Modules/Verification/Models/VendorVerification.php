<?php

namespace App\Modules\Verification\Models;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * VB1/VB2: an admin decision on one verification dimension for a vendor, with
 * optional expiry for re-verification.
 */
class VendorVerification extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id', 'dimension', 'status', 'evidence_ref',
        'notes', 'verified_by', 'verified_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $v) {
            if (empty($v->id)) {
                $v->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** Approved and not past its expiry. */
    public function isValid(): bool
    {
        return $this->status === 'approved'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
