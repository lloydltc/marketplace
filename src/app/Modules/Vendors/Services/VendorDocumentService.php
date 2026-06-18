<?php

namespace App\Modules\Vendors\Services;

use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VendorDocumentService
{
    /**
     * Store an uploaded document for a vendor.
     */
    public function upload(Vendor $vendor, UploadedFile $file, string $documentType): VendorDocument
    {
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $directory = "vendor-docs/{$vendor->id}";
        $path      = $file->storeAs($directory, $filename, 'public');

        return $vendor->documents()->create([
            'document_type'     => $documentType,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'status'            => 'pending',
        ]);
    }

    /**
     * Approve a document (admin action).
     */
    public function approve(VendorDocument $document, User $admin): void
    {
        $document->update(['status' => 'approved', 'rejection_reason' => null]);

        Log::info('Vendor document approved', [
            'document_id' => $document->id,
            'vendor_id'   => $document->vendor_id,
            'by'          => $admin->id,
        ]);
    }

    /**
     * Reject a document with a reason (admin action).
     */
    public function reject(VendorDocument $document, User $admin, string $reason): void
    {
        $document->update(['status' => 'rejected', 'rejection_reason' => $reason]);

        Log::info('Vendor document rejected', [
            'document_id' => $document->id,
            'vendor_id'   => $document->vendor_id,
            'by'          => $admin->id,
        ]);
    }

    /**
     * Delete a document and its stored file.
     */
    public function delete(VendorDocument $document): void
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();
    }
}
