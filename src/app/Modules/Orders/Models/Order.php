<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Models\Vendor;
use App\Modules\Orders\Events\OrderCompletedEvent;
use App\Modules\Orders\Exceptions\IllegalOrderTransitionException;
use App\Modules\Orders\Services\OrderStateMachine;
use App\Modules\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_number',
        'buyer_user_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'buyer_address',
        'buyer_city',
        'vendor_id',
        'fulfilment_track',
        'payment_method',
        'status',
        'currency',
        'subtotal',
        'delivery_fee',
        'total',
        'commission_rate_applied',
        'commission_amount',
        'net_to_vendor',
        'paid_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'settled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'                => 'decimal:2',
            'delivery_fee'            => 'decimal:2',
            'total'                   => 'decimal:2',
            'commission_rate_applied' => 'decimal:2',
            'commission_amount'       => 'decimal:2',
            'net_to_vendor'           => 'decimal:2',
            'paid_at'                 => 'datetime',
            'delivered_at'            => 'datetime',
            'completed_at'            => 'datetime',
            'cancelled_at'            => 'datetime',
            'settled_at'              => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $order) {
            if (empty($order->id)) {
                $order->id = (string) Str::uuid();
            }
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'SD-' . strtoupper(Str::random(8));
        } while (self::where('order_number', $number)->exists());

        return $number;
    }

    // ─── Relationships ──────────────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function delivery(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\Modules\Delivery\Models\Delivery::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    public function isPrepaid(): bool
    {
        return $this->payment_method === 'prepaid';
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'refunded'], true);
    }

    /**
     * Idempotent: only the first call moves the order into the paid state.
     */
    public function markPaid(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        $this->update(['status' => 'paid', 'paid_at' => now()]);

        return true;
    }

    public function markFailed(): void
    {
        if (! $this->isPaid()) {
            $this->update(['status' => 'failed']);
        }
    }

    // ─── State machine (Phase 12) ───────────────────────────────────────────────

    /**
     * @return string[]
     */
    public function allowedTransitions(): array
    {
        return app(OrderStateMachine::class)->allowedTransitions($this);
    }

    public function canTransitionTo(string $status): bool
    {
        return app(OrderStateMachine::class)->canTransition($this, $status);
    }

    /**
     * Move the order to $status, rejecting any move the machine forbids.
     * Reaching "completed" emits the settlement event exactly once.
     */
    public function transitionTo(string $status, ?string $reason = null): void
    {
        if (! $this->canTransitionTo($status)) {
            throw new IllegalOrderTransitionException(
                "Order {$this->order_number} cannot move from '{$this->status}' to '{$status}'."
            );
        }

        $attrs = ['status' => $status];

        if ($status === 'delivered') {
            $attrs['delivered_at'] = now();
        }
        if ($status === 'completed') {
            $attrs['completed_at'] = now();
        }
        if ($status === 'cancelled') {
            $attrs['cancelled_at']        = now();
            $attrs['cancellation_reason'] = $reason;
        }

        $this->update($attrs);

        if ($status === 'completed') {
            $this->emitSettlement();
        }
    }

    /**
     * Fire the settlement event once (guarded by settled_at).
     */
    private function emitSettlement(): void
    {
        if ($this->settled_at !== null) {
            return;
        }

        $this->forceFill(['settled_at' => now()])->save();

        OrderCompletedEvent::dispatch($this);
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForBuyer(Builder $query, string $userId): Builder
    {
        return $query->where('buyer_user_id', $userId);
    }

    public function scopeForVendor(Builder $query, string $vendorId): Builder
    {
        return $query->where('vendor_id', $vendorId);
    }
}
