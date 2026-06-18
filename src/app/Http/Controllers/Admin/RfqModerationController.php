<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Rfq\Models\PartRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RfqModerationController extends Controller
{
    public function index(): View
    {
        $requests = PartRequest::with('buyer')->latest()->paginate(30);

        return view('admin.rfq.index', compact('requests'));
    }

    public function reject(PartRequest $partRequest): RedirectResponse
    {
        $partRequest->update(['moderation_status' => 'rejected', 'status' => 'closed']);

        return back()->with('status', 'Request rejected and closed.');
    }
}
