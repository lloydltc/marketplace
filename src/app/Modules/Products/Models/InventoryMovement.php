<?php

namespace App\Modules\Products\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM2: one auditable stock change on an offering (product). */
class InventoryMovement extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'type',
        'qty',
        'balance_after',
        'reference',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'qty'           => 'integer',
            'balance_after' => 'integer',
            'created_at'    => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->id)) {
                $m->id = (string) Str::uuid();
            }
            if (empty($m->created_at)) {
                $m->created_at = now();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
