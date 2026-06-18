<?php

namespace App\Modules\Media\Models;

use App\Modules\Vehicles\Models\Vehicle;
use Database\Factories\VehicleImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VehicleImage extends Model
{
    use HasFactory;

    protected static function newFactory(): VehicleImageFactory
    {
        return VehicleImageFactory::new();
    }

    public $incrementing = false;

    protected $keyType = 'string';

    public const VIEW_TYPES = ['front', 'side', 'back', 'interior', 'other'];

    protected $fillable = [
        'vehicle_id',
        'disk',
        'original_path',
        'medium_path',
        'thumb_path',
        'view_type',
        'width',
        'height',
        'file_size',
        'display_order',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
            'width'         => 'integer',
            'height'        => 'integer',
            'file_size'     => 'integer',
            'display_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $image) {
            if (empty($image->id)) {
                $image->id = (string) Str::uuid();
            }
        });
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->original_path);
    }

    public function mediumUrl(): string
    {
        return $this->medium_path
            ? Storage::disk($this->disk)->url($this->medium_path)
            : $this->url();
    }

    public function thumbUrl(): string
    {
        return $this->thumb_path
            ? Storage::disk($this->disk)->url($this->thumb_path)
            : $this->url();
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }
}
