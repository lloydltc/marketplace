<?php

namespace App\Http\Controllers;

use App\Modules\Concierge\Models\ConciergeRequest;
use App\Modules\Concierge\Services\ConciergeService;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Vehicles\Repositories\VehicleMakeRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buyer side of Concierge: submit a "find it for me" request, track it, and pay
 * once the admin has quoted.
 */
class ConciergeController extends Controller
{
    public function __construct(private readonly ConciergeService $concierge) {}

    public function create(Request $request, VehicleMakeRepositoryInterface $makes): View
    {
        return view('concierge.create', ['makes' => $makes->allWithModels()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'part_description' => ['required', 'string', 'max:1000'],
            'location'         => ['required', 'string', 'max:120'],
            'make_id'          => ['nullable', 'exists:vehicle_makes,id'],
            'model_id'         => ['nullable', 'exists:vehicle_models,id'],
            'year'             => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $conciergeRequest = ConciergeRequest::create($validated + [
            'buyer_user_id' => $request->user()->id,
            'status'        => 'new',
        ]);

        return redirect()->route('concierge.show', $conciergeRequest)
            ->with('status', 'Your concierge request is in — our team will source it and send you a quote.');
    }

    public function index(Request $request): View
    {
        $requests = ConciergeRequest::where('buyer_user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('concierge.index', compact('requests'));
    }

    public function show(Request $request, ConciergeRequest $conciergeRequest): View
    {
        $this->authorizeBuyer($request, $conciergeRequest);

        return view('concierge.show', ['request' => $conciergeRequest]);
    }

    public function pay(Request $request, ConciergeRequest $conciergeRequest): RedirectResponse
    {
        $this->authorizeBuyer($request, $conciergeRequest);

        if (! $conciergeRequest->isAwaitingPayment()) {
            return back()->withErrors(['payment' => 'This request is not awaiting payment.']);
        }

        try {
            $this->concierge->initiatePayment(
                $conciergeRequest,
                returnUrl: route('concierge.show', $conciergeRequest),
                resultUrl: route('payments.webhook'),
            );
        } catch (PaymentGatewayException) {
            return back()->withErrors(['payment' => 'Could not start payment. Please try again.']);
        }

        if (empty($conciergeRequest->redirect_url)) {
            return back()->withErrors(['payment' => 'The gateway did not return a payment link.']);
        }

        return redirect()->away($conciergeRequest->redirect_url);
    }

    private function authorizeBuyer(Request $request, ConciergeRequest $conciergeRequest): void
    {
        abort_unless($conciergeRequest->buyer_user_id === $request->user()->id, 403);
    }
}
