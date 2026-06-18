<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VendorDocument extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'vendor_id',
        'document_type',
        'file_path',
        'original_filename',
        'status',
        'rejection_reason',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $doc) {
            if (empty($doc->id)) {
                $doc->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function labelForType(): string
    {
        return match ($this->document_type) {
            'business_registration' => 'Business Registration',
            'tax_id'                => 'Tax ID Certificate',
            'bank_proof'            => 'Bank Account Proof',
            'id_copy'               => 'Director ID Copy',
            'address_proof'         => 'Proof of Address',
            default                 => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }
}
