<?php

namespace App\Modules\Parts\Models;

use App\Modules\Products\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** PM6: one component (offering + qty) of a bundle. */
class PartBundleItem extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['bundle_id', 'product_id', 'qty'];

    protected function casts(): array
    {
        return ['qty' => 'integer'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (empty($i->id)) {
                $i->id = (string) Str::uuid();
            }
        });
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(PartBundle::class, 'bundle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
