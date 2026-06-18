<?php

namespace App\Modules\Rfq\Models;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Quote extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'part_request_id',
        'vendor_id',
        'submitted_by',
        'price',
        'condition',
        'delivery_estimate',
        'notes',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price'      => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $quote) {
            if (empty($quote->id)) {
                $quote->id = (string) Str::uuid();
            }
        });
    }

    public function partRequest(): BelongsTo
    {
        return $this->belongsTo(PartRequest::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
