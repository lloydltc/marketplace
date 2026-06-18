<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorBankAccount extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'account_number',
        'bank_name',
        'account_holder',
        'branch_code',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at'    => 'datetime',
            // P3: bank account number is encrypted at rest (transparent to the app).
            'account_number' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $account) {
            if (empty($account->id)) {
                $account->id = (string) Str::uuid();
            }
        });

        // Maintain a deterministic HMAC of the (decrypted) account number so we can
        // still enforce per-vendor uniqueness even though the stored value is
        // encrypted (and therefore non-deterministic).
        static::saving(function (self $account) {
            $account->account_number_hash = $account->account_number !== null
                ? hash_hmac('sha256', $account->account_number, (string) config('app.key'))
                : null;
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function maskedAccountNumber(): string
    {
        $len = strlen($this->account_number);

        return str_repeat('*', max(0, $len - 4)) . substr($this->account_number, -4);
    }
}
