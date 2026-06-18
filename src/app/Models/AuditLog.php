<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Append-only record of a privileged action (R6). Write via {@see AuditLog::record()};
 * rows are never updated or deleted.
 */
class AuditLog extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null; // append-only — no updated_at

    protected $fillable = [
        'actor_id',
        'actor_role',
        'action',
        'target_type',
        'target_id',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $log) {
            if (empty($log->id)) {
                $log->id = (string) Str::uuid();
            }
        });

        // Hard guarantee of immutability — block updates/deletes at the model layer.
        static::updating(fn () => throw new \RuntimeException('Audit logs are append-only.'));
        static::deleting(fn () => throw new \RuntimeException('Audit logs cannot be deleted.'));
    }

    /**
     * Record a privileged action. `$actor` is the user performing it; `$target`
     * may be any model (its type/id are captured) or null for global actions.
     */
    public static function record(
        ?User $actor,
        string $action,
        ?Model $target = null,
        array $metadata = [],
        ?string $ip = null,
    ): self {
        return static::create([
            'actor_id'    => $actor?->id,
            'actor_role'  => $actor?->role,
            'action'      => $action,
            'target_type' => $target ? $target::class : null,
            'target_id'   => $target?->getKey(),
            'metadata'    => $metadata ?: null,
            'ip_address'  => $ip ?? request()?->ip(),
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
