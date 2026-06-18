<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentProfile extends Model
{
    use HasFactory;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'licence_number',
        'licence_expiry',
        'territory',
        'commission_rate',
        'bio',
    ];

    protected function casts(): array
    {
        return [
            'licence_expiry'  => 'date',
            'commission_rate' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $profile) {
            if (empty($profile->id)) {
                $profile->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
