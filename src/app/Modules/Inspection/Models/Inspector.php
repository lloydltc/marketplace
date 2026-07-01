<?php

namespace App\Modules\Inspection\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** TI3: a vetted inspector on the panel. */
class Inspector extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'name', 'kind', 'coverage_area', 'phone', 'email', 'rating', 'review_count', 'is_active'];

    protected function casts(): array
    {
        return ['rating' => 'decimal:2', 'review_count' => 'integer', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $i) {
            if (empty($i->id)) {
                $i->id = (string) Str::uuid();
            }
        });
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }
}
