<?php

namespace App\Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** AC1: a user's on/off choice for one (notification type × channel). */
class NotificationPreference extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['user_id', 'type', 'channel', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            if (empty($p->id)) {
                $p->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
