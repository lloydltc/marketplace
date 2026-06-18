<?php

namespace App\Modules\Settings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A single admin-editable platform setting. Values are stored as strings and
 * cast on read by {@see \App\Modules\Settings\Services\SettingsService}
 * according to the `type` column. Never read these rows directly for fee
 * logic — always go through the cached service.
 *
 * @property string $key
 * @property ?string $value
 * @property string $type
 * @property string $group
 * @property ?string $description
 */
class PlatformSetting extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'updated_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $setting) {
            if (empty($setting->id)) {
                $setting->id = (string) Str::uuid();
            }
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
