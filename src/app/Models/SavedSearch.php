<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property string $type  "products" | "vehicles"
 * @property array<string, mixed> $query_params
 */
class SavedSearch extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'query_params',
    ];

    protected function casts(): array
    {
        return [
            'query_params' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $search) {
            if (empty($search->id)) {
                $search->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The route the saved search re-runs against.
     */
    public function url(): string
    {
        $route = $this->type === 'vehicles' ? 'vehicles.index' : 'products.index';

        return route($route, $this->query_params ?? []);
    }
}
