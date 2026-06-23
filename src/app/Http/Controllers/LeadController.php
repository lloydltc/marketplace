<?php

namespace App\Http\Controllers;

use App\Modules\Leads\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * D6: lead views — a light per-seller CRM (private seller + vendor) and the
 * site-wide admin funnel. Each surface is scoped server-side to the owner.
 */
class LeadController extends Controller
{
    /** Private seller — leads on their own listings. */
    public function sellerIndex(Request $request): View
    {
        $leads = Lead::with('subject', 'buyer')
            ->forSeller($request->user()->id)
            ->latest()->paginate(25);

        return view('leads.index', [
            'leads'       => $leads,
            'updateRoute' => 'seller.leads.update',
            'heading'     => 'Leads',
            'funnel'      => null,
        ]);
    }

    /** Vendor — leads on the vendor's listings. */
    public function vendorIndex(Request $request): View
    {
        $vendor = $request->attributes->get('vendor');
        abort_if($vendor === null, 404);

        $leads = Lead::with('subject', 'buyer')
            ->forVendor($vendor->id)
            ->latest()->paginate(25);

        return view('leads.index', [
            'leads'       => $leads,
            'updateRoute' => 'vendor.leads.update',
            'heading'     => 'Leads',
            'funnel'      => null,
        ]);
    }

    /** Admin — all leads + a simple conversion funnel. */
    public function adminIndex(Request $request): View
    {
        $query = Lead::with('subject', 'buyer');
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $funnel = [
            'total'     => Lead::count(),
            'contacted' => Lead::whereIn('status', ['contacted', 'converted'])->count(),
            'converted' => Lead::where('status', 'converted')->count(),
        ];

        return view('leads.index', [
            'leads'       => $query->latest()->paginate(30)->withQueryString(),
            'updateRoute' => 'admin.leads.update',
            'heading'     => 'All Leads',
            'funnel'      => $funnel,
        ]);
    }

    /** Update a lead's status / notes — owner (seller/vendor) or admin only. */
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorizeOwner($request, $lead);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Lead::STATUSES)],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $lead->update($validated);

        return back()->with('status', 'Lead updated.');
    }

    private function authorizeOwner(Request $request, Lead $lead): void
    {
        $user = $request->user();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return;
        }
        if ($user->hasRole('private_seller') && $lead->seller_user_id === $user->id) {
            return;
        }
        $vendor = $request->attributes->get('vendor');
        if ($vendor !== null && $lead->vendor_id === $vendor->id) {
            return;
        }

        abort(403);
    }
}
