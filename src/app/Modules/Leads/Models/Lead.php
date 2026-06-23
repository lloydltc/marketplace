<?php

namespace App\Modules\Leads\Models;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * D6: a single buyer→seller contact event on a listing (vehicle or product).
 */
class Lead extends Model
{
    public const STATUSES = ['new', 'contacted', 'converted', 'lost'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'type', 'subject_type', 'subject_id', 'seller_user_id', 'vendor_id',
        'buyer_user_id', 'contact_name', 'contact_phone', 'contact_email',
        'message', 'source', 'status', 'notes', 'ip_address',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            if (empty($lead->id)) {
                $lead->id = (string) Str::uuid();
            }
        });
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function sellerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** Leads for a private seller's own listings. */
    public function scopeForSeller(Builder $q, string $userId): Builder
    {
        return $q->where('seller_user_id', $userId);
    }

    /** Leads for a vendor's listings. */
    public function scopeForVendor(Builder $q, string $vendorId): Builder
    {
        return $q->where('vendor_id', $vendorId);
    }

    public function isGuest(): bool
    {
        return $this->buyer_user_id === null;
    }
}
