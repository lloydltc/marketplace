<?php

namespace App\Http\Controllers;

use App\Modules\Orders\Models\Order;
use App\Modules\Payments\Exceptions\PaymentGatewayException;
use App\Modules\Payments\Models\Payment;
use App\Modules\Concierge\Models\ConciergeRequest;
use App\Modules\Concierge\Services\ConciergeService;
use App\Modules\Payments\Services\PaymentService;
use App\Modules\Payments\Services\PesepayClient;
use App\Modules\Promotions\Models\PromotionPurchase;
use App\Modules\Promotions\Services\PromotionService;
use App\Modules\Rfq\Models\RfqDeposit;
use App\Modules\Rfq\Services\DepositService;
use App\Modules\Wallet\Models\WalletTopUp;
use App\Modules\Wallet\Services\TopUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly PesepayClient $gateway,
        private readonly TopUpService $topUps,
        private readonly DepositService $deposits,
        private readonly ConciergeService $concierge,
        private readonly PromotionService $promotions
    ) {}

    /**
     * Start a gateway payment for a prepaid order and redirect to the hosted page.
     */
    public function initiate(Request $request, Order $order): RedirectResponse
    {
        if (! $order->isPrepaid() || ! $order->isAwaitingPayment()) {
            return redirect()->route('checkout.complete')
                ->with('status', 'This order is not awaiting payment.');
        }

        try {
            $payment = $this->payments->initiate(
                $order,
                returnUrl: route('payments.return', ['order' => $order->id]),
                resultUrl: route('payments.webhook'),
            );
        } catch (PaymentGatewayException $e) {
            Log::warning('Pesepay initiation failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['payment' => 'Could not start payment. Please try again.']);
        }

        if (empty($payment->redirect_url)) {
            return back()->withErrors(['payment' => 'The gateway did not return a payment link.']);
        }

        return redirect()->away($payment->redirect_url);
    }

    /**
     * Seamless mobile-money payment (EcoCash / InnBucks). No redirect — the buyer
     * approves on their phone, then lands on the status page which polls the result.
     */
    public function seamless(Request $request, Order $order): RedirectResponse
    {
        if (! $order->isPrepaid() || ! $order->isAwaitingPayment()) {
            return redirect()->route('checkout.complete')
                ->with('status', 'This order is not awaiting payment.');
        }

        $validated = $request->validate([
            'method' => ['required', 'in:ecocash,innbucks'],
            'phone'  => ['required_if:method,ecocash', 'nullable', 'string', 'max:30'],
        ]);

        try {
            $this->payments->initiateSeamless(
                $order,
                $validated['method'],
                $validated['phone'] ?? null,
                returnUrl: route('payments.return', ['order' => $order->id]),
                resultUrl: route('payments.webhook'),
            );
        } catch (PaymentGatewayException $e) {
            Log::warning('Pesepay seamless failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['payment' => 'Could not start the mobile payment. Please try again.']);
        }

        return redirect()->route('payments.return', ['order' => $order->id])
            ->with('status', 'Check your phone to approve the payment.');
    }

    /**
     * Browser return from the gateway. We NEVER trust the redirect alone — the
     * authoritative status comes from a server-side re-check (and the webhook).
     */
    public function return(Request $request, Order $order): View|RedirectResponse
    {
        $payment = $order->payments()->latest()->first();

        if ($payment && $payment->gateway_ref && ! $payment->isTerminal()) {
            try {
                $status = $this->gateway->checkStatus($payment->gateway_ref);
                $this->payments->applyGatewayStatus(
                    $payment,
                    (string) ($status['transactionStatus'] ?? ''),
                );
            } catch (PaymentGatewayException $e) {
                Log::warning('Pesepay status re-check failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return view('payments.return', ['order' => $order->fresh()]);
    }

    /**
     * Gateway-to-server webhook (resultUrl). Signed implicitly by the encryption
     * key, and processed idempotently so replays move no money twice.
     */
    public function webhook(Request $request): JsonResponse
    {
        $raw = (string) $request->input('payload');

        if ($raw === '') {
            return response()->json(['message' => 'No payload.'], 422);
        }

        try {
            $decoded = $this->gateway->decrypt($raw);
        } catch (PaymentGatewayException) {
            // Undecryptable payload = failed signature check.
            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        $reference = (string) ($decoded['referenceNumber'] ?? '');
        $status    = (string) ($decoded['transactionStatus'] ?? '');

        // An order payment?
        $payment = Payment::where('gateway_ref', $reference)->first();
        if ($payment !== null) {
            $this->payments->applyGatewayStatus($payment, $status, $raw);

            return response()->json(['message' => 'Processed.']);
        }

        // A vendor wallet top-up?
        $topUp = WalletTopUp::where('gateway_ref', $reference)->first();
        if ($topUp !== null) {
            $this->topUps->confirm($topUp, $status, $raw);

            return response()->json(['message' => 'Processed.']);
        }

        // An RFQ commitment deposit?
        $deposit = RfqDeposit::where('gateway_ref', $reference)->first();
        if ($deposit !== null) {
            $this->deposits->confirm($deposit, $status, $raw);

            return response()->json(['message' => 'Processed.']);
        }

        // A concierge order?
        $concierge = ConciergeRequest::where('gateway_ref', $reference)->first();
        if ($concierge !== null) {
            $this->concierge->confirmPayment($concierge, $status, $raw);

            return response()->json(['message' => 'Processed.']);
        }

        // A listing-promotion purchase?
        $promotion = PromotionPurchase::where('gateway_ref', $reference)->first();
        if ($promotion !== null) {
            $this->promotions->confirm($promotion, $status, $raw);

            return response()->json(['message' => 'Processed.']);
        }

        // Unknown reference — acknowledge so the gateway stops retrying.
        return response()->json(['message' => 'Ignored.']);
    }
}
