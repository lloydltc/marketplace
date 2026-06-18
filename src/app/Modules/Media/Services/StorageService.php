<?php

namespace App\Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageService
{
    public function disk(): string
    {
        return config('filesystems.default', 'local') === 's3' ? 's3' : 'public';
    }

    /**
     * Store under a randomised UUID filename with a safe, server-derived
     * extension. The client's original filename/extension is never trusted.
     */
    public function store(UploadedFile $file, string $directory, ?string $extension = null): string
    {
        $filename = Str::uuid() . '.' . ($extension ?: 'jpg');
        $path     = $directory . '/' . $filename;

        Storage::disk($this->disk())->putFileAs(
            $directory,
            $file,
            $filename,
        );

        return $path;
    }

    public function storePath(string $sourcePath, string $contents, string $newPath): void
    {
        Storage::disk($this->disk())->put($newPath, $contents);
    }

    public function delete(string $path): void
    {
        Storage::disk($this->disk())->delete($path);
    }

    public function url(string $path): string
    {
        return Storage::disk($this->disk())->url($path);
    }

    public function get(string $path): string
    {
        return Storage::disk($this->disk())->get($path);
    }
}
