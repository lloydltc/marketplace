<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->id)) {
                $user->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'tier',
        'force_password_change',
        'email_otp',
        'email_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_otp',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'email_otp_expires_at'  => 'datetime',
            'password'              => 'hashed',
            'force_password_change' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        // Generate OTP synchronously so it is in the DB immediately.
        // The job only sends the email (async is fine for that part).
        $otp = $this->generateEmailOtp();

        dispatch(new \App\Jobs\Mail\SendVerifyEmailJob($this, $otp));
    }

    public function generateEmailOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->updateQuietly([
            'email_otp'            => $otp,
            'email_otp_expires_at' => now()->addMinutes(10),
        ]);

        return $otp;
    }

    public function isOtpValid(string $otp): bool
    {
        return $this->email_otp === $otp
            && $this->email_otp_expires_at !== null
            && $this->email_otp_expires_at->isFuture();
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_users')
            ->withPivot(['vendor_role', 'invited_at', 'joined_at'])
            ->withTimestamps();
    }

    public function getVendorAttribute(): ?Vendor
    {
        return $this->vendors()->first();
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(AgentProfile::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isVendorAdmin(): bool
    {
        return $this->role === 'vendor_admin';
    }

    public function isVendorWorker(): bool
    {
        return $this->role === 'vendor_worker';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function belongsToVendor(string $vendorId): bool
    {
        return $this->vendors()->where('vendors.id', $vendorId)->exists();
    }

    /** The pivot vendor_role ('admin'|'worker') this user holds at a vendor, or null. */
    public function pivotRoleFor(Vendor $vendor): ?string
    {
        $member = $this->vendors()->where('vendors.id', $vendor->id)->first();

        return $member?->pivot?->vendor_role;
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium';
    }

    public function isUnverified(): bool
    {
        return $this->tier === 'unverified';
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}
