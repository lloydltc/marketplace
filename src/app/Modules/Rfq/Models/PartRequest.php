<?php

namespace App\Modules\Rfq\Models;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Vehicles\Models\VehicleMake;
use App\Modules\Vehicles\Models\VehicleModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PartRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'buyer_user_id',
        'make_id',
        'model_id',
        'year',
        'part_description',
        'budget_min',
        'budget_max',
        'location',
        'estimated_value',
        'status',
        'moderation_status',
        'accepted_quote_id',
        'converted_order_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'year'            => 'integer',
            'budget_min'      => 'decimal:2',
            'budget_max'      => 'decimal:2',
            'estimated_value' => 'decimal:2',
            'expires_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            if (empty($request->id)) {
                $request->id = (string) Str::uuid();
            }
        });
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }

    public function deposit(): HasMany
    {
        return $this->hasMany(RfqDeposit::class);
    }

    public function paidDeposit(): ?RfqDeposit
    {
        return $this->deposit()->where('status', 'paid')->first();
    }

    // ─── Scopes / state ─────────────────────────────────────────────────────────

    public function scopeOpenForQuotes(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'quoted'])
            ->where('moderation_status', 'approved');
    }

    public function isOpenForQuotes(): bool
    {
        return in_array($this->status, ['open', 'quoted'], true)
            && $this->moderation_status === 'approved';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }
}
