<?php

namespace App\Modules\Verification\Models;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * VB3: a vendor's computed reputation snapshot.
 *
 * @property int $score
 * @property array<string, int|null> $components
 */
class VendorReputation extends Model
{
    protected $table = 'vendor_reputation';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['vendor_id', 'score', 'components', 'computed_at'];

    protected function casts(): array
    {
        return ['score' => 'integer', 'components' => 'array', 'computed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (empty($r->id)) {
                $r->id = (string) Str::uuid();
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
