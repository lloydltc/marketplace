<?php

namespace App\Modules\Vendors\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Requests\Vendor\StoreVendorDocumentRequest;
use App\Modules\Vendors\Services\VendorDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        private readonly VendorDocumentService $documentService
    ) {}

    public function index(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $vendor->load('documents');

        return view('vendor.documents.index', compact('vendor'));
    }

    public function store(StoreVendorDocumentRequest $request): RedirectResponse
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $this->documentService->upload(
            $vendor,
            $request->file('document'),
            $request->validated('document_type')
        );

        return redirect()
            ->route('vendor.documents.index')
            ->with('status', 'Document uploaded successfully. Awaiting admin review.');
    }
}
