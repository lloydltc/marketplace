<?php

namespace App\Modules\Delivery\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RiderCashSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'rider_id',
        'session_date',
        'expected_total',
        'collected_total',
        'status',
        'reconciled_by',
        'reconciled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date'    => 'date',
            'expected_total'  => 'decimal:2',
            'collected_total' => 'decimal:2',
            'reconciled_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $session) {
            if (empty($session->id)) {
                $session->id = (string) Str::uuid();
            }
        });
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'cash_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isReconciled(): bool
    {
        return $this->status === 'reconciled';
    }
}
