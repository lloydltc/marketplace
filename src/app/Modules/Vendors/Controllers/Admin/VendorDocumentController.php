<?php

namespace App\Modules\Vendors\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Modules\Vendors\Requests\Admin\ReviewDocumentRequest;
use App\Modules\Vendors\Services\VendorDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorDocumentController extends Controller
{
    public function __construct(
        private readonly VendorDocumentService $documentService
    ) {}

    public function index(Vendor $vendor): View
    {
        $vendor->load('documents');

        return view('admin.vendors.documents', compact('vendor'));
    }

    public function review(ReviewDocumentRequest $request, Vendor $vendor, VendorDocument $document): RedirectResponse
    {
        abort_if($document->vendor_id !== $vendor->id, 404);

        if ($request->validated('action') === 'approve') {
            $this->documentService->approve($document, $request->user());
        } else {
            $this->documentService->reject(
                $document,
                $request->user(),
                $request->validated('rejection_reason')
            );
        }

        return back()->with('status', 'Document reviewed.');
    }
}
