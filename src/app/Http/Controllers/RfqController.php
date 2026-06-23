<?php

namespace App\Http\Controllers;

use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;
use App\Modules\Rfq\Services\DepositService;
use App\Modules\Rfq\Services\RfqService;
use App\Modules\Rfq\Services\RfqThresholdService;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer-facing RFQ ("Request a Part"). The full lifecycle from BUSINESS_MODEL.md
 * §6 Tier 1: post a request → vendors quote → accept a quote → converts to a
 * normal order (standard commission engine applies).
 */
class RfqController extends Controller
{
    public function __construct(
        private readonly RfqService $rfq,
        private readonly RfqThresholdService $thresholds,
        private readonly DepositService $deposits
    ) {}

    /** Public intent-capture form (linked from zero-results search). */
    public function create(Request $request, VehicleMakeRepositoryInterface $makes): View
    {
        return view('rfq.create', [
            'prefill' => trim((string) $request->input('q', '')),
            'makes'   => $makes->allWithModels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'part_description' => ['required', 'string', 'max:1000'],
            'location'         => ['required', 'string', 'max:120'],
            'make_id'          => ['nullable', 'exists:vehicle_makes,id'],
            'model_id'         => ['nullable', 'exists:vehicle_models,id'],
            'year'             => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'budget_min'       => ['nullable', 'numeric', 'min:0'],
            'budget_max'       => ['nullable', 'numeric', 'min:0'],
            'estimated_value'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $partRequest = $this->rfq->createRequest($request->user(), $validated);

        // D6: an RFQ is a lead (buyer reaching the market for a part).
        app(\App\Modules\Leads\Services\LeadService::class)->record('rfq', $partRequest, [
            'buyer'   => $request->user(),
            'message' => $validated['part_description'],
            'ip'      => $request->ip(),
        ]);

        // High-value requests require a refundable commitment deposit (only when
        // thresholds are enabled — off at launch).
        if ($this->thresholds->requiresDeposit((float) ($validated['estimated_value'] ?? 0))) {
            $deposit = $this->deposits->initiate(
                $partRequest,
                returnUrl: route('rfq.show', $partRequest),
                resultUrl: route('payments.webhook'),
            );

            if (! empty($deposit->redirect_url)) {
                return redirect()->away($deposit->redirect_url);
            }
        }

        return redirect()->route('rfq.show', $partRequest)
            ->with('status', 'Your request is live — vendors can now send quotes.');
    }

    public function index(Request $request): View
    {
        $requests = PartRequest::with('quotes')
            ->where('buyer_user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('rfq.index', compact('requests'));
    }

    public function show(Request $request, PartRequest $partRequest): View
    {
        $this->authorizeBuyer($request, $partRequest);

        $partRequest->load(['quotes.vendor', 'make', 'vehicleModel', 'deposit']);

        return view('rfq.show', ['request' => $partRequest]);
    }

    public function accept(Request $request, PartRequest $partRequest, Quote $quote): RedirectResponse
    {
        $this->authorizeBuyer($request, $partRequest);
        abort_unless($quote->part_request_id === $partRequest->id, 404);

        if (! $partRequest->isOpenForQuotes() || ! $quote->isActive()) {
            return back()->withErrors(['rfq' => 'This request can no longer be converted.']);
        }

        // Display-only gating: can't convert a quote from an unverified seller (R4).
        if ($quote->vendor !== null && ! $quote->vendor->canTransact()) {
            return back()->withErrors(['rfq' => 'This seller is still being verified and cannot be transacted with yet.']);
        }

        $customer = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:160'],
            'phone'     => ['required', 'string', 'max:30'],
            'address'   => ['required', 'string', 'max:255'],
            'city'      => ['required', 'string', 'max:80'],
        ]);

        $order = $this->rfq->acceptQuote($partRequest, $quote, $customer);

        // Hand off to the existing order payment flow.
        $request->session()->put('checkout.orders', [$order->id]);

        return redirect()->route('checkout.complete')
            ->with('status', 'Quote accepted — complete payment to confirm your order.');
    }

    public function close(Request $request, PartRequest $partRequest): RedirectResponse
    {
        $this->authorizeBuyer($request, $partRequest);

        $this->rfq->close($partRequest);

        return back()->with('status', 'Request closed.');
    }

    private function authorizeBuyer(Request $request, PartRequest $partRequest): void
    {
        abort_unless($partRequest->buyer_user_id === $request->user()->id, 403);
    }
}
